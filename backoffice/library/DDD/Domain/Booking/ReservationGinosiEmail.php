<?php

namespace DDD\Domain\Booking;

class ReservationGinosiEmail
{
    protected $id;
    protected $apartment_id_assigned;
    protected $acc_name;
    protected $res_number;
    protected $rate_name;
    protected $room_id;
    protected $price;
    protected $booker_price;
    protected $date_from;
    protected $date_to;

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
    protected $check_in;

    /**
     * @var string
     */
    protected $guestArrivalTime;

    protected $pax;
    protected $occupancy;
    protected $penalty;
    protected $penalty_val;
    protected $apartmentCurrencyCode;
    protected $currency_rate;

    /**
     * @var int
     */
    protected $refundableBeforeHours;

    protected $guestCurrencyCode;
    protected $acc_city_id;
    protected $acc_country_id;
    protected $guestPhone;
    protected $remarks;
    protected $model;
    protected $review_page_hash;

    protected $partner_id;
    protected $channel_name;

    protected $guest_balance;
    protected $city_tot;
    protected $city_vat;
    protected $city_sales_tax;
    protected $city_tax;
    protected $city_tot_type;
    protected $totIncluded;
    protected $city_vat_type;
    protected $vatIncluded;
    protected $city_sales_tax_type;
    protected $salesTaxIncluded;
    protected $city_tax_type;
    protected $cityTaxIncluded;
    protected $country_currency;

    protected $phone1;
    protected $phone2;
    protected $rate_capacity;


