<?php
namespace DDD\Service\Website;

use DDD\Dao\ApartmentGroup\ApartmentGroup;
use DDD\Service\ServiceBase;
use DDD\Dao\Apartment\AmenityItems as AmenitiesDao;
use DDD\Dao\ApartmentGroup\FacilityItems as FacilitiesDao;
use DDD\Dao\Apartment\General as GeneralDao;
use DDD\Dao\Apartment\Review as ReviewDao;
use DDD\Dao\Apartment\Inventory as InventoryDao;
use DDD\Dao\Apartment\Description as DescriptionDao;
use DDD\Dao\Apartment\Room as RoomDao;
use DDD\Dao\Apartment\Rate as RateDao;
use DDD\Dao\Apartment\Furniture as FurnitureDao;
use DDD\Dao\Location\City;

use Library\Validator\ClassicValidator;
use Library\Constants\Objects;
use Library\Utility\Currency;
use Library\Constants\DomainConstants;
use Library\Constants\WebSite;
use Library\Constants\Constants;
use Library\Utility\Debug;
use Library\Utility\Helper;

use Zend\Session\Container;

class Apartment extends ServiceBase
{
    protected $_generalDao;
    protected $_reviewDao;
    protected $_avDao;
    protected $_amenitiesDao;
    protected $_facilitiesDao;
    protected $_descriptionDao;
    protected $_textlineService;
    protected $_roomDao;
    protected $_rateDao;
    protected $_fururnitureDao;

    public function getApartment($cityApartel) {
        $cityApartel = explode('--', $cityApartel);

        if (!isset($cityApartel[1])
            || !ClassicValidator::checkApartmentTitle($cityApartel[0])
            || !ClassicValidator::checkApartmentTitle($cityApartel[1])
        ) {
            return false;
        }

        $apartel      = $otherParams['apartel'] = $cityApartel[0];
        $city         = $cityApartel[1];
    	$generalDao   = $this->getApartmentGeneralDao();
    	$descrDao     = $this->getDescriptionDao();
    	$roomDao      = $this->getRoomDao();
        $officeDao = new \DDD\Dao\Office\OfficeManager($this->getServiceLocator());
    	$furnitureDao = $this->getFurnitureDao();

        $apartmentAmenitiesDao = $this->getAmenitiesDao();
        $buildingFacilitiesDao = $this->getFacilitiesDao();

    	$general = $generalDao->getApartmentGeneralBySlug($apartel, Helper::urlForSearch($city, TRUE));

        if (!$general) {
            return false;
        }

        //change currency
        $userCurrency = $this->getCurrencySite();

        if ($userCurrency != $general['code']) {
            $currencyResult = $this->currencyConvert($general['price_avg'], $userCurrency, $general['code']);
            $general['price_avg'] = $currencyResult[0];
            $general['symbol'] = $currencyResult[1];
        }

        //images
        $imgDomain     = DomainConstants::IMG_DOMAIN_NAME;
        $imgPath       = Website::IMAGES_PATH;
        $images        = [];
        $checkHasImage = false;

        foreach ($general as $key => $img) {
              if (strpos($key, 'img') !== false && $img) {
                  $original = Helper::getImgByWith($img);
                  $smallImg = Helper::getImgByWith($img, WebSite::IMG_WIDTH_AMARTMENT_SMALL);
                  $bigImg   = Helper::getImgByWith($img, WebSite::IMG_WIDTH_AMARTMENT_BIG);

                  if ($original && $bigImg && $smallImg) {
                      $checkHasImage = true;
                      $images[] = [
                          'domain' => $imgDomain,
                          'big'    => $bigImg,
                          'small'  => $smallImg,
                          'orig'   => $original,
                      ];
                  }
              }
         }

         if (!$checkHasImage) {
             $noImg = Constants::VERSION . 'img/no_image.png';
             $images[] = [
                 'domain' => $noImg,
                 'big'    => $noImg,
                 'small'  => $noImg,
                 'orig'   => $noImg,
             ];
         }

         $otherParams['images'] = $images;

         //video
         if (isset($general['video']) && $general['video']) {
            $video = Helper::getVideoUrl($general['video']);

            if ($video) {
                $otherParams['video'] = [
	                'video_screen' => $video,
	                'src' => $general['video'],
                ];
            }
         }

        //facilities
        $tempFacilitiesData = $buildingFacilitiesDao->getApartmentBuildingFacilities($general['aprtment_id']);
        $facilities = [];
        foreach($tempFacilitiesData as $tempFacility) {
            $facilities[$tempFacility->getFacilityName()] = $tempFacility->getFacilityTextlineId();
        }
        unset($tempFacilitiesData);

        //amenities
        $tempAmenitiesData = $apartmentAmenitiesDao->getApartmentAmenities($general['aprtment_id']);
        $amenities = [];
        foreach($tempAmenitiesData as $tempAmenity) {
            $amenities[$tempAmenity->getAmenityName()] = $tempAmenity->getAmenityTextlineId();
        }
        unset($tempAmenitiesData);

        if (isset($facilities['Parking']) && $facilities['Parking']) {
            $otherParams['parking'] = true;
	    }

	    if (isset($amenitiesData['Free Wifi']) && $amenitiesData['Free Wifi']) {
		    $otherParams['internet'] = true;
	    }

        //furniture
        $furnitureData = $furnitureDao->getFurnitureLits($general['aprtment_id']);
        $otherParams['furnitures']  = $furnitureData;

        /* @var $websiteSearchService \DDD\Service\Website\Search */
        $websiteSearchService = $this->getServiceLocator()->get('service_website_search');
        $diffHours = $websiteSearchService->getDiffHoursForDate();

        $otherParams['current']     = Helper::getCurrenctDateByTimezone($general['timezone'], 'd-m-Y', $diffHours);
	    $general['city_name']       = $general['city_name'];
	    $general['city_slug']       = $general['city_slug'];
	    $otherParams['guestList']   = Objects::getGuestList([
		    'guest' => $this->getTextLineSite(1455),
		    'guests' => $this->getTextLineSite(1456),
	    ], true);
        $params = [
	        'general' => $general,
	        'amenities' => $amenities,
            'facilities' => $facilities,
	        'otherParams' => $otherParams,
        ];

    	return $params;
    }

