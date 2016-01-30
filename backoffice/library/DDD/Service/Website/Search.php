<?php

namespace DDD\Service\Website;

use DDD\Service\ServiceBase;
use Library\Validator\ClassicValidator;
use Library\Constants\WebSite;
use Library\Constants\Constants;
use DDD\Dao\Location\City;
use DDD\Dao\Apartment\General;
use Library\Constants\Objects;
use Library\Utility\Helper;
use Library\Constants\DomainConstants;
use Library\Utility\Currency;

class Search extends ServiceBase
{
    const DIFF_HOURS_FOR_BO_USERS     = '-24';
    const DIFF_HOURS_FOR_ALL_VISITORS = '0';

    /**
     * @param array $data
     * @return array
     */
    public function getOptions($data)
    {
        $params     = $this->getOptionsForSearch();
        $filterData = $this->filterSearchData($data);

        if ($filterData) {
            $params['status'] = 'error';
            $params['msg']    = $filterData;
            return $params;
        }

        if (isset($data['city'])) {

            $citySlug = $data['city'];
            $cityDao   = new City($this->getServiceLocator(), 'ArrayObject');
            $cityResp  = $cityDao->getCityBySlug($citySlug);
            $params['city_url'] = $citySlug;

            if (!$cityResp) {
                $params['status'] = 'error';
                $params['msg']    = $this->getTextLineSite(1220);
                return $params;
            }

            $cityId = $cityResp['id'];
            $timezone = $cityResp['timezone'];
        } elseif (isset($data['apartel'])) {
            /**
             * @var \DDD\Dao\Apartel\General $apartelDao
             */
            $apartelSlug = $data['apartel'];
            $apartelDao = $this->getServiceLocator()->get('dao_apartel_general');
            $apartelData = $apartelDao->getApartelDataBySlug($apartelSlug);
            if (!$apartelData) {
                 $params['status'] = 'error';
                 $params['msg']    = $this->getTextLineSite(1220);
                 return $params;
            }

            $params['apartel_url']  = $apartelSlug;
            $params['apartel_name'] = $apartelData['apartel_name'];
            $cityId = $apartelData['city_id'];
            $timezone = $apartelData['timezone'];
        } else {
            return [
                'status' => 'error'
            ];
        }

        $params['current_date'] = Helper::getCurrenctDateByTimezone($timezone, 'd-m-Y');
        $correcrData            = $this->correctData($data);
        $params['city_id']      = $cityId;
        $params['guest_user']   = $correcrData['guest'];
        $params['page']         = $correcrData['page'];
        $params['arrival']      = ($correcrData['arrival']) ? Helper::dateForSearch($correcrData['arrival']) : '';
        $params['departure']    = ($correcrData['departure']) ? Helper::dateForSearch($correcrData['departure']) : '';
        $params['status']       = 'success';

        return $params;
    }

    /**
     * @return array
     */
    public function getOptionsForSearch()
    {
        $guest            = Objects::getGuestList(['guest' => $this->getTextLineSite(1455), 'guests' => $this->getTextLineSite(1456)]);
        $params['cities'] = $this->getCityListForSearch();
        $params['guest']  = $guest;
        $params['current_date'] = date('d-m-Y');

        return $params;
    }

    /**
     * @return type
     */
    public function getCityListForSearch()
    {
        $cityDao = $this->getCityDao();
        $cities = $cityDao->getCityForSearch();
        $citiesList = [];

        $diffHours = $this->getDiffHoursForDate();

        foreach ($cities as $city) {
            $city['current_date'] = Helper::getCurrenctDateByTimezone($city['timezone'], 'd-m-Y', $diffHours);

            array_push($citiesList, $city);
        }

        return $citiesList;
    }

