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

use Contao\CoreBundle\Controller\BackendCsvImportController;
use Contao\ListWizard;
use Contao\TableWizard;
use Markocupic\SacEventToolBundle\ContaoBackendMaintenance\MaintainBackendUser;
use Markocupic\SacEventToolBundle\Controller\BackendModule\NotifyEventRegistrationStateController;
use Markocupic\SacEventToolBundle\Model\CalendarContainerModel;
use Markocupic\SacEventToolBundle\Model\CalendarEventsInstructorInvoiceModel;
use Markocupic\SacEventToolBundle\Model\CalendarEventsInstructorModel;
use Markocupic\SacEventToolBundle\Model\CalendarEventsJourneyModel;
use Markocupic\SacEventToolBundle\Model\CalendarEventsMemberModel;
use Markocupic\SacEventToolBundle\Model\CourseMainTypeModel;
use Markocupic\SacEventToolBundle\Model\CourseSubTypeModel;
use Markocupic\SacEventToolBundle\Model\EventOrganizerModel;
use Markocupic\SacEventToolBundle\Model\EventReleaseLevelPolicyModel;
use Markocupic\SacEventToolBundle\Model\EventReleaseLevelPolicyPackageModel;
use Markocupic\SacEventToolBundle\Model\EventTypeModel;
use Markocupic\SacEventToolBundle\Model\FavoredEventsModel;
use Markocupic\SacEventToolBundle\Model\SacSectionModel;
use Markocupic\SacEventToolBundle\Model\TourDifficultyCategoryModel;
use Markocupic\SacEventToolBundle\Model\TourDifficultyModel;
use Markocupic\SacEventToolBundle\Model\TourTypeModel;
use Markocupic\SacEventToolBundle\Model\UserRoleModel;
use Markocupic\SacEventToolBundle\ModuleSacEventToolEventPreviewReader;

// Remove the calendar module from the content list
unset($GLOBALS['BE_MOD']['content']['calendar']);

// Back end modules
$GLOBALS['BE_MOD']['sac_be_modules'] = [
    'sac_section_tool' => [
        'tables' => ['tl_sac_section'],
    ],
    'calendar' => [
        'tables' => ['tl_calendar_container', 'tl_calendar', 'tl_calendar_events', 'tl_calendar_events_instructor_invoice', 'tl_calendar_feed', 'tl_content', 'tl_calendar_events_member'],
        'table' => [BackendCsvImportController::class, 'importTableWizardAction'],
        'list' => [BackendCsvImportController::class, 'importListWizardAction'],
        NotifyEventRegistrationStateController::PARAM_KEY => [NotifyEventRegistrationStateController::class, 'generate'],
    ],
    'sac_course_main_types_tool' => [
        'tables' => ['tl_course_main_type'],
    ],
    'sac_course_sub_types_tool' => [
        'tables' => ['tl_course_sub_type'],
    ],
    'sac_event_type_tool' => [
        'tables' => ['tl_event_type'],
    ],
    'sac_tour_difficulty_tool' => [
        'tables' => ['tl_tour_difficulty_category', 'tl_tour_difficulty'],
        'table' => [TableWizard::class, 'importTable'],
        'list' => [ListWizard::class, 'importList'],
    ],
    'sac_tour_type_tool' => [
        'tables' => ['tl_tour_type'],
    ],
    'sac_event_release_tool' => [
        'tables' => ['tl_event_release_level_policy_package', 'tl_event_release_level_policy'],
        'table' => [TableWizard::class, 'importTable'],
        'list' => [ListWizard::class, 'importList'],
    ],
    'sac_event_organizer_tool' => [
        'tables' => ['tl_event_organizer'],
        'table' => [TableWizard::class, 'importTable'],
        'list' => [ListWizard::class, 'importList'],
    ],
    'sac_event_journey_tool' => [
        'tables' => ['tl_calendar_events_journey'],
    ],
    'sac_user_role_tool' => [
        'tables' => ['tl_user_role'],
    ],
];

/*
 * Register the models
 */
$GLOBALS['TL_MODELS'][CalendarContainerModel::getTable()] = CalendarContainerModel::class;
$GLOBALS['TL_MODELS'][CalendarEventsInstructorInvoiceModel::getTable()] = CalendarEventsInstructorInvoiceModel::class;
$GLOBALS['TL_MODELS'][CalendarEventsInstructorModel::getTable()] = CalendarEventsInstructorModel::class;
$GLOBALS['TL_MODELS'][CalendarEventsJourneyModel::getTable()] = CalendarEventsJourneyModel::class;
$GLOBALS['TL_MODELS'][CalendarEventsMemberModel::getTable()] = CalendarEventsMemberModel::class;
$GLOBALS['TL_MODELS'][CourseMainTypeModel::getTable()] = CourseMainTypeModel::class;
$GLOBALS['TL_MODELS'][CourseSubTypeModel::getTable()] = CourseSubTypeModel::class;
$GLOBALS['TL_MODELS'][EventOrganizerModel::getTable()] = EventOrganizerModel::class;
$GLOBALS['TL_MODELS'][EventReleaseLevelPolicyModel::getTable()] = EventReleaseLevelPolicyModel::class;
$GLOBALS['TL_MODELS'][EventReleaseLevelPolicyPackageModel::getTable()] = EventReleaseLevelPolicyPackageModel::class;
$GLOBALS['TL_MODELS'][EventTypeModel::getTable()] = EventTypeModel::class;
$GLOBALS['TL_MODELS'][FavoredEventsModel::getTable()] = FavoredEventsModel::class;
$GLOBALS['TL_MODELS'][SacSectionModel::getTable()] = SacSectionModel::class;
$GLOBALS['TL_MODELS'][TourDifficultyCategoryModel::getTable()] = TourDifficultyCategoryModel::class;
$GLOBALS['TL_MODELS'][TourDifficultyModel::getTable()] = TourDifficultyModel::class;
$GLOBALS['TL_MODELS'][TourTypeModel::getTable()] = TourTypeModel::class;
$GLOBALS['TL_MODELS'][UserRoleModel::getTable()] = UserRoleModel::class;

/*
 * Backend maintenance: Clear backend user permissions,
 * who inherit group permissions from tl_user_group
 * and tl_user-admin = ''
 * and tl_user.inherit = 'extend'
 */
$GLOBALS['TL_PURGE']['custom']['reset_backend_user_rights'] = [
    'callback' => [MaintainBackendUser::class, 'resetBackendUserPermissions'],
];

/*
 * Contao backend permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'calendar_containers';
$GLOBALS['TL_PERMISSIONS'][] = 'calendar_containerp';

/*
 * Legacy Contao frontend modules
 * Contao 5 ready fe modules are registered in controller-frontend-module.yml
 */
$GLOBALS['FE_MOD']['sac_event_tool_frontend_modules'] = [
    'eventToolCalendarEventPreviewReader' => ModuleSacEventToolEventPreviewReader::class,
];
