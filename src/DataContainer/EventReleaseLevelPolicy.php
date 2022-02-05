<?php

declare(strict_types=1);

/*
 * This file is part of SAC Event Tool Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-event-tool-bundle
 */

namespace Markocupic\SacEventToolBundle\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventReleaseLevelPolicy
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }



    /**
     * @Callback(table="tl_event_release_level_policy", target="list.sorting.child_record")
     */
    public function listCalendars(array $arrRow): string
    {
        return '<div class="tl_content_left"><span class="level">' . $this->translator->trans('MSC.level', [], 'contao_default') . ': '.$arrRow['level'].'</span> '.$arrRow['title']."</div>\n";
    }
}
