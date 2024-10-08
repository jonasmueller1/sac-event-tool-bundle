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

$GLOBALS['TL_DCA']['tl_event_filter_form'] = [
    'config' => [
        // This table is only a fake.
        // The dca structure is used to generate form fields
        // with the form class of contao haste.
        'dataContainer' => DC_Table::class,
    ],
    'fields' => [
        'year'                 => [
            'inputType' => 'select',
            'options'   => range(2016, (int)date('Y') + 1),
            'eval'      => ['includeBlankOption' => true, 'blankOptionLabel' => &$GLOBALS['TL_LANG']['tl_event_filter_form']['blankOptionLabel']],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'dateStart'            => [
            'inputType' => 'text',
            'eval'      => ['placeholder' => 'yyyy-mm-dd', 'maxlength' => 12],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'tourType'             => [
            'inputType'  => 'select',
            'relation'   => ['type' => 'hasOne', 'load' => 'eager'],
            'foreignKey' => 'tl_tour_type.title',
            'eval'       => ['multiple' => true, 'includeBlankOption' => true, 'blankOptionLabel' => &$GLOBALS['TL_LANG']['tl_event_filter_form']['showAll']],
            'sql'        => "varchar(10) NOT NULL default ''",
        ],
        'courseType'           => [
            'inputType' => 'select',
            'eval'      => ['multiple' => true, 'includeBlankOption' => true, 'blankOptionLabel' => &$GLOBALS['TL_LANG']['tl_event_filter_form']['showAll']],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'organizers'           => [
            'inputType' => 'select',
            'eval'      => ['multiple' => true],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'textSearch'           => [
            'inputType' => 'text',
            'eval'      => ['placeholder' => &$GLOBALS['TL_LANG']['tl_event_filter_form']['enterSearchTerms']],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'eventId'              => [
            'inputType' => 'text',
            'eval'      => ['placeholder' => date('Y').'-****'],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'courseId'             => [
            'inputType' => 'text',
            'eval'      => ['placeholder' => &$GLOBALS['TL_LANG']['tl_event_filter_form']['courseId'][0]],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'suitableForBeginners' => [
            'inputType' => 'checkbox',
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'publicTransportEvent' => [
            'inputType' => 'checkbox',
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'favoredEvent'         => [
            'inputType' => 'checkbox',
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
    ],
];
