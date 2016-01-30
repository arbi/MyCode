<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Service\ServiceBase;
use Library\Utility\Helper;
use Zend\Db\Sql\Expression;

/**
 * Methods to work with "Suspended Apartments" widget
 * @author Tigran Petrosyan
 */
final class SuspendedApartments extends ServiceBase
{
    /**
     * @return \ArrayObject|\DDD\Domain\UniversalDashboard\Widget\SuspendedApartments[]
     */
    public function getSuspendedApartments()
    {
		/* @var $apartmentGeneralDao \DDD\Dao\Apartment\General */
		$apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');

		$suspendedApartments = $apartmentGeneralDao->getSuspendedApartments();

		return $suspendedApartments;
	}
    /**
     * @return int
     */
    public function getSuspendedApartmentsCount()
    {
		/* @var $apartmentGeneralDao \DDD\Dao\Apartment\General */
		$apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');

		$suspendedApartments = $apartmentGeneralDao->getSuspendedApartmentsCount();

		return $suspendedApartments;
	}
}
