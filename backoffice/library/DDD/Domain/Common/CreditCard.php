<?php

namespace DDD\Domain\Common;

/**
 * Class CreditCard
 * @package DDD\Domain\Common
 *
 * @author Tigran Petrosyan
 */
class CreditCard
{
    /**
     * @var string card number, see more - http://en.wikipedia.org/wiki/Bank_card_number
     */
    protected $number;

    /**
     * @var string 3 or 4 digits card security code, see more - http://en.wikipedia.org/wiki/Card_security_code
     */
    protected $cvc;

    /**
     * @var string card holder full name
     */
    protected $holderName;

    /**
     * @var string 2 digits expiration year
     */
    protected $expirationYear;

    /**
     * @var string 2 digits expiration month
     */
    protected $expirationMonth;

    public function exchangeArray($data)
    {
        $this->number       = (isset($data['cc_number'])) ? $data['cc_number'] : null;
        $this->cvc          = (isset($data['cc_cvc'])) ? $data['cc_cvc'] : null;
        $this->holderName   = (isset($data['cc_holder_name'])) ? $data['cc_holder_name'] : null;
        $this->expirationYear   = (isset($data['cc_exp_year'])) ? $data['cc_exp_year'] : null;
        $this->expirationMonth  = (isset($data['cc_exp_month'])) ? $data['cc_exp_month'] : null;
    }

    /**
     * @param string $cvc
     */
    public function setCvc($cvc)
    {
        $this->cvc = $cvc;
    }

    /**
     * @return string
     */
    public function getCvc()
    {
        return $this->cvc;
    }

    /**
     * @param string $expirationMonth
     */
    public function setExpirationMonth($expirationMonth)
    {
        $this->expirationMonth = $expirationMonth;
    }

    /**
     * @return string
     */
    public function getExpirationMonth()
    {
        return $this->expirationMonth;
    }

    /**
     * @param string $expirationYear
     */
    public function setExpirationYear($expirationYear)
    {
        $this->expirationYear = $expirationYear;
    }

    /**
     * @return string
     */
    public function getExpirationYear()
    {
        return $this->expirationYear;
    }

    /**
     * @param string $holderName
     */
    public function setHolderName($holderName)
    {
        $this->holderName = $holderName;
    }

    /**
     * @return string
     */
    public function getHolderName()
    {
        return $this->holderName;
    }

    /**
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }
}
