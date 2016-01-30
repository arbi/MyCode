<?php

namespace DDD\Domain\Venue;


class GinocoinCharges
{
    protected $id;
    protected $amount;
    protected $perdayMaxPrice;

    public function exchangeArray($data)
    {
        $this->id               = (isset($data['id'])) ? $data['id'] : null;
        $this->amount           = (isset($data['amount'])) ? $data['amount'] : null;
        $this->perdayMaxPrice   = (isset($data['perday_max_price'])) ? $data['perday_max_price'] : null;
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
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return mixed
     */
    public function getPerdayMaxPrice()
    {
        return $this->perdayMaxPrice;
    }
}
