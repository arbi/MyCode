<?php

namespace DDD\Domain\Booking;

class CancellationEmail
{
    protected $id;
    protected $res_number;
    protected $guestLanguageIso;
    protected $guestEmail;
    protected $guestFirstName;
    protected $guestLastName;
    protected $partner_id;
    protected $acc_name;
    protected $apartment_id;
    protected $apartment_id_assigned;
    protected $rate_name;
    protected $pax;
    protected $date_from;
    protected $date_to;
    protected $price;
    protected $apartmentCurrencyCode;
    protected $currency_rate_usd;
    protected $booker_price;
    protected $guestCurrencyCode;
    protected $currency_rate;

    /**
     * @var bool
     */
    protected $isRefundable;
    protected $penalty;
    protected $penalty_val;
    protected $penaltyFixedAmount;

    /**
     * @var int
     */
    protected $refundableBeforeHours;

    protected $status;
    protected $penalty_bit;

    /**
     * @var int
     */
    protected $overbookingStatus;

    protected $guest_balance;
    protected $phone2;
    protected $phone1;
    protected $check_in;
    protected $check_out;

    public function exchangeArray($data)
    {
        $this->id                    = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number            = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->guestLanguageIso      = (isset($data['guest_language_iso'])) ? $data['guest_language_iso'] : null;
        $this->guestEmail            = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->guestFirstName        = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName         = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->partner_id            = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->acc_name              = (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->apartment_id          = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartment_id_assigned = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->rate_name             = (isset($data['rate_name'])) ? $data['rate_name'] : null;
        $this->pax            		 = (isset($data['pax'])) ? $data['pax'] : null;
        $this->date_from             = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->date_to               = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->price                 = (isset($data['price'])) ? $data['price'] : null;
        $this->apartmentCurrencyCode = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->currency_rate_usd     = (isset($data['currency_rate_usd'])) ? $data['currency_rate_usd'] : null;
        $this->booker_price          = (isset($data['booker_price'])) ? $data['booker_price'] : null;
        $this->guestCurrencyCode     = (isset($data['guest_currency_code'])) ? $data['guest_currency_code'] : null;
        $this->currency_rate         = (isset($data['currency_rate'])) ? $data['currency_rate'] : null;
        $this->isRefundable          = (isset($data['is_refundable'])) ? $data['is_refundable'] : null;
        $this->penalty               = (isset($data['penalty'])) ? $data['penalty'] : null;
        $this->penalty_val           = (isset($data['penalty_val'])) ? $data['penalty_val'] : null;
        $this->refundableBeforeHours = (isset($data['refundable_before_hours'])) ? $data['refundable_before_hours'] : null;
        $this->status                = (isset($data['status'])) ? $data['status'] : null;
        $this->penalty_bit           = (isset($data['penalty_bit'])) ? $data['penalty_bit'] : null;
        $this->penaltyFixedAmount    = (isset($data['penalty_fixed_amount'])) ? $data['penalty_fixed_amount'] : null;
        $this->overbookingStatus     = (isset($data['overbooking_status'])) ? $data['overbooking_status'] : null;
        $this->guest_balance         = (isset($data['guest_balance'])) ? $data['guest_balance'] : null;
        $this->phone1                = (isset($data['phone1'])) ? $data['phone1'] : null;
        $this->phone2                = (isset($data['phone2'])) ? $data['phone2'] : null;
        $this->check_in              = (isset($data['check_in'])) ? $data['check_in'] : null;
        $this->check_out             = (isset($data['check_out'])) ? $data['check_out'] : null;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getResNumber() {
        return $this->res_number;
    }

    public function getGuestLanguageIso() {
        return $this->guestLanguageIso;
    }

    public function getGuestEmail() {
        return $this->guestEmail;
    }

    public function getGuestFirstName() {
        return ucwords(strtolower($this->guestFirstName));
    }

    public function getGuestLastName() {
        return ucwords(strtolower($this->guestLastName));
    }
    
    public function getPartnerId() {
        return $this->partner_id;
    }

    public function getApartmentName() {
        return $this->acc_name;
    }
    
    public function getApartmentId() {
        return $this->apartment_id;
    }

    public function getRateName() {
        return $this->rate_name;
    }

    public function getPAX() {
        return $this->pax;
    }

    public function getDateFrom() {
        return $this->date_from;
    }

    public function getDateTo() {
        return $this->date_to;
    }

    public function getCheckIn() {
        return $this->check_in;
    }

    public function getCheckOut() {
        return $this->check_out;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getApartmentCurrencyCode() {
        return $this->apartmentCurrencyCode;
    }

    public function getCurrencyRateUsd() {
        return $this->currency_rate_usd;
    }

    public function getBookerPrice() {
        return $this->booker_price;
    }

    public function getGuestCurrencyCode() {
        return $this->guestCurrencyCode;
    }

    public function getCurrencyRate() {
        return $this->currency_rate;
    }
    
    public function getPenalty() {
        return $this->penalty;
    }

    public function getPenaltyVal() {
        return $this->penalty_val;
    }

    /**
     * @return float
     */
    public function getPenaltyFixedAmount()
    {
        return $this->penaltyFixedAmount;
    }
    
    public function getStatus() {
        return $this->status;
    }
    
    public function getPenaltyBit() {
        return $this->penalty_bit;
    }
    
    public function getOverbookingStatus()
    {
        return $this->overbookingStatus;
    }
    
    public function getGuestBalance() {
        return $this->guest_balance;
    }

    public function getPhone1() {
        return $this->phone1;
    }

    public function getPhone2() {
        return $this->phone2;
    }
    
    public function getGuestDebt(){
        if($this->guest_balance < 0) {
            return abs($this->guest_balance);
        } else {
            return 0;
        }
    }

    public function getApartmentIdAssigned()
    {
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
}
