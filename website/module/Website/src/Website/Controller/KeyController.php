<?php

namespace Website\Controller;

use DDD\Service\ApartmentGroup\Usages\Building;
use DDD\Service\Booking\BookingTicket;
use Library\Constants\TextConstants;
use Library\Controller\WebsiteBase;
use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Library\Validator\ClassicValidator;
use Library\Utility\Helper;

use Zend\Session\Container as SessionContainer;

use DDD\Service\Lock\General as LockService;
use DDD\Service\Apartment\Details as ApartmentDetails;

class KeyController extends WebsiteBase
{
    /**
     * @return ViewModel
     */
    public function indexAction()
    {
        /**
         * @var \DDD\Service\Textline $textlineService
         * @var \DDD\Service\Lock\General $lockService
         */
        $textlineService      = $this->getServiceLocator()->get('service_textline');
        $lockService          = $this->getServiceLocator()->get('service_lock_general');

        $keyCode = $this->params()->fromQuery('code');
        $view    = $this->params()->fromQuery('view');
        $godMode = $this->params()->fromQuery('bo', false) ?: false;

        if ($keyCode === null || !ClassicValidator::validateAlnum($keyCode)) {
            return $this->redirect()->toRoute('home')->setStatusCode('301');
        }
        /**
         * if have not key code in query...
         * OR not finded thicket...
         * OR "arrival date" NOT MORE than "5"
         * OR "depart date" NOT LESS than "-3"
         * redirect() -> Home
         */
	    $bookingData = $this->getBookingData($keyCode);
        if (!$bookingData || !Helper::checkDatesByDaysCount(1, $bookingData->getDateTo())) {
            return $this->redirect()->toRoute('home')->setStatusCode('301');
        }

        $session = new SessionContainer('visitor');

        $parkingTextline = '';
        if ($bookingData->hasParking()) {
            $parkingTextline = $textlineService->getUniversalTextline($bookingData->getParkingTextlineId());
        }

        /**
         * @var \DDD\Service\Website\Textline $textlineService
         */
        $textlineService = $this->getServiceLocator()->get('service_website_textline');

        $keyDirectEntryTextline    = Helper::evaluateTextline(
            $textlineService->getApartmentDirectKeyInstructionTextline($bookingData->getApartmentId()), [
                '{{PARKING_TEXTLINE}}' => $parkingTextline,
            ]
        );

        $keyReceptionEntryTextline =  Helper::evaluateTextline(
            $textlineService->getApartmentReceptionKeyInstructionTextline($bookingData->getApartmentId()), [
                '{{PARKING_TEXTLINE}}' => $parkingTextline,
            ]
        );

        /* @var $customerService \DDD\Service\Customer */
        $customerService = $this->getServiceLocator()->get('service_customer');

        /**
         * If NOT HAVE flag from BO (view=0)...
         * Specify that looked & save the date view
         */
        if ($view !== '0' && $bookingData->isKiViewed() !== '1' && !$customerService->isBot($session)) {
            /**
             * @var \DDD\Service\Website\Booking $bookingService
             */
            $bookingService = $this->getServiceLocator()->get('service_website_booking');

            $bookingService->updateData($bookingData->getId(), [
                'ki_viewed' => '1',
                'ki_viewed_date' => date('Y-m-d H:i:s')
            ]);
        }

        $lockDatas = $lockService->getLockByReservationApartmentId(
            $bookingData->getApartmentIdAssigned(),
            $bookingData->getPin(),
            [
                LockService::USAGE_APARTMENT_TYPE,
                LockService::USAGE_BUILDING_TYPE,
                LockService::USAGE_PARKING_TYPE
            ],
            true
        );

        foreach ($lockDatas as $key => $lockData) {
            switch ($key) {
                case LockService::USAGE_APARTMENT_TYPE:
                    $bookingData->setPin($lockData['code']);
                    break;
                case LockService::USAGE_BUILDING_TYPE:
                    $bookingData->setOutsideDoorCode($lockData['code']);
                    break;
                case LockService::USAGE_PARKING_TYPE:
                    // TODO: to be or not to be, this is the question.
                    break;
            }
        }

        // get Office Address
        $officeAddress = false;

        if ($bookingData->getKiPageType() == Building::KI_PAGE_TYPE_RECEPTION) {
            /**
             * @var \DDD\Service\Office $officeService
             */
            $officeService    = $this->getServiceLocator()->get('service_office');
            $officeManagement = $officeService->getData($bookingData->getOfficeId());

            /**
             * @var \DDD\Domain\Office\OfficeManager $officeData
             */
            $officeData    = $officeManagement['office'];
            $officeAddress = $officeData->getAddress();
        }

        if ($view !== '0') {
            $this->checkCustomerIdentityData($bookingData);
        }

        $this->layout()->setTemplate('layout/layout-ki');

        $this->layout()->userTrackingInfo = [
            'res_number' => $bookingData->getResNumber(),
            'partner_id' => $bookingData->getPartnerId(),
        ];
        $this->layout()->godMode = ($godMode === substr(md5($keyCode), 12, 5));
        $this->layout()->keyData = $bookingData;
        $this->layout()->keyCode = $keyCode;
        $this->layout()->directEntryTextline = $keyDirectEntryTextline;
        $this->layout()->receptionEntryTextline = $keyReceptionEntryTextline;

        return new ViewModel([
            'keyData'                => $bookingData,
            'keyCode'                => $keyCode,
            'directEntryTextline'    => $keyDirectEntryTextline,
            'receptionEntryTextline' => $keyReceptionEntryTextline,
            'godMode'                => ($godMode === substr(md5($keyCode), 12, 5)),
            'isGuest'                => ($view !== '0'),
            'officeAddress'          => $officeAddress,
        ]);
    }

    /**
     * @return JsonModel
     */
    public function updateEmailAction()
    {
        /**
         * @var Request $request
         * @var BookingTicket $bookingTicketService
         */
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $bookingTicketService->updateReservationDetails($request->getPost());

                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_UPDATE,
                ];
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

    public function checkCustomerIdentityData($bookingData)
    {
        // key instruction already been viewed
        if (!empty($bookingData->isKiViewed())) {
            return false;
        }

        // reserved self via WS
        if (empty($bookingData->getSuperviserId()) && empty($bookingData->getChannelResId())) {
            return false;
        }

        /* @var $customerService \DDD\Service\Customer */
        $customerService = $this->getServiceLocator()->get('service_customer');
        $customerService->saveCustomerIdentityForReservation($bookingData->getId());
    }

    /**
     * @param string $keyCode
     * @return bool|\DDD\Domain\Booking\KeyInstructionPage
     */
    private function getBookingData($keyCode)
    {
        /**
         * @var \DDD\Service\Website\Booking $bookingService
         */
        $bookingService = $this->getServiceLocator()->get('service_website_booking');

        return $bookingService->getReservationByKeyCode($keyCode);
    }
}
