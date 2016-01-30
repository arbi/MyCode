<?php

namespace DDD\Domain\Booking;

class ForCancel {

    protected $id;
    protected $res_number;
    protected $status;
    protected $room_id;
    protected $date_from;
    protected $date_to;

    /**
     * @var bool
     */
    protected $isRefundable;

    protected $guestCurrencyCode;
    protected $apartment_id;
    protected $apartment_id_assigned;
    protected $apartment_status;
    protected $apartmentCurrencyCode;

    /**
     * @var float
     */
    protected $penaltyFixedAmount;

    protected $currency_rate;
    protected $penalty_hours;

    /**
     * @var int
     */
    protected $refundableBeforeHours;

    protected $room_count;
    protected $price;
    protected $tot;
    protected $vat;
    protected $sales_tax;
    protected $city_tax;
    protected $prod_type;
    protected $model;
    protected $partnerCommission;
    protected $affiliateID;

    /**
     * @var boolean
     */
    protected $isOverbooking;
    protected $arrivalStatus;

    public function exchangeArray($data) {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->room_id = (isset($data['room_id'])) ? $data['room_id'] : null;
        $this->date_from = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->date_to = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->isRefundable = (isset($data['is_refundable'])) ? $data['is_refundable'] : null;
        $this->guestCurrencyCode = (isset($data['guest_currency_code'])) ? $data['guest_currency_code'] : null;
        $this->apartment_id = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartment_id_assigned = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartment_status = (isset($data['apartment_status'])) ? $data['apartment_status'] : null;
        $this->apartmentCurrencyCode = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->penaltyFixedAmount = (isset($data['penalty_fixed_amount'])) ? $data['penalty_fixed_amount'] : null;
        $this->currency_rate = (isset($data['currency_rate'])) ? $data['currency_rate'] : null;
        $this->penalty_hours = (isset($data['penalty_hours'])) ? $data['penalty_hours'] : null;
        $this->refundableBeforeHours = (isset($data['refundable_before_hours'])) ? $data['refundable_before_hours'] : null;
        $this->room_count = (isset($data['room_count'])) ? $data['room_count'] : null;
        $this->price = (isset($data['price'])) ? $data['price'] : null;
        $this->tot = (isset($data['tot'])) ? $data['tot'] : null;
        $this->vat = (isset($data['vat'])) ? $data['vat'] : null;
        $this->sales_tax = (isset($data['sales_tax'])) ? $data['sales_tax'] : null;
        $this->city_tax = (isset($data['city_tax'])) ? $data['city_tax'] : null;
        $this->prod_type = (isset($data['prod_type'])) ? $data['prod_type'] : null;
        $this->model = (isset($data['model'])) ? $data['model'] : null;
        $this->affiliateID = (isset($data['affiliate_id'])) ? $data['affiliate_id'] : null;
        $this->partnerCommission = (isset($data['partner_commission'])) ? $data['partner_commission'] : null;
        $this->isOverbooking = (isset($data['is_overbooking'])) ? $data['is_overbooking'] : null;
        $this->arrivalStatus = (isset($data['arrival_status'])) ? $data['arrival_status'] : null;
    }

    /**
     * @return bool
     */
    public function isOverbooking()
    {
        return $this->isOverbooking;
    }

    public function getApartmentStatus()
    {
        return $this->apartment_status;
    }

    public function getProd_type() {
        return $this->prod_type;
    }

	public function getStatus() {
		return $this->status;
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

    public function getPrice() {
        return $this->price;
    }

    public function getPenalty_hours() {
        return $this->penalty_hours;
    }

    public function getCurrency_rate() {
        return $this->currency_rate;
    }

    public function getApartmentId() {
        return $this->apartment_id;
    }

    public function getApartmentCurrencyCode() {
        return $this->apartmentCurrencyCode;
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

    public function getRoom_id() {
        return $this->room_id;
    }

    public function getDate_from() {
        return $this->date_from;
    }

    public function getDate_to() {
        return $this->date_to;
    }

    public function getModel() {
    	return $this->model;
    }

    public function getPartnerCommission() {
    	return $this->partnerCommission;
    }

    public function getAffiliateID() {
    	return $this->affiliateID;
    }

    public function getApartmentIdAssigned() {
        return $this->apartment_id_assigned;
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

    public function getArrivalStatus()
    {
        return $this->arrivalStatus;
    }
}
