<?php
namespace DDD\Service\Website;

use DDD\Service\ServiceBase;
use Library\Validator\ClassicValidator;
use Library\Utility\Helper;

use FileManager\Constant\DirectoryStructure;

class Apartel extends ServiceBase
{
    const DEFAULT_REVIEWS_COUNT = 10;

    /**
     * @param $pageSlug
     * @return array|bool
     */
    public function getApartel($pageSlug)
    {

        // explode slug and get apartel name city name
        $pageSlug = explode('--', $pageSlug);
        if (!isset($pageSlug[1])
            || !ClassicValidator::checkApartmentTitle($pageSlug[0])
            || !ClassicValidator::checkApartmentTitle($pageSlug[1])
        ) {
            return false;
        }

        /**
         * @var $apartelDao \DDD\Dao\Apartel\General
         * @var $serviceLocation \DDD\Service\Website\Location
         * @var $relApartelDao \DDD\Dao\Apartel\RelTypeApartment
         * @var $apartelTypeDao \DDD\Dao\Apartel\Type
         * @var $apartmentService \DDD\Service\Website\Apartment
         */
        $apartelDao = $this->getServiceLocator()->get('dao_apartel_general');
        $serviceLocation = $this->getServiceLocator()->get('service_website_location');
        $apartelTypeDao = $this->getServiceLocator()->get('dao_apartel_type');
        $apartmentService = $this->getServiceLocator()->get('service_website_apartment');
        $router = $this->getServiceLocator()->get('router');

        $apartelSlug = $pageSlug[0];
        $apartelData = $apartelDao->getApartelDataForWebsite($apartelSlug);

        if (!$apartelData) {
            return false;
        }

        $data = [];
        $apartel = $pageSlug[0];
        $city = $pageSlug[1];
        $apartelId = $apartelData['id'];
        $data = $apartelData;
        $data['apartel'] = $apartel;
        $data['city'] = $city;
        $data['img'] = Helper::getImgByWith('/' . DirectoryStructure::FS_IMAGES_APARTEL_BG_IMAGE . $apartelId . '/' . $apartelData['bg_image']);
        $data['apartel_slug'] = $apartelSlug;

        // get options for search
        $dataOption['city_data']['timezone'] = $apartelData['timezone'];
        $options = $serviceLocation->getOptions($dataOption);

        // get review list
        $relApartelDao = $this->getServiceLocator()->get('dao_apartel_rel_type_apartment');
        $reviews = $relApartelDao->getReviewForWebsite($apartelId);

        // review score
        $reviewsScore = $relApartelDao->getReviewAVGScoreForYear($apartelId);

        //change currency
        $userCurrency = $this->getCurrencySite();

        // get room type data
        $roomTypeData = $apartelTypeDao->getRoomTypeForWebsite($apartelId);
        $roomTypes = [];
        foreach ($roomTypeData as $roomtype) {

            if ($userCurrency != $roomtype['code']) {
                $currencyResult = $apartmentService->currencyConvert($roomtype['price'], $userCurrency, $roomtype['code']);
                $roomtype['price'] = $currencyResult[0];
                $roomtype['symbol'] = $currencyResult[1];
            }

            if (strpos(strtolower($roomtype['name']), 'studio') !== false) {
                $roomtype['img'] = 'studio_one_bedroom.png';
                $roomtype['search_name'] = 'studio';
                $roomtype['code'] = $userCurrency;
                $roomtypeName = 'studio';
                $roomTypes[0] = $roomtype;
            } elseif (strpos(strtolower($roomtype['name']), 'one') !== false) {
                $roomtype['img'] = 'studio_one_bedroom.png';
                $roomtype['search_name'] = 'onebedroom';
                $roomtype['code'] = $userCurrency;
                $roomtypeName = 'onebedroom';
                $roomTypes[1] = $roomtype;
            } else {
                $roomtype['img'] = 'two_bedroom.png';
                $roomtype['search_name'] = 'twobedroom';
                $roomtype['code'] = $userCurrency;
                $roomtypeName = 'twobedroom';
                $roomTypes[2] = $roomtype;
            }

            $roomtype['search_url'] = "/search?apartel={$apartel}&guest=2&{$roomtypeName}=1";
        }

        ksort($roomTypes);

        return [
            'data' => $data,
            'options' => $options,
            'reviews' => $reviews,
            'reviewsScore' => $reviewsScore,
            'roomTypes' => $roomTypes,
        ];
    }

    /**
     * @param int $apartelId
     * @param int $count
     * @param int $offset
     * @return bool|\Zend\Db\ResultSet\ResultSet
     */
    public function getReviews($apartelId, $count = self::DEFAULT_REVIEWS_COUNT, $offset = 0)
    {
        try {
            /**
             * @var \DDD\Dao\Apartel\RelTypeApartment $relApartelDao
             */
            $relApartelDao = $this->getServiceLocator()->get('dao_apartel_rel_type_apartment');
            return $relApartelDao->getReviewForWebsite($apartelId, $count, (int)$offset);
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot get Reviews', [
                'apartel_id' => $apartelId,
                'count'      => $count,
                'offset'     => $offset,
            ]);
            return false;
        }
    }

    /**
     * @param int $apartelId
     * @return bool|\DDD\Domain\Apartel\Details\Details|false
     */
    public function getApartelGeneralData($apartelId)
    {
        try {
            /**
             * @var \DDD\Dao\Apartel\Details $apartelDetailsDao
             */
            $apartelDetailsDao = $this->getServiceLocator()->get('dao_apartel_details');
            return $apartelDetailsDao->getApartelDetailsById($apartelId);
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot get Apartel general data', [
                'apartel_id' => $apartelId
            ]);
            return false;
        }
    }

}
