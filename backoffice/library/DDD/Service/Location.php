<?php

namespace DDD\Service;

use DDD\Dao\Location\Country;
use DDD\Domain\Geolocation\Countries;
use DDD\Domain\Geolocation\Provinces;

use Library\Utility\Debug;
use Library\Utility\Helper;
use Library\Upload\Images;
use Library\Constants\DbTables;
use FileManager\Constant\DirectoryStructure;
use Library\ActionLogger\Logger as ActionLogger;

use Zend\Db\Sql\Select;

class Location extends ServiceBase
{
	const IS_SEARCHABLE = 1;
	/**
	 * @access protected
	 * @var \DDD\Dao\Geolocation\Details
	 */
	protected $locationDetailsDao = null;

	/**
	 * @access protected
	 * @var \DDD\Dao\Geolocation\Continents
	 */
	protected $continentDao = null;

	/**
	 * @access protected
	 * @var \DDD\Dao\Geolocation\Poitype
	 */
	protected $poiTypeDao = null;

    const LOCATION_TYPE_CONTINENT = 1;
    const LOCATION_TYPE_COUNTRY   = 2;
    const LOCATION_TYPE_PROVINCE  = 4;
    const LOCATION_TYPE_CITY      = 8;
    const LOCATION_TYPE_POI       = 16;

    const TAX_INCLUDED     = 1;
    const TAX_EXCLUDED     = 0;

    const POSTAL_CODE_NO_SHOW  = 1;
    const POSTAL_CODE_OPTIONAL = 2;
    const POSTAL_CODE_REQUIRED = 3;

    const CITY_ID_HOLLYWOOD_LOS_ANGELES = 48;
    const CITY_ID_DOWNTOWN_LOS_ANGELES  = 57;
    const CITY_ID_CHICAGO               = 55;
    const CITY_ID_DOWNTOWN_WASHINGTON   = 49;
    const CITY_ID_WATERFRONT_WASHINGTON = 59;
    const CITY_ID_SEATTLE               = 52;
    const CITY_ID_YEREVAN               = 6;

    public static function getCityListForWebsiteTopDestinationsWidget()
    {
        return implode(',',
            [
                self::CITY_ID_HOLLYWOOD_LOS_ANGELES,
                self::CITY_ID_DOWNTOWN_LOS_ANGELES,
                self::CITY_ID_CHICAGO,
                self::CITY_ID_DOWNTOWN_WASHINGTON,
                self::CITY_ID_WATERFRONT_WASHINGTON,
                self::CITY_ID_SEATTLE,
                self::CITY_ID_YEREVAN
            ]
            );
    }


	/**
	 * @access public
	 */
	public static $locationTypes = [
		self::LOCATION_TYPE_CONTINENT,
		self::LOCATION_TYPE_COUNTRY,
		self::LOCATION_TYPE_PROVINCE,
		self::LOCATION_TYPE_CITY,
		self::LOCATION_TYPE_POI
	];

    private static $postalCodeRequired = [
        self::POSTAL_CODE_NO_SHOW  => 'Do Not Show',
        self::POSTAL_CODE_OPTIONAL => 'Optional',
        self::POSTAL_CODE_REQUIRED => 'Required',
    ];

    public static function getRequiredPostalCodes()
    {
        return self::$postalCodeRequired;
    }

    public function getLocationByTxt($txt)
    {
        $locationDao = $this->getLocationDetailsDao();
        $countries   = $locationDao->getCountriesByText($txt);
        $provinces   = $locationDao->getProvincesByText($txt);
        $cities      = $locationDao->getCitiesByText($txt);
        $pois        = $locationDao->getPoisByText($txt);
        $searchArray = [];
        $key         = 0;

        foreach ($countries as $row){
            $searchArray[$key]['id']          = $row->getId();
            $searchArray[$key]['name']        = $row->getName();
            $searchArray[$key]['location_id'] = $row->getLocation_id();
            $searchArray[$key]['type']        = self::LOCATION_TYPE_COUNTRY;
            $searchArray[$key]['type_view']   = 'Country';
            $key++;
        }
        foreach ($provinces as $row){
            $searchArray[$key]['id']          = $row->getId();
            $searchArray[$key]['name']        = $row->getName();
            $searchArray[$key]['location_id'] = $row->getLocation_id();
            $searchArray[$key]['type']        = self::LOCATION_TYPE_PROVINCE;
            $searchArray[$key]['type_view']   = 'Province';
            $key++;
        }
        foreach ($cities as $row){
            $searchArray[$key]['id']          = $row->getId();
            $searchArray[$key]['name']        = $row->getName();
            $searchArray[$key]['location_id'] = $row->getLocation_id();
            $searchArray[$key]['type']        = self::LOCATION_TYPE_CITY;
            $searchArray[$key]['type_view']   = 'City';
            $key++;
        }
        foreach ($pois as $row){
            $searchArray[$key]['id']          = $row->getId();
            $searchArray[$key]['name']        = $row->getName();
            $searchArray[$key]['location_id'] = $row->getLocation_id();
            $searchArray[$key]['type']        = self::LOCATION_TYPE_POI;
            $searchArray[$key]['type_view']   = 'POI';
            $key++;
        }
        return $searchArray;
    }

