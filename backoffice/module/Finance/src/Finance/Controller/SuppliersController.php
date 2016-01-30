<?php
namespace Finance\Controller;

use Backoffice\Form\InputFilter\UserAccountFilter;
use Backoffice\Form\UserAccountForm;
use DDD\Service\User\ExternalAccount;
use Library\Constants\Constants;
use Library\Constants\Roles;
use Library\Controller\ControllerBase;
use Library\Finance\Base\Account;
use Library\Utility\Helper;
use Library\Constants\TextConstants;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use DDD\Service\Finance\Suppliers;

use Finance\Form\SupplierForm;

/**
 * SuppliersController
 * @package finance
 */
class SuppliersController extends ControllerBase
{
    protected $_suppliersService = FALSE;

    public function indexAction()
    {
        return new ViewModel();
    }

    public function ajaxSuppliersListAction()
    {
	    /**
	     * @var Suppliers $suppliersService
	     */
	    $request = $this->params();
        $suppliersService = $this->getServiceLocator()->get('service_finance_suppliers');

        $results = $suppliersService->getSuppliersList(
            (integer)$request->fromQuery('iDisplayStart'),
            (integer)$request->fromQuery('iDisplayLength'),
            (integer)$request->fromQuery('iSortCol_0'),
            $request->fromQuery('sSortDir_0'),
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '1')
        );

        $suppliersCount = $suppliersService->getSuppliersCount($request->fromQuery('sSearch'), $request->fromQuery('all', '1'));
        foreach ($results as $row) {
            $status = $row->isActive() ? '<span class="label label-success">Active</span>'
						: '<span class="label label-default">Inactive</span>';
            $result[] = array(
                $status,
                $row->getName(),
                $row->getDescription(),
                '<a href="/finance/suppliers/edit/'.$row->getId().'" class="btn btn-xs btn-primary" data-html-content="Edit"></a>',
            );
        }

        if(!isset($result) or $result === null)
            $result[] = array(' ', '', '', '', '', '', '', '', '');

        $resultArray = array(
            'sEcho' => $request->fromQuery('sEcho'),
            'iTotalRecords' => $suppliersCount,
            'iTotalDisplayRecords' => $suppliersCount,
            'iDisplayStart' => $request->fromQuery('iDisplayStart'),
            'iDisplayLength' => (integer)$request->fromQuery('iDisplayLength'),
            'aaData' => $result,
        );

        return new JsonModel($resultArray);
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function addAction()
    {
    	// creating instance of form
    	$form = new SupplierForm();
    	$form->get('submit')->setValue('Add Supplier');
    	// prepare the form
    	$form->prepare();
        $supplierId = 0;
    	$viewModel  = new ViewModel();
    	$viewModel->setVariables(array(
            'form'      => $form,
            'supplierId' => $supplierId,
            'pageTitle' => $supplierId?'Edit Supplier':'Add Supplier'
    	));
        $viewModel->setTemplate('finance/suppliers/edit');
    	return $viewModel;
    }

