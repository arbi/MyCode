<?php

namespace DDD\Domain\Booking;

class KeyInstructionMail
{
    protected $id;
    protected $guestEmail;
    protected $secondaryEmail;
    protected $res_number;
    protected $ki_page_hash;
    protected $apartment_id;
    protected $apartment_id_assigned;
    protected $guestLanguageIso;
    protected $guestFirstName;
    protected $guestLastName;
    protected $date_from;
    protected $date_to;
    protected $acc_name;
    protected $acc_city_id;
    protected $acc_city_name;
    protected $pin;
    protected $outside_door_code;
    protected $partnerId;
    protected $phone1;
    protected $phone2;
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


    public function exchangeArray($data)
    {
        $this->id                    = (isset($data['id'])) ? $data['id'] : null;
        $this->guestEmail            = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->secondaryEmail        = (isset($data['secondary_email'])) ? $data['secondary_email'] : null;
        $this->res_number            = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->ki_page_hash          = (isset($data['ki_page_hash'])) ? $data['ki_page_hash'] : null;
        $this->apartment_id          = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartment_id_assigned = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->guestLanguageIso      = (isset($data['guest_language_iso'])) ? $data['guest_language_iso'] : null;
        $this->guestFirstName        = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName         = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->date_from             = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->date_to               = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->acc_name              = (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->acc_city_id           = (isset($data['acc_city_id'])) ? $data['acc_city_id'] : null;
        $this->acc_city_name         = (isset($data['acc_city_name'])) ? $data['acc_city_name'] : null;
        $this->pin                   = (isset($data['pin'])) ? $data['pin'] : null;
        $this->outside_door_code     = (isset($data['outside_door_code'])) ? $data['outside_door_code'] : null;
        $this->phone1                = (isset($data['phone1'])) ? $data['phone1'] : null;
        $this->phone2                = (isset($data['phone2'])) ? $data['phone2'] : null;
        $this->isRefundable          = (isset($data['is_refundable'])) ? $data['is_refundable'] : null;
        $this->refundableBeforeHours = (isset($data['refundable_before_hours'])) ? $data['refundable_before_hours'] : null;
        $this->penalty               = (isset($data['penalty'])) ? $data['penalty'] : null;
        $this->penaltyVal            = (isset($data['penalty_val'])) ? $data['penalty_val'] : null;
        $this->guestCurrencyCode     = (isset($data['guest_currency_code'])) ? $data['guest_currency_code'] : null;
        $this->partnerId             = (isset($data['partner_id'])) ? $data['partner_id'] : null;
    }

    public function getOutsideDoorCode() {
        return $this->outside_door_code;
    }

    public function setOutsideDoorCode($outsideDoorCode) {
        $this->outside_door_code = $outsideDoorCode;
        return $this;
    }

    public function getPin() {
        return $this->pin;
    }

    public function setPin($val) {
        $this->pin = $val;
        return $this;
    }

    public function getAccCityName() {
        return $this->acc_city_name;
    }

    public function setAccCityName($val) {
        $this->acc_city_name = $val;
        return $this;
    }

    public function getApartmentCityId()
    {
        return $this->acc_city_id;
    }

    public function setAccCityId($val) {
        $this->acc_city_id = $val;
        return $this;
    }

    public function getApartmentName() {
        return $this->acc_name;
    }

    public function getDateTo() {
        return $this->date_to;
    }

    public function setDateTo($val) {
        $this->date_to = $val;
        return $this;
    }

    public function getDateFrom() {
        return $this->date_from;
    }

    public function setDateFrom($val) {
        $this->date_from = $val;
        return $this;
    }

    public function getGuestLastName() {
        return ucwords(strtolower($this->guestLastName));
    }

    public function setGuestLastName($val) {
        $this->guestLastName = $val;
        return $this;
    }

    public function getGuestFirstName() {
        return ucwords(strtolower($this->guestFirstName));
    }

    public function setGuestFirstName($val) {
        $this->guestFirstName = $val;
        return $this;
    }

    public function getGuestLanguageIso() {
        return $this->guestLanguageIso;
    }

    public function setGuestLanguageIso($val) {
        $this->guestLanguageIso = $val;
        return $this;
    }

    public function getApartmentId() {
        return $this->apartment_id;
    }

    public function getApartmentIdAssigned() {
        return $this->apartment_id_assigned;
    }

    public function getKiPageHash() {
        return $this->ki_page_hash;
    }

    public function setKiPageHash($val) {
        $this->ki_page_hash = $val;
        return $this;
    }

    public function getResNumber() {
        return $this->res_number;
    }

    public function setResNumber($val) {
        $this->res_number = $val;
        return $this;
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
     * @return string|null
     */
    public function getGuestEmail()
    {
        return $this->guestEmail;
    }

    public function getSecondaryEmail() {
        return $this->secondaryEmail;
    }

    public function getId() {
        return $this->id;
    }

    public function getPartnerId() {
        return $this->partnerId;
    }

    public function getPhone1() {
        return $this->phone1;
    }

    public function getPhone2() {
        return $this->phone2;
    }

    public function setId($val) {
        $this->id = $val;
        return $this;
    }
}

?>
