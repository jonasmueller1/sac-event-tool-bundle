<?php

/**
 * SAC Event Tool Web Plugin for Contao
 * Copyright (c) 2008-2019 Marko Cupic
 * @package sac-event-tool-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2017-2019
 * @link https://github.com/markocupic/sac-event-tool-bundle
 */

namespace Markocupic\SacEventToolBundle\Controller;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Date;
use Contao\Environment;
use Contao\Input;
use Contao\System;
use Markocupic\SacEventToolBundle\Services\Docx\ExportEvents2Docx;
use Markocupic\SacEventToolBundle\Services\Pdf\PrintWorkshopsAsPdf;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DownloadController extends AbstractController
{


    /**
     * Download workshops as pdf booklet
     * /_download/print_workshop_booklet_as_pdf?year=2019&cat=0
     * /_download/print_workshop_booklet_as_pdf?year=current&cat=0
     * @Route("/_download/print_workshop_booklet_as_pdf", name="sac_event_tool_download_print_workshop_booklet_as_pdf", defaults={"_scope" = "frontend", "_token_check" = false})
     */
    public function printWorkshopBookletAsPdfAction()
    {
        $this->container->get('contao.framework')->initialize();

        // Für Downloads workshops as pdf booklet
        if (Input::get('year') != '')
        {

            $year = Input::get('year');

            if (Input::get('year') === 'current')
            {
                $year = Date::parse('Y', time());
            }

            // Log download
            $container = System::getContainer();
            $logger = $container->get('monolog.logger.contao');
            $logger->log(LogLevel::INFO, 'The course booklet has been downloaded.', array('contao' => new ContaoContext(__FILE__ . ' Line: ' . __LINE__, Config::get('SAC_EVT_LOG_COURSE_BOOKLET_DOWNLOAD'))));

            $filenamePattern = str_replace('%%s', '%s', Config::get('SAC_EVT_WORKSHOP_FLYER_SRC'));
            $fileSRC = sprintf($filenamePattern, $year);

            Controller::sendFileToBrowser($fileSRC, false);
        }
        exit();
    }

    /**
     * Download events as docx file
     * /_download/print_workshop_details_as_docx?calendarId=6&year=2017
     * /_download/print_workshop_details_as_docx?calendarId=6&year=2017&eventId=89
     * @Route("/_download/print_workshop_details_as_docx", name="sac_event_tool_download_print_workshop_details_as_docx", defaults={"_scope" = "frontend", "_token_check" = false})
     */
    public function printWorkshopDetailsAsDocxAction()
    {
        $this->container->get('contao.framework')->initialize();

        if (Input::get('year') && Input::get('calendarId'))
        {
            ExportEvents2Docx::sendToBrowser(Input::get('calendarId'), Input::get('year'), Input::get('eventId'));
        }
        exit();
    }

    /**
     * Download workshop details as pdf
     * /_download/print_workshop_details_as_pdf?eventId=643
     * @Route("/_download/print_workshop_details_as_pdf", name="sac_event_tool_download_print_workshop_details_as_pdf", defaults={"_scope" = "frontend", "_token_check" = false})
     */
    public function printWorkshopDetailsAsPdfAction()
    {
        $this->container->get('contao.framework')->initialize();

        $objPrint = new PrintWorkshopsAsPdf(0, 0, Input::get('eventId'), true);
        $objPrint->printWorkshopsAsPdf();
        exit();
    }

    /**
     * The defaultAction has to be at the bottom of the class
     * Handles download requests.
     * @Route("/_download/{slug}", name="sac_event_tool_download", defaults={"_scope" = "frontend", "_token_check" = false})
     */
    public function defaultAction($slug = '')
    {
        $this->container->get('contao.framework')->initialize();
        echo sprintf('Welcome to %s::%s. You have called the Service with this route: _download/%s', __CLASS__, __FUNCTION__, $slug);
        exit();
    }
}