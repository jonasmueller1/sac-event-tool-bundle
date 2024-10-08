<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Tool Bundle.
 *
 * (c) Marko Cupic 2024 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-tool-bundle
 */

use Contao\DC_Table;
use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_user_role'] = [
    'config'   => [
        'dataContainer'    => DC_Table::class,
        'doNotCopyRecords' => true,
        'enableVersioning' => true,
        'switchToEdit'     => true,
        'sql'              => [
            'keys' => [
                'id'  => 'primary',
                'pid' => 'index',
            ],
        ],
    ],
    'list'     => [
        'sorting'           => [
            'mode'        => DataContainer::MODE_TREE,
            'fields'      => ['title', 'email'],
            'format'      => '%s %s',
            'panelLayout' => 'filter;search,limit',
        ],
        'label'             => [
            'fields'      => ['title', 'email'],
            'showColumns' => true,
        ],
        'global_operations' => [
            'all',
        ],
    ],
    'palettes' => [
        'default' => 'title,belongsToExecutiveBoard,belongsToBeauftragteStammsektion;{address_legend},street,postal,city,phone,mobile,email',
    ],
    'fields'   => [
        'id'                               => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid'                              => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'sorting'                          => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'tstamp'                           => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'title'                            => [
            'exclude'   => true,
            'search'    => true,
            'sorting'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'clr'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'belongsToExecutiveBoard'          => [
            'exclude'   => true,
            'filter'    => true,
            'sorting'   => true,
            'inputType' => 'checkbox',
            'eval'      => ['mandatory' => false, 'tl_class' => 'clr'],
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'belongsToBeauftragteStammsektion' => [
            'exclude'   => true,
            'filter'    => true,
            'sorting'   => true,
            'inputType' => 'checkbox',
            'eval'      => ['mandatory' => false, 'tl_class' => 'clr'],
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'email'                            => [
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'inputType' => 'text',
            'eval'      => ['assignTo' => 'tl_user.email', 'mandatory' => false, 'maxlength' => 255, 'rgxp' => 'email', 'unique' => false, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'street'                           => [
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['assignTo' => 'tl_user.street', 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'postal'                           => [
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['assignTo' => 'tl_user.postal', 'maxlength' => 32, 'tl_class' => 'w50'],
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'city'                             => [
            'exclude'   => true,
            'filter'    => true,
            'search'    => true,
            'sorting'   => true,
            'inputType' => 'text',
            'eval'      => ['assignTo' => 'tl_user.city', 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'phone'                            => [
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['assignTo' => 'tl_user.phone', 'maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'mobile'                           => [
            'exclude'   => true,
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['assignTo' => 'tl_user.mobile', 'maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
    ],
];
