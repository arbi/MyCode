<?php

namespace DDD\Service;

use DDD\Service\ServiceBase;
use Zend\Validator\Exception\InvalidArgumentException;
use Zend\Validator\Ip;

/**
 * Service class providing methods to work with MaxMind's Geolite Country database
 * @author Tigran Petrosyan
 * @package core
 * @subpackage core_service
 */
class GeoliteCountry extends ServiceBase
{
	/**
	 * Try to find country by given ip and concat it's name to ip
	 * @access public
	 * @param string $ipAddress
	 * @return string
	 */
	public function composeIPAndCountryNameString($ipAddress)
    {
		$composition = '';
		
		$ipValidator = new Ip();
		if ($ipValidator->isValid($ipAddress)) {
            /**
             * @var \DDD\Dao\GeoliteCountry\GeoliteCountry $geoliteCountryDao
             */
            $geoliteCountryDao = $this->getServiceLocator()->get('dao_geolite_country_geolite_country');
			
			$countryName = $geoliteCountryDao->getCountryNameByIp(ip2long($ipAddress));
			if ($countryName != '') {
				$composition =  $ipAddress . ' (' . $countryName . ')';
			} else {
                $composition =  $ipAddress;
            }
		} else {
			$composition = 'Invalid IP';
		}
		return $composition;
	}

    /**
     * @param string $ipAddress
     * @return array|\ArrayObject|null
     * @throws \Zend\Validator\Exception\InvalidArgumentException
     */
    public function getCountryDataByIp($ipAddress = '127.0.0.1')
    {
        $ipValidator = new Ip();
        
		if ($ipValidator->isValid($ipAddress)) {
            /**
             * @var \DDD\Dao\GeoliteCountry\GeoliteCountry $geoliteCountryDao
             */
            $geoliteCountryDao = $this->getServiceLocator()->get('dao_geolite_country_geolite_country');

            $return = $geoliteCountryDao->getCountryDataByIp(ip2long($ipAddress));

            return $return;
            
		} else {
			throw new InvalidArgumentException();
		}
    }
}