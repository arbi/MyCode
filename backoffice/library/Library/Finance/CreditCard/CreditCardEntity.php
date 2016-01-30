<?php

namespace Library\Finance\CreditCard;

class CreditCardEntity
{
    protected $id;
    protected $ccNumber;
    protected $ccHolderName;
    protected $ccCVC;
    protected $ccYear;
    protected $ccMonth;
    protected $ccType;
    protected $token;
    protected $cvcPassed;
    protected $partnerId;
    protected $source;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getNumber()
    {
        return $this->ccNumber;
    }

    public function setNumber($ccNumber)
    {
        $this->ccNumber = $ccNumber;
    }

    public function getHolderName()
    {
        return $this->ccHolderName;
    }

    public function setHolderName($ccHolderName)
    {
        $this->ccHolderName = $ccHolderName;
    }

    public function getCVC()
    {
        return $this->ccCVC;
    }

    public function setCVC($ccCVC)
    {
        $this->ccCVC = $ccCVC;
    }

    public function getYear()
    {
        return $this->ccYear;
    }

    public function setYear($ccYear)
    {
        $this->ccYear = $ccYear;
    }

    public function getMonth()
    {
        return $this->ccMonth;
    }

    public function setMonth($ccMonth)
    {
        $this->ccMonth = $ccMonth;
    }

    public function getType()
    {
        return $this->ccType;
    }

    public function setType($ccType)
    {
        $this->ccType = $ccType;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getCVCPassed()
    {
        return $this->cvcPassed;
    }

    public function setCVCPassed($cvcPassed)
    {
        $this->cvcPassed = $cvcPassed;
    }

    public function getPartnerId()
    {
        return $this->partnerId;
    }

    public function setPartnerId($partnerId)
    {
        $this->partnerId = $partnerId;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }
}
