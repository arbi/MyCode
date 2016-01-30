<?php

namespace Backoffice\Controller;

use DDD\Service\Reservation\ChargeAuthorization;
use DDD\Service\Task as TaskService;
use Library\ActionLogger\Logger;
use Library\Constants\Roles;
use Library\Controller\ControllerBase;
use Zend\View\Model\JsonModel;

use DDD\Service\Reservation\ChargeAuthorization as ChargeAuthorizationService;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Utility\Helper;
use Library\Constants\TextConstants;

use DDD\Service\Booking as BookingService;

/**
 * Class ChargeAuthorizationController
 * @package Backoffice\Controller
 *
 * @author Tigran Petrosyan
 */
class ChargeAuthorizationController extends ControllerBase
{

    public function ajaxGeneratePageAction()
    {
        /**
         * @var BackofficeAuthenticationService $authenticationService
         * @var BookingDao $reservationDao
         */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $reservationDao        = $this->getServiceLocator()->get('dao_booking_booking');

        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        if (!$authenticationService->hasRole(Roles::ROLE_CREDIT_CARD)) {
            $result['msg'] = 'You have no permission for this operation';
            return new JsonModel($result);
        }

        $result = ['success' => TextConstants::SUCCESS_UPDATE];

        try {
            /**
             * @var ChargeAuthorizationService $chargeAuthorizationService
             * @var Logger $logger
             */
            $chargeAuthorizationService = $this->getServiceLocator()->get('service_reservation_charge_authorization');
            $logger                     = $this->getServiceLocator()->get('ActionLogger');
            $request                    = $this->getRequest();

            if ($request->isXmlHttpRequest()) {
                $reservationId = (int)$request->getPost('reservation_id');
                $ccId          = (int)$request->getPost('cc_id');
                $customEmail   = $request->getPost('custom_email');
                $amount        = $request->getPost('amount');

                $emailSection = '';
                if (!empty($customEmail)) {
                    $emailSection = ' --email='. $customEmail;
                }

                $cccaResponse  = $chargeAuthorizationService->generateChargeAuthorizationPageLink($reservationId, $ccId, $amount);

                $cmd    = 'ginosole reservation-email send-ccca --id=' . escapeshellarg($reservationId) . ' --ccca_id=' . $cccaResponse['cccaId'] . $emailSection;
                $output = shell_exec($cmd);

                if (strstr(strtolower($output), 'error')) {
                    $result['status'] = 'error';
                    $result['msg']    = TextConstants::ERROR_SEND_MAIL;

                    return new JsonModel($result);
                }

                // log
                $logger->save(
                    Logger::MODULE_BOOKING,
                    $reservationId,
                    Logger::ACTION_RESERVATION_CCCA_FORM_GENERATED_AND_SENT,
                    ChargeAuthorization::CHARGE_AUTHORIZATION_PAGE_STATUS_GENERATED
                );

                // create auto task
                /**
                 * @var TaskService $taskService
                 */
                $taskService = $this->getServiceLocator()->get('service_task');
                $taskService->createAutoTaskReceiveCccaForm($reservationId);

                $reservationDao->save(['ccca_verified' => BookingService::CCCA_NOT_VERIFIED], ['id' => $reservationId]);

                $result['success'] .= "<br>" . TextConstants::SUCCESS_SEND_MAIL;

                Helper::setFlashMessage($result);
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::ERROR;

            Helper::setFlashMessage($result);
        }

        return new JsonModel($result);
    }
}
