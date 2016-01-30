<?php

namespace DDD\Domain\User;

class User
{
    protected $id;
    protected $disabled;
    protected $firstname;
    protected $lastname;
    protected $email;
    protected $internal_number;
    protected $manager_id;
    protected $start_date;

    /**
     * Last working day of user
     * @var string
     */
    protected $endDate;

    protected $vacation_days;
    protected $personal_phone;
    protected $business_phone;
    protected $emergency_phone;
    protected $house_phone;
    protected $asana_id;
    protected $addressPermanent;
    protected $addressResidence;
    protected $timezone;
    protected $shift;
    protected $scheduleType;
    protected $scheduleStart;
    protected $city_id;
    protected $city;
    protected $birthday;
    protected $country_id;
    protected $position;
    protected $vacation_days_per_year;
    protected $employment;

    /**
     * @var int
     */
    protected $apartmentGroupId;

    protected $avatar;
    protected $system;
    protected $external;
    protected $alt_email;
    protected $periodOfEvaluation;
    protected $previousEvaluation;
    protected $nextEvaluation;
    protected $startDate;
    protected $password;
    protected $livingCity;

    /**
     * Identifying weather employee attended to 3 neXt sessions or not
     * @var bool
     */
    protected $badgeNext;

    /**
     * @var int
     */
    protected $reportingOfficeId;

    protected $lastLogin;
    protected $departmentId;
    protected $sickDays;

    protected $ginocoinLimitAmount;
    protected $ginocoinPin;

    protected $currencyId;
    protected $currencyCode;


