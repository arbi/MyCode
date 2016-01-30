<?php

namespace Website\View\Helper;

use Website\View\Helper\BaseHelper;

use Website\View\Helper\CityName;
use Website\View\Helper\ProvinceName;

use Library\Constants\DomainConstants;

class UrlToCity extends BaseHelper
{
    public function __invoke($cityId = FALSE)
    {
        if (!$cityId) {
            return FALSE;
        }

        $cityUrlParam = $this->getCitySlug($cityId).'--'.$this->getProvinceSlugByCityId($cityId);

        $router = $this->serviceLocator->get('router');

        $result = $router->assemble([
                'action' => 'location',
                'cityProvince' => $cityUrlParam
            ], [
                'name' => 'location/child'
            ]);

        return '//'.DomainConstants::WS_DOMAIN_NAME.$result;
    }

    public function getCityName($cityId)
    {
        $cityNameHelper = new CityName();
        $cityNameHelper->setServiceLocator($this->serviceLocator);

        $cityName = $cityNameHelper->getFromCache($cityId);

        if (!$cityName) {
            return FALSE;
        }

        return strtolower(str_replace(' ', '-', $cityName));
    }

    public function getCitySlug($cityId)
    {
        /* @var $cityDao \DDD\Dao\Geolocation\City */
        $cityDao = $this->serviceLocator->get('dao_geolocation_city');

        /* @var $cityDetails \DDD\Domain\Geolocation\City */
        $cityDetails = $cityDao->getCityById((int)$cityId);

        return $cityDetails->getSlug();
    }

    public function getProvinceName($cityId)
    {
        $provinceNameHelper = new ProvinceName();
        $provinceNameHelper->setServiceLocator($this->serviceLocator);

        $provinceDao = new \DDD\Dao\Location\Province($this->serviceLocator);
        $provinceId = $provinceDao->getProvinceIdByCityId($cityId);

        $provinceName = $provinceNameHelper->getFromCache($provinceId);

        if (!$provinceName) {
            return FALSE;
        }

        return strtolower(str_replace(' ', '-', $provinceName));
    }

    public function getProvinceSlugByCityId($cityId)
    {
        $provinceLocationDao = new \DDD\Dao\Location\Province($this->serviceLocator);
        $provinceId = $provinceLocationDao->getProvinceIdByCityId($cityId);

        /* @var $countryDao \DDD\Dao\Geolocation\Provinces */
        $provinceDao = $this->serviceLocator->get('dao_geolocation_provinces');

        /* @var $provinceDetails \DDD\Domain\Geolocation\Provinces */
        $provinceDetails = $provinceDao->getProvinceById((int)$provinceId);

        return $provinceDetails->getSlug();
    }
}
