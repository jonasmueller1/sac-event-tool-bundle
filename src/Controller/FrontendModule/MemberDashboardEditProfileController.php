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

namespace Markocupic\SacEventToolBundle\Controller\FrontendModule;

use Codefog\HasteBundle\Form\Form;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Environment;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\Message;
use Contao\ModuleModel;
use Contao\PageModel;
use Markocupic\SacEventToolBundle\Config\Log;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsFrontendModule(MemberDashboardEditProfileController::TYPE, category:'sac_event_tool_frontend_modules', template:'mod_member_dashboard_edit_profile')]
class MemberDashboardEditProfileController extends AbstractFrontendModuleController
{
    public const TYPE = 'member_dashboard_edit_profile';

    private FrontendUser|null $objUser;
    private FragmentTemplate|null $template;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface|null $contaoGeneralLogger = null,
    ) {
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array|null $classes = null, PageModel|null $page = null): Response
    {
        // Get logged in member object
        $this->objUser = $this->security->getUser();

        if (null !== $page) {
            // Neither cache nor search page
            $page->noSearch = 1;
            $page->cache = 0;
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        // Do not allow for not authorized users
        if (!$this->objUser instanceof FrontendUser) {
            throw new UnauthorizedHttpException('Not authorized. Please log in as frontend user.');
        }

        $this->template = $template;

        $this->template->set('objUser', $this->objUser);

        // Generate the users profile form
        $this->template->set('userProfileForm', $this->generateUserProfileForm());

        // Add messages to template
        $this->addMessagesToTemplate($request);

        return $this->template->getResponse();
    }

    /**
     * Add messages from session to template.
     */
    private function addMessagesToTemplate(Request $request): void
    {
        $messageAdapter = $this->framework->getAdapter(Message::class);
        $session = $request->getSession();
        $flashBag = $session->getFlashBag();

        if ($messageAdapter->hasInfo()) {
            $this->template->set('hasInfoMessage', true);
            $infoMsg = $flashBag->get('contao.FE.info');
            $this->template->set('infoMessage', $infoMsg[0]);
            $this->template->set('infoMessages', $infoMsg);
        }

        if ($messageAdapter->hasError()) {
            $this->template->set('hasErrorMessage', true);
            $errorMsg = $flashBag->get('contao.FE.error');
            $this->template->set('errorMessage', $errorMsg[0]);
            $this->template->set('errorMessages', $errorMsg);
        }

        $messageAdapter->reset();
    }

    private function generateUserProfileForm(): string
    {
        // Set adapters
        $environmentAdapter = $this->framework->getAdapter(Environment::class);
        $memberModelAdapter = $this->framework->getAdapter(MemberModel::class);

        $objForm = new Form(
            'form-user-profile',
            'POST',
        );

        $objForm->setAction($environmentAdapter->get('uri'));

        // Now let's add form fields:
        $objForm->addFormField('emergencyPhone', [
            'label' => $this->translator->trans('FORM.evt_reg_emergencyPhone', [], 'contao_default'),
            'inputType' => 'text',
            'eval' => ['rgxp' => 'phone', 'mandatory' => true, 'maxlength' => 64],
        ]);

        $objForm->addFormField('emergencyPhoneName', [
            'label' => $this->translator->trans('FORM.evt_reg_emergencyPhoneName', [], 'contao_default'),
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255],
        ]);

        $objForm->addFormField('foodHabits', [
            'label' => $this->translator->trans('FORM.evt_reg_foodHabits', [], 'contao_default'),
            'inputType' => 'text',
            'eval' => ['mandatory' => false, 'maxlength' => 5000],
        ]);

        $objForm->addFormField('submit', [
            'label' => $this->translator->trans('MSC.save', [], 'contao_default'),
            'inputType' => 'submit',
        ]);

        // Get form presets from tl_member
        $arrFields = ['emergencyPhone', 'emergencyPhoneName', 'foodHabits'];

        foreach ($arrFields as $field) {
            $objWidget = $objForm->getWidget($field);

            if (empty($objWidget->value)) {
                $objWidget = $objForm->getWidget($field);
                $objWidget->value = $this->objUser->{$field};
            }
        }

        // Bind form to the MemberModel
        $objModel = $memberModelAdapter->findByPk($this->objUser->id);
        $objForm->setBoundModel($objModel);

        if ($objForm->validate()) {
            // The model will now contain the changes, so you can save it.
            $objModel->save();

            $this->contaoGeneralLogger->info(
                sprintf(
                    'Frontend user %s %s "%s" has updated his user profile.',
                    $this->objUser->firstname,
                    $this->objUser->lastname,
                    $this->objUser->username,
                ),
                ['contao' => new ContaoContext(__METHOD__, Log::MEMBER_DASHBOARD_UPDATE_PROFILE)]
            );
        }

        return $objForm->generate();
    }
}
