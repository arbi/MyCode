<?php

namespace DDD\Service;

use CreditCard\Service\Card;
use DDD\Dao\Accommodation\Accommodations as AccommodationDao;
use DDD\Dao\Apartment\Inventory;
use DDD\Dao\Apartment\Rate;
use DDD\Dao\Currency\Currency as CurrencyDao;
use DDD\Dao\Apartment\Details;
use DDD\Dao\Apartment\Room as ProductRoomCubilisDao;
use DDD\Domain\Apartment\Details\Sync;
use DDD\Domain\Apartment\Room\Cubilis as ProductRoomCubilisDomain;
use DDD\Domain\Apartment\ProductRate\Cubilis as ProductRateCubilisDomain;
use DDD\Service\Booking\BookingTicket as ReservationTicketService;
use DDD\Service\Booking as BookingService;
use DDD\Domain\Booking\ResId;
use DDD\Service\Reservation\Main;
use Library\ChannelManager\ChannelManager as ChannelManagerLib;
use Library\ChannelManager\CivilResponder;
use Library\ChannelManager\Provider\Cubilis\PHPDocInterface\ResInfo;
use Library\ChannelManager\Provider\Cubilis\PHPDocInterface\Reservation;
use Library\ChannelManager\Provider\Cubilis\PHPDocInterface\ReservationItem;
use Library\ActionLogger\Logger as ALogger;
use Library\ChannelManager\Testing\ConnectionTest;
use Library\Constants\Objects;
use Library\Constants\TextConstants;
use DDD\Dao\Booking\Partner as BookingPartnerDao;
use Library\Finance\CreditCard\CreditCard;

/**
 * Class ChannelManager
 * @important: sendConfirmation() now is off
 * @package DDD\Service
 */
class ChannelManager extends ServiceBase
{
    const ATTEMPTS = 5;

    private $dataStorage = [];
    private $counter = 0;
    const CANCELLED_TEXT = 'cancelled';

