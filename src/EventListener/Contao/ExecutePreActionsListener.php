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

namespace Markocupic\SacEventToolBundle\EventListener\Contao;

use Contao\BackendTemplate;
use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Date;
use Contao\MemberModel;
use Contao\StringUtil;
use Contao\Validator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Safe\Exceptions\JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsHook('executePreActions', priority: 100)]
class ExecutePreActionsListener
{
    // Adapters
    private Adapter $backendUserAdapter;
    private Adapter $configAdapter;
    private Adapter $dateAdapter;
    private Adapter $memberModelAdapter;
    private Adapter $stringUtilAdapter;
    private Adapter $validatorAdapter;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
    ) {
        // Adapters
        $this->backendUserAdapter = $this->framework->getAdapter(BackendUser::class);
        $this->configAdapter = $this->framework->getAdapter(Config::class);
        $this->dateAdapter = $this->framework->getAdapter(Date::class);
        $this->memberModelAdapter = $this->framework->getAdapter(MemberModel::class);
        $this->stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
        $this->validatorAdapter = $this->framework->getAdapter(Validator::class);
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function __invoke(string $strAction = ''): void
    {
        // Get current request
        $request = $this->requestStack->getCurrentRequest();

        // Get suggested user data when registering manually a new event member in the Contao backend!
        if ('autocompleterLoadMemberDataFromSacMemberId' === $strAction) {
            // Output
            $json = ['status' => 'error'];
            $objMemberModel = $this->memberModelAdapter->findOneBySacMemberId($request->request->get('sacMemberId'));

            if (null !== $objMemberModel) {
                $json = $objMemberModel->row();
                $json['name'] = $json['firstname'].' '.$json['lastname'];
                $json['username'] = str_replace(' ', '', strtolower($json['name']));
                $json['dateOfBirth'] = $this->dateAdapter->parse($this->configAdapter->get('dateFormat'), $json['dateOfBirth']);
                $json['status'] = 'success';

                // Bin to hex uuids
                $json['avatar'] = $this->validatorAdapter->isBinaryUuid($json['avatar']) ? $this->stringUtilAdapter->binToUuid($json['avatar']) : '';
                $json['password'] = '';
                $json['sectionId'] = $this->stringUtilAdapter->deserialize($objMemberModel->sectionId, true);

                $html = '<div>';
                $html .= '<h1>Mitglied gefunden</h1>';
                $html .= '<div>Sollen die Daten von %s %s übernommen werden?</div>';
                $html .= '<button class="tl_button">Ja</button> <button class="tl_button">nein</button>';
                $html .= '</div>';

                $json['html'] = sprintf($html, $objMemberModel->firstname, $objMemberModel->lastname);

                $json = array_map(
                    function ($val) {
                        if (empty($val) || !\is_string($val)) {
                            return $val;
                        }

                        return $this->stringUtilAdapter->revertInputEncoding($val);
                    },
                    $json
                );
            }

            // Send json data to the browser
            $this->json($json);
        }

        // editAllNavbarHandler in the Contao backend when using the overrideAll or editAll mode
        if ('editAllNavbarHandler' === $strAction) {
            if ('loadNavbar' === $request->request->get('subaction')) {
                $json = [];
                $json['navbar'] = '';
                $json['status'] = 'error';
                $json['subaction'] = $request->request->get('subaction');

                if (null !== $this->backendUserAdapter->getInstance()) {
                    $objTemplate = new BackendTemplate('be_edit_all_navbar_helper');
                    $json['navbar'] = $objTemplate->parse();
                    $json['status'] = 'success';
                }
                // Send json data to the browser
                $this->json($json);
            }

            if ('getSessionData' === $request->request->get('subaction')) {
                $strTable = $request->query->get('table', '_default');
                $session = $this->requestStack->getSession();
                $bag = $session->all();
                $arrChecked = $bag['editAllHelper'][$strTable] ?? [];

                $json = [];
                $json['status'] = 'success';
                $json['table'] = $strTable;
                $json['itemsChecked'] = $arrChecked;

                // Send json data to the browser
                $this->json($json);
            }

            if ('saveSessionData' === $request->request->get('subaction')) {
                $strTable = $request->query->get('table', '_default');
                $arrChecked = $request->request->all()['checkedItems'];
                $arrChecked = empty($arrChecked) ? [] : $arrChecked;

                $session = $this->requestStack->getSession();
                $bag = $session->all();
                $bag['editAllHelper'][$strTable] = $arrChecked;
                $session->replace($bag);

                $json = [];
                $json['status'] = 'success';
                $json['table'] = $strTable;
                $json['itemsChecked'] = $bag['editAllHelper'][$strTable];

                // Send json data to the browser
                $this->json($json);
            }
        }
    }

    private function json(array $json): void
    {
        $json = mb_convert_encoding($json, 'UTF-8', 'UTF-8');
        $response = new JsonResponse($json);

        throw new ResponseException($response);
    }
}
