<?php

namespace Library\ChannelManager\Provider\Cubilis\PHPDocInterface;

abstract class ResRoomTypeItem extends RoomTypeItem {
	/**
	 * @var $configuration string If the room is booked in a package then the value of configuration is Package.
	 */
	public $configuration;

	/**
	 * @var $promotionCode int If the room is booked in a package then promotionCode contains the ID of the Package.
	 */
	public $promotionCode;

	/**
	 * @return ResRoomTypeDetail
	 */
	abstract public function getDetail();
}
