<?php

namespace Library\ChannelManager\Provider\Cubilis\PHPDocInterface;

abstract class ResRatePlanItem extends RatePlanItem {
	/**
	 * @var $effectiveDate string Ex. 2010-08-24
	 */
	public $effectiveDate;

	/**
	 * @var $isTaxInclusive bool
	 */
	public $isTaxInclusive;

	/**
	 * @return ResRatePlanDetail
	 */
	abstract public function getDetail();
}
