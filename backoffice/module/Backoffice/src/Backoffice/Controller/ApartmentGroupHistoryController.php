<?php

namespace Backoffice\Controller;

use Backoffice\Form\Concierge as ConciergeForm;

use Library\Constants\DbTables;
use Library\Utility\Helper;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\ActionLogger\Logger;
use Library\Controller\ControllerBase;
use Library\Constants\Constants;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplateMapResolver;


class ApartmentGroupHistoryController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Service\ApartmentGroup\Facilities $facilitiesService
         * @var \DDD\Service\ApartmentGroup\FacilityItems $facilitiyItemsService
         * @var ApartmentGroup $conciergeService
         * */

        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        $id = (int)$this->params()->fromRoute('id', 0);

        if ($id && !$this->getServiceLocator()->get('dao_apartment_group_apartment_group')->checkRowExist(DbTables::TBL_APARTMENT_GROUPS, 'id', $id)) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'apartment-group']);
        }

        $form   = $this->getForm($id);
        $global = false;


        /**
         * @var \DDD\Service\ApartmentGroup $apartmentGroupService
         */
        $apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group');

        if ($auth->hasRole(Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER)) {
            $global = true;
        } else {
            $manageableList = $apartmentGroupService->getManageableList();

            if (!in_array($id, $manageableList)) {
                $this->redirect()->toRoute('home');
            }
        }

        $apartmentGroupName = '';

        $logsAaData = [];
        if ($id > 0) {

            $apartmentGroupName = $apartmentGroupService->getApartmentGroupNameById($id);
            $apartmentGroupLogs = $apartmentGroupService->getApartmentGroupLogs($id);

            if (count($apartmentGroupLogs) > 0) {
                foreach ($apartmentGroupLogs as $log) {
                    $rowClass = '';
                    if ($log['user_name'] == TextConstants::SYSTEM_USER) {
                        $rowClass = "warning";
                    }

                    $apartmentGroupLogsArray[] = [
                        date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($log['timestamp'])),
                        $log['user_name'],
                        $this->identifyApartmentGroupAction($log['action_id']),
                        $log['value'],
                        "DT_RowClass" => $rowClass
                    ];
                }
            } else {
                $apartmentGroupLogsArray = [];
            }

            $logsAaData = $apartmentGroupLogsArray;
        }

        $logsAaData = json_encode($logsAaData);

        $viewModel = new ViewModel();

        $viewModel->setVariables([
            'apartmentGroupName' => $apartmentGroupName,
            'id'                 => $id,
            'global'             => $global,
            'historyAaData'      => $logsAaData,
        ]);

        $resolver = new TemplateMapResolver(
            ['backoffice/apartment-group/usages/history' => '/ginosi/backoffice/module/Backoffice/view/backoffice/apartment-group/usages/history.phtml']
        );

        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);
        $viewModel->setTemplate('backoffice/apartment-group/usages/history');
        return $viewModel;
    }

    protected function getForm($id)
    {
        /**
         * @var \DDD\Service\ApartmentGroup $service
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Domain\ApartmentGroup\ApartmentGroup $accGroupData
         */
        $auth                 = $this->getServiceLocator()->get('library_backoffice_auth');
        $service              = $this->getServiceLocator()->get('service_apartment_group');
        $accgroupmanageOption = $service->getConciergeOptions();
        $accgroupmanageData   = '';
        $global               = false;

        if ($id > 0) {
            $accgroupmanageData = $service->getConciergeManageById($id);
            $accGroupData = $accgroupmanageData->get('accGroupManageMain');

            if ($accGroupData->getGroupManagerId() == $auth->getIdentity()->id) {
                $global = true;
            }
        }

        if ($auth->hasRole(Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER)) {
            $global = true;
        }

        $generalLocationService = $this->getServiceLocator()->get('service_location');

        return new ConciergeForm(
            'form_edit_apartment_group',
            $accgroupmanageData,
            $accgroupmanageOption,
            $global
        );
    }

    /**
     *
     * @param int $actionId
     * @return string
     */
    private function identifyApartmentGroupAction($actionId)
    {
        $apartmentGroupActions = [
            Logger::ACTION_APARTMENT_GROUPS_NAME           => 'Group Name',
            Logger::ACTION_APARTMENT_GROUPS_APARTMENT_LIST => 'Apartments List',
            Logger::ACTION_APARTMENT_GROUPS_USAGE          => 'Group\'s Usages',
        ];

        if (isset($apartmentGroupActions[$actionId])) {
            return $apartmentGroupActions[$actionId];
        }

        return 'not defined';
    }
}