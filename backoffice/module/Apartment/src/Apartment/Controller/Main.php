<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Library\Constants\DomainConstants;
use Library\Constants\Objects;
use Zend\View\Model\JsonModel;
use DDD\Dao\Booking\Booking;
use \DDD\Dao\Apartment\Media;
use DDD\Service\Lock\General as LockService;

class Main extends ApartmentBaseController {

	public function indexAction() {
		/**
         * @var \DDD\Service\Apartment\General $apartmentGeneralService
		 * @var \DDD\Service\Apartment\Main $apartmentMainService
         * @var \DDD\Service\Apartment\OTADistribution $apartmentOTAService
         * @var $taskService \DDD\Service\Task
         * @var $bookingTicketService \DDD\Service\Booking\BookingTicket
		 */
        $apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
        $apartmentMainService    = $this->getServiceLocator ()->get('service_apartment_main' );
        $apartmentOTAService     = $this->getServiceLocator()->get('service_apartment_ota_distribution');
        $taskService             = $this->getServiceLocator()->get('service_task');
        $bookingTicketService    = $this->getServiceLocator()->get('service_booking_booking_ticket');

        $apartmentTasks   = $taskService->getFrontierTasksOnApartment($this->apartmentId);
        $apartmentOTAList = $apartmentOTAService->getOTAList($this->apartmentId);
        $building         = $apartmentMainService->getApartmentBuilding($this->apartmentId);
        $apartels         = $apartmentMainService->getApartmentApartels($this->apartmentId);
        $bookingDao       = new Booking($this->getServiceLocator(), 'ArrayObject');
        $mediaDao         = new Media($this->getServiceLocator(), 'ArrayObject');
        $dateDisabled     = $currentReservation = $nextReservation = false;
        $apartmentDates   = $apartmentMainService->getApartmentDates($this->apartmentId);

        $generalInfo = $apartmentGeneralService->getApartmentGeneral( $this->apartmentId );

        if($this->apartmentStatus == Objects::PRODUCT_STATUS_DISABLED) {
            $dateDisabled = $apartmentDates['disable_date'];
        } else {
            $currentReservation = $bookingDao->getCurrentReservationByAcc($this->apartmentId, date('Y-m-d'));
            $resId              = $currentReservation['id'];
            $pin                = $currentReservation['pin'];
            $current            = true;

            if(!$currentReservation) {
                $nextReservation = $bookingDao->getNextReservationByAcc($this->apartmentId, date('Y-m-d'));
                $resId           = $nextReservation['id'];
                $pin             = $nextReservation['pin'];
                $current         = false;
            }

            if ($resId && $pin) {
                $lockDatas = $bookingTicketService->getLockByReservation(
                    $resId,
                    $pin,
                    [LockService::USAGE_APARTMENT_TYPE]
                );

                foreach ($lockDatas as $key => $lockData) {
                    switch ($key) {
                        case LockService::USAGE_APARTMENT_TYPE:
                            if ($current) {
                                $currentReservation['pin'] = $lockData['code'];
                            } else {
                                $nextReservation['pin'] = $lockData['code'];
                            }
                            break;
                    }
                }
            }
        }

        $img = $mediaDao->getFirstImage($this->apartmentId)['img1'];

		$viewModel = new \Zend\View\Model\ViewModel ();
		$viewModel->setVariables([
			'apartmentId'            => $this->apartmentId,
            'apartmentStatus'        => $this->apartmentStatus,
            'currentReservation'     => $currentReservation,
            'nextReservation'        => $nextReservation,
            'dateCreated'            => $apartmentDates['create_date'],
            'dateDisabled'           => $dateDisabled,
            'img'                    => str_replace('orig', 445, $img),
            'OTAList'                => $apartmentOTAList,
            'building'               => $building,
            'apartels'               => $apartels,
            'apartment'              => $generalInfo,
            'apartmentTasks'         => $apartmentTasks
		]);

		$viewModel->setTemplate ( 'apartment/main/index' );

		return $viewModel;
	}
}