    /**
     * @param array $data
     * @return array
     */
    public function searchApartmentList($data, $getAll = false)
    {
        /**
         * @var \DDD\Dao\Currency\Currency $currencyDao
         */
        $currencyDao = $this->getServiceLocator()->get('dao_currency_currency');

        $filterData  = $this->filterSearchData($data);
        $apartelList = $apartels = $options = [];
        $error       = false;
        $totalPages  = 1;
        $queryString = '';
        if ($filterData) {
            return [
                'status' => 'error',
                'msg'    => $filterData,
            ];
        }

        $correcrData        = $this->correctData($data);
        $bedrooms           = $this->defineBedrooms($data);
        $guest              = $correcrData['guest'];
        $page               = $correcrData['page'];
        $arrival            = $correcrData['arrival'];
        $departure          = $correcrData['departure'];

        $apartelGeneralDao  = $this->getApartelGeneralDao();
        $pageItemCount      = WebSite::PAGINTAION_ITEM_COUNT;
        $offset             = (int)($page - 1) * $pageItemCount;

        if (isset($data['city'])) {
            $city = $data['city'];
            // Has date
            if ($arrival && $departure) {
                $apartelsResult = $apartelGeneralDao->getApartmentsByCityDate($city, $arrival, $departure, $guest, $pageItemCount, $offset, $bedrooms);
                $options['price_text'] = $this->getTextLineSite(1210);
            } else {
                $apartelsResult = $apartelGeneralDao->getApartmentsCity($city, $guest, $pageItemCount, $offset, $getAll, $bedrooms);
                $options['price_text'] = $this->getTextLineSite(1333);
            }
        } elseif (isset($data['apartel'])) {
            $apartel = $data['apartel'];
            // Has date
            if ($arrival && $departure) {
                $apartelsResult = $apartelGeneralDao->getApartmentsByApartelDate($apartel, $arrival, $departure, $guest, $pageItemCount, $offset, $bedrooms);
                $options['price_text'] = $this->getTextLineSite(1210);
            } else {
                $apartelsResult = $apartelGeneralDao->getApartmentsApartel($apartel, $guest, $pageItemCount, $offset, $getAll, $bedrooms);
                $options['price_text'] = $this->getTextLineSite(1333);
            }
        } else {
            return [
                'status' => 'error'
            ];
        }

        $apartmentList      = $apartelsResult['result'];
        $total              = $apartelsResult['total'];
        $totalPages         = ($total > 0) ? ceil($total / $pageItemCount) : 1;

        if (!$apartmentList->count()) {
            return [
                'status' => 'no_av',
                'msg'    => $this->getTextLineSite(1218),
            ];
        }

        $visitorLoc = $this->getVisitorCountry();

        // Change currency
        $userCurrency   = $this->getCurrencySite();
        $currencySymbol = WebSite::DEFAULT_CURRENCY;
        $currencyResult = $currencyDao->fetchOne(['code' => $userCurrency]);

        if ($currencyResult) {
            $currencySymbol = $currencyResult->getSymbol();
        }

        $currencyUtility = new Currency($currencyDao);
        $query_array = [];

        if ($arrival && $departure) {
            array_push($query_array, 'arrival=' . Helper::dateForUrl($arrival));
            array_push($query_array, 'departure=' . Helper::dateForUrl($departure));
            array_push($query_array, 'guest=' . $guest);

            $queryString = '?' . implode('&', $query_array);
        } elseif ($guest >= 1) {
            $queryString = '?guest='.$guest;
        } elseif ($getAll) {
            $queryString = '?show=reviews';
        }

        $apartmentList = iterator_to_array($apartmentList);
        // add apartel id if apartel reservation
        if (isset($data['apartel'])) {
            $queryString .=  ($queryString ? '&' : '?') . 'apartel_id=' . current($apartmentList)['apartment_group_id'];
        }

        foreach ($apartmentList as $al) {
            // Generate image
            $noImg = false;

            if ($al['img1']) {
                if ($img = Helper::getImgByWith($al['img1'], WebSite::IMG_WIDTH_SEARCH)) {
                    $noImg = true;
                    $al['image'] = $img;
                }
            }

            if (!$noImg) {
                $al['image'] = Constants::VERSION . 'img/no_image.png';
            }

            // Calculate percent
            if ($arrival && $departure) {
                $al['percent'] = round(($al['price_max'] - $al['price_min']) / $al['price_max'] * 100);
            } else {
                $al['percent'] = rand(10, 13);
                $al['rate_name'] = 'Non refundable';
            }

            // User currency price
            if ($userCurrency != $al['code']) {
                $price = $currencyUtility->convert($al['price_min'], $al['code'], $userCurrency);
                $al['price_min'] = $price;
                $al['symbol'] = $currencySymbol;
            }

            $al['url_to_search'] = 'apartment/' . $al['url'] . '--' . $al['slug'] . $queryString;

            // Sale percent
            array_push($apartels, $al);
        }

        // Pagination view
        $paginatinView = $this->paginationViewItem($total, $page, $pageItemCount);

        return [
            'apartelList'   => $apartels,
            'totalPages'    => $totalPages,
            'status'        => 'success',
            'paginatinView' => $paginatinView,
            'options'       => $options,
            'visitorLoc'    => $visitorLoc,
        ];
    }