    public function ajaxSaveAction()
    {
        $request = $this->getRequest();
        $data = $request->getPost();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];
        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $suppliersService = $this->getServiceLocator()->get('service_finance_suppliers');
                $supplierId = (int) $data['id'];
                unset($data['id']);
                $data['active'] = 1;
                if (!isset($data['ignoreDuplication'])) {
                    $hasActiveDuplicate = $suppliersService->hasActiveOrInactiveDuplicate($data['name'], $supplierId, 1);
                    if ($hasActiveDuplicate !== false) {
                        return new JsonModel([
                            'status' => 'error',
                            'msg' => 'An active Supplier with this name already exists',
                        ]);
                    }
                    $hasInactiveDuplicate = $suppliersService->hasActiveOrInactiveDuplicate($data['name'], $supplierId, 0);
                    if ($hasInactiveDuplicate !== false) {
                        return new JsonModel([
                            'status' => 'warning',
                            'activationUrl' => '<a href="/finance/suppliers/activate/' . $hasInactiveDuplicate->getId() . '/1" ' .
                                'class="btn btn-primary">Activate deactivated duplicate</a>'
                        ]);
                    }
                }
                else {
                    unset($data['ignoreDuplication']);
                }
                $suppliersService->saveSupplier($data, $supplierId);
                $result = [
                    'status' => 'success',
                    'msg' => ($supplierId) ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_ADD,
                ];
                if ($supplierId == 0) {
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                }
            } catch (\Exception $ex) {
                $result['msg'] = $ex->getMessage();
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function editAction()
    {
        $suppliersService = $this->getServiceLocator()->get('service_finance_suppliers');
    	// creating instance of form
    	$form = new SupplierForm();
    	$form->get('submit')->setValue('Save Changes');
    	// prepare the form
    	$form->prepare();
        $supplierId = $this->params("id", 0);
            if ($supplierId){
                $supplier = $suppliersService->getSupplierById($supplierId);
                if (!$supplier) {
                    Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                    return $this->redirect()->toRoute('finance/suppliers_activate');
                }
                $supplierData = [];
                $supplierData['id'] = $supplier->getId();
                $supplierData['name'] = $supplier->getName();
                $supplierData['description'] = $supplier->getDescription();
                $supplierData['active'] = $supplier->isActive();
                $form->populateValues($supplierData);
            }

    	$viewModel  = new ViewModel();
    	$viewModel->setVariables(array(
            'form'      => $form,
            'supplierId' => $supplierId,
            'isActive' => isset($supplierData['active'])?$supplierData['active']:null,
            'pageTitle' => $supplierId?'Edit Supplier':'Add Supplier'
    	));
        $viewModel->setTemplate('finance/suppliers/edit');
    	return $viewModel;
    }

    public function activateAction()
    {
        $service    = $this->getServiceLocator()->get('service_finance_suppliers');
        $supplierId = $this->params()->fromRoute('id', false);
        $status     = $this->params()->fromRoute('status', false);
        if($supplierId) {
            $result = $service->changeStatus($supplierId, $status);
            $successText = ($status) ? TextConstants::SUCCESS_ACTIVATE : TextConstants::SUCCESS_DEACTIVATE;
            if($result){
                Helper::setFlashMessage(['success' => $successText]);
            } else {
                Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
            }
        } else {
            Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
        }
        $this->redirect()->toRoute('finance/suppliers', ['controller' => 'suppliers']);
    }

    /**
     * Get Supplier account list for datatable
     *
     * @return JsonModel
     */
    public function ajaxGetSupplierAccountListAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_SUPPLIERS_MANAGEMENT)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::SUPPLIER_ACCOUNT_CANNOT_BE_MANAGE,
            ]);
        }

        /**
         * @var \DDD\Service\User\ExternalAccount $userAccountService
         * @var \DDD\Service\User
         */
        $externalAccountService = $this->getServiceLocator()->get('service_user_external_account');
        $userId = (int)$this->params()->fromRoute('id', 0);

        if((int) $this->params()->fromQuery('all', 0) == 2) {
            $status = ExternalAccount::EXTERNAL_ACCOUNT_STATUS_ARCHIVED;
        } else {
            $status = ExternalAccount::EXTERNAL_ACCOUNT_STATUS_ACTIVE;
        }

        /**
         * @var \DDD\Dao\Finance\Transaction\TransactionAccounts $transactionAccountDao
         */
        $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
        $transactionAccountID  = $transactionAccountDao->getAccountIdByHolderAndType($userId, Account::TYPE_SUPPLIER);

        /**
         * @var \DDD\Dao\Geolocation\Countries $countriesDao
         */
        $countriesDao = $this->getServiceLocator()->get('dao_geolocation_countries');
        $countriesById = $countriesDao->getCountriesById();

        // custom sorting
        $columns = [
            'is_default', 'name', 'type', 'full_legal_name', 'address', 'countryId', 'iban', 'swft'
        ];

        $sortCol    = $this->params()->fromQuery('iSortCol_0', 0);
        $sortDir    = $this->params()->fromQuery('sSortDir_0', 0);
        $sortSearch = $this->params()->fromQuery('sSearch', 0);

        $sort = [];
        if ($columns[$sortCol]) {
            $sort = [
                $columns[$sortCol] => $sortDir
            ];
        }

        // get query parameters and reservations data
        $externalAccounts = $externalAccountService->getExternalAccountsByParams([
            'transactionAccountID' => $transactionAccountID,
            'status'               => $status,
            'sort'                 => $sort,
            'search'               => $sortSearch
        ]);

        $data = [];
        foreach ($externalAccounts as $key => $externalAccount) {
            /**
             * @var \DDD\Domain\User\ExternalAccount $userAccount
             */
            if ($externalAccount->getIsDefault() == ExternalAccount::EXTERNAL_ACCOUNT_IS_DEFAULT) {
                $data[$key][] = '<span class="label label-primary">Default</span>';
            } else {
                $data[$key][] = '';
            }

            $data[$key][] = $externalAccount->getName();
            if ($externalAccount->getType() == ExternalAccount::EXTERNAL_ACCOUNT_TYPE_DIRECT_DEPOSIT) {
                $data[$key][] = 'Direct Deposit';
            } elseif ($externalAccount->getType() == ExternalAccount::EXTERNAL_ACCOUNT_TYPE_CHECK) {
                $data[$key][] = 'Check';
            } elseif ($externalAccount->getType() == ExternalAccount::EXTERNAL_ACCOUNT_TYPE_CASH) {
                $data[$key][] = 'Cash';
            } elseif ($externalAccount->getType() == ExternalAccount::EXTERNAL_ACCOUNT_TYPE_COMPANY_CARD) {
                $data[$key][] = 'Company Card';
            }
            $data[$key][] = $externalAccount->getFullLegalName();

            $addressString = '';
            if (strlen($externalAccount->getBillingAddress()) > 0) {
                $addressString .= $externalAccount->getBillingAddress() . '<br>';
            }
            if (strlen($externalAccount->getMailingAddress()) > 0) {
                $addressString .= $externalAccount->getMailingAddress() . '<br>';
            }
            if (strlen($externalAccount->getBankAddress()) > 0) {
                $addressString .= $externalAccount->getBankAddress() . '<br>';
            }
            $data[$key][] = $addressString;
            $data[$key][] = $countriesById[$externalAccount->getCountryId()]->getName();
            $data[$key][] = $externalAccount->getIban();
            $data[$key][] = $externalAccount->getSwft();

            if ($externalAccount->getStatus() == ExternalAccount::EXTERNAL_ACCOUNT_STATUS_ARCHIVED) {
                // special for datatable edit link
                $data[$key][] = 0;
            } else {
                // special for datatable edit link
                $data[$key][] = $externalAccount->getId();
            }
        }

        return new JsonModel(
            [
                'iTotalRecords'        => sizeof($data),
                "aaData"               => $data,
                'sEcho'                => $this->params()->fromQuery('sEcho'),
                'iDisplayStart'        => $this->params()->fromQuery('iDisplayStart'),
                'iDisplayLength'       => $this->params()->fromQuery('iDisplayLength'),
                'iTotalDisplayRecords' => sizeof($data),
            ]
        );
    }

    /**
     * Render Supplier Account Form
     *
     * @return string
     */
    public function ajaxSupplierAccountEditAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_SUPPLIERS_MANAGEMENT)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::SUPPLIER_ACCOUNT_CANNOT_BE_MANAGE,
            ]);
        }

        /**
         * @var array|\ArrayObject[] $resultJson
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost()->get('supplier_id')) {
            $postData   = $request->getPost();
            $supplierId = $postData->get('supplier_id');

            // get User Account form
            $supplierAccountForm = $this->getSupplierAccountForm();

            if ($postData->get('id')) {
                $id = $postData->get('id');
                /**
                 * @var \DDD\Dao\User\ExternalAccount $userAccountDao
                 */
                $externalAccountDao = $this->getServiceLocator()->get('dao_user_external_account');
                $externalAccount    = $externalAccountDao->getById($id);
                $supplierAccountForm->bind($externalAccount);

                if ($externalAccount->getIsDefault() == ExternalAccount::EXTERNAL_ACCOUNT_IS_DEFAULT) {
                    $supplierAccountForm->get('isDefault')->setAttribute('checked', 'checked');
                }

                $vars = [
                    'id' => $externalAccount->getId()
                ];
            }

            $supplierAccountForm->prepare();

            $vars['form']       = $supplierAccountForm;
            $vars['supplierId'] = $supplierId;

            $viewModel = new ViewModel();
            $viewModel->setTemplate('finance/suppliers/edit-account');
            $viewModel->setTerminal(true);
            $viewModel->setVariables($vars);

            return $viewModel;
        }

        return new JsonModel($result);
    }

    /**
     * Save Supplier Account data
     *
     * @return JsonModel
     */
    public function ajaxSaveSupplierAccountAction()
    {
        /**
         * @var $auth \Library\Authentication\BackofficeAuthenticationService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_SUPPLIERS_MANAGEMENT)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::SUPPLIER_ACCOUNT_CANNOT_BE_MANAGE,
            ]);
        }

        $request = $this->getRequest();

        $result = [
            'status'  => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $postData = $request->getPost();

            try {
                $form        = $this->getSupplierAccountForm();
                $inputFilter = new UserAccountFilter();

                $form->setInputFilter($inputFilter->getInputFilter());
                $form->setData($postData);
                $form->prepare();

                if ($form->isValid()) {
                    $validData = $form->getData();

                    /**
                     * @var \DDD\Dao\Finance\Transaction\TransactionAccounts $transactionAccountDao
                     */
                    $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
                    $transactionAccountID  = $transactionAccountDao->getAccountIdByHolderAndType($postData->get('supplierId'), Account::TYPE_SUPPLIER);

                    $where = [];
                    $data  = [
                        'name'                   => $validData['name'],
                        'transaction_account_id' => $transactionAccountID,
                        'type'                   => $validData['type'],
                        'full_legal_name'        => $validData['fullLegalName'],
                        'billing_address'        => $validData['billingAddress'],
                        'mailing_address'        => $validData['mailingAddress'],
                        'bank_address'           => $validData['bankAddress'],
                        'country_id'             => $validData['countryId'],
                        'status'                 => $validData['status'],
                        'is_default'             => $postData->offsetExists('isDefault') ? ExternalAccount::EXTERNAL_ACCOUNT_IS_DEFAULT : 0,
                        'iban'                   => $validData['iban'],
                        'swft'                   => $validData['swft'],
                        'creator_id'             => $auth->getIdentity()->id
                    ];

                    if ($postData->get('id')) {
                        $where['id']           = $postData->get('id');
                    } else {
                        $data['creation_date'] = date(Constants::DATABASE_DATE_TIME_FORMAT);
                        $data['status']        = ExternalAccount::EXTERNAL_ACCOUNT_STATUS_ACTIVE;
                    }

                    /**
                     * @var \DDD\Dao\User\ExternalAccount $userAccountDao;
                     */
                    $externalAccountDao = $this->getServiceLocator()->get('dao_user_external_account');

                    // check unique isDefault column
                    $defaultAccount = $externalAccountDao->checkDefault($data['transaction_account_id']);
                    if ($defaultAccount && $data['is_default'] == ExternalAccount::EXTERNAL_ACCOUNT_IS_DEFAULT) {
                        if (!isset($where['id']) || ($defaultAccount->getId() != $where['id'])) {
                            return new JsonModel([
                                "status"  => "error",
                                "msg" => "Default Account Already Exist",
                                "url"     => $this->url()->fromRoute('finance/suppliers', ['controller' => 'suppliers', 'action' => 'edit', 'id' => $postData->get('supplierId')])
                            ]);
                        }
                    }

                    $externalAccountDao->save($data, $where);

                    Helper::setFlashMessage(["success" => "Successfully updated."]);

                    return new JsonModel([
                        "status"  => "success",
                        "msg" => "Successfully updated.",
                        "url"     => $this->url()->fromRoute('finance/suppliers', ['controller' => 'suppliers', 'action' => 'edit', 'id' => $postData->get('supplierId')])
                    ]);
                } else {
                    foreach ($form->getMessages() as $messageId => $messages) {
                        $result['msg'] = '';
                        foreach ($messages as $message) {
                            $result['msg'] .= ' Validation failure ' . $messageId . ' : ' . $message;
                        }
                    }
                }
            } catch (\Exception $e) {
                $result['msg'] = $e->getMessage();
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    /**
     * Inactive Supplier account
     *
     * @return JsonModel
     */
    public function ajaxSetSupplierAccountArchiveAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_SUPPLIERS_MANAGEMENT)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::SUPPLIER_ACCOUNT_CANNOT_BE_MANAGE,
            ]);
        }

        $request = $this->getRequest();

        $result = [
            'status'  => 'error',
            'message' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost()->get('supplierId')) {
            $postData          = $request->getPost();
            $supplierAccountId = $postData->get('id');
            $supplierId        = $postData->get('supplierId');

            if ($supplierAccountId) {
                /**
                 * @var \DDD\Dao\User\ExternalAccount $userAccountDao
                 */
                $userAccountDao = $this->getServiceLocator()->get('dao_user_external_account');
                $userAccountDao->save([
                    'status'     => ExternalAccount::EXTERNAL_ACCOUNT_STATUS_ARCHIVED,
                    'is_default' => 0,
                ], [
                    'id' => $supplierAccountId
                ]);

                Helper::setFlashMessage(["success" => "Successfully Deactivated."]);

                return new JsonModel([
                    "status"  => "success",
                    "message" => "Successfully updated.",
                    "url"     => $this->url()->fromRoute('finance/suppliers', ['controller' => 'suppliers', 'action' => 'edit', 'id' => $supplierId])
                ]);
            }
        }

        return new JsonModel($result);
    }

    /**
     * Get Supplier Account Form
     *
     * @return bool|UserAccountForm
     */
    private function getSupplierAccountForm()
    {
        /**
         * @var \DDD\Dao\Geolocation\Countries $countriesDao
         */
        $countriesDao = $this->getServiceLocator()->get('dao_geolocation_countries');
        $countries    = $countriesDao->getCountriesListWithCities();

        return new UserAccountForm($name = 'supplier-account-form', $countries);
    }
}
