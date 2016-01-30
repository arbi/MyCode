<?php

namespace Backoffice\Controller;

use Backoffice\Form\ApartmentGroup\BuildingForm;

use Library\Constants\DbTables;
use Library\Constants\DomainConstants;
use Library\Utility\Helper;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\ActionLogger\Logger;
use Library\Controller\ControllerBase;

use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplateMapResolver;


class ApartmentGroupBuildingController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Service\Lock\Usages\Building $lockBuildingUsageService
         * @var \DDD\Service\ApartmentGroup\Facilities $facilitiesService
         * @var \DDD\Service\ApartmentGroup\FacilityItems $facilityItemsService
         * @var \DDD\Dao\Parking\General $parkingLotDao
         * @var \DDD\Dao\ApartmentGroup\BuildingDetails $buildingDetailsDao
         * @var \DDD\Dao\Textline\Group $textlineGroupDao
         * @var \DDD\Service\ApartmentGroup\Usages\Building $serviceBuilding
         */

        $auth                  = $this->getServiceLocator()->get('library_backoffice_auth');
        $facilitiesService     = $this->getServiceLocator()->get('service_apartment_group_facilities');
        $facilityItemsService  = $this->getServiceLocator()->get('service_apartment_group_facility_items');
        $buildingDetailsDao    = $this->getServiceLocator()->get('dao_apartment_group_building_details');
        $textlineGroupDao       = $this->getServiceLocator()->get('dao_textline_group');
        $lockBuildingUsageService = $this->getServiceLocator()->get('service_lock_usages_building');
        $parkingLotDao         = $this->getServiceLocator()->get('dao_parking_general');
        $serviceBuilding       = $this->getServiceLocator()->get('service_apartment_group_usages_building');

        $id = (int)$this->params()->fromRoute('id', 0);

        if ($id && !$this->getServiceLocator()->get('dao_apartment_group_apartment_group')->checkRowExist(DbTables::TBL_APARTMENT_GROUPS, 'id', $id)) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'apartment-group']);
        }

        if (!$id) {
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'apartment-group']);
        }

        /** @var \DDD\Dao\ApartmentGroup\ApartmentGroup $apartmentGroupDao */
        $apartmentGroupDao  = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

        /* @var $apartmentGroupData \DDD\Domain\ApartmentGroup\ApartmentGroup */
        $apartmentGroupData = $apartmentGroupDao->getRowById($id);

        $isActive = ($apartmentGroupData->getActive()) ? true : false;

        if ($apartmentGroupData && !(int)$apartmentGroupData->isBuilding()) {
            return $this->redirect()->toRoute('apartment-group', ['controller' => 'apartment-group-general', 'id' => $id]);
        }

        $form   = $this->getForm($id);
        $global = false;

        $facilitiesList     = $facilitiesService->getFacilitiesList();
        $buildingFacilities = $facilityItemsService->getBuildingFacilities($id);

        /** @var \DDD\Service\ApartmentGroup $conciergeService */
        $conciergeService = $this->getServiceLocator()->get('service_apartment_group');

        if ($auth->hasRole(Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER)) {
            $global = true;
        } else {
            $manageableList = $conciergeService->getManageableList();

            if (!in_array($id, $manageableList)) {
                $this->redirect()->toRoute('home');
            }
        }

        $receptionEntryTextlineId = null;
        if ($id) {
            $apartmentGroupDao        = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
            $preApartmentGroupInfo    = $apartmentGroupDao->getRowById($id);
        }

        //KI map
        $mapAttachment = false;
        $buildingDetails = $buildingDetailsDao->fetchOne(['apartment_group_id' => $id], ['map_attachment']);
        if ($buildingDetails && $buildingDetails['map_attachment']) {
            $mapAttachment = '//' . DomainConstants::IMG_DOMAIN_NAME . '/building/' . $id . '/map/' . $buildingDetails['map_attachment'];
        }

        // get group usage information
        $groupUsage = $textlineGroupDao->getGroupUsageById($id);

        // get group facilities information
        $groupFacility = $textlineGroupDao->getGroupFacilityById($id);

        // get group policy information
        $groupPolicy = $textlineGroupDao->getGroupPolicyById($id);

        $viewModel = new ViewModel();

        // lock list
        $lock = $lockBuildingUsageService->getLockByUsage($id);
        unset($lock[0]);

        // lot list
        $lotsList = $parkingLotDao->getAllLots();
        $lots = [];
        foreach($lotsList as $row) {
            $lots[$row['id']] = $row['name'];
        }

        // get section list
        $sectionList = $serviceBuilding->getSectionData($id);

        $viewModel->setVariables([
            'form'                     => $form,
            'id'                       => $id,
            'global'                   => $global,
            'facilitiesList'           => $facilitiesList,
            'buildingFacilities'       => $buildingFacilities,
            'receptionEntryTextlineId' => $receptionEntryTextlineId,
            'mapAttachment'            => $mapAttachment,
            'lockId'                   => (int)$apartmentGroupData->getLockId(),
            'isActive'                 => $isActive,
            'groupUsage'               => $groupUsage,
            'groupFacility'            => $groupFacility,
            'groupPolicy'              => $groupPolicy,
            'lots'                     => $lots,
            'lock'                     => $lock,
            'sectionList'              => $sectionList,
        ]);

        $resolver = new TemplateMapResolver([
            'backoffice/apartment-group/usages/building' => '/ginosi/backoffice/module/Backoffice/view/backoffice/apartment-group/usages/building.phtml'
        ]);

        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);
        $viewModel->setTemplate('backoffice/apartment-group/usages/building');
        return $viewModel;
    }

    public function ajaxsaveAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $result = [
            'result' => [],
            'id'     => 0,
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');

            $id     = (int)$request->getPost('id', 0);
            $global = false;

            if ($auth->hasRole(Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER)) {
                $global = true;
            }

            /** @var \DDD\Service\ApartmentGroup\Usages\Building $service */
            $service     = $this->getServiceLocator()->get('service_apartment_group_usages_building');
            /** @var \DDD\Service\Lock\General $lockService */
            $lockService = $this->getServiceLocator()->get('service_lock_general');
            $data        = $request->getPost();

            if (isset($data['lock_id']) && $data['lock_id']) {
                $isDuplicatePhysicalLock = $lockService->checkDuplicatePhysicalLock(
                    $id,
                    $data['lock_id'],
                    '\DDD\Dao\ApartmentGroup\BuildingDetails'
                );

                if ($isDuplicatePhysicalLock['isDuplicate']) {
                    return new JsonModel(
                        [
                            "status" => "error",
                            "msg"    => "<b>" . $isDuplicatePhysicalLock['name'] . "</b> lock is set as physical and already assigned to other entity."
                        ]
                    );
                }
            }

            $responseDb = $service->buildingSave($data, $id, $global, $request);

            if ($responseDb) {
                $result['id'] = $id;
                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
            } else {
                $result['status'] = 'error';
                $result['msg']    = TextConstants::SERVER_ERROR;
            }
        }

        return new JsonModel($result);
    }

    protected function getForm($id)
    {
        /**
         * @var \DDD\Service\ApartmentGroup $service
         * @var \DDD\Service\ApartmentGroup\Usages\Building $apartmentDetailsService
         * @var \DDD\Service\Office $officeService
         * @var \DDD\Domain\ApartmentGroup\ApartmentGroup $accGroupData
         * */
        $service = $this->getServiceLocator()->get('service_apartment_group');
        $buildingDetailsService = $this->getServiceLocator()->get('service_apartment_group_usages_building');
        $officeService = $this->getServiceLocator()->get('service_office');

        $formOptions = [];
        $formOptions['keyInstructionPageTypes']  = $buildingDetailsService->getApartmentKeyInstructionPageTypes();
        $formOptions['officeOptions']            = $officeService->getOfficeSelectOptions();


        $accGroupData = [];
        if ($id > 0) {
            $accGroupManageData = $service->getConciergeManageById($id);
            $accGroupData = $accGroupManageData->get('accGroupManageMain');
        }

        return new BuildingForm('form_apartment_group_building', $accGroupData, $formOptions);
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

    public function ajaxSaveSectionAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\ApartmentGroup\Usages\Building $service
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $data = $request->getPost();
                $service = $this->getServiceLocator()->get('service_apartment_group_usages_building');
                $service->saveSection($data->toArray());
                $result['status'] = 'success';
                Helper::setFlashMessage(['success' => $data['section_id'] ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_ADD]);
            }
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
        }

        return new JsonModel($result);
    }

    public function deleteAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\ApartmentGroup\Usages\Building $service
         */
        $groupId = (int)$this->params()->fromRoute('id', 0);
        $sectionId = (int)$this->params()->fromRoute('section_id', 0);

        if ($groupId && $sectionId) {
            $service = $this->getServiceLocator()->get('service_apartment_group_usages_building');
            $service->deleteSection($sectionId);
            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
        } else {
            Helper::setFlashMessage(['error' => TextConstants::BAD_REQUEST]);
        }

        return $this->redirect()->toRoute('apartment-group/building', ['id' => $groupId]);
    }
}