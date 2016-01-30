<?php

namespace Finance\Controller;

use DDD\Service\Booking;
use DDD\Service\Distribution;
use DDD\Service\Finance\Expense\ExpenseTicket;
use DDD\Service\Finance\Suppliers;
use DDD\Service\MoneyAccount;
use DDD\Service\Partners;
use DDD\Service\Psp;
use DDD\Service\Reservation\Main as ReservationMainService;
use DDD\Service\User;
use Finance\Form\Transfer;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use DDD\Service\Finance\Transfer as TransferService;
use Library\Utility\Helper;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class TransferController extends ControllerBase
{
    public function indexAction()
    {
        /** @var ReservationMainService $reservationService */
        $reservationService = $this->getServiceLocator()->get('service_reservation_main');
        /** @var MoneyAccount $moneyAccountService */
        $moneyAccountService = $this->getServiceLocator()->get('service_money_account');
        /** @var Partners $partnerService */
        $partnerService = $this->getServiceLocator()->get('service_partners');
        /** @var TransferService $transferService */
        $transferService = $this->getServiceLocator()->get('service_finance_transfer');
        /** @var Psp $pspService */
        $pspService = $this->getServiceLocator()->get('service_psp');
        /** @var \Library\Authentication\BackofficeAuthenticationService $auth */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        $userId = $auth->getIdentity()->id;

        $transferId = $this->params()->fromQuery('id');
        $form = new Transfer(
            $partnerService->getActivePartnerlist(),
            $partnerService->getActivePartnerFilteredList(),
            $pspService->getBatchPSPList()
        );

        if (!is_null($transferId)) {
            $data = $transferService->getPendingTransfer($transferId);

            if ($data) {
                $form->populateValues($data);
            } else {
                Helper::setFlashMessage(['error' => 'Pending transfer not found']);
                return $this->redirect()->toRoute('finance/transfer');
            }
        }

        return new ViewModel([
            'form' => $form,
            'data' => isset($data) ? $data : null,
            'moneyAccounts' => $moneyAccountService->getUserMoneyAccountListByPosession($userId, $moneyAccountService::OPERATION_ADD_TRANSACTION),
            'pspList' => $moneyAccountService->getMoneyAccountList(),
            'reservations' => $reservationService->getCollectFromPartnerReservationsAsJson(),
        ]);
    }

    public function ajaxSaveAction()
    {
        /**
         * @var TransferService $transferService
         */
        $transferService = $this->getServiceLocator()->get('service_finance_transfer');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isXmlHttpRequest() && $request->isPost()) {
            try {
                if ($transferService->makeTransfer($request->getPost())) {
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_TRANSACTED]);
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_TRANSACTED,
                    ];
                }
            } catch (\Exception $ex) {
                $result['msg'] = $ex->getMessage();
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function getApartmentsAndApartelsAction()
    {
        /**
         * @var Distribution $distributionService
         */
        $distributionService = $this->getServiceLocator()->get('service_distribution');
        $request = $this->getRequest();
        $query = $request->getPost('q');
        $distributionList = [];

        if ($request->isXmlHttpRequest() && $request->isPost()) {
            $distributionList = $distributionService->getApartmentsAndApartels($query);
        }

        return new JsonModel($distributionList);
    }

    public function getPartnerPaymentReservationsAction()
    {
        /**
         * @var Booking $bookingService
         */
        $bookingService = $this->getServiceLocator()->get('service_booking');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isXmlHttpRequest() && $request->isPost()) {
            try {
                $reservations = $bookingService->getPayToPartnerReservations((array)$request->getPost());

                if (count($reservations)) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_FOUND,
                        'data' => $reservations,
                    ];
                } else {
                    throw new \RuntimeException('No reservations found');
                }
            } catch (\RuntimeException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function getTransactionsToCollectAction()
    {
        /**
         * @var Booking\BankTransaction $reservationTransactionService
         */
        $reservationTransactionService = $this->getServiceLocator()->get('service_booking_bank_transaction');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isXmlHttpRequest() && $request->isPost()) {
            try {
                $reservationTransactions = $reservationTransactionService->getCollectionReadyVirtualReservations(
                    $request->getPost('pspId'), $request->getPost('dates')
                );

                if (count($reservationTransactions)) {
                    $result = [
                        'status' => 'success',
                        'msg' => TextConstants::SUCCESS_FOUND,
                        'data' => $reservationTransactions,
                    ];
                } else {
                    throw new \RuntimeException('No reservations found');
                }
            } catch (\RuntimeException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function getExpenseItemBalanceAction()
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
                $balance = $expenseService->getTicketBalance($request->getPost('expenseId'));

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_FOUND,
                    'data' => $balance,
                ];
            } catch (\RuntimeException $ex) {
                $result['msg'] = $ex->getMessage();
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function savePendingAction()
    {
        /**
         * @var TransferService $transferService
         */
        $transferService = $this->getServiceLocator()->get('service_finance_transfer');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isXmlHttpRequest() && $request->isPost()) {
            try {
                $transferService->savePendingTransfer($request->getPost()->toArray());
                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_ADD,
                ];
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    public function cancelAction()
    {
        /**
         * @var TransferService $transferService
         */
        $transferService = $this->getServiceLocator()->get('service_finance_transfer');
        $request = $this->getRequest();
        $id = $this->params()->fromRoute('id');
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isXmlHttpRequest() && $request->isPost()) {
            try {
                $transferService->deletePendingTransfer($id);

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_ADD,
                ];
            } catch (\Exception $ex) {
                // do nothing
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }
}
