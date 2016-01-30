<?php

namespace DDD\Domain\Booking;

use Library\Constants\Constants;

class ReservationConfirmationEmail
{
    protected $id;
    protected $apartmentIdAssigned;
    protected $apartmentName;
    protected $resNumber;
    protected $rateName;
    protected $roomId;
    protected $rateId;
    protected $price;
    protected $bookerPrice;
    protected $dateFrom;
    protected $dateTo;
    protected $cleaningFee;

    /**
     * @var bool
     */
    protected $isRefundable;

    protected $guestLanguageIso;
    protected $guestEmail;
    protected $secondaryEmail;
    protected $guestFirstName;
    protected $guestLastName;
    protected $overbookingStatus;
    protected $checkIn;
    protected $checkOut;

    /**
     * @var string
     */
    protected $guestArrivalTime;

    protected $pax;
    protected $occupancy;
    protected $penalty;
    protected $penaltyVal;
    protected $apartmentCurrencyCode;
    protected $currency_rate;

    /**
     * @var int
     */
    protected $refundableBeforeHours;

    protected $guestCurrencyCode;
    protected $apartmentCityId;
    protected $apartmentCountryId;
    protected $guestPhone;
    protected $remarks;
    protected $model;
    protected $reviewPageHash;
    protected $partnerId;
    protected $guestBalance;
    protected $cityTot;
    protected $totIncluded;
    protected $cityVat;
    protected $vatIncluded;
    protected $citySalesTax;
    protected $salesTaxIncluded;
    protected $cityTax;
    protected $cityTaxIncluded;
    protected $cityTotType;
    protected $cityVatType;
    protected $citySalesTaxType;
    protected $cityTaxType;
    protected $countryCurrency;

    protected $countryPhoneApartment;
    protected $countryPhoneGuest;
    protected $rateCapacity;
    protected $emailingEnabled;
    protected $apartmentAssignedAddress;
    protected $apartmentAssignedPostalCode;
    protected $apartmentCityThumb;
    protected $guestAddress;
    protected $partnerName;