    /**
     * Pull Reservation from Cubilis. Entry point for cron.
     *
     * @return bool
     * @throws \Exception
     */
    public function pullReservation()
    {
        /**
         * @var ChannelManagerLib $chm
         * @var CivilResponder $result
         * @var Reservation $reservation
         * @var \DDD\Dao\Apartel\General $apartelDao
         */
        $this->gr2info('Pull reservation start', ['cron' => 'ChannelManager']);

        try {
            $connectionCheck = false;
            // apartment pull
            $accDao = $this->getDetailsDao();
            $accDomainList = $accDao->getReadyToSyncAccs();
            $chm = $this->getServiceLocator()->get('ChannelManager');

            if ($accDomainList->count()) {
                $chm->setProductType($chm::PRODUCT_APARTMENT);
                $currentApartment = $accDomainList->current();
                $connectionTest = $this->isTestPassed($currentApartment->getApartmentId());

                if ($connectionTest['status'] == 'success') {
                    $connectionCheck = true;
                    foreach ($accDomainList as $accDomain) {
                        $this->counterPlusPlus();

                        $result = $chm->cronCheckReservation([
                            'apartment_id' => $accDomain->getApartmentId(),
                            'data' => []
                        ]);

                        if ($result->getStatus() == CivilResponder::STATUS_SUCCESS) {
                            $reservation = $result->getData();
                            $channelResIdList = $this->handleReservations($reservation, $accDomain->getApartmentId());

                            // Look inside VCS for confirmation
                        } else {
                            if ($this->onLimit() && $this->isTimeout($result->getMessage())) {
                                $this->gr2err('Number of "Cubilis connection timeout" exceeded.', [
                                    'full_message' => $result->getMessage(),
                                    'cron'         => 'ChannelManager'
                                ]);

                                break;
                            } else {
                                $this->gr2err("Channel Manager result is '{$result->getStatus()}'", [
                                    'apartment_id'  => $accDomain->getApartmentId(),
                                    'provider'      => $result->getProvider(),
                                    'full_message'  => $result->getMessage()
                                ]);
                            }
                        }
                    }
                } else {
                    $this->gr2err("Cubilis connection test hasn't passed", [
                        'cron'         => 'ChannelManager',
                        'apartment_id' => $currentApartment->getApartmentId(),
                        'full_message' => $connectionTest['msg'],
                    ]);
                }
            } else {
                $this->gr2err("Apartments weren't found to sync with Cubilis", ['cron' => 'ChannelManager']);
            }

            // apartment pull
            $apartelDao = $this->getServiceLocator()->get('dao_apartel_general');
            $apartelDomainList = $apartelDao->getReadyToSyncCubilis();

            if ($apartelDomainList->count()) {
                $chm->setProductType($chm::PRODUCT_APARTEL);
                if (!$connectionCheck) {
                    $currentApartel = $apartelDomainList->current();
                    $connectionTest = $this->isTestPassed($currentApartel->getId(), true);
                    $connectionCheck = $connectionTest['status'] == 'success' ? true : false;
                }

                if ($connectionCheck) {
                    foreach ($apartelDomainList as $apartelDomain) {
                        $this->counterPlusPlus();

                        $result = $chm->cronCheckReservation([
                            'apartment_id' => $apartelDomain->getId(),
                            'data' => []
                        ]);

                        if ($result->getStatus() == CivilResponder::STATUS_SUCCESS) {
                            $reservation = $result->getData();
                            $channelResIdList = $this->handleReservations($reservation, $apartelDomain->getId(), true);

                            // Look inside VCS for confirmation
                        } else {
                            if ($this->onLimit() && $this->isTimeout($result->getMessage())) {
                                $this->gr2err('Number of "Cubilis connection timeout" exceeded.', [
                                    'full_message' => $result->getMessage(),
                                    'cron' => 'ChannelManager'
                                ]);

                                break;
                            } else {
                                $this->gr2err("Channel Manager result is '{$result->getStatus()}'", [
                                    'apartel_id'    => $apartelDomain->getId(),
                                    'provider'      => $result->getProvider(),
                                    'full_message'  => $result->getMessage()
                                ]);
                            }
                        }
                    }
                } else {
                    $this->gr2err('Cubilis connection test hasn\'t passed', [
                        'cron' => 'ChannelManager',
                        'apartel_id' => $currentApartel->getId(),
                        'full_message' => $connectionTest['msg'],
                    ]);
                }
            } else {
                $this->gr2err('No apartel found to sync with Cubilis', ['cron' => 'ChannelManager']);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            if ($e->getPrevious() instanceof \Exception) {
                $errorMessage = $errorMessage . ', ' . $e->getPrevious()->getMessage();
            }

            $this->gr2logException($e, 'Pull Reservation Failed');

            throw new \Exception('Critical! Pull Reservation Failed: ' . $errorMessage);
        }

        $this->gr2info('Pull reservation end', ['cron' => 'ChannelManager']);

        return false;
    }

    /**
     * @param $data
     * @param $productId
     * @param bool $isApartel
     * @return array
     */
    public function handleReservations($data, $productId, $isApartel = false)
    {
        /**
         * @var \DDD\Dao\ChannelManager\ReservationIdentificator $reservationIdentificatorDao
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $reservationIdentificatorDao = $this->getServiceLocator()->get('dao_channel_manager_reservation_identificator');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        $bookingDao->setEntity(new \DDD\Domain\Booking\ResId());

        $channelResIdList = [];
        $product = $isApartel ? 'Apartel' : 'Apartment';
        $logArray = [
            'cron' => 'ChannelManager',
            'product_id' => $productId,
            'product_type' => $product
        ];

        if ($data->getLength()) {
            $reservationStatus = $this->getPossibleStatuses();
            foreach ($data->getItems() as $res) {
                $this->gr2info("Reservation with status={$res->status} start", $logArray + ['reservation_status' => $res->status]);

                // get channel res id
                $channelResId = $this->getChannelResId($res);
                if (!$channelResId) {
                    // Other Strategy to detect the Reservation and return reservation number
                    $msg = "Missing channel res id. Trying to get reservation id via alternative strategy. {$product} {$productId}";
                    $this->gr2emerg($msg, $logArray);
                    break;
                }
                $result = false;
                if (in_array($res->status, $reservationStatus['reservation']) || in_array($res->status, $reservationStatus['modification'])) {

                    if (in_array($res->status, $reservationStatus['reservation'])) {
                        $reservationTypeText = 'New Reservation';
                        $reservationType = 'reservation';
                    } else {
                        $reservationTypeText = 'Modification';
                        $reservationType = 'modification';
                    }

                    $roomStayList = $this->getRoomStayList($res, $isApartel);
                    if (!empty($roomStayList)) {
                        if ($isApartel) {
                            if ($reservationType == 'reservation') {

                                // new reservations
                                if (count($roomStayList) == 1) {

                                    $this->gr2info("Apartel single room reservation", $logArray);

                                    $options = $this->conquerMyTrustCubilis($res, current($roomStayList), $channelResId, false, $productId, $reservationType, $isApartel);
                                    $result = $this->initReservationWithType($res, $productId, $options, current($roomStayList), $isApartel);
                                } else {
                                    /** @var \DDD\Dao\Apartel\Type $typeDaoApartment */
                                    $typeDaoApartment = $this->getServiceLocator()->get('dao_apartel_type');
                                    $buildings = $typeDaoApartment->getBuildingsListByApartelRoomListCount($productId, count($roomStayList));
                                    // set as overbooking
                                    if (!$buildings->count()) {

                                        $this->gr2info("Apartel multi room reservation. There is not available the same building.", $logArray);

                                        foreach ($roomStayList as $roomStay) {
                                            $options = $this->conquerMyTrustCubilis($res, $roomStay, $channelResId, false, $productId, $reservationType, $isApartel);
                                            if ($options) {
                                                $options['overbooking'] = true;
                                                $result = $this->initReservationWithType($res, $productId, $options, $roomStay, $isApartel);
                                            }
                                        }
                                    } else {
                                        $optionsList = $apartmentListUsed = [];
                                        foreach ($buildings as $building) {
                                            $checkIsOverbooking = false;
                                            $optionsList = $apartmentListUsed = [];
                                            foreach ($roomStayList as $roomStay) {
                                                $options = $this->conquerMyTrustCubilis($res, $roomStay, $channelResId, false, $productId, $reservationType, $isApartel, $building['building_id'], $apartmentListUsed);
                                                if ($options) {
                                                    $apartmentListUsed[] = $options['apartmentId'];
                                                    $optionsList[] = ['option' => $options, 'roomStay' => $roomStay];
                                                    if ($options['overbooking']) {
                                                        $checkIsOverbooking = true;
                                                    }
                                                } else {
                                                    $checkIsOverbooking = true;
                                                }
                                            }

                                            if (!$checkIsOverbooking && !empty($optionsList)) {
                                                break;
                                            }
                                        }

                                        $this->gr2info("Apartel multi room reservation. Has available the same building.", $logArray);

                                        foreach ($optionsList as $option) {
                                            $result = $this->initReservationWithType($res, $productId, $option['option'], $option['roomStay'], $isApartel);
                                        }
                                    }
                                }
                            } else {
                                /** @var \DDD\Service\Reservation\Identificator $reservationIdentificator */
                                $reservationIdentificator = $this->getServiceLocator()->get('service_reservation_identificator');
                                $checkExistingCount = $reservationIdentificatorDao->getExistingCount($channelResId);

                                if (count($roomStayList) == 1 && $checkExistingCount == 1) {
                                    $this->gr2info("Apartel single room reservation modify", $logArray);
                                    $bookingDomain = $bookingDao->getBookingTicketByChannel($channelResId, 'cubilis');
                                    $options = $this->conquerMyTrustCubilis($res, current($roomStayList), $channelResId, $bookingDomain, $productId, $reservationType, $isApartel);
                                    $result = $this->initReservationWithType($res, $productId, $options, current($roomStayList), $isApartel);
                                } else {
                                    $reservationsDetected = $reservationIdentificator->identificatorModification($roomStayList, $channelResId);
                                    $this->gr2info("Apartel multi room reservation modify", $logArray);
                                    if (!$reservationsDetected) {
                                        $result = false;
                                    } else {
                                        foreach ($reservationsDetected as $keyType => $typeItem) {
                                            foreach ($typeItem as $reservation) {
                                                if ($keyType == 'new') {
                                                    $options = $this->conquerMyTrustCubilis($res, $reservation['roomStay'], $channelResId, false, $productId, 'reservation', $isApartel, $reservation['buildingId'], [], true);
                                                    $result = $this->initReservationWithType($res, $productId, $options, $reservation['roomStay'], $isApartel); // $options['res']
                                                } elseif ($keyType == 'cancel') {
                                                    $result = $this->initCancelation($res, BookingService::BOOKING_STATUS_CANCELLED_PENDING, $reservation, $productId, $isApartel, true);
                                                } else {
                                                    $options = $this->conquerMyTrustCubilis($res, $reservation['roomStay'], $channelResId, $reservation['resData'], $productId, 'modification', $isApartel);
                                                    $result = $this->initReservationWithType($res, $productId, $options, $reservation['roomStay'], $isApartel);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $bookingDomain = $bookingDao->getBookingTicketByChannel($channelResId, 'cubilis');
                            $options = $this->conquerMyTrustCubilis($res, current($roomStayList), $channelResId, $bookingDomain, $productId, $reservationType, $isApartel);
                            $result = $this->initReservationWithType($res, $productId, $options, current($roomStayList), $isApartel);
                        }
                    }
                } elseif (in_array($res->status, $reservationStatus['cancelation']) || in_array($res->status, $reservationStatus['request_denied'])) {
                    // Cancellation
                    if (in_array($res->status, $reservationStatus['request_denied'])) {
                        $cancelationType = BookingService::BOOKING_STATUS_CANCELLED_BY_GINOSI;
                        $reservationTypeText = 'Deletion (by Ginosi)';
                    } else {
                        $cancelationType = BookingService::BOOKING_STATUS_CANCELLED_PENDING;
                        $reservationTypeText = 'Cancellation';
                    }

                    if ($isApartel) {
                        $reservationListForCancel = $reservationIdentificatorDao->getReservationsByChannelResId($channelResId);
                        foreach ($reservationListForCancel as $forCancel) {
                            $result = $this->initCancelation($res, $cancelationType, $forCancel, $productId, $isApartel);
                        }
                    } else {
                        $bookingDomain = $bookingDao->getResIdByChannel($channelResId, 'Cubilis');
                        $result = $this->initCancelation($res, $cancelationType, $bookingDomain, $productId, $isApartel);
                    }
                } else {
                    // Undefined State of Reservation
                    $result = $this->initDefault($res, $productId);
                    $reservationTypeText = 'Undefined Reservation';
                }

                if ($result) {
                    $channelResIdList[] = $this->getChannelResId($res);
                    $this->gr2info('Reservation successful end', $logArray);
                } else {
                    $this->gr2err("Reservation handling is not correct", [
                        'reservation_type'   => $reservationTypeText,
                        'channel_res_id'     => $channelResId,
                        'reservation_status' => $res->status
                    ]);
                }
            }
        }

        return $channelResIdList;
    }