    public function getLocationById($id, $locationType)
    {
        /**
         * @var Countries $country
         * @var Provinces $province
         */
        $locationDetailsDao = $this->getLocationDetailsDao();

        $response['details'] = $locationDetailsDao->fetchOne(['id' => $id]);

        switch ($locationType) {
        	case self::LOCATION_TYPE_CITY:
        		$cityDao = $this->getCityDao();
        		$cityRow = $cityDao->getCityDataAndCurrency($id);

                if ($cityRow) {
                    $response['timezone'] = $cityRow->getTimezone();
                    $response['currency'] = $cityRow->getCurrency();
                }

        		break;
        	case self::LOCATION_TYPE_POI:
                $poiDao              = $this->getPoiDao();
                $poi                 = $poiDao->fetchOne(['detail_id' => $id]);
                $response['poitype'] = $poi;

        		break;
        	case self::LOCATION_TYPE_COUNTRY:
                /**
                 * @var \DDD\Dao\Geolocation\Countries $geolocationCountryDao
                 */
                $geolocationCountryDao = $this->getServiceLocator()->get('dao_geolocation_countries');

                $country                          = $geolocationCountryDao->fetchOne(['detail_id' => $id]);
                $response['currency']             = $country->getCurrencyId();
                $response['contactPhone']         = $country->getContactPhone();
                $response['required_postal_code'] = $country->getRequiredPostalCode();

        		break;
            case self::LOCATION_TYPE_PROVINCE:
                $provinceDao                     = $this->getProvinceDao();
                $province                        = $provinceDao->fetchOne(['detail_id' => $id]);
                $response['province_short_name'] = $province->getProvinceShortName();
        }

        return $response;
    }

    /**
     * @return array
     */
    public function getLocationType()
    {
        $poiTypeDao          = $this->getPoiTypeDao();
        $params['poi_types'] = $poiTypeDao->getAllList();
        $currencyDao         = $this->getDao('dao_currency_currency');
        $listCurrency        = $currencyDao->getList();
        $currencyAll         = [ 0 => '-- Currency --'];

        foreach ($listCurrency as $currency) {
            $currencyAll[$currency->getId()] = $currency->getCode();
        }

        $params['currency_list'] = $currencyAll;
        return $params;
    }

    public function saveToTemp(Array $file)
    {
        $image = new Images($file);

        if ($image->errors) {
            $result['status'] = 'error';
            $result['msg']    = $image->errors;

            return $result;
        } else {
            $result['status'] = 'success';
            $result['msg']    = 'Successfully uploaded';
            $result['src']    = $image->saveImage(DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_TEMP_PATH);

            return $result;
        }
    }

