<?php

namespace Backoffice\Controller;

use Backoffice\Form\InputFilter\PartnerGcmValueFilter;
use Backoffice\Form\InputFilter\UserAccountFilter;
use Backoffice\Form\PartnerGcmValueForm;
use Backoffice\Form\UserAccountForm;
use DDD\Service\Partners;

use DDD\Service\User\ExternalAccount;
use Library\Controller\ControllerBase;
use Library\Constants\DomainConstants;
use Library\Constants\TextConstants;
use Library\Finance\Base\Account;
use Library\Utility\Helper;
use Library\ActionLogger\Logger;
use Library\Constants\Roles;
use Library\Constants\Constants;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Backoffice\Form\PartnerForm;
use Backoffice\Form\InputFilter\PartnerFilter;

class PartnersController extends ControllerBase
{
    /**
     * @var bool
     */
    protected $_partnersService = FALSE;

    /**
     * @return ViewModel
     */
    public function indexAction()
    {
        return new ViewModel();
    }

    /**
     * @return ViewModel
     */
    public function addAction()
    {
        $form = new PartnerForm();
        $form->get('submit')->setValue('Create Partner')->setLabel('Create Partner');
        $form->get('submit')->setAttribute('id', 'addPartner');
        $form->get('submit')->setAttribute('data-loading-text', 'It creates ...');
        $form->remove('delete');
        $form->remove('open');

        $form->remove('gid');
        $form->remove('create_date');

        $inputFilter = new PartnerFilter();
    	$form->setInputFilter($inputFilter->getInputFilter());
        $form->prepare();


        $formTemplate  = 'form-templates/partner-form';

        //Source code
        $viewModelForm = new ViewModel();
    	$viewModelForm->setVariables([
            'form'   => $form,
            'action' => 'add',
    	]);
    	$viewModelForm->setTemplate($formTemplate);
    	$viewModel  = new ViewModel();
    	$viewModel->setVariables([
            'form'       => $form,
            'action'     => 'add',
        ]);

    	$viewModel->addChild($viewModelForm, 'formOutput');
    	$viewModel->setTemplate('backoffice/partners/add');

    	return $viewModel;
    }

    /**
     * @return JsonModel
     */
    public function ajaxAddPartnerAction()
    {
        $request = $this->getRequest();
        $partnerService = $this->getPartnersService();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            $form = new PartnerForm();
            $inputFilter = new PartnerFilter();
            $form->setInputFilter($inputFilter->getInputFilter());
            $form->prepare();

            if ($request->isPost()) {
                $params = $request->getPost();
                $form->setData($params);

                if ($form->isValid()) {
                    $params = $form->getData();
                    $partnerResult = $partnerService->addPartner($params);

                    if ($partnerResult) {
                        $result['status'] = 'success';
                        $result['msg'] = TextConstants::SUCCESS_ADD;

                        Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                    }
                } else {
                    $msgs = $form->getMessages();

                    foreach ($msgs as $field => $msg) {
                        $result['msg'] = '<b>' . ucfirst(str_replace('_', ' ', $field)) . ':</b> ' . array_shift($msg);
                    }

                    $result['status'] = 'error';
                }
            } else {
                $result['msg'] = TextConstants::AJAX_NO_POST_ERROR;
            }
        } catch (\Exception $ex) {
            $result['msg'] = TextConstants::PARTNER_SAVE_IMPOSSIBLE;
        }

