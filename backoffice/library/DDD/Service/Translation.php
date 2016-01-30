<?php
namespace DDD\Service;

use Library\Constants\Objects;

class Translation extends ServiceBase
{
	protected $_langDao = null;
	protected $_translationUser = null;

	const PRODUCT_TYPE_APARTMENT = 1;
	const PRODUCT_TYPE_APARTEL   = 2;
	const PRODUCT_TYPE_BUILDING  = 3;
	const PRODUCT_TYPE_OFFICE    = 4;
	const PRODUCT_TYPE_PARKING   = 5;

    const PRODUCT_SHORT_TEASER_GENERAL_AND_DESCROPTION = 2;
    const PRODUCT_ROOM_DESCRIPTION                     = 3;

    const PRODUCT_TEXTLINE_TYPE_APARTMENT_DIRECT_ENTRY_KEY_INSTRUCTION = 6;

	const PRODUCT_TEXTLINE_TYPE_APARTEL_CONTENT                        = 40;
    const PRODUCT_TEXTLINE_TYPE_APARTEL_MOTO                           = 41;
    const PRODUCT_TEXTLINE_TYPE_APARTEL_META_DESCRIPTION               = 42;

    const PRODUCT_TEXTLINE_TYPE_BUILDING_SECTION_APARTMENT_ENTRY       = 81;
    const PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_FACILITIES          = 82;
    const PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_USAGE               = 83;
    const PRODUCT_TEXTLINE_TYPE_APARTMENT_USAGE                        = 84;
    const PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_POLICIES            = 85;

	const PRODUCT_TEXTLINE_TYPE_OFFICE_RECEPTION_ENTRY                 = 101;

    const PRODUCT_TEXTLINE_TYPE_PARKING_LOTS                           = 200;

    public static $TRANSLATION_TYPE = ['u', 'l', 'p'];
    public static $LOCATION_OPTION  = ['name', 'info'];
    public static $LOCATION_TYAPE   = ['country', 'province', 'city', 'poi'];

	public static $PRODUCT_TYPES = [
		self::PRODUCT_TYPE_APARTMENT => 'Apartment',
		self::PRODUCT_TYPE_APARTEL	 => 'Apartel',
		self::PRODUCT_TYPE_BUILDING	 => 'Building',
		self::PRODUCT_TYPE_OFFICE	 => 'Office',
		self::PRODUCT_TYPE_PARKING   => 'Parking',
	];

    public static $APARTMENT_TEXTLINE_TYPES = [
        self::PRODUCT_SHORT_TEASER_GENERAL_AND_DESCROPTION,
        self::PRODUCT_ROOM_DESCRIPTION,
        self::PRODUCT_TEXTLINE_TYPE_APARTMENT_DIRECT_ENTRY_KEY_INSTRUCTION,
        self::PRODUCT_TEXTLINE_TYPE_APARTMENT_USAGE
    ];

    public static $BUILDING_TEXTLINE_TYPES = [
        self::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_FACILITIES,
        self::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_USAGE,
        self::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_POLICIES
    ];

    public static $APARTEL_TEXTLINE_TYPES = [
        self::PRODUCT_TEXTLINE_TYPE_APARTEL_CONTENT,
        self::PRODUCT_TEXTLINE_TYPE_APARTEL_MOTO,
        self::PRODUCT_TEXTLINE_TYPE_APARTEL_META_DESCRIPTION,
    ];

    public static $OFFICE_TEXTLINE_TYPES = [
        self::PRODUCT_TEXTLINE_TYPE_OFFICE_RECEPTION_ENTRY,
    ];

	public static $PARKING_TEXTLINE_TYPES = [
        self::PRODUCT_TEXTLINE_TYPE_PARKING_LOTS,
    ];