    /**
     * @param $res
     * @param $roomStay
     * @param $channelResId
     * @param $bookingDomain
     * @param $productId
     * @param $assumption
     * @param $isApartel
     * @param bool $building
     * @param array $apartmentListUsed
     * @param bool $notCheckChannelResId
     * @return array|bool
     */
    private function conquerMyTrustCubilis($res, $roomStay, $channelResId, $bookingDomain, $productId, $assumption, $isApartel, $building = false, $apartmentListUsed = [], $notCheckChannelResId = false)
    {
        $options = [
            'overbooking' => false,
            'res' => false,
            'reservationType' => false,
            'apartmentId' => false,
            'identificator' => false,
        ];

        // check channel res id
        if ($channelResId) {

            $logArray = [
                'cron' => 'ChannelManager',
                'channel_res_id' => $channelResId,
                'product_id' => $productId,
                'product_type' => $isApartel ? 'Apartel' : 'Apartment',
            ];

            if ($assumption == 'reservation') {
                $this->gr2info('It is new reservation', $logArray);
                // check has reservation this channel res id
                if (!$notCheckChannelResId) {
                    /**
                     * @var \DDD\Dao\Booking\Booking $bookingDao
                     */
                    $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
                    $bookingDao->setEntity(new \DDD\Domain\Booking\ResId());

                    $checkAlreadyHas = $bookingDao->getBookingTicketByChannel($channelResId, 'cubilis');
                    if ($checkAlreadyHas) {
                        $this->gr2err("Duplicate new reservation", $logArray);
                        return false;
                    }
                }
            } else {
                $this->gr2info('It is modification', $logArray);
                // Keep Reservation
                $options['res'] = $bookingDomain;
                if ($bookingDomain) {
                    if ($bookingDomain->getStatus() != Booking::BOOKING_STATUS_BOOKED) {
                        // Canceled
                        $this->gr2info('We already have this cancellation.', $logArray);
                        return false;
                    }
                } else {
                    $msg = "We do not have this channel reservation ID #{$channelResId} so this reservation will be registered as overbooking, Product ID: {$productId}";
                    $this->gr2crit($msg, $logArray);

                    $assumption = 'reservation';
                    $options['overbooking'] = true;
                }
            }

            $productRoomAndRate = $this->getCompleteDataForReservation($res, $bookingDomain, $roomStay, $productId, $channelResId, $isApartel, $building, $apartmentListUsed);
            if ($productRoomAndRate) {
                if ($isApartel) {
                    $apartmentId = isset($productRoomAndRate['apartel']['apartment_id']) ? $productRoomAndRate['apartel']['apartment_id'] : 0;
                } else {
                    $apartmentId = $productId;
                }

                $productGeneralDao = $this->getProductGeneralDao();
                $productGeneralDomain = $productGeneralDao->getAccById($apartmentId);

                if (!$productGeneralDomain) {
                    $this->gr2err("I'm just logging this imposable situation", $logArray);
                    return false;
                }

                if ($productRoomAndRate['overbooking']) {
                    $options['overbooking'] = true;
                }
            } else {
                return false;
            }

            $options['reservationType'] = $assumption;
            $options['productGeneralDomain'] = $productGeneralDomain;
            $options['productRoomAndRate'] = $productRoomAndRate;
            $options['apartmentId'] = $apartmentId;
            $options['identificator'] = $roomStay['identificator'];

            return $options;
        }

        return false;
    }

    /**
     * @param $res
     * @param $productId
     * @param $options
     * @param $roomStay
     * @param $isApartel
     * @return mixed
     */
    private function initReservationWithType($res, $productId, $options, $roomStay, $isApartel)
    {
        if (!$options) {
            return false;
        }
        $method = 'init' . ucfirst($options['reservationType']);
        return $this->$method($res, $productId, $options, $roomStay, $isApartel);
    }

    /**
     * @param $res
     * @param $isApartel
     * @return array
     */
    private function getRoomStayList($res, $isApartel)
    {
        $roomStayList = $res->getRoomStay();
        $roomStayListLength = $roomStayList->getLength();
        $channelResId = $this->getChannelResId($res);
        $correctRoomStay = [];
        $rateId = '';
        // not roomStay
        if (!$roomStayListLength) {
            $this->gr2err("Reservation without RoomStay element inside is not a Reservation", [
                'cron' => 'ChannelManager',
            ]);

            return $correctRoomStay;
        }

        foreach ($roomStayList->getItems() as $key => $roomStay) {
            if (strtolower($roomStay->status) == self::CANCELLED_TEXT) {
                continue;
            }

            // get room type
            $cubilisRoomId = null;
            $roomTypeList = $roomStay->getRoomType();
            $roomTypeListLength = $roomTypeList->getLength();
            if ($roomTypeListLength) {
                foreach ($roomTypeList->getItems() as $roomType) {
                    if (!is_null($roomType->roomId)) {
                        $cubilisRoomId = $roomType->roomId;
                        break;
                    }
                }
            }

            // if not room id
            if (is_null($cubilisRoomId)) {
                $this->gr2err("RoomType without RoomId attribute inside is not a RoomType", [
                    'cron'              => 'ChannelManager',
                    'channel_res_id'    => $channelResId
                ]);
                break;
            }
            $correctRoomStay[$key]['roomTypeId'] = $cubilisRoomId;

            // get rate
            $ratePlan = $roomStay->getRatePlan();
            if (!$ratePlan->getLength()) {
                $this->gr2err("RoomStay without RatePlan element inside is not a RoomStay", [
                    'cron'              => 'ChannelManager',
                    'channel_res_id'    => $channelResId
                ]);
                break;
            }

            $dateFrom = $dateTo = '';
            foreach ($ratePlan->getItems() as $rateRow) {
                if (!isset($correctRoomStay[$key]['rateId'])) {
                    $correctRoomStay[$key]['rateId'] = $rateRow->id;
                }

                $date = $rateRow->effectiveDate;
                if (!$dateFrom && !$dateTo) {
                    $dateFrom = $date;
                    $dateTo = $date;
                }
                $dateFrom =  $date < $dateFrom ?  $date : $dateFrom;
                $dateTo = $date > $dateTo ? $date : $dateTo;
                if (!$rateId) {
                    $rateId = $rateRow->id;
                }
                $correctRoomStay[$key]['rates'][] = [
                    'rateId' => $rateRow->id,
                    'date' => $date,
                    'name' => $rateRow->name,
                    'price' => $rateRow->amount,
                    'currency' => $rateRow->currency,
                    'isTaxInclusive' => $rateRow->isTaxInclusive,
                ];
            }

            // reservation date
            $correctRoomStay[$key]['dateFrom'] = $dateFrom;
            $dateTo = date('Y-m-d', strtotime($dateTo . ' +1 days'));
            $correctRoomStay[$key]['dateTo'] = $dateTo;

            // get comment and guest name
            $comment = $guestName = '';
            if ($roomStay->getComment()->getLength()) {
                foreach ($roomStay->getComment()->getItems() as $roomStayComment) {
                    $comment .= $roomStayComment->text;
                }
            }

            $correctRoomStay[$key]['comment'] = $comment;

            preg_match("/guest name:(.*)(\n|<)/Ui", $comment, $guestNameReg);
            if (isset($guestNameReg[1])) {
                $guestName = $guestNameReg[1];
            }
            $correctRoomStay[$key]['guestName'] = trim($guestName);

            // occupancy
            $occupancy = 0;
            if ($roomStay->getGuestCount()->getLength()) {
                foreach ($roomStay->getGuestCount()->getItems() as $guestCountItem) {
                    $occupancy += $guestCountItem->count;
                }
            }
            $correctRoomStay[$key]['occupancy'] = $occupancy;

            $correctRoomStay[$key]['totalPrice'] = $roomStay->totalAmountAfterTax;
            $correctRoomStay[$key]['currency'] = $roomStay->currency;

            // identificator
            $correctRoomStay[$key]['identificator'] = [
                'channel_res_id' => $channelResId,
                'roomId' => $cubilisRoomId,
                'rateId' => $rateId,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'guestName' => $guestName,
            ];

            // if apartment reservation
            if (!$isApartel) {
                return $correctRoomStay;
            }
        }

        return $correctRoomStay;
    }

