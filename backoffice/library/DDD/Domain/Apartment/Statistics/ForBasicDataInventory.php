<?php

namespace DDD\Domain\Apartment\Statistics;

class ForBasicDataInventory
{
    protected $id;
    protected $date;
    protected $max_price;
    protected $min_price;
    protected $max_availability;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->max_price = (isset($data['max_price'])) ? $data['max_price'] : null;
        $this->min_price = (isset($data['min_price'])) ? $data['min_price'] : null;
        $this->max_availability = (isset($data['max_availability'])) ? $data['max_availability'] : null;
    }

	public function getMax_availability () {
		return $this->max_availability;
	}

	public function getMin_price () {
		return $this->min_price;
	}

	public function getMax_price () {
		return $this->max_price;
	}

	public function getId () {
		return $this->id;
	}

	public function getDate () {
		return $this->date;
	}

}
