<?php

namespace DDD\Domain\Partners;

class PartnerCityCommission
{
    protected $id;
    protected $partner_id;
    protected $city_id;
    protected $commission;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->partner_id = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->city_id = (isset($data['city_id'])) ? $data['city_id'] : null;
        $this->commission = (isset($data['commission'])) ? $data['commission'] : null;
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
    public function getPartnerId()
    {
        return $this->partner_id;
    }

    /**
     * @return mixed
     */
    public function getCityId()
    {
        return $this->city_id;
    }

    /**
     * @return mixed
     */
    public function getCommission()
    {
        return $this->commission;
    }


}