    /**
     * @param $res
     * @param $productId
     * @param $options
     * @param $roomStay
     * @param $isApartel
     * @return bool
     */
    private function initReservation($res, $productId, $options, $roomStay, $isApartel)
    {
        /**
         * @var ProductRoomCubilisDomain $roomDomain
         * @var ProductRateCubilisDomain $rateDomain
         * @var Main $reservationService
         */
        $productRoomAndRate = $options['productRoomAndRate'];
        $productGeneralDomain = $options['productGeneralDomain'];
        $reservationService = $this->getServiceLocator()->get('service_reservation_main');

        $roomDomain = $productRoomAndRate['productTypeDomain'];
        $ratesData = $productRoomAndRate['ratesData'];
        $apartelData = $productRoomAndRate['apartel'];
        $otherInfo = [];
        $data = $this->prepareBookingTicketData($res, $roomStay, $roomDomain, $productGeneralDomain, false, $apartelData);
        // Register as Overbooking
        if ($options['overbooking'] || empty($ratesData)) {
            $this->markAsOverbooking($data);
            $this->gr2emerg("Reservation registered as an overbooking", [
                'cron' => 'ChannelManager',
                'apartment_id' => $productId,
                'reservation_number' => $data['res_number']
            ]);
        }

        $otherInfo['cc_provided'] = false;
        // Save Reservation
        if ($res->getInfo()->getPaymentCard()->getLength()) {
            $data['customer_data']['source'] = Card::CC_SOURCE_CHANNEL_RESERVATION_SYSTEM;
            $otherInfo['cc_provided'] = true;
        }

        $newAvailability = 0;
        $otherInfo['availability'] = $newAvailability;
        $otherInfo['ratesData'] = $ratesData;
        // set apartel data
        $otherInfo['apartel'] = $apartelData;

        // set identificator
        if ($isApartel) {
            $otherInfo['identificator'] = $options['identificator'];
        }

        // if missing rate create task
        if (isset($productRoomAndRate['rateMissingTask'])) {
            $otherInfo['rateMissingTask'] = true;
        }

        $reservationId = $reservationService->registerReservation($data, $otherInfo, true);

        return true;

    }

    /**
     * @param $res
     * @param $productId
     * @param $options
     * @param $roomStay
     * @param $isApartel
     * @return bool
     */
    private function initModification($res, $productId, $options, $roomStay, $isApartel)
    {
        /**
         * @var ProductRoomCubilisDomain $roomDomain
         * @var ProductRateCubilisDomain $rateDomain
         * @var ResId $bookingDomain
         * @var Main $reservationService
         */
        $reservationService = $this->getServiceLocator()->get('service_reservation_main');

        $productRoomAndRate = $options['productRoomAndRate'];
        $productGeneralDomain = $options['productGeneralDomain'];

        $roomDomain = $productRoomAndRate['productTypeDomain'];
        $ratesData = $productRoomAndRate['ratesData'];
        $apartelData = $productRoomAndRate['apartel'];
        $otherInfo = [];
        $bookingDomain = $options['res'];
        $data = $this->prepareBookingTicketData($res, $roomStay, $roomDomain, $productGeneralDomain, $bookingDomain, $apartelData);
        if (isset($data['send_payment_modify'])) {
            $otherInfo['send_payment_modify'] = true;
        }

        // Cleanup unnecessary data
        $this->cleanupPreparedDataForModification($data);

        // Update Reservation
        if ($res->getInfo()->getPaymentCard()->getLength()) {
            $data['customer_data']['source'] = Card::CC_SOURCE_CHANNEL_MODIFICATION_SYSTEM;
            $otherInfo['cc_provided'] = true;
        } else {
            $otherInfo['cc_provided'] = false;
        }
        $otherInfo['apartment_id'] = $productId;
        $otherInfo['ratesData'] = $ratesData;

        // set overbooking status
        $isOverbooking = false;
        if ($options['overbooking']) {
            $isOverbooking = true;
            $this->markAsOverbooking($data);
            $this->gr2emerg("Reservation registered as an overbooking", [
                'cron' => 'ChannelManager',
                'apartment_id' => $productId
            ]);
        }

        // set apartel data
        $otherInfo['apartel'] = $apartelData;

        // set identificator
        if ($isApartel) {
            $otherInfo['identificator'] = $options['identificator'];
        }

        // if missing rate create task
        if (isset($productRoomAndRate['rateMissingTask'])) {
            $otherInfo['rateMissingTask'] = true;
        }

        return $reservationService->modifyReservation($data, $bookingDomain, $otherInfo, $isOverbooking);

    }

    /**
     * @param $res
     * @param int $canceledBy
     * @param $bookingDomain
     * @param $productId
     * @param $isApartel
     * @param bool $identificatorChecker
     * @return bool
     */
    private function initCancelation($res, $canceledBy = Booking::BOOKING_STATUS_CANCELLED_PENDING, $bookingDomain, $productId, $isApartel, $identificatorChecker = false)
    {
        $channelResId = $this->getChannelResId($res);
        if (!$channelResId) {
            // Other Strategy to detect the Reservation and return reservation number
            $this->gr2emerg("Missing channel res id. Trying to get reservation id via alternative strategy", [
                'cron' => 'ChannelManager',
            ]);
            return false;
        }

        $product = $isApartel ? 'Apartel' : 'Apartment';
        $logArray = [
            'cron' => 'ChannelManager',
            'channel_res_id' => $channelResId,
            'product_id' => $productId,
            'product_type' => $product,
        ];

        if ($bookingDomain) {
            $resNumber = $bookingDomain->getResNumber();

            if ($bookingDomain->getStatus() == Booking::BOOKING_STATUS_BOOKED) {
                $push = false;
                $pleaseSendEmail = true;
                $canceledBy = (
                $bookingDomain->getFundsConfirmed() == ReservationTicketService::CC_STATUS_INVALID
                    ? Booking::BOOKING_STATUS_CANCELLED_INVALID
                    : $canceledBy
                );

                // delete identificator
                if ($isApartel) {
                    /** @var \DDD\Dao\ChannelManager\ReservationIdentificator $daoReservationIdentificator */
                    $daoReservationIdentificator = $this->getServiceLocator()->get('dao_channel_manager_reservation_identificator');
                    if ($identificatorChecker) {
                        $daoReservationIdentificator->delete(['reservation_id' => $bookingDomain->getId()]);
                    } else {
                        $daoReservationIdentificator->delete(['channel_res_id' => $channelResId]);
                    }
                }

                /**
                 * @var \DDD\Service\Apartment\Inventory $inventoryService
                 */
                $inventoryService = $this->getServiceLocator()->get('service_apartment_inventory');

                $inventoryService->processCancellation($resNumber, $push, $pleaseSendEmail, $canceledBy);
            } else {
                $errorMessage = 'Cannot cancel already canceled reservation.';
                $this->gr2notice($errorMessage, $logArray + ['reservation_number' => $resNumber]);

                // Save part of data in db to resolve it later via UD Widget
                $this->logDuplicateCancellation($bookingDomain);
                return false;
            }

            return true;
        }  else {
            $this->gr2emerg('We do not have this channel reservation ID', $logArray);
        }

        return false;
    }

    /**
     * @param \DDD\Domain\Booking\ChannelReservation|null $bookingDomain
     */
    private function logDuplicateCancellation($bookingDomain)
    {
          $msg =
              'Reservation Number: ' . $bookingDomain->getResNumber() .
              ', Cancellation Date: '  . $bookingDomain->getDateFrom() .
              ', Detected Date: '      . date('Y-m-d H:i:s') .
              ', Detected Status: '    . BookingService::$bookingStatuses[$bookingDomain->getStatus()];
          $logger = new ALogger($this->getServiceLocator());
          $logger->save(ALogger::MODULE_BOOKING, $bookingDomain->getId(), ALogger::ACTION_DUPLICATE_CANCELLATION, $msg);
    }

    /**
     * Abnormal status for reservation. For example "pending".
     *
     * @param ReservationItem $res
     * @param int $productId
     * @return bool
     */
    private function initDefault($res, $productId)
    {
        $this->gr2err("Cannot handle reservation", [
            'cron'                  => 'ChannelManager',
            'reservation_status'    => $res->status,
            'product_id'            => $productId
        ]);

        return false;
    }

