<?php

namespace Library\ChannelManager\Provider\Cubilis\PHPDocInterface;

abstract class HotelRoomListItem {
	/**
	 * @var $hotelId int
	 */
	public $hotelId;

	/**
	 * @return RoomStay
	 */
	abstract public function getRoomStay();
}
