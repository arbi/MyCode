<?php

namespace Console\Controller;

use DDD\Service\Booking;
use DDD\Service\Fraud;
use Library\ActionLogger\Logger;
use Library\Controller\ConsoleBase;
use Library\Constants\EmailAliases;
use Library\Constants\DomainConstants;
use Library\Utility\Helper;
use Zend\Validator\EmailAddress;
use DDD\Service\Taxes;
use DDD\Service\Booking as ReservationService;
use Mailer\Service\Email as MailerService;
use Library\Constants\Constants;
use Library\Constants\TextConstants;

/**
 * Class ReservationEmailController
 * @package Console\Controller
 */
class ReservationEmailController extends ConsoleBase
{
    private $id         = false;
    private $bo_action  = false;
    private $email      = false;
    private $cc_provided = FALSE;
    private $cccaId;
    private $over           = false;
    private $ginosi         = false;
    private $booker         = false;
    private $dates_shifted  = false;


    /**
     * @return bool
     */
    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', 'check');

        if ($this->getRequest()->getParam('bo')) {
            $this->bo_action = true;
        }

        if ($this->getRequest()->getParam('id')) {
            $this->id = $this->getRequest()->getParam('id');
        }

        if ($this->getRequest()->getParam('email')) {
            $this->email = $this->getRequest()->getParam('email');
        }

        if ($this->getRequest()->getParam('ccp')
            && $this->getRequest()->getParam('ccp') !== '') {
            $this->cc_provided = $this->getRequest()->getParam('ccp');
        }

        if ($this->getRequest()->getParam('ccca_id')) {
            $this->cccaId = $this->getRequest()->getParam('ccca_id');
        }

        if ($this->getRequest()->getParam('overbooking')
            OR $this->getRequest()->getParam('o')) {
            $this->over = true;
        }

        if ($this->getRequest()->getParam('ginosi')) {
            $this->ginosi = true;
        }

        if ($this->getRequest()->getParam('booker')) {
            $this->booker = true;
        }

        if ($this->getRequest()->getParam('shifted')) {
            $this->dates_shifted = true;
        }