    public function exchangeArray($data)
    {
        $this->id                    = (isset($data['id'])) ? $data['id'] : null;
        $this->apartment_id_assigned = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->acc_name              = (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->res_number            = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->rate_name             = (isset($data['rate_name'])) ? $data['rate_name'] : null;
        $this->room_id               = (isset($data['room_id'])) ? $data['room_id'] : null;
        $this->price                 = (isset($data['price'])) ? $data['price'] : null;
        $this->booker_price          = (isset($data['booker_price'])) ? $data['booker_price'] : null;
        $this->date_from             = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->date_to               = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->isRefundable          = (isset($data['is_refundable'])) ? $data['is_refundable'] : null;
        $this->guestLanguageIso      = (isset($data['guest_language_iso'])) ? $data['guest_language_iso'] : null;
        $this->guestEmail            = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->secondaryEmail        = (isset($data['secondary_email'])) ? $data['secondary_email'] : null;
        $this->guestFirstName        = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName         = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->overbookingStatus     = (isset($data['overbooking_status'])) ? $data['overbooking_status'] : null;
        $this->check_in              = (isset($data['check_in'])) ? $data['check_in'] : null;
        $this->guestArrivalTime      = (isset($data['guest_arrival_time'])) ? $data['guest_arrival_time'] : null;
        $this->pax                   = (isset($data['pax'])) ? $data['pax'] : null;
        $this->occupancy             = (isset($data['occupancy'])) ? $data['occupancy'] : null;
        $this->penalty               = (isset($data['penalty'])) ? $data['penalty'] : null;
        $this->penalty_val           = (isset($data['penalty_val'])) ? $data['penalty_val'] : null;
        $this->apartmentCurrencyCode = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->currency_rate         = (isset($data['currency_rate'])) ? $data['currency_rate'] : null;
        $this->refundableBeforeHours = (isset($data['refundable_before_hours'])) ? $data['refundable_before_hours'] : null;
        $this->guestCurrencyCode     = (isset($data['guest_currency_code'])) ? $data['guest_currency_code'] : null;
        $this->acc_city_id           = (isset($data['acc_city_id'])) ? $data['acc_city_id'] : null;
        $this->acc_country_id        = (isset($data['acc_country_id'])) ? $data['acc_country_id'] : null;
        $this->guestPhone            = (isset($data['guest_phone'])) ? $data['guest_phone'] : null;
        $this->remarks               = (isset($data['remarks'])) ? $data['remarks'] : null;
        $this->model                 = (isset($data['model'])) ? $data['model'] : null;
        $this->review_page_hash      = (isset($data['review_page_hash'])) ? $data['review_page_hash'] : null;
        $this->partner_id            = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->channel_name          = (isset($data['channel_name'])) ? $data['channel_name'] : null;
        $this->guest_balance         = (isset($data['guest_balance'])) ? $data['guest_balance'] : null;
        $this->city_tot              = (isset($data['city_tot'])) ? $data['city_tot'] : null;
        $this->totIncluded           = (isset($data['tot_included'])) ? $data['tot_included'] : null;
        $this->city_vat              = (isset($data['city_vat'])) ? $data['city_vat'] : null;
        $this->vatIncluded           = (isset($data['vat_included'])) ? $data['vat_included'] : null;
        $this->city_sales_tax        = (isset($data['city_sales_tax'])) ? $data['city_sales_tax'] : null;
        $this->salesTaxIncluded      = (isset($data['sales_tax_included'])) ? $data['sales_tax_included'] : null;
        $this->city_tax              = (isset($data['city_tax'])) ? $data['city_tax'] : null;
        $this->cityTaxIncluded       = (isset($data['city_tax_included'])) ? $data['city_tax_included'] : null;
        $this->city_tot_type         = (isset($data['city_tot_type'])) ? $data['city_tot_type'] : null;
        $this->city_vat_type         = (isset($data['city_vat_type'])) ? $data['city_vat_type'] : null;
        $this->city_sales_tax_type   = (isset($data['city_sales_tax_type'])) ? $data['city_sales_tax_type'] : null;
        $this->city_tax_type         = (isset($data['city_tax_type'])) ? $data['city_tax_type'] : null;
        $this->country_currency      = (isset($data['country_currency'])) ? $data['country_currency'] : null;
        $this->phone1                = (isset($data['phone1'])) ? $data['phone1'] : null;
        $this->phone2                = (isset($data['phone2'])) ? $data['phone2'] : null;
        $this->rate_capacity         = (isset($data['rate_capacity'])) ? $data['rate_capacity'] : null;
    }

    public function getCountryCurrency() {
        return $this->country_currency;
    }

    public function getCityTotType() {
        return $this->city_tot_type;
    }

    public function getCityVatType() {
        return $this->city_vat_type;
    }

    public function getCitySalesTaxType() {
        return $this->city_sales_tax_type;
    }

    public function getCityTaxType() {
        return $this->city_tax_type;
    }

    public function getId() {
        return $this->id;
    }

    public function getApartmentIdAssigned() {
        return $this->apartment_id_assigned;
    }

    public function getApartmentName() {
        return $this->acc_name;
    }

    public function getResNumber() {
        return $this->res_number;
    }

    public function getRateName() {
        return $this->rate_name;
    }

    public function getRoomId() {
        return $this->room_id;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getBookerPrice() {
        return $this->booker_price;
    }

    public function getDateFrom() {
        return $this->date_from;
    }

    public function getDateTo() {
        return $this->date_to;
    }

    public function getGuestLanguageIso() {
        return 'en';
    }

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

    public function getOverbookingStatus()
    {
        return $this->overbookingStatus;
    }

    public function getCheckIn() {
        return $this->check_in;
    }

    /**
     * @return string
     */
    public function getGuestArrivalTime()
    {
        return $this->guestArrivalTime;
    }

    public function getPenalty() {
        return $this->penalty;
    }

    public function getPenaltyVal() {
        return $this->penalty_val;
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
        return $this->acc_city_id;
    }

    public function getApartmentCountryId()
    {
        return $this->acc_country_id;
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
        return $this->review_page_hash;
    }

    public function getPartnerId() {
        return $this->partner_id;
    }

    public function getChannelName() {
        return $this->channel_name;
    }

    public function getPAX() {
        return $this->pax;
    }

    public function getGuestBalance() {
        return $this->guest_balance;
    }

    public function getCityTot() {
        return $this->city_tot;
    }

    public function getCityVat() {
        return $this->city_vat;
    }

    public function getCitySalesTax() {
        return $this->city_sales_tax;
    }

    public function getCityTax() {
        return $this->city_tax;
    }

    public function getPhone1() {
        return $this->phone1;
    }

    public function getPhone2() {
        return $this->phone2;
    }

    public function getRateCapacity() {
        return $this->rate_capacity;
    }

    /**
     * @return bool
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
}
