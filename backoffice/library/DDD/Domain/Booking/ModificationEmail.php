<?php

namespace DDD\Domain\Booking;

class ModificationEmail
{

    protected $id;
    protected $res_number;
    protected $guestLaguageIso;
    protected $guestEmail;
    protected $guestFirstName;
    protected $guestLastName;
    protected $provide_cc_page_hash;
    protected $date_from;
    protected $phone1;
    protected $phone2;
    protected $partnerId;
    protected $apartment_id_assigned;
    protected $city_name;

    public function exchangeArray($data)
    {
        $this->id                    = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number            = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->guestLaguageIso       = (isset($data['guest_language_iso'])) ? $data['guest_language_iso'] : null;
        $this->guestEmail            = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->guestFirstName        = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName         = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->provide_cc_page_hash  = (isset($data['provide_cc_page_hash'])) ? $data['provide_cc_page_hash'] : null;
        $this->date_from             = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->phone1                = (isset($data['phone1'])) ? $data['phone1'] : null;
        $this->phone2                = (isset($data['phone2'])) ? $data['phone2'] : null;
        $this->partnerId             = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->apartment_id_assigned = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->city_name             = (isset($data['acc_city_name'])) ? $data['acc_city_name'] : null;
    }

    /**
     * @return mixed
     */
    public function getDateFrom()
    {
        return $this->date_from;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getResNumber()
    {
        return $this->res_number;
    }

    /**
     * @return mixed
     */
    public function getGuestLaguageIso()
    {
        return $this->guestLaguageIso;
    }

    /**
     * @return mixed
     */
    public function getGuestEmail()
    {
        return $this->guestEmail;
    }

    /**
     * @return string
     */
    public function getGuestFirstName()
    {
        return ucwords(strtolower($this->guestFirstName));
    }

    /**
     * @return string
     */
    public function getGuestLastName()
    {
        return ucwords(strtolower($this->guestLastName));
    }

    /**
     * @return mixed
     */
    public function getProvideCcPageHash()
    {
        return $this->provide_cc_page_hash;
    }

    /**
     * @return integer
     */
    public function getPartnerId()
    {
        return $this->partnerId;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param $res_number
     * @return $this
     */
    public function setResNumber($res_number)
    {
        $this->res_number = $res_number;
        return $this;
    }

    /**
     * @param $guestLaguageIso
     * @return $this
     */
    public function setGuestLaguageIso($guestLaguageIso)
    {
        $this->guestLaguageIso = $guestLaguageIso;
        return $this;
    }

    /**
     * @param $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param $guestFirstName
     * @return $this
     */
    public function setGuestFirstName($guestFirstName)
    {
        $this->guestFirstName = $guestFirstName;
        return $this;
    }

    /**
     * @param $guestLastName
     * @return $this
     */
    public function setGuestLastName($guestLastName)
    {
        $this->guestLastName = $guestLastName;
        return $this;
    }

    /**
     * @param $provide_cc_page_hash
     * @return $this
     */
    public function setProvideCcPageHash($provide_cc_page_hash)
    {
        $this->provide_cc_page_hash = $provide_cc_page_hash;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPhone1()
    {
        return $this->phone1;
    }

    /**
     * @return mixed
     */
    public function getPhone2()
    {
        return $this->phone2;
    }

    /**
     * @return mixed
     */
    public function getApartmentIdAssigned()
    {
        return $this->apartment_id_assigned;
    }

    /**
     * @return string
     */
    public function getCityName()
    {
        return $this->city_name;
    }

}
