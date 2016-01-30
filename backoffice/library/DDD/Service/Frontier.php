<?php

namespace DDD\Service;

use CreditCard\Service\Card;
use DDD\Dao\Booking\Booking as ReservationsDAO;
use Library\ActionLogger\Logger;
use Library\Constants\DomainConstants;
use Library\Constants\Roles;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use DDD\Service\ServiceBase;
use DDD\Service\Task as TaskService;
use DDD\Service\Booking\BookingAddon;
use DDD\Service\Booking\ReservationIssues;
use DDD\Service\Finance\Customer;
use Library\Constants\TextConstants;
use DDD\Service\Booking\BookingTicket as ReservationTicketService;

use Library\Constants\Constants;

class Frontier extends ServiceBase
{
    const CARD_RESERVATION = 1;
    const CARD_APARTMENT   = 2;
    const CARD_BUILDING    = 3;

    const FRONTIER_QUICK_TASK = 1;
    const FRONTIER_FOB_TASK = 2;
    const FRONTIER_KEY_TASK = 3;

    const LIMIT_APARTMENT_SHOW_COUNT_BUILDING_CARD = 10;
    /**
     * @param $query
     * @param int $limit
     * @return array
     */
    public function findCards($query, $limit = 0)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $reservationDao
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $apartmentGroupDao
         * @var \DDD\Dao\Apartment\General $apartmentDao
         */
        $reservationDao    = $this->getServiceLocator()->get('dao_booking_booking');
        $apartmentDao      = $this->getServiceLocator()->get('dao_apartment_general');
        $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $user = $auth->getIdentity();

        $reservationCards = $reservationDao->getFrontierCardList($query, $user, $limit);
        $cards = [];
        if ($reservationCards) {
            foreach ($reservationCards as $row) {
                $cards[] = [
                    'id'    => self::CARD_RESERVATION . '_' . $row->getId(),
                    'type'  => self::CARD_RESERVATION,
                    'text'  => $row->getResNumber(),
                    'label' => 'reservation-card',
                    'info'  => $row->getGuest() . ', ' . $row->getApartmentAssigned()
                ];
            }
        }

        $apartmentCards = $apartmentDao->getFrontierCardList($query, $user, $limit);
        if ($apartmentCards) {
            foreach ($apartmentCards as $row) {
                $info = [];
                if ($row->getBuilding()) {
                    $info[] = $row->getBuilding();
                }
                if ($row->getUnitNumber()) {
                    $info[] = $row->getUnitNumber();
                }
                $cards[] = [
                    'id'    => self::CARD_APARTMENT . '_' . $row->getId(),
                    'type'  => self::CARD_APARTMENT,
                    'text'  => $row->getName(),
                    'label' => 'apartment-card',
                    'info'  => implode(', ', $info)
                ];
            }
        }

        $buildingCards = $apartmentGroupDao->getFrontierCardList($query, $user, $limit);
        if ($buildingCards) {
            foreach ($buildingCards as $row) {
                $cards[] = [
                    'id'    => self::CARD_BUILDING . '_' . $row->getId(),
                    'type'  => self::CARD_BUILDING,
                    'text'  => $row->getName(),
                    'label' => 'building-card',
                    'info'  => ''
                ];
            }
        }

