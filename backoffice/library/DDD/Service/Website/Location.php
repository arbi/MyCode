<?php
namespace DDD\Service\Website;

use DDD\Service\ServiceBase;
use Library\Utility\Helper;
use Library\Validator\ClassicValidator;
use DDD\Dao\Geolocation\Cities;
use DDD\Dao\Apartment\General as ProdGeneral;
use DDD\Dao\Geolocation\Poi;
use DDD\Dao\Geolocation\Details;
use Library\Constants\Objects;
use Library\Constants\Constants;
use Library\Utility\Currency;
use Library\Constants\DomainConstants;
use Library\Constants\WebSite;


class Location extends ServiceBase
{

    /**
     *
     * @var type
     */
    protected $_cityDao;

    /**
     *
     * @var type
     */
    protected $_productDao;
    /**
     *
     * @var type
     */
    protected $_poiDao;

    /**
     *
     * @param string $cityProvince
     * @return array
     */
    public function getCityByProvincCity($cityProvince)
    {
        $location = explode('--', $cityProvince);

        if (!isset($location[0])
            || !isset($location[1])
            || !ClassicValidator::checkCityName($location[0])
            || !ClassicValidator::checkCityName($location[1])
        ) {
            return false;
        }

        $cityName     = Helper::urlForSearch($location[0], TRUE);
        $provinceName = Helper::urlForSearch($location[1], TRUE);

        /* @var $cityDao \DDD\Dao\Geolocation\Cities */
        $cityDao = $this->getCityDao();

        $city = $cityDao->getCityByProvinceViaSlug($cityName, $provinceName);

        return [
            'city_data' => $city,
            'city_url'  => $location[0]
        ];
    }
    /**
     *
     * @param int $cityId
     * @return array
     */
    public function getApartmentListByCity($cityId, $cityUrl){
        $prodDao     = $this->getProdGeneralDao();
        $apartelList = $prodDao->getProdByCityRandom($cityId);
        //Media
        $imgDomain = DomainConstants::IMG_DOMAIN_NAME;
        $imgPath   = Website::IMAGES_PATH;
        $apartels  = [];
        $router    = $this->getServiceLocator()->get('router');

        foreach ($apartelList as $row){
            $img = Helper::getImgByWith($row['img1'], WebSite::IMG_WIDTH_SEARCH);
            $apartels[] = [
                'img'  => $img,
                'name' => $row['name'],
                'url'  => $router->assemble(['apartmentTitle' => $row['url'].'--'.$cityUrl], ['name' => 'apartment'])
            ];
        }
        return $apartels;
    }

    /**
     * @param int $cityId
     * @param string $cityProvince
     * @return array
     */
    public function getPoiListByCity($cityId, $cityProvince)
    {
        /* @var $poiDao \DDD\Dao\Geolocation\Poi */
        $poiDao    = $this->getPoiDao();
        $poiList   = $poiDao->getPoiList($cityId);

        $poiType   = [];
        $poiTypeId = 0;
        $router    = $this->getServiceLocator()->get('router');

        foreach ($poiList as $poi) {
            if ($poi['type_id'] != $poiTypeId) {
                $poiType[$poi['type_id']] = [
                    'poi_type_name' => $poi['poi_type_name'],
                    'poi_list' => [
                        [
                            'name' => $poi['poi_name'],
                            'url'  => $router->assemble(
                                [
                                    'action'       => 'location',
                                    'cityProvince' => $cityProvince,
                                    'poi'          => $poi['poi_slug']
                                ],
                                ['name' => 'location/child']
                            )
                        ]
                    ],
                ];
            } else {
                $poiType[$poi['type_id']]['poi_list'][] = [
                    'name' => $poi['poi_name'],
                    'url'  => $router->assemble(
                        [
                            'action'       => 'location',
                            'cityProvince' => $cityProvince,
                            'poi'          => $poi['poi_slug']
                        ],
                        ['name' => 'location/child']
                    )
                ];
            }

            $poiTypeId = $poi['type_id'];
        }

        return $poiType;
    }

