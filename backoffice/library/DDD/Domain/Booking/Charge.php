<?php

namespace DDD\Domain\Booking;

class Charge
{
    protected $id;
    protected $res_number;
    protected $addons_type;
    protected $customer_amount;
    protected $customer_currency;
    protected $acc_amount;
    protected $acc_currency;
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
    protected $location_join;
    protected $tax_type;
    protected $reservation_nightly_date;
    protected $rate_name;
    protected $reservationNightlyId;
    protected $reservationId;
    protected $entityId;

    /**
     * Can be 2 (Ginosi Collect) or 3 (Partner Collect)
     * @var int
     */
    protected $moneyDirection;

    /**
     * Commission percent
     * @var float
     */
    protected $commission;

    public function exchangeArray($data) {
        $this->id                       = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number               = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->addons_type              = (isset($data['addons_type'])) ? $data['addons_type'] : null;
        $this->customer_amount          = (isset($data['customer_amount'])) ? $data['customer_amount'] : null;
        $this->customer_currency        = (isset($data['customer_currency'])) ? $data['customer_currency'] : null;
        $this->acc_amount               = (isset($data['acc_amount'])) ? $data['acc_amount'] : null;
        $this->acc_currency             = (isset($data['acc_currency'])) ? $data['acc_currency'] : null;
        $this->comment                  = (isset($data['comment'])) ? $data['comment'] : null;
        $this->status                   = (isset($data['status'])) ? $data['status'] : null;
        $this->date                     = (isset($data['date'])) ? $data['date'] : null;
        $this->addon                    = (isset($data['addon'])) ? $data['addon'] : null;
        $this->user                     = (isset($data['user'])) ? $data['user'] : null;
        $this->addons_value             = (isset($data['addons_value'])) ? $data['addons_value'] : null;
        $this->date_delete              = (isset($data['date_delete'])) ? $data['date_delete'] : null;
        $this->user_delete              = (isset($data['user_delete'])) ? $data['user_delete'] : null;
        $this->comment_delete           = (isset($data['comment_delete'])) ? $data['comment_delete'] : null;
        $this->type                     = (isset($data['type'])) ? $data['type'] : null;
        $this->location_join            = (isset($data['location_join'])) ? $data['location_join'] : null;
        $this->moneyDirection           = (isset($data['money_direction'])) ? $data['money_direction'] : null;
        $this->commission               = (isset($data['commission'])) ? $data['commission'] : null;
        $this->tax_type                 = (isset($data['tax_type'])) ? $data['tax_type'] : null;
        $this->reservation_nightly_date = (isset($data['reservation_nightly_date'])) ? $data['reservation_nightly_date'] : null;
        $this->rate_name                = (isset($data['rate_name'])) ? $data['rate_name'] : null;
        $this->reservationNightlyId     = (isset($data['reservation_nightly_id'])) ? $data['reservation_nightly_id'] : null;
        $this->reservationId            = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
        $this->entityId                 = (isset($data['entity_id'])) ? $data['entity_id'] : null;
        }

    public function getTaxType() {
        return $this->tax_type;
    }

    public function getLocation_join() {
        return $this->location_join;
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

    public function getApartmentCurrency() {
        return $this->acc_currency;
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

    /**
     * Returns money direction number, can be 2 (Ginosi Collect) or 3 (Partner Collect)
     * @return number
     */
	public function getMoneyDirection() {
		return $this->moneyDirection;
	}

	/**
	 * @return float
	 */
	public function getCommission() {
		return $this->commission;
	}


    public function getRateName() {
        return $this->rate_name;
    }

    public function getReservationNightlyDate() {
        return $this->reservation_nightly_date;
    }

    public function getReservationNightlyId()
    {
        return $this->reservationNightlyId;
    }

    public function getReservationId()
    {
        return $this->reservationId;
    }
    public function getEntityId()
    {
        return $this->entityId;
    }
}