    public function apartmentReviewList($data, $showAll = false) {
        if (!isset($data['apartment_id']) || !$data['apartment_id']) {
            return false;
        }

        $page           = (isset($data['page']) && $data['page'] > 0) ? (int)$data['page'] : 1;
        $apartment_id   = (int)$data['apartment_id'];
        $reviewDao      = $this->getApartmentReviewDao();
        $pageItemCount  = WebSite::REVIEW_PAGE_COUNT;
        $offset         = (int)($page - 1) * $pageItemCount;
        $reviews        = $reviewDao->getApartelReviews($apartment_id, $pageItemCount, $offset, $showAll);

        if (!$reviews || !$reviews['total']) {
            return false;
        }

        $totalPages = ($reviews['total'] > 0) ? ceil($reviews['total'] / $pageItemCount) : 1;

	    return [
		    'result' => $reviews['result'],
		    'totalPages' => $totalPages,
		    'total' => $reviews['total'],
	    ];
    }


    public function apartmentReviewCount($apartmentId) {
        if (!(int)$apartmentId) {
            return false;
        }

        $reviewDao = $this->getApartmentReviewDao();
        $reviews   = $reviewDao->apartmentReviewCount($apartmentId);

	    if ($reviews) {
            return $reviews['count'];
	    }

        return false;
    }