    public function locationSave($data, $id, $locationType, $slugRegenerate = FALSE)
    {
        $locationDetailsDao = $this->getLocationDetailsDao();
        $data = (array)$data;
        $informationText = isset($data['information']) ? $data['information'] : Null;
        if (!is_null($informationText)) {
            $withoutHtml = strip_tags($informationText);
            $without2nbsp = str_replace('&nbsp;&nbsp;',' ',$withoutHtml);
            $without1nbsp = str_replace('&nbsp;',' ',$without2nbsp);
            $informationTextHtmlClean = $without1nbsp;
        } else {
            $informationTextHtmlClean = Null;
        }
        $saveData = [
            'name'             => $data['name'],
            'information_text' => $informationText,
            'information_text_html_clean' => $informationTextHtmlClean,
            'is_selling'       => ((int)$data['is_selling'] > 0) ? (int)$data['is_selling'] : 0,
            'latitude'         => isset($data['latitude']) ? $data['latitude'] : Null,
            'longitude'        => isset($data['longitude']) ? $data['longitude'] : Null,

        ];

        $parentData = [];
        $parentID = $data['autocomplete_id'];
        switch ($locationType) {
            case self::LOCATION_TYPE_COUNTRY:
                $saveData['iso']                    = $data['iso'];
                $saveData['is_searchable']          = ((int)$data['is_searchable'] > 0) ? (int)$data['is_searchable'] : 0;
                $parentData['currency_id']          = (!empty($data['currency']) ? $data['currency'] : null);
                $parentData['contact_phone']        = $data['contact_phone'];
                $parentData['required_postal_code'] = $data['required_postal_code'];
                $parentField                        = 'continent_id';
                break;
            case self::LOCATION_TYPE_PROVINCE:
                $parentData['short_name'] = $data['province_short_name'];
                $parentField = 'country_id';
                break;
            case self::LOCATION_TYPE_CITY:
                $saveData['is_searchable']          = ((int)$data['is_searchable'] > 0) ? (int)$data['is_searchable'] : 0;
                $saveData['tot_included']           = ((int)$data['tot_included'] > 0) ? (int)$data['tot_included'] : 0;
                $saveData['tot_max_duration']       = ((int)$data['tot_max_duration'] > 0) ? (int)$data['tot_max_duration'] : 0;
                $saveData['vat_included']           = ((int)$data['vat_included'] > 0) ? (int)$data['vat_included'] : 0;
                $saveData['vat_max_duration']       = ((int)$data['vat_max_duration'] > 0) ? (int)$data['vat_max_duration'] : 0;
                $saveData['city_tax_included']      = ((int)$data['city_tax_included'] > 0) ? (int)$data['city_tax_included'] : 0;
                $saveData['city_tax_max_duration']  = ((int)$data['city_tax_max_duration'] > 0) ? (int)$data['city_tax_max_duration'] : 0;
                $saveData['sales_tax_included']     = ((int)$data['sales_tax_included'] > 0) ? (int)$data['sales_tax_included'] : 0;
                $saveData['sales_tax_max_duration'] = ((int)$data['sales_tax_max_duration'] > 0) ? (int)$data['sales_tax_max_duration'] : 0;

                if($data['tot_type'] > 0 && $data['tot'] > 0) {
                    $saveData['tot_type']       = $data['tot_type'];
                    $saveData['tot']            = $data['tot'];
                    $saveData['tot_additional'] = $data['tot_additional'];
                } else {
                    $saveData['tot_type'] = 0;
                    $saveData['tot']      = 0;
                    $saveData['tot_additional'] = 0;
                }

                if($data['vat_type'] > 0 && $data['vat'] > 0) {
                    $saveData['vat_type'] = $data['vat_type'];
                    $saveData['vat']      = $data['vat'];
                    $saveData['vat_additional'] = $data['vat_additional'];
                } else {
                    $saveData['vat_type'] = 0;
                    $saveData['vat']      = 0;
                    $saveData['vat_additional'] = 0;
                }

                if($data['sales_tax_type'] > 0 && $data['sales_tax'] > 0) {
                    $saveData['sales_tax_type'] = $data['sales_tax_type'];
                    $saveData['sales_tax']      = $data['sales_tax'];
                    $saveData['sales_tax_additional'] = $data['sales_tax_additional'];
                } else {
                    $saveData['sales_tax_type'] = 0;
                    $saveData['sales_tax']      = 0;
                    $saveData['sales_tax_additional'] = 0;
                }

                if($data['city_tax_type'] > 0 && $data['city_tax'] > 0) {
                    $saveData['city_tax_type'] = $data['city_tax_type'];
                    $saveData['city_tax']      = $data['city_tax'];
                    $saveData['city_tax_additional'] = $data['city_tax_additional'];
                } else {
                    $saveData['city_tax_type'] = 0;
                    $saveData['city_tax']      = 0;
                    $saveData['city_tax_additional'] = 0;
                }

                $parentData['timezone'] = $data['timezone'];
                $parentField            = 'province_id';
                break;
            case self::LOCATION_TYPE_POI:
                $saveData['is_searchable'] = self::IS_SEARCHABLE;
                $parentData['ws_show_right_column'] = $data['ws_show_right_column'];
                $parentData['type_id']     = $data['poi_type'];
                $parentField               = 'city_id';
                break;
        }

        $insert_id   = $id;
        $location_id = 0;

        if ($id > 0) {

            /**
             * @var \Library\ActionLogger\Logger $actionLogger
             */
            $actionLogger = $this->getServiceLocator()->get('ActionLogger');

            if ($slugRegenerate) {
                $saveData['slug'] = $this->generateSlug($saveData['name']);

                $checkDuplicateSlug = $this->checkDuplicateSlug($saveData['slug'], $locationType, $id);
                if ($checkDuplicateSlug) {
                    // we found duplicate slug = error
                    return $checkDuplicateSlug;
                }
            }

            $locationCurrentData = $locationDetailsDao->getDetailsById($id);

            if ($locationType == self::LOCATION_TYPE_CITY
                && $saveData['is_searchable'] != $locationCurrentData->getIs_searchable()) {

                $value = ($saveData['is_searchable']) ? '' : ' non';

                $actionLogger->save(
                    ActionLogger::MODULE_LOCATIONS,
                    $id,
                    ActionLogger::ACTION_LOCATION_SEARCHABLE,
                    'Location is set as' . $value . ' searchable'
                );
            }

            if ($saveData['name'] != $locationCurrentData->getName()) {
                $actionLogger->save(
                    ActionLogger::MODULE_LOCATIONS,
                    $id,
                    ActionLogger::ACTION_LOCATION_NAME,
                    'Location name change from "'
                    . $locationCurrentData->getName()
                    . '" to "'
                    . $saveData['name'].'"'
                );
            }

            if ($saveData['information_text'] != $locationCurrentData->getInformation_text()) {
                $actionLogger->save(
                        ActionLogger::MODULE_LOCATIONS,
                        $id,
                        ActionLogger::ACTION_LOCATION_INFORMATION,
                        'The location\'s information text was updated'
                );
            }

            $preLocationInfo = $locationDetailsDao->fetchOne(['id' => (int)$id]);
            $locationDetailsDao->save($saveData, ['id' => (int)$id]);

            $locatinInfo  = $locationDetailsDao->fetchOne(['id' => (int)$id]);
            $casheService = $this->getServiceLocator()->get('service_website_cache');

            if ($preLocationInfo->getName() != $saveData['name']) {

                if ($locationType == self::LOCATION_TYPE_CITY) {
                    $cityDao  = $this->getCityDao();
                    $cityInfo = $cityDao->fetchOne(['detail_id' => (int)$id]);

                    if (   ($casheService->get('city-' . $cityInfo->getId() . '-en') != null)
                        && ($casheService->get('city-' . $cityInfo->getId() . '-en') != $saveData['name'])
                    ) {
                        $casheService->set('city-'.$cityInfo->getId().'-en', $saveData['name']);
                    }
                } elseif ($locationType == self::LOCATION_TYPE_COUNTRY) {
                    /**
                     * @var \DDD\Dao\Geolocation\Countries $geolocationCountryDao
                     */
                    $geolocationCountryDao = $this->getServiceLocator()->get('dao_geolocation_countries');

                    $countryInfo = $geolocationCountryDao->fetchOne(['detail_id' => (int)$id]);
                    if (   ($casheService->get('country-' . $countryInfo->getId() . '-en') != null)
                        && ($casheService->get('country-' . $countryInfo->getId() . '-en') != $saveData['name'])
                    ) {
                        $casheService->set('country-'.$countryInfo->getId().'-en', $saveData['name']);
                    }
                }
            }

            if ($locationType == self::LOCATION_TYPE_POI) {
            	$poiDao = $this->getPoiDao();
                $poiDao->save($parentData, ['detail_id' => (int)$id]);
            }

            if ($locationType == self::LOCATION_TYPE_CITY) {
            	$cityDao = $this->getCityDao();
            	$cityDao->save($parentData, ['detail_id' => (int)$id]);
            }

            if ($locationType == self::LOCATION_TYPE_COUNTRY) {
                /**
                 * @var \DDD\Dao\Geolocation\Countries $geolocationCountryDao
                 */
                $geolocationCountryDao = $this->getServiceLocator()->get('dao_geolocation_countries');

                $geolocationCountryDao->save($parentData, array('detail_id'=>(int)$id));
            }

            if ($locationType == self::LOCATION_TYPE_PROVINCE) {
            	$provinceDao = $this->getProvinceDao();
                $provinceDao->save($parentData, array('detail_id'=>(int)$id));
            }
        } else {
            $saveData['slug'] = $this->generateSlug($saveData['name']);

            $checkDuplicateSlug = $this->checkDuplicateSlug($saveData['slug'], $locationType, $id);
            if ($checkDuplicateSlug) {
                // we found duplicate slug = error
                return $checkDuplicateSlug;
            }

            $insert_id                = $locationDetailsDao->save($saveData);
            $parentDao                = $this->getLocationDaoByType($locationType);
            $parentData['detail_id']  = $insert_id;
            $parentData[$parentField] = $parentID;
            $location_id              = $parentDao->save($parentData);
        }

        for ($i = 1; $i <= 2; $i++) {
            if($i == 1){
                $saveImg = 'cover_image';
                $imageFieldName = 'cover_image_post';
            }
            elseif($i == 2){
                $saveImg = 'thumbnail';
                $imageFieldName = 'thumbnail_post';
            }

            if ($data[$imageFieldName] !== '' && file_exists($data[$imageFieldName])) {
                $filePathArray       = explode('/', $data[$imageFieldName]);
                $fileInfo[0]['name'] = end($filePathArray);

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $fileInfo[0]['type'] = finfo_file($finfo, $data[$imageFieldName]);
                finfo_close($finfo);

                $fileInfo[0]['tmp_name'] = $data[$imageFieldName];
                $fileInfo[0]['error']    = 0;
                $fileInfo[0]['size']     = filesize($data[$imageFieldName]);

                $image   = new Images($fileInfo);

                $image->resizeToWidth([1920]);
                $image->resizeToWidth([500]);
                $image->resizeToWidth([360]);
                $image->resizeToWidth([140]);

                $newImg = str_replace(
                    ['/ginosi/images/locations/'. $insert_id . '/'],
                    [''],
                    $image->saveImage(DirectoryStructure::FS_GINOSI_ROOT
                        . DirectoryStructure::FS_IMAGES_ROOT
                        . DirectoryStructure::FS_IMAGES_LOCATIONS_PATH
                        . $insert_id . '/', 75, $i, TRUE, 'orig', 'jpg')
                );

                if ($newImg) {
                    $row = $locationDetailsDao->fetchOne(['id' => $insert_id]);

                    if ($i == 1) {
                        if ($row->getCover_image() != '') {
                            $imageMask = explode('_orig', $row->getCover_image());
                            @array_map( "unlink", glob('/ginosi/images/locations/'. $insert_id . '/' . $imageMask[0] . '*'));
                        }
                    } else {
                        if ($row->getThumbnail() != '') {
                            $imageMask = explode('_orig', $row->getThumbnail());
                            @array_map( "unlink", glob('/ginosi/images/locations/'. $insert_id . '/' . $imageMask[0] . '*'));
                        }
                    }
                    $locationDetailsDao->save([$saveImg => $newImg], ['id' => $insert_id]);
                }
            }
        }

        return array($insert_id, $location_id);
    }

