<?php

namespace DDD\Domain\Currency;

class CurrencyVault
{
    protected $id;
    protected $currencyId;
    protected $code;
    protected $value;
    protected $date;

    public function exchangeArray($data)
    {
        $this->id  = (isset($data['id'])) ? $data['id'] : null;
        $this->currencyId = (isset($data['currencyId'])) ? $data['currencyId'] : null;
        $this->code = (isset($data['code'])) ? $data['code'] : null;
        $this->value = (isset($data['value'])) ? $data['value'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
    }

	public function getId()
	{
		return $this->id;
	}

	public function getCurrencyId()
	{
		return $this->currencyId;
	}

	public function getCode()
	{
		return $this->code;
	}

    public function getValue()
    {
        return $this->value;
    }

	public function getDate()
	{
		return $this->date;
	}
}