        switch ($action) {
            case 'send-ki':    $this->sendKIAction();
                break;
            case 'check-ki':   $this->checkKIAction();
                break;
            case 'send-ginosi': $this->sendGinosiAction();
                break;
            case 'send-guest': $this->sendGuestAction();
                break;
            case 'check-confirmation': $this->checkConfirmationAction();
                break;
            case 'send-overbooking': $this->sendOverbookingAction();
                break;
            case 'check-review': $this->checkReviewAction();
                break;
            case 'send-review': $this->sendReviewAction();
                break;
            case 'send-ccca': $this->sendCCCAAction();
                break;
            case 'send-modification-cancel': $this->sendCancellationAction();
                break;
            case 'send-update-payment-details-guest': $this->sendUpdatePaymentDetailsGuest();
                break;
            case 'send-payment-details-updated-ginosi': $this->sendPaymentDetailsUpdatedGinosi();
                break;
            case 'send-modification-ginosi': $this->sendModificationGinosiAction();
                break;
            case 'show-modification': $this->showModificationAction();
                break;
            case 'send-receipt':
                $this->sendReceiptAction();
                break;
            default :
                echo '- type true parameter ( reservation-email [ check-ki | send-ki | check-confirmation | send-guest | send-ginosi | send-overbooking | check-review | send-review | send-ccca | send-modification-cancel | send-update-payment-details-guest | send-payment-details-updated-ginosi | send-modification-ginosi | show-modification)' . PHP_EOL;
                return false;
        }
    }

    /**
     * @param string $mode
     * @return \ArrayObject|bool|\DDD\Domain\Booking\KeyInstructionPage[]
     */
    public function checkKIAction($mode = 'check')
    {
        try {
            /**
             * @var \DDD\Service\Booking $bookingService
             */
            $bookingService = $this->getServiceLocator()->get('service_booking');

            $bookings = $bookingService->bookingInfoForKeyInstructionMail($this->id);

            if ($mode === 'send') {
                return $bookings;
            } else {
                if (!empty($bookings)) {
                    foreach ($bookings as $row) {

                        echo PHP_EOL;
                        echo 'booking id:      ' . $row->getId() . PHP_EOL;
                        echo 'res number:      ' . $row->getResNumber() . PHP_EOL;
                        echo 'acc id:          ' . $row->getApartmentId() . PHP_EOL;
                        echo 'primary email:   ' . $row->getGuestEmail() . PHP_EOL;
                        echo 'secondary email: ' . $row->getSecondaryEmail() . PHP_EOL;
                        echo 'ki_page_hash:    ' . $row->getKiPageHash() . PHP_EOL;
                        echo 'first name:      ' . $row->getGuestFirstName() . PHP_EOL;
                        echo 'last name:       ' . $row->getGuestLastName() . PHP_EOL;
                        echo 'date from:       ' . $row->getDateFrom() . PHP_EOL;
                        echo 'date to:         ' . $row->getDateTo() . PHP_EOL;
                        echo 'acc name:        ' . $row->getApartmentName() . PHP_EOL;
                        echo 'acc city id:     ' . $row->getApartmentCityId() . PHP_EOL;
                        echo 'pin:             ' . $row->getPin() . PHP_EOL;
                        echo 'outside code:    ' . $row->getOutsideDoorCode() . PHP_EOL;
                    }
                } else {
                    echo 'No have key instructions for send.' . PHP_EOL;
                }
            }
        } catch (\Exception $e) {
            $this->outputMessage('[error]Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return bool
     */
    public function sendKIAction()
    {
        /**
         * @var Logger $logger
         * @var \DDD\Domain\Booking\KeyInstructionPage|\ArrayObject $bookings
         * @var Fraud $fraudService
         * @var \DDD\Service\Textline $textlineService
         */
        try {
            $mailer = $this->getServiceLocator()->get('Mailer\Email');
            $logger = $this->getServiceLocator()->get('ActionLogger');
            $textlineService = $this->getServiceLocator()->get('service_textline');

            $bookings = $this->checkKIAction('send');

            $emailValidator = new EmailAddress();
            $resNumbers = [];

            foreach ($bookings as $row) {
                $resNumber      = $row->getResNumber();
                $resNumberArray = explode('-', $resNumber);
                $phone1         = $row->getPhone1();
                $phone2         = $row->getPhone2();
                $guestEmail     = !empty($this->email) ? $this->email : $row->getGuestEmail();

                if (!in_array($resNumberArray[0], $resNumbers)) {
                    if (!$emailValidator->isValid($guestEmail)) {
                        echo '[error] Email is not valid ' . $guestEmail . '. Reservation ' . $row->getResNumber() . PHP_EOL;

                        $this->gr2crit("Key Instruction mail wasn't sent to Customer", [
                            'apartment_id'       => $row->getApartmentId(),
                            'reservation_number' => $row->getResNumber(),
                            'reservation_id'     => $row->getId(),
                            'reason'             => 'Email is not valid'
                        ]);

                        continue;
                    }

                    $fraudService = $this->getServiceLocator()->get('service_fraud');
                    $fraud        = $fraudService->isFraudReservation($row->getId());

                    if (!$this->bo_action && $fraud) {
                        $this->gr2emerg('User From Blacklist', [
                            'apartment_id'       => $row->getApartmentId(),
                            'reservation_number' => $row->getResNumber(),
                            'reservation_id'     => $row->getId()
                        ]);

                        continue;
                    }

                    // calculate reservation data for receive email
                    if ($row->getIsRefundable() === '1') {
                        $penaltyVal = 0;
                        switch ($row->getPenalty()) {
                            case 1:
                                $penaltyVal = $row->getPenaltyVal() . '%';
                                break;
                            case 2:
                                $penaltyVal = number_format($row->getPenaltyVal(), 2) . ' ' . $row->getGuestCurrencyCode();
                                break;
                            case 3:
                                $penaltyVal = $row->getPenaltyVal() . ' ' . $textlineService->getUniversalTextline(862);
                                break;
                        }

                        if ((int)$row->getRefundableBeforeHours() > 48) {
                            $time = ((int)$row->getRefundableBeforeHours() / 24) . ' ' . $textlineService->getUniversalTextline(977);
                        } else {
                            $time = $row->getRefundableBeforeHours() . ' ' . $textlineService->getUniversalTextline(976);
                        }

                        $cancellation = Helper::evaluateTextline($textlineService->getUniversalTextline(859),
                            [
                                '{{CXL_PENALTY}}' => $penaltyVal,
                                '{{CXL_TIME}}'    => $time,
                            ]);
                    } else {
                        $cancellation = $textlineService->getUniversalTextline(861);
                    }

                    $cityName = $textlineService->getCityName($row->getApartmentCityId());

                    $mailContent = Helper::evaluateTextline($textlineService->getUniversalTextline(1113), [
                        '{{CITY_NAME}}'  => $cityName,
                        '{{PRODUCT}}'    => $row->getApartmentName()
                    ]);

                    $mailSubject = Helper::evaluateTextline($textlineService->getUniversalTextline(1090, true), [
                        '{{ACC_NAME}}'     => $row->getApartmentName(),
                        '{{CITY_NAME}}'    => $cityName,
                        '{{ARRIVAL_DATE}}' => date("j M, Y", strtotime($row->getDateFrom())),
                        '{{RES_NUMBER}}'   => $resNumberArray[0],
                    ]);

                    $mailSubject = preg_replace('/\s+/', ' ', trim($mailSubject));

                    // analytics image
                    $analyticsCode  = $this->getAnalyticsCode();
                    $analyticsImage = Helper::evaluateTextline($textlineService->getUniversalTextline(1681),
                        [
                            '{{ANALYTICS_CODE}}'       => $analyticsCode,
                            '{{ANALYTICS_RES_NUMBER}}' => $resNumberArray[0],
                            '{{ANALYTICS_TEMPLATE}}'   => 'key-instruction',
                            '{{ANALYTICS_PARTNER_ID}}' => $row->getPartnerId(),
                        ]);

                    $analyticsQuery  = Helper::evaluateTextline($textlineService->getUniversalTextline(1682),
                        [
                            '{{ANALYTICS_TEMPLATE}}'   => 'key-instruction',
                        ]);

                    $mailer->send(
                        'key-instruction', [
                        'layout'                  => "layout-new",
                        'analyticsQuery'          => $analyticsQuery,
                        'to'                      => $guestEmail,
                        'to_name'                 => $row->getGuestFirstName() . ' ' . $row->getGuestLastName(),
                        'from_address'            => EmailAliases::FROM_MAIN_MAIL,
                        'from_name'               => 'Ginosi Apartments',
                        'subject'                 => $mailSubject,
                        'title'                   => $textlineService->getUniversalTextline(1114),
                        'content'                 => $mailContent,
                        'guestName'               => $row->getGuestFirstName() . ' ' . $row->getGuestLastName(),
                        'keyLink'                 => 'https://' . DomainConstants::WS_SECURE_DOMAIN_NAME . '/key?code=' . $row->getKiPageHash() . '&' . $analyticsQuery ,
                        'viewButtonText'          => $textlineService->getUniversalTextline(1109),
                        'phone1'                  => $phone1,
                        'phone2'                  => $phone2,
                        'textLine1478'            => $textlineService->getUniversalTextline(1478),
                        'textLine1479'            => $textlineService->getUniversalTextline(1479),
                        'textLine1659'            => $textlineService->getUniversalTextline(1659),
                        'textLine1658'            => $textlineService->getUniversalTextline(1658),
                        'textline842'             => $textlineService->getUniversalTextline(842),
                        'cancellationTitle'       => $textlineService->getUniversalTextline(1309),
                        'cancellation'            => $cancellation,
                        'paymentPolicyTitle'      => $textlineService->getUniversalTextline(1654),
                        'paymentPolicy'           => $textlineService->getUniversalTextline(1653),
                        'termsAndConditions'      => $textlineService->getUniversalTextline(1171),
                        'termsAndConditionsTitle' => $textlineService->getUniversalTextline(547),
                        'analyticsImage'          => $analyticsImage,
                    ]);

                    /**
                     * @var \DDD\Service\Booking $bookingService
                     */
                    $bookingService = $this->getServiceLocator()->get('service_booking');

                    $bookingService->updateOutsideDoorCode($row->getId(), $row->getOutsideDoorCode());

                    $msg = 'Sending key instruction to ' . $guestEmail . ' for reservation '.$row->getResNumber() . ', id ' . $row->getId();

                    // To prevent duplicate logging
                    if (!$this->bo_action) {
                        $logger->save(Logger::MODULE_BOOKING, $row->getId(), Logger::ACTION_BOOKING_EMAIL, Logger::VALUE_EMAIL_KI) || print($logger->getErrorMessage());
                    }

                    $this->gr2info('Sending key instruction', [
                        'apartment_id'       => $row->getApartmentId(),
                        'reservation_number' => $row->getResNumber(),
                        'reservation_id'     => $row->getId()
                    ]);

                    $this->outputMessage($msg);
                }
            }

            return true;
        } catch (\Exception $e) {
            $msg = "[error]Error: Key instruction wasn't sent.";

            $this->gr2logException($e, "Key instruction wasn't sent", [
                'apartment_id'       => isset($row) ? $row->getApartmentId() : '',
                'reservation_number' => isset($row) ? $row->getResNumber() : '',
                'reservation_id'     => isset($row) ? $row->getId() : ''
            ]);

            $this->outputMessage($msg . ' ' . $e->getMessage());

            return false;
        }
    }


    public function checkConfirmationAction($mode = 'check')
    {
        try{
            /**
             * @var \DDD\Service\Booking $bookingService
             */
            $bookingService = $this->getServiceLocator()->get('service_booking');

            $bookings = $bookingService->bookingInfoForReservationMail($this->id, $mode);

            if ($mode !== 'check') {
                return $bookings;
            } else {
                if ($bookings && count($bookings) > 0) {
                    /**
                     * @var \DDD\Service\Textline $textlineService
                     */
                    $textlineService = $this->getServiceLocator()->get('service_textline');

                    /** @var \DDD\Domain\Booking\ReservationConfirmationEmail $row */
                    foreach ($bookings as $row) {
                        $dateIn     = new \DateTime($row->getDateFrom());
                        $dateOut    = new \DateTime($row->getDateTo());
                        $interval   = $dateIn->diff($dateOut);

                        $roomParams = array(
                            'max_caps'          => $row->getPAX(),
                            'room_names'        => $row->getApartmentName(),
                            'res_number'        => $row->getResNumber(),
                            'rate_name'         => $row->getRateName(),
                            'is_refundable'     => $row->getIsRefundable(),
                            'penalty_type'      => $row->getPenalty(),
                            'penalty_val'       => $row->getPenaltyVal(),
                            'refundable_before_hours' => $row->getRefundableBeforeHours()
                        );

                        $accCity = $textlineService->getCityName($row->getApartmentCityId());
                        $accCountry = $textlineService->getCountryName($row->getApartmentCountryId());

                        /**
                         * @var \DDD\Service\Accommodations $accommodationsService
                         */
                        $accommodationsService = $this->getServiceLocator()->get('service_accommodations');
                        $accGeneral = $accommodationsService->getAppartmentFullAddressByID($row->getApartmentIdAssigned());

                        $penaltyAmountBooker = $row->getGuestBalance() * $row->getCurrencyRate();


                        echo PHP_EOL;
                        echo 'booking id:               '.$row->getId().PHP_EOL;
                        echo 'acc id:                   '.$row->getApartmentIdAssigned().PHP_EOL;
                        echo 'acc currency:             '.$row->getApartmentCurrencyCode().PHP_EOL;
                        echo 'currency:                 '.$row->getGuestCurrencyCode().PHP_EOL;
                        echo 'res number:               '.$row->getResNumber().PHP_EOL;
                        echo 'primary email:            '.$row->getGuestEmail().PHP_EOL;
                        echo 'secondary email:          '.$row->getSecondaryEmail().PHP_EOL;
                        echo 'first name:               '.$row->getGuestFirstName().PHP_EOL;
                        echo 'last name:                '.$row->getGuestLastName().PHP_EOL;
                        echo 'date from:                '.$row->getDateFrom().PHP_EOL;
                        echo 'date to:                  '.$row->getDateTo().PHP_EOL;
                        echo 'check-in:                 '.$row->getCheckIn().PHP_EOL;
                        echo 'arrival time:             '.$row->getGuestArrivalTime().PHP_EOL;
                        echo 'total nights:             '.$interval->format('%d').PHP_EOL;
                        echo 'capacity:                 '.$row->getPAX().PHP_EOL;
                        echo 'room params:              '. print_r($roomParams, true).PHP_EOL;
                        echo 'date diff:                '.$interval->format('%d').PHP_EOL;
                        echo 'conversion:               '.$row->getCurrencyRate().PHP_EOL;
                        echo 'acc address:              '.$accGeneral->getAddress().PHP_EOL;
                        echo 'acc country id:           '.$row->getApartmentCountryId().PHP_EOL;
                        echo 'acc country:              '.$accCountry.PHP_EOL;
                        echo 'acc city:                 '.$accCity.PHP_EOL;
                        echo 'postal code:              '.$accGeneral->getPostalCode().PHP_EOL;
                        echo 'telephone:                '.$row->getGuestPhone().PHP_EOL;
                        echo 'remarks:                  '.$row->getRemarks().PHP_EOL;
                        echo 'model:                    '.$row->getModel().PHP_EOL;
                        echo 'review_page_hash:         '.$row->getReviewPageHash().PHP_EOL;
                        echo 'penalty amount product:   '.$row->getGuestBalance().PHP_EOL;
                        echo 'penalty amount customer:  '.$penaltyAmountBooker.PHP_EOL;
                        echo 'over booking              '.$row->getOverbookingStatus().PHP_EOL.PHP_EOL;
                    }
                } else {
                    echo 'Not found reservation(s) for sending.'.PHP_EOL;
                }
            }
        } catch (\Exception $e) {
            $this->outputMessage('[error]Error ' . $e->getMessage());
            return FALSE;
        }
    }


    public function sendGuestAction()
    {
        $mode = 'send-guest';
        $this->sendConfirmation($mode);
    }

    public function sendGinosiAction()
    {
        $mode = 'send-ginosi';
        $this->sendConfirmation($mode);
    }

    public function sendConfirmation($mode = 'send-ginosi')
    {
        /**
         * @var \DDD\Domain\Booking\ReservationConfirmationEmail[] $reservations
         */
        try {
            $reservations = $this->checkConfirmationAction($mode);

            if (!$reservations) {
                echo 'Notice: Not found reservation(s) for sending.' . PHP_EOL;
                exit;
            }

            /**
             * @var \DDD\Service\Textline $textlineService
             * @var \DDD\Service\Booking $bookingService
             */
            $textlineService = $this->getServiceLocator()->get('service_textline');
            $bookingService = $this->getServiceLocator()->get('service_booking');

            $serviceLocator = $this->getServiceLocator();
            $mailer         = $serviceLocator->get('Mailer\Email');
            $emailValidator = new EmailAddress();

            $resNumbers = array();

            foreach ($reservations as $reservation) {
                $resNumber = explode('-', $reservation->getResNumber());
                if (!in_array($resNumber[0], $resNumbers)) {

                    $guestEmail = !empty($this->email) ? $this->email : $reservation->getGuestEmail();

                    if (!$emailValidator->isValid($guestEmail)) {
                        $bookingService->setBookingStatusIsQueueAsError($reservation->getId());

                        $this->gr2crit("Reservation mail wasn't sent to Customer", [
                            'apartment_id'       => $reservation->getApartmentIdAssigned(),
                            'reservation_number' => $reservation->getResNumber(),
                            'reservation_id'     => $reservation->getId(),
                            'reason'             => 'Email is not valid'
                        ]);

                        echo '[error] Email is not valid ' . $guestEmail . '. Reservation ' . $reservation->getResNumber() . PHP_EOL;
                        continue;
                    }

                    if ($reservation->isEmailingDisabled() && $this->id === FALSE) {
                        $bookingService->setBookingInQueueAsSent($reservation->getId());

                        $msg = 'Reservation Email not sent to guest ' . $guestEmail
                            . ' for reservation ' . $reservation->getResNumber()
                            . ', id ' . $reservation->getId() . '. Because guest Emails are disabled for this Partner.' . PHP_EOL;

                        $this->gr2err("Reservation mail wasn't sent to Customer",
                            [
                                'reservation_number' => $reservation->getResNumber(),
                                'reservation_id'     => $reservation->getId(),
                                'reason'             => 'Because guest emails are disabled for this Partner'
                            ]);

                        echo $msg;
                        continue;
                    }

                    $dateIn   = new \DateTime($reservation->getDateFrom());
                    $dateOut  = new \DateTime($reservation->getDateTo());
                    $totalNights = $dateIn->diff($dateOut)->format('%d');

                    $remarks = '<span style="font-weight: bold;">'
                        . $textlineService->getUniversalTextline(868)
                        . ':</span> ' . $reservation->getRemarks();

                    if ($reservation->getIsRefundable() === '1') {
                        $penaltyVal = 0;
                        switch ($reservation->getPenalty()) {
                            case 1:
                                $penaltyVal = $reservation->getPenaltyVal() . '%';
                                break;
                            case 2:
                                $penaltyVal = number_format($reservation->getPenaltyVal(), 2) . ' ' . $reservation->getGuestCurrencyCode();
                                break;
                            case 3:
                                $penaltyVal = $reservation->getPenaltyVal() . ' ' . $textlineService->getUniversalTextline(862);
                                break;
                        }

                        if ((int)$reservation->getRefundableBeforeHours() > 48) {
                            $time = ((int)$reservation->getRefundableBeforeHours() / 24) . ' ' . $textlineService->getUniversalTextline(977);
                        } else {
                            $time = $reservation->getRefundableBeforeHours() . ' ' . $textlineService->getUniversalTextline(976);
                        }

                        $cancelInfo = Helper::evaluateTextline($textlineService->getUniversalTextline(859),
                            [
                                '{{CXL_PENALTY}}' => $penaltyVal,
                                '{{CXL_TIME}}'    => $time,
                            ]);
                    } else {
                        $cancelInfo = $textlineService->getUniversalTextline(861);
                    }

                    $mailSubject = Helper::evaluateTextline($textlineService->getUniversalTextline(947, true),
                        [
                            '{{RES_NUMBER}}' => $resNumber[0],
                            '{{GUEST_NAME}}' => $reservation->getGuestFirstName() . ' ' . $reservation->getGuestLastName(),
                            '{{DATE_FROM}}'  => date(Constants::GLOBAL_DATE_FORMAT, strtotime($reservation->getDateFrom())),
                            '{{DATE_TO}}'    => date(Constants::GLOBAL_DATE_FORMAT, strtotime($reservation->getDateTo())),
                        ]);

                    $mailSubject = preg_replace('/\s+/', ' ', trim($mailSubject));

                    if ($mode == 'send-guest') {
                        $to     = $guestEmail;
                        $toName = $reservation->getGuestFirstName() . ' ' . $reservation->getGuestLastName();
                        $msg = '[success]Sending confirmation mail to Customer ' . $guestEmail
                            . ' for reservation ' . $reservation->getResNumber()
                            . ', id ' . $reservation->getId();
                    } else {
                        $to     = EmailAliases::TO_RESERVATION;
                        $toName = 'Ginosi Apartments';
                        $msg = '[success]Sending confirmation mail to Ginosi '
                            . ' for reservation ' . $reservation->getResNumber()
                            . ', id ' . $reservation->getId();
                    }

                    // payments data
                    /** @var \DDD\Service\Booking\Charge $chargeService */
                    $chargeService = $this->getServiceLocator()->get('service_booking_charge');

                    if ($mode == 'send-guest') {
                        $chargesList = $chargeService->getToBeChargedByReservationId($reservation->getId(), true);
                    } else {
                        $chargesList = $chargeService->getToBeChargedByReservationId($reservation->getId());
                    }

                    // analytics image
                    $analyticsCode  = $this->getAnalyticsCode();
                    $analyticsImage = Helper::evaluateTextline($textlineService->getUniversalTextline(1681),
                        [
                            '{{ANALYTICS_CODE}}'       => $analyticsCode,
                            '{{ANALYTICS_RES_NUMBER}}' => $reservation->getResNumber(),
                            '{{ANALYTICS_TEMPLATE}}'   => 'reservation-confirmation',
                            '{{ANALYTICS_PARTNER_ID}}' => $reservation->getPartnerId(),
                        ]);

                    $analyticsQuery  = Helper::evaluateTextline($textlineService->getUniversalTextline(1682),
                        [
                            '{{ANALYTICS_TEMPLATE}}'   => 'key-instruction',
                        ]);

                    $mailer->send(
                        'reservation-confirmation',
                        array(
                            'layout'                  => 'layout-new',
                            'analyticsQuery'          => $analyticsQuery,
                            'to'                      => $to,
                            'to_name'                 => $toName,
                            'replyTo'                 => EmailAliases::RT_RESERVATION,
                            'from_address'            => EmailAliases::FROM_MAIN_MAIL,
                            'from_name'               => 'Ginosi Apartments',
                            'subject'                 => $mailSubject,
                            'reservation'             => $reservation,
                            'resDetails'              => $textlineService->getUniversalTextline(705),
                            'totalNights'             => $totalNights,
                            'remarks'                 => $remarks,
                            'cancelInfo'              => $cancelInfo,
                            'paymentTitle'            => $textlineService->getUniversalTextline(931),
                            'chargesList'             => $chargesList,
                            'termsAndConditions'      => $textlineService->getUniversalTextline(1171),
                            'termsAndConditionsTitle' => $textlineService->getUniversalTextline(547),
                            'textLine1478'            => $textlineService->getUniversalTextline(1478),
                            'textLine1479'            => $textlineService->getUniversalTextline(1479),
                            'textLine1659'            => $textlineService->getUniversalTextline(1659),
                            'analyticsImage'          => $analyticsImage
                        ));

                    $bookingService->setBookingInQueueAsSent($reservation->getId());

                    $this->outputMessage($msg);

                    $this->gr2info('Sending Confirmation mail to ' . ($mode == 'send-guest' ? 'customer' : 'Ginosi'),
                        [
                            'reservation_number' => $reservation->getResNumber(),
                            'reservation_id' => $reservation->getId(),
                        ]);
                }
            }
            return TRUE;
        } catch (\Exception $e) {
            $msg = "[error]Error: Confirmation mail wasn't sent to" . ($mode == 'send-guest' ? 'customer' : 'Ginosi');

            $this->gr2logException($e, "Confirmation mail wasn't sent to " . ($mode == 'send-guest' ? 'customer' : 'Ginosi'), [
                'apartment_id' => isset($reservation) ? $reservation->getApartmentIdAssigned() : '',
                'reservation_number' => isset($reservation) ? $reservation->getResNumber() : '',
                'reservation_id' => isset($reservation) ? $reservation->getId() : ''
            ]);

            $this->outputMessage($msg . ' ' . $e->getMessage());

            return FALSE;
        }
    }

    public function sendOverbookingAction()
    {
        try {
            /**
             * @var \DDD\Dao\Booking\Booking $reservationDao
             */
            $reservationId = $this->id;
            $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');
            $mailer = $this->getServiceLocator()->get('Mailer\Email');

            if (!$reservationId) {
                $this->gr2warn('Not found overbooking reservation');
                return false;
            }

            $reservation = $reservationDao->getOverbookingDataForEmail($reservationId);

            if (!$reservation) {
                $this->gr2warn('Not found overbooking reservation', [
                    'reservation_id' => $reservationId
                ]);
                return false;
            }

            /**
             * @var \DDD\Service\Textline $textlineService
             */
            $textlineService = $this->getServiceLocator()->get('service_textline');

            $phone1 = $reservation['phone1'];
            $phone2 = $reservation['phone2'];
            $reservationNumber = $reservation['res_number'];
            $ticketLink = 'http://' . DomainConstants::BO_DOMAIN_NAME . '/booking/edit/' . $reservationNumber;
            $mailSubject = 'Overbooking Reservation R# ' . $reservationNumber;
            $details = $textlineService->getUniversalTextline(1512);

            $analyticsQuery  = Helper::evaluateTextline($textlineService->getUniversalTextline(1682),
                [
                    '{{ANALYTICS_TEMPLATE}}'   => 'key-instruction',
                ]);

            $mailer->send(
                'overbooking',
                array(
                    'analyticsQuery'   => $analyticsQuery,
                    'to'               => EmailAliases::TO_RESERVATION,
                    'to_name'          => 'Ginosi Apartments',
                    'replyTo'          => EmailAliases::RT_RESERVATION,
                    'from_address'     => EmailAliases::FROM_MAIN_MAIL,
                    'from_name'        => 'Overbooking Reservation',
                    'subject'          => $mailSubject,
                    'title'            => 'Overbooking Reservation',
                    'details'          => $details,
                    'link'             => $ticketLink,
                    'phone1'           => $phone1,
                    'phone2'           => $phone2,
                    'textLine1478'     => $textlineService->getUniversalTextline(1478),
                    'textLine1479'     => $textlineService->getUniversalTextline(1479)
                ));

            $this->gr2info('Sending Overbooking mail to Ginosi',
                [
                    'reservation_number' => $reservationNumber,
                    'reservation_id' => $reservationId,
                ]);
            return true;
        } catch (\Exception $e) {
            $msg = '[error]Error: Overbooking mail to Ginosi not sent';
            $this->gr2logException($e, "Overbooking mail wasn't sent to Ginosi", [
                'reservation_number' => isset($reservationNumber) ? $reservationNumber : '',
                'reservation_id'     => isset($reservationId) ? $reservationId : ''
            ]);

            $this->outputMessage($msg . ' ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $mode
     * @return \DDD\Domain\Booking\Review[]|\ArrayObject|bool
     */
    public function checkReviewAction($mode = 'check')
    {
        try {
            /**
             * @var \DDD\Service\Booking $bookingService
             */
            $bookingService = $this->getServiceLocator()->get('service_booking');

            $bookings = $bookingService->bookingInfoForReviewMail($this->id);

            if ($mode === 'send') {
                return $bookings;
            } else {
                if ($bookings->count() > 0) {
                    echo PHP_EOL;

                    foreach ($bookings as $row) {

                        echo PHP_EOL;
                        echo 'booking id:      ' . $row->getId() . PHP_EOL;
                        echo 'res number:      ' . $row->getResNumber() . PHP_EOL;
                        echo 'status:          ' . $row->getStatus() . PHP_EOL;
                        echo 'acc id:          ' . $row->getApartmentIdAssigned() . PHP_EOL;
                        echo 'primary email:   ' . $row->getGuestEmail() . PHP_EOL;
                        echo 'secondary email: ' . $row->getSecondaryEmail() . PHP_EOL.PHP_EOL;
                    }
                } else {
                    echo 'There is no reservations to review.' . PHP_EOL;
                }
            }
        } catch (\Exception $e) {
            $this->outputMessage('[error]Error: ' . $e->getMessage());
        }

        return false;
    }

    public function sendReviewAction()
    {
        /**
         * @var Fraud $fraudService
         */
        $mailer = $this->getServiceLocator()->get('Mailer\Email');
        $fraudService = $this->getServiceLocator()->get('service_fraud');

        try {
            $bookings = $this->checkReviewAction('send');

            $emailValidator = new EmailAddress();

            if ($bookings->count()) {
                /**
                 * @var \DDD\Service\Textline $textlineService
                 */
                $textlineService = $this->getServiceLocator()->get('service_textline');

                foreach ($bookings as $row) {
                    $resNumber  = str_replace('-1-1', '', $row->getResNumber());
                    $guestEmail = !empty($this->email) ? $this->email : $row->getGuestEmail();

                    if (!$emailValidator->isValid($guestEmail)) {
                        echo '[error] Email is not valid ' . $guestEmail . '. Reservation ' . $row->getResNumber() . PHP_EOL;

                        $this->gr2crit("Review mail wasn't sent to Customer", [
                            'apartment_id'       => $row->getApartmentId(),
                            'reservation_number' => $row->getResNumber(),
                            'reservation_id'     => $row->getId(),
                            'reason'             => 'Email is not valid'
                        ]);

                        continue;
                    }

                    if ($fraudService->isFraudReservation($row->getId())) {
                        $this->gr2emerg('User is in blacklist', [
                            'reservation_number' => $row->getResNumber(),
                            'reservation_id'     => $row->getId(),
                        ]);

                        continue;
                    }

                    // calculate reservation data for receive email
                    if ($row->getIsRefundable() === '1') {
                        $penaltyVal = 0;
                        switch ($row->getPenalty()) {
                            case 1:
                                $penaltyVal = $row->getPenaltyVal() . '%';
                                break;
                            case 2:
                                $penaltyVal = number_format($row->getPenaltyVal(), 2) . ' ' . $row->getGuestCurrencyCode();
                                break;
                            case 3:
                                $penaltyVal = $row->getPenaltyVal() . ' ' . $textlineService->getUniversalTextline(862);
                                break;
                        }

                        if ((int)$row->getRefundableBeforeHours() > 48) {
                            $time = ((int)$row->getRefundableBeforeHours() / 24) . ' ' . $textlineService->getUniversalTextline(977);
                        } else {
                            $time = $row->getRefundableBeforeHours() . ' ' . $textlineService->getUniversalTextline(976);
                        }

                        $cancellation = Helper::evaluateTextline($textlineService->getUniversalTextline(859),
                            [
                                '{{CXL_PENALTY}}' => $penaltyVal,
                                '{{CXL_TIME}}'    => $time,
                            ]);
                    } else {
                        $cancellation = $textlineService->getUniversalTextline(861);
                    }

                    $phone1    = $row->getPhone1();
                    $phone2    = $row->getPhone2();
                    $guestName = $row->getGuestFirstName() . ' ' . $row->getGuestLastName();

                    $mailContent = Helper::evaluateTextline($textlineService->getUniversalTextline(987), [
                        '{{GUEST_NAME}}'   => $guestName,
                    ]);

                    $subject = Helper::evaluateTextline($textlineService->getUniversalTextline(1650, true), [
                        '{{CITY}}'         => $row->getCityName(),
                        '{{ARRIVAL_DATE}}' => date("j M, Y", strtotime($row->getDateFrom())),
                    ]);

                    $subject = preg_replace('/\s+/', ' ', trim($subject));

                    // analytics image
                    $analyticsCode  = $this->getAnalyticsCode();
                    $analyticsImage = Helper::evaluateTextline($textlineService->getUniversalTextline(1681),
                        [
                            '{{ANALYTICS_CODE}}'       => $analyticsCode,
                            '{{ANALYTICS_RES_NUMBER}}' => $resNumber,
                            '{{ANALYTICS_TEMPLATE}}'   => 'review',
                            '{{ANALYTICS_PARTNER_ID}}' => $row->getPartnerId(),
                        ]);

                    $analyticsQuery  = Helper::evaluateTextline($textlineService->getUniversalTextline(1682),
                        [
                            '{{ANALYTICS_TEMPLATE}}'   => 'key-instruction',
                        ]);

                    $mailer->send('review', [
                        'layout'                  => 'layout-new',
                        'analyticsQuery'          => $analyticsQuery,
                        'to'                      => $guestEmail,
                        'to_name'                 => $guestName,
                        'from_address'            => EmailAliases::FROM_MAIN_MAIL,
                        'from_name'               => 'Ginosi Apartments',
                        'subject'                 => $subject,
                        'title'                   => $textlineService->getUniversalTextline(986),
                        'content'                 => $mailContent,
                        'guestName'               => $row->getGuestFirstName() . ' ' . $row->getGuestLastName(),
                        'reviewLink'              => 'https://' . DomainConstants::WS_SECURE_DOMAIN_NAME . '/add-review?code=' . $row->getReviewPageHash() . '&' . $analyticsQuery,
                        'thankReview'             => $textlineService->getUniversalTextline(989),
                        'viewButtonText'          => $textlineService->getUniversalTextline(1169),
                        'resNumber'               => $resNumber,
                        'phone1'                  => $phone1,
                        'phone2'                  => $phone2,
                        'textLine1478'            => $textlineService->getUniversalTextline(1478),
                        'textLine1479'            => $textlineService->getUniversalTextline(1479),
                        'textLine1659'            => $textlineService->getUniversalTextline(1659),
                        'textLine1658'            => $textlineService->getUniversalTextline(1658),
                        'textline842'             => $textlineService->getUniversalTextline(842),
                        'cancellationTitle'       => $textlineService->getUniversalTextline(1309),
                        'cancellation'            => $cancellation,
                        'paymentPolicyTitle'      => $textlineService->getUniversalTextline(1654),
                        'paymentPolicy'           => $textlineService->getUniversalTextline(1653),
                        'termsAndConditions'      => $textlineService->getUniversalTextline(1171),
                        'termsAndConditionsTitle' => $textlineService->getUniversalTextline(547),
                        'analyticsImage'          => $analyticsImage
                    ]);

                    /**
                     * @var \DDD\Service\Booking $bookingService
                     */
                    $bookingService = $this->getServiceLocator()->get('service_booking');

                    $bookingService->updateBookingReviewMail($row->getId());

                    $this->outputMessage('Sending review to ' . $guestEmail . ' for reservation ' . $row->getResNumber() . ', id ' . $row->getId());

                    $this->gr2info('Sending review mail', [
                        'reservation_number' => $row->getResNumber(),
                        'reservation_id' => $row->getId(),
                    ]);
                }
            } else {
                echo 'There is no reservations to review.' . PHP_EOL;
            }

            return true;
        } catch (\Exception $e) {
            $this->gr2logException($e, "Review mail wasn't sent", [
                'apartment_id'       => isset($row) ? $row->getApartmentId() : '',
                'reservation_number' => isset($row) ? $row->getResNumber() : '',
                'reservation_id'     => isset($row) ? $row->getId() : '',
            ]);

            $this->outputMessage('[error]Error: Review Email not sent. ' . $e->getMessage());
        }

        return false;
    }

    public function sendCCCAAction()
    {
        if (empty($this->id) || empty($this->cccaId)) {
            echo 'Wrong parameters supplied';

            exit;
        }

        /**
         * @var MailerService $mailer
         */
        $mailer = $this->getServiceLocator()->get('Mailer\Email');

        try {
            /**
             * @var \DDD\Dao\Booking\Booking $reservationDao
             * @var \DDD\Service\Textline $textlineService
             */
            $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');
            $cccaDao        = $this->getServiceLocator()->get('dao_finance_ccca');
            $textlineService = $this->getServiceLocator()->get('service_textline');

            $reservationId = $this->id;

            $emailData = $reservationDao->getReservationDataForChargeAuthorizationEmail($reservationId);
            $cccaData  = $cccaDao->fetchOne(['id' => $this->cccaId]);

            $emailAddress = $emailData->getGuestEmail();
            if ($this->email) {
                $emailAddress = $this->email;
            }

            $emailValidator = new EmailAddress();
            if (!$emailValidator->isValid($emailAddress)) {
                echo '[error] Email is not valid ' . $emailAddress . '. Reservation ' . $emailData->getReservationNumber() . PHP_EOL;

                $this->gr2crit("CCCA Form mail wasn't sent to Customer", [
                    'reservation_number' => $emailData->getReservationNumber(),
                    'reservation_id'     => $reservationId,
                    'reason'             => 'Email is not valid'
                ]);
            }

            $phone1 = $emailData->getPhone1();
            $phone2 = $emailData->getPhone2();

            $guestFullName = $emailData->getGuestFirstName() . ' ' . $emailData->getGuestLastName();

            // analytics image
            $analyticsCode  = $this->getAnalyticsCode();
            $analyticsImage = Helper::evaluateTextline($textlineService->getUniversalTextline(1681),
                [
                    '{{ANALYTICS_CODE}}'       => $analyticsCode,
                    '{{ANALYTICS_RES_NUMBER}}' => $emailData->getReservationNumber(),
                    '{{ANALYTICS_TEMPLATE}}'   => 'ccca-form',
                    '{{ANALYTICS_PARTNER_ID}}' => $emailData->getPartnerId(),
                ]);

            $analyticsQuery  = Helper::evaluateTextline($textlineService->getUniversalTextline(1682),
                [
                    '{{ANALYTICS_TEMPLATE}}'   => 'key-instruction',
                ]);

            $mailer->send('ccca-form', [
                'layout'             => "layout-new",
                'analyticsQuery'     => $analyticsQuery,
                'to'                 => $emailAddress,
                'to_name'            => $guestFullName,
                'from_address'       => EmailAliases::FROM_MAIN_MAIL,
                'from_name'          => 'Ginosi Apartments',
                'subject'            => Helper::evaluateTextline($textlineService->getUniversalTextline(1579, true), [
                    '{{RES_NUMBER}}'     => $emailData->getReservationNumber(),
                    '{{APARTMENT_NAME}}' => $emailData->getApartmentName(),
                    '{{DATE}}'           => date(Constants::GLOBAL_DATE_FORMAT, strtotime($emailData->getDateFrom())),
                ]),
                'title'              => $textlineService->getUniversalTextline(986), // Thank ou for choosing Ginosi Apartments
                'cccaPageLink'       => 'https://' . DomainConstants::WS_SECURE_DOMAIN_NAME . '/ccca-page?token=' . $cccaData->getPageToken() . '&' . $analyticsQuery,
                'viewButtonText'     => $textlineService->getUniversalTextline(1576),
                'phone1'             => $phone1,
                'phone2'             => $phone2,
                'textLine1479'       => $textlineService->getUniversalTextline(1479),
                'textLine1659'       => $textlineService->getUniversalTextline(1659),
                'textline1667'       => $textlineService->getUniversalTextline(1667),
                'textline1575'       => $textlineService->getUniversalTextline(1575),
                'dearGuestName'      => $textlineService->getUniversalTextline(842) . ' ' . $guestFullName . ',',
                'analyticsImage'     => $analyticsImage
            ]);

            $this->outputMessage('Sending CCCA Form to ' . $emailAddress . ' for reservation ' . $emailData->getReservationNumber() . ', id ' . $reservationId);

            $this->gr2info('Sending CCCA Form', [
                'reservation_number' => $emailData->getReservationNumber(),
                'reservation_id' => $reservationId,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->gr2logException($e, "CCCA form mail wasn't sent", [
                'reservation_number' => isset($emailData) ? $emailData->getReservationNumber() : '',
                'reservation_id'     => $reservationId,
            ]);

            $this->outputMessage('[error]Error: CCCA form Email not sent. ' . $e->getMessage());
        }

        return false;
    }

    /**
     * @param string $mode
     * @return bool|\Zend\Db\ResultSet\ResultSet
     */
    public function showModificationAction($mode = 'show')
    {
        try {
            /**
             * @var \DDD\Service\Booking $bookingService
             */
            $bookingService = $this->getServiceLocator()->get('service_booking');

            if ($mode === 'send-modification-cancel') {
                $bookings = $bookingService->bookingInfoForCancellationMail($this->id);
            } elseif ($mode === 'send-modification-ginosi') {
                $bookings = $bookingService->bookingInfoForModificationMail($this->id, 'modify-ginosi');
            } else {
                $bookings = $bookingService->bookingInfoForModificationMail($this->id, $mode);
            }

            if ($mode !== 'show') {
                return $bookings;
            } else {
                if ($bookings && count($bookings) > 0) {
                    foreach ($bookings as $row) {
                        echo PHP_EOL;
                        echo 'booking id:               '.$row->getId().PHP_EOL;
                        echo 'res number:               '.$row->getResNumber().PHP_EOL;
                        echo 'email:                    '.$row->getGuestEmail().PHP_EOL;
                        echo 'first name:               '.$row->getGuestFirstName().PHP_EOL;
                        echo 'last name:                '.$row->getGuestLastName().PHP_EOL;
                        echo 'cc password:              '.$row->getProvideCcPageHash().PHP_EOL.PHP_EOL;
                    }
                } else {
                    echo 'Not found modification for sending.'.PHP_EOL;
                }
            }
        } catch (\Exception $e) {
            $this->outputMessage('[error]Error: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * @return bool
     */
    public function sendUpdatePaymentDetailsGuest()
    {
        try{
            $bookings = $this->showModificationAction('send-update-payment-details-guest');

            $serviceLocator = $this->getServiceLocator();
            $mailer = $serviceLocator->get('Mailer\Email');
            $emailValidator = new EmailAddress();

            if ($bookings && count($bookings) > 0) {
                /**
                 * @var \DDD\Service\Textline $textlineService
                 */
                $textlineService = $this->getServiceLocator()->get('service_textline');

                foreach ($bookings as $row) {
                    $guestEmail = !empty($this->email) ? $this->email : $row->getGuestEmail();

                    if (!$emailValidator->isValid($guestEmail)) {
                        echo '[error] Email is not valid ' . $guestEmail . '. Reservation ' . $row->getResNumber() . PHP_EOL;

                        $this->gr2crit("Payment details change mail wasn't sent to Customer", [
                            'apartment_id'       => $row->getApartmentIdAssigned(),
                            'reservation_number' => $row->getResNumber(),
                            'reservation_id'     => $row->getId(),
                            'reason'             => 'Email is not valid'
                        ]);

                        continue;
                    }

                    $phone1 = $row->getPhone1();
                    $phone2 = $row->getPhone2();

                    $mailSubject = Helper::evaluateTextline($textlineService->getUniversalTextline(1099, true), [
                        '{{GUEST_NAME}}' => $row->getGuestFirstName() . ' ' . $row->getGuestLastName(),
                        '{{CITY}}'       => $row->getCityName(),
                    ]);

                    $link = 'https://'.DomainConstants::WS_SECURE_DOMAIN_NAME
                        .'/booking/update-cc-details?code='
                        .$row->getProvideCcPageHash();

                    $textline1103Evaluated = Helper::evaluateTextline($textlineService->getUniversalTextline(1103, true), [
                        '{{RES_NUMBER}}'     => $row->getResNumber()
                    ]);

                    $dear = $textlineService->getUniversalTextline(842)
                        .' '.$row->getGuestFirstName()
                        .' '.$row->getGuestLastName().',';

                    $title = $textlineService->getUniversalTextline(931);

                    $viewButtonText = $textlineService->getUniversalTextline(1170);

                    // analytics image
                    $analyticsCode  = $this->getAnalyticsCode();
                    $analyticsImage = Helper::evaluateTextline($textlineService->getUniversalTextline(1681),
                        [
                            '{{ANALYTICS_CODE}}'       => $analyticsCode,
                            '{{ANALYTICS_RES_NUMBER}}' => $row->getResNumber(),
                            '{{ANALYTICS_TEMPLATE}}'   => 'new-cc-link-guest',
                            '{{ANALYTICS_PARTNER_ID}}' => $row->getPartnerId(),
                        ]);
                    $analyticsQuery  = Helper::evaluateTextline($textlineService->getUniversalTextline(1682),
                        [
                            '{{ANALYTICS_TEMPLATE}}'   => 'key-instruction',
                        ]);

                    $mailer->send(
                        'new-cc-link-guest',
                        array(
                            'layout'                => 'layout-new',
                            'analyticsQuery'        => $analyticsQuery,
                            'to'                    => $guestEmail,
                            'to_name'               => $row->getGuestFirstName().' '.$row->getGuestLastName(),
                            'replyTo'               => EmailAliases::RT_MODIFICATION,
                            'from_address'          => EmailAliases::FROM_MAIN_MAIL,
                            'from_name'             => 'Ginosi Apartments',
                            'subject'               => $mailSubject,
                            'title'                 => $title,
                            'dear'                  => $dear,
                            'detailsPart1'          => (isset($details[0]) ? $details[0] : ''),
                            'detailsPart2'          => (isset($details[1]) ? $details[1] : ''),
                            'link'                  => $link . '&' . $analyticsQuery,
                            'viewButtonText'        => $viewButtonText,
                            'phone1'                => $phone1,
                            'phone2'                => $phone2,
                            'textLine1479'          => $textlineService->getUniversalTextline(1479),
                            'textLine1659'          => $textlineService->getUniversalTextline(1659),
                            'textLine1668'          => $textlineService->getUniversalTextline(1668),
                            'textline1103Evaluated' => $textline1103Evaluated,
                            'analyticsImage'        => $analyticsImage
                        ));

                    $msg = 'Sending link to enter a new CC mail to '.$guestEmail
                        .' for reservation '.$row->getResNumber()
                        .', id '.$row->getId();

                    $this->outputMessage($msg);

                    $this->gr2info('Sending link to enter a new CC data',
                        [
                            'reservation_number' => $row->getResNumber(),
                            'reservation_id' => $row->getId()
                        ]);
                }

            } else {
                echo 'Not found modification for sending.'.PHP_EOL;
            }

        } catch (\Exception $e) {
            $msg = "[error]Error: Email to collect new payment details wasn't sent";

            $this->gr2logException($e, "Email to collect new payment details wasn't sent", [
                'apartment_id'       => isset($row) ? $row->getApartmentIdAssigned() : '',
                'reservation_number' => isset($row) ? $row->getResNumber() : '',
                'reservation_id'     => isset($row) ? $row->getId() : ''
            ]);

            $this->outputMessage($msg . ' ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * @return bool
     */
    public function sendPaymentDetailsUpdatedGinosi()
    {
        try{
            $bookings = $this->showModificationAction('send-payment-details-updated-ginosi');

            $serviceLocator = $this->getServiceLocator();
            $mailer = $serviceLocator->get('Mailer\Email');
            $emailValidator = new EmailAddress();

            if ($bookings && count($bookings) > 0) {
                /**
                 * @var \DDD\Service\Textline $textlineService
                 */
                $textlineService = $this->getServiceLocator()->get('service_textline');

                foreach ($bookings as $row) {
                    if (!$emailValidator->isValid($row->getGuestEmail())) {
                        echo '[error] Email is not valid ' . $row->getGuestEmail() . '. Reservation ' . $row->getResNumber() . PHP_EOL;

                        $this->gr2crit("Confirmation mail wasn't sent to Customer", [
                            'apartment_id'       => $row->getApartmentIdAssigned(),
                            'reservation_number' => $row->getResNumber(),
                            'reservation_id'     => $row->getId(),
                            'reason'             => 'Email is not valid'
                        ]);

                        continue;
                    }

                    $mailSubject = Helper::evaluateTextline($textlineService->getUniversalTextline(1099, true), [
                        '{{GUEST_NAME}}' => $row->getGuestFirstName() . ' ' . $row->getGuestLastName(),
                        '{{CITY}}'       => $row->getCityName(),
                    ]);

                    $title = 'Payment Details';

                    $resNumber = '<b><a href="http://'.DomainConstants::BO_DOMAIN_NAME
                        .'/booking/edit/'.$row->getResNumber().'">'
                        .$row->getResNumber().'</a></b>';

                    $phone1 = $row->getPhone1();
                    $phone2 = $row->getPhone2();

                    $details = Helper::evaluateTextline($textlineService->getUniversalTextline(1097),
                        [
                            '{{RES_NUMBER}}' => $resNumber
                        ]);

                    if ($this->cc_provided) {
                        $ccProvided = '<b>Credit Card Provided</b>';
                    } else {
                        $ccProvided = '<b>No Credit Card Provided</b>';
                    }

                    $analyticsQuery  = Helper::evaluateTextline($textlineService->getUniversalTextline(1682),
                        [
                            '{{ANALYTICS_TEMPLATE}}'   => 'key-instruction',
                        ]);

                    $mailer->send(
                        'new-cc-confirm-ginosi',
                        array(
                            'analyticsQuery'    => $analyticsQuery,
                            'to'                => EmailAliases::TO_MODIFICATION,
                            'to_name'           => 'Ginosi Apartments',
                            'replyTo'           => EmailAliases::RT_MODIFICATION,
                            'from_address'      => EmailAliases::FROM_MAIN_MAIL,
                            'from_name'         => 'Ginosi Apartments',
                            'subject'           => $mailSubject,
                            'title'             => $title,
                            'details'           => $details,
                            'newCC'             => $ccProvided,
                            'phone1'            => $phone1,
                            'phone2'            => $phone2,
                            'textLine1478'      => $textlineService->getUniversalTextline(1478),
                            'textLine1479'      => $textlineService->getUniversalTextline(1479)
                        ));

                    $msg = 'Sending confirmation mail to '.EmailAliases::TO_MODIFICATION
                        .' for reservation '.$row->getResNumber()
                        .', id '.$row->getId();

                    $this->outputMessage($msg);

                    $this->gr2info('Sending confirmation mail to Ginosi',
                        [
                            'reservation_number' => $row->getResNumber(),
                            'reservation_id' => $row->getId()
                        ]);
                }

            } else {
                echo 'Not found modification for sending.'.PHP_EOL;
            }

        } catch (\Exception $e) {
            $msg = "[error]Error: Confirmation mail about modification to Ginosi wasn't sent. Script broken!";

            $this->gr2logException($e, "Confirmation mail about modification to Ginosi wasn't sent", [
                'apartment_id'       => isset($row) ? $row->getApartmentIdAssigned() : '',
                'reservation_number' => isset($row) ? $row->getResNumber() : '',
                'reservation_id'     => isset($row) ? $row->getId() : ''
            ]);

            $this->outputMessage($msg . ' ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Send cancellation email to booker or ginosi
     *
     * @return bool
     */
    public function sendCancellationAction()
    {
        try{
            $penaltyVal = 0;
            $bookings = $this->showModificationAction('send-modification-cancel');

            $serviceLocator = $this->getServiceLocator();
            $mailer = $serviceLocator->get('Mailer\Email');
            $emailValidator = new EmailAddress();

            if ($bookings && count($bookings) > 0) {
                /**
                 * @var \DDD\Service\Textline $textlineService
                 */
                $textlineService = $this->getServiceLocator()->get('service_textline');

                foreach ($bookings as $row) {
                    /**
                     * @var \DDD\Service\Partners $partnersService
                     */
                    $partnersService = $this->getServiceLocator()->get('service_partners');

                    $partner = $partnersService->partnerById($row->getPartnerId());

                    // collect for sending to Ginosi
                    $subject = Helper::evaluateTextline($textlineService->getUniversalTextline(1047, true),
                        [
                            '{{RES_NUMBER}}' => $row->getResNumber(),
                        ]);

                    $resNumber = '<a href="http://'.DomainConstants::BO_DOMAIN_NAME.'/booking/edit/'.$row->getResNumber().'" style="color: rgb(255, 255, 255); text-decoration: underline;">'.$row->getResNumber().'</a>';
                    $title     = Helper::evaluateTextline($textlineService->getUniversalTextline(1047),
                        [
                            '{{RES_NUMBER}}' => $resNumber
                        ]);

                    $phone1 = $row->getPhone1();
                    $phone2 = $row->getPhone2();

                    $checkInDate  = date(Constants::GLOBAL_DATE_TIME_WO_SEC_FORMAT, strtotime($row->getDateFrom(). ' ' .$row->getCheckIn()));
                    $checkOutDate = date(Constants::GLOBAL_DATE_TIME_WO_SEC_FORMAT, strtotime($row->getDateTo(). ' ' .$row->getCheckOut()));

                    $dateIn     = new \DateTime($row->getDateFrom());
                    $dateOut    = new \DateTime($row->getDateTo());
                    $interval   = $dateIn->diff($dateOut);

                    $cancelTitle = $textlineService->getUniversalTextline(712);

                    // Cancellation policy
                    if (in_array($row->getStatus(), array('9', '10'))) {
                        $cancelInfo = $textlineService->getUniversalTextline(1081);
                    } elseif (in_array($row->getStatus(), array('11'))) {
                        $cancelInfo = 'Cancelled as test booking.';
                    } else {
                        switch ($row->getIsRefundable()) {
                            case '1':
                                switch ($row->getPenalty()) {
                                    case '1':
                                        $penaltyVal = $row->getPenaltyVal().'%';
                                        break;
                                    case '2':
                                        $penaltyVal = number_format($row->getPenaltyVal(), 2, '.', ' ').' '.$row->getApartmentCurrencyCode();
                                        break;
                                    case '3':
                                        $penaltyVal = $row->getPenaltyVal().' '.$textlineService->getUniversalTextline(862);
                                        break;
                                }

                                if ((int)$row->getRefundableBeforeHours() > 48) {
                                    $time = ((int)$row->getRefundableBeforeHours()/24).' '.$textlineService->getUniversalTextline(977);
                                } else {
                                    $time = $row->getRefundableBeforeHours().' '.$textlineService->getUniversalTextline(976);
                                }
                                $cancelInfo = Helper::evaluateTextline($textlineService->getUniversalTextline(859),
                                    [
                                        '{{CXL_PENALTY}}' => $penaltyVal,
                                        '{{CXL_TIME}}' => $time,
                                    ]);
                                break;

                            case '2':
                                $cancelInfo = $textlineService->getUniversalTextline(861);
                                break;
                        }
                    }

                    if ($row->getApartmentCurrencyCode() == 'AMD') {
                        $totalPrice = (int) $row->getPrice() . ' ' . $row->getApartmentCurrencyCode();
                        $penalty    = (int) $row->getPenaltyFixedAmount() . ' ' . $row->getApartmentCurrencyCode();
                    } else {
                        $totalPrice = number_format($row->getPrice(), 2, '.', ' ') . ' ' . $row->getApartmentCurrencyCode();
                        $penalty    = number_format($row->getPenaltyFixedAmount(), 2, '.', ' ') . ' ' . $row->getApartmentCurrencyCode();
                    }

                    // only for ginosi cancellation
                    if ($row->getStatus() == Booking::BOOKING_STATUS_CANCELLED_BY_GINOSI || $row->getStatus() == Booking::BOOKING_STATUS_CANCELLED_EXCEPTION) {
                        $penalty = '0 ' . $row->getApartmentCurrencyCode();
                    }

                    if ($this->ginosi) {
                        // send to Ginosi
                        $msg = 'Sending cancellation mail to Ginosi - '.EmailAliases::TO_CANCELLATION
                            .' for reservation '.$row->getResNumber()
                            .', id '.$row->getId();

                        $this->outputMessage($msg);

                        $mailer->send(
                            'cancellation',
                            [
                                'layout'             => 'layout-new',
                                'to'                 => EmailAliases::TO_CANCELLATION,
                                'to_name'            => 'Ginosi Apartments',
                                'replyTo'            => EmailAliases::RT_CANCELLATION,
                                'from_address'       => EmailAliases::FROM_MAIN_MAIL,
                                'from_name'          => 'Ginosi Apartments',
                                'subject'            => $subject,
                                'title'              => $title,
                                'reservationId'      => $row->getResNumber(),
                                'checkInDate'        => $checkInDate,
                                'checkOutDate'       => $checkOutDate,
                                'totalNight'         => $interval->format('%d'),
                                'totalPrice'         => $totalPrice,
                                'cancelTitle'        => $cancelTitle,
                                'cancelInfo'         => $cancelInfo,
                                'penalty'            => $penalty,
                                'phone1'             => $phone1,
                                'phone2'             => $phone2,
                            ]);

                        $this->gr2info('Sending cancellation mail to Ginosi',
                            [
                                'reservation_number' => $row->getResNumber(),
                                'reservation_id' => $row->getId()
                            ]);
                    }

                    if ($this->booker) {
                        // collect for sending to Customer
                        if (   $partner->getCustomerEmail() === '1'
                            && $row->getOverbookingStatus() === '0'
                        ){
                            $guestEmail = $this->email ? $this->email : $row->getGuestEmail();
                            if (!$emailValidator->isValid($guestEmail)) {
                                echo '[error] Email is not valid ' . $guestEmail . '. Reservation ' . $row->getResNumber() . PHP_EOL;

                                $this->gr2crit("Cancellation mail wasn't sent to Customer", [
                                    'apartment_id'       => $row->getApartmentIdAssigned(),
                                    'reservation_number' => $row->getResNumber(),
                                    'reservation_id'     => $row->getId(),
                                    'reason'             => 'Email is not valid'
                                ]);

                                continue;
                            }

                            $title = Helper::evaluateTextline($textlineService->getUniversalTextline(1046),
                                [
                                    '{{RES_NUMBER}}' => $row->getResNumber(),
                                    '{{GUEST_NAME}}' => $row->getGuestFirstName() . ' ' . $row->getGuestLastName()
                                ]);

                            $msg = 'Sending cancellation mail to Customer '. $guestEmail
                                .' for reservation '.$row->getResNumber()
                                .', id '.$row->getId();

                            $this->outputMessage($msg);

                            // analytics image
                            $analyticsCode  = $this->getAnalyticsCode();
                            $analyticsImage = Helper::evaluateTextline($textlineService->getUniversalTextline(1681),
                                [
                                    '{{ANALYTICS_CODE}}'       => $analyticsCode,
                                    '{{ANALYTICS_RES_NUMBER}}' => $row->getResNumber(),
                                    '{{ANALYTICS_TEMPLATE}}'   => 'cancellation',
                                    '{{ANALYTICS_PARTNER_ID}}' => $row->getPartnerId(),
                                ]);
                            $analyticsQuery  = Helper::evaluateTextline($textlineService->getUniversalTextline(1682),
                                [
                                    '{{ANALYTICS_TEMPLATE}}'   => 'key-instruction',
                                ]);

                            // send to Guest
                            $mailer->send(
                                'cancellation',
                                array(
                                    'layout'            => 'layout-new',
                                    'analyticsQuery'    => $analyticsQuery,
                                    'to'                => $guestEmail,
                                    'to_name'           => $row->getGuestFirstName().' '.$row->getGuestLastName(),
                                    'replyTo'           => EmailAliases::RT_CANCELLATION,
                                    'from_address'      => EmailAliases::FROM_MAIN_MAIL,
                                    'from_name'         => 'Ginosi Apartments',
                                    'subject'           => $subject,
                                    'title'             => $title,
                                    'reservationId'     => $row->getResNumber(),
                                    'checkInDate'       => $checkInDate,
                                    'checkOutDate'      => $checkOutDate,
                                    'totalNight'        => $interval->format('%d'),
                                    'totalPrice'        => $totalPrice,
                                    'cancelTitle'       => $cancelTitle,
                                    'cancelInfo'        => $cancelInfo,
                                    'penalty'           => $penalty,
                                    'phone1'            => $phone1,
                                    'phone2'            => $phone2,
                                    'analyticsImage'    => $analyticsImage,
                                ));

                            $this->gr2info('Sending cancellation mail to Customer',
                                [
                                    'reservation_number' => $row->getResNumber(),
                                    'reservation_id' => $row->getId()
                                ]);
                        }
                    }

                    if (!$this->booker && !$this->ginosi) {
                        echo 'Please select to whom send an email. (--ginosi or/and --booker)'.PHP_EOL;
                    }
                }

            } else {
                echo 'Not found modification for sending.'.PHP_EOL;
            }

        } catch (\Exception $e) {
            $msg = "[error]Error: Cancellation mail wasn't sent.";

            $this->gr2logException($e, "Cancellation mail wasn't sent", [
                'apartment_id'       => isset($row) ? $row->getApartmentIdAssigned() : '',
                'reservation_number' => isset($row) ? $row->getResNumber() : '',
                'reservation_id'     => isset($row) ? $row->getId() : ''
            ]);

            $this->outputMessage($msg . ' ' . $e->getMessage());
            return FALSE;
        }
    }

    public function sendModificationGinosiAction()
    {
        try {
            $bookings = $this->showModificationAction('send-modification-ginosi');

            if ($bookings === false) {
                echo 'There is no reservation to modify for';

                exit;
            }

            /**
             * @var \DDD\Service\Textline $textlineService
             * @var \DDD\Service\Booking $bookingService
             */
            $textlineService = $this->getServiceLocator()->get('service_textline');
            $bookingService = $this->getServiceLocator()->get('service_booking');

            $serviceLocator = $this->getServiceLocator();
            $mailer = $serviceLocator->get('Mailer\Email');
            $emailValidator = new EmailAddress();

            $resNumbers = array();

            foreach ($bookings as $row) {
                $resNumber = explode('-', $row->getResNumber());

                $phone1 = $row->getPhone1();
                $phone2 = $row->getPhone2();

                if (!in_array($resNumber[0], $resNumbers)) {

                    if (!$emailValidator->isValid($row->getGuestEmail())) {
                        $bookingService->setBookingStatusIsQueueAsError($row->getId());

                        echo '[error] Email is not valid ' . $row->getGuestEmail() . '. Reservation ' . $row->getResNumber() . PHP_EOL;

                        $this->gr2crit("Modification mail wasn't sent to Customer", [
                            'apartment_id'       => $row->getApartmentIdAssigned(),
                            'reservation_number' => $row->getResNumber(),
                            'reservation_id'     => $row->getId(),
                            'reason'             => 'Email is not valid'
                        ]);

                        continue;
                    }

                    $title = 'Reservation '.$row->getResNumber().' has been modified.';

                    $modified = $row->getResNumber().' reservation will be updated and no email will be sent to the guest.';
                    if ($this->dates_shifted) {
                        $modified .= '<br><font style="color: rgb(196,20,20);"><b>Because of the dates changes a new reservation has been registered in backoffice, this reservation and availabilities must be checked. No email will be sent to the guest.</b></font>';
                    }

                    if ($this->cc_provided) {
                        $ccProvided = '<b>Credit Card Provided</b>';
                    } else {
                        $ccProvided = '<b>No Credit Card Provided</b>';
                    }

                    if ($row->getChannelName() === 'Cubilis') {
                        $cubilis = 'This transaction is via Cubilis and has been registered in Ginosi Backoffice';
                    } else {
                        $cubilis = FALSE;
                    }

                    if ($row->getOverbookingStatus() === '1') {
                        $overbooking = 'An overbooking has occurred, no emails will be sent to the guest!';
                    } else {
                        $overbooking = FALSE;
                    }

                    /**
                     * @var \DDD\Service\Accommodations $accommodationsService
                     */
                    $accommodationsService = $this->getServiceLocator()->get('service_accommodations');
                    $accGeneral = $accommodationsService->getAppartmentFullAddressByID($row->getApartmentIdAssigned());

                    $address = '<span style="font-weight: bold;">'
                        .$textlineService->getUniversalTextline(785)
                        .':</span> '.$accGeneral->getAddress()
                        .', '.$accGeneral->getPostalCode()
                        .' '.$textlineService->getCityName($row->getApartmentCityId())
                        .' '.$textlineService->getCountryName($row->getApartmentCountryId());

                    $guestName = '<span style="font-weight: bold;">'
                        .$textlineService->getUniversalTextline(740)
                        .':</span> '.$row->getGuestFirstName()
                        .' '.$row->getGuestLastName();

                    $guestEmail = '<span style="font-weight: bold;">'
                        .$textlineService->getUniversalTextline(635)
                        .':</span> '.str_replace(['@','.'], ['[at]','[dot]'], $row->getGuestEmail());

                    $guestPhone = '<span style="font-weight: bold;">'
                        .$textlineService->getUniversalTextline(649)
                        .':</span> '.$row->getGuestPhone();

                    $today = new \DateTime();
                    $dateIn = new \DateTime($row->getDateFrom());
                    $interval = $today->diff($dateIn);

                    if ($interval->format('%y') === '0'
                        && $interval->format('%m') === '0'
                        && $interval->format('%d') === '0') {
                        $chInStyle = 'color: rgb(196, 20, 20); text-decoration: underline; font-weight: bold;';
                    } else {
                        $chInStyle = '';
                    }

                    $checkInDate = '<font style="'.$chInStyle.'"><span style="font-weight: bold;">'
                        .$textlineService->getUniversalTextline(706)
                        .':</span> '.$row->getDateFrom().'</font>';

                    $checkOutDate = '<span style="font-weight: bold;">'
                        .$textlineService->getUniversalTextline(707)
                        .':</span> '.$row->getDateTo();

                    if ($row->getGuestArrivalTime() !== '00:00:00'
                        && $this->getDeltaTime($row->getGuestArrivalTime(), $row->getCheckIn()) < 0) {
                        $arTimeStyle = 'color: rgb(196, 20, 20); text-decoration: underline; font-weight: bold;';
                    } else {
                        $arTimeStyle = '';
                    }

                    $checkInTime = '<span style="font-weight: bold;">'
                        .$textlineService->getUniversalTextline(1101)
                        .':</span> '.$row->getCheckIn();

                    if ($row->getGuestArrivalTime() === '00:00:00') {
                        $arrivalTime = '';
                    } else {
                        $arrivalTime = '<font style="'.$arTimeStyle.'"><span style="font-weight: bold;">'
                            .$textlineService->getUniversalTextline(743)
                            .':</span> '.$row->getGuestArrivalTime().'</font>';
                    }

                    $dateIn     = new \DateTime($row->getDateFrom());
                    $dateOut    = new \DateTime($row->getDateTo());
                    $interval   = $dateIn->diff($dateOut);

                    if ($interval->format('%d') === '1') {
                        $totalNight = '<span style="font-weight: bold;">'
                            .$textlineService->getUniversalTextline(708)
                            .' '.$textlineService->getUniversalTextline(730).':</span> '.$interval->format('%d');
                    } else {
                        $totalNight = '<span style="font-weight: bold;">'
                            .$textlineService->getUniversalTextline(708)
                            .' '.$textlineService->getUniversalTextline(669).':</span> '.$interval->format('%d');
                    }

                    $remarks = '<span style="font-weight: bold;">'
                        .$textlineService->getUniversalTextline(868)
                        .':</span> '.$row->getRemarks();


                    $roomParams = $textlineService->getUniversalTextline(723)
                        .': <span style="font-weight: bold;">'
                        .$row->getResNumber().'</span><br>'
                        .$textlineService->getUniversalTextline(710)
                        .': <span style="font-weight: bold;">'
                        .$row->getApartmentName().'</span><br>'
                        .$textlineService->getUniversalTextline(389)
                        .': <span style="font-weight: bold;">'
                        .$row->getRateName().'</span><br>';
                    $roomParams .= $textlineService->getUniversalTextline(733)
                        .' '.$textlineService->getUniversalTextline(711)
                        .': <span style="font-weight: bold;">'
                        .'1</span><br>'
                        .' '.$textlineService->getUniversalTextline(711)
                        .': <span style="font-weight: bold;">'
                        .'1</span><br>';
                    $roomParams .= $textlineService->getUniversalTextline(745).
                        ': <span style="font-weight: bold;">'
                        .$row->getPAX().'</span><br>';

                    $penaltyVal = 0;
                    if ($row->getIsRefundable() === '1') {
                        switch ($row->getPenalty()) {
                            case '1':
                                $penaltyVal = $row->getPenaltyVal().'%';
                                break;
                            case '2':
                                $penaltyVal = number_format($row->getPenaltyVal(), 2, '.', ' ').' '.$row->getApartmentCurrencyCode();
                                break;
                            case '3':
                                $penaltyVal = $row->getPenaltyVal().' '.$textlineService->getUniversalTextline(862);
                                break;
                        }

                        if ((int)$row->getRefundableBeforeHours() > 48) {
                            $time = ((int)$row->getRefundableBeforeHours()/24).' '.$textlineService->getUniversalTextline(977);
                        } else {
                            $time = $row->getRefundableBeforeHours().' '.$textlineService->getUniversalTextline(976);
                        }
                        $cancelInfo = Helper::evaluateTextline($textlineService->getUniversalTextline(859),
                            [
                                '{{CXL_PENALTY}}' => $penaltyVal,
                                '{{CXL_TIME}}' => $time,
                            ]);
                    } else {
                        $cancelInfo = $textlineService->getUniversalTextline(861);
                    }

                    $ticketLink = 'http://'
                        .DomainConstants::BO_DOMAIN_NAME
                        .'/booking/edit/'
                        .$row->getResNumber();

                    $totalPrice = $textlineService->getUniversalTextline(672)
                        .': '.number_format(floatval($row->getPrice()), 2, '.', ' ')
                        .' '.$row->getApartmentCurrencyCode();

                    $mailSubject = Helper::evaluateTextline($textlineService->getUniversalTextline(947, true),
                        [
                            '{{RES_NUMBER}}' => $row->getResNumber(),
                            '{{GUEST_NAME}}' => $row->getGuestFirstName().' '.$row->getGuestLastName()
                        ]);

                    $productName = '<a href="'.DomainConstants::BO_DOMAIN_NAME.'/apartment/'
                        .$row->getApartmentIdAssigned().'/calendar" style="color: rgb(0,120,164);">'
                        .$row->getApartmentName()
                        .'</a>';

                    $mailSubject = 'Modification: '.preg_replace('/\s+/', ' ', trim($mailSubject))
                        .' ('.$row->getDateFrom().' — '.$row->getDateTo().')';

                    $analyticsQuery  = Helper::evaluateTextline($textlineService->getUniversalTextline(1682),
                        [
                            '{{ANALYTICS_TEMPLATE}}'   => 'key-instruction',
                        ]);

                    $mailer->send(
                        'modification-ginosi',
                        array(
                            'analyticsQuery'    => $analyticsQuery,
                            'to'                => EmailAliases::TO_RESERVATION,
                            'to_name'           => 'Ginosi Apartments',
                            'replyTo'           => EmailAliases::RT_RESERVATION,
                            'from_address'      => EmailAliases::FROM_MAIN_MAIL,
                            'from_name'         => 'Ginosi Apartments',
                            'subject'           => $mailSubject,
                            'title'             => $title,
                            'creditCard'        => $ccProvided,
                            'cubilis'           => $cubilis,
                            'modified'          => $modified,
                            'overbooking'       => $overbooking,
                            'resDetails'        => $textlineService->getUniversalTextline(705),
                            'productName'       => $productName,
                            'address'           => $address,
                            'guestName'         => $guestName,
                            'guestEmail'        => $guestEmail,
                            'guestPhone'        => $guestPhone,
                            'checkInDate'       => $checkInDate,
                            'checkOutDate'      => $checkOutDate,
                            'checkInTime'       => $checkInTime,
                            'arrivalTime'       => $arrivalTime,
                            'totalNight'        => $totalNight,
                            'remarks'           => $remarks,
                            'roomParams'        => $roomParams,
                            'ticketLink'        => $ticketLink,
                            'cancelTitle'       => $textlineService->getUniversalTextline(712),
                            'cancelInfo'        => $cancelInfo,
                            'totalPrice'        => $totalPrice,
                            'phone1'            => $phone1,
                            'phone2'            => $phone2,
                            'textLine1478'      => $textlineService->getUniversalTextline(1478),
                            'textLine1479'      => $textlineService->getUniversalTextline(1479)
                        ));

                    $msg = 'Sending modification mail to '.EmailAliases::TO_RESERVATION
                        .' for reservation '.$row->getResNumber()
                        .', id '.$row->getId();

                    $this->outputMessage($msg);

                    $this->gr2info('Sending modification mail to Ginosi', [
                        'reservation_number' => $row->getResNumber(),
                        'reservation_id' => $row->getId()
                    ]);
                }
            }
            return TRUE;
        } catch (\Exception $e) {
            $msg = "[error]Error: Modification mail wasn't sent.";

            $this->gr2logException($e, "Modification mail wasn't sent", [
                'apartment_id'       => isset($row) ? $row->getApartmentIdAssigned() : '',
                'reservation_number' => isset($row) ? $row->getResNumber() : '',
                'reservation_id'     => isset($row) ? $row->getId() : ''
            ]);

            $this->outputMessage($msg . ' ' . $e->getMessage());
            return FALSE;
        }
    }

    public function sendReceiptAction()
    {
        /**
         * @var \DDD\Service\Booking\BookingTicket $bookingService
         */
        try {
            $serviceLocator = $this->getServiceLocator();
            $mailer = $serviceLocator->get('Mailer\Email');
            $bookingService = $serviceLocator->get('service_booking_booking_ticket');
            $reservationId = $this->id;

            if (!$reservationId) {
                $this->gr2err("Reservation ID missing",[
                    'reason' => 'Reservation Id not found'
                ]);
                return false;
            }

            $receiptData    = $bookingService->getReceiptData($reservationId);
            $reservation    = $receiptData['reservation'];
            $emailValidator = new EmailAddress();

            $email = $reservation['guest_email'];

            if ($this->getRequest()->getParam('email')) {
                $email = $this->getRequest()->getParam('email');
            }

            if ($receiptData['status'] != 'success' || !$emailValidator->isValid($email)) {
                $this->gr2err('Receipt status or mail is not correct', [
                    'reservation_id'     => $reservationId,
                    'reservation_number' => $reservation['res_number']
                ]);
                return false;
            }

            /**
             * @var \DDD\Service\Textline $textlineService
             */
            $textlineService = $this->getServiceLocator()->get('service_textline');

            $mailSubject        = 'Receipt for Transaction: ' . $reservation['res_number'];
            $title              = 'Receipt';
            $reservationDetails = 'Reservation Details';
            $financeDetails     = 'Finance Details';
            $receiptThankYou    = TextConstants::RECEIPT_THANK_YOU;

            // added by me
            $reservationId     = $reservation['id'];
            $reservationNumber = $reservation['res_number'];
            $receiptIssueDate  = $receiptData['today'];
            $customerName      = $reservation['guest_first_name'] . ' ' . $reservation['guest_last_name'];
            $customerAddress   = $reservation['guest_address'];
            $checkInDate       = date(Constants::GLOBAL_DATE_TIME_WO_SEC_FORMAT, strtotime($reservation['date_from'] . $reservation['check_in']));
            $checkOutDate      = date(Constants::GLOBAL_DATE_TIME_WO_SEC_FORMAT, strtotime($reservation['date_to'] . $reservation['check_out']));
            $apartmentName     = $reservation['apartment_name'];
            $apartmentAddress  = $reservation['apartment_address'];

            $totalAmountToPay = 0;
            $nightsCount      = 0;
            $taxes            = [];
            if ($receiptData['charges'] && $receiptData['charges']) {
                foreach ($receiptData['charges'] as $charge) {
                    // calculate taxes
                    if ($charge['addon']) {
                        if ($charge['addons_type'] == 1) {
                            $chargeKey = ucfirst($textlineService->getUniversalTextline(669));
                            $nightsCount++;
                        } else {
                            $chargeKey = $charge['addon'];
                        }
                    } elseif ($charge['type'] == 'p') {
                        $chargeKey = 'Penalty';
                    } elseif ($charge['type'] == 'g') {
                        $chargeKey = 'Penalty Ginosi';
                    } else {
                        $chargeKey = 'Other';
                    }

                    if($charge['addons_value'] > 0 && $charge['location_join'] != '') {
                        $chargeKey .= ' ' . $charge['addons_value'];
                        if($charge['tax_type'] == Taxes::TAXES_TYPE_PERCENT) {
                            $chargeKey .= ' %';
                        } elseif ($charge['tax_type'] == Taxes::TAXES_TYPE_PER_NIGHT) {
                            $chargeKey .= ' p/n';
                        } elseif ($charge['tax_type'] == Taxes::TAXES_TYPE_PER_PERSON) {
                            $chargeKey .= ' p/p';
                        }
                    }

                    if (isset($taxes[$chargeKey])) {
                        $taxes[$chargeKey] += $charge['acc_amount'];
                    } else {
                        $taxes[$chargeKey] = $charge['acc_amount'];
                    }

                    // calculate all amount to pay
                    $totalAmountToPay += $charge['acc_amount'];
                }
            }

            $totalAmountToPaid = 0;
            if ($receiptData['transactions'] && $receiptData['transactions']->count()) {
                foreach ($receiptData['transactions'] as $key => $transaction) {
                    $totalAmountToPaid += $transaction['acc_amount'];
                }
            }

            $totalAmountPaid = $totalAmountToPaid;
            $balance         = number_format(($totalAmountToPay - $totalAmountToPaid), 2, '.', '');

            // analytics image
            $analyticsCode  = $this->getAnalyticsCode();
            $analyticsImage = Helper::evaluateTextline($textlineService->getUniversalTextline(1681),
                [
                    '{{ANALYTICS_CODE}}'       => $analyticsCode,
                    '{{ANALYTICS_RES_NUMBER}}' => $reservationNumber,
                    '{{ANALYTICS_TEMPLATE}}'   => 'receipt',
                    '{{ANALYTICS_PARTNER_ID}}' => $reservation['partner_id'],
                ]);
            $analyticsQuery  = Helper::evaluateTextline($textlineService->getUniversalTextline(1682),
                [
                    '{{ANALYTICS_TEMPLATE}}'   => 'key-instruction',
                ]);

            $mailer->send(
                'receipt',
                array(
                    'layout'             => 'layout-new',
                    'analyticsQuery'     => $analyticsQuery,
                    'to'                 => $email,
                    'to_name'            => $reservation['guest_first_name'] . ' ' . $reservation['guest_last_name'],
                    'replyTo'            => EmailAliases::RT_RESERVATION,
                    'from_address'       => EmailAliases::FROM_MAIN_MAIL,
                    'from_name'          => 'Ginosi Apartments',
                    'subject'            => $mailSubject,
                    'title'              => $title,
                    'reservationDetails' => $reservationDetails,
                    'financeDetails'     => $financeDetails,
                    'receiptThankYou'    => $receiptThankYou,
                    'data'               => $receiptData,
                    'reservationId'      => $reservationId,
                    'reservationNumber'  => $reservationNumber,
                    'reservationSymbol'  => $reservation['symbol'],
                    'receiptIssueDate'   => $receiptIssueDate,
                    'customerName'       => $customerName,
                    'customerAddress'    => $customerAddress,
                    'checkInDate'        => $checkInDate,
                    'checkOutDate'       => $checkOutDate,
                    'apartmentName'      => $apartmentName,
                    'apartmentAddress'   => $apartmentAddress,
                    'nightsCount'        => $nightsCount,
                    'taxes'              => $taxes,
                    'totalAmountPaid'    => $totalAmountPaid,
                    'balance'            => $balance,
                    'phone1'             => $reservation['phone1'],
                    'phone2'             => $reservation['phone2'],
                    'analyticsImage'     => $analyticsImage
                ));

            $this->gr2info('Sending Customer Receipt mail', [
                'reservation_number' => $reservation['res_number'],
            ]);


            return true;
        } catch (\Exception $e) {
            $msg = "[error]Error: Receipt mail wasn't sent";
            $this->gr2logException($e, "Receipt mail wasn't sent", [
                'reservation_number' => isset($reservation) ? $reservation['res_number'] : '',
                'reservation_id'     => isset($reservationId) ? $reservationId : '',
            ]);

            $this->outputMessage($msg . ' ' . $e->getMessage());

            return false;
        }
    }

    private function getDeltaTime($dtTime1, $dtTime2)
    {
        $dtTime1 = new \DateTime($dtTime1);
        $dtTime2 = new \DateTime($dtTime2);

        $nUXDate1 = strtotime($dtTime1->format("H:i:s"));
        $nUXDate2 = strtotime($dtTime2->format("H:i:s"));

        $nUXDelta = $nUXDate1 - $nUXDate2;
        $strDeltaTime = "" . $nUXDelta/60/60; // sec -> hour

        $nPos = strpos($strDeltaTime, ".");
        if ($nPos !== false) {
            $strDeltaTime = substr($strDeltaTime, 0, $nPos + 3);
        }

        return (int)$strDeltaTime;
    }

    private function getAnalyticsCode()
    {
        $config = $this->getServiceLocator()->get('config');

        $googleAnalyticsCode = $config['google-analytics']['code'];

        return $googleAnalyticsCode;
    }
}
