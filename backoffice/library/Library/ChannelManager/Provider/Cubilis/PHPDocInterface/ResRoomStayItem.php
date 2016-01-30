<?php
/**
 * Created by JetBrains PhpStorm.
 * User: developer
 * Date: 11/7/13
 * Time: 2:06 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Library\ChannelManager\Provider\Cubilis\PHPDocInterface;


abstract class ResRoomStayItem {
	/**
	 * Information missing from pdf
	 * @var $index int
	 */
	public $index;

	/**
	 * @var $totalAmountAfterTax float
	 */
	public $totalAmountAfterTax;

	/**
	 * @var $currency string
	 */
	public $currency;

	/**
	 * @var $status string
	 */
	public $status;

	/**
	 * @return ResRoomType
	 */
	abstract public function getRoomType();

	/**
	 * @return ResRatePlan
	 */
	abstract public function getRatePlan();

	/**
	 * @return ResGuestCount
	 */
	abstract public function getGuestCount();

	/**
	 * @return ResComment
	 */
	abstract public function getComment();
}
