<?php

namespace Finance\Controller;

use Finance\Form\InputFilter\MoneyAccountFilter;
use Finance\Form\MoneyAccount;
use Finance\Form\MoneyAccountsDocumentsForm;

use DDD\Dao\Finance\Bank;
use DDD\Dao\Psp\Psp;

use DDD\Service\Finance\Transaction\BankTransaction;
use DDD\Service\MoneyAccount as MoneyAccountService;

use Library\Constants\Constants;
use Library\Finance\Base\TransactionBase;
use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Debug;
use Library\Utility\Helper;

use Zend\Db\ResultSet\ResultSet;
use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

use FileManager\Constant\DirectoryStructure;

class MoneyAccountController extends ControllerBase
{
    /**
     * @return ViewModel
     */
    public function indexAction()
    {
        /**
         * @var Request $request
         * @var BackofficeAuthenticationService $auth
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        return new ViewModel([
            'bankAccountCreator' => $auth->hasRole(Roles::ROLE_MONEY_ACCOUNT_CREATOR)
        ]);
    }

    /**
     * @return JsonModel
     */
    public function ajaxMoneyAccountListAction()
    {
        /** @var BackofficeAuthenticationService $auth */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        /** @var MoneyAccountService $moneyAccountService */
        $moneyAccountService = $this->getServiceLocator()->get('service_money_account');
        $params = $this->params();

        /** @var \DDD\Domain\MoneyAccount\MoneyAccount[]|ResultSet $moneyAccounts */
        $moneyAccounts = $moneyAccountService->moneyAccountList(
            (int)$params->fromQuery('iDisplayStart'),
            (int)$params->fromQuery('iDisplayLength'),
            (int)$params->fromQuery('iSortCol_0'),
            $params->fromQuery('sSortDir_0'),
            $params->fromQuery('sSearch'),
            $params->fromQuery('all', '1'),
            $auth->getIdentity()->id
        );

        $tableData = [];

        if ($moneyAccounts->count()) {
            foreach ($moneyAccounts as $moneyAccount) {
                $router = $this->getEvent()->getRouter();
                $editUrl         = $router->assemble([
                    'controller' => 'money-account',
                    'action'     => 'edit',
                    'id'         => $moneyAccount->getId(),
                ], ['name' => 'finance/default']);
                $transactionsUrl = $router->assemble([
                    'controller' => 'money-account',
                    'action'     => 'transactions',
                    'id'         => $moneyAccount->getId(),
                ], ['name' => 'finance/default']
                );

                $editBtn = '';
                $transactionsBtn = '';
                if ($auth->hasRole(Roles::ROLE_MONEY_ACCOUNT_GLOBAL_MANAGER) || $moneyAccount->isManager()) {
                    $editBtn = '<a class="btn btn-xs btn-primary" href="' . $editUrl . '" target="_blank" data-html-content="Edit"></a>';
                    $transactionsBtn = '<a class="btn btn-xs btn-primary" href="' . $transactionsUrl . '" target="_blank">Transactions</a>';
                } else if ($auth->hasRole(Roles::ROLE_MONEY_ACCOUNT_GLOBAL_MANAGER) || $moneyAccount->hasTransactionsView()) {
                    $transactionsBtn = '<a class="btn btn-xs btn-primary" href="' . $transactionsUrl . '" target="_blank">Transactions</a>';
                } else {
                    continue;
                }

                $status          = (
                $moneyAccount->getActive()
                    ? '<span class="label label-success">Active</span>'
                    : '<span class="label label-default">Inactive</span>'
                );

                array_push($tableData, [
                    '<div class="text-center">' . $status . '</div>',
                    $moneyAccount->getName(),
                    MoneyAccountService::getMoneyAccountTypeById($moneyAccount->getType()),
                    number_format($moneyAccount->getBalance(), 2),
                    $moneyAccount->getCurrencyName(),
                    $editBtn,
                    $transactionsBtn,
                ]);
            }
        }

        $moneyAccountCount = $moneyAccountService->moneyAccountCount($params->fromQuery('sSearch'), $params->fromQuery('all', '1'));

        $resultArray = [
            'sEcho'                => $params->fromQuery('sEcho'),
            'iTotalRecords'        => count($tableData),
            'iTotalDisplayRecords' => count($tableData),
            'iDisplayStart'        => $params->fromQuery('iDisplayStart'),
            'iDisplayLength'       => (int)$params->fromQuery('iDisplayLength'),
            'aaData'               => $tableData,
        ];

        return new JsonModel($resultArray);
    }

    /**
     * @return ViewModel
     */
    public function editAction()
    {
        /** @var BackofficeAuthenticationService $auth */
        $auth       = $this->getServiceLocator()->get('library_backoffice_auth');
        $docService = $this->getServiceLocator()->get('service_money_account_attachment');
        /** @var Bank $bankDao */
        $bankDao = $this->getServiceLocator()->get('dao_finance_bank');
        /** @var MoneyAccountService $moneyAccountService */
        $moneyAccountService = $this->getServiceLocator()->get('service_money_account');
        /** @var Request $request */
        $request         = $this->getRequest();

        $moneyAccountId  = $this->params()->fromRoute('id');
        $bankData        = false;
        $bankAccountData = false;
        $error           = false;

        $permissionLevel = $moneyAccountService->composePermissionsOnMoneyAccount($moneyAccountId);

        // If don't have permission to manage this money account / create money account
        if (!($permissionLevel & MoneyAccountService::PERMISSION_MANAGE_ACCOUNT)) {
            return $this->redirect()->toRoute('finance/default', ['controller' => 'money-account']);
        }

        if ($moneyAccountId) {
            /** @var Psp $pspDao */
            $pspDao          = $this->getServiceLocator()->get('dao_psp_psp');
            $pspList         = $pspDao->getPspListByMoneyAccountID($moneyAccountId);
            $bankAccountData = $moneyAccountService->getMoneyAccountData($moneyAccountId);

            if (!$bankAccountData) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                return $this->redirect()->toRoute('finance/money-account');
            }

            if ($bankAccountData['bank_id']) {
                $bankData = $bankDao->getBankById($bankAccountData['bank_id']);
            }
        }

        $form = new MoneyAccount($this->getServiceLocator(), $bankAccountData, $moneyAccountId);
        $form->setInputFilter(new MoneyAccountFilter());
        $form->prepare();
        $form = $moneyAccountService->fillData($form, $moneyAccountId);

        if ($request->isPost()) {
            $postData = $request->getPost();
            $form->setData($postData);

            if ($form->isValid()) {
                if ($moneyAccountService->saveMoneyAccount($postData, $moneyAccountId, $auth->getIdentity()->id)) {
                    Helper::setFlashMessage(['success' => 'Money account successfully ' . ($moneyAccountId ? 'edited' : 'added')]);
                    $this->redirect()->toRoute('finance/default', ['controller' => 'money-account', 'action' => 'edit', 'id' => $moneyAccountId]);
                } else {
                    $error = TextConstants::SERVER_ERROR;
                }
            } else {
                $error = $form->getMessages();
            }

            $form->populateValues($postData);
        } else {
            if ($moneyAccountId) {
                if ($bankAccountData) {
                    $form->populateValues($bankAccountData);
                } else {
                    $this->redirect()->toRoute('finance/default', ['controller' => 'money-account']);
                }
            }
        }

        $moneyAccountDocLists = $docService->getAttachments($moneyAccountId);
        $moneyAccountDocData  = [];
        $downloadAction       = '';

        foreach ($moneyAccountDocLists as $key => $moneyAccountDocList) {
            if (isset($moneyAccountDocList['filePaths'][0])) {
                $downloadUrl = $this->url()->fromRoute('finance/money-account-download', [
                    'doc_id'           => $moneyAccountDocList['id'],
                    'money_account_id' => $moneyAccountId,
                ]);

                $downloadAction = "<button type='button' class='btn btn-xs btn-primary self-submitter state downloadViewButton' value='{$downloadUrl}'><span class='glyphicon glyphicon-download'></span> Download</button>";
            }

            $deleteAction =
                '<a class="btn btn-xs btn-danger pull-right deleteAttachment" ' .
                'href="javascript:void(0)" data-docid="' . $moneyAccountDocList['id'] .
                '" data-moneyaccountid="' . $moneyAccountId . '">Delete</a>';

            array_push($moneyAccountDocData, [
                $moneyAccountDocList['createdDate'],
                $moneyAccountDocList['attacher'],
                $moneyAccountDocList['description'],
                $downloadAction,
                $deleteAction
            ]);
        }

        isset($moneyAccountDocData[0])
            ? $attachment = json_encode($moneyAccountDocData)
            : $attachment = 0;

        return new ViewModel([
            'form'                 => $form,
            'error'                => $error,
            'id'                   => $moneyAccountId,
            'bank'                 => $bankData,
            'status'               => (isset($bankAccountData) ? (int)$bankAccountData['active'] : false),
            'globalManager'        => $auth->hasRole(Roles::ROLE_MONEY_ACCOUNT_GLOBAL_MANAGER),
            'moneyAccountsDocForm' => $this->getMoneyAccountsDocFormData($moneyAccountId),
            'moneyAccountDocList'  => $attachment,
            'pspList'              => (isset($pspList) ? $pspList : new \ArrayObject()),
        ]);
    }

