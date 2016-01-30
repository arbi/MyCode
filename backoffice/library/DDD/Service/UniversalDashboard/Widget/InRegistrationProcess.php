<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Service\ServiceBase;
use Library\Utility\Helper;
use Zend\Db\Sql\Expression;

/**
 * Methods to work with "Apartments In Registration" widget
 * @author Tigran Ghabuzyan
 */
final class InRegistrationProcess extends ServiceBase
{
	public function getApartmentsInRegistrationProcess()
    {
		/* @var $apartmentsDao \DDD\Dao\Apartment\General */
		$apartmentsDao = new \DDD\Dao\Apartment\General($this->getServiceLocator());

		$apartmentsInRegistrationProcess = $apartmentsDao->getApartmentsInRegistrationProcess();
		return $apartmentsInRegistrationProcess;
	}
    public function getApartmentsInRegistrationProcessCount()
    {
        /* @var $apartmentsDao \DDD\Dao\Apartment\General */
        $apartmentsDao = new \DDD\Dao\Apartment\General($this->getServiceLocator());

        $count = $apartmentsDao->getApartmentsInRegistrationProcessCount();
        return $count;
    }
}
