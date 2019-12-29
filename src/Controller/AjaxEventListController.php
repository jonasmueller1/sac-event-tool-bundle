<?php

declare(strict_types=1);

/**
 * SAC Event Tool Web Plugin for Contao
 * Copyright (c) 2008-2020 Marko Cupic
 * @package sac-event-tool-bundle
 * @author Marko Cupic m.cupic@gmx.ch, 2017-2020
 * @link https://github.com/markocupic/sac-event-tool-bundle
 */

namespace Markocupic\SacEventToolBundle\Controller;

use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendUser;
use Markocupic\SacEventToolBundle\Services\FrontendAjax\FrontendAjax;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AjaxEventListController
 * @package Markocupic\SacEventToolBundle\Controller
 */
class AjaxEventListController extends AbstractController
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var string
     */
    private $tokenName;

    /**
     * AjaxEventListController constructor.
     * Handles ajax requests.
     * Allow if ...
     * - is XmlHttpRequest
     * - csrf token is valid
     * @param ContaoFramework $framework
     * @param CsrfTokenManagerInterface $tokenManager
     * @param RequestStack $requestStack
     * @param Security $security
     * @param string $tokenName
     * @throws \Exception
     */

    public function __construct(ContaoFramework $framework, CsrfTokenManagerInterface $tokenManager, RequestStack $requestStack, Security $security, string $tokenName)
    {
        $this->framework = $framework;
        $this->tokenManager = $tokenManager;
        $this->requestStack = $requestStack;
        $this->security = $security;
        $this->tokenName = $tokenName;

        $this->framework->initialize();

        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        // Validate request token
        if (!$this->tokenManager->isTokenValid(new CsrfToken($this->tokenName, $request->get('REQUEST_TOKEN'))))
        {
            throw new InvalidRequestTokenException('Invalid CSRF token. Please reload the page and try again.');
        }

        // Do allow only xhr requests
        if (!$request->isXmlHttpRequest())
        {
            throw $this->createNotFoundException('The route "/ajax" is allowed to XMLHttpRequest requests only.');
        }
    }

    /**
     * @Route("/ajaxEventList/getEventData", name="sac_event_tool_ajax_event_list_get_event_data", defaults={"_scope" = "frontend"})
     */
    public function getEventDataAction()
    {
        /** @var  FrontendAjax $controller */
        $controller = $this->container->get('Markocupic\SacEventToolBundle\Services\FrontendAjax\FrontendAjax');

        $controller->getEventData();

        exit();
    }

}