    /**
     * @param $res
     * @param $bookingDomain
     * @param $roomStay
     * @param $apartmentId
     * @param $channelResId
     * @param $isApartel
     * @param $building
     * @param $apartmentListUsed
     * @return array
     */
    private function getCompleteDataForReservation($res, $bookingDomain, $roomStay, $apartmentId, $channelResId, $isApartel, $building, $apartmentListUsed)
    {
        /**
         * @var \DDD\Dao\Apartel\Type $apartelTypeDao
         * @var Main $reservationService
         */

        $reservationService     = $this->getServiceLocator()->get('service_reservation_main');

        // Cubilis room
        $cubilisRoomId = $roomStay['roomTypeId'];
        if ($isApartel) {
            $apartelTypeDao = $this->getServiceLocator()->get('dao_apartel_type');
            $roomTypeDomain = $apartelTypeDao->getRoomTypeByCubilisRoomId($cubilisRoomId);
        } else {
            $roomDao = $this->getRoomDao();
            $roomTypeDomain = $roomDao->getRoomByCubilisRoomId($cubilisRoomId);
        }

        if (!$roomTypeDomain) {
            $this->gr2err("Cannot found room", [
                'cron'           => 'ChannelManager',
                'channel_res_id' => $channelResId,
            ]);
            return false;
        }

        // Date
        $dates['date_from'] = $roomStay['dateFrom'];
        $dates['date_to']   = $roomStay['dateTo'];
        $cubilisRateIdDates = '';
        $loop = 0;
        $fromCubilisRatesData = [];

        // Rate plan
        foreach ($roomStay['rates'] as $rate) {
            $cubilisRateId = $rate['rateId'];
            $dateRate = $rate['date'];
            $nameRate = $rate['name'];
            $priceRate = $rate['price'];
            $currencyRate = $rate['currency'];
            $isTaxInclusive = $rate['isTaxInclusive'];

            $fromCubilisRatesData[$dateRate] = [
                'price' => $priceRate,
                'rate_name' => $nameRate,
                'currency' => $currencyRate,
                'channel_rate_id' => $cubilisRateId,
                'is_tax_inclusive' => $isTaxInclusive
            ];

            $cubilisRateIdDates .= ($loop ? ',' : '') . "({$cubilisRateId}, '{$dateRate}')";
            $loop++;
        }

        // Detect Partner
        $uniqueIdList = $res->getUniqueId();

        $partnerId = false;
        if ($uniqueIdList->getLength()) {
            $partnerId = $uniqueIdList->getItems()[0]->id;
        }

        // get guest count
        $guestCount = isset($roomStay['occupancy']) ? $roomStay['occupancy'] : 0;

        // get room rate data
        $ratesData  = $reservationService->getNightlyData(
            $cubilisRateIdDates,
            $roomTypeDomain->getId(),
            $dates,
            $bookingDomain,
            $fromCubilisRatesData,
            $apartmentId,
            $channelResId,
            $partnerId,
            $isApartel,
            $building,
            $apartmentListUsed,
            $guestCount
        );

        $responseData = [
            'productTypeDomain' => $roomTypeDomain,
            'ratesData' => $ratesData['ratesData'],
            'overbooking' => $ratesData['overbooking'],
            'apartel' => $ratesData['apartel'],
        ];

        if (isset($ratesData['rateMissingTask'])) {
            $responseData['rateMissingTask'] = true;
        }

        return $responseData;
    }

