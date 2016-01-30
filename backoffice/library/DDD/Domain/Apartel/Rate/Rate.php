<?php

namespace DDD\Domain\Apartel\Rate;

class Rate
{
    protected $id;
    protected $apartel_id;
    protected $apartel_type_id;
    protected $name;
    protected $active;
    protected $capacity;
    protected $type;
    protected $week_price;
    protected $weekend_price;
    protected $min_stay;
    protected $max_stay;
    protected $default_availability;
    protected $release_period_start;
    protected $release_period_end;
    protected $is_refundable;
    protected $refundable_before_hours;
    protected $penalty_type;
    protected $penalty_value;
    protected $cubilis_id;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->apartel_id = (isset($data['apartel_id'])) ? $data['apartel_id'] : null;
        $this->apartel_type_id = (isset($data['apartel_type_id'])) ? $data['apartel_type_id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->active = (isset($data['active'])) ? $data['active'] : null;
        $this->capacity = (isset($data['capacity'])) ? $data['capacity'] : null;
        $this->type = (isset($data['type'])) ? $data['type'] : null;
        $this->week_price = (isset($data['week_price'])) ? $data['week_price'] : null;
        $this->weekend_price = (isset($data['weekend_price'])) ? $data['weekend_price'] : null;
        $this->min_stay = (isset($data['min_stay'])) ? $data['min_stay'] : null;
        $this->max_stay = (isset($data['max_stay'])) ? $data['max_stay'] : null;
        $this->default_availability = (isset($data['default_availability'])) ? $data['default_availability'] : null;
        $this->release_period_start = (isset($data['release_period_start'])) ? $data['release_period_start'] : null;
        $this->release_period_end = (isset($data['release_period_end'])) ? $data['release_period_end'] : null;
        $this->is_refundable = (isset($data['is_refundable'])) ? $data['is_refundable'] : null;
        $this->refundable_before_hours = (isset($data['refundable_before_hours'])) ? $data['refundable_before_hours'] : null;
        $this->penalty_type = (isset($data['penalty_type'])) ? $data['penalty_type'] : null;
        $this->penalty_value = (isset($data['penalty_value'])) ? $data['penalty_value'] : null;
        $this->cubilis_id = (isset($data['cubilis_id'])) ? $data['cubilis_id'] : null;
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @return mixed
     */
    public function getApartelId()
    {
        return $this->apartel_id;
    }

    /**
     * @return mixed
     */
    public function getApartelTypeId()
    {
        return $this->apartel_type_id;
    }

    /**
     * @return mixed
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * @return mixed
     */
    public function getCubilisId()
    {
        return $this->cubilis_id;
    }

    /**
     * @return mixed
     */
    public function getDefaultAvailability()
    {
        return $this->default_availability;
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
    public function getIsRefundable()
    {
        return $this->is_refundable;
    }

    /**
     * @return mixed
     */
    public function getMaxStay()
    {
        return $this->max_stay;
    }

    /**
     * @return mixed
     */
    public function getMinStay()
    {
        return $this->min_stay;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getPenaltyType()
    {
        return $this->penalty_type;
    }

    /**
     * @return mixed
     */
    public function getPenaltyValue()
    {
        return $this->penalty_value;
    }

    /**
     * @return mixed
     */
    public function getRefundableBeforeHours()
    {
        return $this->refundable_before_hours;
    }

    /**
     * @return mixed
     */
    public function getReleasePeriodEnd()
    {
        return $this->release_period_end;
    }

    /**
     * @return mixed
     */
    public function getReleasePeriodStart()
    {
        return $this->release_period_start;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getWeekPrice()
    {
        return $this->week_price;
    }

    /**
     * @return mixed
     */
    public function getWeekendPrice()
    {
        return $this->weekend_price;
    }


}