    public static $TEXTLINE_TYPES = [
        self::PRODUCT_SHORT_TEASER_GENERAL_AND_DESCROPTION                 => 'Apartment, Short Description',
        self::PRODUCT_ROOM_DESCRIPTION                                     => 'Apartment, Room Description',

        self::PRODUCT_TEXTLINE_TYPE_APARTMENT_DIRECT_ENTRY_KEY_INSTRUCTION => 'Apartment, Entry Instructions',

        self::PRODUCT_TEXTLINE_TYPE_APARTEL_CONTENT                        => 'Apartel, Content',
        self::PRODUCT_TEXTLINE_TYPE_APARTEL_MOTO                           => 'Apartel, Moto',
        self::PRODUCT_TEXTLINE_TYPE_APARTEL_META_DESCRIPTION               => 'Apartel, Meta Description',

        self::PRODUCT_TEXTLINE_TYPE_BUILDING_SECTION_APARTMENT_ENTRY       => 'Building, Apartment Entry Instructions',
        self::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_FACILITIES          => 'Building, Welcome Note, Facilities',
        self::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_USAGE               => 'Building, Welcome Note, Rules',
        self::PRODUCT_TEXTLINE_TYPE_APARTMENT_USAGE                        => 'Apartment, Welcome Note, Rules',
        self::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_POLICIES            => 'Building, Welcome Note, Policies',

		self::PRODUCT_TEXTLINE_TYPE_OFFICE_RECEPTION_ENTRY                 => 'Office, Reception Entry Instructions',
        self::PRODUCT_TEXTLINE_TYPE_PARKING_LOTS                           => 'Parking, Direction',
    ];


    public function getUniversalPages(){
        $dao = new \DDD\Dao\Translation\UniversalPages($this->getServiceLocator());
        return $dao->fetchAll();
    }


    public function getTranslationBasicInfo($filterParams = array(), $sortCol = 0, $sortDir = 'ASC') {
        $status        = Objects::getTranslationStatus();
        $filteredArray = [];
        $lang          = 'en';
        $result        = [
            'count'  => [],
            'result' => []
        ];

        if ($filterParams['category'] == 1) {
            $dao = new \DDD\Dao\Translation\Universal($this->getServiceLocator(), 'DDD\Domain\Translation\UniversalView');
            $response = $dao->getTranslationListForSearch($filterParams);
            foreach ($response['result'] as $reservation){
                $row = array(
                    $reservation->getId(),
                    $reservation->getPageName(),
                    substr($reservation->getContent(), 0, 100).((strlen($reservation->getContent()) > 100) ? '...' :''),
                    'u-'.$reservation->getId().'-'.$lang,
                );
                $filteredArray[] = $row;
            }
            $result['count']  = $response['count'];
            $result['result'] = $filteredArray;
        } elseif ($filterParams['category'] == 2) {
            $dao = new \DDD\Dao\Geolocation\Details($this->getServiceLocator(), 'DDD\Domain\Translation\LocationView');
            $response = $dao->getTranslationListForSearch($filterParams);
            foreach ($response['result'] as $reservation){
                $types = '';
                if($reservation->getCountry()){
                    $types = 'Country';
                } elseif($reservation->getProvinces()) {
                    $types = 'Province';
                } elseif($reservation->getCity()) {
                    $types = 'City';
                } elseif($reservation->getPoi()) {
                    $types = 'POI';
                }

                $row = array(
                    $reservation->getId(),
                    $reservation->getName() . ' - ' . $types,
                    substr($reservation->getName(), 0, 100).((strlen($reservation->getName()) > 100) ? '...' :''),
                    'l-'.$reservation->getId().'-'.$lang.'-name-'.strtolower($types),
                );

                $filteredArray[] = $row;
                $row = array(
                    $reservation->getId(),
                    'Information',
                    substr($reservation->getTx_2(), 0, 100).((strlen($reservation->getTx_2()) > 100) ? '...' :''),
                    'l-'.$reservation->getId().'-'.$lang.'-info-'.strtolower($types),
                );
                $filteredArray[] = $row;
            }
            $result['count']  = $response['count']->getCount();
            $result['result'] = $filteredArray;
        } elseif ($filterParams['category'] == 3) {
            $dao = new \DDD\Dao\Translation\Product($this->getServiceLocator(), 'DDD\Domain\Translation\ProductView');
            $response = $dao->getTranslationListForSearch($filterParams);
            /** @var \DDD\Domain\Translation\ProductView $item */
            foreach ($response['result'] as $item){
                if ($typeProd = $this->getDescriptionType($item->getType())) {
                    $row = [
                        $item->getId(),
                        $item->getEntityName() . ' - ' . $typeProd,
                        substr($item->getContent(), 0, 100) . ((strlen($item->getContent()) > 100) ? '...' :''),
                        'p-' . $item->getId() . '-'.$lang,
                    ];
                    $filteredArray[] = $row;
                }
            }
            $result['count']  = $response['total'];
            $result['result'] = $filteredArray;
        }
    	return $result;
    }

