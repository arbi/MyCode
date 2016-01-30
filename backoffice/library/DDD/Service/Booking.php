<?php

namespace DDD\Service;

use DDD\Domain\Booking\SaleStatisticsRow;
use DDD\Service\Booking\BookingTicket;
use DDD\Service\Booking\Charge;
use DDD\Service\Lock\General as LockService;
use DDD\Service\Task as TaskService;
use DDD\Service\Booking\BookingTicket as ReservationTicketService;

use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Utility\Helper;

use Zend\Db\Sql\Expression;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Where;

class Booking extends ServiceBase
{
    protected $serviceLocator;

    /**
     * @var $bookingQueueDao \DDD\Dao\Booking\BookingQueue
     */
    protected $bookingQueueDao = null;
    private   $_partnerDao = null;
    protected $_chargingDao = null;
    protected $_chargeTransactionDao = null;

    const BOOKING_STATUS_ALL                    = 0;
    const BOOKING_STATUS_BOOKED 				= 1;
	const BOOKING_STATUS_CANCELLED_MOVED 		= 5;
	const BOOKING_STATUS_CANCELLED_BY_CUSTOMER 	= 7;
	const BOOKING_STATUS_CANCELLED_BY_HOTEL 	= 8;
	const BOOKING_STATUS_CANCELLED_BY_GINOSI 	= 9;
	const BOOKING_STATUS_CANCELLED_INVALID 		= 10;
	const BOOKING_STATUS_CANCELLED_TEST_BOOKING = 11;
	const BOOKING_STATUS_CANCELLED_FRAUDULANT 	= 13;
    const BOOKING_STATUS_CANCELLED_NOSHOW 		= 14;
    const BOOKING_STATUS_CANCELLED_PENDING 		= 15;
    const BOOKING_STATUS_CANCELLED_EXCEPTION    = 16;
    const BOOKING_STATUS_CANCELLED_UNWANTED     = 17;

    const PRICE_VALUE_TYPE      = 1;
	const PRICE_PENALTY_TYPE    = 2;

    const PRODUCT_CURRENCY = 'apartment_currency_code';

    public static $bookingStatuses = array(
    	self::BOOKING_STATUS_BOOKED                 => "BO",
    	self::BOOKING_STATUS_CANCELLED_BY_CUSTOMER  => "CB",
    	self::BOOKING_STATUS_CANCELLED_BY_HOTEL     => "CA",
    	self::BOOKING_STATUS_CANCELLED_BY_GINOSI    => "CG",
    	self::BOOKING_STATUS_CANCELLED_INVALID      => "CI",
    	self::BOOKING_STATUS_CANCELLED_TEST_BOOKING => "TB",
    	self::BOOKING_STATUS_CANCELLED_FRAUDULANT   => "CF",
        self::BOOKING_STATUS_CANCELLED_MOVED        => "CM",
    	self::BOOKING_STATUS_CANCELLED_NOSHOW       => "CNS",
    	self::BOOKING_STATUS_CANCELLED_PENDING      => "CP",
    	self::BOOKING_STATUS_CANCELLED_EXCEPTION    => "CE",
    	self::BOOKING_STATUS_CANCELLED_UNWANTED     => "CU",
    );

    public static $bookingStatusForChart = [
        0                                           => "-- All Statuses --",
        self::BOOKING_STATUS_BOOKED                 => "Booked",
        self::BOOKING_STATUS_CANCELLED_MOVED        => "Canceled (Moved)",
        self::BOOKING_STATUS_CANCELLED_BY_CUSTOMER  => "Canceled by Customer",
        self::BOOKING_STATUS_CANCELLED_BY_GINOSI    => "Canceled by Ginosi",
        self::BOOKING_STATUS_CANCELLED_INVALID      => "Canceled (Invalid)",
        self::BOOKING_STATUS_CANCELLED_TEST_BOOKING => "Canceled (Test Booking)",
        self::BOOKING_STATUS_CANCELLED_FRAUDULANT   => "Canceled (Fraudulent)",
        self::BOOKING_STATUS_CANCELLED_UNWANTED     => "Canceled (Unwanted)",
        self::BOOKING_STATUS_CANCELLED_NOSHOW       => "Canceled (No Show)",
        Constants::NOT_BOOKED_STATUS                => "Canceled",
    ];

    public static $bookingCancelStatuses = [
        self::BOOKING_STATUS_CANCELLED_BY_CUSTOMER,
        self::BOOKING_STATUS_CANCELLED_BY_GINOSI,
        self::BOOKING_STATUS_CANCELLED_INVALID,
        self::BOOKING_STATUS_CANCELLED_TEST_BOOKING,
        self::BOOKING_STATUS_CANCELLED_FRAUDULANT,
        self::BOOKING_STATUS_CANCELLED_NOSHOW,
        self::BOOKING_STATUS_CANCELLED_EXCEPTION,
        self::BOOKING_STATUS_CANCELLED_UNWANTED,
        self::BOOKING_STATUS_CANCELLED_PENDING
    ];

    const CCCA_NOT_VERIFIED = 0;
    const CCCA_VERIFIED     = 1;

    ///////////////////////////// UNIVERSAL DASHBOARD /////////////////////////////

    /**
     * Get reservations for "Pending Reservations" widget
     * @return Ambigous <\Library\DbManager\Ambigous, \Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     *
     * @todo create new service for "Pending Reservations" widget and move this method to there
     */
	public function getPendingReservations() {
		$dao = $this->getServiceLocator()->get('dao_booking_booking');

		return $dao->getPendingReservations();
	}

