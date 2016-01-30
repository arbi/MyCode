<?php

namespace DDD\Domain;

class Count
{
    protected $count;

    public function exchangeArray($data)
    {
        $this->count = (isset($data['count'])) ? $data['count'] : null;
    }

	public function getCount () {
		return $this->count;
	}
}