    public function getDescriptionType($type_id)
    {
        if (isset(self::$TEXTLINE_TYPES[$type_id])) {
            return self::$TEXTLINE_TYPES[$type_id];
        } else {
            return 'Unknown';
        }
    }

    public function getCount($filterParams)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Count());

    	$where = $this->constructWhereFromFilterParams($filterParams);

    	$count = $bookingDao->getCount($where);

    	return $count;
    }

    public function forTranslation($params){
        $dao = $this->getDaoByType($params['type'], 'DDD\Domain\Translation\Edit');

        if (!$dao->checkRowExist(null, 'id', $params['id'])) {
			return false;
        }

        $result = $dao->getForTranslation($params);
        return [
            'result' => $result
        ];
    }

    public function translationSave($data){
        $data = (array)$data;
        $id   = 0;

        if(isset($data['edit_id']) && $data['edit_id'] > 0){
            $id = $data['edit_id'];
		}

        $dao = $this->getDaoByType($data['type_translation'], 'DDD\Domain\Translation\GetLang');

        if ($id > 0) {
            $saveData = [];
            if ($data['type_translation'] == 'l') {
                $name    = ($data['location_option'] == 'name') ? 'name' : 'information_text';
                $content = $name;

				if ($data['location_option'] == 'name') {
                    $saveData[$content] = preg_replace('/<p>(.*?)<\/p>/is', '$1', $data['content'], 1);
				} else {
                    $saveData[$content]                 = $data['content'];
                    $withoutHtml                        = strip_tags($saveData[$content]);
                    $without2nbsp                       = str_replace('&nbsp;&nbsp;',' ',$withoutHtml);
                    $without1nbsp                       = str_replace('&nbsp;',' ',$without2nbsp);
                    $saveData[$content . '_html_clean'] = $without1nbsp;
                }

            } elseif ($data['type_translation'] == 'p'){
                $content                            = $data['lang_code'];
                $saveData[$content]                 = $data['content'];
                $withoutHtml                        = strip_tags($saveData[$content]);
                $without2nbsp                       = str_replace('&nbsp;&nbsp;',' ',$withoutHtml);
                $without1nbsp                       = str_replace('&nbsp;',' ',$without2nbsp);
                $saveData[$content . '_html_clean'] = $without1nbsp;
            } else {
                $content                 = $data['lang_code'];
				$saveData['description'] = isset($data['description']) ? $data['description'] : '';

                if (substr_count($data['content'], '<p>') == 1){
                    $saveData[$content] = preg_replace('/<p>(.*?)<\/p>/is', '$1', $data['content'], 1);
				} else{
                    $saveData[$content] = $data['content'];
				}

                $withoutHtml                        = strip_tags($saveData[$content]);
                $without2nbsp                       = str_replace('&nbsp;&nbsp;',' ',$withoutHtml);
                $without1nbsp                       = str_replace('&nbsp;',' ',$without2nbsp);
				$saveData[$content . '_html_clean'] = $without1nbsp;
            }

            $dao->save($saveData, array('id'=>(int)$id));

			$uniTextlineRelDao = $this->getServiceLocator()->get('dao_textline_universal_page_rel');

			$uniTextlineRelDao->delete(['textline_id' => (int)$id]);

			if (isset($data['textline-type'])) {

				foreach ($data['textline-type'] as $pageId) {
					$uniTextlineRelDao->save(['textline_id' => (int)$id, 'page_id' => $pageId]);
				}
			}
        } else {
            $dao = new \DDD\Dao\Translation\Universal($this->getServiceLocator(), 'DDD\Domain\Translation\GetLang');

            if (substr_count($data['content'], '<p>') == 1){
                $saveData['en'] = preg_replace('/<p>(.*?)<\/p>/is', '$1', $data['content'], 1);
			} else{
                $saveData['en'] = $data['content'];
			}

			$saveData['description'] = isset($data['description']) ? $data['description'] : '';

            $id = $dao->save($saveData);

			$uniTextlineRelDao = $this->getServiceLocator()->get('dao_textline_universal_page_rel');

			$uniTextlineRelDao->delete(['textline_id' => (int)$id]);

			if (isset($data['textline-type'])) {

				foreach ($data['textline-type'] as $pageId) {
					$uniTextlineRelDao->save(['textline_id' => (int)$id, 'page_id' => $pageId]);
				}
			}
        }
        return ['id' => $id];
    }

    public function getAutocomplateList($txt, $type){
       $respons = [];
       if($type == 2){
            $service = $this->getServiceLocator()->get('service_location');
            $respons = $service->getLocationByTxt($txt);
       } elseif($type == 3){
            $dao = new \DDD\Dao\Accommodation\Accommodations($this->getServiceLocator(), 'DDD\Domain\Accommodation\TranslationAutocomplete');
            $result = $dao->getAccommodationsForTranlation($txt);
            foreach ($result as $row){
                $respons[] = ['id' => $row->getId(),'name' => $row->getName()];
            }
       }
       return $respons;
    }

    /**
     * Get Key Entry Textline id for Apartment
     * OLD, type = 5
     *
     * @param integer $id - Apartment ID
     * @return boolean | object
     */
    public function getKeyInstIdByProdId($id){
       $dao = new \DDD\Dao\Translation\Product($this->getServiceLocator(), 'DDD\Domain\Translation\GetLang');
       $result = $dao->fetchOne([
           'entity_id'   =>$id,
           'entity_type' =>5
       ]);

       if($result) {
           return 'p-' . $result->getId() . '-en';
       }

       return false;
    }

    /**
     * Get KI Direct Type Textline id for Apartment
     * type = 6
     *
     * @param integer $id - Apartment ID
     * @return boolean | object
     */
    public function getKiDirectTypeIdByApartmentId($id)
    {
       $dao    = new \DDD\Dao\Translation\Product($this->getServiceLocator(), 'ArrayObject');

		$result = $dao->fetchOne([
			'entity_id'   => $id,
			'type'		  => self::PRODUCT_TYPE_APARTMENT,
			'entity_type' => self::PRODUCT_TEXTLINE_TYPE_APARTMENT_DIRECT_ENTRY_KEY_INSTRUCTION
		]);

       if ($result) {
			return 'p-' . $result['id'] . '-en';
       }

       return false;
    }

    private function getDaoByType($type, $domain){
        if($type == 'l'){
            $dao = new \DDD\Dao\Geolocation\Details($this->getServiceLocator(), $domain);
        } elseif($type == 'p'){
            $dao = new \DDD\Dao\Translation\Product($this->getServiceLocator(), $domain);
        } else {
            $dao = new \DDD\Dao\Translation\Universal($this->getServiceLocator(), $domain);
        }
        return $dao;
    }

    private function getLangDao() {
        if ($this->_langDao === null) {
            $this->_langDao = $this->getServiceLocator()->get('dao_website_language_language');
        }
        return $this->_langDao;
    }

    /**
     * @param string $text
     * @return string
     */
    public static function cleanTextline($text)
    {
        return trim(str_replace('&nbsp;', ' ', strip_tags($text)));
    }

    public function addTextline($params)
    {
        $universalTextlineDao = $this->getServiceLocator()->get('dao_universal_textline');
        $universalTextlineDao->save(
            [
                'en'            => $params['content'],
				'en_html_clean' => $this->cleanTextline($params['content']),
                'description'   => $this->cleanTextline($params['description']),
            ]
        );

		$textlineId = $universalTextlineDao->getLastInsertValue();

		$uniTextlineRelDao = $this->getServiceLocator()->get('dao_textline_universal_page_rel');

		if (isset($params['add-textline-page']) && !empty($params['add-textline-page'])) {
			$pages = explode(',', $params['add-textline-page']);

			foreach ($pages as $pageId) {
				$uniTextlineRelDao->save(['textline_id' => (int)$textlineId, 'page_id' => $pageId]);
			}
		}
        return $textlineId;
    }

	public function getUniversalTextlinePages($textlineId)
	{
		$uniTextlineRelDao = $this->getServiceLocator()->get('dao_textline_universal_page_rel');
		$selectedPages = $uniTextlineRelDao->fetchAll(['textline_id' => $textlineId]);

		$pages = [];
		foreach ($selectedPages as $row) {
			array_push($pages, $row['page_id']);
		}

		return $pages;
	}

}
