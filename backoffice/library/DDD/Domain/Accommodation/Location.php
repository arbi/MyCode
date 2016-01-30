<?php

namespace DDD\Domain\Accommodation;

class Location
{
    protected $id;

    protected $transport_description;

    public function exchangeArray($data) {
        $this->id			= (isset($data['id'])) ? $data['id'] : null;
        $this->transport_description 		= (isset($data['transport_description'])) ? $data['transport_description'] : null;
    }

	public function getId() {
		return $this->id;
	}

	public function getTransport_description() {
		return $this->transport_description;
	}
}
