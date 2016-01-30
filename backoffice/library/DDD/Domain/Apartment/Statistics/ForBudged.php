<?php

namespace DDD\Domain\Apartment\Statistics;

class ForBudged
{
    protected $id;
    protected $symbol;
    protected $monthly_cost;
    protected $startup_cost;
    protected $code;

    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->symbol = (isset($data['symbol'])) ? $data['symbol'] : null;
        $this->monthly_cost = (isset($data['monthly_cost'])) ? $data['monthly_cost'] : null;
        $this->startup_cost = (isset($data['startup_cost'])) ? $data['startup_cost'] : null;
        $this->code = (isset($data['code'])) ? $data['code'] : null;
    }

	public function getCode () {
		return $this->code;
	}

	public function getStartup_cost () {
		return $this->startup_cost;
	}

	public function getMonthly_cost () {
		return $this->monthly_cost;
	}

	public function getId () {
		return $this->id;
	}

	public function getSymbol () {
		return $this->symbol;
	}

}
