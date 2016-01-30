<?php

namespace DDD\Domain\Accommodation;

class Statistics
{
    protected $id;
    protected $name;
    protected $city_name;
    protected $pax;
    protected $bedrooms;
    protected $building;
    protected $oc_percent;
    protected $date;
    protected $month;
    protected $month_name;
    protected $year;

    /**
     * @var int
     */
    protected $availability;
    protected $day_count;

    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->city_name = (isset($data['city_name'])) ? $data['city_name'] : null;
        $this->pax = (isset($data['pax'])) ? $data['pax'] : null;
        $this->bedrooms = (isset($data['bedrooms'])) ? $data['bedrooms'] : null;
        $this->building = (isset($data['building'])) ? $data['building'] : null;
        $this->oc_percent = (isset($data['oc_percent'])) ? $data['oc_percent'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->month = (isset($data['month'])) ? $data['month'] : null;
        $this->month_name = (isset($data['month_name'])) ? $data['month_name'] : null;
        $this->year = (isset($data['year'])) ? $data['year'] : null;
        $this->availability = (isset($data['availability'])) ? $data['availability'] : null;
        $this->day_count = (isset($data['day_count'])) ? $data['day_count'] : null;
    }

	public function getDay_count () {
		return $this->day_count;
	}

    /**
     * @return int
     */
    public function getAvailability()
    {
		return $this->availability;
	}

	public function getYear () {
		return $this->year;
	}

	public function getMonth_name () {
		return $this->month_name;
	}

	public function getMonth () {
		return $this->month;
	}

	public function getDate () {
		return $this->date;
	}

	public function getOc_percent () {
		return $this->oc_percent;
	}

	public function getCity_name () {
		return $this->city_name;
	}

	public function getBedrooms () {
		return $this->bedrooms;
	}

	public function getPax () {
		return $this->pax;
	}

	public function getBuilding () {
		return $this->building;
	}

	public function getId () {
		return $this->id;
	}

	public function getName () {
		return $this->name;
	}
}