    /**
     * @param $res
     * @param $roomStay
     * @param $roomDomain
     * @param $accDomain
     * @param bool|\DDD\Domain\Booking\ChannelReservation $bookingDomain
     * @param $apartelData
     * @return array
     */
    private function prepareBookingTicketData($res, $roomStay, $roomDomain, $accDomain, $bookingDomain = false, $apartelData)
    {
        /**
         * @var BookingTicket $bookingTicketService
         * @var ApartmentGroup $apartmentGroupService
         * @var Location $locationService
         * @var \DDD\Service\Partners $partnerService
         * @var \DDD\Service\Reservation\PartnerSpecific $partnerSpecificService
         */
        $penaltyService         = $this->getServiceLocator()->get('service_penalty_calculation');
        $bookingTicketService   = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $apartmentGroupService  = $this->getServiceLocator()->get('service_apartment_group');
        $partnerService         = $this->getServiceLocator()->get('service_partners');
        $partnerSpecificService = $this->getServiceLocator()->get('service_reservation_partner_specific');

        $output = [];
        $generatedResNumber = $bookingTicketService->generateResNumber();

        /** @todo: Collect all not used data from xml and write as a comment (or remarks). */
        $bookingComment = [];

        // Customer Related Info
        $info  = $res->getInfo();
        $dates = $this->getDates($res);

        $output['date_from'] = $dates['date_from'];
        $output['date_to']   = $dates['date_to'];

        // get date from rate
        if (isset($roomStay['dateFrom']) && isset($roomStay['dateTo'])) {
            $output['date_from'] = $roomStay['dateFrom'];
            $output['date_to']   = $roomStay['dateTo'];
        }

        $output['guest_arrival_time'] = $dates['guest_arrival_time'];

        if ($output['guest_arrival_time'] === '00:00:00') {
            unset($output['guest_arrival_time']);
        }

        // Room Stay List
        $roomStayList = $res->getRoomStay();

        // Detect Partner
        $uniqueIdList = $res->getUniqueId();

        // Remarks
        $comments = $info->getComment();
        $remarks = $commentForPars = '';

        if ($comments->getLength()) {
            foreach ($comments->getItems() as $comment) {
                $commentForPars = $comment->text;
                $remarks .= "{$comment->text} <br><br>\n\n";
            }
        }

        if (isset($roomStay['comment'])) {
            $remarks .= $roomStay['comment'];
        }

        $output['remarks'] = trim($remarks);

        // init strategy to get Partner Id if one was not registered in our system
        $ginosiPartnerId = $partnerService::PARTNER_UNKNOWN;

        if ($uniqueIdList->getLength()) {
            $partnerId = $uniqueIdList->getItems()[0]->id;

            if (!is_null($partnerId)) {
                $output['channel_partner_id'] = $partnerId;
                $changedPartnerId = $partnerSpecificService->changePartnerForSomeCases($partnerId, $accDomain->getId());
                $partnerId        = ($changedPartnerId) ? $changedPartnerId : $partnerId;
                $isOurPartnerId   = ($changedPartnerId) ? true : false;

                $partnerData                  = $partnerService->getPartnerDataForReservation($partnerId, $accDomain->getId(), $isOurPartnerId, $commentForPars);
                $commission                   = $partnerData->getCommission();
                $ginosiPartnerId              = $partnerData->getGid();
                $partnerBusinessModel         = $partnerData->getBusinessModel();
                $partnerName                  = $partnerData->getPartnerName();
                $output['partner_commission'] = $commission;
                $output['partner_id']         = $ginosiPartnerId;
                $output['partner_name']       = $partnerName;
                $output['model']              = $partnerBusinessModel;
            } else {
                $this->gr2emerg("Partner Id is missed from XML", [
                    'cron' => 'ChannelManager',
                ]);
            }
        } else {
            $this->gr2emerg('Unique Id element is missing', [
                'cron' => 'ChannelManager',
            ]);
        }

        if (!isset($output['partner_commission'])) {
            $output['partner_commission'] = $partnerService::PARTNER_UNKNOWN_COMMISSION;
            $this->gr2emerg('Partner Commission not defined', [
                'cron' => 'ChannelManager',
            ]);
        }

        if (!isset($output['partner_id'])) {
            $output['partner_id'] = $partnerService::PARTNER_UNKNOWN;
            $this->gr2emerg('Affiliate ID not defined', [
                'cron' => 'ChannelManager',
                'partner_id' => $output['partner_id'],
            ]);
        }

        // get Total Price
        $totalPrice = 0;
        if (isset($roomStay['totalPrice'])) {
            $totalPrice = $roomStay['totalPrice'];
        }

        // get currency
        $currency = '';
        if (isset($roomStay['currency'])) {
            $currency = $roomStay['currency'];
        }

        $guestCount = 0;
        if (isset($roomStay['occupancy'])) {
            $guestCount = $roomStay['occupancy'];
        }

        if (!is_null($totalPrice) && !is_null($currency)) {
            $output['booker_price'] = $totalPrice;
            $output['guest_currency_code'] = $currency;
        } else {
            $this->gr2emerg("Trying to fetch amount and currency via alternative strategy", [
                'cron' => 'ChannelManager',
            ]);
            $price = $this->getDateAndPriceFromRate($res);

            if ($price) {
                $totalPrice = $price['total_amount'];
                $currency = $price['currency'];
            } else {
                $totalPrice = '';
                $currency = '';
                $this->gr2err('Cannot get total amount and currency', [
                    'cron' => 'ChannelManager',
                ]);
            }
        }

        $output['booker_price'] = $totalPrice;
        $output['guest_currency_code'] = $currency;

        // Timestamp
        $timestamp = $res->creationDatetime;

        if (!is_null($timestamp)) {
            $output['timestamp'] = $timestamp;
        } else {
            // init Strategy to get timestamp
            $output['timestamp'] = $this->getTimestamp();

            $this->gr2emerg('Reservation Timestamp is missed from XML', [
                'cron' => 'ChannelManager',
            ]);
        }

        // Channel Reservation Id
        $channelResId = $this->getChannelResId($res);

        if (!is_null($channelResId)) {
            $output['channel_res_id'] = $channelResId;
        } else {
            $this->gr2emerg('Channel Reservation Id is missing from XML', [
                'cron'         => 'ChannelManager',
                'full_message' => 'Channel Reservation Id is missing from XML. It is important because cancellation is made by that id.'
            ]);
        }

        // Affiliate Reference
        $reservationId = $info->getReservationId();

        if ($reservationId->getLength() && !is_null($reservationId->getItems()[0]->source)) {
            $affiliateRef = $reservationId->getItems()[0]->source;
        } else {
            $affiliateRef = $this->getAffiliateReferenceIfOneIsMissing();
            $this->gr2info('Using alternative strategy to get affiliate reference.', [
                'cron' => 'ChannelManager',
            ]);
        }

        $output['partner_ref'] = $affiliateRef;


        // Customer Info
        $profiles = $info->getProfile();

        if ($profiles->getLength()) {
            $customers = $profiles->getItems()[0]->getCustomer();

            if ($customers->getLength()) {
                $customer = $customers->getItems()[0];

                // Language
                if (!is_null($customer->language)) {
                    $output['guest_language_iso'] = $customer->language;
                } else {
                    $this->gr2err("Customer's language is missing from XML", [
                        'cron' => 'ChannelManager',
                    ]);
                }

                // First Name
                if (!is_null($customer->name)) {
                    $output['guest_first_name'] = $customer->name;
                } else {
                    $this->gr2err("Customer's language is missing from XML", [
                        'cron' => 'ChannelManager',
                    ]);
                }

                // Last Name
                if (!is_null($customer->surname)) {
                    $output['guest_last_name'] = $customer->surname;
                } else {
                    $this->gr2err("Customer's last name is missing from XML", [
                        'cron' => 'ChannelManager',
                    ]);
                }

                // Telephone
                if (!is_null($customer->phone)) {
                    $output['guest_phone'] = $customer->phone;
                } else {
                    $this->gr2err("Customer's telephone number is missing from XML", [
                        'cron' => 'ChannelManager',
                    ]);
                }

                if (!is_null($customer->email)) {
                    $output['guest_email'] = $customer->email;
                } else {
                    $this->gr2err("Customer's email is missing from XML", [
                        'cron' => 'ChannelManager',
                    ]);
                }

                // Address
                if (!is_null($customer->address)) {
                    $output['guest_address'] = $customer->address;
                } else {
                    $this->gr2err("Customer's address is missing from XML", [
                        'cron' => 'ChannelManager',
                    ]);
                }

                // Country
                if (!is_null($customer->country)) {
                    $locationService = $this->getServiceLocator()->get('service_location');
                    $countryCode = $locationService->getCountryIdByISOCode($customer->country);

                    if ($countryCode === false) {
                        $countryCode = null;
                    }

                    $output['guest_country_id'] = $countryCode;
                } else {
                    $output['guest_country_id'] = null;

                    $this->gr2err("Customer's country code is missing from XML", [
                        'cron' => 'ChannelManager',
                    ]);
                }

                // City
                if (!is_null($customer->city)) {
                    $output['guest_city_name'] = $customer->city;
                } else {
                    $this->gr2err("Customer's city is missing from XML", [
                        'cron' => 'ChannelManager',
                    ]);
                }

                // Postal Code
                if (!is_null($customer->postalCode)) {
                    $output['guest_zip_code'] = $customer->postalCode;
                } else {
                    $this->gr2err("Customer's zip code is missing from XML", [
                        'cron' => 'ChannelManager',
                    ]);
                }
            } else {
                $this->gr2err("Element Customer is missing from XML", [
                    'cron' => 'ChannelManager',
                ]);
            }
        } else {
            $this->gr2err("Element Profile is missing from XML", [
                'cron' => 'ChannelManager',
            ]);
        }

        // Reservation Number
        $output['res_number'] = $generatedResNumber;

        // Confirm With Supply
        $output['ki_viewed'] = 0;

        // Guest Count
        $output['occupancy'] = $guestCount;

        // Channel Name
        $output['channel_name'] = 'Cubilis';

        // Customer IP
        $output['ip_address'] = $res->customerIP;

        // Credit Card Info
        $paymentCards = $info->getPaymentCard();

        if ($paymentCards->getLength()) {
            $paymentCard = $paymentCards->getItems()[0];
            $customerData['cc_provided'] = true;

            // Card Number
            $cardNumber = $paymentCard->cardNumber;
            $customerData['number'] = $cardNumber;

            if (is_null($cardNumber)) {
                $this->gr2err("Card number is missing from XML", [
                    'cron' => 'ChannelManager',
                ]);
            }

            // Card Holder Name
            $cardHolderName = $paymentCard->cardHolderName;
            $customerData['holder'] = $cardHolderName;

            if (is_null($cardHolderName)) {
                $this->gr2err("Card holder name is missing from XML", [
                    'cron' => 'ChannelManager',
                ]);
            }

            // Card Expiration Date
            $cardExp = $paymentCard->expireDate;
            $customerData['year']  = $customerData['month'] = false;
            if (!is_null($cardExp)) {
                $customerData['month'] = substr($cardExp, 0, 2);
                $customerData['year'] = substr($cardExp, 2, 2);
            } else {
                $this->gr2err("Expiration date is missing from XML", [
                    'cron' => 'ChannelManager',
                ]);
            }

            // Card CVC Code
            $cardCVC = $paymentCard->seriesCode;
            $customerData['cvc'] = $cardCVC;

            if (is_null($cardCVC)) {
                $this->gr2err("CVC number is missing from XML", [
                    'cron' => 'ChannelManager',
                ]);
            }
        } else {
            $customerData['cc_provided'] = false;
            $this->gr2err("Payment card information is missing from XML", [
                'cron' => 'ChannelManager',
            ]);
        }

        $customerData['email']           = (isset($output['email']) && $output['email']) ? $output['email'] : null;
        $customerData['partner_id']      = $ginosiPartnerId;
        $customerData['source']          = Card::CC_SOURCE_CHANNEL_RESERVATION_SYSTEM;

        $output['customer_data']         = $customerData;

        // Room Information
        $output['room_id'] = $roomDomain->getId();

        // Accommodation Info Repetition
        $output['apartment_id_assigned'] = $accDomain->getId();
        $output['apartment_id_origin'] = $accDomain->getId();
        $output['apartment_currency_code'] = $accDomain->getCurrencyCode();
        $output['acc_name'] = $accDomain->getName();
        $output['acc_country_id'] = $accDomain->getCountryId();
        $output['acc_province_id'] = $accDomain->getProvinceId();
        $output['acc_province_name'] = $accDomain->getProvinceName();
        $output['acc_city_id'] = $accDomain->getCityId();
        $output['acc_city_name'] = $accDomain->getCityName();
        $output['acc_address'] = $accDomain->getAddress();
        $output['building_name'] = $apartmentGroupService->getBuildingName($accDomain->getId());

        if (isset($apartelData['apartel_id'])) {
            /** @var \DDD\Dao\Apartel\General $apartelDao */
            $apartelDao = $this->getServiceLocator()->get('dao_apartel_general');
            $apartmentGroup = $apartelDao->getApartmentGroup($apartelData['apartel_id']);
            $output['apartel_id'] = $apartmentGroup['apartment_group_id'];
        }

        /** @var \DDD\Service\Currency\Currency $currencyService */
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');

        // Currency Rate
        $output['currency_rate']     = $currencyService->getCurrencyConversionRate($accDomain->getCurrencyCode(), $currency);
        $output['currency_rate_usd'] = $currencyService->getCurrencyConversionRate('USD', $accDomain->getCurrencyCode());

        // Review Page Hash
        $output['review_page_hash'] = $bookingTicketService->generatePageHash($output['res_number'], $output['apartment_id_origin']);

        // KI Page Hash
        $output['ki_page_hash'] = $bookingTicketService->generatePageHash($output['res_number'], $output['timestamp']);

        return $output;
    }

