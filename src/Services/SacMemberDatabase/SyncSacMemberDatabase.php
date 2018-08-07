<?php

declare(strict_types=1);

/**
 * SAC Event Tool Web Plugin for Contao
 * Copyright (c) 2008-2017 Marko Cupic
 * @package sac-event-tool-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2017-2018
 * @link https://sac-kurse.kletterkader.com
 * @link http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html
 */

namespace Markocupic\SacEventToolBundle\Services\SacMemberDatabase;


use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Date;
use Contao\File;
use Contao\Input;
use Contao\System;
use Doctrine\DBAL\Connection;

/**
 * Class SyncSacMemberDatabase
 * @package Markocupic\SacEventToolBundle\Services\SacMemberDatabase
 */
class SyncSacMemberDatabase
{
    /**
     * Log type for new member
     */
    const SAC_EVT_LOG_ADD_NEW_MEMBER = 'ADD_NEW_MEMBER';

    /**
     * Log type for a successfull sync
     */
    const SAC_EVT_LOG_SAC_MEMBER_DATABASE_SYNC = 'MEMBER_DATABASE_SYNC';

    /**
     * Log type if a member has been disabled
     */
    const SAC_EVT_LOG_DISABLE_MEMBER = 'DISABLE_MEMBER';

    /**
     * @var
     */
    private $framework;

    /**
     * @var
     */
    private $connection;

    /**
     * @var
     */
    private $section_ids;

    /**
     * @var
     */
    private $ftp_hostname;

    /**
     * @var
     */
    private $ftp_username;

    /**
     * @var
     */
    private $ftp_password;

    /**
     *
     */
    private $root_dir;


