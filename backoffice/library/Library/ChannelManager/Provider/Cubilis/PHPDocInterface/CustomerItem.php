<?php

namespace Library\ChannelManager\Provider\Cubilis\PHPDocInterface;

abstract class CustomerItem {
	/**
	 * @var $language string
	 */
	public $language;

	/**
	 * @var $name string
	 */
	public $name;

	/**
	 * @var $surname string
	 */
	public $surname;

	/**
	 * @var $phone string
	 */
	public $phone;

	/**
	 * @var $email string
	 */
	public $email;

	/**
	 * @var $address string
	 */
	public $address;

	/**
	 * @var $city string
	 */
	public $city;

	/**
	 * @var $postalCode int
	 */
	public $postalCode;

	/**
	 * @var $country string
	 */
	public $country;
}
