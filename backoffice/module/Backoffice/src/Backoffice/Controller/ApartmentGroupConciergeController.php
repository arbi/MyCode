<?php

namespace Backoffice\Controller;

use Backoffice\Form\ApartmentGroup\ConciergeForm;

use Library\Constants\DbTables;
use Library\Utility\Helper;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\TemplateMapResolver;

class ApartmentGroupConciergeController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
        **/
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        $id = (int)$this->params()->fromRoute('id', 0);

        if ($id && !$this->getServiceLocator()->get('dao_apartment_group_apartment_group')->checkRowExist(DbTables::TBL_APARTMENT_GROUPS, 'id', $id)) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'apartment-group']);
        }

        if (!$id) {
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'apartment-group']);
        }

        $apartmentGroupDao  = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

        /* @var $apartmentGroupData \DDD\Domain\ApartmentGroup\ApartmentGroup */
        $apartmentGroupData = $apartmentGroupDao->getRowById($id);

        $isActive = ($apartmentGroupData->getActive()) ? true : false;

        if ($apartmentGroupData && !(int)$apartmentGroupData->getIsArrivalsDashboard()) {
            return $this->redirect()->toRoute('apartment-group', ['controller' => 'apartment-group-general', 'id' => $id]);
        }

        $form   = $this->getForm($id);
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
            'form'     => $form,
            'id'       => $id,
            'global'   => $global,
            'isActive' => $isActive
        ]);

        $resolver = new TemplateMapResolver(
            ['backoffice/apartment-group/usages/concierge' => '/ginosi/backoffice/module/Backoffice/view/backoffice/apartment-group/usages/concierge.phtml']
        );

        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);
        $viewModel->setTemplate('backoffice/apartment-group/usages/concierge');
        return $viewModel;
    }

    protected function getForm($id)
    {
        /**
         * @var \DDD\Service\ApartmentGroup $service
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Domain\ApartmentGroup\ApartmentGroup $accGroupData
         */
        $service              = $this->getServiceLocator()->get('service_apartment_group');
        $accGroupData         = false;

        if ($id > 0) {
            $accgroupmanageData = $service->getConciergeManageById($id);
            /** @var \DDD\Domain\ApartmentGroup\ApartmentGroup $accGroupData */
            $accGroupData = $accgroupmanageData->get('accGroupManageMain');
        }

        $conciergeFormOptions = $service->getConciergeOptions($accGroupData);

        return new ConciergeForm(
            'form_apartment_group_concierge',
            $accGroupData,
            $conciergeFormOptions
        );
    }

    public function ajaxsaveAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\ApartmentGroup\Usages\Concierge $service
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

            $id       = (int)$request->getPost('id', 0);
            $messages = '';
            $global   = false;

            if ($auth->hasRole(Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER)) {
                $global = true;
            }

            $service   = $this->getServiceLocator()->get('service_apartment_group_usages_concierge');
            $data      = $request->getPost();
            $responsDb = $service->conciergeSave((array)$data, $global, $id);

            if ($responsDb) {
                $result['id'] = $id;
                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
            } else {
                $result['status'] = 'error';
                $result['msg']    = TextConstants::SERVER_ERROR;
            }
        }

        return new JsonModel($result);
    }
}