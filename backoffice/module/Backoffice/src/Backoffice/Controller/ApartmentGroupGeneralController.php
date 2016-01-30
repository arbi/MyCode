<?php

namespace Backoffice\Controller;

use Backoffice\Form\ApartmentGroup\GeneralForm;
use Backoffice\Form\InputFilter\ApartmentGroup\GeneralFilter;

use DDD\Service\Translation;
use Library\Constants\DbTables;
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


class ApartmentGroupGeneralController extends ControllerBase
{
    protected $_apartmentGroupService;


    public function indexAction()
    {
        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Service\ApartmentGroup\FacilityItems $facilitiyItemsService
         * @var ApartmentGroup $conciergeService
         * */

        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        $id = (int)$this->params()->fromRoute('id', 0);

        if ($id && !$this->getServiceLocator()->get('dao_apartment_group_apartment_group')->checkRowExist(DbTables::TBL_APARTMENT_GROUPS, 'id', $id)) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'apartment-group']);
        }

        $dataForm = $this->getDataForm($id);
        $form = $dataForm['form'];
        $global = false;

        /**
         * @var \DDD\Service\ApartmentGroup $conciergeService
         */
        $conciergeService = $this->getServiceLocator()->get('service_apartment_group');

        if ($auth->hasRole(Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER)) {
            $global = true;
        } else {
            $manageableList = $conciergeService->getManageableList();

            if (!in_array($id, $manageableList)) {
                $this->redirect()->toRoute('home');
            }
        }

        $viewModel = new ViewModel();

        $viewModel->setVariables([
            'accGroupsManageForm' => $form,
            'id'                  => $id,
            'global'              => $global,
            'isApartel'           => $dataForm['isApartel'],
        ]);

        $resolver = new TemplateMapResolver(
            ['backoffice/apartment-group/usages/general' => '/ginosi/backoffice/module/Backoffice/view/backoffice/apartment-group/usages/general.phtml']
        );

        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);
        $viewModel->setTemplate('backoffice/apartment-group/usages/general');
        return $viewModel;
    }

    protected function getDataForm($id)
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
        $global = $accGroupData = false;

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
        $form = new GeneralForm(
            'form_edit_apartment_group',
            $accgroupmanageData,
            $accgroupmanageOption,
            $global
        );
        return [
            'form' => $form,
            'isApartel' => $accGroupData && $accGroupData->getIsApartel() ? true : false,
        ];
    }

    public function ajaxsaveAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\ApartmentGroup $service
         */
        $request = $this->getRequest();
        $result = [
            'result' => [],
            'id'     => 0,
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');

            $id = (int)$request->getPost('apartment_group_id', 0);
            $dataForm = $this->getDataForm($id);
            $form = $dataForm['form'];
            $messages = '';
            $global = false;

            if ($auth->hasRole(Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER)) {
                $global = true;
            }

            /* @var $service \DDD\Service\ApartmentGroup\Main */
            $service = $this->getApartmentGroupService();

            $form->setInputFilter(new GeneralFilter($global));
            $filter  = $form->getInputFilter();
            $form->setInputFilter($filter);
            $data = (array)$request->getPost();

            if (!$id) {
                $data['usage_building']     = 0;
                $data['usage_apartel']      = 0;
            } else {
                $accGroupsManagementDao   = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

                /* @var $apartmentGroupCurrentState \DDD\Domain\ApartmentGroup\ApartmentGroup */
                $apartmentGroupCurrentState = $accGroupsManagementDao->getRowById($id);


                if ($apartmentGroupCurrentState->isBuilding()
                    && $data['usage_building']
                    && $data['usage_building_val']
                ) {
                    /* @var $apartmentGroupService \DDD\Service\ApartmentGroup */
                    $apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group');

                    $data['accommodations'] = $apartmentGroupService->getApartmentGroupItems($id);
                }
            }

            $form->setData($data);
            $name = strip_tags(trim($request->getPost('name')));

            if ($global) {
                if ($name != '') {
                    if ($service->checkGroupName($name, $id)) {
                        $messages = 'Bad group name. Group name is in use<br>';
                    }
                } else {
                    $messages = 'Bad Group Name <br>';
                }
            }

            if ($form->isValid() && $messages == '') {
                if ($data['usage_building'] == 1 && !empty($data['accommodations'])) {
                    $mismatchedApartments = $service->getAccommodationNotInBuilding($data['accommodations'], $id);
                    $mismatchedApartmentNames = array();
                    if($mismatchedApartments->count()) {
                        //Has accommodations associated with other buildings
                        foreach($mismatchedApartments as $mismatchedApartment) {
                            $mismatchedApartmentNames[] = $mismatchedApartment->getName();
                        }
                        $result['status'] = 'error';
                        $result['msg'] = 'Unable to mark this group as building, since the following apartments associated with this group are associated with other buildings: <b>'.implode(', ', $mismatchedApartmentNames).'</b>';
                    }
                }

                if ($result['status'] != 'error') {
                    $data = (array)$data;

                    $responsDb = $service->generalSave($data, $id, $global);

                    if ($responsDb > 0) {
                        $result['id'] = $id;
                        Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);

                        if (!$id) {
                            $result['id'] = $responsDb;
                            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                        }
                    } else {
                        $result['status'] = 'error';
                        $result['msg']    = TextConstants::SERVER_ERROR;
                    }
                }
            } else {
                $errors = $form->getMessages();

                foreach ($errors as $key => $row) {
                    if (!empty($row)) {
                        $messages .= ucfirst($key) . ' ';
                        $messages_sub = '';

                        foreach ($row as $keyer => $rower) {
                            $messages_sub .= $rower;
                        }

                        $messages .= $messages_sub . '<br>';
                    }
                }

                $result['status'] = 'error';
                $result['msg'] = $messages;
            }
        }

        return new JsonModel($result);
    }

    public function ajaxCheckNameAction()
    {
        try{
            $request = $this->getRequest();

            $result = [
                'status'        => 'success',
                'duplicate'     => false,
                'is_changed'    => false
            ];

            if($request->isXmlHttpRequest()) {
                $name = strip_tags(trim($request->getPost('name')));
                $id = (int)$request->getPost('id');

                $service  = $this->getApartmentGroupService();

                // check duplicate name
                if ($service->checkGroupName($name, $id)) {
                    $result = [
                        'status'    => 'error',
                        'duplicate' => true,
                        'msg'       => TextConstants::ERROR_DUPLICATE_APARTMENT_GROUP_NAME
                    ];
                } else {
                    // check name is changed
                    if (!$service->checkGroupName($name, $id, true)) {
                        $result = [
                            'status'        => 'error',
                            'is_changed'    => true
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $result = [
                'status'    => 'error',
                'msg'       => TextConstants::SERVER_ERROR . PHP_EOL . $e->getMessage()
            ];
        }

        return new JsonModel($result);
    }


    public function ajaxGetApartmentsForCountryAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'success',
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $countryId               = $request->getPost('country_id');
                $apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
                $apartmentsForSelectize  = $apartmentGeneralService->getApartmentsForCountryForSelect($countryId);
                $result['apartments']    = $apartmentsForSelectize;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
        }

        return new JsonModel($result);
    }

    public function ajaxCreateApartelAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR
        ];

        try {
            $groupId  = $request->getPost('groupId', 0);
            if ($request->isXmlHttpRequest() && $groupId) {
                /**
                 * @var \DDD\Service\Apartel\General $apartelService
                 */
                $apartelService = $this->getServiceLocator()->get('service_apartel_general');
                $response = $apartelService->createApartel($groupId);
                $result['status'] = $response['status'];
                $result['msg'] = $response['msg'];
                if ($response['status'] == 'success') {
                    Helper::setFlashMessage(['success' => $response['msg']]);
                    $result['apartelId'] = $response['apartelId'];
                }
            }
        } catch (\Exception $e) {

        }

        return new JsonModel($result);
    }

    public function ajaxDeactivateApartelAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR
        ];

        try {
            $groupId  = $request->getPost('groupId', 0);
            if ($request->isXmlHttpRequest() && $groupId) {
                /**
                 * @var \DDD\Service\Apartel\General $apartelService
                 */
                $apartelService = $this->getServiceLocator()->get('service_apartel_general');
                $response = $apartelService->deactivateApartel($groupId);

                $result['status'] = $response['status'];
                $result['msg'] = $response['msg'];

                if ($response['status'] == 'success') {
                    Helper::setFlashMessage(['success' => $response['msg']]);
                }
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot delete apartel');

            $result['msg'] = $e->getMessage();
        }

        return new JsonModel($result);
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

    /**
     * @return \DDD\Service\ApartmentGroup\Main
     */
    public function getApartmentGroupService()
    {
        if ($this->_apartmentGroupService === null) {
            $this->_apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group_main');
        }

        return $this->_apartmentGroupService;
    }
}