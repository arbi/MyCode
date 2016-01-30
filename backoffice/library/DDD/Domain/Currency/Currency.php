<?php

namespace DDD\Domain\Currency;

class Currency
{
    protected $id;
    protected $name;
    protected $code;
    protected $symbol;
    protected $value;
    protected $date;
    protected $auto_update;
    protected $gate;
    protected $last_updated;
	protected $visible;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->code = (isset($data['code'])) ? $data['code'] : null;
        $this->symbol = (isset($data['symbol'])) ? $data['symbol'] : null;
        $this->value = (isset($data['value'])) ? $data['value'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->auto_update = (isset($data['auto_update'])) ? $data['auto_update'] : null;
        $this->gate = (isset($data['gate'])) ? $data['gate'] : null;
        $this->last_updated = (isset($data['last_updated'])) ? $data['last_updated'] : null;
        $this->visible = (isset($data['visible'])) ? $data['visible'] : null;
    }

	public function getId()
    {
		return $this->id;
	}
	public function setId($id)
    {
		$this->id = $id;
	}

	public function getName()
    {
		return $this->name;
	}
	public function setName($name)
    {
		$this->name = $name;
	}

	public function getCode()
    {
		return $this->code;
	}
	public function setCode($name)
    {
		$this->code = $name;
	}

	public function getSymbol()
    {
		return $this->symbol;
	}
	public function setSymbol($name)
    {
		$this->symbol = $name;
	}

	public function getValue()
    {
		return $this->value;
	}
	public function setValue($name)
    {
		$this->value = $name;
	}

    public function getDate()
    {
        return $this->date;
    }

	public function getAutoUpdate()
    {
		return $this->auto_update;
	}
	public function setAutoUpdate($name)
    {
		$this->auto_update = $name;
	}

	public function getGate()
    {
		return $this->gate;
	}
	public function setGate($name)
    {
		$this->gate = $name;
	}

	public function getLastUpdated()
    {
		return $this->last_updated;
	}
	public function setLastUpdated($name)
    {
		$this->last_updated = $name;
	}

	public function getVisible()
	{
		return (int) $this->visible;
	}
}
