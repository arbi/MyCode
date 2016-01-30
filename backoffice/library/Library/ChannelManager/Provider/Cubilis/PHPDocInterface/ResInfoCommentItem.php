<?php

namespace Library\ChannelManager\Provider\Cubilis\PHPDocInterface;

abstract class ResInfoCommentItem {
	/**
	 * @var $guestViewable bool
	 */
	public $guestViewable;

	/**
	 * @var $name string|null Optional
	 */
	public $name;

	/**
	 * @var $creatorId int|null Optional
	 */
	public $creatorId;

	/**
	 * @var $text string
	 */
	public $text;
}
