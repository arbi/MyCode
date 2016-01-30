<?php

namespace Apartment\Controller;

use Library\Controller\ControllerBase;

/**
 * @property int apartmentId
 */
class Base extends ControllerBase
{
	/**
	 * @var int
	 */
	protected $apartmentId;

    protected $apartmentStatus;

    /**
	 * @param int $apartmentId
	 */
	public function setApartmentID($apartmentId)
    {
		$this->apartmentId = $apartmentId;

        $apartmentGeneralDao = new \DDD\Dao\Apartment\General($this->getServiceLocator(), 'ArrayObject');
        $this->apartmentStatus = $apartmentGeneralDao->getStatusID($apartmentId)['status'];
	}
}
