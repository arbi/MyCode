<?php

namespace DDD\Domain\Partners;

class PartnerGcmValue
{
    protected $id;
    protected $partnerId;
    protected $key;
    protected $value;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->partnerId = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->key        = (isset($data['key'])) ? $data['key'] : null;
        $this->value      = (isset($data['value'])) ? $data['value'] : null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPartnerId()
    {
        return $this->partnerId;
    }

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }


}