<?php

namespace DDD\Domain\Apartment\Statistics;

class ForBasicDataReview
{
    protected $avg;
    protected $count;

    public function exchangeArray($data)
    {
        $this->avg     = (isset($data['avg'])) ? $data['avg'] : null;
        $this->count = (isset($data['count'])) ? $data['count'] : null;
    }

	public function getAvg () {
		return $this->avg;
	}

	public function getCount () {
		return $this->count;
	}

}
