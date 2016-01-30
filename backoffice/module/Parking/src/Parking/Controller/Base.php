<?php

namespace Parking\Controller;

use Library\Controller\ControllerBase;

class Base extends ControllerBase
{
	/**
	 * @access protected
	 * @var int
	 */
	protected $parkingLotId;

    /**
	 * @access public
	 * @param int $parkingLotId
	 */
	public function setParkingLotId($parkingLotId)
    {
		$this->parkingLotId = $parkingLotId;
	}
}