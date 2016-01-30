<?php

namespace Backoffice\Controller;

use DDD\Service\Apartment\Main;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class ApartmentGroupController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Service\ApartmentGroup $service
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $service = $this->getServiceLocator()->get('service_apartment_group');

        $isApartelViewer = $auth->hasRole(Roles::ROLE_APARTMENT_INVENTORY_MANAGER) || $auth->hasRole(Roles::ROLE_APARTMENT_CONNECTION);
        $isGlobalApartmentGroupManager = $auth->hasRole(Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER);
        $isAtLeaseManagerOfOneGroup = $service->getManagerGroupCount($auth->getIdentity()->id);

        return new ViewModel([
            'isGlobalApartmentGroupManager' => $isGlobalApartmentGroupManager,
            'isApartelViewer' => $isApartelViewer,
            'isAtLeaseManagerOfOneGroup' => $isAtLeaseManagerOfOneGroup,
        ]);
    }

    public function ajaxGroupListAction()
    {
        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Service\ApartmentGroup $service
         */
        $service = $this->getServiceLocator()->get('service_apartment_group');
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');

        $request = $this->params();
        $result = [];
        $results = $service->getApartmentGroupsList(
            (int)$request->fromQuery('iDisplayStart'),
            (int)$request->fromQuery('iDisplayLength'),
            (int)$request->fromQuery('iSortCol_0'),
            $request->fromQuery('sSortDir_0'),
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '1')
        );

        $viewApartelPage = $auth->hasRole(Roles::ROLE_APARTMENT_INVENTORY_MANAGER) || $auth->hasRole(Roles::ROLE_APARTMENT_CONNECTION);
        $isAtLeaseManagerOfOneGroup = (bool)$service->getManagerGroupCount($auth->getIdentity()->id);

        foreach ($results as $row) {
            $isManager = $auth->hasRole(Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER) || $row->getManagerId() == $auth->getIdentity()->id;
            $tableData = [
                $row->isActive() ? '<span class="label label-success">Active</span>' : '<span class="label label-default">Inactive</span>',
                $row->getNameWithApartelUsage(),
                $row->getCount(),
                $row->getCountry(),
                $row->getIsCostCenter() ? '<i class="glyphicon glyphicon-ok"></i>' : '',
                $row->getIsArrivalDashboard() ? '<i class="glyphicon glyphicon-ok"></i>' : '',
                $row->getIsBuilding() ? '<i class="glyphicon glyphicon-ok"></i>' : '',
                $row->getIsPerformance() ? '<i class="glyphicon glyphicon-ok"></i>' : '',
                $row->getIsApartel()
                    ? $viewApartelPage
                        ? '<a href="/apartel/' . $row->getApartelId() . '" class="btn btn-xs btn-primary">Manage</a>'
                        : '<i class="glyphicon glyphicon-ok"></i>'
                    : ''
            ];

            if ($isAtLeaseManagerOfOneGroup || $auth->hasRole(Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER)) {
                array_push($tableData, $isManager
                    ? '<a href="/concierge/edit/' . $row->getId() . '" class="btn btn-xs btn-primary" data-html-content="Edit"></a>'
                    : ''
                );
            }

            array_push($result, $tableData);
        }

        return new JsonModel([
            'sEcho' => $request->fromQuery('sEcho'),
            'aaData' => $result,
        ]);
    }

    /**
     * @return JsonModel
     */
    public function deactivateAction()
    {
        $apartmentGroupId = $this->params()->fromRoute('id', 0);

        if ($apartmentGroupId) {
            /**
             * @var \DDD\Service\ApartmentGroup\Main $apartmentGroupMainService
             */
            $apartmentGroupMainService = $this->getServiceLocator()->get('service_apartment_group_main');

            $accGroupsManagementDao   = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
            /* @var $apartmentGroupCurrentState \DDD\Domain\ApartmentGroup\ApartmentGroup */
            $apartmentGroupCurrentState = $accGroupsManagementDao->getRowById($apartmentGroupId);

            /* @var $apartmentGroupService \DDD\Service\ApartmentGroup */
            $apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group');

            $currentAccommodations = $apartmentGroupService->getApartmentGroupItems($apartmentGroupId);

            if ($apartmentGroupCurrentState->isBuilding() && $currentAccommodations) {

                Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);

                return new JsonModel([
                    'status' => 'error',
                    'msg' => 'Please move all apartments of this  building group to another building before deactivation group'
                ]);
            }

            // Deactivation
            $result = $apartmentGroupMainService->deactivate($apartmentGroupId);

            if ($result) {
                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DEACTIVATE]);

                return new JsonModel([
                    'status' => 'success',
                    'msg' => 'Successful'
                ]);
            } else {
                Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);

                return new JsonModel([
                    'status' => 'error',
                    'msg' => 'Something went wrong while trying to deactivate apartment group.'
                ]);
            }
        } else {
            Helper::setFlashMessage(['error' => 'Wrong Apartment Group ID']);

            return new JsonModel([
                'status' => 'error',
                'msg' => 'Wrong Apartment Group ID'
            ]);
        }
    }
}
