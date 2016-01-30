<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CustomerService
 *
 * @author developer
 */

namespace DDD\Domain\Booking;

class CustomerService {
    protected $id;
    protected $res_number;
    protected $acc_name;
    protected $acc_province_name;
    protected $acc_city_name;
    protected $pax;
    protected $guestFirstName;
    protected $guestLastName;
    protected $guestPhone;
    protected $lmr_resolved;
    protected $guest_balance;
    protected $apartmentCurrencyCode;
    protected $symbol;

    /**
     * @var string
     */
    protected $dateFrom;

    
    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? 
            $data['id'] : null;
        $this->res_number = (isset($data['res_number'])) ? 
            $data['res_number'] : null;
        $this->acc_name = (isset($data['acc_name'])) ? 
            $data['acc_name'] : null;
        $this->acc_city_name = (isset($data['acc_city_name'])) ? 
            $data['acc_city_name'] : null;
        $this->pax = (isset($data['pax'])) ? 
            $data['pax'] : null;
        $this->guestFirstName = (isset($data['guest_first_name'])) ?
            $data['guest_first_name'] : null;
        $this->guestLastName = (isset($data['guest_last_name'])) ?
            $data['guest_last_name'] : null;
        $this->guestPhone = (isset($data['guest_phone'])) ?
            $data['guest_phone'] : null;
        $this->lmr_resolved = (isset($data['lmr_resolved'])) ? 
            $data['lmr_resolved'] : null;
        $this->guest_balance = (isset($data['guest_balance'])) ?
            $data['guest_balance'] : null;
        $this->apartmentCurrencyCode = (isset($data['apartment_currency_code'])) ?
            $data['apartment_currency_code'] : null;
        $this->symbol = (isset($data['symbol'])) ? $data['symbol'] : null;
        $this->dateFrom = (isset($data['date_from'])) ? $data['date_from'] : null;
    }
    
    public function getLmrResolved() {
        return $this->lmr_resolved;
    }
    
    public function setLmrResolved($val) {
        $this->lmr_resolved = $val;
        return $this;
    }
    
    public function getPhone() {
        return $this->guestPhone;
    }
    
    public function setPhone($val) {
        $this->guestPhone = $val;
        return $this;
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
    
    public function getPAX() {
        return $this->pax;
    }
    
    public function setPAX($val) {
        $this->pax = $val;
        return $this;
    }
    
    public function getAccCityName() {
        return $this->acc_city_name;
    }
    
    public function setAccCityName($val) {
        $this->acc_city_name = $val;
        return $this;
    }
    
    public function getApartmentName() {
        return $this->acc_name;
    }
    
    public function getResNumber() {
        return $this->res_number;
    }
    
    public function setResNumber($val) {
        $this->res_number = $val;
        return $this;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function setId($val) {
        $this->id = $val;
        return $this;
    }

    public function getGuest_balance() {
        return $this->guest_balance;
    }

    public function getApartmentCurrencyCode() {
        return $this->apartmentCurrencyCode;
    }

    public function getSymbol() {
        return $this->symbol;
    }

    /**
     * @return string
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }
}

?>
