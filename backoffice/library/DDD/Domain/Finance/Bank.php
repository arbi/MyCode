<?php

namespace DDD\Domain\Finance;

class Bank
{
    protected $id;
    protected $name;
    protected $address;
    protected $bic;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->address = (isset($data['address'])) ? $data['address'] : null;
        $this->bic = (isset($data['bic'])) ? $data['bic'] : null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getBic()
    {
        return $this->bic;
    }
}
