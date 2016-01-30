<?php

namespace Backoffice\Controller;

use Library\Authentication\BackofficeAuthenticationService;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Library\Constants\TextConstants;
use Library\Constants\Roles;

use Zend\View\Model\JsonModel;

use DDD\Service\Booking;

class CcProvideController extends ControllerBase
{

    public function ajaxGeneratePageAction()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         */
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();
        $result  = [
            'status' => 'error',
            'msg'    => TextConstants::ERROR,
        ];

        if (!$auth->hasRole(Roles::ROLE_CREDIT_CARD) && !$auth->hasRole(Roles::ROLE_FRONTIER_CHARGE)) {
            return new JsonModel($result);
        }

        $result = ['success' => TextConstants::SUCCESS_UPDATE];

        try {
            if ($request->isXmlHttpRequest()) {
                $booking_id = (int)$request->getPost('id');
                $num        = (int)$request->getPost('num');
                $email      = $request->getPost('email');

                /**
                 * @var \DDD\Service\Booking $bookingService
                 */
                $bookingService = $this->getServiceLocator()->get('service_booking');

                $bookingService->generateCcDataUpdatePage($booking_id, 1);

                if ($num == 2) {
                    $cmd    = 'ginosole reservation-email send-update-payment-details-guest --id=' . escapeshellarg($booking_id) . ' --email= ' . $email . ' -v';
                    $output = shell_exec($cmd);

                    if (strstr(strtolower($output), 'error')) {
                        $result['status'] = 'error';
                        $result['msg']    = TextConstants::ERROR_SEND_MAIL;

                        return new JsonModel($result);
                    }

                    $result['success'] .= "<br>" . TextConstants::SUCCESS_SEND_MAIL;
                }

                Helper::setFlashMessage($result);
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxGenerateResetAction()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::ERROR,
        ];

        if (!$auth->hasRole(Roles::ROLE_CREDIT_CARD) && !$auth->hasRole(Roles::ROLE_FRONTIER_CHARGE)) {
            return new JsonModel($result);
        }

        $result = ['success' => TextConstants::SUCCESS_UPDATE];

        try {
            if ($request->isXmlHttpRequest()) {
                $booking_id = (int)$request->getPost('id');

                /**
                 * @var \DDD\Service\Booking $bookingService
                 */
                $bookingService = $this->getServiceLocator()->get('service_booking');

                $bookingService->generateCcDataUpdatePage($booking_id, 0);
                Helper::setFlashMessage($result);
            }
        } catch (\Exception $e) {
            $result['error'] = TextConstants::ERROR;
        }

        return new JsonModel($result);
    }
}