    /**
     * SyncSacMemberDatabase constructor.
     * @param ContaoFrameworkInterface $framework
     * @param Connection $connection
     * @param $root_dir
     */
    public function __construct(ContaoFrameworkInterface $framework, Connection $connection, $root_dir)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->root_dir = $root_dir;
        $this->ftp_hostname = Config::get('SAC_EVT_FTPSERVER_MEMBER_DB_BERN_HOSTNAME');
        $this->ftp_username = (string)Config::get('SAC_EVT_FTPSERVER_MEMBER_DB_BERN_USERNAME');
        $this->ftp_password = (string)Config::get('SAC_EVT_FTPSERVER_MEMBER_DB_BERN_PASSWORD');
        $this->section_ids = explode(',', Config::get('SAC_EVT_SAC_SECTION_IDS'));

    }


    /**
     * @return $this
     * @throws \Exception
     */
    public function loadDataFromFtp()
    {


        // Open FTP connection
        $connId = $this->openFtpConnection();

        foreach ($this->section_ids as $sectionId)
        {
            $localFile = $this->root_dir . '/system/tmp/Adressen_0000' . $sectionId . '.csv';
            $remoteFile = 'Adressen_0000' . $sectionId . '.csv';

            if ($this->downloadFileFromFtp($connId, $localFile, $remoteFile))
            {
                // Write csv file to the tmp folder
            }
            else
            {
                $this->log('Error during SAC member database sync. Could not open FTP connection.',
                    __FILE__ . ' Line: ' . __LINE__,
                    TL_ERROR
                );
                throw new \Exception("Tried to open FTP connection.");
            }
        }
        \ftp_close($connId);

        return $this;
    }

    /**
     * @return resource
     * @throws \Exception
     */
    private function openFtpConnection()
    {
        $connId = \ftp_connect($this->ftp_hostname);
        if (!\ftp_login($connId, $this->ftp_username, $this->ftp_password) || !$connId)
        {
            throw new \Exception('Could not establish ftp connection.');
        }
        return $connId;
    }

    /**
     * Download files from ftp server
     * @param $connId
     * @param $localFile
     * @param $remoteFile
     * @return bool
     * @throws \Exception
     */
    private function downloadFileFromFtp($connId, $localFile, $remoteFile)
    {
        $connId = \ftp_get($connId, $localFile, $remoteFile, FTP_BINARY);
        if (!$connId)
        {
            throw new \Exception('Could not download files from ftp server.');
        }
        return $connId;
    }

    /**
     * @param $text
     * @param $method
     * @param $type
     */
    private function log($text, $method, $type): void
    {
        $adapter = $this->framework->getAdapter(System::class);
        $adapter->log($text, $method, $type);
    }

    /**
     * @return $this
     */
    public function syncContaoDatabase()
    {
        $startTime = \time();

        $statement = $this->connection->query('SELECT sacMemberId FROM tl_member');
        $arrMemberIDS = $statement->fetchAll(\PDO::FETCH_COLUMN);

        $arrMember = array();
        foreach ($this->section_ids as $sectionId)
        {

            $objFile = new File('system/tmp/Adressen_0000' . $sectionId . '.csv');
            if ($objFile !== null)
            {
                $arrFile = $objFile->getContentAsArray();
                foreach ($arrFile as $line)
                {
                    // End of line
                    if (\strpos($line, '* * * Dateiende * * *') !== false)
                    {
                        continue;
                    }

                    $arrLine = \explode('$', $line);
                    $set = array();
                    $set['sacMemberId'] = \intval($arrLine[0]);
                    $set['username'] = \intval($arrLine[0]);
                    // Mehrere Sektionsmitgliedschaften möglich
                    $set['sectionId'] = array((string)ltrim($arrLine[1], '0'));
                    $set['lastname'] = $arrLine[2];
                    $set['firstname'] = $arrLine[3];
                    $set['addressExtra'] = $arrLine[4];
                    $set['street'] = trim($arrLine[5]);
                    $set['streetExtra'] = $arrLine[6];
                    $set['postal'] = $arrLine[7];
                    $set['city'] = $arrLine[8];
                    $set['country'] = \strtolower($arrLine[9]) == '' ? 'ch' : \strtolower($arrLine[9]);
                    $set['dateOfBirth'] = \strtotime($arrLine[10]);
                    $set['phoneBusiness'] = $arrLine[11];
                    $set['phone'] = $arrLine[12];
                    $set['mobile'] = $arrLine[14];
                    $set['fax'] = $arrLine[15];
                    $set['email'] = $arrLine[16];
                    $set['gender'] = \strtolower($arrLine[17]) == 'weiblich' ? 'female' : 'male';
                    $set['profession'] = $arrLine[18];
                    $set['language'] = \strtolower($arrLine[19]) == 'd' ? 'de' : \strtolower($arrLine[19]);
                    $set['entryYear'] = $arrLine[20];
                    $set['membershipType'] = $arrLine[23];
                    $set['sectionInfo1'] = $arrLine[24];
                    $set['sectionInfo2'] = $arrLine[25];
                    $set['sectionInfo3'] = $arrLine[26];
                    $set['sectionInfo4'] = $arrLine[27];
                    $set['debit'] = $arrLine[28];
                    $set['memberStatus'] = $arrLine[29];
                    $set['tstamp'] = \time();
                    $set['disable'] = '';
                    $set['isSacMember'] = '1';


                    $set = \array_map(function ($value) {
                        if (!\is_array($value))
                        {
                            $value = \is_string($value) ? \trim($value) : $value;
                            $value = \is_string($value) ? \utf8_encode($value) : $value;
                            return $value;
                        }
                        return $value;

                    }, $set);

                    // Check if the member is already in the array
                    if (isset($arrMember[$set['sacMemberId']]))
                    {
                        $arrMember[$set['sacMemberId']]['sectionId'] = \array_merge($arrMember[$set['sacMemberId']]['sectionId'], $set['sectionId']);
                    }
                    else
                    {
                        $arrMember[$set['sacMemberId']] = $set;
                    }
                }
            }
        }

        // Set tl_member.isSacMember to ''
        $this->connection->executeUpdate('UPDATE tl_member SET isSacMember = ?', array(''));

        // Start transaction (big thank to cyon.ch)
        $this->connection->beginTransaction();
        try
        {
            $i = 0;
            foreach ($arrMember as $sacMemberId => $arrValues)
            {
                $arrValues['sectionId'] = \serialize($arrValues['sectionId']);

                if (!in_array($sacMemberId, $arrMemberIDS))
                {
                    // Add new user
                    $this->connection->insert('tl_member', $arrValues);
                    $this->log(
                        \sprintf('Insert new SAC-member with SAC-User-ID: %s to tl_member.', $arrValues['sacMemberId']),
                        __FILE__ . ' Line: ' . __LINE__,
                        self::SAC_EVT_LOG_ADD_NEW_MEMBER
                    );
                }
                else
                {
                    // Sync datarecord
                    $this->connection->update('tl_member', $arrValues, array('sacMemberId' => $sacMemberId));
                }

                // Log, if sync has finished without errors (max script execution time!!!!)
                $i++;
            }
            $this->connection->commit();

        } catch (\Exception $e)
        {
            $this->log(
                'Error during the database sync process. Starting transaction rollback, now.',
                __FILE__ . ' Line: ' . __LINE__,
                self::SAC_EVT_LOG_SAC_MEMBER_DATABASE_SYNC
            );
            //transaction rollback
            $this->connection->rollBack();
            throw $e;
        }


        // Set tl_member.disable to true if member was not found in the csv-file
        $statement = $this->connection->executeQuery('SELECT * FROM tl_member WHERE disable=? AND isSacMember=?', array('', ''));
        while (false !== ($objDisabledMember = $statement->fetch(\PDO::FETCH_OBJ)))
        {
            $arrSet = array(
                'tstamp'  => \time(),
                'disable' => '1',
            );
            $this->connection->update('tl_member', $arrSet, array('id' => $objDisabledMember->id));

            // Log
            $this->log(
                \sprintf('Disable SAC-Member "%s %s" SAC-User-ID: %s during the sync process. The user can not be found in the SAC main database from Bern.', $objDisabledMember->firstname, $objDisabledMember->lastname, $objDisabledMember->sacMemberId),
                __FILE__ . ' Line: ' . __LINE__,
                self::SAC_EVT_LOG_DISABLE_MEMBER
            );
        }

        if ($i == count($arrMember))
        {
            $duration = \time() - $startTime;

            // Log
            $this->log(
                'Finished syncing SAC member database with tl_member. Synced ' . \count($arrMember) . ' entries. Duration: ' . $duration . ' s',
                __FILE__ . ' Line: ' . __LINE__,
                self::SAC_EVT_LOG_SAC_MEMBER_DATABASE_SYNC
            );
        }

        return $this;
    }
}