<?php

namespace Library\ChannelManager\Provider\Cubilis\PHPDocInterface;

class UniqueIdItem {
	/**
	 * @var $id int
	 */
	public $id;

	/**
	 * @var $type string Choices: PAR, HOT
	 */
	public $type;

	/**
	 * @var $companyName string Optional parameter. Exists only if type equal to PAR
	 */
	public $companyName;
}
