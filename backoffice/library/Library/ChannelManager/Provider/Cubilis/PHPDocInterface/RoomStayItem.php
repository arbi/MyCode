<?php

namespace Library\ChannelManager\Provider\Cubilis\PHPDocInterface;

abstract class RoomStayItem {
	/**
	 * @return RoomType
	 */
	abstract public function getRoomType();

	/**
	 * @return RatePlan
	 */
	abstract public function getRatePlan();
}