    /**
     * @param int $cityId
     * @param string $poiSlug
     * @return boolean
     */
    public function getPoiData($cityId, $poiSlug)
    {
        if (!$poiSlug
            || !ClassicValidator::checkCityName($poiSlug)
        ) {
            return false;
        }

        /* @var $poiDao \DDD\Dao\Geolocation\Poi */
        $poiDao = $this->getPoiDao();
        $poi = $poiDao->getPoiDataBySlug($cityId, Helper::urlForSearch($poiSlug, TRUE));

        if($poi) {
            $poi['img'] = Helper::getImgByWith('/locations/' . $poi['detail_id'] . '/' . $poi['cover_image'], WebSite::IMG_WIDTH_LOCATION_BIG);
        }

        return $poi;
    }
    /**
     *
     * @param int $id
     * @return type
     */
    public function getCityData($id){
        $detailsDao = new Details($this->getServiceLocator(), 'ArrayObject');
        $details = $detailsDao->getDetailsById($id);
        if($details) {
            $details['img'] = Helper::getImgByWith('/locations/' . $id . '/' . $details['cover_image'], WebSite::IMG_WIDTH_LOCATION_BIG);
        }
        return $details;
    }

    /**
     *
     * @return type
     */
    public function getCityForLocation()
    {
        /* @var $cityDao \DDD\Dao\Geolocation\Cities */
        $cityDao  = $this->getCityDao();
        $cities   = $cityDao->getCityForLocation();

        $cityList = [];
        $router   = $this->getServiceLocator()->get('router');

        foreach ($cities as $city) {
            $cityProvince = $city['city_url'] .'--' . $city['province_url'];

            $cityList[] =  [
                'city_id'       => $city['id'],
                'country_id'    => $city['country_id'],
                'img'           => Helper::getImgByWith('/locations/' . $city['detail_id'] . '/' .$city['cover_image'], WebSite::IMG_WIDTH_LOCATION_MEDIUM, false, false, $city['detail_id']),
                'url'           => $router->assemble(['action'=>'location', 'cityProvince' => $cityProvince], ['name' => 'location/child']),
                'apartment_url' => 'search?city=' . $city['city_url'] . '&amp;show=all',
            ];
        }

        return $cityList;
    }

    /**
     *
     * @return array
     */
    public function getOptions($data){
        /* @var $websiteSearchService \DDD\Service\Website\Search */
        $websiteSearchService = $this->getServiceLocator()->get('service_website_search');
        $diffHours = $websiteSearchService->getDiffHoursForDate();

        $options['current_date'] = Helper::getCurrenctDateByTimezone($data['city_data']['timezone'], 'd-m-Y', $diffHours);
        $router                  = $this->getServiceLocator()->get('router');
        $url                     = $router->assemble([], ['name' => 'search']);
        $options['url']          = $url;
        $guest                   = Objects::getGuestList(['guest' => $this->getTextLineSite(1455), 'guests' => $this->getTextLineSite(1456)]);
        $options['guest']        = $guest;
        return $options;
    }

        /**
     *
     * @param type $domain
     * @return ArrayObject
     */
    private function getPoiDao($domain = 'ArrayObject')
    {
        if (!$this->_poiDao) {
            $this->_poiDao = new Poi($this->getServiceLocator(), $domain);
        }

        return $this->_poiDao;
    }
    /**
     *
     * @param type $domain
     * @return ArrayObject
     */
    private function getCityDao($domain = 'ArrayObject')
    {
        if (!$this->_cityDao) {
            $this->_cityDao = new Cities($this->getServiceLocator(), $domain);
        }

        return $this->_cityDao;
    }
    /**
     *
     * @param type $domain
     * @return ArrayObject
     */
    private function getProdGeneralDao($domain = 'ArrayObject')
    {
        if (!$this->_productDao) {
            $this->_productDao = new ProdGeneral($this->getServiceLocator(), $domain);
        }

        return $this->_productDao;
    }

    public function getPhoneByCountryId($countryId)
    {
        $countryDao = new \DDD\Dao\Geolocation\Countries($this->getServiceLocator(), 'ArrayObject');
        $phone = $countryDao->getPhoneByCountryId($countryId);

        return $phone;
    }
}
