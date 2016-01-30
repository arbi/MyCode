<?php

namespace Finance\Controller;

use DDD\Service\Finance\Espm as EspmService;
use Finance\Form\Espm as EspmForm;
use Finance\Form\InputFilter\Espm as EspmFilter;
use Library\ActionLogger\Logger;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Library\Authentication\BackofficeAuthenticationService;


class EspmController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        // has creator role
        $hasCreatorRole = $auth->hasRole(Roles::ROLE_ESPM_PAYMENT_ADDER) || $auth->hasRole(Roles::ROLE_ESPM_PAYMENT_MANAGER);

        // statuses
        $statuses = EspmService::$espmStatuses;

        // types
        $types = EspmService::$espmTypes;

        return [
            'statuses' => $statuses,
            'types' => $types,
            'hasCreatorRole' => $hasCreatorRole
        ];
	}

    public function getDatatableAction()
    {
        $result = [
            'iTotalRecords'        => 0,
            'iTotalDisplayRecords' => 0,
            'iDisplayStart'        => 0,
            'iDisplayLength'       => 0,
            'aaData'               => [],
        ];

        /**
         * @var \DDD\Service\Finance\Espm $service
         */
        $service = $this->getServiceLocator()->get('service_finance_espm');
        $request = $this->getRequest();

        if ($request->isGet() && $request->isXmlHttpRequest()) {
            try {

                $data = $service->getDatatableData($this->params()->fromQuery());

                if ($data['iTotalRecords']) {
                    $result = $data;
                }
            } catch (\Exception $ex) {
                // do nothing
            }
        }

        return new JsonModel($result);
    }

    public function getSupplierAccountAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Dao\User\ExternalAccount $accountDao
         */

        $request = $this->getRequest();
        $result = [];

        try {
            if ($request->isXmlHttpRequest()) {
                $supplierId = (int)$this->params()->fromRoute('supplier_id', 0);
                $accountDao = $this->getServiceLocator()->get('dao_user_external_account');

                if (!$supplierId) {
                    throw new \Exception('Bed Data');
                }

                $accounts = $accountDao->getAccountsBySupplierId($supplierId);
                foreach ($accounts as $account) {
                    $result[] = [
                        'id'   => $account['id'],
                        'name' => $account['name'],
                    ];
                }
            }
        } catch (\Exception $e) {

        }

        return new JsonModel($result);
    }

    public function editAction()
    {
        /**
         * @var \DDD\Service\Finance\Espm $service
         * @var BackofficeAuthenticationService $auth
         * @var Logger $loggerService
         */

        $service      = $this->getServiceLocator()->get('service_finance_espm');
        $auth         = $this->getServiceLocator()->get('library_backoffice_auth');
        $request      = $this->getRequest();
        $espmId       = $this->params()->fromRoute('id', 0);
        $archived     = 0;
        $actionLogs   = [];
        // init form
        $form = new EspmForm($espmId, $service->getOptions());
        $form->setInputFilter(new EspmFilter());
        $form->prepare();

        // set permission
        $isGlobal = $auth->hasRole(Roles::ROLE_ESPM_PAYMENT_MANAGER);
        $isPayer = $auth->hasRole(Roles::ROLE_ESPM_PAYMENT_PAYER);

        if(!$espmId && !$isGlobal && !$auth->hasRole(Roles::ROLE_ESPM_PAYMENT_ADDER)) {
            Helper::setFlashMessage(['error' => TextConstants::NO_PERMISSION]);
            $this->redirect()->toRoute('finance/espm');
        }

        $espmData = false;

        if ($request->isPost()) {
            $postData = $request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                if ($redirectId = $service->saveEspm($postData, $espmId)) {
                    Helper::setFlashMessage(['success' => ($espmId > 0) ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_ADD]);
                    $this->redirect()->toRoute('finance/espm/edit', ['controller' => 'edit', 'action' => 'edit', 'id' => $redirectId]);
                } else {
                    Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
                    $this->redirect()->toRoute('finance/espm');
                }
            }
        } else {
            if ($espmId) {
                $espmData = $service->getEspmData($espmId);
                if ($espmData) {
                    $form->populateValues($espmData);
                    $archived = $espmData['is_archived'];

                    $loggerService = $this->getServiceLocator()->get('ActionLogger');
                    $actionLogs = $loggerService->getDatatableData(Logger::MODULE_ESPM, $espmId);
                } else {
                    Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                    $this->redirect()->toRoute('finance/espm');
                }
            }
        }

        return [
            'form' => $form,
            'id' => $espmId,
            'archived' => $archived,
            'isGlobal' => $isGlobal,
            'isPayer' => $isPayer,
            'espmData' => $espmData,
            'historyData' => json_encode($actionLogs),
        ];
    }

    public function archiveAction()
    {
        /**
         * @var \DDD\Service\Finance\Espm $service
         * @var BackofficeAuthenticationService $auth
         */
        $service = $this->getServiceLocator()->get('service_finance_espm');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        if (!$auth->hasRole(Roles::ROLE_ESPM_PAYMENT_MANAGER)) {
            Helper::setFlashMessage(['error' => TextConstants::NO_PERMISSION]);
            $this->redirect()->toRoute('finance/espm');
        }

        $espmId = $this->params()->fromRoute('id', 0);
        $archive = $this->params()->fromRoute('archive', 0);

        if ($espmId > 0) {
            $service->archive($espmId, $archive);
            Helper::setFlashMessage(['success' => $archive ? TextConstants::SUCCESS_ARCHIVE : TextConstants::SUCCESS_UNARCHIVE]);
        } else {
            Helper::setFlashMessage(['success' => TextConstants::BAD_REQUEST]);
        }

        $this->redirect()->toRoute('finance/espm/edit', ['controller' => 'edit', 'action' => 'edit', 'id' => $espmId]);
    }
}