    public function exchangeArray($data)
    {
        $this->id                     = (isset($data['id'])) ? $data['id'] : null;
        $this->disabled               = (isset($data['disabled'])) ? $data['disabled'] : null;
        $this->firstname              = (isset($data['firstname'])) ? $data['firstname'] : null;
        $this->lastname               = (isset($data['lastname'])) ? $data['lastname'] : null;
        $this->email                  = (isset($data['email'])) ? $data['email'] : null;
        $this->internal_number        = (isset($data['internal_number'])) ? $data['internal_number'] : null;
        $this->manager_id             = (isset($data['manager_id'])) ? $data['manager_id'] : null;
        $this->start_date             = (isset($data['start_date'])) ? $data['start_date'] : null;
        $this->endDate                = (isset($data['end_date'])) ? $data['end_date'] : null;
        $this->vacation_days          = (isset($data['vacation_days'])) ? $data['vacation_days'] : null;
        $this->personal_phone         = (isset($data['personal_phone'])) ? $data['personal_phone'] : null;
        $this->business_phone         = (isset($data['business_phone'])) ? $data['business_phone'] : null;
        $this->emergency_phone        = (isset($data['emergency_phone'])) ? $data['emergency_phone'] : null;
        $this->house_phone            = (isset($data['house_phone'])) ? $data['house_phone'] : null;
        $this->asana_id               = (isset($data['asana_id'])) ? $data['asana_id'] : null;
        $this->addressPermanent       = (isset($data['address_permanent'])) ? $data['address_permanent'] : null;
        $this->addressResidence       = (isset($data['address_residence'])) ? $data['address_residence'] : null;
        $this->timezone               = (isset($data['timezone'])) ? $data['timezone'] : null;
        $this->shift                  = (isset($data['shift'])) ? $data['shift'] : null;
        $this->scheduleType           = (isset($data['schedule_type'])) ? $data['schedule_type'] : null;
        $this->scheduleStart          = (isset($data['schedule_start'])) ? $data['schedule_start'] : null;
        $this->city_id                = (isset($data['city_id'])) ? $data['city_id'] : null;
        $this->city                   = (isset($data['city'])) ? $data['city'] : null;
        $this->birthday               = (isset($data['birthday']) && $data['birthday'] != '0000-00-00') ? $data['birthday'] : null;
        $this->country_id             = (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->livingCity             = (isset($data['living_city'])) ? $data['living_city'] : null;
        $this->position               = (isset($data['position'])) ? $data['position'] : null;
        $this->vacation_days_per_year = (isset($data['vacation_days_per_year'])) ? $data['vacation_days_per_year'] : null;
        $this->apartmentGroupId       = (isset($data['apartment_group_id'])) ? $data['apartment_group_id'] : null;
        $this->avatar                 = (isset($data['avatar'])) ? $data['avatar'] : null;
        $this->system                 = (isset($data['system'])) ? $data['system'] : null;
        $this->external               = (isset($data['external'])) ? $data['external'] : null;
        $this->alt_email              = (isset($data['alt_email'])) ? $data['alt_email'] : null;
        $this->periodOfEvaluation     = (isset($data['period_of_evaluation'])) ? $data['period_of_evaluation'] : null;
        $this->startDate              = (isset($data['start_date'])) ? $data['start_date'] : null;
        $this->password               = (isset($data['password'])) ? $data['password'] : null;
        $this->reportingOfficeId      = (isset($data['reporting_office_id'])) ? $data['reporting_office_id'] : null;
        $this->lastLogin              = (isset($data['last_login'])) ? $data['last_login'] : null;
        $this->badgeNext              = (isset($data['badge_next'])) ? $data['badge_next'] : null;
        $this->nextEvaluation         = (isset($data['next_evaluation'])) ? $data['next_evaluation'] : null;
        $this->employment             = (isset($data['employment'])) ? $data['employment'] : null;
        $this->departmentId           = (isset($data['department_id'])) ? $data['department_id'] : null;
        $this->sickDays               = (isset($data['sick_days'])) ? $data['sick_days'] : null;
        $this->ginocoinLimitAmount    = (isset($data['ginocoin_limit_amount'])) ? $data['ginocoin_limit_amount'] : null;
        $this->ginocoinPin            = (isset($data['ginocoin_pin'])) ? $data['ginocoin_pin'] : null;
        $this->currencyId             = (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->currencyCode           = (isset($data['currency_code'])) ? $data['currency_code'] : null;
    }

    public function getPassword()
    {
    	return $this->password;
    }

    public function getStartDate()
    {
    	return $this->startDate;
    }

    public function getDisabled()
    {
    	return $this->disabled;
    }

    public function getAlt_email()
    {
        return $this->alt_email;
    }

    /**
     * @return int
     */
    public function getApartmentGroupId()
    {
        return $this->apartmentGroupId;
    }

    /**
     * @param $val
     * @return $this
     */
    public function setApartmentGroupId($val)
    {
        $this->apartmentGroupId = $val;
        return $this;
    }

    public function getVacation_days_per_year()
    {
        return $this->vacation_days_per_year;
    }

    public function setVacation_days_per_year($val)
    {
        $this->vacation_days_per_year = $val;
        return $this;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($val)
    {
        $this->position = $val;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($val)
    {
        $this->id = $val;
        return $this;
    }

    public function getCity_id()
    {
        return $this->city_id;
    }

    public function setCity_id($val)
    {
        $this->city_id = $val;
        return $this;
    }

	public function getBirthday()
    {
		return $this->birthday;
	}

	public function setBirthday($val)
    {
		$this->birthday = $val;
		return $this;
	}

    public function getCountry_id()
    {
        return $this->country_id;
    }

    public function setCountry_id($val)
    {
        $this->country_id = $val;
        return $this;
    }

    public function getFirstName()
    {
        return $this->firstname;
    }

    public function setFirstName($val)
    {
        $this->firstname = $val;
        return $this;
    }

    public function getLastName()
    {
        return $this->lastname;
    }

    public function setLastName($val)
    {
        $this->lastname = $val;
        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($val)
    {
        $this->email = $val;
        return $this;
    }

    public function getInternalNumber()
    {
        return $this->internal_number;
    }

    public function setInternalNumber($internal_number)
    {
        $this->internal_number = $internal_number;
        return $this;
    }

    public function getManager_id()
    {
        return $this->manager_id;
    }

    public function setManager_id($val)
    {
        $this->manager_id = $val;
        return $this;
    }

    public function getStart_date()
    {
        return $this->start_date;
    }

    public function getEndDate()
    {
    	return $this->endDate;
    }

    public function setStart_date($val)
    {
        $this->start_date = $val;
        return $this;
    }

    public function getVacation_days()
    {
        return $this->vacation_days;
    }

    public function setVacation_days($val)
    {
        $this->vacation_days = $val;
        return $this;
    }

    public function getPersonal_phone()
    {
        return $this->personal_phone;
    }

    public function setPersonal_phone($val)
    {
        $this->personal_phone = $val;
        return $this;
    }

    public function getBusiness_phone()
    {
        return $this->business_phone;
    }

    public function setBusiness_phone($val)
    {
        $this->business_phone = $val;
        return $this;
    }

    public function getEmergency_phone()
    {
        return $this->emergency_phone;
    }

    public function setEmergency_phone($val)
    {
        $this->emergency_phone = $val;
        return $this;
    }

    public function getHouse_phone()
    {
        return $this->house_phone;
    }

    public function getAsanaId()
    {
        return $this->asana_id;
    }

    public function setHouse_phone($val)
    {
        $this->house_phone = $val;
        return $this;
    }

    public function getAddressPermanent()
    {
        return $this->addressPermanent;
    }

    public function getAddressResidence()
    {
        return $this->addressResidence;
    }

    public function setTimezone($val)
    {
        $this->timezone = $val;
        return $this;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function setShift($val)
    {
        $this->shift = $val;
        return $this;
    }

    public function getShift()
    {
        return $this->shift;
    }

	public function getAvatar()
    {
		return $this->avatar;
	}

	public function setAvatar($val)
    {
		$this->avatar = $val;
		return $this;
	}

    public function getSystem()
    {
		return $this->system;
	}

    public function isExternal()
    {
        return $this->external;
    }

	public function setSystem($val)
    {
		$this->system = $val;
		return $this;
	}

    public function getPeriodOfEvaluation()
    {
		return $this->periodOfEvaluation;
	}

	public function setPeriodOfEvaluation($val)
    {
		$this->periodOfEvaluation = $val;
		return $this;
	}

    public function getPreviousEvaluation()
    {
        return $this->previousEvaluation;
	}

    public function getNextEvaluation() {
        return $this->nextEvaluation;
	}

	public function setPreviousEvaluation($val)
    {
		$this->previousEvaluation = $val;
		return $this;
	}

    public function getFullName() {
        return $this->firstname.' '.$this->lastname;
    }

    /**
     * @return int
     */
    public function getReportingOfficeId()
    {
		return $this->reportingOfficeId;
	}

    /**
     * @param $officeId int
     * @return $this
     */
    public function setReportingOfficeId($officeId)
    {
		$this->reportingOfficeId = $officeId;
		return $this;
	}

    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getBadgeNext()
    {
        return $this->badgeNext;
    }

     public function getEmployment()
     {
        return $this->employment;
    }

    public function setEmployment($employment)
    {
        $this->employment = $employment;
        return $this;
    }

    public function getLivingCity()
    {
        return $this->livingCity;
    }

    public function getDepartmentId()
    {
        return $this->departmentId;
    }

    public function setSickDays($sickDays)
    {
        $this->sickDays = $sickDays;
        return $this;
    }

    public function getSickDays()
    {
        return $this->sickDays;
    }

    /**
     * @return int
     */
    public function getScheduleType()
    {
        return $this->scheduleType;
    }

    /**
     * @return string
     */
    public function getScheduleStart()
    {
        return $this->scheduleStart;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return int
     */
    public function getExternal()
    {
        return $this->external;
    }

    /**
     * @return mixed
     */
    public function getGinocoinLimitAmount()
    {
        return $this->ginocoinLimitAmount;
    }

    /**
     * @return mixed
     */
    public function getGinocoinPin()
    {
        return $this->ginocoinPin;
    }

    /**
     * @return mixed
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    /**
     * @return mixed
     */
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }
}
