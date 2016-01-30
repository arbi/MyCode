<?php

namespace DDD\Domain\User;

class UserLogin
{
    public $id;
    public $firstname;
    public $lastname;
    public $email;
    public $manager_id;
    public $start_date;
    public $vacation_days;
    public $personal_phone;
    public $business_phone;
    public $emergency_phone;
    public $house_phone;
    public $address;
    public $timezone;
    public $shift;
    public $city_id;
    public $birthday;
    public $country_id;
    public $department;
    public $position;
    public $vacation_days_per_year;

    /**
     * @var int
     */
    public $apartmentGroupId;

    public $avatar;
    public $system;
    public $alt_email;

    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->firstname  = (isset($data['firstname'])) ? $data['firstname'] : null;
        $this->lastname  = (isset($data['lastname'])) ? $data['lastname'] : null;
        $this->email  = (isset($data['email'])) ? $data['email'] : null;
        $this->manager_id  = (isset($data['manager_id'])) ? $data['manager_id'] : null;
        $this->start_date  = (isset($data['start_date'])) ? $data['start_date'] : null;
        $this->vacation_days  = (isset($data['vacation_days'])) ? $data['vacation_days'] : null;
        $this->personal_phone  = (isset($data['personal_phone'])) ? $data['personal_phone'] : null;
        $this->business_phone  = (isset($data['business_phone'])) ? $data['business_phone'] : null;
        $this->emergency_phone  = (isset($data['emergency_phone'])) ? $data['emergency_phone'] : null;
        $this->house_phone  = (isset($data['house_phone'])) ? $data['house_phone'] : null;
        $this->address  = (isset($data['address'])) ? $data['address'] : null;
        $this->timezone  = (isset($data['timezone'])) ? $data['timezone'] : null;
        $this->shift  = (isset($data['shift'])) ? $data['shift'] : null;
        $this->city_id  = (isset($data['city_id'])) ? $data['city_id'] : null;
        $this->birthday  = (isset($data['birthday'])) ? $data['birthday'] : null;
        $this->country_id  = (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->department  = (isset($data['department'])) ? $data['department'] : null;
        $this->position  = (isset($data['position'])) ? $data['position'] : null;
        $this->vacation_days_per_year  = (isset($data['vacation_days_per_year'])) ? $data['vacation_days_per_year'] : null;
        $this->apartmentGroupId  = (isset($data['apartment_group_id'])) ? $data['apartment_group_id'] : null;
        $this->avatar  = (isset($data['avatar'])) ? $data['avatar'] : null;
        $this->system  = (isset($data['system'])) ? $data['system'] : null;
        $this->alt_email  = (isset($data['alt_email'])) ? $data['alt_email'] : null;
    }

}