    /**
     * @param array $data
     * @return array
     */
    public function apartmentSearch($data)
    {
        $filter         = $this->filterSearchData($data);
        $result         = ['status' => '', 'result' => []];
	    $rateList       = [];
        $apartment      = $data['apartment'];
        $city           = $data['city'];
        $guest          = $data['guest'] > 0 ? (int)$data['guest'] : 1;
        $arrival        = date('Y-m-d', strtotime($data['arrival']));
        $departure      = date('Y-m-d', strtotime($data['departure']));
	    $nightCount     =  Helper::getDaysFromTwoDate($arrival, $departure);
	    $no_av_status   = 'no_av';

        $no_av_result   = [
            'arrival'   => Helper::dateForUrl($arrival),
            'departure' => Helper::dateForUrl($departure),
            'guest'     => $guest,
            'city'      => $city,
	    ];

	    if ($filter) {
            $rateDao  = $this->getInventoryDao();
            $response = $rateDao->getAvailableRates($apartment, Helper::urlForSearch($city), $guest, $arrival, $departure);
            $i        = 1;

            if ($response->count() > 0) {
                $currencySymbol = WebSite::DEFAULT_CURRENCY;
                $userCurrency   = $this->getCurrencySite();
                $currencyDao    = $this->getServiceLocator()->get('dao_currency_currency');
                $currencyResult = $currencyDao->fetchOne(['code' => $userCurrency]);

                if ($currencyResult) {
                    $currencySymbol = $currencyResult->getSymbol();
                }

                $currencyUtility = new Currency($currencyDao);

                foreach ($response as $row) {
                    //check user currency and apartment currency
                    if ($userCurrency != $row['code']) {
                        $price = $currencyUtility->convert($row['price'], $row['code'], $userCurrency);
                    } else {
                        $price = $row['price'];
                    }

                    //cancelation policy
                    $cancelationData                = $row;
                    $cancelationData['night_count'] = $nightCount;
                    $cancelation                    = $this->cancelationPolicy($cancelationData);

                    $discountPrice = 0;
                    $visitor       = new Container('visitor');

                    if (!is_null($visitor->partnerId) && (int)$visitor->partnerId) {
                        $partnerDao    = new \DDD\Dao\Partners\Partners($this->getServiceLocator());
                        $partnerInfo   = $partnerDao->fetchOne(['gid' => (int)$visitor->partnerId]);
                        $discountPrice = 0;
                        if ($partnerInfo && ceil($partnerInfo->getDiscount())) {
                            $discountPrice = number_format($price * (100 - $partnerInfo->getDiscount()) * 0.01, 2, '.', '');
                        }
                    }

                    //rateList
                    $rateList[] = [
                        'primary' => ($i === 1 ? true : false),
                        'rate'    => [
                            'id'   => $row['id'],
                            'name' => $row['name']
                        ],
                        'capacity'    => $row['capacity'],
                        'price'       => number_format($price, 2, '.', ''),
                        'total_price' => number_format($nightCount*$price, 2, '.', ''),
                        'currency'    => [
                            'name' => $userCurrency,
                            'sign' => $currencySymbol
                        ],
                        'policy' => [
                            'name'        => $cancelation['type'],
                            'description' => $cancelation['description']
                        ],
                        'discount' => [
                            'price' => $discountPrice,
                            'total' => number_format($nightCount * $discountPrice, 2, '.', '')
                        ]
                    ];

                    $i++;
               }

               $result['status'] = 'success';
               $result['result'] = $rateList;
            } else {
                $result['status'] = $no_av_status;
                $result['result'] = $no_av_result;
            }

            return $result;
        }

        return [
	        'status' => $no_av_status,
	        'result' => $no_av_result,
        ];
    }

    public function currencyConvert($price, $userCurrency, $accCurrency) {
        $currencySymbol = WebSite::DEFAULT_CURRENCY;
        $currencyDao    = $this->getServiceLocator()->get('dao_currency_currency');
        $currencyResult = $currencyDao->getCurrencyData($userCurrency);

        if ($currencyResult) {
            $currencySymbol = $currencyResult['symbol'];
        }

        $currencyUtility = new Currency($currencyDao);
        $price = $currencyUtility->convert($price, $accCurrency, $userCurrency);

        return [$price, $currencySymbol];
    }

        /**
     *
     * @param array $data
     * @return array
     */
    public function cancelationPolicy($data) {
        $result = ['type' => '', 'description' => ''];

        if (isset($data['is_refundable']) && $data['is_refundable'] == 2) {
            //None refundable
            $result['type']         = $this->getTextLineSite(845);
            $result['description'] .= $this->getTextLineSite(861);
        } elseif (isset($data['is_refundable']) && $data['is_refundable'] == 1) {
            //Refundable
            $result['type'] = $this->getTextLineSite(846);
            $time = (int)$data['refundable_before_hours'];

            if ($time > 48) {
                $time = ($time / 24) . ' ' . $this->getTextLineSite(977);
            } else {
                $time = $time . ' ' . $this->getTextLineSite(976);
            }

            //penalty
            $pen_val = '';

            switch ($data['penalty_type']) {
                case 1:
                    $pen_val = $data['penalty_percent'] . "%";
                    break;
                case 2:
                    $pen_val = $data['penalty_fixed_amount'] . ' ' . $data['code'];
                    break;
                case 3:
                    $night = $data['night_count'];

	                if ($night < $data['penalty_nights']) {
                        $pen_val = $night;
                    } else {
                        $pen_val = $data['penalty_nights'];
                    }

                    $pen_val .= ' ' . $this->getTextLineSite(862);
                    break;
            }
            $description = Helper::evaluateTextline($this->getTextLineSite(859),
                                                    [
                                                        '{{CXL_TIME}}' => $time,
                                                        '{{CXL_PENALTY}}' => $pen_val
                                                    ]);

            $result['description'] .= $description;
        }

        return $result;
    }

