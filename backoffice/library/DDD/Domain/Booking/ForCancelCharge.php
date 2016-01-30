<?php

namespace DDD\Domain\Booking;

class ForCancelCharge
{
    protected $id;
    protected $res_number;
    protected $addons_type;
    protected $customer_amount;
    protected $customer_currency;
    protected $acc_amount;
    protected $apartmentCurrencyCode;
    protected $comment;
    protected $status;
    protected $date;
    protected $addon;
    protected $user;
    protected $addons_value;
    protected $date_delete;
    protected $user_delete;
    protected $comment_delete;
    protected $type;
    protected $location_tax;
    protected $cxl_apply;
    protected $commission;
    protected $moneyDirectiron;
    protected $entityId;
    protected $reservationNightlyDate;


    public function exchangeArray($data)
    {
        $this->id                = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number        = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->addons_type       = (isset($data['addons_type'])) ? $data['addons_type'] : null;
        $this->customer_amount   = (isset($data['customer_amount'])) ? $data['customer_amount'] : null;
        $this->customer_currency = (isset($data['customer_currency'])) ? $data['customer_currency'] : null;
        $this->acc_amount        = (isset($data['acc_amount'])) ? $data['acc_amount'] : null;
        $this->apartmentCurrencyCode = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->comment           = (isset($data['comment'])) ? $data['comment'] : null;
        $this->status            = (isset($data['status'])) ? $data['status'] : null;
        $this->date              = (isset($data['date'])) ? $data['date'] : null;
        $this->reservationNightlyDate  = (isset($data['reservation_nightly_date'])) ? $data['reservation_nightly_date'] : null;
        $this->addon             = (isset($data['addon'])) ? $data['addon'] : null;
        $this->user              = (isset($data['user'])) ? $data['user'] : null;
        $this->addons_value      = (isset($data['addons_value'])) ? $data['addons_value'] : null;
        $this->date_delete       = (isset($data['date_delete'])) ? $data['date_delete'] : null;
        $this->user_delete       = (isset($data['user_delete'])) ? $data['user_delete'] : null;
        $this->comment_delete    = (isset($data['comment_delete'])) ? $data['comment_delete'] : null;
        $this->type              = (isset($data['type'])) ? $data['type'] : null;
        $this->location_tax      = (isset($data['location_tax'])) ? $data['location_tax'] : null;
        $this->cxl_apply         = (isset($data['cxl_apply'])) ? $data['cxl_apply'] : null;
        $this->commission        = (isset($data['commission'])) ? $data['commission'] : null;
        $this->moneyDirectiron   = (isset($data['money_direction'])) ? $data['money_direction'] : null;
        $this->entityId          = (isset($data['entity_id'])) ? $data['entity_id'] : null;

    }

    public function getCxl_apply() {
        return $this->cxl_apply;
    }

    public function getLocation_tax() {
        return $this->location_tax;
    }

    public function getType() {
        return $this->type;
    }

    public function getComment_delete() {
        return $this->comment_delete;
    }

    public function getUser_delete() {
        return $this->user_delete;
    }

    public function getDate_delete() {
        return $this->date_delete;
    }

    public function getAddons_value() {
        return $this->addons_value;
    }

    public function getUser() {
        return $this->user;
    }

    public function getAddon() {
        return $this->addon;
    }

    public function getId() {
        return $this->id;
    }

    public function getReservationNumber()
    {
        return $this->res_number;
    }

    public function getAddons_type() {
        return $this->addons_type;
    }

    public function getCustomer_amount() {
        return $this->customer_amount;
    }

    public function getCustomer_currency() {
        return $this->customer_currency;
    }

    public function getAcc_amount() {
        return $this->acc_amount;
    }

    public function getApartmentCurrencyCode() {
        return $this->apartmentCurrencyCode;
    }

    public function getComment() {
        return $this->comment;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getDate() {
        return $this->date;
    }

	public function getCommission() {
		return $this->commission;
	}

    public function getMoneyDirection() {
        return $this->moneyDirectiron;
    }
    public function getEntityId()
    {
        return $this->entityId;
    }

    public function getReservationNightlyDate()
    {
        return $this->reservationNightlyDate;
    }

}
