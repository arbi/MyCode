<?php
namespace DDD\Service\Website;

use DDD\Service\ServiceBase;
use DDD\Dao\Location\City as CityDao;
use DDD\Dao\Booking\Booking as BookingDao;
use DDD\Dao\Blog\Blog;
use Library\Utility\Helper;
use Library\Constants\WebSite;


class Index extends ServiceBase
{
    protected $_bookingWebDao;
    /**
     *
     * @return array
     */
    public function getOptions() {
        //search options
        $searchService = $this->getServiceLocator()->get('service_website_search');
        $options       = $searchService->getOptionsForSearch();

        //location options
        $cacheService      = $this->getServiceLocator()->get('service_website_cache');
        $keyLocationsCache = 'locations_for_home_en';
        $router            = $this->getServiceLocator()->get('router');

        if (($rowsLocations = $cacheService->get($keyLocationsCache))) {
            $options['locations'] = $rowsLocations;
        } else {
            $cityDao    = new CityDao($this->getServiceLocator(), 'ArrayObject');
            $citiesList = $cityDao->getCityListForIndex();
            $cities     = [];

            foreach ($citiesList as $city) {
                $cityProvince = $city['city_slug'] .'--' . $city['province_slug'];
                $img          = Helper::getImgByWith('/locations/' . $city['detail_id'] . '/' . $city['cover_image'], WebSite::IMG_WIDTH_LOCATION_MEDIUM);

                $cities[$city['ordering']] = [
                    'city_id'            => $city['city_id'],
                    'country_id'         => $city['country_id'],
                    'capacity'           => $city['capacity'],
                    'spent_nights'       => $city['spent_nights'],
                    'sold_future_nights' => $city['sold_future_nights'],
                    'img'                => $img,
                    'url'                => $router->assemble(
                        ['action'=>'location', 'cityProvince' => $cityProvince],
                        ['name' => 'location/child']
                    )
                ];
            }
            ksort($cities);
            $options['locations'] = $cities;
            $cacheService->set($keyLocationsCache, $cities);
        }
        //country count
        $keyCountryCashe = 'country_count_for_home';
        if (($countryCount = $cacheService->get($keyCountryCashe))) {
            $options['countryCount'] = $countryCount;
        } else {
            $bookingDao = $this->getBookingWebDao();
            $countries               = $bookingDao->getBookingCountryEmailCount('guest_country_id');
            $options['countryCount'] = ($countries) ? $countries['count'] : 0;
            $cacheService->set($keyCountryCashe, $options['countryCount']);
        }
        //people count
        $keyPeopleCache = 'people_count_for_home';
        if (($peopleCount = $cacheService->get($keyPeopleCache))) {
            $options['peopleCount'] = $peopleCount;
        } else {
            $bookingDao             = $this->getBookingWebDao();
            $peoples                = $bookingDao->getBookingCountryEmailCount('guest_email');
            $options['peopleCount'] = ($peoples) ? $peoples['count'] : 0;
            $cacheService->set($keyPeopleCache, $options['peopleCount']);
        }
        //blog list
        $blogDao           = new Blog($this->getServiceLocator(), 'ArrayObject');
        $bloges            = $blogDao->getBlogForWebIndex();
        $options['bloges'] = $bloges;

        $router            = $this->getServiceLocator()->get('router');
        $url               = $router->assemble([], ['name' => 'search']);
        $options['action'] = $url;

        return $options;
    }

    private function getBookingWebDao()
    {
        if (!$this->_bookingWebDao) {
            $this->_bookingWebDao = new BookingDao($this->getServiceLocator(), 'ArrayObject');
        }
        return $this->_bookingWebDao;
    }

}
