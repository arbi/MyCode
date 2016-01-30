<?php

namespace Library\ChannelManager\Provider\Cubilis;

use Library\ChannelManager\ProviderAdapterInterface;
use Library\Utility\Debug;

class CubilisAdapter implements ProviderAdapterInterface {
	private $adaptee;

	public function __construct(Cubilis $cubilis) {
		$this->adaptee = $cubilis;
	}

	public function updateRate($params) {
		return $this->adaptee->updateRate($params);
	}

	public function checkRate($params) {
		return $this->adaptee->checkRate($params);
	}

	public function checkReservation($params) {
		return $this->adaptee->checkReservation($params);
	}

	public function confirm($params) {
		return $this->adaptee->notificationReport($params);
	}

	public function sendRaw($apartmentId, $xml)
    {
		return $this->adaptee->sendRaw($apartmentId, $xml);
	}
}