    /**
     * Get reservations count for "Pending Reservations" widget
     * @return int
     */
	public function getPendingReservationsCount() {
		$dao = $this->getServiceLocator()->get('dao_booking_booking');
		return $dao->getPendingReservationsCount();
	}

    ///////////////////////////// UNIVERSAL DASHBOARD /////////////////////////////

    /**
     * @param int $id
     * @return \DDD\Domain\Booking\Review[]|\ArrayObject
     */
    public function bookingInfoForReviewMail($id)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\Review());

        return $bookingDao->getBookingForReviewMail($id);
    }

    public function updateBookingReviewMail($id)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\Review());

        $bookingDao->save(['review_mail_sent' => 1], ['id' => $id]);
    }

    /**
     * @param $id
     *
     * @return \DDD\Domain\Booking\KeyInstructionMail[]|\ArrayObject
     */
    public function bookingInfoForKeyInstructionMail($id) {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\KeyInstructionMail());

        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $reservations         = $bookingDao->getBookingForKeyInstructionMail($id);
        $reservations         = iterator_to_array($reservations);

        foreach ($reservations as $index => $reservation) {
            $lockDatas = $bookingTicketService->getLockByReservation(
                $reservation->getId(),
                $reservation->getPin(),
                [
                    LockService::USAGE_APARTMENT_TYPE,
                    LockService::USAGE_BUILDING_TYPE,
                    LockService::USAGE_PARKING_TYPE
                ]
            );
            foreach ($lockDatas as $key => $lockData) {
                switch ($key) {
                    case LockService::USAGE_APARTMENT_TYPE:
                        $reservations[$index]->setPin($lockData['code']);
                        break;
                    case LockService::USAGE_BUILDING_TYPE:
                        $reservations[$index]->setOutsideDoorCode($lockData['code']);
                        break;
                    case LockService::USAGE_PARKING_TYPE:
                        // TODO: to be or not to be, this is the question.
                        break;
                }
            }
        }

        return $reservations;
    }

    public function updateOutsideDoorCode($id, $outsideDoorCode)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\KeyInstructionMail());

        $bookingDao->save(
            array(
                'outside_door_code' => $outsideDoorCode,
                'ki_mail_sent'      => 1,
                'ki_page_status'    => BookingTicket::SEND_KI_PAGE_STATUS,
            ),
            array('id' => $id)
        );
    }

    public function bookingInfoForReservationMail($id, $mode = 'check') {
        if (!$id) {
            $this->getBookingQueueDao();
            $bookingIdsFromQueue = $this->bookingQueueDao->getBookingsForSendEmail();

            if (count($bookingIdsFromQueue) > 0) {
                $id = array();
                foreach ($bookingIdsFromQueue as $row) {
                    $id[] = $row->getReservationId();
                }
            }
        } elseif (is_numeric($id)) {
            $id = array($id);
        } else {
            return false;
        }

        if ($id) {
            /**
             * @var \DDD\Dao\Booking\Booking $bookingDao
             */
            $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
            $bookingDao->setEntity(new \DDD\Domain\Booking\ReservationConfirmationEmail());

            return $bookingDao->getBookingForReservationConfirmationMail($id);
        } else {
            return false;
        }
    }

    public function bookingInfoForModificationMail($id, $mode = 'check')
    {
        if ($id) {
            /**
             * @var \DDD\Dao\Booking\Booking $bookingDao
             */
            $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

            switch ($mode) {
                case 'modify-ginosi' :
                    $bookingDao->setEntity(new \DDD\Domain\Booking\ReservationGinosiEmail());
                    return $bookingDao->getBookingForGinosiReservationMail(array($id));
                    break;
                case 'modify-booker' :
                    $bookingDao->setEntity(new \DDD\Domain\Booking\ReservationConfirmationEmail());
                    return $bookingDao->getBookingForReservationConfirmationMail(array($id));
                    break;
                default :
                    $bookingDao->setEntity(new \DDD\Domain\Booking\ModificationEmail());
                    return $bookingDao->getBookingInfoForModificationMail($id);
            }
        } else {
            return false;
        }

    }

    public function bookingInfoForCancellationMail($id) {
        if ($id) {
            /**
             * @var \DDD\Dao\Booking\Booking $bookingDao
             */
            $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
            $bookingDao->setEntity(new \DDD\Domain\Booking\CancellationEmail());

            return $bookingDao->getBookingInfoForCancellationMail($id);
        } else {
            return false;
        }

    }

    public function setBookingInQueueAsSent($id) {
        $this->getBookingQueueDao();

        return $this->bookingQueueDao->delBookingFromQueue($id);
    }

    public function setBookingStatusIsQueueAsError($id) {
        $this->getBookingQueueDao();

        $errorMessage = 'Warning: Guest Email not sent.';

        return $this->bookingQueueDao->save(
            [
                'error_status'  => 1,
                'error'         => $errorMessage
            ],
            ['reservation_id' => $id]
        );
    }

    public function getPartnerById($id){
        $partner = $this->getPartnerDao();
        return $partner->fetchOne(['gid'=>$id]);
    }


    public function generateCcDataUpdatePage($reservationId, $val)
    {
        /**
         * @var Logger $logger
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\GenerationLink());

        /* @var $bookingTicketService \DDD\Service\Booking\BookingTicket */
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');

        $bookingTicketData = $bookingTicketService->getBookingThicketByReservationId($reservationId);

        $generateHash = $bookingTicketService->generatePageHash(
            $bookingTicketData->getTimestamp(),
            $bookingTicketData->getResNumber()
        );

        $bookingDao->save(
            [
                'provide_cc_page_hash' => $generateHash,
                'provide_cc_page_status' => (int)$val,
            ],
            [
                'id' => $reservationId
            ]
        );

        $logger->save(Logger::MODULE_BOOKING, $reservationId, Logger::ACTION_REQUEST_PAYMENT_DETAILS, $val);

        return true;
    }

    /**
     * @return SaleStatisticsRow[]
     */
    public function getSalesStatistics()
    {
        /**
         * @var \DDD\Dao\Booking\Booking $reservationDao
         * @var Charge $reservationChargeService
         */
        $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');
        $reservationChargeService = $this->getServiceLocator()->get('service_booking_charge');

        $todayReservations = $reservationDao->getTodayReservations();
        $yesterdayReservations = $reservationDao->getYesterdayReservations();
        $last30DaysReservations = $reservationDao->getLast30Reservations();

        $todayReservationsTotalPriceInEuro      = $reservationChargeService->getTotalPriceInEuroForSaleStatistics($todayReservations);
        $yesterdayReservationsTotalPriceInEuro  = $reservationChargeService->getTotalPriceInEuroForSaleStatistics($yesterdayReservations);
        $last30DaysReservationsTotalPriceInEuro = $reservationChargeService->getTotalPriceInEuroForSaleStatistics($last30DaysReservations);

        $todayStatistics = new SaleStatisticsRow();
        $todayStatistics->setLabel('Today');
        $todayStatistics->setTotalAmount($todayReservationsTotalPriceInEuro);
        $todayStatistics->setCount(count($todayReservations));

        $yesterdayStatistics = new SaleStatisticsRow();
        $yesterdayStatistics->setLabel('Yesterday');
        $yesterdayStatistics->setTotalAmount($yesterdayReservationsTotalPriceInEuro);
        $yesterdayStatistics->setCount(count($yesterdayReservations));

        $last30DaysStatistics = new SaleStatisticsRow();
        $last30DaysStatistics->setLabel('Last 30 Days');
        $last30DaysStatistics->setTotalAmount($last30DaysReservationsTotalPriceInEuro);
        $last30DaysStatistics->setCount(count($last30DaysReservations));

        $saleStatistics = [
            $todayStatistics,
            $yesterdayStatistics,
            $last30DaysStatistics
        ];

		return $saleStatistics;
	}

    public function checkDuplicateDoorCode($code, $cityId, $dateFrom)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\DoorCode());

        return $bookingDao->getTicketByDoorCode($code, $cityId, $dateFrom);
    }
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    public function getPartnersNameById($id) {
    	$affiliate = new \DDD\Dao\Partners\Partners($this->getServiceLocator(), 'DDD\Domain\Partners\PartnerBooking');
        $affiliates = $affiliate->getPartners();

    	foreach ($affiliate as $row) {
    		if ($row->getGid() == $id) {
    			return $row->getPartnerName();
            }
    	}

    	return '';
    }

    /**
     * @param $reservationId
     * @param $num
     * @return array|bool
     */
    public function saveBlackList($reservationId, $num) {
    	/**
         * @var \DDD\Service\Fraud $fraudService
         */
		$fraudService = $this->getServiceLocator()->get('service_fraud');

    	if ($num == 1) {
    		$response = $fraudService->saveFraudManual($reservationId);
    	} else {
    		$response = $fraudService->removeFromFraud($reservationId);
    	}

        return $response;
    }

    /**
     * @param $id
     * @return bool|\DDD\Domain\Booking\BookingTicket
     */
    public function getNextReservation($id)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        $currentReservation = $bookingDao->getReservationById($id);

        if ($currentReservation === FALSE) {
            return FALSE;
        }

        $nextReservation = $bookingDao->getFollowingReservationId(
            $currentReservation->getApartmentIdAssigned(),
            $currentReservation->getDate_to()
        );

        if ($nextReservation === FALSE) {
            return FALSE;
        }

        return $bookingDao->getReservationById($nextReservation->getId());
    }

    /**
     * @param Int $reservationId
     * @return array|\ArrayObject|null
     */
    public function getBasicInfoById($reservationId)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        return $bookingDao->getBasicInfoById($reservationId);
    }

    /**
     * @param Int $reservationId
     * @return array|\ArrayObject|null
     */
    public function getBasicInfoForAutoTaskCreationById($reservationId)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        return $bookingDao->getBasicInfoForAutoTaskCreationById($reservationId);
    }

    /**
     * Check Apartment availability in given date range
     * @param Int $apartmentId
     * @param String $dateFrom
     * @param String $dateTo
     * @return bool
     */
    public function checkApartmentAvailability($apartmentId, $dateFrom, $dateTo)
    {
        $inventoryDao = $this->getInventoryDao();

        $return = $inventoryDao->checkApartmentAvailability($apartmentId, $dateFrom, $dateTo);

        return $return;
    }

    /**
     * @param Int $reservationId
     * @param Int $newApartmentId
     * @param Int $oldApartmentId
     * @param String $oldApartmentName
     * @param String $dateFrom
     * @param String $dateTo
     */
    public function moveReservation($reservationId, $resNumber, $newApartmentId, $oldApartmentId, $oldApartmentName, $dateFrom, $dateTo, $originStartDate)
    {
        /**
         * @var \DDD\Service\Availability $availabilityService
         * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $availabilityService     = $this->getServiceLocator()->get('service_availability');
        $bookingTicketService    = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $parkingInventoryService = $this->getServiceLocator()->get('service_parking_spot_inventory');
        $logger                  = $this->getServiceLocator()->get('ActionLogger');
        $bookingDao              = $this->getServiceLocator()->get('dao_booking_booking');

        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        $bookingDao->beginTransaction();

        $apartmentService = $this->getServiceLocator()->get('service_apartment_general');

        // Save new apartment for reservation
        $bookingDao->save(['apartment_id_assigned' => $newApartmentId], ['id' => $reservationId]);
        $bookingData = $bookingDao->fetchOne(['id' => $reservationId],['res_number', 'overbooking_status', 'arrival_status']);

        // if status overbooking
        if ($bookingData->getOverbooking() == BookingTicket::OVERBOOKING_STATUS_OVERBOOKED) {
            // resolve overbooking
            $bookingTicketService->changeOverbookingStatus($reservationId, $bookingTicketService::OVERBOOKING_STATUS_RESOLVED);
        } else {
            // Close Availability for new apartment
            $availabilityService->updateAvailabilityForApartmentDateRange($newApartmentId, $dateFrom, $dateTo, 0, true);

            // Decrease Availability for apartel
            $availabilityService->updateAvailabilityForApartelByApartmentDateRange($newApartmentId, $dateFrom, $dateTo, true);
        }

        // for old apartment
        if ($bookingData->getOverbooking() != BookingTicket::OVERBOOKING_STATUS_OVERBOOKED) {
            // Open Availability for old apartment
            $availabilityService->updateAvailabilityForApartmentDateRange($oldApartmentId, $dateFrom, $dateTo, 1, true);

            // Increase Availability for apartel
            $availabilityService->updateAvailabilityForApartelByApartmentDateRange($oldApartmentId, $dateFrom, $dateTo, true);
        }

        // change nightly and charges data to new apartment
        $this->changeApartmentNightlyAndChargesDataOnMove($reservationId, $newApartmentId, $dateFrom, $dateTo);
        $newApartmentName = $apartmentService->getApartmentGeneral($newApartmentId)['name'];

        // change parking location if this reservation has parking charges.
        $parkingInventoryService->changeParkingByApartment($reservationId, $resNumber, $newApartmentId, $dateFrom, $dateTo, $originStartDate);

        $logMsg = 'Reservation Moved From <b>' . $oldApartmentName . '</b> to <b>' . $newApartmentName . '</b>.';

        $logger->save(Logger::MODULE_BOOKING, $reservationId, Logger::ACTION_RESERVATION_MOVE, $logMsg);
        $taskService = $this->getServiceLocator()->get('service_task');

        $taskService->deleteTask([
            'property_id'    => $oldApartmentId,
            'res_id'         => $reservationId,
            'is_hk'          => TaskService::TASK_IS_HOUSEKEEPING,
        ]);

        if ($bookingData->getArrivalStatus() == ReservationTicketService::BOOKING_ARRIVAL_STATUS_CHECKED_IN) {
            //checked in, but canceled
            //create extra task for cleaning, and key change for tomorrow
            $taskService->createExtraCleaningCancelReservationAfterCheckin($reservationId, $oldApartmentId, Task::CASE_MOVE);
        } else {
            //if the previous task is done , or the reservation has started
            //create extra task for key change on next reservation's checkin date
            $taskService->checkAndCreateExtraTaskForStartedReservationsCancelation($reservationId, $oldApartmentId, Task::CASE_MOVE);
        }

        $bookingDao->commitTransaction();
    }

    public function getBookingInfoByApartmentIdAndCheckoutDate($apartmentId,$date)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        return $bookingDao->getBookingInfoByApartmentIdAndCheckoutDate($apartmentId,$date);
    }


    public function getPreviousReservationForApartment($resId, $apartmentId, $dateFrom)
    {
        /**
         * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        $preReservation = $bookingDao->getPreviousReservationForApartment($resId, $apartmentId, $dateFrom);

        if ($preReservation) {
            $lockDatas = $bookingTicketService->getLockByReservation(
                $preReservation['id'],
                $preReservation['pin'],
                [
                    LockService::USAGE_APARTMENT_TYPE,
                    LockService::USAGE_BUILDING_TYPE
                ]
            );

            foreach ($lockDatas as $key => $lockData) {
                switch ($key) {
                    case LockService::USAGE_APARTMENT_TYPE:
                        $preReservation['pin'] = $lockData['code'];
                        $preReservation['lock_type'] = $lockData['type'];
                        $preReservation['next_pin']  = $lockData['code'];

                        if (in_array($lockData['type'], LockService::$typeWithoutCode)) {
                            $preReservation['next_pin'] = null;
                        }
                        break;
                    case LockService::USAGE_BUILDING_TYPE:
                        $preReservation['outside_door_code'] = $lockData['code'];
                        break;
                }
            }
        }

        return $preReservation;
    }

    public function getLastReservationForApartment($apartmentId, $todayDate, $dateTimeToday)
    {
        /**
         * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        $lastReservation = $bookingDao->getLastReservationForApartment($apartmentId,$todayDate,$dateTimeToday);

        if ($lastReservation) {
            $lockDatas = $bookingTicketService->getLockByReservation(
                $lastReservation['id'],
                $lastReservation['pin'],
                [
                    LockService::USAGE_APARTMENT_TYPE,
                    LockService::USAGE_BUILDING_TYPE
                ]
            );

            foreach ($lockDatas as $key => $lockData) {
                switch ($key) {
                    case LockService::USAGE_APARTMENT_TYPE:
                        $lastReservation['pin'] = $lockData['code'];
                        $lastReservation['lock_type'] = $lockData['type'];
                        $lastReservation['next_pin']  = $lockData['code'];

                        if (in_array($lockData['type'], LockService::$typeWithoutCode)) {
                            $lastReservation['next_pin'] = null;
                        }
                        break;
                    case LockService::USAGE_BUILDING_TYPE:
                        $lastReservation['outside_door_code'] = $lockData['code'];
                        break;
                }
            }
        }

        return $lastReservation;
    }

    public function getReservationByIdForHousekeeping($resId)
    {
        /**
         * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        $lastReservation = $bookingDao->getReservationByIdForHousekeeping($resId);

        if ($lastReservation) {
            $lockDatas = $bookingTicketService->getLockByReservation(
                $lastReservation['id'],
                $lastReservation['pin'],
                [
                    LockService::USAGE_APARTMENT_TYPE,
                    LockService::USAGE_BUILDING_TYPE
                ]
            );

            foreach ($lockDatas as $key => $lockData) {
                switch ($key) {
                    case LockService::USAGE_APARTMENT_TYPE:
                        $lastReservation['pin'] = $lockData['code'];
                        $lastReservation['lock_type'] = $lockData['type'];
                        $lastReservation['next_pin']  = $lockData['code'];

                        if (in_array($lockData['type'], LockService::$typeWithoutCode)) {
                            $lastReservation['next_pin'] = null;
                        }
                        break;
                    case LockService::USAGE_BUILDING_TYPE:
                        $lastReservation['outside_door_code'] = $lockData['code'];
                        break;
                }
            }
        }

        return $lastReservation;
    }

    public function getNextReservationsForApartment($apartmentId,$todayDate, $lastReservation, $dateTimeAfter2days = false)
    {
        /**
         * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        $nextReservations = $bookingDao->getNextReservationsForApartment($apartmentId,$todayDate, $lastReservation, $dateTimeAfter2days);
        $nextReservations = iterator_to_array($nextReservations);

        foreach ($nextReservations as $index => $nextReservation) {
            $lockDatas = $bookingTicketService->getLockByReservation(
                $nextReservation['id'],
                $nextReservation['pin'],
                [
                    LockService::USAGE_APARTMENT_TYPE,
                    LockService::USAGE_BUILDING_TYPE
                ]
            );

            foreach ($lockDatas as $key => $lockData) {
                switch ($key) {
                    case LockService::USAGE_APARTMENT_TYPE:
                        $nextReservations[$index]['lock_type'] = $lockData['type'];
                        $nextReservations[$index]['pin']       = $lockData['code'];
                        $nextReservations[$index]['next_pin']  = $lockData['code'];

                        if (in_array($lockData['type'], LockService::$typeWithoutCode)) {
                            $nextReservations[$index]['next_pin'] = null;
                        }
                        break;
                    case LockService::USAGE_BUILDING_TYPE:
                        $nextReservations[$index]['outside_door_code'] = $lockData['code'];
                        break;
                }
            }
        }

        return $nextReservations;
    }
    public function getActiveReservationStartingFromDate($dateStarting,$dateTill)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        return $bookingDao->getActiveReservationStartingFromDate($dateStarting,$dateTill);
    }

    /**
     * @param $resDateFrom
     * @param $apartmentId
     * @return bool|string
     */
    public function getMoveStartDate($resDateFrom, $apartmentId)
    {
        /**
         * @var \DDD\Dao\Apartment\General $apartmentDao
         */
        $apartmentDao = $this->getServiceLocator()->get('dao_apartment_general');

        $apartmentData = $apartmentDao->getApartmentDataForReservationMove($apartmentId);
        $today = Helper::getCurrenctDateByTimezone($apartmentData['timezone']);
//        $time = Helper::getCurrenctDateByTimezone($apartmentData['timezone'], 'H:i:s');
//        if ($resDateFrom > $today) {
//            return $resDateFrom;
//        } else if ($time >= $apartmentData['check_in']) {
//            return $today;
//        } else {
//            return Helper::getDateByTimeZone('-1 days', $apartmentData['timezone']);
//        }
        if ($resDateFrom >= $today) {
            return $resDateFrom;
        } else {
            return $today;
        }
    }

    public function getMoveReservationsByResNumbers($resNumbers)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        return $bookingDao->getMoveReservationsByResNumbers($resNumbers);
    }

    public function simultaneouslyMoveReservations($moves)
    {
        /**
         * @var \DDD\Service\Apartment\General $apartmentService
         * @var \DDD\Service\Apartment\Inventory $inventoryService
         * @var \DDD\Dao\Apartment\Inventory $inventoryDao
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $apartmentGroupDao
         * @var BackofficeAuthenticationService $auth
         * @var \DDD\Service\Queue\InventorySyncQueue $inventorySyncQueueService
         * @var \DDD\Service\Availability $availabilityService
         * @var \DDD\Service\Queue\InventorySynchronizationQueue $syncService
         * @var \DDD\Service\Parking\Spot\Inventory $parkingInventoryService
         */
        $apartmentService          = $this->getServiceLocator()->get('service_apartment_general');
        $inventoryService          = $this->getServiceLocator()->get('service_apartment_inventory');
        $auth                      = $this->getServiceLocator()->get('library_backoffice_auth');
        $reservationDao            = new \DDD\Dao\Booking\Booking($this->getServiceLocator(), 'ArrayObject');
        $syncService               = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');
        $logger                    = $this->getServiceLocator()->get('ActionLogger');
        $bookingTicketService      = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $availabilityService       = $this->getServiceLocator()->get('service_availability');
        $parkingInventoryService   = $this->getServiceLocator()->get('service_parking_spot_inventory');

        $identity      = $auth->getIdentity();
        $log           = [];
        $overbookedLog = [];

        $return = [
            'status' => 'error',
            'msg'    => 'Unable to make moves.'
        ];

        $inventoryDao      = $this->getInventoryDao();
        $apartmentGroupDao = $this->getApartmentGroupDao();
        $resNumbers        = array_keys($moves);
        $reservations      = iterator_to_array($this->getMoveReservationsByResNumbers($resNumbers));

        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        $bookingDao->beginTransaction();

        // Step 1: Open availabilities for current apartments of reservations
        foreach ($reservations as $reservation) {
            if ($reservation['overbooking_status'] != BookingTicket::OVERBOOKING_STATUS_OVERBOOKED) {
                // We don't pass rate id and room id cause we don't really need them.
                $availabilityService->updateAvailabilityForApartmentDateRange($reservation['apartment_id_assigned'], $reservation['date_from'], $reservation['date_to'], 1);
            }
        }
        // Step 1.2: check the capacity of destination apartment
        $paxMismatchedReservations = [];

        foreach ($moves as $originResNum => $destination) {
            if (!empty($destination)) {
                $reservationArrays = (array)$reservations;
                foreach ($reservationArrays as $reservationArray) {
                    if (array_search($originResNum, (array)$reservationArray) !== false) {
                        $occupancy = $reservationArray['occupancy'];

                        $destinationApartment = $apartmentService->getApartmentGeneral($destination);

                        if ($destinationApartment['max_capacity'] < $occupancy) {
                            array_push($paxMismatchedReservations, $originResNum);
                        }
                    }
                }
            }
        }

        if (count($paxMismatchedReservations)) {
            $bookingDao->rollbackTransaction();
            $return['msg'] = 'Unable to move reservation' .
                (count($paxMismatchedReservations) > 1 ? 's' : '') .
                ' <b>' . implode('</b>, <b>', $paxMismatchedReservations) . '</b> to a lower capacity apartment';
            return $return;
        }

        // Step 2: Check move possibility
        foreach ($reservations as $reservation) {
            // Check reservation for being outdated
            if (strtotime($reservation['date_from']) < strtotime("yesterday")) {
                $bookingDao->rollbackTransaction();

                $return['msg'] = 'Unable to make moves. Reservation #' .
                    $reservation['res_number'] . ' is outdated.';
                return $return;
            }

            if (!empty($moves[$reservation['res_number']])) {
                // Check destination apartment to be in $reservation['apartel_id']
                // or just in same apartel as $reservation['apartment_id_assigned'] if (apartel_id = 0 || apartel_id = -1)
                $apartelCheck = $apartmentGroupDao->checkApartmentsInSameApartel(
                    $reservation['apartment_id_assigned'],
                    $moves[$reservation['res_number']],
                    $reservation['apartel_id']
                );

                // Destination apartment and currently assigned apartments don't have any mutual apartels
                if (!$apartelCheck) {
                    $return['msg'] = 'Unable to make moves. Destination apartment for reservation <b>' .
                        $reservation['res_number'] . '</b> is not in same apartel with currently assigned apartment.';
                    return $return;
                }

                if ($reservation['apartel_id'] > 0) {

                }

                // Check destination apartments availability
                $isAvailable = $inventoryDao->checkApartmentAvailability(
                    $moves[$reservation['res_number']],
                    $reservation['date_from'],
                    $reservation['date_to']
                );

                if (!$isAvailable) {
                    $bookingDao->rollbackTransaction();

                    $return['msg'] = 'Unable to make moves. Destination apartment for reservation ' .
                            $reservation['res_number'] . ' is not available.';
                    return $return;
                }
            }
        }

        // Step 3: Process moves
        foreach ($reservations as $reservation) {
            $assignedApartment    = $reservation['apartment_id_assigned'];
            $destinationApartment = $moves[$reservation['res_number']];
            $dateFrom             = $reservation['date_from'];
            $dateTo               = $reservation['date_to'];

            // Push to synchronization queue
            $syncService->push($assignedApartment, $dateFrom, $dateTo);

            // for old apartment
            $availabilityService->updateAvailabilityForApartelByApartmentDateRange($assignedApartment, $dateFrom, $dateTo, true);

            if (empty($moves[$reservation['res_number']])) {

                $overbooked = $bookingTicketService->changeOverbookingStatus(
                    $reservation['id'],
                    BookingTicket::OVERBOOKING_STATUS_OVERBOOKED,
                    false
                );

                array_push($overbookedLog, "Res: {$reservation['res_number']}, Apartment : {$assignedApartment} ({$reservation['apartment_name']})");
            }

            if (!empty($moves[$reservation['res_number']])) {

                $sameApartment = false;
                if ($assignedApartment == $destinationApartment) {
                    $sameApartment = true;
                }

                $preOverbookingStatus = $reservation['overbooking_status'];


                $bookingUpdateData = [];
                $bookingUpdateData['apartment_id_assigned'] = $destinationApartment;

                // Save new apartment for reservation
                $bookingDao->save($bookingUpdateData, ['id' => $reservation['id']]);

                // Close Availability for new apartment
                $availabilityService->updateAvailabilityForApartmentDateRange($destinationApartment, $dateFrom, $dateTo, 0, true);


                // for new apartment
                $availabilityService->updateAvailabilityForApartelByApartmentDateRange($destinationApartment, $dateFrom, $dateTo, true);

                // change nightly and charges data to new apartment
                $this->changeApartmentNightlyAndChargesDataOnMove($reservation['id'], $destinationApartment, $dateFrom, $dateTo);

                // Change parking location if this reservation has parking charges.
                $parkingInventoryService->changeParkingByApartment(
                    $reservation['id'],
                    $reservation['res_number'],
                    $destinationApartment,
                    $dateFrom,
                    $dateTo,
                    $dateFrom
                );

                if (!$sameApartment) {
                    $newApartmentName = $apartmentService->getApartmentGeneral($destinationApartment)['name'];

                    $logMsg = 'Reservation Moved From <b>' . $reservation['apartment_name'] . '</b> to <b>' . $newApartmentName . '</b>.';

                    $logger->save(Logger::MODULE_BOOKING, $reservation['id'], Logger::ACTION_RESERVATION_MOVE, $logMsg);

                    $taskService = $this->getServiceLocator()->get('service_task');
                    //delete auto-created cleaning tasks
                    $taskService->deleteTask([
                        'res_id'         => $reservation['id'],
                        'property_id'    => $assignedApartment,
                        'is_hk'          => TaskService::TASK_IS_HOUSEKEEPING,
                    ]);
                    $bookingTicket        = $bookingTicketService->getBookingThicketByReservationId($reservation['id']);
                    if ($bookingTicket->getArrivalStatus() == ReservationTicketService::BOOKING_ARRIVAL_STATUS_CHECKED_IN) {
                        //checked in, but canceled
                        //create extra task for cleaning, and key change for tomorrow
                        $taskService->createExtraCleaningCancelReservationAfterCheckin($reservation['id'], $assignedApartment, Task::CASE_MOVE);
                    } else {
                        //if the previous task is done , or the reservation has started
                        //create extra task for key change on next reservation's checkin date
                        $taskService->checkAndCreateExtraTaskForStartedReservationsCancelation($reservation['id'], $assignedApartment, Task::CASE_MOVE);
                    }

                    array_push($log, "Res: {$reservation['res_number']}, Apartment From: {$assignedApartment} ({$reservation['apartment_name']}), Apartment To: {$destinationApartment}");

                    if ($preOverbookingStatus == BookingTicket::OVERBOOKING_STATUS_OVERBOOKED) {
                        $bookingTicketService->changeOverbookingStatus(
                            $reservation['id'],
                            BookingTicket::OVERBOOKING_STATUS_RESOLVED,
                            true,
                            true
                        );
                        array_push($overbookedLog, "Res: {$reservation['res_number']}, Apartment : {$assignedApartment} ({$reservation['apartment_name']})");
                    }
                } else {
                    $bookingTicketService->changeOverbookingStatus(
                        $reservation['id'],
                        BookingTicket::OVERBOOKING_STATUS_RESOLVED,
                        true,
                        true
                    );

                    array_push($overbookedLog, "Res: {$reservation['res_number']}, Apartment : {$assignedApartment} ({$reservation['apartment_name']})");
                }
            }
        }



        $bookingDao->commitTransaction();

        $this->gr2info('Reservations overbooking status have been changed with tetris', [
            'user_id'      => $identity->id,
            'full_message' => implode(", \n", $overbookedLog),
        ]);

        $this->gr2info('Reservations has been moved with tetris', [
            'user_id'      => $identity->id,
            'user_name'    => $identity->firstname . ' ' . $identity->lastname,
            'full_message' => implode(", \n", $log),
        ]);

        $return = [
            'status' => 'success',
            'msg'    => 'Moves successfully done.'
        ];

        return $return;
    }

    /**
     * @param $reservationId
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     */
    public function changeApartmentNightlyAndChargesDataOnMove($reservationId, $apartmentId, $dateFrom, $dateTo)
    {
        /**
         * @var \DDD\Dao\Booking\ReservationNightly $reservationNightlyDao
         * @var \DDD\Dao\Booking\Charge $chargeDao
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var \DDD\Dao\Apartment\Rate $apartmentRateDao
         * @var \DDD\Dao\Apartel\Rate $apartelRateDao
         * @var \DDD\Dao\Apartment\Room $apartmentRoomDao
         * @var \DDD\Dao\Apartel\RelTypeApartment $relApartelRoomTypeApartment
         */
        $reservationNightlyDao = $this->getServiceLocator()->get('dao_booking_reservation_nightly');
        $apartmentRateDao = $this->getServiceLocator()->get('dao_apartment_rate');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $apartelRateDao = $this->getServiceLocator()->get('dao_apartel_rate');
        $chargeDao = $this->getServiceLocator()->get('dao_booking_charge');

        // check is apartel
        $apartel = $bookingDao->checkIsApartel($reservationId);
        $roomTypeId = 0;
        if (!$apartel) {
            $apartmentRoomDao = $this->getServiceLocator()->get('dao_apartment_room');
            $roomData = $apartmentRoomDao->fetchOne(['apartment_id' => $apartmentId]);
            if ($roomData) {
                $roomTypeId = $roomData->getId();
            }
        }

        $getNightlyData = $reservationNightlyDao->getNightlyDataByResIdDate($reservationId, $dateFrom, $dateTo, $apartel);

        foreach ($getNightlyData as $night) {
            // set reservation nightly apartment to new assignment apartment
            $nightlyData = ['apartment_id' => $apartmentId];

            // get same type rate
            if (!$apartel) {

                // set Room
                if ($roomTypeId) {
                    $nightlyData['room_id'] = $roomTypeId;

                    // set room id for reservation table
                    $bookingDao->save(['room_id' => $roomTypeId], ['id' => $night['reservation_id']]);
                }

                $getSameRateType = $apartmentRateDao->getSameRateType($apartmentId, $night['rate_type'], $night['rate_capacity'], $night['date']);
                if ($getSameRateType && $getSameRateType['has_inventory']) {
                    $nightlyData['rate_id'] = $getSameRateType['id'];
                } else {
                    $apartmentParentRate = $apartmentRateDao->getApartmentParentRate($apartmentId);
                    $nightlyData['rate_id'] = $apartmentParentRate['id'];
                }
            }

            // nightly data
            $reservationNightlyDao->save($nightlyData, ['id' => $night['id']]);

            // set charge apartment to new assignment apartment
            $chargeDao->save(['apartment_id' => $apartmentId], ['reservation_nightly_id' => $night['id']]);


        }
    }

    /**
     * @param array $data
     * @return array
     */
    public function getPayToPartnerReservations(array $data)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        $where = new Where();

        if (isset($data['res_number'])) {
            $where->equalTo(DbTables::TBL_BOOKINGS . '.res_number', $data['res_number']);
        } else {
            $partnerId = $data['partner'];
            $dateFrom = $data['date_from'];
            $dateTo = $data['date_to'];
            $dist = $data['dist'];

            $where->equalTo(DbTables::TBL_BOOKINGS . '.partner_id', $partnerId);
            $where->greaterThanOrEqualTo(DbTables::TBL_BOOKINGS . '.date_to', date('Y-m-d', strtotime($dateFrom)));
            $where->lessThanOrEqualTo(DbTables::TBL_BOOKINGS . '.date_to', date('Y-m-d', strtotime($dateTo)));
            $where->greaterThan(DbTables::TBL_BOOKINGS . '.partner_balance', 0);
            $where->equalTo(DbTables::TBL_BOOKINGS . '.partner_settled', 0);
            $where->equalTo(DbTables::TBL_BOOKINGS . '.payment_settled', 1);
            $where->expression(DbTables::TBL_BOOKINGS . '.apartment_id_assigned not in (?, ?)',
                [Constants::TEST_APARTMENT_1, Constants::TEST_APARTMENT_2]
            );

            if (is_array($dist) && count($dist)) {
                $apartmentIdList = [];
                $apartelIdList = [];
                $fiscalList = [];

                foreach ($dist as $distItem) {
                    list($entityType, $entityId) = explode('_', $distItem);

                    // 1 for apartment, 2 for apartel (as a convention)
                    if ($entityType == Distribution::TYPE_APARTMENT) {
                        array_push($apartmentIdList, $entityId);
                    } elseif ($entityType == Distribution::TYPE_APARTEL) {
                        array_push($apartelIdList, $entityId);
                    } else {
                        array_push($fiscalList, $entityId);
                    }
                }

                if (!count($apartmentIdList) && !count($apartelIdList) && !count($fiscalList)) {
                    throw new \RuntimeException('Entity not selected.');
                }

                $wrapperPredicate = new Predicate();
                $checkAddOr = false;

                // apartment
                if (count($apartmentIdList)) {
                    $predicate = new Predicate();
                    $predicate
                        ->in(DbTables::TBL_BOOKINGS . '.apartment_id_origin', $apartmentIdList)
                        ->lessThan(DbTables::TBL_BOOKINGS . '.apartel_id', 1);

                    $wrapperPredicate->addPredicate($predicate);
                    $checkAddOr = true;
                }

                // apartel
                if (count($apartelIdList)) {
                    if ($checkAddOr) {
                        $wrapperPredicate->or;
                    }
                    $checkAddOr = true;
                    $wrapperPredicate->in(DbTables::TBL_BOOKINGS . '.apartel_id', $apartelIdList);
                }

                // fiscal
                if (count($fiscalList)) {
                    if ($checkAddOr) {
                        $wrapperPredicate->or;
                    }
                    $wrapperPredicate->in('fiscal.id', $fiscalList);
                }

                $where->addPredicate($wrapperPredicate);
            }
        }

        $reservations = $bookingDao->getPayToPartnerReservationsByFilter($where);
        $reservationList = [];

        if ($reservations->count()) {
            foreach ($reservations as $reservation) {
                array_push($reservationList, [
                    'id' => $reservation->getReservationId(),
                    'res_number' => $reservation->getReservationNumber(),
                    'status' => $reservation->getStatus(),
                    'apartel_id' => $reservation->getApartelId(),
                    'booking_date' => $reservation->getBookingDate(),
                    'departure_date' => $reservation->getDepartureDate(),
                    'apartment_id' => $reservation->getApartmentId(),
                    'apartment_name' => $reservation->getApartmentName(),
                    'partner_id' => $reservation->getPartnerId(),
                    'partner_name' => $reservation->getPartnerName(),
                    'guest_balance' => $reservation->getGuestBalance(),
                    'partner_balance' => $reservation->getPartnerBalance(),
                    'symbol' => $reservation->getSymbol(),
                    'currency_id' => $reservation->getCurrencyId(),
                ]);
            }
        }

        return $reservationList;
    }

    public function getPartnerDao() {
    	if ($this->_partnerDao === null) {
    		$this->_partnerDao = new \DDD\Dao\Partners\Partners($this->getServiceLocator(), 'DDD\Domain\Partners\PartnerBooking');
    	}
    	return $this->_partnerDao;
    }

    /**
     * @param string $domain
     * @return \DDD\Dao\Booking\BookingQueue
     */
    private function getBookingQueueDao($domain = 'DDD\Domain\Booking\BookingQueue') {
    	$this->bookingQueueDao = new \DDD\Dao\Booking\BookingQueue($this->getServiceLocator(), $domain);

    	return $this->bookingQueueDao;
    }

    /**
     * @param string $domain
     * @return \DDD\Dao\User\UserManager
     */
    private function getUserManagerDao($domain = 'DDD\Domain\User\User') {
    	return new \DDD\Dao\User\UserManager($this->getServiceLocator(), $domain);
    }

    /**
     * @param string $domain
     * @return \DDD\Dao\Apartment\Inventory
     */
    public function getInventoryDao($domain = 'ArrayObject')
    {
        return new \DDD\Dao\Apartment\Inventory($this->getServiceLocator(), $domain);
    }

    /**
     * @param string $domain
     * @return \DDD\Dao\ApartmentGroup\ApartmentGroup
     */
    public function getApartmentGroupDao($domain = 'ArrayObject')
    {
        return new \DDD\Dao\ApartmentGroup\ApartmentGroup($this->getServiceLocator(), $domain);
    }
}
