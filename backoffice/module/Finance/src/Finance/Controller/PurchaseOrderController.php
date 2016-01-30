<?php

namespace Finance\Controller;

use DDD\Service\Currency;
use DDD\Service\Finance\Expense\ExpenseAttachments;
use DDD\Service\Finance\Expense\ExpenseItemCategories;
use DDD\Service\Finance\Expense\ExpenseTicket;
use DDD\Service\Finance\Suppliers;
use DDD\Service\Finance\TransactionAccount;
use DDD\Service\MoneyAccount;
use DDD\Service\Office;
use DDD\Service\Partners;
use DDD\Service\User;
use Finance\Form;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Finance\Exception\ExpenseCustomException;
use Library\Finance\Exception\NotFoundException;
use Library\Utility\CsvGenerator;
use Library\Utility\Debug;
use Library\Utility\Helper;
use Library\Finance\Process\Expense\Helper as FinHelper;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class PurchaseOrderController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var ExpenseItemCategories $expenseCategoryService
         * @var BackofficeAuthenticationService $auth
         * @var MoneyAccount $moneyAccountService
         * @var User $usersService
         * @var Currency\Currency $currencyService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $usersService = $this->getServiceLocator()->get('service_user');
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');

        // Permission Shortcuts
        $isFinance = $auth->hasRole(Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL);
        $isBudgetHolder = $auth->hasRole(Roles::ROLE_FINANCE_BUDGET_HOLDER);
        $userId = $auth->getIdentity()->id;

        $users = $usersService->getPeopleListAsArray(false, false);
        $currencies = $currencyService->getSimpleCurrencyList();

        $form = new Form\ExpenseSearch($users, $currencies);

        return new ViewModel([
            'form' => $form,
            'isFinance' => $isFinance,
            'isBudgetHolder' => $isBudgetHolder,
            'userId' => $userId,
            'hasPOCreatorRole' => $auth->hasRole(Roles::ROLE_EXPENSE_CREATOR)
        ]);
    }

    public function addAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var ExpenseTicket $expenseService
         * @var User $usersService
         * @var \DDD\Service\Finance\Budget $budgetService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $usersService = $this->getServiceLocator()->get('service_user');
        $budgetService = $this->getServiceLocator()->get('service_finance_budget');

        $peopleList = $usersService->getPeopleListWithManagersForSelect();
        $budgetHoldersList = $usersService->getBudgetHolderList();
        $budgetList = $budgetService->getBudgetsForPO();

        $form = new Form\ExpenseTicket($budgetHoldersList, $budgetList);

        return (new ViewModel([
            'peopleList' => $peopleList,
            'form' => $form,
            'auth' => $auth,
            'period' => $expenseService->periodTransformer(),
        ]))->setTemplate('finance/purchase-order/ticket');
    }

    public function editAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var ExpenseTicket $expenseService
         * @var ExpenseAttachments $attachmentService
         * @var MoneyAccount $moneyAccountService
         * @var User $usersService
         * @var \DDD\Service\Currency\Currency $currencyService
         * @var \DDD\Service\Finance\Budget $budgetService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $attachmentService = $this->getServiceLocator()->get('service_finance_expense_expenses_attachments');
        $moneyAccountService = $this->getServiceLocator()->get('service_money_account');
        $usersService = $this->getServiceLocator()->get('service_user');
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');
        $budgetService = $this->getServiceLocator()->get('service_finance_budget');

        $ticketId = $this->params()->fromRoute('id');
        $itemId = $this->params()->fromRoute('item_id', 0);
        $transactionId = $this->params()->fromQuery('transaction_id', 0);

        try {
            $expenseData = $expenseService->getTicketData($ticketId);

            // Permission Shortcuts
            $isTicketManager = ($expenseData['ticket']['ticket_manager'] == $auth->getIdentity()->id);
            $isFinance = $auth->hasRole(Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL);

            // If not a ticket manager but wanna more - пошел лесом
            if ($isFinance || $isTicketManager) {
                // good
            } else {
                Helper::setFlashMessage(['error' => 'You have no permissions to look into ticket details.']);
                return $this->redirect()->toRoute('finance/purchase-order');
            }

            $inactiveMoneyAccountList = $moneyAccountService->getInactiveMoneyAccountSimpleList();
        } catch (NotFoundException $ex) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('finance/purchase-order');
        }

        $attachmentData = $attachmentService->getAttachmentListForPreview($ticketId);
        $budgetHoldersList = $usersService->getBudgetHolderList($expenseData['ticket']['ticket_manager']);
        $budgetList = $budgetService->getBudgetsForPO($expenseData['ticket']['budget']);

        $form = new Form\ExpenseTicket($budgetHoldersList, $budgetList);
        $form->setData($expenseData['ticket']);

        return (new ViewModel([
            'attachments' => $attachmentData,
            'inactiveMoneyAccounts' => $inactiveMoneyAccountList,
            'data' => $expenseData,
            'form' => $form,
            'auth' => $auth,
            'itemId' => $itemId,
            'transactionId' => $transactionId,
            'period' => $expenseService->periodTransformer(),
            'currenciesList' => $currencyService->getSimpleCurrencyList()
        ]))->setTemplate('finance/purchase-order/ticket');
    }

    public function saveAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $request = $this->getRequest();
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                if ($expenseService->saveExpenseTicket($request->getPost()->data, $request->getFiles(), $extra)) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_ADD,
                        'ticketId' => $extra['ticketId'],
                    ];

                    $flash = Helper::getSessionContainer('use_zf2');
                    $flash->flash = ['success' => $extra['isEdit'] ? TextConstants::SUCCESS_UPDATE: TextConstants::SUCCESS_ADD];

                    if (count($extra['errorMessages'])) {
                        $flash->flash = ['error' => '<ul><li>' . implode('</li><li>', $extra['errorMessages']) . '</li>' . '</ul>'];
                    }
                } else {
                    $result['msg'] = 'Cannot save purchase order ticket';
                }
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function downloadAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var ExpenseTicket $expenseService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');

        try {
            $expenseData = $expenseService->getExpenseListToDownload($this->params()->fromQuery(), [
                'isFinance' => $auth->hasRole(Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL),
            ]);
        } catch (\Exception $ex) {
            $flash = Helper::getSessionContainer('use_zf2');
            $flash->flash = ['error' => $ex->getMessage()];

            return $this->redirect()->toRoute('finance/purchase-order');
        }

        $response = $this->getResponse();
        $headers  = $response->getHeaders();

        $filename = 'Purchase Orders ' . date('Y-m-d') . '.csv';

        $utilityCsvGenerator = new CsvGenerator();
        $utilityCsvGenerator->setDownloadHeaders($headers, $filename);

        $csv = $utilityCsvGenerator->generateCsv($expenseData);

        $response->setContent($csv);

        return $response;
    }

    public function validateDownloadCsvAction()
    {
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::ERROR,
        ];

        /**
         * @var BackofficeAuthenticationService $auth
         * @var ExpenseTicket $expenseService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');

        try {
            $expenseData = $expenseService->validateDownloadCsv($this->params()->fromQuery(), [
                'isFinance' => $auth->hasRole(Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL),
            ]);

            if ($expenseData) {
                $result = [
                    'status' => 'success',
                    'msg'    => '',
                ];
            } else {
                $result = [
                    'status' => 'error',
                    'msg'    => TextConstants::DOWNLOAD_ERROR_CSV,
                ];
            }
        } catch (\Exception $ex) {
        }

        return new JsonModel($result);
    }

    public function getExpensesAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var ExpenseTicket $expenseService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $result = [
            'iTotalRecords' => 0,
            'iTotalDisplayRecords' => 0,
            'iDisplayStart' => 0,
            'iDisplayLength' => 0,
            'aaData' => [],
        ];

        if ($request->isGet() && $request->isXmlHttpRequest()) {
            try {
                $expenseData = $expenseService->getExpenseList($this->params()->fromQuery(), [
                    'isFinance' => $auth->hasRole(Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL)
                ]);

                if ($expenseData['iTotalRecords']) {
                    $result = $expenseData;
                }
            } catch (\Exception $ex) {
                // do nothing
            }
        }

        return new JsonModel($result);
    }

    public function getCurrenciesAction()
    {
        /**
         * @var Currency\Currency $currencyService
         */
        $request = $this->getRequest();
        $dateList = $request->getPost('dateList');
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $currencies = $currencyService->getCurrenciesByDates($dateList, false);

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_FOUND,
                    'data' => $currencies,
                ];
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function getSubCategoriesAction()
    {
        /**
         * @var ExpenseItemCategories $categoryService
         */
        $request = $this->getRequest();
        $categoryService = $this->getServiceLocator()->get('service_finance_expense_expenses_item_categories');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $categories = $categoryService->getActiveSubCategoryList();

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_FOUND,
                    'data' => $categories,
                ];
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function getMoneyAccountsAction()
    {
        /**
         * @var MoneyAccount $moneyAccountService
         */
        $request = $this->getRequest();
        $moneyAccountService = $this->getServiceLocator()->get('service_money_account');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $accounts = $moneyAccountService->getActiveMoneyAccountList();

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_FOUND,
                    'data' => $accounts,
                ];
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function getAccountsAction()
    {
        /**
         * @var TransactionAccount $accountService
         */
        $request = $this->getRequest();
        $accountService = $this->getServiceLocator()->get('service_finance_transaction_account');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $accounts = $accountService->getAccountsByAutocomplete($request->getPost('q'));

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_FOUND,
                    'data' => $accounts,
                ];
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function getCostCentersAction()
    {
        /**
         * @var Suppliers $supplierService
         */
        $request = $this->getRequest();
        $supplierService = $this->getServiceLocator()->get('service_finance_suppliers');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $suppliers = $supplierService->getCostCentersAutocomplete($request->getPost('q'));

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_FOUND,
                    'data' => $suppliers,
                ];
            } catch (\Exception $ex) {
                // do nothing
            }
        }

        return new JsonModel($result);
    }

    public function getOfficesAction()
    {
        /**
         * @var Office $officeService
         */
        $request = $this->getRequest();
        $officeService = $this->getServiceLocator()->get('service_office');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $offices = $officeService->getOfficesAndSections();

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_FOUND,
                    'data' => $offices,
                ];
            } catch (\Exception $ex) {
                // do nothing
            }
        }

        return new JsonModel($result);
    }

    public function getAffiliatesAction()
    {
        /**
         * @var Partners $partnerService
         */
        $request = $this->getRequest();
        $partnerService = $this->getServiceLocator()->get('service_partners');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $partners = $partnerService->getPartnerlist();

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_FOUND,
                    'data' => $partners,
                ];
            } catch (\Exception $ex) {
                // do nothing
            }
        }

        return new JsonModel($result);
    }

    public function getPeopleAction()
    {
        /**
         * @var User $usersService
         */
        $request = $this->getRequest();
        $usersService = $this->getServiceLocator()->get('service_user');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];


        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $peopleList = $usersService->getPeopleListAsArray();

                if (count($peopleList)) {
                    $peopleListToExport = [];

                    foreach ($peopleList as $peopleId => $peopleName) {
                        array_push($peopleListToExport, [
                            'id' => $peopleId,
                            'name' => $peopleName,
                        ]);
                    }

                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_FOUND,
                        'data' => $peopleListToExport,
                    ];
                }
            } catch (\Exception $ex) {
                // do nothing
            }
        }

        return new JsonModel($result);
    }

    public function deleteAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $request = $this->getRequest();
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $ticketId = $this->params()->fromRoute('id');

            try {
                if ($expenseService->deleteTicket($ticketId)) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_DELETE,
                    ];

                    $flash = Helper::getSessionContainer('use_zf2');
                    $flash->flash = ['success' => $result['msg']];
                } else {
                    $result['msg'] = 'Cannot delete purchase order ticket.';
                }
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function duplicateAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $request = $this->getRequest();
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $ticketId = $this->params()->fromRoute('id');

            try {
                $newTicketId = $expenseService->duplicateTicket($ticketId);

                if ($newTicketId) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_DUPLICATE,
                        'ticketId' => $newTicketId,
                    ];
                } else {
                    $result['msg'] = 'Cannot duplicate purchase order ticket.';
                }
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function previewAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');

        $attachmenType = $this->params()->fromQuery('type', ExpenseTicket::ATTACHMENT_PREVIEW_TYPE_TICKET);
        $id = $this->params()->fromQuery('id', null);
        $width = $this->params()->fromQuery('width', 100);
        $height = $this->params()->fromQuery('height', 100);

        switch ($attachmenType) {
            case ExpenseTicket::ATTACHMENT_PREVIEW_TYPE_TICKET:
                $path = $expenseService->getAttachmentPathById($id);
                break;
            case ExpenseTicket::ATTACHMENT_PREVIEW_TYPE_ITEM:
                $path = $expenseService->getItemAttachmentPathById($id);
                break;
        }

        list($imageWidth, $imageHeight) = getimagesize($path);

        if ($imageWidth && $imageHeight) {
            if ($imageWidth > $imageHeight) {
                $width = $height * $imageWidth / $imageHeight;
            } else {
                $height = $width * $imageHeight / $imageWidth;
            }
        }

        header('Pragma: public');
        header('Cache-Control: max-age = 604800');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT');

        Helper::thumbnail($path, $width, $height);
    }

    public function downloadAttachmentAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');

        $id = $this->params()->fromQuery('id', null);
        $path = $expenseService->getAttachmentPathById($id);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($path));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);

        exit;
    }

    public function downloadItemAttachmentAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');

        $id = $this->params()->fromRoute('id', null);

        $path = $expenseService->getItemAttachmentPathById($id);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($path));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);

        exit;
    }

    public function approveAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $request = $this->getRequest();
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $ticketId = $this->params()->fromRoute('id');
            $limit = $request->getPost('limit', 0);

            try {
                if ($expenseService->approveTicket($ticketId, $limit)) {
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_UPDATE,
                    ];
                } else {
                    $result['msg'] = 'Cannot approve purchase order ticket.';
                }
            } catch (ExpenseCustomException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function rejectAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $request = $this->getRequest();
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $ticketId = $this->params()->fromRoute('id');

            try {
                if ($expenseService->rejectTicket($ticketId)) {
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_UPDATE,
                    ];
                } else {
                    $result['msg'] = 'Cannot reject purchase order ticket.';
                }
            } catch (ExpenseCustomException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function readyAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $request = $this->getRequest();
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $ticketId = $this->params()->fromRoute('id');

            try {
                if ($expenseService->readyTicket($ticketId)) {
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_UPDATE,
                    ];
                } else {
                    $result['msg'] = 'Cannot set purchase order ticket as rendered.';
                }
            } catch (ExpenseCustomException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function settleAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $request = $this->getRequest();
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $ticketId = $this->params()->fromRoute('id');

            try {
                if ($expenseService->settleTicket($ticketId)) {
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_UPDATE,
                    ];
                } else {
                    $result['msg'] = 'Cannot settle purchase order ticket.';
                }
            } catch (ExpenseCustomException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function unsettleAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $request = $this->getRequest();
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $ticketId = $this->params()->fromRoute('id');

            try {
                if ($expenseService->unsettleTicket($ticketId)) {
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_UPDATE,
                    ];
                } else {
                    $result['msg'] = 'Cannot unsettle purchase order ticket.';
                }
            } catch (ExpenseCustomException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function revokeAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $request = $this->getRequest();
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            $ticketId = $this->params()->fromRoute('id');

            try {
                if ($expenseService->revokeTicket($ticketId)) {
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_DELETE,
                    ];
                } else {
                    $result['msg'] = 'Cannot revoke purchase order ticket.';
                }
            } catch (ExpenseCustomException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function getItemsAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_FOUND,
                    'data' => $expenseService->getItems($request->getPost()),
                ];
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function getTransactionsAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_FOUND,
                    'data' => $expenseService->getTransactions($request->getPost()),
                ];
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function removeItemAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                if ($expenseService->removeExpenseItem($request->getPost('id'), $extra)) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_ITEM_DELETE,
                        'data' => $extra,
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

    public function removeRejectedItemAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                if ($expenseService->removeRejectedItem($this->params()->fromRoute('id'))) {
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ITEM_DELETE]);

                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_ITEM_DELETE,
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

    public function voidTransactionAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                if ($expenseService->voidTransaction($this->params()->fromRoute('id'), $extra)) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_TRANSACTION_VOIDED,
                        'data' => $extra,
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

    public function attachItemAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                if ($expenseService->attachItem($this->params()->fromRoute('id'), $request->getPost('itemId'))) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_ITEM_ATTACH,
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

    public function detachItemAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                if ($expenseService->detachItem($request->getPost('itemId'))) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_ITEM_DETACH,
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

    public function attachTransactionAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                if ($expenseService->attachItem($request->getPost('transactionId'), $this->params()->fromRoute('id'))) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_ITEM_ATTACH,
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

    public function detachTransactionAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                if ($expenseService->detachItem($this->params()->fromRoute('id'))) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_ITEM_DETACH,
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

    public function itemAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var ExpenseTicket $expenseService
         * @var User $usersService
         * @var MoneyAccount $moneyAccountService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $moneyAccountService = $this->getServiceLocator()->get('service_money_account');
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $usersService = $this->getServiceLocator()->get('service_user');

        $isFinance = $auth->hasRole(Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL);
        $itemId = $this->params()->fromRoute('id', 0);
        $userId = $auth->getIdentity()->id;
        $itemDetails = false;
        $costCenters = false;
        $poList = [];

        if ($itemId) {
            $itemDetails = $expenseService->getItemDetails($itemId);

            if (!$itemDetails) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                return $this->redirect()->toRoute('finance/purchase-order');
            }

            if (!in_array($userId, [$itemDetails['creator_id'], $itemDetails['manager_id']]) && !$isFinance) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_VIEW_PERMISSION]);
                return $this->redirect()->toRoute('finance/purchase-order');
            }

            if ($itemDetails['status'] == FinHelper::ITEM_STATUS_COMPLETED && !$isFinance) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_ITEM_IS_COMPLETED]);
                return $this->redirect()->toRoute('finance/purchase-order/edit', ['id' => $itemDetails['expense_id']]);
            }

            $costCenters = $expenseService->getItemCostCenters($itemDetails['id']);
            $poList = $expenseService->getManagersPOListJson($userId);
        }

        $moneyAccountList = $moneyAccountService->getUserMoneyAccountListByPosession($userId, $moneyAccountService::OPERATION_ADD_TRANSACTION);
        $peopleList = $usersService->getPeopleListWithManagersForSelect();
        $budgetHoldersList = $usersService->getBudgetHolderList();

        $isApproved = ($itemDetails['status'] == FinHelper::ITEM_STATUS_APPROVED);

        // Conditions for item to be editable
        $editable =
            // PO is not settled
            !($itemDetails['finance_status'] == FinHelper::FIN_STATUS_SETTLED) &&
            (
                // logged in user is from final team and has corresponding role
                $isFinance
                ||
                (
                    // logged in user is PO manager
                    ($itemDetails['po_manager_id'] == $userId)
                    &&
                    // PO is not closed for review
                    !($itemDetails['finance_status'] == FinHelper::FIN_STATUS_READY)
                )
                ||
                // Item is not approved
                (!$isApproved)
            );

        $form = new Form\ExpenseTicket($budgetHoldersList, $peopleList, $itemDetails['type'], !$editable);

        return new ViewModel([
            'data' => $itemDetails,
            'costCenters' => $costCenters,
            'peopleList' => $peopleList,
            'moneyAccountList' => $moneyAccountList,
            'poList' => $poList,
            'form' => $form,
            'auth' => $auth,
            'period' => $expenseService->periodTransformer(),
            'isFinance' => $isFinance,
            'id' => $itemId,
            'budgetHoldersList' => $budgetHoldersList,
            'editable' => $editable
        ]);
    }

    public function saveItemAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $id = $this->params()->fromRoute('id');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                if ($expenseService->saveItem($request->getPost()->data, $request->getFiles(), $extra)) {
                    $result = ['status' => 'success'];

                    if ($extra['poId']) {
                        $message = TextConstants::SUCCESS_UPDATE;

                        $result['redirect-url'] = $this->url()->fromRoute('finance/purchase-order/edit', ['id' => $extra['poId']]);
                    } else {
                        $message = $id ? TextConstants::SUCCESS_UPDATE: TextConstants::SUCCESS_ADD;

                        $result['redirect-url'] = $this->url()->fromRoute('finance/item/edit', ['id' => $extra['itemId']]);
                    }

                    $result['msg'] = $message;

                    $flash = Helper::getSessionContainer('use_zf2');
                    $flash->flash = ['success' => $message];
                }
            } catch (\Exception $ex) {
                $result['msg'] = $ex->getMessage();
                // do nothing
            }
        }

        return new JsonModel($result);
    }

    public function rejectItemAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $id = $this->params()->fromRoute('id');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                if ($expenseService->rejectItem($id)) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_REJECTED,
                    ];
                }
            } catch (\RuntimeException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function completeItemAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $id = $this->params()->fromRoute('id');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                if ($expenseService->completeItem($id)) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_COMPLETED,
                    ];
                }
            } catch (\RuntimeException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function approveItemAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $id = $this->params()->fromRoute('id');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                if ($expenseService->approveItem($id)) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_COMPLETED,
                    ];
                }
            } catch (\RuntimeException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function changeItemManagerAction()
    {
        /**
         * @var ExpenseTicket $expenseService
         */
        $expenseService = $this->getServiceLocator()->get('service_finance_expense_expense_ticket');
        $request = $this->getRequest();
        $id = $this->params()->fromRoute('id');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $newManagerId = (int)$request->getPost('newManagerId');
                if ($expenseService->changeManager($id, $newManagerId)) {
                    $result = [
                        'status' => 'success'
                    ];
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
                }
            } catch (\RuntimeException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::BAD_REQUEST;
        }

        return new JsonModel($result);
    }
}
