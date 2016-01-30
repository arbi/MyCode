<?php

namespace Library\ChannelManager\Provider\Cubilis\PHPDocInterface;

abstract class ResInfo {
	/**
	 * @var $timeSpanStart string Ex. 2010-08-24T14:00:00
	 */
	public $timeSpanStart;

	/**
	 * @var $timeSpanEnd string Ex. 2010-08-27
	 */
	public $timeSpanEnd;

	/**
	 * @var $totalAmountAfterTax float
	 */
	public $totalAmountAfterTax;

	/**
	 * @var $currency string
	 */
	public $currency;

	/**
	 * @return ReservationId
	 */
	abstract public function getReservationId();

	/**
	 * @return Profile
	 */
	abstract public function getProfile();

	/**
	 * @return PaymentCard
	 */
	abstract public function getPaymentCard();

	/**
	 * @return ResInfoComment
	 */
	abstract public function getComment();
}
