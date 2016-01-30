<?php

namespace DDD\Domain\Booking;

class ChargeForView
{
    protected $addonsType;
    protected $amount;
    protected $apartmentCurrency;
    protected $date;
    protected $addonsValue;
    protected $taxType;
    protected $nightlyDate;
    protected $addon;
    protected $currencySymbol;

    public function exchangeArray($data)
    {
        $this->addonsType = (isset($data['addons_type'])) ? $data['addons_type'] : null;
        $this->amount = (isset($data['acc_amount'])) ? $data['acc_amount'] : null;
        $this->apartmentCurrency = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->addonsValue = (isset($data['addons_value'])) ? $data['addons_value'] : null;
        $this->taxType = (isset($data['tax_type'])) ? $data['tax_type'] : null;
        $this->nightlyDate = (isset($data['reservation_nightly_date'])) ? $data['reservation_nightly_date'] : null;
        $this->addon = (isset($data['addon'])) ? $data['addon'] : null;
        $this->currencySymbol = (isset($data['currency_symbol'])) ? $data['currency_symbol'] : null;
    }

    public function getCurrencySymbol()
    {
        return $this->currencySymbol;
    }

    public function getAddon()
    {
        return $this->addon;
    }

    public function getTaxType()
    {
        return $this->taxType;
    }

    public function getAddonsValue()
    {
        return $this->addonsValue;
    }

    public function getAddonsType()
    {
        return $this->addonsType;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getApartmentCurrency()
    {
        return $this->apartmentCurrency;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getNightlyDate()
    {
        return $this->nightlyDate;
    }
}
