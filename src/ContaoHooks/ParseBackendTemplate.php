<?php

/**
 * SAC Event Tool Web Plugin for Contao
 * Copyright (c) 2008-2017 Marko Cupic
 * @package sac-event-tool-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2017
 * @link    https://sac-kurse.kletterkader.com
 */

namespace Markocupic\SacEventToolBundle\ContaoHooks;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Controller;
use Contao\Input;

class ParseBackendTemplate
{

    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }


    /**
     * @param $strBuffer
     * @param $strTemplate
     * @return mixed
     */
    public function parseBackendTemplate($strBuffer, $strTemplate)
    {
        if ($strTemplate === 'be_main')
        {
            // Add icon explanation legend to tl_calendar_events_member
            if (Input::get('do') === 'sac_calendar_events_tool' && Input::get('table') === 'tl_calendar_events_member')
            {
                if (preg_match('/<table class=\"tl_listing(.*)<\/table>/sU', $strBuffer))
                {
                    Controller::loadDataContainer('tl_calendar_events_member');
                    Controller::loadLanguageFile('tl_calendar_events_member');
                    $strLegend = '<div class="legend-box">';
                    $strLegend .= '<div class="subscription-state-legend">';
                    $strLegend .= '<h3>Status der Event-Anmeldung</h3>';
                    $strLegend .= '<ul>';
                    $arrStates = $GLOBALS['TL_DCA']['tl_calendar_events_member']['fields']['stateOfSubscription']['options'];
                    foreach ($arrStates as $state)
                    {
                        $strLegend .= sprintf('<li><img src="%s/icons/%s.svg" width="16" height="16"> %s</li>', SAC_EVT_ASSETS_DIR, $state, $GLOBALS['TL_LANG']['tl_calendar_events_member'][$state]);
                    }
                    $strLegend .= '</ul>';
                    $strLegend .= '</div>';

                    $strLegend .= '<div class="participation-state-legend">';
                    $strLegend .= '<h3>Teilnahmestatus <span style="color:red">(Erst nach der Event-Durchf&uuml;hrung auszuf&uuml;llen!)</span></h3>';
                    $strLegend .= '<ul>';
                    $strLegend .= sprintf('<li><img src="%s/icons/%s.svg" width="16" height="16"> %s</li>', SAC_EVT_ASSETS_DIR, 'has-not-participated', 'Hat am Event nicht/noch nicht teilgenommen');
                    $strLegend .= sprintf('<li><img src="%s/icons/%s.svg" width="16" height="16"> %s</li>', SAC_EVT_ASSETS_DIR, 'has-participated', 'Hat am Event teilgenommen');
                    $strLegend .= '</ul>';
                    $strLegend .= '</div>';

                    $strLegend .= '</div>';

                    // Add legend to the listing table
                    $strBuffer = preg_replace('/<table class=\"tl_listing(.*)<\/table>/sU', '${0}' . $strLegend, $strBuffer);
                }

            }

            // Do not show submit container in the e-mail mode of tl_calendar_events_member
            if (Input::get('do') === 'sac_calendar_events_tool' && Input::get('table') === 'tl_calendar_events_member' && (Input::get('call') === 'refuseWithEmail' || Input::get('call') === 'acceptWithEmail'))
            {
                if (preg_match('/<div class=\"tl_formbody_submit(.*)<\/form>/sU', $strBuffer))
                {
                    // Remove submit tl_formbody_submit
                    $strBuffer = preg_replace('/<div class=\"tl_formbody_submit(.*)<\/form>/sU', '</form>', $strBuffer);
                }
            }
        }

        return $strBuffer;
    }
}