        return new JsonModel($result);
    }

    /**
     * @return ViewModel
     */
    public function editAction()
    {
        /**
         * @var \DDD\Dao\Geolocation\City $cityDao
         * @var \DDD\Dao\Partners\PartnerCityCommission $partnerCityCommissionDao
         * @var \DDD\Dao\Partners\PartnerGcmValue $partnergcmValueService
         */
        $partnerCityCommissionDao = $this->getServiceLocator()->get('dao_partners_partner_city_commission');
        $auth                     = $this->getServiceLocator()->get('library_backoffice_auth');
        $cityDao                  = $this->getServiceLocator()->get('dao_geolocation_city');

        $id               = $this->params()->fromRoute('id');
        $partnerService   = $this->getPartnersService();
        $partnerLogsArray = [];

        $form        = new PartnerForm();
        $inputFilter = new PartnerFilter();

        $form->setInputFilter($inputFilter->getInputFilter());
        $form->prepare();

        $partnerId = (int)$id;
        $partner   = $partnerService->partnerById($partnerId);

        if (!$partner) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'partners']);
        }

        $partnerAccounts = $partnerService->getPartnerAccounts($partnerId);

        $partnerData['partner-number']            = $partner->getGid();
        $partnerData['partner_name']              = $partner->getPartnerName();
        $partnerData['business_model']            = $partner->getBusinessModel();
        $partnerData['contact_name']              = $partner->getContactName();
        $partnerData['email']                     = $partner->getEmail();
        $partnerData['mobile']                    = $partner->getMobile();
        $partnerData['phone']                     = $partner->getPhone();
        $partnerData['commission']                = $partner->getCommission();
        $partnerData['additional_tax_commission'] = $partner->hasAdditionalTaxCommission();
        $partnerData['account_holder_name']       = $partner->getAccHolderName();
        $partnerData['bank_bsr']                  = $partner->getBankBsr();
        $partnerData['bank_account_num']          = $partner->getBankAccNum();
        $partnerData['notes']                     = $partner->getNotes();
        $partnerData['cubilis_id']                = $partnerAccounts;
        $partnerData['customer_email']            = $partner->getCustomerEmail();
        $partnerData['create_date']               = $partner->getCreateDate();
        $partnerData['active']                    = $partner->getActive();
        $partnerData['is_ota']                    = $partner->getIsOta();
        $partnerData['discount_num']              = $partner->getDiscount();
        $partnerData['show_partner']              = $partner->getShowPartner();
        $partnerData['apply_fuzzy_logic']         = $partner->getApplyFuzzyLogic();
        $partnerData['is_deducted_commission']    = $partner->getIsDeductedCommission();

        if ($partner->getGid() > 0) {
            $partnerLogs = $partnerService->getPartnerLogs($partner->getGid());

            if (count($partnerLogs) > 0) {
                foreach ($partnerLogs as $log) {
                    $rowClass = '';
                    if ($log['user_name'] == TextConstants::SYSTEM_USER) {
                        $rowClass = "warning";
                    }

                    $partnerLogsArray[] = [
                        date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($log['timestamp'])),
                        $log['user_name'],
                        $this->identifyPartnerAction($log['action_id']),
                        $log['value'],
                        "DT_RowClass" => $rowClass
                    ];
                }
            } else {
                $partnerLogsArray = [];
            }
        }

        $form->populateValues($partnerData);
        $form->populateValues(["open" => '//' . DomainConstants::WS_DOMAIN_NAME . '/?gid=' . $partnerData['partner-number']]);

        if ($partnerId == Partners::GINOSI_EMPLOYEE) {
            $form->get('discount_num')->setAttribute('value', '25');
            $form->get('discount_num')->setAttribute('disabled', true);
        }

        if (!$auth->hasRole(Roles::ROLE_PARTNER_DISCOUNTS)) {
            $form->get('discount_num')->setAttribute('disabled', true);
        }

        $formTemplate  = 'form-templates/partner-form';

        $viewModelForm = new ViewModel();
        $viewModelForm->setVariables([
            'form'       => $form,
            'action'     => 'edit',
            'id'         => $partnerId,
            'status'     => $partner->getActive(),
            'partnerURL' => DomainConstants::WS_DOMAIN_NAME . '/?gid=' . $partnerData['partner-number']
        ]);

        $viewModelForm->setTemplate($formTemplate);
        $viewModel = new ViewModel();

        // get city list for specific commission
        $cityList = $cityDao->getCityForPartnerCommission($partnerId);

        // get existing partner city commission list
        $partnerCityCommissionList = $partnerCityCommissionDao->getPartnerCityCommission($partnerId);

        try {
            /**
             * @var \DDD\Dao\Finance\Transaction\TransactionAccounts $transactionAccountDao
             */
            $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
            $transactionAccountId  = $transactionAccountDao->getAccountIdByHolderAndType($partnerId, Account::TYPE_PARTNER);
        } catch (\Exception $e) {
            $transactionAccountId = false;
        }

        $viewModel->setVariables([
            'partnerName'               => $partner->getPartnerName(),
            'partnerId'                 => $partner->getGid(),
            'logs'                      => $partnerLogsArray,
            'status'                    => $partner->getActive(),
            'form'                      => $form,
            'action'                    => 'edit',
            'id'                        => $partnerId,
            'cityList'                  => $cityList,
            'partnerCityCommissionList' => $partnerCityCommissionList,
            'transactionAccountId'      => $transactionAccountId,
            'hasGCMSpecificRole'        => ($auth->hasRole(Roles::ROLE_PARTNER_MANAGEMENT) &&
                                            $auth->hasRole(Roles::ROLE_APARTMENT_MANAGEMENT) &&
                                            $auth->hasRole(Roles::ROLE_APARTMENT_CONNECTION)),
        ]);

        $viewModel->addChild($viewModelForm, 'formOutput');
        $viewModel->setTemplate('backoffice/partners/edit');

        return $viewModel;
    }

    /**
     * @return ViewModel
     */
    public function gcmSpecificAction()
    {
        /**
         * @var \DDD\Dao\Geolocation\City $cityDao
         * @var \DDD\Dao\Partners\PartnerGcmValue $partnergcmValueService
         */
        $partnerGcmValueService   = $this->getServiceLocator()->get('service_partner_gcm_value');
        $auth                     = $this->getServiceLocator()->get('library_backoffice_auth');
        $partnerService           = $this->getServiceLocator()->get('service_partners');

        $id        = $this->params()->fromRoute('id');
        $partnerId = (int)$id;
        $partner   = $partnerService->partnerById($partnerId);

        if (!$partner) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'partners']);
        }

        $viewModel = new ViewModel();

        // get Gcm values form
        $form        = new PartnerGcmValueForm();
        $inputFilter = new PartnerGcmValueFilter();
        $form->setInputFilter($inputFilter->getInputFilter());
        $form->prepare();

        // get Gcm values by partner id
        $partnerGcmValues = $partnerGcmValueService->getByPartnerId($partnerId);

        $viewModel->setVariables([
            'partnerName'        => $partner->getPartnerName(),
            'partnerId'          => $partner->getGid(),
            'action'             => 'gcmSpecific',
            'id'                 => $partnerId,
            'hasGCMSpecificRole' => ($auth->hasRole(Roles::ROLE_PARTNER_MANAGEMENT) &&
                                    $auth->hasRole(Roles::ROLE_APARTMENT_MANAGEMENT) &&
                                    $auth->hasRole(Roles::ROLE_APARTMENT_CONNECTION)),
            'partnerGcmValues'   => $partnerGcmValues,
            'form'               => $form
        ]);

        $viewModel->setTemplate('backoffice/partners/gcm-specific');

        return $viewModel;
    }

    /**
     * @return ViewModel
     */
    public function ajaxSaveGcmSpecificValuesAction()
    {
        $partnerService    = $this->getServiceLocator()->get('service_partners');
        $partnerGcmService = $this->getServiceLocator()->get('service_partner_gcm_value');
        $request           = $this->getRequest();
        $result            = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $params = $request->getPost();

                $form = new PartnerGcmValueForm();
                $form->setData($params);

                if ($form->isValid()) {
                    $params    = $this->params()->fromPost();
                    $partnerId = $this->params()->fromRoute('id');
                    $partner   = $partnerService->partnerById($partnerId);

                    if ($partner) {
                        $response = $partnerGcmService->saveValues($params, $partnerId);
                        if ($response) {
                            $result = [
                                'msg'    => TextConstants::SUCCESS_UPDATE,
                                'status' => 'success'
                            ];
                        }
                    }
                } else {
                    $msgs = $form->getMessages();

                    foreach($msgs as $field => $msg) {
                        $result['msg'] = '<b>' . ucfirst(str_replace('_', ' ', $field)) . ':</b> ' . array_shift($msg);
                    }

                    $result['status'] = 'error';
                }
            } else {
                $result['msg'] = TextConstants::AJAX_NO_POST_ERROR;
            }
        } catch (\Exception $exc) {
            $result['msg'] = TextConstants::PARTNER_SAVE_IMPOSSIBLE;
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function ajaxSavePartnerAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            $partnerService = $this->getPartnersService();

            $form = new PartnerForm();
            $inputFilter = new PartnerFilter();
            $form->setInputFilter($inputFilter->getInputFilter());
            $form->prepare();

            if ($request->isPost()) {
                $params = $request->getPost();
                $form->setData($params);

                if ($form->isValid()) {
                    $params = $form->getData();

                    $userId   = $auth->getIdentity()->id;
                    $response = $partnerService->savePartner($params, $userId);
                    if (!$response) {
                        throw new \Exception();
                    }

                    $result['status'] = 'success';
                    $result['msg'] = TextConstants::SUCCESS_UPDATE;
                } else {
                    $msgs = $form->getMessages();

                    foreach($msgs as $field => $msg) {
                        $result['msg'] = '<b>' . ucfirst(str_replace('_', ' ', $field)) . ':</b> ' . array_shift($msg);
                    }

                    $result['status'] = 'error';
                }
            } else {
                $result['msg'] = TextConstants::AJAX_NO_POST_ERROR;
            }
        } catch (\Exception $exc) {
            $result['msg'] = TextConstants::PARTNER_SAVE_IMPOSSIBLE;
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function ajaxPartnersListAction()
    {
	    /**
	     * @var Partners $partnersService
	     */
        $request         = $this->params();
        $partnersService = $this->getPartnersService();
        $currentPage     = ($request->fromQuery('iDisplayStart') / $request->fromQuery('iDisplayLength')) + 1;
        $result          = [];

        $results = $partnersService->partnersList(
            (int)$request->fromQuery('iDisplayStart'),
            (int)$request->fromQuery('iDisplayLength'),
            (int)$request->fromQuery('iSortCol_0'),
            $request->fromQuery('sSortDir_0'),
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '1')
        );

        $partnersCount = $partnersService->partnersCount($request->fromQuery('sSearch'), $request->fromQuery('all', '1'));

        foreach ($results as $row) {
            $status = $row->getActive()
                ? '<span class="label label-success">Active</span>'
                : '<span class="label label-default">Inactive</span>';

            array_push($result, [
                $status,
                $row->getGid(),
                $row->getPartnerName(),
                $row->getContactName(),
                $row->getEmail(),
                $row->getMobile(),
                $row->getPhone(),
                '<a href="//' . DomainConstants::WS_DOMAIN_NAME . '/?gid=' . $row->getGid() . '" target="_new" class="btn btn-xs btn-info">Open</a>',
                '<a href="/partners/edit/' . $row->getGid() . '" class="btn btn-xs btn-primary" data-html-content="Edit"></a>',
            ]);
        }

        if (!isset($result) || is_null($result)) {
            array_push($result, [' ', '', '', '', '', '', '', '', '']);
        }

        $resultArray = [
            'sEcho'                => $request->fromQuery('sEcho'),
            'iTotalRecords'        => $partnersCount,
            'iTotalDisplayRecords' => $partnersCount,
            'iDisplayStart'        => ($currentPage - 1) * (int)$request->fromQuery('iDisplayLength'),
            'iDisplayLength'       => (int)$request->fromQuery('iDisplayLength'),
            'aaData'               => $result,
        ];

        return new JsonModel($resultArray);
    }

    /**
     * @return JsonModel
     */
    public function activateAction()
    {
        $service   = $this->getPartnersService();
        $partnerId = $this->params()->fromRoute('id', false);
        $status    = $this->params()->fromRoute('status', false);

        if ($partnerId) {
            $result = $service->changeStatus($partnerId, $status);
            $successText = ($status) ? TextConstants::SUCCESS_ACTIVATE : TextConstants::SUCCESS_DEACTIVATE;

            if ($result) {
                Helper::setFlashMessage(['success' => $successText]);
            } else {
                Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
            }
        } else {
            Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
        }

        return new JsonModel([1]);
    }

    /**
     * @return \DDD\Service\Partners
     */
    private function getPartnersService()
    {
        if (!$this->_partnersService) {
            $this->_partnersService = $this->getServiceLocator()->get('service_partners');
        }

        return $this->_partnersService;
    }

    /**
     * @param int $actionId
     * @return string
     */
    private function identifyPartnerAction($actionId)
    {
        $partnerActions = [
            Logger::ACTION_PARTNER_NAME     => 'Partner Name',
            Logger::ACTION_PARTNER_STATUS   => 'Partner Status',
            Logger::ACTION_PARTNER_DISCOUNT => 'Partner Discount',
        ];

        if (isset($partnerActions[$actionId])) {
            return $partnerActions[$actionId];
        }

        return 'not defined';
    }

    /**
     * @return JsonModel
     */
    public function ajaxAddPartnerCityCommissionAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $service = $this->getPartnersService();
                $cityId = (int)$request->getPost('city_id', 0);
                $commission = (int)$request->getPost('commission', 0);
                $partnerId = (int)$request->getPost('partner_id', 0);

                if ($partnerId && $cityId && $commission) {
                    $response = $service->savePartnerCityCommission($partnerId, $cityId, $commission);

                    if ($response['status'] == 'success') {
                        Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                        $result['status'] = 'success';
                    } else {
                        $result['msg'] = $response['msg'];
                    }
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    /**
     * Delete partner city commission
     */
    public function partnerCityCommissionDeleteAction()
    {
        /**
         * @var \DDD\Dao\Partners\PartnerCityCommission $partnerCityCommissionDao
         */
        $partnerId = $this->params()->fromRoute('partner_id', 0);
        $itemId = $this->params()->fromRoute('item_id', 0);

        if ($partnerId && $itemId) {
            $partnerCityCommissionDao = $this->getServiceLocator()->get('dao_partners_partner_city_commission');
            $partnerCityCommissionDao->delete(['id' => $itemId]);
            Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
        }

        $url = $this->url()->fromRoute('backoffice/default', [
            'controller' => 'partners',
            'action'     => 'edit',
            'id'         => $partnerId,
        ]);

        $this->redirect()->toUrl($url . '#commission-part');
    }


    /**
     * Get Partner account list for datatable
     *
     * @return JsonModel
     */
    public function ajaxGetPartnerAccountListAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PARTNER_MANAGEMENT)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::PARTNER_ACCOUNT_CANNOT_BE_MANAGE,
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
        $transactionAccountID  = $transactionAccountDao->getAccountIdByHolderAndType($userId, Account::TYPE_PARTNER);

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
             * @var \DDD\Domain\User\ExternalAccount $externalAccount
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
     * Render Partner Account Form
     *
     * @return string
     */
    public function ajaxPartnerAccountEditAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PARTNER_MANAGEMENT)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::PARTNER_ACCOUNT_CANNOT_BE_MANAGE,
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

        if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost()->get('partner_id')) {
            $postData  = $request->getPost();
            $partnerId = $postData->get('partner_id');

            // get Partner Account form
            $partnerAccountForm = $this->getPartnerAccountForm();

            if ($postData->get('id')) {
                $id = $postData->get('id');
                /**
                 * @var \DDD\Dao\User\ExternalAccount $userAccountDao
                 * @var \DDD\Domain\User\ExternalAccount $externalAccount
                 */
                $externalAccountDao = $this->getServiceLocator()->get('dao_user_external_account');
                $externalAccount    = $externalAccountDao->getById($id);
                $partnerAccountForm->bind($externalAccount);

                if ($externalAccount->getIsDefault() == ExternalAccount::EXTERNAL_ACCOUNT_IS_DEFAULT) {
                    $partnerAccountForm->get('isDefault')->setAttribute('checked', 'checked');
                }

                $vars = [
                    'id' => $externalAccount->getId()
                ];
            }

            $partnerAccountForm->prepare();

            $vars['form']      = $partnerAccountForm;
            $vars['partnerId'] = $partnerId;

            $viewModel = new ViewModel();
            $viewModel->setTemplate('backoffice/partners/edit-account');
            $viewModel->setTerminal(true);
            $viewModel->setVariables($vars);

            return $viewModel;
        }

        return new JsonModel($result);
    }

    /**
     * Save Partner Account data
     *
     * @return JsonModel
     */
    public function ajaxSavePartnerAccountAction()
    {
        /**
         * @var $auth \Library\Authentication\BackofficeAuthenticationService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PARTNER_MANAGEMENT)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::PARTNER_ACCOUNT_CANNOT_BE_MANAGE,
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
                $form        = $this->getPartnerAccountForm();
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
                    $transactionAccountID  = $transactionAccountDao->getAccountIdByHolderAndType($postData->get('partnerId'), Account::TYPE_PARTNER);

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
                                "status" => "error",
                                "msg"    => "Default Account Already Exist",
                            ]);
                        }
                    }

                    $externalAccountDao->save($data, $where);

                    Helper::setFlashMessage(["success" => "Successfully updated."]);

                    return new JsonModel([
                        "status"  => "success",
                        "msg" => "Successfully updated.",
                        "url"     => $this->url()->fromRoute('backoffice/default', ['controller' => 'partners', 'action' => 'edit', 'id' => $postData->get('partnerId')])
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
     * Inactive Partner account
     *
     * @return JsonModel
     */
    public function ajaxSetPartnerAccountArchiveAction()
    {
        // get authentication module
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$auth->hasRole(Roles::ROLE_PARTNER_MANAGEMENT)) {
            return new JsonModel([
                'status' => 'error',
                'msg'    => TextConstants::PARTNER_ACCOUNT_CANNOT_BE_MANAGE,
            ]);
        }

        $request = $this->getRequest();

        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost()->get('partnerId')) {
            $postData         = $request->getPost();
            $partnerAccountId = $postData->get('id');
            $partnerId        = $postData->get('partnerId');

            if ($partnerAccountId) {
                /**
                 * @var \DDD\Dao\User\ExternalAccount $userAccountDao
                 */
                $userAccountDao = $this->getServiceLocator()->get('dao_user_external_account');
                $userAccountDao->save([
                    'status'     => ExternalAccount::EXTERNAL_ACCOUNT_STATUS_ARCHIVED,
                    'is_default' => 0,
                ], [
                    'id' => $partnerAccountId
                ]);

                Helper::setFlashMessage(["success" => "Successfully Deactivated."]);

                return new JsonModel([
                    "status" => "success",
                    "msg"    => "Successfully updated.",
                    "url"    => $this->url()->fromRoute('backoffice/default', ['controller' => 'partners', 'action' => 'edit', 'id' => $partnerId])
                ]);
            }
        }

        return new JsonModel($result);
    }

    /**
     * Get Partner Account Form
     *
     * @return bool|UserAccountForm
     */
    private function getPartnerAccountForm()
    {
        /**
         * @var \DDD\Dao\Geolocation\Countries $countriesDao
         */
        $countriesDao = $this->getServiceLocator()->get('dao_geolocation_countries');
        $countries    = $countriesDao->getCountriesListWithCities();

        return new UserAccountForm($name = 'partner-account-form', $countries);
    }
}
