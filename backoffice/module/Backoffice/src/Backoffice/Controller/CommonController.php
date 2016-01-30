<?php

namespace Backoffice\Controller;

use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Zend\View\Model\JsonModel;
use Library\Constants\TextConstants;
use DDD\Service\User as UserService;
use Library\Constants\Roles;
use DDD\Service\Booking\BankTransaction;

class CommonController extends ControllerBase
{
    public function ajaxChangeTransactionStatusAction()
    {
        try {
            /**
             * @var \DDD\Service\Booking\BankTransaction $service
             * @var \DDD\Service\Booking\BookingTicket $ticketService
             */
            $request = $this->getRequest();
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $message = TextConstants::ERROR;
            $status  = 'error';

            if (!$auth->hasRole(Roles::ROLE_BOOKING_TRANSACTION_VERIFIER)
                && !$auth->hasDashboard(UserService::DASHBOARD_CASH_PAYMENTS)
                && !$auth->hasDashboard(UserService::DASHBOARD_TRANSACTION_PENDING)
                && !$auth->hasDashboard(UserService::DASHBOARD_FRONTIER_CHARGE_REVIEWED)
            ) {
                throw new \Exception('No has permission');
            }

            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $service = $this->getServiceLocator()->get('service_booking_bank_transaction');

                $transactionStatus = (int)$request->getPost('transaction_status');
                $transactionId     = (int)$request->getPost('transaction_id');
                $transactionType   = (int)$request->getPost('transaction_type');
                $reservationId     = (int)$request->getPost('reservation_id');

                $responseData = $service->changeTransactionState(
                    $transactionId,
                    $transactionStatus,
                    $transactionType
                );

                $status  = $responseData['status'];
                $message = $responseData['msg'];

                if ($transactionStatus !== BankTransaction::BANK_TRANSACTION_STATUS_APPROVED) {
                    $ticketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
                    $ticketService->markAsUnsettledReservationById(
                        $reservationId
                    );
                }
            } else {
                $message = TextConstants::BAD_REQUEST;
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Change Transaction Status Failed');

            $status  = 'error';
        }

        Helper::setFlashMessage([$status => $message]);

        return new JsonModel([
            'status' => $status,
            'msg'    => $message,
        ]);
    }
}
