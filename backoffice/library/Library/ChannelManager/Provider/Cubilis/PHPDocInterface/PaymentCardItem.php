<?php

namespace Library\ChannelManager\Provider\Cubilis\PHPDocInterface;

abstract class PaymentCardItem {
	/**
	 * @var $cardCode string The type of the credit card. The value can be: “Visa”, “AmercianExpress”, “DinersClub”, “Mastercard”, “Discoverycard”, “JCBcard” en “XDeleted”.
	 */
	public $cardCode;

	/**
	 * @var $cardNumber string
	 */
	public $cardNumber;

	/**
	 * @var $seriesCode string
	 */
	public $seriesCode;

	/**
	 * @var $expireDate string
	 */
	public $expireDate;

	/**
	 * @var $cardHolderName string
	 */
	public $cardHolderName;
}