    private function correctData($data)
    {
        $guest              = (isset($data['guest']) && $data['guest'] > 0) ? (int)$data['guest'] : 1;
        $page               = (isset($data['page']) && $data['page'] > 0) ? (int)$data['page'] : 1;
        $date               = $this->getFixedDate($data);
        $arrival            = $date['arrival'];
        $departure          = $date['departure'];

        return [
            'guest' => $guest,
            'page' => $page,
            'arrival' => $arrival,
            'departure' => $departure,
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    private function defineBedrooms($data)
    {
        $bedrooms = [];

        if (isset($data['studio']) && (int)$data['studio'] === 1) {
            array_push($bedrooms, 0);
        }

        if (isset($data['onebedroom']) && (int)$data['onebedroom'] === 1) {
            array_push($bedrooms, 1);
        }

        if (isset($data['twobedroom']) && (int)$data['twobedroom'] === 1) {
            for ($i = 2; $i <= 10; $i++) {
                array_push($bedrooms, $i);
            }
        }

        return $bedrooms;
    }

    /**
     *
     * @param array $data
     * @return array
     */
    public function getFixedDate($data){
        $currentDate = date('Y-m-d');
        $cityDao     = new City($this->getServiceLocator(), 'ArrayObject');
        if(isset($data['city']) && ClassicValidator::checkCityName($data['city'])) {
            $cityResp     = $cityDao->getCityBySlug($data['city']);
            if ($cityResp) {

                $diffHousr = $this->getDiffHoursForDate();

                $currentDate = Helper::getCurrenctDateByTimezone($cityResp['timezone'], 'd-m-Y', $diffHousr);
            }
        }
        $arrival     = (isset($data['arrival']) && $data['arrival'] && ClassicValidator::validateDate($data['arrival'])) ? date('Y-m-d', strtotime($data['arrival']))  : '';
        $departure   = (isset($data['departure']) && $data['departure'] && ClassicValidator::validateDate($data['departure'])) ? date('Y-m-d', strtotime($data['departure']))  : '';
        if($arrival && $departure && strtotime($arrival) > strtotime($departure)) {
            $arrival = $departure = '';
        } elseif(($arrival && strtotime($currentDate) > strtotime($arrival)) || ($departure && strtotime($currentDate) > strtotime($departure))) {
            $arrival = $departure = '';
        } elseif(!$arrival && $departure) {
            $departure = '';
        } elseif($arrival && !$departure) {
            $departure = date('Y-m-d', strtotime($arrival.'+1 day'));
        }
        return ['arrival' => $arrival, 'departure' => $departure];
    }

    /**
     *
     * @param array $data
     * @return string
     */
    public function filterSearchData($data){
        $error = '';
        //city
        if (isset($data['city']) && !ClassicValidator::checkCityName($data['city'])) {
            $error .= $this->getTextLineSite(1220);
        }

        if (isset($data['apartel']) && !ClassicValidator::checkApartmentTitle($data['apartel'])) {
            $error .= $this->getTextLineSite(1220);
        }

        return $error;
    }

    /**
     *
     * @param int $totalCount
     * @param int $currentPage
     * @param int $viewCount
     * @return string
     */
    private function paginationViewItem($totalCount, $currentPage, $viewCount)
    {
        if ($totalCount <= $viewCount) {
            return '';
        }

        $viewFirst = ($currentPage == 1) ? 1 : ($currentPage - 1) * $viewCount;
        $viewSecnd = (($currentPage * $viewCount) <= $totalCount) ? $currentPage * $viewCount : $totalCount;

        return Helper::evaluateTextline(
            $this->getTextLineSite(1215),
            [
                '{{PAGE_COUNT}}' => ("$viewFirst-$viewSecnd"),
                '{{ITEM_COUNT}}' => $totalCount
            ]
        );
    }

    /**
     *
     */
    public function autocompleteSearch($txt)
    {
        $cityDao = new City($this->getServiceLocator(), 'ArrayObject');
        $cities  = $cityDao->getCityForSearch($txt);
        $result  = [];

        foreach ($cities as $city) {
            $result[] = ['value' => $city['city_url'], 'slug' => Helper::urlForSite($city['city_url']), 'type' => 'city'];
        }

        $apartmentDao = $this->getApartelGeneralDao();
        $apartments   = $apartmentDao->getApartmentSearch($txt);

        foreach ($apartments as $apartment) {
            $result[] = [
                'value' => $apartment['name'],
                'slug' => Helper::urlForSite($apartment['url']) . '--' . Helper::urlForSite($apartment['location_slug']), 'type' => 'apartment'];
        }

        return $result;
    }

    public function getDiffHoursForDate()
    {
        $diffHours = self::DIFF_HOURS_FOR_ALL_VISITORS;

        if ($this->checkBackofficeUserStatus()) {
            $diffHours = self::DIFF_HOURS_FOR_BO_USERS;
        }

        return $diffHours;
    }

    private function checkBackofficeUserStatus()
    {
        try {
            $boUser = false;

            if (isset($_COOKIE['backoffice_user'])) {
                $backofficeUserId = $_COOKIE['backoffice_user'];

                /* @var $userManagerService \DDD\Dao\User\UserManager */
                $userManagerService = $this->getServiceLocator()->get('dao_user_user_manager');
                $userData           = $userManagerService->getUserById($backofficeUserId, false, ['id']);

                if ($userData) {
                    $boUser = true;
                }
            }

            return $boUser;
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot check BO user status');

            return false;
        }
    }

    /**
     *
     * @param  string $domain
     * @return \DDD\Dao\Location\City
     */
    private function getCityDao($domain = 'ArrayObject')
    {
        return new City($this->getServiceLocator(), $domain);
    }

    /**
     *
     * @param  string $domain
     * @return \DDD\Dao\Apartment\General
     */
    private function getApartelGeneralDao($domain = 'ArrayObject')
    {
        return new General($this->getServiceLocator(), $domain);
    }
}
