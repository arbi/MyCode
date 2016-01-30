<?php

namespace DDD\Service\Website;

use CreditCard\Service\Card;
use DDD\Domain\Booking\ChargeProcess;
use DDD\Service\ApartmentGroup;
use DDD\Service\Booking\BookingTicket;
use DDD\Service\Booking\Charge as BookingCharge;
use DDD\Service\Booking\BookingAddon;
use DDD\Service\ChannelManager;
use DDD\Service\Finance\Customer;
use DDD\Service\PenaltyCalculation;
use DDD\Service\ServiceBase;
use DDD\Service\Taxes;
use DDD\Service\Apartment\Rate as ApartmentRate;
use DDD\Service\Partners as PartnerService;

use DDD\Dao\Geolocation\Countries;
use DDD\Dao\Booking\Partner;
use DDD\Dao\Location\City;

use Library\ActionLogger\Logger;
use Library\Constants\Objects;
use Library\Finance\CreditCard\CreditCard;
use Library\Finance\CreditCard\CreditCardValidator;
use Library\Validator\ClassicValidator;
use Library\Utility\Helper;
use Library\Constants\WebSite;
use Library\Utility\Currency;

use Zend\Session\Container;

class Booking extends ServiceBase
{
    /**
     * @param string $code
     * @return \ArrayObject|null
     */
    public function getReservationForVerification($code)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \ArrayObject());

        return $bookingDao->getReservationForVerification($code);
    }

    /**
     * @param string $keyCode
     * @return \DDD\Domain\Booking\KeyInstructionPage|bool
     */
    public function getReservationByKeyCode($keyCode)
    {
        return $this->getServiceLocator()->get('dao_booking_booking')->getBookingTicketByKeyCode($keyCode);
    }

    /**
     * By 'review_page_hash' field
     *
     * @param string $reviewPageHash
     * @return \DDD\Domain\Booking\ReviewPage
     */
    public function getReservationByReviewCode($reviewPageHash)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\ReviewPage());

        return $bookingDao->getBookingTicketByReviewCode($reviewPageHash);
    }

    /**
     * @param int $id
     * @param array $data
     *
     * @return int
     */
    public function updateData($id, $data)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\KeyInstructionPage());

        return $bookingDao->save($data, ['id' => $id]);
    }

    /**
     * @param array $data
     * @return array
     */
    public function bookingReservationData($data)
    {
        $apartmentService = $this->getServiceLocator()->get('service_website_apartment');
        $currencyDao      = $this->getServiceLocator()->get('dao_currency_currency');
        $filter           = $this->filterReservationData($data);

        if (!$filter) {
            return ['status' => 'error'];
        }

        $inventoryDao    = $this->getInventoryDao();
        $arrival         = date('Y-m-d', strtotime($data['arrival']));
        $departure       = date('Y-m-d', strtotime($data['departure']));
        $occupancy       = $data['guest'];
        $bookNightCount  = Helper::getDaysFromTwoDate($arrival, $departure);
        $checkNightCount = Helper::getDaysFromTwoDate($arrival, date('Y-m-d'));
        $result          = $inventoryDao->bookingReservationData($data['rate-for-booking'], $arrival, $departure, $data['guest'], $bookNightCount, $checkNightCount);

        if (!$result) {
            return ['status' => 'not_av'];
        }

        $session_booking = Helper::getSessionContainer('booking');

        // Clear Booking Data
        $session_booking->getManager()->getStorage()->clear('booking');

        // Media
        $img = Helper::getImgByWith($result['img1'], WebSite::IMG_WIDTH_SEARCH, true, true);

        if ($img) {
            $result['image'] = $img;
        }

        // Date view user
        $result['from'] = $data['arrival'];
        $result['to'] = $data['departure'];

        // Date
        $result['date_from']   = $arrival;
        $result['date_to']     = $departure;
        $result['guest']       = $data['guest'];
        $result['night_count'] = $bookNightCount;

        // Change currency
        $userCurrency   = $this->getCurrencySite();
        $currencySymbol = WebSite::DEFAULT_CURRENCY;
        $currencyResult = $currencyDao->fetchOne(['code' => $userCurrency]);
        $result['acc_price'] = $result['amount_price'];

        if ($currencyResult) {
            $currencySymbol = $currencyResult->getSymbol();
        }

        if ($userCurrency != $result['code']) {
            $currencyUtility    = new Currency($currencyDao);
            $price = $currencyUtility->convert($result['amount_price'], $result['code'], $userCurrency);
            $result['amount_price'] = $price;
            $result['symbol'] = $currencySymbol;
        }
        $result['user_currency'] = $userCurrency;
        // Price calculate
        $result['acc_currency'] = $result['code'];
        $result['apartment_id'] = $result['prod_id'];
        $payments = $this->getPaymentsData($result, $occupancy);
        $result['payments'] = $payments;

        //cancellation policy
        $cancellationPolicy = $apartmentService->cancelationPolicy($result);
        $result['cancelation_type']   = $cancellationPolicy['type'];
        $result['cancelation_policy'] = $cancellationPolicy['description'];

        //set reservation data in to session
        $session_booking->reservation = $result;

        return [
            'status' => 'success',
            'result' => $session_booking->reservation,
        ];
    }

    /**
     * @param $data
     * @param $occupancy
     * @return ChargeProcess
     */
    private function getPaymentsData($data, $occupancy)
    {
        $charge = new ChargeProcess();
        $charge->setPrice($data['amount_price']);
        $visitor = new Container('visitor');
        if (!is_null($visitor->partnerId) && (int)$visitor->partnerId) {
            $partnerId    = (int)$visitor->partnerId;
            $charge->setPartnerId($partnerId);
        }
        $charge->setDateTo($data['date_to']);
        $charge->setDateFrom($data['date_from']);
        $charge->setGuestCurrency($data['user_currency']);
        $charge->setCountryCurrency($data['country_currency']);
        if (isset($data['tot_type']) && $data['tot_type'] > 0 && $data['tot'] > 0) {
            $charge->setCityTot($data['tot'] + $data['tot_additional']);
            $charge->setCityTotType($data['tot_type']);
            $charge->setTotIncluded($data['tot_included']);
            $charge->setTotMaxDuration($data['tot_max_duration']);
        }

        if (isset($data['vat_type']) && $data['vat_type'] > 0 && $data['vat'] > 0) {
            $charge->setCityVat($data['vat'] + $data['vat_additional']);
            $charge->setCityVatType($data['vat_type']);
            $charge->setVatIncluded($data['vat_included']);
            $charge->setVatMaxDuration($data['vat_max_duration']);
        }

        if (isset($data['sales_tax_type']) && $data['sales_tax_type'] > 0 && $data['sales_tax'] > 0) {
            $charge->setCitySalesTax($data['sales_tax'] + $data['sales_tax_additional']);
            $charge->setCitySalesTaxType($data['sales_tax_type']);
            $charge->setSalesTaxIncluded($data['sales_tax_included']);
            $charge->setSalesTaxMaxDuration($data['sales_tax_max_duration']);
        }

        if (isset($data['city_tax_type']) && $data['city_tax_type'] > 0 && $data['city_tax'] > 0) {
            $charge->setCityTax($data['city_tax'] + $data['city_tax_additional']);
            $charge->setCityTaxType($data['city_tax_type']);
            $charge->setCityTaxIncluded($data['city_tax_included']);
            $charge->setCityTaxMaxDuration($data['city_tax_max_duration']);
        }

        $charge->setOccupancy($occupancy);
        $charge->setApartmentId($data['apartment_id']);
        $charge->setApartmentCurrency($data['acc_currency']);
        $charge->setCurrencySymbol($data['symbol']);
        $charge->setCheckCurrency(true);

        /** @var \DDD\Service\Booking\Charge $chargeService */
        $chargeService = $this->getServiceLocator()->get('service_booking_charge');
        return $chargeService->getToBeChargedItems($charge);
    }

    /**
     * @param array $userAndPayData
     * @return array
     */
    public function bookingProcess($userAndPayData)
    {
        /**
         * @var BookingTicket $bookingTicketService
         * @var ChannelManager $channelManagerService
         * @var PenaltyCalculation $penaltyService
         * @var ApartmentGroup $apartmentGroupService
         * @var \DDD\Service\Finance\Customer $customerFinanceService
         * @var Logger $logger
         * @var \DDD\Service\Reservation\Main $reservationService
         * @var \DDD\Service\Partners $partnerService
         * @var \DDD\Service\Queue\InventorySynchronizationQueue $syncService
         */

        $bookingTicketService      = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $channelManagerService     = $this->getServiceLocator()->get('service_channel_manager');
        $apartmentGroupService     = $this->getServiceLocator()->get('service_apartment_group');
        $reservationService        = $this->getServiceLocator()->get('service_reservation_main');
        $partnerService            = $this->getServiceLocator()->get('service_partners');
        $syncService               = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');
        $session_booking           = Helper::getSessionContainer('booking');

        if (empty($userAndPayData) || !$session_booking->offsetExists('reservation')) {
            return ['status' => 'error'];
        }
        $reservationData = $session_booking->reservation;

        //bookingProcess
        $data = $tankYou = [];
        $data['apartment_id_assigned'] = $reservationData['prod_id'];
        $data['apartment_id_origin']   = $reservationData['prod_id'];
        $data['room_id']               = $reservationData['room_id'];
        $data['acc_name']              = $reservationData['prod_name'];
        $data['acc_country_id']        = $reservationData['country_id'];
        $data['acc_province_id']       = $reservationData['province_id'];
        $data['acc_city_id']           = $reservationData['city_id'];
        $data['acc_province_name']     = $reservationData['province_name'];
        $data['acc_city_name']         = $reservationData['city_name'];
        $data['acc_address']           = $reservationData['address'];
        $data['building_name']         = $apartmentGroupService->getBuildingName($reservationData['prod_id']);
        $data['date_from']             = $reservationData['date_from'];
        $data['date_to']               = $reservationData['date_to'];

        // Currency Rate
        /** @var \DDD\Service\Currency\Currency $currencyService */
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');
		$data['currency_rate']      = $currencyService->getCurrencyConversionRate($this->getCurrencySite(), $reservationData['code']);
		$data['currency_rate_usd']  = $currencyService->getCurrencyConversionRate('USD', $reservationData['code']);

		$data['booker_price']       = $reservationData['amount_price'];
		$data['guest_currency_code']= $this->getCurrencySite();
		$data['occupancy']          = $reservationData['guest'];
        $resNumber                  = $tankYou['res_number'] = $bookingTicketService->generateResNumber();
		$data['res_number']         = $resNumber;
        $data['timestamp']          = date('Y-m-d H:i:s');
		$data['apartment_currency_code'] = $reservationData['code'];
		$data['ki_page_status']     = BookingTicket::NOT_SEND_KI_PAGE_STATUS;

		$data['ki_page_hash']       = $bookingTicketService->generatePageHash($data['res_number'], $data['timestamp']);
        $data['review_page_hash']   = $bookingTicketService->generatePageHash($data['res_number'], $data['apartment_id_origin']);

        $data['remarks']            = (isset($userAndPayData['remarks']) ? $userAndPayData['remarks'] : '');
        if (isset($reservationData['arrival_time']) && !empty($reservationData['arrival_time'])) {
            $data['guest_arrival_time'] = $reservationData['arrival_time'];
        }

        // Affiliate Data
        $webSiteAff = WebSite::WEB_SITE_PARTNER;
        $visitor    = new Container('visitor');

        if (
            isset($userAndPayData['aff-id']) &&
            (int)$userAndPayData['aff-id'] > 0 &&
            $userAndPayData['aff-id'] != WebSite::WEB_SITE_PARTNER &&
            Helper::isBackofficeUser()
        ) {
            $webSiteAff = $userAndPayData['aff-id'];

        } elseif(!is_null($visitor->partnerId) && (int)$visitor->partnerId) {
            $webSiteAff = (int)$visitor->partnerId;
        }

        $partnerData                = $partnerService->getPartnerDataForReservation($webSiteAff, $reservationData['prod_id'], true);
        $data['partner_ref']        = (isset($userAndPayData['aff-ref']) ? $userAndPayData['aff-ref'] : '');
        $data['partner_id']         = $webSiteAff;
        $data['partner_name']       = $partnerData->getPartnerName();
        $data['partner_commission'] = $partnerData->getCommission();
        $data['model']              = $partnerData->getBusinessModel();

        // Is backoffice user
        if (Helper::isBackofficeUser()) {
            if (isset($userAndPayData['not_send_mail']) && $userAndPayData['not_send_mail'] > 0) {
                $otherInfo['no_send_guest_mail'] = true;
            }
        }

        // check apartel
        if (isset($userAndPayData['apartel']) && $userAndPayData['apartel'] > 0) {
            /** @var $apartelGeneralDao \DDD\Dao\Apartel\General */
            $apartelGeneralDao = $this->getServiceLocator()->get('dao_apartel_general');
            if ($apartelGeneralDao->checkApartmentFromThisApartel($userAndPayData['apartel'], $reservationData['prod_id'])) {
                $data['apartel_id'] = $userAndPayData['apartel'];
            }
        }

        $userAndPayData['phone']    = $this->clearPhone($userAndPayData['phone']);

        // Personal Data
        $data['guest_first_name']   = $tankYou['first_name'] = $userAndPayData['first-name'];
        $data['guest_last_name']    = $tankYou['last_name'] = $userAndPayData['last-name'];
        $data['guest_email']        = $tankYou['email'] = $userAndPayData['email'];
        $data['guest_address']      = $tankYou['address'] = $userAndPayData['address'];
        $data['guest_city_name']    = $tankYou['city'] = $userAndPayData['city'];
        $data['guest_country_id']   = $tankYou['country'] = $userAndPayData['country'];
        $data['guest_language_iso'] = 'en';
        $data['guest_zip_code']     = $userAndPayData['zip'];
        $data['guest_phone']        = $tankYou['phone'] = ($userAndPayData['phone']) ? $userAndPayData['phone'] : '';
        $tankYou['partner']         = $partnerData->getPartnerName();
        $tankYou['partner_id']      = $webSiteAff;
        $tankYou['totalTax']        = (isset($tankYou['totalTax'])) ? number_format($tankYou['totalTax'], 2, '.', '') : '';
        $tankYou['totalWithoutTax'] = number_format($reservationData['acc_price'] * $data['currency_rate_usd'], 2, '.', '');

        /** @var \DDD\Dao\Booking\Booking $bookingDao */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \ArrayObject());

        $customerData['email']      = $data['guest_email'];

        // Credit Card Data
        $ccNotProvided = (Helper::isBackofficeUser() && isset($userAndPayData['noCreditCard']));
        if ($ccNotProvided) {
            $customerData['cc_provided'] = false;
        } else {
            $customerData['cc_provided'] = true;
            $customerData['number'] = $cr_number = $userAndPayData['number'];
            $customerData['holder']             = $userAndPayData['holder'];
            $customerData['month']              = $userAndPayData['month'];
            $customerData['year']               = $userAndPayData['year'];
            $customerData['cvc']                = (isset($userAndPayData['cvc']) ? $userAndPayData['cvc'] : false);
        }

        if (Helper::isBackofficeUser()) {
            $customerData['source'] = Card::CC_SOURCE_WEBSITE_RESERVATION_EMPLOYEE;
        } else {
            $customerData['source'] = Card::CC_SOURCE_WEBSITE_RESERVATION_GUEST;
        }

        try {
			$bookingDao->beginTransaction();

            $newAvailability           = 0;
            $otherInfo['cc_provided']  = $customerData['cc_provided'];
            $otherInfo['availability'] = $newAvailability;
            $data['customer_data']     = $customerData;
            $ratesData                 = $reservationService->getRateDataByRateIdDates($reservationData['rateId'], $reservationData['date_from'], $reservationData['date_to']);
            $otherInfo['ratesData']    = $ratesData;
            $reservationId             = $reservationService->registerReservation($data, $otherInfo, true);
            $tankYou['reservation_id'] = $reservationId;
            $session_booking->tankyou  = $tankYou;
            // discount for Ginosiks
            $discountValidator = $bookingTicketService->validateAndCheckDiscountData([
                'email'               => $data['guest_email'],
                'aff_id'              => $data['partner_id']
            ]);

            if (   $discountValidator['valid']
                && ceil($discountValidator['discount_value'])
                && ($discountValidator['aff_id'] == BookingTicket::SECRET_DISCOUNT_AFFILIATE_ID)
            ) {

                $discountSave['is_refundable']   = ApartmentRate::APARTMENT_RATE_NON_REFUNDABLE;
                $discountSave['penalty']         = ApartmentRate::PENALTY_TYPE_PERCENT;
                $discountSave['penalty_val']     = 100;
                $discountSave['funds_confirmed'] = BookingTicket::CC_STATUS_VALID;

                $bookingDao->save($discountSave, ['id' => $reservationId]);

                $logger = $this->getServiceLocator()->get('ActionLogger');
                $logger->save(
                    Logger::MODULE_BOOKING,
                    $reservationId,
                    Logger::ACTION_BOOKING_CC_STATUS,
                    BookingTicket::CC_STATUS_VALID + 1
                );
            }

            // Push into synchronization queue
            $syncService->push($reservationData['prod_id'], $reservationData['date_from'], $reservationData['date_to']);

            $bookingDao->commitTransaction();

            return ['status' => 'success'];
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot booking from Website', [
                'apartment_id'      => $data['apartment_id_origin'],
                'room_id'           => $data['room_id'],
                'rate_id'           => (isset($data['rate_id'])) ? $data['rate_id'] : '',
                'date_from'         => $data['date_from'],
                'date_to'           => $data['date_to'],
                'partner_id'        => $data['partner_id'],
                'partner_reference' => $data['partner_ref']
            ]);

			$bookingDao->rollbackTransaction();

            return ['status' => 'error'];
		}
    }

    private function clearPhone($phoneNumber) {
        return preg_replace('/[^0-9]/', '', $phoneNumber);
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        $countriesDao = new Countries($this->getServiceLocator());
        $countries = $countriesDao->getCountriesList();
        $partnerDao = $this->getBookingPartnerDao();
        $partners = $partnerDao->getPartnerListForWeb();
        $userCountryData = Helper::getUserCountry();

        return [
            'countris'        => $countries,
            'partners'        => $partners,
            'userCountryData' => $userCountryData,
        ];
    }

    /**
     * @param array $data
     * @return string
     */
    private function filterReservationData($data)
    {
        $currentDate = date('Y-m-d');
        $cityDao = new City($this->getServiceLocator(), 'ArrayObject');

        if (isset($data['city']) && ClassicValidator::checkCityName($data['city'])) {
            $cityResp = $cityDao->getCityByName(Helper::urlForSearch($data['city']));

            if ($cityResp) {
                /* @var $websiteSearchService \DDD\Service\Website\Search */
                $websiteSearchService = $this->getServiceLocator()->get('service_website_search');
                $diffHours = $websiteSearchService->getDiffHoursForDate();

                $currentDate = Helper::getCurrenctDateByTimezone($cityResp['timezone'], 'd-m-Y', $diffHours);
            }
        }

        if (
            !isset($data['city']) || !ClassicValidator::checkCityName($data['city']) ||
            !isset($data['apartment']) || !ClassicValidator::checkApartmentTitle($data['apartment']) ||
            !isset($data['guest']) || !is_numeric($data['guest']) ||
            !isset($data['apartment_id']) || !is_numeric($data['apartment_id']) ||
            !isset($data['arrival']) || !ClassicValidator::validateDate($data['arrival'], 'd M Y') ||
            !isset($data['departure']) || !ClassicValidator::validateDate($data['departure'], 'd M Y') ||
            strtotime($data['arrival']) >= strtotime($data['departure']) ||
            (strtotime($currentDate)  - strtotime($data['arrival'])  > 129600) ||
            //1.5 days, because we let BO user to book 1 day before, and + 0.5 day for the timezone
            !isset($data['rate-for-booking']) || !is_numeric($data['rate-for-booking']) ||
            (isset($data['apartel_id']) && !is_numeric($data['apartel_id']))
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param array $data
     * @return array
     */
    public function bookingDataByCCPassword($data)
    {
        /**
         * @var \DDD\Service\Website\Apartment $apartmentService
         * @var \DDD\Service\Booking\Charge $chargeService
         */
        $apartmentService  = $this->getServiceLocator()->get('service_website_apartment');
        $chargeService  = $this->getServiceLocator()->get('service_booking_charge');

        if (!isset($data['code']) || !ClassicValidator::checkCCPdateCode($data['code'])) {
            return false;
        }

        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \ArrayObject());

        $resData = $bookingDao->getResDataByCCUpdateCode($data['code']);

        if (!$resData) {
           return false;
        }

        $img = Helper::getImgByWith($resData['img1'], WebSite::IMG_WIDTH_SEARCH, true, true);

        if ($img) {
            $resData['image'] = $img;
        }

        $resData['user_currency'] = $resData['guest_currency_code'];
        $bookNightCount = Helper::getDaysFromTwoDate($resData['date_from'], $resData['date_to']);
        $resData['night_count'] = $bookNightCount;
        $resData['totalNigthCount'] = $bookNightCount;

        // Cancelation Policy
        $cancelationDate = $resData;
        $cancelationDate['penalty_percent'] = $cancelationDate['penalty_fixed_amount'] = $cancelationDate['penalty_nights'] = $resData['penalty_val'];
        $cancelationDate['night_count'] = $bookNightCount;
        $cancelationDate['code'] = $resData['apartment_currency_code'];

        $cancelationPolicy = $apartmentService->cancelationPolicy($cancelationDate);
        $resData['cancelation_type']   = $cancelationPolicy['type'];
        $resData['cancelation_policy'] = $cancelationPolicy['description'];

        $paymentDetails = $chargeService->getCharges($resData['id']);

        // When showing to customers we don't really want to show base and additional parts of taxes separately
        // This part of code runs through payment details and combines same type of taxes for same day into a single unit
        $floatPattern = '/-?(?:\d+|\d*\.\d+)/';
        $smarterPaymentDetails = [];
        if ($paymentDetails) {
            foreach($paymentDetails as $payment) {
                if ($payment['type'] != 'tax') {
                    array_push($smarterPaymentDetails, $payment);
                } else {
                    $paymentKey = $payment['type_id'] . '-' . $payment['date'];
                    if (!isset($smarterPaymentDetails[$paymentKey])) {
                        $smarterPaymentDetails[$paymentKey] = $payment;
                    } else {
                        preg_match($floatPattern, $smarterPaymentDetails[$paymentKey]['label'], $match);
                        $currValue = $match[0];
                        $currPrice = $smarterPaymentDetails[$paymentKey]['price'];
                        preg_match($floatPattern, $payment['label'], $match);
                        $additionalValue = $match[0];
                        $additionalPrice = $payment['price'];

                        $smarterPaymentDetails[$paymentKey]['label'] = str_replace($currValue, $currValue + $additionalValue, $smarterPaymentDetails[$paymentKey]['label']);
                        $smarterPaymentDetails[$paymentKey]['price'] = str_replace($currPrice, $currPrice + $additionalPrice, $smarterPaymentDetails[$paymentKey]['price']);
                        $smarterPaymentDetails[$paymentKey]['price_view'] = str_replace($currPrice, $currPrice + $additionalPrice, $smarterPaymentDetails[$paymentKey]['price_view']);
                    }
                }
            }
        }

        $resData['paymentDetails']['payments'] = $smarterPaymentDetails;
        return $resData;
    }

    /**
     * @param $data
     * @param $resData
     * @return bool
     */
    public function updateCCData($data, $resData)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \ArrayObject());

        $bookingIssueService = $this->getServiceLocator()->get('service_booking_reservation_issues');

        $pan                        = $data['number'];
        $params['guest_country_id']       = $data['country'];
        $params['guest_city_name']  = $data['city'];
        $params['guest_zip_code']   = $data['zip'];
        $params['funds_confirmed']  = BookingTicket::CC_STATUS_UNKNOWN;
        $params['provide_cc_page_status'] = BookingTicket::PROVIDE_CC_PAGE_STATUS_NOT_CHECK;

        // Customer/CC Creation
        $customerData['customer_id']      = $resData['customer_id'];
        $customerData['email']            = $resData['guest_email'];
        $customerData['cc_provided']      = true;
        $customerData['number']           = $pan;
        $customerData['holder']           = $data['holder'];
        $customerData['cvc']              = $data['cvc'];
        $customerData['year']             = $data['year'];
        $customerData['month']            = $data['month'];

        if (isset($_COOKIE['backoffice_user'])) {
            $customerData['source'] = Card::CC_SOURCE_WEBSITE_EMPLOYEE;
        } else {
            $customerData['source'] = Card::CC_SOURCE_WEBSITE_GUEST;
        }

        try {
            $bookingDao->beginTransaction();

            /**
             * @var Card $cardService
             */
            $cardService = $this->getServiceLocator()->get('service_card');

            $cardService->processCreditCardData($customerData);

            // Save ticket
            $bookingDao->save($params, ['id' => $resData['id']]);
            $bookingIssueService->resolveReservationIssueByType($resData['id']);
            $bookingDao->commitTransaction();
        } catch (\Exception $ex) {
            $this->gr2logException($ex, 'Cannot update CC data from Website', [
                'customer_id' => $customerData['customer_id']
            ]);
            $bookingDao->rollbackTransaction();

            return false;
        }

        $this->sendCCdataMail($resData['id']);

        return true;
    }

    /**
     * @param $reservationId
     * @return array
     */
    public function changePaymentDetailsByGuestCurrency($reservationId)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var \DDD\Service\Booking\Charge $chargeService
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $chargeService = $this->getServiceLocator()->get('service_booking_charge');

        $chargeData = $bookingDao->getDataForToBeCharged($reservationId, true);

        // for currency
        $chargeData->setCheckCurrency(true);
        $chargeData->setPrice($chargeData->getBookerPrice());
        return $chargeService->getToBeChargedItems($chargeData);
    }

    /**
     * @param $code
     * @param $cityId
     * @param $dateFrom
     * @return array|\ArrayObject|null
     */
    public function checkDuplicateDoorCode($code, $cityId, $dateFrom)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\DoorCode());

        return $bookingDao->getTicketByDoorCode($code, $cityId, $dateFrom);
    }

    /**
     * @param $reservationId
     */
    private function sendCCdataMail($reservationId)
    {
        shell_exec('ginosole reservation-email send-payment-details-updated-ginosi --id=' . $reservationId . ' --ccp=yes > /dev/null &');
    }

    /**
     * @param string $domain
     * @return Partner
     */
    public function getBookingPartnerDao($domain = 'ArrayObject')
    {
		return new Partner($this->getServiceLocator(), $domain);
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
     * @access private
     * @param string $domain
     * @return \DDD\Dao\Apartment\Room
     */
    private function getApartmentRoomDao($domain = 'ArrayObject') {
        return new \DDD\Dao\Apartment\Room($this->getServiceLocator(), $domain);
    }

}
