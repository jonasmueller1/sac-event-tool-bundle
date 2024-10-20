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

namespace Markocupic\SacEventToolBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\PageModel;
use Contao\UserModel;
use Markocupic\SacEventToolBundle\Util\CalendarEventsUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(UserPortraitController::TYPE, category:'sac_event_tool_content_elements', template:'ce_user_portrait')]
class UserPortraitController extends AbstractContentElementController
{
    public const TYPE = 'user_portrait';

    public function __construct(
        private readonly ContaoFramework $framework,
    ) {
    }

    public function __invoke(Request $request, ContentModel $model, string $section, array|null $classes = null, PageModel|null $pageModel = null): Response
    {
        return parent::__invoke($request, $model, $section, $classes);
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $userModelAdapter = $this->framework->getAdapter(UserModel::class);
        $calendarEventsUtilAdapter = $this->framework->getAdapter(CalendarEventsUtil::class);

        $user = null;

        if ($request->query->has('username')) {
            $username = $request->query->get('username');
            $user = $userModelAdapter->findByUsername($username);
        }

        // Do not display the profile of a disabled or deleted user.
        if (null === $user || $user->disable || ('' !== $user->start && $user->start > time()) || ('' !== $user->stop && $user->stop < time()) || ('' !== $user->start && $user->start > time())) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $arrUser = $user->row();
        $arrUser['mainQualification'] = $calendarEventsUtilAdapter->getMainQualification($user);
        $template->set('user', $arrUser);
        $template->set('userModel', $user);

        return $template->getResponse();
    }
}
