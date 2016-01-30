<?php

namespace DDD\Domain\ApartmentGroup;

use Library\Constants\DomainConstants;

class ConciergeView
{

    public $id;
    public $ki_page_hash;
    public $guestFirstName;
    public $guestLastName;
    public $res_number;
    public $pax;
    protected $occupancy;
    public $date_to;
    public $arrivalStatus;
    public $parking;
    public $acc_name;
    public $guestBalance;
    public $housekeeping_comment;
    public $ki_page_status;
    public $unitNumber;
    protected $cc_part;
    protected $model;
    protected $check_charged;
    protected $provide_cc_page_status;
    protected $provide_cc_page_hash;
    protected $is_default;
    protected $is_default_email_vault;
    protected $cc_part_email_vault;
    protected $cc_type;
    protected $guestEmail;
    protected $keyTask;
    protected $firstDigits;
    protected $salt;
    protected $overbookingStatus;
    protected $fraudScore;

    public function exchangeArray($data)
    {
        $this->id            = (isset($data['id'])) ? $data['id'] : null;
        $this->ki_page_hash  = (isset($data['ki_page_hash'])) ? $data['ki_page_hash'] : null;
        $this->guestFirstName= (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->res_number    = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->pax           = (isset($data['pax'])) ? $data['pax'] : null;
        $this->occupancy     = (isset($data['occupancy'])) ? $data['occupancy'] : null;
        $this->date_to       = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->arrivalStatus = (isset($data['arrival_status'])) ? $data['arrival_status'] : null;
        $this->parking       = (isset($data['parking'])) ? $data['parking'] : null;
        $this->acc_name      = (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->guestBalance  = (isset($data['guest_balance'])) ? $data['guest_balance'] : null;
        $this->cc_part     = (isset($data['cc_part'])) ? $data['cc_part'] : null;
        $this->housekeeping_comment     = (isset($data['housekeeping_comment'])) ? $data['housekeeping_comment'] : null;
        $this->ki_page_status           = (isset($data['ki_page_status'])) ? $data['ki_page_status'] : null;
        $this->unitNumber               = (isset($data['unitNumber'])) ? $data['unitNumber'] : null;
        $this->model                    = (isset($data['model'])) ? $data['model'] : null;
        $this->check_charged            = (isset($data['check_charged'])) ? $data['check_charged'] : null;
        $this->provide_cc_page_status   = (isset($data['provide_cc_page_status'])) ? $data['provide_cc_page_status'] : null;
        $this->provide_cc_page_hash     = (isset($data['provide_cc_page_hash'])) ? $data['provide_cc_page_hash'] : null;
        $this->is_default               = (isset($data['is_default'])) ? $data['is_default'] : null;
        $this->is_default_email_vault   = (isset($data['is_default_email_vault'])) ? $data['is_default_email_vault'] : null;
        $this->cc_part_email_vault      = (isset($data['cc_part_email_vault'])) ? $data['cc_part_email_vault'] : null;
        $this->cc_type                  = (isset($data['cc_type'])) ? $data['cc_type'] : null;
        $this->guestEmail               = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->keyTask                  = (isset($data['key_task'])) ? $data['key_task'] : null;
        $this->firstDigits              = (isset($data['first_digits'])) ? $data['first_digits'] : null;
        $this->salt                     = (isset($data['salt'])) ? $data['salt'] : null;
        $this->overbookingStatus        = (isset($data['overbooking_status'])) ? $data['overbooking_status'] : null;
    }

    public function getCcType()
    {
        return $this->cc_type;
    }

    public function getIsDefault()
    {
        return $this->is_default;
    }

    public function getIsDefaultEmailVault()
    {
        return $this->is_default_email_vault;
    }

    public function getCcPartEmailVault()
    {
        return $this->cc_part_email_vault;
    }

    public function setHousekeepingComment($housekeeping_comment)
    {
        $this->housekeeping_comment = $housekeeping_comment;
    }

    public function getHousekeepingComment()
    {
        return $this->housekeeping_comment;
    }

    public function setUnitNumber($unitNumber)
    {
        $this->unitNumber = $unitNumber;
    }

    public function getUnitNumber()
    {
        return $this->unitNumber;
    }

    public function setKeyPageStatus($ki_page_status)
    {
        $this->ki_page_status = $ki_page_status;
    }

    public function getKiPageStatus()
    {
        return $this->ki_page_status;
    }

    public function getCCPart()
    {
        return $this->cc_part;
    }

    public function getGuestBalance()
    {
        return $this->guestBalance;
    }

    public function getApartmentName()
    {
        return $this->acc_name;
    }

    public function setAcc_name($val)
    {
        $this->acc_name = $val;
    }

    public function getParking()
    {
        return $this->parking;
    }

    public function setParking($val)
    {
        $this->parking = $val;
    }

    public function getDate_to()
    {
        return $this->date_to;
    }

    public function setDate_to($val)
    {
        $this->date_to = $val;
    }

    public function getPAX()
    {
        return $this->pax;
    }

    public function setPAX($val)
    {
        $this->pax = $val;
    }

    public function getReservationNumber()
    {
        return $this->res_number;
    }

    public function setRes_number($val)
    {
        $this->res_number = $val;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($val)
    {
        $this->id = $val;
    }

    public function getGuestFirstName()
    {
        return $this->guestFirstName;
    }

    public function setGuestFirstName($val)
    {
        $this->guestFirstName = $val;
    }

    public function getGuestLastName()
    {
        return $this->guestLastName;
    }

    public function setGuestLastName($val)
    {
        $this->guestLastName = $val;
    }

    public function getKiPageHash()
    {
        return $this->ki_page_hash;
    }

    public function getKiPageGodModeCode()
    {
        return substr(md5($this->ki_page_hash), 12, 5);
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getCheckCharged()
    {
        return $this->check_charged;
    }

    public function getProvideCcPageStatus()
    {
        return $this->provide_cc_page_status;
    }

    /**
     * @return int
     */
    public function getArrivalStatus()
    {
        return $this->arrivalStatus;
    }

    /**
     * @return int | null
     */
    public function getOccupancy()
    {
        return $this->occupancy;
    }

    public function getGuestEmail()
    {
        return strtolower($this->guestEmail);
    }

    /**
     * @return mixed
     */
    public function getKeyTask()
    {
        return $this->keyTask;
    }

    /**
     * @return mixed
     */
    public function getFirstDigits()
    {
        return $this->firstDigits;
    }

    public function setFirstDigits($firstDigits)
    {
        $this->firstDigits = $firstDigits;
    }

    /**
     * @return mixed
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @return mixed
     */
    public function getOverbookingStatus()
    {
        return $this->overbookingStatus;
    }

    /**
     * @return int
     */
    public function getFraudScore()
    {
        return $this->fraudScore;
    }

    /**
     * @param int|string $fraudScore
     */
    public function setFraudScore($fraudScore)
    {
        $this->fraudScore = (is_numeric($fraudScore)) ? $fraudScore : 0;
    }
}