        return $cards;
    }

    /**
     * @param int $id
     * @param int $type
     * @param ControllerBase $controller
     * @return array|bool
     */
    public function getTheCard($id, $type, ControllerBase $controller)
    {
        $card = false;
        switch ($type) {
            case self::CARD_RESERVATION:
                $card = $this->getReservationCard($id, $controller);
                break;
            case self::CARD_APARTMENT:
                $card = $this->getApartmentCard($id);
                break;
            case self::CARD_BUILDING:
                $card = $this->getBuildingCard($id);
                break;
        }

        return $card;
    }

    /**
     * @param int $id
     * @param ControllerBase $controller
     * @return array | bool
     */
    public function getReservationCard($id, ControllerBase $controller)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $reservationDao
         */
        $card = false;

        /**
         *  @var $reservationDao \DDD\Dao\Booking\Booking
         *  @var $cccaDao \DDD\Dao\Finance\Ccca
        */
        $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');
        $cccaDao        = $this->getServiceLocator()->get('dao_finance_ccca');

        $result = $reservationDao->getTheCard($id);

        // check has frontier charge permission
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $router = $controller->getEvent()->getRouter();

        if ($result) {
            $card = [
                'id'                  => $result->getId(),
                'bookingStatus'       => $result->getBookingStatus(),
                'resNumber'           => $result->getResNumber(),
                'name'                => $result->getResNumber(),
                'guest'               => $result->getGuest(),
                'apartmentAssignedId' => $result->getApartmentAssignedId(),
                'apartmentAssigned'   => $result->getApartmentAssigned(),
                'building'            => $result->getBuilding(),
                'buildingId'          => $result->getBuildingId(),
                'unitNumber'          => $result->getUnitNumber(),
                'guest_phone'         => $result->getGuestPhone(),
                'travelPhone'         => $result->getGuestTravelPhone(),
                'dateFrom'            => $result->getDateFrom() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($result->getDateFrom())) : '',
                'dateTo'              => $result->getDateTo() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($result->getDateTo())) : '',
                'arrivalDate'         => $result->getArrivalDate() ? date(Constants::GLOBAL_DATE_FORMAT . ' H:i', strtotime($result->getArrivalDate())) : '',
                'departureDate'       => $result->getDepartureDate() ? date(Constants::GLOBAL_DATE_FORMAT . ' H:i', strtotime($result->getDepartureDate())) : '',
                'now'                 => Helper::getCurrenctDateByTimezone($result->getTimezone()),
                'arrivalStatus'       => $result->getArrivalStatus(),
                'occupancy'           => $result->getOccupancy(),
                'guestBalance'        => $result->getGuestBalance(),
                'housekeepingComments' => $result->getHousekeepingComments(),
                'cccaVerified'        => $result->getCccaVerified(),
                'parking'             => $result->getParking(),
                'parking_url'         => $router->assemble(['res_num' => $result->getResNumber()], ['name' => 'frontier/print-parking-permits']),
                'key_task'             => $result->getKeyTask(),
                'keyTask'             => $result->getKeyTask(),
                'guestEmail'          => $result->getGuestEmail(),
                'cccaPageStatus'      => $result->getCccaPageStatus(),
                'cccaPageToken'       => $result->getCccaPageToken(),
                'apartmentCurrencyCode'  => $result->getApartmentCurrencyCode(),
                'cccaPageLink'        => '',
            ];

            if (strtotime($card['now']) <= strtotime($card['dateTo']) && $result->getKiPageStatus() == ReservationTicketService::SEND_KI_PAGE_STATUS && $result->getKiPageHash()) {
                $card['keyUrl'] = 'https://'.DomainConstants::WS_SECURE_DOMAIN_NAME.'/key?code='
                                  . $result->getKiPageHash()
                                  . '&view=0'
                                  . '&bo=' . $result->getKiPageGodModeCode();
            }

            if ($auth->hasRole(Roles::ROLE_FRONTIER_CHARGE)) {
                $card['frontierCharge'] = '/frontier/charge/' . $result->getId() . '/0/' . Helper::hashForFrontierCharge($result->getId());
            }

            if (is_null($result->getArrivalTime())) {
                $card['arrivalTime'] = date('H:i', strtotime($result->getApartmentCheckInTime()));
            } else {
                $card['arrivalTime'] = date('H:i', strtotime($result->getArrivalTime()));
            }

            /* @var $chargeAuthorizationService \DDD\Service\Reservation\ChargeAuthorization */
            $chargeAuthorizationService = $this->getServiceLocator()->get('service_reservation_charge_authorization');
            $card['creditCardsForAuthorization'] = $chargeAuthorizationService->getCreditCardsForAuthorization($id);
            // CCCA page link
            $cccaData = $cccaDao->getAllSentCcca($id);

            if ($cccaData) {
                foreach ($cccaData as $index => $row) {
                    $lastDigit = 0;
                    foreach ($card['creditCardsForAuthorization'] as $cardInfo) {
                        if ($cardInfo->getId() == $row->getCcId()) {
                            $lastDigit = $cardInfo->getLast4Digits();
                            break;
                        }
                    }
                    $card['cccaPageLink'][$index] = [
                        'link'      => 'https://' . DomainConstants::WS_SECURE_DOMAIN_NAME . '/ccca-page?token=' . $row->getPageToken(),
                        'lastDigit' => $lastDigit
                    ];

                }
            }

            $card['hasMoreCards'] = false;
            if (count($card['creditCardsForAuthorization']) > $cccaData->count()) {
                $card['hasMoreCards'] = true;
            }
        }

        return $card;
    }

    /**
     * @param int $id
     * @return array | bool
     */
    public function getApartmentCard($id)
    {
        $card                = false;
        /** @var \DDD\Dao\Apartment\General $apartmentDao */
        $apartmentDao        = $this->getServiceLocator()->get('dao_apartment_general');
        /** @var \DDD\Dao\Booking\Booking $resDao */
        $resDao              = $this->getServiceLocator()->get('dao_booking_booking');
        /** @var \DDD\Dao\Textline\Apartment $texlineApartmentDao */
        $texlineApartmentDao = $this->getServiceLocator()->get('dao_textline_apartment');
        $result              = $apartmentDao->getTheCard($id);
        $preResNumber        = null;
        $preResId            = null;
        $preResGuest         = null;

        if (!is_null($result->getCurResId())) {
            $preRes = $resDao->getPreviousReservation($result->getId(), $result->getCurResDateFrom());

        } else {
            $preRes = $resDao->getPreviousReservation($id, date('Y-m-d'));
        }
        if ($preRes) {
            $preResId     = $preRes->getId();
            $preResNumber = $preRes->getResNumber();
            $preResGuest  = $preRes->getGuestFirstName() . ' ' . $preRes->getGuestLastName();
        }

        if ($result) {
            $card = [
                'id'                     => $result->getId(),
                'name'                   => $result->getName(),
                'address'                => $result->getAddress(),
                'unitNumber'             => $result->getUnitNumber(),
                'building'               => $result->getBuilding(),
                'buildingId'             => $result->getBuildingId(),
                'curResId'               => $result->getCurResId(),
                'curResNum'              => $result->getCurResNum(),
                'curResGuest'            => $result->getCurResGuest(),
                'bedroomCount'           => $result->getBedroomCount(),
                'primaryWiFiNetwork'     => $result->getPrimaryWiFiNetwork(),
                'primaryWiFiPass'        => $result->getPrimaryWiFiPass(),
                'secondaryWiFiNetwork'   => $result->getSecondaryWiFiNetwork(),
                'secondaryWiFiPass'      => $result->getSecondaryWiFiPass(),
                'preResNum'              => $preResNumber,
                'preResId'               => $preResId,
                'preResGuest'            => $preResGuest
            ];
        }

        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');
        $user    = $auth->getIdentity();
        $userId = $user->id;
        /** @var \DDD\Dao\Document\Document $documentsDao */
        $documentsDao = $this->getServiceLocator()->get('dao_document_document');
        $card['documents'] = $documentsDao->getApartmentDocumentsForFrontier($userId,$id);
        return $card;
    }

    /**
     * @param int $id
     * @return array | bool
     */
    public function getBuildingCard($id)
    {
        /**
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $apartmentGroupDao
         */
        $card              = false;
        $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $result            = $apartmentGroupDao->getTheCard($id);

        if ($result) {
            foreach ($result as $row) {
                if (!$card['id']) {
                    $card['id']   = $row->getId();
                    $card['name'] = $row->getName();
                }
                $card['apartments'][] = [
                    'id'   => $row->getApartmentId(),
                    'name' => $row->getApartmentName(),
                    'unit_number' => $row->getUnitNumber(),
                    'bedroom_count' => $row->getBedroomCount(),
                ];
            }
        }
        return $card;
    }

    /**
     * @param int $id
     * @param string $search
     * @return array
     */
    public function getApartmentsForBuilding($id, $search)
    {
        /**
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $apartmentGroupDao
         */
        $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $result            = $apartmentGroupDao->getTheCard($id, $search);
        $apartments = [];
        foreach ($result as $row) {
            array_push($apartments, [
                'id'   => $row->getApartmentId(),
                'name' => $row->getApartmentName(),
                'unit_number' => $row->getUnitNumber(),
                'bedroom_count' => $row->getBedroomCount(),
            ]);
        }
        return $apartments;
    }

    /**
     * @param $id
     * @param $type
     * @return array|bool
     */
    public function getEntityTasks($id, $type)
    {
        /**
         * @var \DDD\Dao\Task\Task $tasksDao
         */
        $tasksDao = $this->getServiceLocator()->get('dao_task_task');
        $tasks = false;
        switch ($type) {
            case self::CARD_RESERVATION:
                $result = $tasksDao->getTasksOnReservation($id);
                break;
            case self::CARD_APARTMENT:
                $result = $tasksDao->getFrontierTasksOnApartment($id);
                break;
            case self::CARD_BUILDING:
                $result = $tasksDao->getTasksOnBuilding($id);
                break;
        }

        if (!empty($result)) {
            foreach ($result as $row) {
                $tasks[] = [
                    'id'            => $row->getId(),
                    'title'         => $row->getTitle(),
                    'priority'      => $row->getPriority(),
                    'priorityLabel' => TaskService::getTaskPriorityLabeled()[$row->getPriority()],
                    'type'          => $row->getTaskTypeId()
                ];
            }
        }

        return $tasks;
    }

    /**
     * @param $bookingId
     * @param $itemId
     * @return array
     */
    public function getDataForFrontierCharge($bookingId, $itemId)
    {
        /**
         * @var \DDD\Service\Booking\BookingAddon $bookingAddonService
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var \DDD\Service\Taxes $taxesService
         */
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $bookingAddonService  = $this->getServiceLocator()->get('service_booking_booking_addon');
        $taxesService         = $this->getServiceLocator()->get('service_taxes');
        $apartmentGroupDao    = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $chargeDao            = $this->getServiceLocator()->get('dao_booking_charge');
        $bookingDao           = $this->getServiceLocator()->get('dao_booking_booking');
        $accDetailsDao         = new \DDD\Dao\Apartment\Details($this->getServiceLocator(), 'ArrayObject');

        $addonsArray = $bookingAddonService->getAddonsArray();
        $bookingData = $bookingDao->getBookingForFrontierCharge($bookingId);


        $detailsRow = $accDetailsDao->fetchOne(
            ['apartment_id' => $bookingData['apartment_id_assigned']]
        );

        if ($detailsRow && (int)$detailsRow['cleaning_fee']) {

            foreach($addonsArray as $key => $addon) {
                if ($addon['id'] == BookingAddon::ADDON_TYPE_CLEANING_FEE) {

                    $cleaningFee = $detailsRow['cleaning_fee'];

                    $addonsArray[$key]['cname']         = $bookingData['acc_currency_sign'];
                    $addonsArray[$key]['currency_rate'] = $bookingData['acc_currency_rate'];
                    $addonsArray[$key]['currency_id']   = $bookingData['acc_currency_id'];
                    $addonsArray[$key]['value']         = $cleaningFee;
                }
            }
        }

        $data = [
            'addons_array' => $addonsArray,
        ];

        $data['booking_data'] = $bookingData;
        $data['booking_data']['night_count'] = Helper::getDaysFromTwoDate($bookingData['date_to'], $bookingData['date_from']);

        // Taxes
        $nightCount = Helper::getDaysFromTwoDate($bookingData['date_to'], $bookingData['date_from']);
        $taxesParams = [
            'tot'                    => $bookingData['tot'],
            'tot_type'               => $bookingData['tot_type'],
            'tot_included'           => $bookingData['tot_included'],
            'tot_max_duration'       => $bookingData['tot_max_duration'],
            'tot_additional'         => $bookingData['tot_additional'],

            'vat'                    => $bookingData['vat'],
            'vat_type'               => $bookingData['vat_type'],
            'vat_included'           => $bookingData['vat_included'],
            'vat_additional'         => $bookingData['vat_additional'],
            'vat_max_duration'       => $bookingData['vat_max_duration'],

            'city_tax'               => $bookingData['city_tax'],
            'city_tax_type'          => $bookingData['city_tax_type'],
            'city_tax_included'      => $bookingData['city_tax_included'],
            'city_tax_additional'    => $bookingData['city_tax_additional'],
            'city_tax_max_duration'  => $bookingData['city_tax_max_duration'],

            'sales_tax'              => $bookingData['sales_tax'],
            'sales_tax_type'         => $bookingData['sales_tax_type'],
            'sales_tax_included'     => $bookingData['sales_tax_included'],
            'sales_tax_max_duration' => $bookingData['sales_tax_max_duration'],
            'sales_tax_additional'   => $bookingData['sales_tax_additional'],

            'apartment_currency'     => $bookingData['apartment_currency_code'],
            'customer_currency'      => $bookingData['guest_currency_code'],
            'country_currency'       => $bookingData['country_currency'],
            'night_count'            => $nightCount,
            'rate_capacity'          => $bookingData['rate_capacity'],
            'occupancy'              => $bookingData['occupancy'],
        ];
        $taxesData = $taxesService->getTaxesForCharge($taxesParams);
        $data += $taxesData;

        $apartmentGroup = $apartmentGroupDao->fetchOne(['id' => $itemId]);

        // Charges
        $charges = $chargeDao->getChargesByReservationId($bookingId, 1);
        $data['charges'] = $charges;

        $balances = $bookingTicketService->getSumAndBalanc($bookingId);
        $balance = number_format($balances['ginosiBalanceInApartmentCurrency'], 2, '.', '');
        $data['balance'] = $balance;
        $data['group_name'] = 'Group';

        if ($apartmentGroup) {
            $data['group_name'] = $apartmentGroup->getName();
        }

        return $data;
    }

    /**
     * @param $reservationId
     * @param $creditCardData
     * @return array
     */
    public function addCreditCard($reservationId, $creditCardData)
    {
        /**
         * @var ReservationsDAO $reservationsDao
         * @var Card $cardService
         */
        $reservationsDao = $this->getServiceLocator()->get('dao_booking_booking');
        $cardService = $this->getServiceLocator()->get('service_card');

        $reservationCustomerId = $reservationsDao->getCustomerIdByReservationId($reservationId);

        $creditCardData['source'] = Card::CC_SOURCE_FRONTIER_DASHBOARD_EMPLOYEE;
        $creditCardData['customer_id'] = $reservationCustomerId;

        $cardService->processCreditCardData($creditCardData);

        /**
         * @todo
         */
//        shell_exec('ginosole cc-creation-queue execute');

        return [
            'status' => 'success',
            'msg' => TextConstants::SUCCESS_ADD,
        ];
    }

    /**
     * @param $comment
     * @param $resId
     * @return bool|string
     */
    public function sendComment($comment, $resId)
    {
        /**
         * @var Logger $logger
         */
        $logger = $this->getServiceLocator()->get('ActionLogger');

        $logger->save(Logger::MODULE_BOOKING, $resId, Logger::ACTION_HOUSEKEEPING_COMMENT, $comment);

        return $logger->get(Logger::MODULE_BOOKING, $resId, Logger::ACTION_HOUSEKEEPING_COMMENT);
    }
}
