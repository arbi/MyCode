<?php

namespace Library\ChannelManager\Provider\Cubilis\PHPDocInterface;

abstract class ReservationItem {
	/**
	 * @var $creationDatetime string Ex. 2010-05-30T10:51:38
	 */
	public $creationDatetime;

	/**
	 * @var $creatorID string ID of the reservation
	 */
	public $creatorId;

	/**
	 * @var $status string
	 * The status of the reservation. When Version=2.0, the value can be: “new”, “cancelled”,
	 * “modified” or “deleted”. Reservations that still needs be confirmed, will be sent with status modified.
	 * The reservation can be sent a second time with status cancelled or with status new, depending on the choice
	 * of the customer. When Version=2.02, the value can be: “Reserved”, “Cancelled”, “Modify”, “Request denied” or
	 * “Waitlisted”. Reserved is the status of a new reservation, Request denied is the status of a deleted reservation and
	 * Waitlisted is the status of reservation that still needs to be confirmed.
	 * A reservation which has been cancelled or modified doesn’t have to be preceded by a reservation with status
	 * “new” or “Reserved”.
	 * Cubilis will send at least the status and the id of the reservation to the pms system, all other fields can be empty.
	 */
	public $status;

	/**
	 * @var $customerIP string
	 * When the reservation was made through Logis Manager, TerminalID contains the ip address of the customer. When the
	 * reservation was made through another partner, it contains the value “127.0.0.1”.
	 */
	public $customerIP;

	/**
	 * @return UniqueId
	 */
	abstract public function getUniqueId();

	/**
	 * @return ResRoomStay
	 */
	abstract public function getRoomStay();

	/**
	 * @return ResInfo
	 */
	abstract public function getInfo();
}
