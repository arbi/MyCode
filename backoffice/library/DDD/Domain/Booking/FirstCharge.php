<?php

namespace DDD\Domain\Booking;

class FirstCharge
{
    protected $id;
    protected $res_number;
    protected $guestCurrencyCode;
    protected $apartmentCurrencyCode;
    protected $price;
    protected $currency_rate;

    /**
     * @var bool
     */
    protected $isRefundable;

    /**
     * @var int
     */
    protected $refundableBeforeHours;

    protected $date_from;

    /**
     * @var float
     */
    protected $penaltyFixedAmount;

    protected $model;
    protected $partnerCommission;
    protected $apartment_id_assigned;

    protected $tot;
    protected $totAdditional;
    protected $tot_type;
    protected $totIncluded;
    protected $totMaxDuration;

    protected $vat;
    protected $vatAdditional;
    protected $vat_type;
    protected $vatIncluded;
    protected $vatMaxDuration;

    protected $sales_tax;
    protected $salesTaxAdditional;
    protected $sales_tax_type;
    protected $salesTaxIncluded;
    protected $salesTaxMaxDuration;

    protected $city_tax;
    protected $cityTaxAdditional;
    protected $city_tax_type;
    protected $cityTaxIncluded;
    protected $cityTaxMaxDuration;

    protected $country_currecny;
    protected $date_to;
    protected $rate_capacity;
    protected $guestEmail;
    protected $partner_id;
    protected $partner_ref;
    protected $occupancy;
    protected $channelName;
    protected $remarks;
    protected $nights;

    public function exchangeArray($data)
    {
        $this->id                    = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number            = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->guestCurrencyCode     = (isset($data['guest_currency_code'])) ? $data['guest_currency_code'] : null;
        $this->apartmentCurrencyCode = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->price                 = (isset($data['price'])) ? $data['price'] : null;
        $this->currency_rate         = (isset($data['currency_rate'])) ? $data['currency_rate'] : null;
        $this->tot                   = (isset($data['tot'])) ? $data['tot'] : null;
        $this->totAdditional         = (isset($data['tot_additional'])) ? $data['tot_additional'] : null;
        $this->tot_type              = (isset($data['tot_type'])) ? $data['tot_type'] : null;
        $this->totIncluded           = (isset($data['tot_included'])) ? $data['tot_included'] : null;
        $this->totMaxDuration        = (isset($data['tot_max_duration'])) ? $data['tot_max_duration'] : null;
        $this->vat                   = (isset($data['vat'])) ? $data['vat'] : null;
        $this->vatAdditional         = (isset($data['vat_additional'])) ? $data['vat_additional'] : null;
        $this->vat_type              = (isset($data['vat_type'])) ? $data['vat_type'] : null;
        $this->vatIncluded           = (isset($data['vat_included'])) ? $data['vat_included'] : null;
        $this->vatMaxDuration        = (isset($data['vat_max_duration'])) ? $data['vat_max_duration'] : null;
        $this->sales_tax             = (isset($data['sales_tax'])) ? $data['sales_tax'] : null;
        $this->salesTaxAdditional    = (isset($data['sales_tax_additional'])) ? $data['sales_tax_additional'] : null;
        $this->sales_tax_type        = (isset($data['sales_tax_type'])) ? $data['sales_tax_type'] : null;
        $this->salesTaxIncluded      = (isset($data['sales_tax_included'])) ? $data['sales_tax_included'] : null;
        $this->salesTaxMaxDuration   = (isset($data['sales_tax_max_duration'])) ? $data['sales_tax_max_duration'] : null;
        $this->city_tax              = (isset($data['city_tax'])) ? $data['city_tax'] : null;
        $this->cityTaxAdditional     = (isset($data['city_tax_additional'])) ? $data['city_tax_additional'] : null;
        $this->city_tax_type         = (isset($data['city_tax_type'])) ? $data['city_tax_type'] : null;
        $this->cityTaxIncluded       = (isset($data['city_tax_included'])) ? $data['city_tax_included'] : null;
        $this->cityTaxMaxDuration    = (isset($data['city_tax_max_duration'])) ? $data['city_tax_max_duration'] : null;
        $this->isRefundable          = (isset($data['is_refundable'])) ? $data['is_refundable'] : null;
        $this->refundableBeforeHours = (isset($data['refundable_before_hours'])) ? $data['refundable_before_hours'] : null;
        $this->date_from             = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->penaltyFixedAmount    = (isset($data['penalty_fixed_amount'])) ? $data['penalty_fixed_amount'] : null;
        $this->model                 = (isset($data['model'])) ? $data['model'] : null;
        $this->partnerCommission     = (isset($data['partner_commission'])) ? $data['partner_commission'] : null;
        $this->apartment_id_assigned = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->country_currecny      = (isset($data['country_currecny'])) ? $data['country_currecny'] : null;
        $this->date_to               = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->rate_capacity         = (isset($data['rate_capacity'])) ? $data['rate_capacity'] : null;
        $this->guestEmail            = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->partner_id            = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->partner_ref           = (isset($data['partner_ref'])) ? $data['partner_ref'] : null;
        $this->occupancy             = (isset($data['occupancy'])) ? $data['occupancy'] : null;
        $this->channelName           = (isset($data['channel_name'])) ? $data['channel_name'] : null;
        $this->remarks               = (isset($data['remarks'])) ? $data['remarks'] : null;
        $this->nights                = (isset($data['nights'])) ? $data['nights'] : null;
    }