    /**
     * @param array $data
     * @return string
     */
    private function filterSearchData($data){
        if (!isset($data['city']) || !ClassicValidator::checkCityName($data['city']) ||
            !isset($data['apartment']) || !ClassicValidator::checkApartmentTitle($data['apartment']) ||
            !isset($data['guest']) || !is_numeric($data['guest']) || !$this->filterQueryData($data)) {
            return false;
        }

        return true;
    }

    /**
     * @param array $data
     * @return string
     */
    public function filterQueryData($data) {
        $currentDate = date('Y-m-d');
        $cityDao = new City($this->getServiceLocator(), 'ArrayObject');

        if (!isset($data['city'])) {
            $pageSlugExp = explode('--', $data['slug']);
            $citySlug = $pageSlugExp[1];
        } else {
            $citySlug = $data['city'];
        }

        if (isset($citySlug) && ClassicValidator::checkCityName($citySlug)) {
            $cityResp = $cityDao->getCityBySlug(Helper::urlForSearch($citySlug, TRUE));

            if ($cityResp) {
                /* @var $websiteSearchService \DDD\Service\Website\Search */
                $websiteSearchService = $this->getServiceLocator()->get('service_website_search');
                $diffHours = $websiteSearchService->getDiffHoursForDate();

                $currentDate = Helper::getCurrenctDateByTimezone($cityResp['timezone'], 'd-m-Y', $diffHours);
            }
        }

        if (!isset($data['arrival']) || !ClassicValidator::validateDate($data['arrival']) ||
            !isset($data['departure']) || !ClassicValidator::validateDate($data['departure']) ||
            strtotime($data['arrival']) < strtotime($currentDate) || strtotime($data['arrival']) >= strtotime($data['departure'])) {
            return false;
        }

        return true;
    }

    /**
     * @param $apartmentId
     * @return \DDD\Domain\ApartmentGroup\ForSelect[]
     */
    public function getApartelsByApartmentId($apartmentId)
    {
        /**
         * @var ApartmentGroup $apartmentGroupDao
         */
        $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $apartels = $apartmentGroupDao->getApartelsByApartmentId($apartmentId);

        return $apartels;
    }

    private function getApartmentGeneralDao($domain = 'ArrayObject') {
        if (!$this->_generalDao) {
    		$this->_generalDao = new GeneralDao($this->getServiceLocator(), $domain);
        }

    	return $this->_generalDao;
    }

    private function getRateDao($domain = 'ArrayObject') {
        if (!$this->_rateDao) {
    		$this->_rateDao = new RateDao($this->getServiceLocator(), $domain);
        }

    	return $this->_rateDao;
    }

    private function getInventoryDao($domain = 'ArrayObject') {
        if (!$this->_avDao) {
    		$this->_avDao = new InventoryDao($this->getServiceLocator(), $domain);
        }

    	return $this->_avDao;
    }

    private function getApartmentReviewDao($domain = 'ArrayObject') {
        if (!$this->_reviewDao) {
    		$this->_reviewDao = new ReviewDao($this->getServiceLocator(), $domain);
        }

    	return $this->_reviewDao;
    }

    public function getAmenitiesDao($domain = 'DDD\Domain\Apartment\Amenities\ApartmentAmenities') {
        if (!$this->_amenitiesDao) {
    		$this->_amenitiesDao = new AmenitiesDao($this->getServiceLocator(), $domain);
        }

    	return $this->_amenitiesDao;
    }

    public function getFacilitiesDao($domain = 'DDD\Domain\ApartmentGroup\BuildingFacilities') {
        if (!$this->_facilitiesDao) {
    		$this->_facilitiesDao = new FacilitiesDao($this->getServiceLocator(), $domain);
        }

    	return $this->_facilitiesDao;
    }

    private function getDescriptionDao($domain = 'ArrayObject') {
        if (!$this->_descriptionDao) {
    		$this->_descriptionDao = new DescriptionDao($this->getServiceLocator(), $domain);
        }

    	return $this->_descriptionDao;
    }

    private function getTextlineService() {
        if (!$this->_textlineService) {
    		$this->_textlineService = $this->getServiceLocator()->get('service_textline');
        }

    	return $this->_textlineService;
    }

    private function getRoomDao($domain = 'ArrayObject') {
        if (!$this->_roomDao) {
    		$this->_roomDao = new RoomDao($this->getServiceLocator(), $domain);
        }

    	return $this->_roomDao;
    }

    private function getFurnitureDao($domain = 'ArrayObject') {
        if (!$this->_fururnitureDao) {
    		$this->_fururnitureDao = new FurnitureDao($this->getServiceLocator(), $domain);
        }

    	return $this->_fururnitureDao;
    }
}
