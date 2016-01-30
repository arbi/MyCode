<?php

namespace DDD\Domain\Booking;

class Review
{
    protected $id;
    protected $guestEmail;
    protected $secondaryEmail;
    protected $res_number;
    protected $review_page_hash;
    protected $apartment_id;
    protected $apartment_id_assigned;
    protected $guestLanguageIso;
    protected $status;
    protected $guestFirstName;
    protected $guestLastName;
    protected $acc_name;
    protected $cityName;
    protected $phone1;
    protected $phone2;
    protected $date_from;
    protected $partnerId;
    /**
     * @var int
     */
    protected $refundableBeforeHours;
    /**
     * @var bool
     */
    protected $isRefundable;
    /**
     * @var
     */
    protected $penalty;
    /**
     * @var
     */
    protected $penaltyVal;
    /**
     * @var
     */
    protected $guestCurrencyCode;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->id                    = (isset($data['id'])) ? $data['id'] : null;
        $this->guestEmail            = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->secondaryEmail        = (isset($data['secondary_email'])) ? $data['secondary_email'] : null;
        $this->res_number            = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->review_page_hash      = (isset($data['review_page_hash'])) ? $data['review_page_hash'] : null;
        $this->apartment_id          = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartment_id_assigned = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->guestLanguageIso      = (isset($data['guest_language_iso'])) ? $data['guest_language_iso'] : null;
        $this->status                = (isset($data['status'])) ? $data['status'] : null;
        $this->guestFirstName        = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName         = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->acc_name              = (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->phone1                = (isset($data['phone1'])) ? $data['phone1'] : null;
        $this->phone2                = (isset($data['phone2'])) ? $data['phone2'] : null;
        $this->cityName              = (isset($data['city_name'])) ? $data['city_name'] : null;
        $this->isRefundable          = (isset($data['is_refundable'])) ? $data['is_refundable'] : null;
        $this->refundableBeforeHours = (isset($data['refundable_before_hours'])) ? $data['refundable_before_hours'] : null;
        $this->penalty               = (isset($data['penalty'])) ? $data['penalty'] : null;
        $this->penaltyVal            = (isset($data['penalty_val'])) ? $data['penalty_val'] : null;
        $this->partnerId             = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->guestCurrencyCode     = (isset($data['guest_currency_code'])) ? $data['guest_currency_code'] : null;
        $this->date_from             = (isset($data['date_from'])) ? $data['date_from'] : null;
    }

    public function getApartmentName() {
        return $this->acc_name;
    }

    public function getGuestLastName() {
        return $this->guestLastName;
    }

    public function setGuestLastName($val) {
        $this->guestLastName = $val;
        return $this;
    }

    public function getGuestFirstName() {
        return $this->guestFirstName;
    }

    public function setGuestFirstName($val) {
        $this->guestFirstName = $val;
        return $this;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($val) {
        $this->status = $val;
        return $this;
    }

    public function getGuestLanguageIso() {
        return $this->guestLanguageIso;
    }

    public function setlang($val) {
        $this->guestLanguageIso = $val;
        return $this;
    }

    public function getApartmentId() {
        return $this->apartment_id;
    }

    public function getApartmentIdAssigned() {
        return $this->apartment_id_assigned;
    }

    public function getReviewPageHash() {
        return $this->review_page_hash;
    }

    public function setReviewPageHash($val) {
        $this->review_page_hash = $val;
        return $this;
    }

    public function getResNumber() {
        return $this->res_number;
    }

    public function setResNumber($val) {
        $this->res_number = $val;
        return $this;
    }

    public function getGuestEmail() {
        return $this->guestEmail;
    }

    public function getPartnerId() {
        return $this->partnerId;
    }

    /**
     * @return string|null
     */
    public function getSecondaryEmail() {
        return $this->secondaryEmail;
    }

    public function setGuestEmail($val) {
        $this->guestEmail = $val;
        return $this;
    }

    public function getPhone1() {
        return $this->phone1;
    }

    public function getPhone2() {
        return $this->phone2;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($val) {
        $this->id = $val;
        return $this;
    }

    public function getCityName() {
        return $this->cityName;
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
     * @return mixed
     */
    public function getPenalty() {
        return $this->penalty;
    }

    /**
     * @return mixed
     */
    public function getPenaltyVal() {
        return $this->penaltyVal;
    }

    /**
     * @return mixed
     */
    public function getGuestCurrencyCode() {
        return $this->guestCurrencyCode;
    }

    /**
     * @return mixed
     */
    public function getDateFrom() {
        return $this->date_from;
    }
}