    public function getDateTo()
    {
    	return $this->date_to;
    }

    public function getCountryCurrecny()
    {
    	return $this->country_currecny;
    }

    public function getTot_type()
    {
    	return $this->tot_type;
    }

    public function getVat_type()
    {
    	return $this->vat_type;
    }

    public function getSales_tax_type()
    {
    	return $this->sales_tax_type;
    }

    public function getCity_tax_type()
    {
    	return $this->city_tax_type;
    }

    public function getApartmentIdAssigned() {
        return $this->apartment_id_assigned;
    }

    public function getModel() {
        return $this->model;
    }

    public function getPartnerCommission() {
    	return $this->partnerCommission;
    }

    public function getDate_from() {
        return $this->date_from;
    }

    public function getGuestCurrencyCode() {
        return $this->guestCurrencyCode;
    }

    public function getId() {
        return $this->id;
    }

    public function getReservationNumber()
    {
        return $this->res_number;
    }

    public function getApartmentCurrencyCode() {
        return $this->apartmentCurrencyCode;
    }

    /**
     * Return formatted price
     * @return string
     */
    public function getPrice() {
        return number_format($this->price, 2, '.', '');
    }

    public function getCurrency_rate() {
        return $this->currency_rate;
    }

    public function getCity_tax() {
    	return $this->city_tax;
    }

    public function getSales_tax() {
    	return $this->sales_tax;
    }

    public function getVat() {
    	return $this->vat;
    }

    public function getTot() {
    	return $this->tot;
    }
    public function getRateCapacity() {
    	return $this->rate_capacity;
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
     * @return float
     */
    public function getPenaltyFixedAmount()
    {
        return $this->penaltyFixedAmount;
    }

    public function getGuestEmail()
    {
        return $this->guestEmail;
    }

    public function getPartnerId()
    {
        return $this->partner_id;
    }

    public function getPartnerRef()
    {
        return $this->partner_ref;
    }


    /**
     * @param mixed $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
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

    public function getChannelName()
    {
        return $this->channelName;
    }

    /**
     * @return string | null
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * @return mixed
     */
    public function getTotMaxDuration()
    {
        return $this->totMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getVatMaxDuration()
    {
        return $this->vatMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getSalesTaxMaxDuration()
    {
        return $this->salesTaxMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getCityTaxMaxDuration()
    {
        return $this->cityTaxMaxDuration;
    }

    /**
     * @return mixed
     */
    public function getNights()
    {
        return $this->nights;
    }

    /**
     * @return int|null
     */
    public function getTotAdditional()
    {
        return $this->totAdditional;
    }

    /**
     * @return int|null
     */
    public function getVatAdditional()
    {
        return $this->vatAdditional;
    }

    /**
     * @return int|null
     */
    public function getSalesTaxAdditional()
    {
        return $this->salesTaxAdditional;
    }

    /**
     * @return int|null
     */
    public function getCityTaxAdditional()
    {
        return $this->cityTaxAdditional;
    }
}
