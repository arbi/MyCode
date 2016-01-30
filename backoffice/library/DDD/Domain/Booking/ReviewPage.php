<?php

namespace DDD\Domain\Booking;

class ReviewPage
{
    protected $id;
    protected $res_number;
    protected $review_page_hash;

    protected $apartment_id;
    protected $apartment_id_assigned;
    protected $acc_name;
    protected $acc_country_id;
    protected $acc_province_id;
    protected $acc_city_id;
    protected $acc_address;
    protected $acc_postal_code;

    protected $date_from;
    protected $date_to;

    protected $guestFirstName;
    protected $guestLastName;
    protected $guestEmail;

    protected $image;

    protected $check_in_time;
    protected $check_out_time;

    protected $guestCountryId;
    protected $guestCityName;

    protected $partnerId;

    public function exchangeArray($data)
    {
        $this->id                    = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number            = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->review_page_hash      = (isset($data['review_page_hash'])) ? $data['review_page_hash'] : null;

        $this->apartment_id          = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartment_id_assigned = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->acc_name              = (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->acc_country_id        = (isset($data['acc_country_id'])) ? $data['acc_country_id'] : null;
        $this->acc_province_id       = (isset($data['acc_province_id'])) ? $data['acc_province_id'] : null;
        $this->acc_city_id           = (isset($data['acc_city_id'])) ? $data['acc_city_id'] : null;
        $this->acc_address           = (isset($data['acc_address'])) ? $data['acc_address'] : null;
        $this->acc_postal_code       = (isset($data['acc_postal_code'])) ? $data['acc_postal_code'] : null;

        $this->date_from             = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->date_to               = (isset($data['date_to'])) ? $data['date_to'] : null;

        $this->guestFirstName        = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName         = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->guestEmail            = (isset($data['guest_email'])) ? $data['guest_email'] : null;

        $this->image                 = (isset($data['image'])) ? $data['image'] : null;

        $this->check_in_time         = (isset($data['check_in_time'])) ? $data['check_in_time'] : null;
        $this->check_out_time        = (isset($data['check_out_time'])) ? $data['check_out_time'] : null;

        $this->guestCountryId        = (isset($data['guest_country_id'])) ? $data['guest_country_id'] : null;
        $this->guestCityName         = (isset($data['guest_city_name'])) ? $data['guest_city_name'] : null;

        $this->partnerId             = (isset($data['partner_id'])) ? $data['partner_id'] : null;
    }

    public function getId() {
        return $this->id;
    }

    public function getResNumber() {
        return $this->res_number;
    }

    public function getReviewPageHash() {
        return $this->review_page_hash;
    }

    public function getApartmentId() {
        return $this->apartment_id;
    }

    public function getApartmentIdAssigned() {
        return $this->apartment_id_assigned;
    }

    public function getApartmentName() {
        return $this->acc_name;
    }

    public function getApartmentCountryId()
    {
        return $this->acc_country_id;
    }

    public function getAccProvinceId() {
        return $this->acc_province_id;
    }

    public function getApartmentCityId()
    {
        return $this->acc_city_id;
    }

    public function getAccAddress() {
        return $this->acc_address;
    }

    public function getAccPostalCode() {
        return $this->acc_postal_code;
    }

    public function getDateFrom() {
        return $this->date_from;
    }

    public function getDateTo() {
        return $this->date_to;
    }

    public function getGuestFirstName() {
        return ucwords(strtolower($this->guestFirstName));
    }

    public function getGuestLastName() {
        return ucwords(strtolower($this->guestLastName));
    }

    public function getGuestEmail() {
        return $this->guestEmail;
    }

    public function getImage() {
        return $this->image;
    }

    public function getCheckInTime() {
        return $this->check_in_time;
    }

    public function getCheckOutTime() {
        return $this->check_out_time;
    }

    public function getGuestCountryId() {
        return $this->guestCountryId;
    }

    public function getGuestCityName() {
        return $this->guestCityName;
    }

    public function getPartnerId() {
        return $this->partnerId;
    }
}