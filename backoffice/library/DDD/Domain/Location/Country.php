<?php
namespace DDD\Domain\Location;

use DDD\Domain\Location\LocationAbstract;

class Country extends LocationAbstract
{
	protected $id;
	protected $iso;
	protected $name;

	public function exchangeArray($data) {
		parent::exchangeArray($data);
        $this->iso      = (isset($data['iso']))     ? $data['iso']      : null;
	}

	public function getId() {
		return $this->id;
	}

	public function getIso() {
		return $this->iso;
	}

	public function getName() {
		return $this->name;
	}
}