    public function exchangeArray($data) {
        $this->id                          = (isset($data['id'])) ? $data['id'] : null;
        $this->apartmentIdAssigned         = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartmentName               = (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->resNumber                   = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->rateName                    = (isset($data['rate_name'])) ? $data['rate_name'] : null;
        $this->roomId                      = (isset($data['room_id'])) ? $data['room_id'] : null;
        $this->rateId                      = (isset($data['rate_id'])) ? $data['rate_id'] : null;
        $this->price                       = (isset($data['price'])) ? $data['price'] : null;
        $this->bookerPrice                 = (isset($data['booker_price'])) ? $data['booker_price'] : null;
        $this->dateFrom                    = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->dateTo                      = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->isRefundable                = (isset($data['is_refundable'])) ? $data['is_refundable'] : null;
        $this->guestLanguageIso            = (isset($data['guest_language_iso'])) ? $data['guest_language_iso'] : null;
        $this->guestEmail                  = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->secondaryEmail              = (isset($data['secondary_email'])) ? $data['secondary_email'] : null;
        $this->guestFirstName              = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName               = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->overbookingStatus           = (isset($data['overbooking_status'])) ? $data['overbooking_status'] : null;
        $this->checkIn                     = (isset($data['check_in'])) ? $data['check_in'] : null;
        $this->checkOut                    = (isset($data['check_out'])) ? $data['check_out'] : null;
        $this->guestArrivalTime            = (isset($data['guest_arrival_time'])) ? $data['guest_arrival_time'] : null;
        $this->pax                         = (isset($data['pax'])) ? $data['pax'] : null;
        $this->occupancy                   = (isset($data['occupancy'])) ? $data['occupancy'] : null;
        $this->penalty                     = (isset($data['penalty'])) ? $data['penalty'] : null;
        $this->penaltyVal                  = (isset($data['penalty_val'])) ? $data['penalty_val'] : null;
        $this->apartmentCurrencyCode       = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->currency_rate               = (isset($data['currency_rate'])) ? $data['currency_rate'] : null;
        $this->refundableBeforeHours       = (isset($data['refundable_before_hours'])) ? $data['refundable_before_hours'] : null;
        $this->guestCurrencyCode           = (isset($data['guest_currency_code'])) ? $data['guest_currency_code'] : null;
        $this->apartmentCityId             = (isset($data['acc_city_id'])) ? $data['acc_city_id'] : null;
        $this->apartmentCountryId          = (isset($data['acc_country_id'])) ? $data['acc_country_id'] : null;
        $this->guestPhone                  = (isset($data['guest_phone'])) ? $data['guest_phone'] : null;
        $this->remarks                     = (isset($data['remarks'])) ? $data['remarks'] : null;
        $this->model                       = (isset($data['model'])) ? $data['model'] : null;
        $this->reviewPageHash              = (isset($data['review_page_hash'])) ? $data['review_page_hash'] : null;
        $this->partnerId                   = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->guestBalance                = (isset($data['guest_balance'])) ? $data['guest_balance'] : null;
        $this->cityTot                     = (isset($data['city_tot'])) ? $data['city_tot'] : null;
        $this->totIncluded                 = (isset($data['tot_included'])) ? $data['tot_included'] : null;
        $this->cityVat                     = (isset($data['city_vat'])) ? $data['city_vat'] : null;
        $this->vatIncluded                 = (isset($data['vat_included'])) ? $data['vat_included'] : null;
        $this->citySalesTax                = (isset($data['city_sales_tax'])) ? $data['city_sales_tax'] : null;
        $this->salesTaxIncluded            = (isset($data['sales_tax_included'])) ? $data['sales_tax_included'] : null;
        $this->cityTax                     = (isset($data['city_tax'])) ? $data['city_tax'] : null;
        $this->cityTaxIncluded             = (isset($data['city_tax_included'])) ? $data['city_tax_included'] : null;
        $this->cityTotType                 = (isset($data['city_tot_type'])) ? $data['city_tot_type'] : null;
        $this->cityVatType                 = (isset($data['city_vat_type'])) ? $data['city_vat_type'] : null;
        $this->citySalesTaxType            = (isset($data['city_sales_tax_type'])) ? $data['city_sales_tax_type'] : null;
        $this->cityTaxType                 = (isset($data['city_tax_type'])) ? $data['city_tax_type'] : null;
        $this->countryCurrency             = (isset($data['country_currency'])) ? $data['country_currency'] : null;
        $this->countryPhoneApartment       = (isset($data['country_phone_apartment'])) ? $data['country_phone_apartment'] : null;
        $this->countryPhoneGuest           = (isset($data['country_phone_guest'])) ? $data['country_phone_guest'] : null;
        $this->rateCapacity                = (isset($data['rate_capacity'])) ? $data['rate_capacity'] : null;
        $this->cleaningFee                 = (isset($data['cleaning_fee'])) ? $data['cleaning_fee'] : 0;
        $this->emailingEnabled             = (isset($data['emailing_enabled'])) ? $data['emailing_enabled'] : null;
        $this->apartmentAssignedAddress    = (isset($data['apartment_assigned_address'])) ? $data['apartment_assigned_address'] : null;
        $this->apartmentAssignedPostalCode = (isset($data['apartment_assigned_postal_code'])) ? $data['apartment_assigned_postal_code'] : null;
        $this->apartmentCityThumb          = (isset($data['apartment_city_thumb'])) ? $data['apartment_city_thumb'] : null;
        $this->guestAddress                = (isset($data['guest_address'])) ? $data['guest_address'] : null;
        $this->partnerName                 = (isset($data['partner_name'])) ? $data['partner_name'] : null;
    }

    public function getCountryCurrency() {
        return $this->countryCurrency;
    }

    public function getCityTotType() {
        return $this->cityTotType;
    }

    public function getCityVatType() {
        return $this->cityVatType;
    }

    public function getCitySalesTaxType() {
        return $this->citySalesTaxType;
    }

    public function getCityTaxType() {
        return $this->cityTaxType;
    }

    public function getId() {
        return $this->id;
    }

    public function getApartmentIdAssigned() {
        return $this->apartmentIdAssigned;
    }

    public function getApartmentName() {
        return $this->apartmentName;
    }

    public function getResNumber() {
        return $this->resNumber;
    }

    public function getRateName() {
        return $this->rateName;
    }

    public function getRoomId() {
        return $this->roomId;
    }

    public function getRateId() {
        return $this->rateId;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getBookerPrice() {
        return $this->bookerPrice;
    }

    public function getDateFrom() {
        return $this->dateFrom;
    }

    public function getDateTo() {
        return $this->dateTo;
    }

    public function getGuestLanguageIso() {
        return $this->guestLanguageIso;
    }

    /**
     * @return string|null
     */
    public function getGuestEmail() {
        return $this->guestEmail;
    }

    public function getSecondaryEmail() {
        return $this->secondaryEmail;
    }

    public function getGuestFirstName() {
        return ucwords(strtolower($this->guestFirstName));
    }

    public function getGuestLastName() {
        return ucwords(strtolower($this->guestLastName));
    }

    public function getGuestFullName() {
        return $this->getGuestFirstName() . ' ' . $this->getGuestLastName();
    }

    public function getOverbookingStatus()
    {
        return $this->overbookingStatus;
    }

    public function getCheckIn() {
        return $this->checkIn;
    }

    public function getCheckOut() {
        return $this->checkOut;
    }

    public function getCheckOutHoursMinutes() {
        return substr($this->checkOut, 0, 5);
    }

    /**
     * @return string
     */
    public function getGuestArrivalTime()
    {
        return $this->guestArrivalTime;
    }

    public function getPAX() {
        return $this->pax;
    }

    public function getPenalty() {
        return $this->penalty;
    }

    public function getPenaltyVal() {
        return $this->penaltyVal;
    }

    public function getApartmentCurrencyCode() {
        return $this->apartmentCurrencyCode;
    }

    public function getCurrencyRate() {
        return $this->currency_rate;
    }

    public function getGuestCurrencyCode() {
        return $this->guestCurrencyCode;
    }

    public function getApartmentCityId()
    {
        return $this->apartmentCityId;
    }

    public function getApartmentCountryId()
    {
        return $this->apartmentCountryId;
    }

    public function getGuestPhone() {
        return $this->guestPhone;
    }

    public function getRemarks() {
        return $this->remarks;
    }

    public function getModel() {
        return $this->model;
    }

    public function getReviewPageHash() {
        return $this->reviewPageHash;
    }

    public function getPartnerId() {
        return $this->partnerId;
    }

    public function getGuestBalance() {
        return $this->guestBalance;
    }

    public function getCityTot() {
        return $this->cityTot;
    }

    public function getCityVat() {
        return $this->cityVat;
    }

    public function getCitySalesTax() {
        return $this->citySalesTax;
    }

    public function getCityTax() {
        return $this->cityTax;
    }

    public function getCountryPhoneApartment() {
        return $this->countryPhoneApartment;
    }

    public function getCountryPhoneGuest() {
        return $this->countryPhoneGuest;
    }

    public function getRateCapacity() {
        return $this->rateCapacity;
    }

    /**
     * @return boolean
     */
    public function getIsRefundable()
    {
        return $this->isRefundable;
    }

    /**
     * @return int
     */
    public function getRefundableBeforeHours()
    {
        return $this->refundableBeforeHours;
    }

    /**
     * @return int|null
     */
    public function getTotIncluded()
    {
        return $this->totIncluded;
    }

    /**
     * @return int|null
     */
    public function getVatIncluded()
    {
        return $this->vatIncluded;
    }

    /**
     * @return int|null
     */
    public function getSalesTaxIncluded()
    {
        return $this->salesTaxIncluded;
    }

    /**
     * @return int|null
     */
    public function getCityTaxIncluded()
    {
        return $this->cityTaxIncluded;
    }

    /**
     * @return int|null
     */
    public function getOccupancy()
    {
        return $this->occupancy;
    }

    /**
     * @return mixed
     */
    public function getCleaningFee()
    {
        return $this->cleaningFee;
    }

    /**
     * @return bool
     */
    public function isEmailingDisabled()
    {
        return ($this->emailingEnabled === 0 ? true : false);
    }

    /**
     * @return mixed
     */
    public function getApartmentAssignedAddress()
    {
        return $this->apartmentAssignedAddress;
    }

    /**
     * @return mixed
     */
    public function getApartmentAssignedPostalCode()
    {
        return $this->apartmentAssignedPostalCode;
    }

    /**
     * @return mixed
     */
    public function getApartmentCityThumb()
    {
        return $this->apartmentCityThumb;
    }

    public function getCheckInDatetime()
    {
        return date(Constants::GLOBAL_DATE_TIME_WO_SEC_FORMAT, strtotime($this->getDateFrom() . ' ' . $this->getCheckIn()));
    }

    public function getCheckOutDatetime()
    {
        return date(Constants::GLOBAL_DATE_TIME_WO_SEC_FORMAT, strtotime($this->getDateTo() . ' ' . $this->getCheckOut()));
    }

    /**
     * @return string|null
     */
    public function getGuestAddress()
    {
        return $this->guestAddress;
    }

    /**
     * @return string|null
     */
    public function getPartnerName()
    {
        return $this->partnerName;
    }
}