    /**
     * @return JsonModel
     */
    public function activateAction()
    {
        /**
         * @var Request $request
         */
        $service        = $this->getServiceLocator()->get('service_money_account');
        $request        = $this->getRequest();
        $status         = $request->getPost('status');
        $moneyAccountId = $request->getPost('id');
        $result         = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            if ($service->activateMoneyAccount($moneyAccountId, $status)) {
                Helper::setFlashMessage(['success' => 'Money Account successfully ' . ($status ? 'activated' : 'deactivated')]);
                $result = [
                    'status' => 'success',
                    'msg'    => 'Money Account successfully ' . ($status ? 'activated' : 'deactivated'),
                ];
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    /**
     * @return ViewModel
     */
    public function transactionsAction()
    {
        /**
         * @var BankTransaction $transactionService
         */
        $transactionService  = $this->getServiceLocator()->get('service_finance_transaction_bank_transaction');
        $moneyAccountService = $this->getServiceLocator()->get('service_money_account');
        $moneyAccountId      = $this->params()->fromRoute('id');
        $auth                = $this->getServiceLocator()->get('library_backoffice_auth');
        $router              = $this->getEvent()->getRouter();

        $transactions  = $transactionService->getMoneyAccountTransactions($moneyAccountId);
        $isVerified    = TransactionBase::IS_VERIFIED;
        $isNotVerified = TransactionBase::IS_UNVERIFIED;

        $permissionLevel = $moneyAccountService->composePermissionsOnMoneyAccount($moneyAccountId);

        // If don't have permission to manage this money account / create money account
        if (!($permissionLevel & MoneyAccountService::PERMISSION_VIEW_TRANSACTIONS)) {
            return $this->redirect()->toRoute('finance/default', ['controller' => 'money-account']);
        }

        $transactionsTableData = [];
        if ($transactions->count()) {
            foreach ($transactions as $transaction) {
                $canBeCombined = $creditCol = $debitCol = $verifyBtn = $voidBtn = $viewBtn = '';
                $rowClasses    = [];

                if ($transaction['is_voided']) {
                    array_push($rowClasses, 'row-faded');
                }

                if ($permissionLevel & MoneyAccountService::PERMISSION_MANAGE_TRANSACTIONS) {
                    if (!$transaction['is_voided']) {
                        $voidBtn = '<a class="btn btn-danger btn-xs btn-void" data-id="' . $transaction['id'] . '" id="btn-void-' . $transaction['id'] . '">Void</a>';
                    }

                    if ($transaction['is_verified']) {
                        $verifyBtn = '<a class="btn btn-danger btn-xs btn-toggle-verify" data-id="' . $transaction['id'] . '"  data-status="' . $isNotVerified . '">Unverify</a>';
                    } else {
                        $verifyBtn = '<a class="btn btn-success btn-xs btn-toggle-verify" data-id="' . $transaction['id'] . '"  data-status="' . $isVerified . '">Verify</a>';
                    }

                    $viewBtn = '<a href="'
                        . $router->assemble([
                            'controller' => 'money-account',
                            'action'     => 'transaction-details',
                            'id'         => $transaction['id'],
                        ], ['name' => 'finance/money-account'])
                        . '" target="_blank" class="btn btn-xs btn-primary">Details</a>';
                }

                if ($transaction['amount'] > 0) {
                    $creditCol     = $transaction['currency_sign'] . number_format($transaction['amount'], 2);
                    $canBeCombined = '';
                } else {
                    $debitCol = $transaction['currency_sign'] . number_format(abs($transaction['amount']), 2);
                    if ($auth->hasRole(Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL)) {
                        $canBeCombined = '<input type="checkbox" class="combine-money-transactions" data-id="' . $transaction['id'] . '">';
                    }
                }

                if ($transaction['is_verified']) {
                    array_push($rowClasses, 'success');
                }

                array_push($transactionsTableData, [
                    $transaction['is_voided'] ? 'voided' : 'active',
                    $canBeCombined,
                    $transaction['id'],
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($transaction['date'])),
                    $transaction['description'],
                    $creditCol,
                    $debitCol,
                    $verifyBtn,
                    $voidBtn,
                    $viewBtn,
                    'DT_RowClass' => implode(' ', $rowClasses)
                ]);
            }
        }

        return new ViewModel([
            'transactionsTableData'             => $transactionsTableData,
            'accountName'                       => $moneyAccountService->getMoneyAccountName($moneyAccountId),
            'accountId'                         => $moneyAccountId,
            'hasPoAndTransferManagerGlobalRole' => $auth->hasRole(Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL),
            'isVerified'                        => $isVerified,
            'isNotVerified'                     => $isNotVerified,
            'permissionLevel'                   => $permissionLevel
        ]);
    }

    /**
     * @return ViewModel
     */
    public function transactionDetailsAction()
    {
        /** @var BankTransaction $transactionService */
        $transactionService = $this->getServiceLocator()->get('service_finance_transaction_bank_transaction');
        /** @var Logger $logger */
        $logger = $this->getServiceLocator()->get('ActionLogger');

        $transactionId = $this->params()->fromRoute('id');
        $transactions  = $transactionService->getMoneyAccountTransactionDetails($transactionId);

        $logData = $logger->getDatatableData(
            Logger::MODULE_MONEY_ACCOUNT,
            $transactionId
        );

        return new ViewModel([
            'transactions' => $transactions,
            'logData'      => $logData
        ]);
    }

    /**
     * @return JsonModel
     */
    public function ajaxUploadFilesAction()
    {
        $moneyAccountDocService = $this->getServiceLocator()->get('service_money_account_attachment');
        $request                = $this->getRequest();
        $result                 = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $fileSize = 0;

                foreach ($request->getFiles() as $key => $value) {
                    $fileSize += $value['size'];
                }

                if (($fileSize) > DirectoryStructure::FS_UPLOAD_MAX_FILE_SIZE) {
                    $result = [
                        'status' => 'error',
                        'msg'    => TextConstants::FILE_SIZE_EXCEEDED,
                    ];

                    return new JsonModel($result);
                }

                $data       = (array)$request->getPost();
                $auth       = $this->getServiceLocator()->get('library_backoffice_auth');
                $attacherId = $auth->getIdentity()->id;

                if (empty(trim(strip_tags($data['doc_description'])))) {
                    $result = [
                        'status' => 'error',
                        'msg'    => TextConstants::ERROR_DESCRIPTION_EMPTY,
                    ];

                    return new JsonModel($result);
                }

                $docData = [
                    'money_account_id' => $data['money_account_id'],
                    'attacher_id'      => $attacherId,
                    'description'      => trim(strip_tags($data['doc_description'])),
                    'created_date'     => date('Y-m-d H:i:s')
                ];

                $docId = $moneyAccountDocService->saveDocData($docData);

                if ($docId) {
                    $response = $moneyAccountDocService->uploadFile($request, $docId, $data['money_account_id']);

                    if ($response) {
                        $result = [
                            'status' => 'success',
                            'msg'    => TextConstants::SUCCESS_ADD,
                        ];

                        Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                    }
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function ajaxDeleteAttachmentAction()
    {
        $moneyAccountDocService = $this->getServiceLocator()->get('service_money_account_attachment');
        $request                = $this->getRequest();
        $result                 = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $moneyAccountId = (int)$request->getPost('money_account_id');
                $docId          = (int)$request->getPost('doc_id');

                $response = $moneyAccountDocService->deleteAttachment($docId, $moneyAccountId);

                if ($response) {
                    $result = [
                        'status' => 'success',
                        'msg'    => TextConstants::SUCCESS_DELETE,
                    ];
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    /**
     * @return bool
     */
    public function downloadAction()
    {
        $docId          = (int)$this->params()->fromRoute('doc_id', 0);
        $moneyAccountId = (int)$this->params()->fromRoute('money_account_id', 0);

        if ($docId && $moneyAccountId) {
            $docFileDao = $this->getServiceLocator()->get('dao_money_account_attachment_item');

            $files = $docFileDao->getDocFiles($moneyAccountId, $docId);
            $paths = [];

            foreach ($files as $file) {
                $year  = date('Y', strtotime($file->getCreatedDate()));
                $month = date('m', strtotime($file->getCreatedDate()));

                $path['path'] = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_UPLOADS_ROOT
                    . DirectoryStructure::FS_UPLOADS_MONEY_ACCOUNT_DOCUMENTS
                    . $year . '/' . $month . '/'
                    . $moneyAccountId . '/' . $docId . '/' . $file->getAttachment();

                $path['name'] = $file->getAttachment();
                $paths[]      = $path;
            }

            $filename = 'money_account_' . $moneyAccountId . '_attachment_' . $docId . '.zip';

            $zipFileFullPathname = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_UPLOADS_ROOT
                . DirectoryStructure::FS_UPLOADS_TMP
                . $filename;
            $zipFilePath         = DirectoryStructure::FS_UPLOADS_TMP . $filename;
            $zip                 = new \ZipArchive;

            $zip->open($zipFileFullPathname, \ZipArchive::CREATE);

            foreach ($paths as $path) {
                $zip->addFile($path['path'], $path['name']);
            }

            $zip->close();

            /**
             * @var \FileManager\Service\GenericDownloader $genericDownloader
             */
            $genericDownloader = $this->getServiceLocator()->get('fm_generic_downloader');

            $genericDownloader->downloadAttachment($zipFilePath);

            if ($genericDownloader->hasError()) {
                Helper::setFlashMessage(['error' => $genericDownloader->getErrorMessages(true)]);

                if ($this->getRequest()->getHeader('Referer')) {
                    $url = $this->getRequest()->getHeader('Referer')->getUri();
                    $this->redirect()->toUrl($url);
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @return JsonModel
     */
    public function ajaxGetFullAddressAction()
    {
        $request = $this->getRequest();
        $result  = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $userId      = (int)$request->getPost('id');
                $userService = $this->getServiceLocator()->get('service_user');

                $userFullAddress = $userService->getUserFullAddress($userId);
                if ($userFullAddress) {
                    $result = [
                        'status'       => 'success',
                        'full_address' => $userFullAddress
                    ];
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function combineTransactionsAction()
    {
        /**
         * @var Request $request
         */
        $service        = $this->getServiceLocator()->get('service_money_account');
        $request        = $this->getRequest();
        $transactionIds = $request->getPost('money_transaction_ids');
        $result         = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];
//        try {
        if ($request->isPost() && $request->isXmlHttpRequest()) {
            if ($service->combineTransactions($transactionIds)) {
                Helper::setFlashMessage(['success' => 'Money Accounts combined successfully']);
                $result = [
                    'status' => 'success',
                    'msg'    => 'OK'
                ];
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }
//        } catch (\Exception $e) {
//            // do nothing
//        }
        return new JsonModel($result);
    }

    /**
     * @return JsonModel
     */
    public function changeVerifyStatusAction()
    {
        /**
         * @var \DDD\Service\Finance\Transaction\BankTransaction $service
         */
        /** @var Request $request */
        $request = $this->getRequest();
        $result  = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $id      = (int)$request->getPost('id');
                $status  = (int)$request->getPost('status');
                $service = $this->getServiceLocator()->get('service_finance_transaction_bank_transaction');
                if ($service->changeVerifyStatus($id, $status)) {
                    $result = [
                        'status'        => 'success',
                        'verify_status' => $status,
                        'msg'           => $status ? TextConstants::SUCCESS_TRANSACTION_VERIFIED : TextConstants::SUCCESS_TRANSACTION_UNVERIFIED,
                    ];
                }
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function voidAction()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        $result  = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $id      = (int)$request->getPost('id');
                /** @var \DDD\Service\Finance\Transaction\BankTransaction $service */
                $service = $this->getServiceLocator()->get('service_finance_transaction_bank_transaction');
                if ($service->voidTransaction($id)) {
                    $result = [
                        'status'        => 'success',
                        'msg'           => TextConstants::SUCCESS_TRANSACTION_VOIDED,
                    ];
                }
            } catch(\RuntimeException $runtimeException) {
                $result['msg'] = $runtimeException->getMessage();
            } catch(\Exception $exception) {
                $result['msg'] = TextConstants::SERVER_ERROR;
            }
        } else {
            $result['msg'] = TextConstants::BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    /**
     * @param $moneyAccountId
     * @return MoneyAccountsDocumentsForm
     */
    protected function getMoneyAccountsDocFormData($moneyAccountId)
    {
        $options = ['moneyAccountId' => $moneyAccountId];

        $form = new MoneyAccountsDocumentsForm($options);

        return $form;
    }
}