    /**
     *
     * @param string $slug
     * @param int $locationType
     *
     * @return bool
     */
    private function checkDuplicateSlug($slug, $locationType, $detailsId)
    {
        $result = FALSE;

        /* @var $locationDetails \DDD\Dao\Geolocation\Details */
        $locationDetailsDao = $this->getServiceLocator()->get('dao_geolocation_details');

        $allLocationsDetails = $locationDetailsDao->fetchAll(
            function (Select $select) use ($slug) {
                $select->columns(['id']);

                $select->where(['slug' => $slug]);
            }
        );

        if ($allLocationsDetails->count()) {

            foreach ($allLocationsDetails as $locationWithSameSlug) {

                /* @var $locationWithSameSlug \DDD\Domain\Geolocation\Details */
                if ($locationWithSameSlug->getId() != $detailsId) {

                    switch ($locationType) {
                        case self::LOCATION_TYPE_COUNTRY:
                            /* @var $countryDao \DDD\Dao\Geolocation\Countries */
                            $countryDao = $this->getServiceLocator()->get('dao_geolocation_countries');

                            $findCountryWithSameId = $countryDao->fetchOne(
                                function (Select $select) use ($locationWithSameSlug) {
                                    $select->columns(['id']);

                                    $select->where(['detail_id' => $locationWithSameSlug->getId()]);
                                }
                            );

                            if ($findCountryWithSameId) {
                                $result = [
                                    'status' => 'error',
                                    'msg'    => 'We already have a Country with a matching slug'
                                ];
                            }

                            break;
                        case self::LOCATION_TYPE_PROVINCE:
                            /* @var $provinceDao \DDD\Dao\Geolocation\Provinces */
                            $provinceDao = $this->getServiceLocator()->get('dao_geolocation_provinces');

                            $findProvinceWithSameId = $provinceDao->fetchOne(
                                function (Select $select) use ($locationWithSameSlug) {
                                    $select->columns(['id']);

                                    $select->where(['detail_id' => $locationWithSameSlug->getId()]);
                                }
                            );

                            if ($findProvinceWithSameId) {
                                $result = [
                                    'status' => 'error',
                                    'msg'    => 'We already have a Province with a matching slug'
                                ];
                            }

                            break;
                        case self::LOCATION_TYPE_CITY:
                            /* @var $cityDao \DDD\Dao\Geolocation\City */
                            $cityDao = $this->getServiceLocator()->get('dao_geolocation_city');

                            $findCityWithSameId = $cityDao->fetchOne(
                                function (Select $select) use ($locationWithSameSlug) {
                                    $select->columns(['id']);

                                    $select->where(['detail_id' => $locationWithSameSlug->getId()]);
                                }
                            );

                            if ($findCityWithSameId) {
                                $result = [
                                    'status' => 'error',
                                    'msg'    => 'We already have a City with a matching slug'
                                ];
                            }

                            break;
                        case self::LOCATION_TYPE_POI:
                            /* @var $poiDao \DDD\Dao\Geolocation\Poi */
                            $poiDao = $this->getServiceLocator()->get('dao_geolocation_poi');

                            $findPoiWithSameId = $poiDao->fetchOne(
                                function (Select $select) use ($locationWithSameSlug) {
                                    $select->columns(['id']);

                                    $select->where(['detail_id' => $locationWithSameSlug->getId()]);
                                }
                            );

                            if ($findPoiWithSameId) {
                                $result = [
                                    'status' => 'error',
                                    'msg'    => 'We already have a POI with a matching slug'
                                ];
                            }

                            break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     *
     * @param string $string
     * @return string
     */
    public function generateSlug($string)
    {
        $result = '';
        $cleanString = strip_tags(trim($string));

        for ($i = 0, $prevCharIsSpace = FALSE; strlen($cleanString) > $i; $i++) {
            $char = substr($cleanString, $i, 1);

            if (ctype_space($char) && !$prevCharIsSpace) {
                $prevCharIsSpace = TRUE;
                $result .= '-';

                continue;
            }

            if (ctype_alnum($char)) {
                $prevCharIsSpace = FALSE;
                $result .= strtolower($char);
            }
        }

        if (substr($result, 0, 1) === '-') {
            $result = substr($result, 1);
        }

        if (substr($result, -1, 1) === '-') {
            $result = substr($result, 0, strlen($result) - 1);
        }

        return $result;
    }

    public function removeImage($val, $id)
    {
        $locationDetailsDao = $this->getLocationDetailsDao();
        $row = $locationDetailsDao->fetchOne(['id' => $id]);

        if ($val == 1) {
            if ($row->getCover_image() != '') {
                $imageMask = explode('_orig', $row->getCover_image());
                @array_map( "unlink", glob('/ginosi/images/locations/' . $id . '/' . $imageMask[0] . '*'));
                $locationDetailsDao->save(['cover_image' => ''], ['id' => $id]);
            }
        } else {
            if ($row->getThumbnail() != '') {
                $imageMask = explode('_orig', $row->getThumbnail());
                @array_map( "unlink", glob('/ginosi/images/locations/' . $id . '/' . $imageMask[0] . '*'));
                $locationDetailsDao->save(['thumbnail' => ''], ['id' => $id]);
            }
        }

    }

    public function searchAutocomplate($query, $locationType, $is_searchable = null)
    {
        $locationDao = $this->getLocationDetailsDao();
        $searchArray = [];

        switch ($locationType) {
        	case self::LOCATION_TYPE_COUNTRY:
        		$continentDao = $this->getContinentDao();
        		$result = $continentDao->getContinentsByText($query, $is_searchable);

                foreach ($result as $key => $row) {
                    $searchArray[$key]['id'] = $row->getId();
                    $searchArray[$key]['name'] = $row->getEn();
                }

        		break;
        	case self::LOCATION_TYPE_PROVINCE:
        		$result = $locationDao->getCountriesByText($query, $is_searchable);

                foreach ($result as $key => $row) {
                    $searchArray[$key]['id'] = $row->getLocation_id();
                    $searchArray[$key]['name'] = $row->getName();
                    $searchArray[$key]['parent_id'] = $row->getParent_id();

                    if ($row->getParent_id()) {
                        $continentDao = $this->getContinentDao();
                        $continentData = $continentDao->getContinentById($row->getParent_id());
                        $searchArray[$key]['parent_name'] = $continentData->getEn();
                        $searchArray[$key]['category'] = $row->getCategory();
                    }
                }

        		break;
        	case self::LOCATION_TYPE_CITY:
        		$result = $locationDao->getProvincesByText($query, $is_searchable);

                foreach ($result as $key => $row) {
                    $searchArray[$key]['id'] = $row->getLocation_id();
                    $searchArray[$key]['name'] = $row->getName();
                    $searchArray[$key]['parent_id'] = $row->getParent_id();

                    /**
                     * @var \DDD\Dao\Geolocation\Countries $geolocationCountryDao
                     */
                    $geolocationCountryDao = $this->getServiceLocator()->get('dao_geolocation_countries');

                    $countryData = $geolocationCountryDao->getCountryById($row->getParent_id());
                    $searchArray[$key]['parent_name'] = $countryData->getName();
                }

        		break;
        	case self::LOCATION_TYPE_POI:
        		$result = $locationDao->getCitiesByText($query, $is_searchable);

                foreach ($result as $key => $row) {
                    $searchArray[$key]['id']        = $row->getLocation_id();
                    $searchArray[$key]['name']      = $row->getName();
                    $searchArray[$key]['parent_id'] = $row->getParent_id();

                    $provinceDao  = $this->getProvinceDao();
                    $provinceData = $provinceDao->getProvinceById($row->getParent_id());
                    $searchArray[$key]['parent_name'] = $provinceData->getName();
                    $searchArray[$key]['category']    = $row->getCategory();
                }

        		break;
        	default:
        		return false;
        }

        return $searchArray;
    }

    public function getOptions($id, $locationType)
    {
        $parentTxt = '';
        $result = [];

        $locationTable = $this->getLocationTableByType($locationType);

        if ($locationType == self::LOCATION_TYPE_COUNTRY) {
            /**
             * @var \DDD\Dao\Geolocation\Countries $geolocationCountryDao
             */
            $geolocationCountryDao = $this->getServiceLocator()->get('dao_geolocation_countries');

            $result = $this->getProvinceDao()->getAllProvinceByCountryID($id);
            $parent = $geolocationCountryDao->getParentDetail($id);

            if (is_object($parent)) {
                $parentTxt = $parent->getName();
            }

            $filed = self::LOCATION_TYPE_PROVINCE;
        } elseif ($locationType == self::LOCATION_TYPE_PROVINCE) {
            $result = $this->getCityDao()->getAllCitiesByProvinceID($id);
            $filed = self::LOCATION_TYPE_CITY;

            $parentLocationTable = DbTables::TBL_COUNTRIES;
            $parentIDField = 'country_id';

            $parent = $this->getLocationDetailsDao()->getParentDetail($id, $parentIDField, $locationTable, $parentLocationTable);

            if (is_object($parent)) {
                $parentTxt = $parent->getName();
            }
        } elseif ($locationType == self::LOCATION_TYPE_CITY) {
            $result = $this->getPoiDao()->getAllPoisByCityID($id);
            $filed = self::LOCATION_TYPE_POI;

            $parentLocationTable = DbTables::TBL_PROVINCES;
            $parentIDField = 'province_id';

            $parent = $this->getLocationDetailsDao()->getParentDetail($id, $parentIDField, $locationTable, $parentLocationTable);

            if (is_object($parent)) {
                $parentTxt = $parent->getName();
            }
        } elseif ($locationType == self::LOCATION_TYPE_POI) {

            $parentLocationTable = DbTables::TBL_CITIES;
            $parentIDField = 'city_id';

            $parent = $this->getLocationDetailsDao()->getParentDetail($id, $parentIDField, $locationTable, $parentLocationTable);

            if (is_object($parent)) {
                $parentTxt = $parent->getName();
            }
        } else {
            return false;
        }

        $searchArray = [];

        foreach ($result as $key => $row) {
            $searchArray[$key]['id'] = $row->getId();
            $searchArray[$key]['name'] = $row->getName();
            $searchArray[$key]['detail_id'] = $row->getDetail_id();
            $searchArray[$key]['type'] = $filed;
        }

        return [
            'searchArray' => $searchArray,
            'parentTxt' => $parentTxt,
        ];
    }

    /**
     * Check for child locations to warn, when user try to remove some location
     *
     * @param int $locationID
     * @param int $locationType
     * @return bool
     * @throws \Exception
     */
    public function checkChildExist($locationID, $locationType)
    {

    	$locationDao = null;
    	$parentIDField = null;

    	switch ($locationType) {
    		case self::LOCATION_TYPE_COUNTRY:
    			$locationDao = $this->getProvinceDao();
    			$parentIDField = 'country_id';
    			break;
    		case self::LOCATION_TYPE_PROVINCE:
    			$locationDao = $this->getCityDao();
    			$parentIDField = 'province_id';
    			break;
    		case self::LOCATION_TYPE_CITY:
    			$locationDao = $this->getPoiDao();
    			$parentIDField = 'city_id';
    			break;
    		case self::LOCATION_TYPE_POI:
    			return false;
    			break;
    		default:
    			throw new \Exception("Wrong location type given");
    	}

    	return $locationDao->checkRowExist(null, $parentIDField, $locationID);
    }

    /**
     * Remove specific location
     *
     * @param int $id
     * @param int $locationType
     * @param int $detailsID
     * @return boolean
     */
    function deleteLocation($id, $locationType, $detailsID)
    {
    	$locationDao = $this->getLocationDaoByType($locationType);

        if ($locationDao !== null) {
        	// remove directory
        	Helper::deleteDirectory('/ginosi/images/locations/'.$detailsID);

        	$locationDetailsDao = $this->getLocationDetailsDao();
        	$locationDetailsDao->deleteWhere(array('id'=>$detailsID));

        	$locationDao->deleteWhere(array('id'=>$id));

        	return true;
        }else {
        	// unknown location type
        	return false;
        }
    }

    public function getProcCountByCityId($id)
    {
        $dao = new \DDD\Dao\Geolocation\Cities($this->getServiceLocator(), 'DDD\Domain\Geolocation\CityUrl');

        /* @var $resp \DDD\Domain\Geolocation\CityUrl */
        $resp = $dao->getProcCountByCityId($id);

        if(is_object($resp)){
            return '/location/' . $resp->getCitySlug() . '--' . $resp->getProvinceSlug();
        }
    }


    public function getProcvincCountryCityByPoiId($id)
    {
        $dao = new \DDD\Dao\Geolocation\Poi($this->getServiceLocator(), 'DDD\Domain\Geolocation\PoiUrl');

        /* @var $resp \DDD\Domain\Geolocation\PoiUrl */
        $resp = $dao->getProcvincCountryCityByPoiId($id);

        if(is_object($resp)){
            return '/location/' . $resp->getCitySlug() . '--' . $resp->getProvinceSlug() . '/' . $resp->getPoiSlug();
        }
    }


    /**
     * @return \DDD\Domain\Location\Country[]
     */
    public function getAllActiveCountries()
    {
        /**
         * @var \DDD\Dao\Location\Country $countryDao
         */
        $countryDao = $this->getServiceLocator()->get('dao_location_country');

        return $countryDao->getCountriesWithChildrenCount();
    }

    /**
     * @return ArrayObject
     */
    public function getCountriesWithCurrecny()
    {
        $countryDao = $this->getServiceLocator()->get('dao_location_country');
        $list = $countryDao->getCountriesWithCurrecny();
        $listArray = [];
        foreach ($list as $row) {
            if ($row['code']) {
                $listArray[] = ['id' => $row['id'], 'code' => $row['code']];
            }
        }
        return json_encode($listArray);
    }

    public function getAllCountries()
    {
        $countriesDao = $this->getServiceLocator()->get('dao_geolocation_countries');
        $list = $countriesDao->getAllActiveCountries();
        return $list;
    }

    /**
     * @access public
     * @param int $childLocationType
     * @param int $locationID
     * @return Ambigous <\Library\DbManager\Ambigous, \Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    public function getActiveChildLocations($childLocationType, $locationID)
    {
    	$locationDao = $this->getLocationDaoByType($childLocationType);

    	$childLocations = array();

    	switch ($childLocationType) {
    		case self::LOCATION_TYPE_PROVINCE:
    			$childLocations = $locationDao->getAllProvinceByCountryID($locationID);
    			break;
    		case self::LOCATION_TYPE_CITY:
    			$childLocations = $locationDao->getAllCitiesByProvinceID($locationID);
    			break;
    		case self::LOCATION_TYPE_PROVINCE:
    			$childLocations = $locationDao->getAllPoisByCityID($locationID);
    			break;
    	}

    	return $childLocations;
    }

	/**
	 * @param string $isoCode
	 * @return \DDD\Domain\Location\Country
	 */
	public function getCountryIdByISOCode($isoCode)
    {
		$countryDao = new Country($this->getServiceLocator());
		$isoCode = strtoupper($isoCode);

		if (strlen($isoCode) == 2) {
			$countryDomain = $countryDao->getByISOCode($isoCode);
		} elseif (strlen($isoCode) == 3) {
			$countryDomain = $countryDao->getByISOAlpha3Code($isoCode);
		} else {
			return false;
		}

		if ($countryDomain) {
			return $countryDomain->getId();
		}

		return false;
	}

    public function getCityThumb($id)
    {
        $countryDao = new \DDD\Dao\Location\City($this->getServiceLocator(), 'DDD\Domain\Location\CityThumb');
        return $countryDao->getCityThumbById($id)->getThumb();
    }

    public function getParentCountryCurrencyByCityId($cityId)
    {
        $cityDao = new \DDD\Dao\Location\City(
            $this->getServiceLocator(),
            'DDD\Domain\Location\Country');
        $parrentCountry = $cityDao->getParentCountryCurrency($cityId);

        return $parrentCountry['code'];
    }

    /**
     * @access private
     * @return \DDD\Dao\Geolocation\Details
     */
    private function getLocationDetailsDao()
    {
        if ($this->locationDetailsDao === null) {
            $this->locationDetailsDao = $this->getServiceLocator()->get('dao_geolocation_details');
        }
        return $this->locationDetailsDao;
    }

    /**
     * @access private
     * @return \DDD\Dao\Geolocation\Continents
     */
    private function getContinentDao()
    {
        if ($this->continentDao === null) {
            $this->continentDao = $this->getServiceLocator()->get('dao_geolocation_continents');
        }
        return $this->continentDao;
    }

    /**
     * @access private
     * @return \DDD\Dao\Geolocation\Provinces
     */
    private function getProvinceDao()
    {
    	$provinceDao = $this->getServiceLocator()->get('dao_geolocation_provinces');

    	return $provinceDao;
    }

    /**
     * @access private
     * @return \DDD\Dao\Geolocation\Cities
     */
    private function getCityDao()
    {
    	$cityDao = $this->getServiceLocator()->get('dao_geolocation_cities');

    	return $cityDao;
    }

    /**
     * @access private
     * @return \DDD\Dao\Geolocation\Poi
     */
    private function getPoiDao()
    {
    	$poiDao = $this->getServiceLocator()->get('dao_geolocation_poi');

    	return $poiDao;
    }

    /**
     * @access private
     * @return \DDD\Dao\Geolocation\Poitype
     */
    private function getPoiTypeDao()
    {
    	if ($this->poiTypeDao === null) {
    		$this->poiTypeDao = $this->getServiceLocator()->get('dao_geolocation_poi_type');
    	}
    	return $this->poiTypeDao;
    }

    /**
     * @access private
     * @param int $locationType
     * @return NULL|Ambigous <NULL, \DDD\Dao\Geolocation\Poi>
     */
    private function getLocationDaoByType($locationType)
    {
    	$locationDao = null;

    	switch ((int)$locationType) {
    		case self::LOCATION_TYPE_CONTINENT:
    			$locationDao = $this->getContinentDao();
    			break;
    		case self::LOCATION_TYPE_COUNTRY:
                /**
                 * @var \DDD\Dao\Geolocation\Countries $geolocationCountryDao
                 */
                $locationDao = $this->getServiceLocator()->get('dao_geolocation_countries');
    			break;
    		case self::LOCATION_TYPE_PROVINCE:
    			$locationDao = $this->getProvinceDao();
    			break;
    		case self::LOCATION_TYPE_CITY:
    			$locationDao = $this->getCityDao();
    			break;
    		case self::LOCATION_TYPE_POI:
    			$locationDao = $this->getPoiDao();
    			break;
    		default:
    			return null;
    	}

    	return $locationDao;
    }

    /**
     * @access private
     * @param int $locationType
     * @return NULL|Ambigous <NULL, \DDD\Dao\Geolocation\Poi>
     */
    private function getLocationTableByType($locationType)
    {
    	$locationTable = null;

    	switch ($locationType) {
    		case self::LOCATION_TYPE_CONTINENT:
    			$locationTable = DbTables::TBL_CONTINENTS;
    			break;
    		case self::LOCATION_TYPE_COUNTRY:
    			$locationTable = DbTables::TBL_COUNTRIES;
    			break;
    		case self::LOCATION_TYPE_PROVINCE:
    			$locationTable = DbTables::TBL_PROVINCES;
    			break;
    		case self::LOCATION_TYPE_CITY:
    			$locationTable = DbTables::TBL_CITIES;
    			break;
    		case self::LOCATION_TYPE_POI:
    			$locationTable = DbTables::TBL_POI;
    			break;
    		default:
    			return null;
    	}

    	return $locationTable;
    }

    public function getCurrentDateCity($cityId)
    {
        $cityDao = new \DDD\Dao\Geolocation\Cities($this->getServiceLocator(), 'ArrayObject');
        $city = $cityDao->fetchOne(['id'=>$cityId], ['timezone']);
        if($city && $city['timezone']) {
            return Helper::getCurrenctDateByTimezone($city['timezone'], 'Y-m-d H:i:s');
        }
        return date('Y-m-d H:i:s');
    }

    /**
     * @param $cityId
     * @param $hours
     * @return bool|string
     */
    public function getIncrementedDateCity($cityId, $hours)
    {
        $cityDao = new \DDD\Dao\Geolocation\Cities($this->getServiceLocator(), 'ArrayObject');
        $city = $cityDao->fetchOne(['id'=>$cityId], ['timezone']);
        if($city && $city['timezone']) {
            return Helper::incrementDateByTimezone($city['timezone'], $hours, 'Y-m-d H:i:s');
        }
        return date('Y-m-d H:i:s');
    }

    /**
     *
     * @param int $locationId
     * @return \ArrayObject|\ArrayObject[]
     */
    public function getLocationLogs($locationId)
    {
        /**
         * @var \DDD\Dao\ActionLogs\ActionLogs $actionLogsDao
         */
        $actionLogsDao = $this->getServiceLocator()->get('dao_action_logs_action_logs');

        return $actionLogsDao->getByLocationId($locationId);
    }

	/**
     * getAllTypesOfLocations
     * @return array
     */
    public function getAllTypesOfLocations()
    {
        try {
            $auth            = $this->getServiceLocator()->get('library_backoffice_auth');
            $userId          = $auth->getIdentity()->id;
            $countryId       = $auth->getIdentity()->countryId;
            $cityId          = $auth->getIdentity()->cityId;


            $apartmentDao    = $this->getServiceLocator()->get('dao_accommodation_accommodations');
            $storageDao      = $this->getServiceLocator()->get('dao_warehouse_storage');
            $buildingDao     = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
            $officeDao       = $this->getServiceLocator()->get('dao_office_office_manager');
            $categoryService = $this->getServiceLocator()->get('service_warehouse_category');

            $apartmentInfo   = $apartmentDao->getAparatmentByCityId($cityId);
            $storageInfo     = $storageDao->getStorageByUser($userId);
            $buildingInfo    = $buildingDao->getBuildingsListForSelect(false, $countryId, true);
            $officeInfo      = $officeDao->getOfficeListByCity($cityId);

            $apartmentsList  = [];
            $storagesList    = [];
            $buildingsList   = [];
            $officeList      = [];

            foreach ($apartmentInfo as $row) {
                $apartmentsList[] = iterator_to_array($row);
            }

            foreach ($storageInfo as $row) {
                $storagesList[] = iterator_to_array($row);
            }

            foreach ($buildingInfo as $row) {
                $buildingsList[] = iterator_to_array($row);
            }

            foreach ($officeInfo as $row) {
                $officeList[] = iterator_to_array($row);
            }

            $locationInfo['apartments'] = $apartmentsList;
            $locationInfo['buildings']  = $buildingsList;
            $locationInfo['storages']   = $storagesList;
            $locationInfo['offices']    = $officeList;


            return $locationInfo;
        } catch (\Exception $e) {
            return false;
        }
    }
}