    /**
     * @param ReservationItem $res
     * @return array
     */
    private function getRateCollection($res)
    {
        $collection = [];
        $roomStays = $res->getRoomStay();

        if ($roomStays->getLength()) {
            foreach ($roomStays->getItems() as $roomStayItem) {
                $ratePlans = $roomStayItem->getRatePlan();

                if ($ratePlans->getLength()) {
                    foreach ($ratePlans->getItems() as $ratePlanItem) {
                        $collection[] = [
                            'id' => $ratePlanItem->id,
                            'date' => $ratePlanItem->effectiveDate,
                        ];
                    }
                }
            }
        }

        return $collection;
    }

    /**
     * @param array $collection
     * @return bool
     */
    private function haveDifferentRates(array $collection)
    {
        if (count($collection)) {
            $storage = [];

            foreach ($collection as $item) {
                $storage[$item['id']] = $item['date'];
            }

            if (count($storage) > 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Strategy to get <b>From Date</b>, <b>To Date</b>, <b>Total Amount</b> and <b>Currency</b> from Rate Plan information.
     * Method should return false if something fail and an array if all went right.
     *
     * @param ReservationItem $res
     * @return array|bool
     * <pre>
     * array(
     *   'date_from' => $dateFrom,
     *   'date_to' => $dateTo,
     *   'total_amount' => $totalAmount,
     *   'currency' => $currency,
     * )
     * </pre>
     */
    private function getDateAndPriceFromRate($res)
    {
        $dateFrom = '';
        $dateTo = '';
        $totalAmount = 0;
        $currency = null;

        foreach ($res->getRoomStay()->getItems() as $roomStay) {
            foreach ($roomStay->getRatePlan()->getItems() as $ratePlan) {
                if (!is_null($ratePlan->effectiveDate)) {
                    if (!$ratePlan->getDetail()->getLength()) {
                        return false;
                    }
                } else {
                    return false;
                }

                if (empty($dateFrom) && empty($dateTo)) {
                    $dateFrom = $ratePlan->effectiveDate;
                    $dateTo = $ratePlan->effectiveDate;
                }

                $dateFrom = $ratePlan->effectiveDate < $dateFrom ? $ratePlan->effectiveDate : $dateFrom;
                $dateTo = $ratePlan->effectiveDate > $dateTo ? $ratePlan->effectiveDate : $dateTo;

                foreach ($ratePlan->getDetail()->getItems() as $ratePlanDetail) {
                    if (!is_null($ratePlanDetail->currency) && !is_null($currency)) {
                        $currency = $ratePlanDetail->currency;
                    }

                    if (is_null($ratePlanDetail->amount)) {
                        return false;
                    }

                    $totalAmount += $ratePlanDetail->amount;
                }
            }
        }

        $dateTo = date('Y-m-d', strtotime('+1 day', strtotime($dateTo)));

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'total_amount' => $totalAmount,
            'currency' => $currency,
        ];
    }

    /**
     * Get Start Date, End Date and Arrival Time via 2 Strategies.
     *
     * @param ReservationItem $res
     * @return array
     * <pre>
     * array(
     *    'date_from' => $dateFrom,
     *    'date_to' => $dateTo,
     *    'guest_arrival_time' => $arrivalTime,
     * )
     * </pre>
     */
    private function getDates($res)
    {
        $info = $res->getInfo();
        $output = [
            'date_from'          => '',
            'date_to'            => '',
            'guest_arrival_time' => '',
        ];

        // Date From and Arrival Time
        $dateFrom = $info->timeSpanStart;

        if (!is_null($dateFrom)) {
            list($output['date_from'], $output['guest_arrival_time']) = explode('T', $dateFrom);
        } else {
            $this->gr2emerg("DateFrom is missed. Trying alternative strategy to get it.", [
                'cron' => 'ChannelManager',
            ]);

            // Init some strategy to get From Date from rate plans. But in this case we are losing arrival time
            $dateFromAlter = $this->getDateAndPriceFromRate($res);

            if ($dateFromAlter) {
                $output['date_from'] = $dateFromAlter['date_from'];
            } else {
                $this->gr2warn("Date From is missed from booking ticket", [
                    'cron' => 'ChannelManager',
                ]);
            }
        }

        // Date To
        $dateTo = $info->timeSpanEnd;

        if (!is_null($dateTo)) {
            $output['date_to'] = $dateTo;
        } else {
            $this->gr2emerg("DateTo is missed. Trying alternative strategy to get it.", [
                'cron' => 'ChannelManager',
            ]);

            // Trying to use alternative strategy
            if (isset($dateFromAlter)) {
                $output['date_to'] = $dateFromAlter['to'];
            } else {
                $dateFromAlter = $this->getDateAndPriceFromRate($res);

                if ($dateFromAlter) {
                    $output['date_to'] = $dateFromAlter['date_to'];
                } else {
                    $this->gr2warn("Date To is missed from booking ticket", [
                        'cron' => 'ChannelManager',
                    ]);
                }
            }
        }

        return $output;
    }

    /**
     * There is data that doesn't need to be replaced. Eg. res_number.
     *
     * @param array $data
     */
    private function cleanupPreparedDataForModification(array &$data)
    {
        $fated = [
            'timestamp',
            'review_page_hash',
            'ki_page_hash',
            'ki_viewed',
            'channel_name',
            'res_number',
            'IP',
            'customer_id',
            'send_payment_modify',
            // Acc Details
            'room_id',
            'occupancy',
            'apartment_id_assigned',
            'apartment_id_origin',
            'commission',
            'apartment_currency_code',
            'acc_name',
            'acc_country_id',
            'acc_province_id',
            'acc_province_name',
            'acc_city_id',
            'acc_city_name',
            'acc_address',
            'model',
            // Currency, Price and Penalty related
            'currency_rate',
            'currency_rate_usd',
            //'price',
            //'penalty'
            //'rate_name',
        ];

        foreach ($data as $column => $value) {
            if (in_array($column, $fated)) {
                unset($data[$column]);
            }
        }
    }

    /**
     * Strategy to get Affiliate Reference if one is missing.
     *
     * @return string
     */
    private function getAffiliateReferenceIfOneIsMissing()
    {
        return '';
    }

    /**
     * Strategy to get Reservation Number by Unknown Fields.
     *
     * @param ReservationItem $res
     * @return string Reservation Id
     */
    private function getResByUnknownFields($res)
    {
        $this->gr2err("Cannot get reservation number by unknown field. It is abnormal situation and we need to think about.", [
            'cron' => 'ChannelManager',
        ]);

        return false;
    }

    /**
     * Send Confirmation to cubilis
     *
     * @param ChannelManagerLib $chm
     * @param int $productId
     * @param array $channelResIdList
     * @return bool
     */
    private function sendConfirmation(ChannelManagerLib $chm, $productId, array $channelResIdList)
    {
        try {
            $result = $chm->sendConfirmation([
                'apartment_id' => $productId,
                'data' => $this->transformChannelResIdList($channelResIdList),
            ]);

            if ($result->getStatus() == CivilResponder::STATUS_SUCCESS) {
                $this->gr2info("Confirmation successful!", [
                    'cron' => 'ChannelManager',
                ]);

                return true;
            } else {
                $this->gr2err("Cubilis confirmation about reservation is failed", [
                    'cron'            => 'ChannelManager',
                    'channel_message' => $result->getMessage()
                ]);
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, "Channel Manager: Confirmation mail wasn't send", [
                'apartment_id' => $productId
            ]);
        }

        return false;
    }

    /**
     * Get Identity Details from XML.
     *
     * @param ResInfo $info
     * @return array
     * <pre>
     * array(
     *    'guest_first_name' => $guestFirstName,
     *    'guest_last_name' => $guestLastName,
     *    'date_from' => $dateFrom,
     *    'date_to' => $dateTo,
     * )
     * </pre>
     */
    private function getBookingTicketIdentityDetails($info)
    {
        $this->gr2info("Trying to get booking ticket identity details", [
            'cron' => 'ChannelManager',
        ]);
        $output = [
            'guest_first_name' => '',
            'guest_last_name' => '',
            'date_from' => '',
            'date_to' => '',
        ];

        if (!is_null($info->timeSpanStart)) {
            list($output['date_from']) = explode('T', $info->timeSpanStart);
        }

        if (!is_null($info->timeSpanEnd)) {
            $output['date_to'] = $info->timeSpanEnd;
        }

        if ($info->getProfile()->getLength()) {
            foreach ($info->getProfile()->getItems() as $profile) {
                if ($profile->getCustomer()->getLength()) {
                    foreach ($profile->getCustomer()->getItems() as $customer) {
                        if (!is_null($customer->name)) {
                            $output['guest_first_name'] = $customer->name;
                        }

                        if (!is_null($customer->surname)) {
                            $output['guest_last_name'] = $customer->surname;
                        }
                    }
                }
            }
        }

        $this->gr2info("Identity details where status is bo", [
            'cron' => 'ChannelManager',
            'full_message' => json_encode($output)
        ]);

        return $output;
    }

    /**
     * Get Channel Reservation Id via 2 Strategies.
     *
     * @param ReservationItem $res
     * @return int|bool
     */
    private function getChannelResId($res)
    {
        if (!is_null($res->creatorId)) {
            return $res->creatorId;
        } else {
            $this->gr2info("Trying to get channel reservation id by alternative strategy", [
                'cron' => 'ChannelManager',
            ]);

            if ($res->getInfo()) {
                if ($res->getInfo()->getReservationId()->getLength()) {
                    foreach ($res->getInfo()->getReservationId()->getItems() as $resItem) {
                        if (!is_null($resItem->value)) {
                            return $resItem->value;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Transform simple array to CoC compliance format.
     *
     * @param array|int[] $resIdList
     * @return array
     */
    private function transformChannelResIdList(array $resIdList)
    {
        $output = [];

        if ($resIdList) {
            foreach ($resIdList as $resId) {
                $output[]['res_id'] = $resId;
            }
        }

        return $output;
    }

    /**
     * Any Possible statuses that Cubilis can return.
     *
     * @return array
     */
    private function getPossibleStatuses()
    {
        return [
            'reservation' => ['new', 'Reserved'],
            'cancelation' => ['cancelled', 'Cancelled'],
            'modification' => ['modified', 'Modify'],
            'request_denied' => ['deleted', 'Request denied'],

            // not used
            'waitlisted' => ['pending', 'Waitlisted'],
        ];
    }

    /**
     * Strategy to get timestamp.
     *
     * @return string
     */
    private function getTimestamp()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Mark Booking Ticket as Overbooking.
     *
     * @param array $data
     */
    private function markAsOverbooking(&$data)
    {
        $data['overbooking_status'] = ReservationTicketService::OVERBOOKING_STATUS_OVERBOOKED;
    }

    /**
     * @param $productId
     * @param bool $isApartel
     * @return array
     */
    public function isTestPassed($productId, $isApartel = false)
    {
        $test = new ConnectionTest($this->getServiceLocator());

        return $test->testFetchList($productId, $isApartel);
    }

    /**
     * Increase counter value
     */
    private function counterPlusPlus()
    {
        $this->counter++;
    }

    /**
     * @return bool
     */
    private function onLimit()
    {
        return ($this->counter >= self::ATTEMPTS);
    }

    /**
     * @param $message
     * @return bool
     */
    private function isTimeout($message)
    {
        return (strpos($message, 'timeout') !== false);
    }

    /**
     * Connected with {@link \DDD\Domain\Apartment\Inventory\RateAvailability Apartment\Inventory\RateAvailability} Domain.
     *
     * @param string $domain
     * @return Inventory
     */
    private function getInventoryDao($domain = 'DDD\Domain\Apartment\Inventory\RateAvailability')
    {
        return new Inventory($this->getServiceLocator(), $domain);
    }

    /**
     * Connected with {@link \DDD\Domain\Apartment\Details\Sync Apartment\Details\Sync} Domain.
     *
     * @param string $domain
     * @return Details
     */
    private function getDetailsDao($domain = 'DDD\Domain\Apartment\Details\Sync')
    {
        return new Details($this->getServiceLocator(), $domain);
    }

    /**
     * Connected with {@link \DDD\Domain\Apartment\Rate\CublistRate Apartment\Rate\CublistRate} Domain.
     *
     * @param string $domain
     * @return Rate
     */
    private function getRateDao($domain = 'DDD\Domain\Apartment\Rate\CublistRate')
    {
        return new Rate($this->getServiceLocator(), $domain);
    }

    /**
     * Connected with {@link \DDD\Domain\Apartment\Room\Cubilis Apartment\Room\Cubilis} Domain.
     *
     * @param string $domain
     * @return ProductRoomCubilisDao
     */
    private function getRoomDao($domain = 'DDD\Domain\Apartment\Room\Cubilis')
    {
        return new ProductRoomCubilisDao($this->getServiceLocator(), $domain);
    }

    /**
     * Connected with {@link \DDD\Domain\Accommodation\Accommodations Accommodation\Accommodations} Domain.
     *
     * @param string $domain
     * @return AccommodationDao
     */
    private function getProductGeneralDao($domain = 'DDD\Domain\Accommodation\Accommodations')
    {
        return new AccommodationDao($this->getServiceLocator(), $domain);
    }

    /**
     * Connected with {@link \DDD\Domain\Booking\Partner Booking\Partner} Domain.
     *
     * @param string $domain
     * @return BookingPartnerDao
     */
    private function getBookingPartnerDao($domain = 'DDD\Domain\Booking\Partner')
    {
        return new BookingPartnerDao($this->getServiceLocator(), $domain);
    }

    /**
     * Connected with {@link \DDD\Domain\Currency\Currency Currency\Currency} Domain.
     *
     * @param string $domain
     * @return CurrencyDao
     */
    private function getCurrencyDao($domain = 'DDD\Domain\Currency\Currency')
    {
        return new CurrencyDao($this->getServiceLocator(), $domain);
    }
}
