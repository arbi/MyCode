<?php

namespace DDD\Domain\Booking;

class ForCharge
{
    protected $id;
    protected $resNumber;
    protected $partner_id;
    protected $customer_id;

    public function exchangeArray($data)
    {
        $this->id 			= (isset($data['id'])) ? $data['id'] : null;
        $this->resNumber 	= (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->partner_id 	= (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->customer_id 	= (isset($data['customer_id'])) ? $data['customer_id'] : null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getResNumber()
    {
        return $this->resNumber;
    }

    public function getPartnerId()
    {
        return $this->partner_id;
    }

    public function getCustomerId()
    {
        return $this->customer_id;
    }
}
