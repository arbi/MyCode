<?php

namespace DDD\Service\Apartment;

use DDD\Dao\Apartment\Room;
use DDD\Service\ServiceBase;
use DDD\Dao\Apartment\Details as DetailsDao;
use League\Flysystem\Exception;
use Library\ChannelManager\CivilResponder;
use Zend\Log\Logger;
use Library\Constants\DomainConstants;
use Library\Constants\Objects;
use Library\Constants\Constants;
use Library\Constants\TextConstants;
use DDD\Service\Translation;
use Library\Constants\Roles;
use Library\ActionLogger\Logger as ActionLogger;

/**
 * Service class providing methods to work with apartment
 * @author Tigran Petrosyan
 * @package core
 * @subpackage core/service
 */
class General extends ServiceBase
{
	/**
	 * Get apartment status
	 * @param int $apartmentId
	 * @return int
	 */
	public function getStatus($apartmentId) {
		$apartmentGeneralDao = $this->getApartmentGeneralDao('ArrayObject');

		$statusID = $apartmentGeneralDao->getStatusID($apartmentId);

		return $statusID['status'];
	}

	/**
	 * @param $countryId
	 * @return array
	 */
	public function getApartmentsForCountryForSelect($countryId = false, $prepareSelect = true, $selectedId = 0)
	{
		$apartmentGeneralDao = $this->getApartmentGeneralDao('ArrayObject');
		$apartments = $apartmentGeneralDao->getApartmentsForCountryForSelect($countryId, $selectedId);

        if ($prepareSelect) {
            $apartmentsForSelectize = [];
            foreach ($apartments as $item) {
                $apartmentsForSelectize[] = ['value' => $item['id'], 'text' => $item['name']];
            }
            return $apartmentsForSelectize;
        } else {
            return $apartments;
        }
	}

	/**
	 * Check connected to cubilis or not
	 * @param int $apartmentId
	 * @return int
	 */
	public function isCubilisConnected($apartmentId) {
		$apartmentDetailsDao = $this->getApartmentDetailsDao('ArrayObject');

		$connected = $apartmentDetailsDao->isCubilisConnected($apartmentId);

		return $connected['sync_cubilis'];
	}

	public function getAllApartmentsIdsAndTimezones() {
       $apartmentGeneralDao = $this->getApartmentGeneralDao();
       return $apartmentGeneralDao->getAllApartmentsIdsAndTimezones();
	}

    public function getAllApartmentsIdsAndTimezonesThatHaveExtraInspectionEnabled()
    {
        $apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');
        return $apartmentGeneralDao->getAllApartmentsIdsAndTimezonesThatHaveExtraInspectionEnabled();
    }

    public function getApartmentTimezoneById($apartmentId) {
        $apartmentGeneralDao = $this->getApartmentGeneralDao();
        return $apartmentGeneralDao->getApartmentTimezoneById($apartmentId);
    }

    /**
     * @param $apartmentId
     * @param $openNextMonthAvailability
     */
    public function updateOpenNextMonthAvailability($apartmentId, $openNextMonthAvailability) {
        $apartmentGeneralDao = $this->getApartmentGeneralDao();
        $apartmentGeneralDao->updateOpenNextMonthAvailability($apartmentId, $openNextMonthAvailability);
    }

    /**
     * @param $apartmentId
     * @return mixed
     */
    public function getOpenNextMonthAvailability($apartmentId)
    {
        $apartmentGeneralDao = $this->getApartmentGeneralDao();
        return $apartmentGeneralDao->getOpenNextMonthAvailability($apartmentId)['open_next_month_availability'];
    }

    public function saveOpenNextMonthAvailability($apartmentId, $openNextMonthAvailability)
    {
        $apartmentGeneralDao = $this->getApartmentGeneralDao();
        $apartmentGeneralDao->save(
            ['open_next_month_availability' => $openNextMonthAvailability],
            ['id' => $apartmentId]
        );
    }

    /**
     * Get apartment full address
     * @param int $apartmentId
     * @return string
     */
    public function getFullAddress($apartmentId) {
    	$apartmentDao = $this->getAccommodationsDao('DDD\Domain\Accommodation\ProductFullAddress');

    	return $apartmentDao->getAppartmentFullAddressByID($apartmentId);
    }

	public function getCurrency($apartmentId)
    {
		$productGeneralDao = new \DDD\Dao\Apartment\General($this->getServiceLocator());
		$currency = $productGeneralDao->getCurrency($apartmentId);

		return $currency ? $currency['code']: 'N/A';
	}

	public function getCurrencySymbol($apartmentId)
    {
		$productGeneralDao = new \DDD\Dao\Apartment\General($this->getServiceLocator());
		$currency = $productGeneralDao->getCurrency($apartmentId);

		return $currency ? $currency['symbol']: 'N/A';
	}

	public function getMaxCapacity($apartmentId) {
		$generalDao = new \DDD\Dao\Apartment\General($this->getServiceLocator());
		$response = $generalDao->getMaxCapacity($apartmentId);
		return $response['max_capacity'];
	}

    /**
     * Get apartment website link
     *
     * @param int $apartmentId
     * @return string
     */
    public function getWebsiteLink($apartmentId)
    {
        $apartmentGeneralDao = $this->getApartmentGeneralDao('\DDD\Domain\Apartment\Location\ApartmentUrlComponents');
        $components          = $apartmentGeneralDao->getApartmentUrlComponents($apartmentId);
        $websiteURL          = '';

        if ($components) {
            $name       = $components->getUrl();
            $city       = $components->getCitySlug();
            $websiteURL = '//' . DomainConstants::WS_DOMAIN_NAME . '/apartment/' . $name . '--' . $city;
        }

        return $websiteURL;
    }

    /**
     * @access public
     * @param int $apartmentId
     * @return int
     */
    public function getRoomID($apartmentId) {
    	$apartmentRoomDao = $this->getApartmentRoomDao();

    	$room = $apartmentRoomDao->getById($apartmentId);
    	$roomID = $room['id'];
    	return $roomID;
    }

    /**
     * @access publiuc
     * @param int $apartmentId
     *
     * @return int
     * @author Tigran Petrosyan
     */
    public function getReviewScore($apartmentId) {
    	$apartmentReviewDao = $this->getApartmentGeneralDao();

    	$reviewScore = $apartmentReviewDao->getReviewScore($apartmentId);
    	return $reviewScore['score'];
    }

    /**
     * @param $apartmentId
     * @return \Zend\Stdlib\ArrayObject
     */
    public function getApartmentGeneral($apartmentId) {
    	$apartmentGeneralDao = $this->getApartmentGeneralDao('ArrayObject');
    	$generalInfo = $apartmentGeneralDao->getApartmentGeneralInfo($apartmentId);

    	return $generalInfo;
    }
    /**
     *
     * @param array $data
     * @param int $apartmentId
     * @return bool
     */
    public function checkApartmentName($data, $apartmentId)
    {
        if (!isset($data['apartment_name'])) {
            return false;
        }

        $apartmentGeneralDao = $this->getApartmentGeneralDao('ArrayObject');
        $check               = $apartmentGeneralDao->checkApartmentSlug($this->getApartmentSlug($data['apartment_name']), $apartmentId);

        if (!$check) {
            return true;
        }

        return false;
    }

    /**
     * Save Apartment General Data
     *
     * @param $apartmentId
     * @param $data
     * @return int
     * @throws Exception
     */
    public function saveApartmentGeneral($apartmentId, $data) {
    	$generalData = array(
            'name'           => $data['apartment_name'],
            'status'         => $data['status'],
            'edit_date'      => date('Y-m-d'),
            'max_capacity'   => $data['max_capacity'],
            'url'            => $this->getApartmentSlug($data['apartment_name']),
            'square_meters'  => $data['square_meters'],
            'bedroom_count'  => $data['bedrooms'],
            'bathroom_count' => $data['bathrooms'],
            'room_count'     => $data['room_count'],
    	);

    	$roomData = array(
    		'name' => $data['apartment_name'],
    	);

    	$descriptionData = array(
            'check_in'    => $data['chekin_time'],
            'check_out'   => $data['chekout_time']
    	);

    	$generalDescriptionTextlineWhere = array(
            'id' => $data['general_description_textline']
    	);
        $withoutHtml  = strip_tags($data['general_description']);
        $without2nbsp = str_replace('&nbsp;&nbsp;',' ',$withoutHtml);
        $without1nbsp = str_replace('&nbsp;',' ',$without2nbsp);

    	$generalDescriptionTextlineData = array(
            'en' => $data['general_description'],
            'en_html_clean' => $without1nbsp
    	);

        $apartmentGeneralDao     = $this->getApartmentGeneralDao('ArrayObject');
        $apartmentRoomDao        = $this->getApartmentRoomDao('ArrayObject');
        $apartmentDescriptionDao = $this->getApartmentDescriptionDao('ArrayObject');
        $apartmentTextlineDao    = $this->getApartmentTextlineDao();

        if ($apartmentId > 0) {
            $apartmentData = $apartmentGeneralDao->getApartmentGeneralInfo($apartmentId);

            $actionLogger = $this->getServiceLocator()->get('ActionLogger');

            if ($generalData['status'] != $apartmentData['status']) {
                $actionLogger->save(
                    ActionLogger::MODULE_APARTMENT_GENERAL,
                    $apartmentId,
                    ActionLogger::ACTION_APARTMENT_STATUS,
                    (int)$generalData['status']
                );

				// if status change from suspend to selling it should open availability to
				if (   in_array($generalData['status'], [Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE, Objects::PRODUCT_STATUS_LIVEANDSELLIG])
					&& ($apartmentData['status'] == Objects::PRODUCT_STATUS_SUSPENDED)
				) {
					/**
					 * @var \DDD\Service\Apartment\Inventory $apartmentInventoryService
					 */
					$apartmentInventoryService = $this->getServiceLocator()->get('service_apartment_inventory');

					$weekDays     = [1, 1, 1, 1, 1, 1, 1];
					$dateRange    = date('Y-m-d') . ' - ' . date('Y-m-t', strtotime('+12 months'));
					$availability = 1;

					$apartmentInventoryService->updateInventoryRangeByAvailability($apartmentId, $dateRange, $weekDays, $availability, true);
				}

                // if change status from selling should change apartel availability
                if (!in_array($generalData['status'], [Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE, Objects::PRODUCT_STATUS_LIVEANDSELLIG]) &&
                    in_array($apartmentData['status'], [Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE, Objects::PRODUCT_STATUS_LIVEANDSELLIG])
                ) {
                    // remove from apartel room type if has

                    /**
                     * @var \DDD\Dao\Apartel\RelTypeApartment $relApartelRoomTypeApartment
                     * @var \DDD\Service\Queue\InventorySynchronizationQueue $syncService
                     * @var \DDD\Dao\Apartel\Inventory $apartelInventoryDao
                     * @var \DDD\Dao\Apartel\Type $typeDao
                     */
                    $relApartelRoomTypeApartment = $this->getServiceLocator()->get('dao_apartel_rel_type_apartment');
                    $syncService                 = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');
                    $apartelInventoryDao         = $this->getServiceLocator()->get('dao_apartel_inventory');
                    $typeDao                     = $this->getServiceLocator()->get('dao_apartel_type');
                    $dates                       = $apartelInventoryDao->getMinMaxDate();

                    $apartelRoomTypes = $relApartelRoomTypeApartment->getApartelRoomTypeByApartment($apartmentId);
                    foreach ($apartelRoomTypes as $roomType) {
                        $roomTypeId = $roomType['apartel_type_id'];
                        $relApartelRoomTypeApartment->delete([
                            'apartment_id' => $apartmentId,
                            'apartel_type_id' => $roomTypeId,
                        ]);

                        $apartelInventoryDao->setApartelAvailabilityByRoomType($roomTypeId);
                        $isSyncWithCubilis = $typeDao->getApartelTypeSyncWithCubilis($roomTypeId);
                        if ($isSyncWithCubilis) {
                            $syncService->push($roomTypeId, $dates['min_date'], $dates['max_date'], [], $syncService::ENTITY_TYPE_APARTEL);
                        }
                    }
                }
            }

            if ($generalData['name'] != $apartmentData['name']) {
                $actionLogger->save(
                    ActionLogger::MODULE_APARTMENT_GENERAL,
                    $apartmentId,
                    ActionLogger::ACTION_APARTMENT_NAME,
                    'Apartment name change from "'
                        .$apartmentData['name']
                        .'" to "'
                        .$generalData['name'].'"'
                );
            }

            if ($data['general_description'] != $apartmentData['general_description']) {
                $actionLogger->save(
                    ActionLogger::MODULE_APARTMENT_GENERAL,
                    $apartmentId,
                    ActionLogger::ACTION_APARTMENT_DESCRIPTION,
                    'The apartment\'s description was updated'
                );
            }

            $resultGeneral     = $apartmentGeneralDao->save($generalData, array('id' => $apartmentId));
            $resultRoom        = $apartmentRoomDao->save($roomData, array('apartment_id' => $apartmentId));
            $resultDescription = $apartmentDescriptionDao->save($descriptionData, array('apartment_id' => $apartmentId));
            $resultGeneralDescriptionTextline = $apartmentTextlineDao->save($generalDescriptionTextlineData, $generalDescriptionTextlineWhere);

			// If occupancy has been reduced, the corresponding reservation will be added to reservation issue table.
			if ($apartmentData['max_capacity'] > $data['max_capacity']) {
				$reservationIssuesService = $this->getServiceLocator()->get('service_booking_reservation_issues');
				$reservationIssuesService->issueForOccupanyReservation($apartmentId);
			}

        } else {

            if (!isset($data['building_id']) || !$data['building_id'] ||
                !isset($data['building_section']) || !$data['building_section']
            ) {
                throw new Exception('Not set building or building section');
            }

            // building data
            $generalData['building_id'] = $data['building_id'];
            $generalData['building_section_id'] = $data['building_section'];

            //General
            $generalData['create_date'] = date('Y-m-d');
            $apartmentNewId             = $apartmentId = $apartmentGeneralDao->save($generalData);
            $roomData['apartment_id']   = $descriptionData['apartment_id'] = $apartmentNewId;
            //Room
            $roomData['active'] = 1;
            $roomTypeId         = $apartmentRoomDao->save($roomData);
            //Textline
            $generalDescriptionTextlineData['entity_id']   = $apartmentNewId;
			$generalDescriptionTextlineData['entity_type'] = Translation::PRODUCT_SHORT_TEASER_GENERAL_AND_DESCROPTION;
            $generalDescriptionTextlineData['type']        = Translation::PRODUCT_TYPE_APARTMENT;
            $generalDescrID                                = $resultGeneralDescriptionTextline = $apartmentTextlineDao->save($generalDescriptionTextlineData);
            //Description
            $descriptionData['general_descr'] = $generalDescrID;
            $resultDescription                = $apartmentDescriptionDao->save($descriptionData);
            $params                           = ['apartment_id' => $apartmentNewId];
            //Details
            $details             = ['apartment_id' => $apartmentNewId];
            $apartmentDetailsDao = $this->getApartmentDetailsDao();
            $apartmentDetailsDao->save($details);
            //Media
            $apartmentMediaDao = $this->getApartmentMediaDao();
            $apartmentMediaDao->save($params);

            $locationParams = [
                'apartment_id' => $apartmentNewId,
                'x_pos'        => 41,
                'y_pos'        => -74,
            ];
            $apartmentLocationDao = $this->getApartmentLocationDao();
            $apartmentLocationDao->save($locationParams);

            //key Instruction direct textline
            $kiDirectTextLine = [
                'entity_id'   => $apartmentNewId,
				'entity_type' => Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_DIRECT_ENTRY_KEY_INSTRUCTION,
                'type'        => Translation::PRODUCT_TYPE_APARTMENT,
            ];

            $apartmentTextlineDao->save($kiDirectTextLine);
        }

    	return $apartmentId;
    }

    private function getApartmentSlug($name)
    {
        if (!$name) {
            return '';
        }

        return strtolower(str_replace(' ', '-', trim(strtolower($name))));
    }

    public function saveGeneralPolicy($apartmentId, $policy) {
    	$apartmentGeneralDao = $this->getApartmentGeneralDao('ArrayObject');

    	$result = $apartmentGeneralDao->save($policy, array("apartment_id" => $apartmentId));

    	return $result;
    }

	/**
	 * @param int $apartmentId
	 * @param array $metrics
	 * @return int
	 */
    public function saveMetrics($apartmentId, $metrics) {
    	$apartmentRoomDao = $this->getApartmentRoomDao('ArrayObject');

    	$result = $apartmentRoomDao->save($metrics, ["apartment_id" => $apartmentId]);

    	return $result;
    }

	public function getCubilisDetailsAsArray($apartmentId)
    {
		$productDetailsDao = new DetailsDao($this->getServiceLocator());
		$productDetailsDomain = $productDetailsDao->getCubilisDetails($apartmentId);

		return [
			'sync_cubilis' => $productDetailsDomain->getSync_cubilis(),
			'cubilis_id' => $productDetailsDomain->getCubilisId(),
			'cubilis_username' => $productDetailsDomain->getCubilisUs(),
			'cubilis_password' => $productDetailsDomain->getCubilisPass(),
		];
	}

	/**
	 * Returns accommodation room/rate relation.
	 *
	 * @param int $apartmentId Accommodation id
	 * @return array
	 * <pre>
	 * array(
	 *    '0' => array(
	 *       array(
	 *          'rate_id' => 10365,
	 *          'name' => 'Standard Rate',
	 *          'cubilis_rate_id' => 0,
	 *       ),
	 *       ...
	 *    ),
	 *    ...
	 * )
	 * </pre>
	 */
	public function getRoomRates($apartmentId)
    {
		$output = [
			'bo_rate' => [],
			'cubilis_rate' => [],
		];

        /** @var \DDD\Dao\Apartment\Rate  $productRateDao */
		$productRateDao = $this->getServiceLocator()->get('dao_apartment_rate');
		$productRateDomainList = $productRateDao->getRatesByApartmentId($apartmentId);

		if ($productRateDomainList) {
			foreach ($productRateDomainList as $productRateDomain) {
				$output['bo_rate'][$productRateDomain->getRateId()] = [
					'rate_id' => $productRateDomain->getRateId(),
					'name' => $productRateDomain->getRateName(),
					'cubilis_rate_id' => $productRateDomain->getCubilisRateId(),
				];

				$output['cubilis_rate'][$productRateDomain->getCubilisRateId()] = [
					'rate_id' => $productRateDomain->getRateId(),
					'name' => $productRateDomain->getRateName(),
					'cubilis_rate_id' => $productRateDomain->getCubilisRateId(),
				];
			}
		}

		return $output;
	}

    /**
     * @param $params
     * @param $apartmentId
     * @return bool
     */
	public function linkRoomRate($params, $apartmentId) {
		if (is_array($params) && isset($params['product_rates']) && isset($params['product_room'])) {

			if (isset($params['product_room']['room_id']) && isset($params['product_room']['cubilis_room_id'])) {
				$productTypeDao = new Room($this->getServiceLocator());
				$productTypeDao->updateCubilisLink($params['product_room']['room_id'], $params['product_room']['cubilis_room_id']);
			} else {
				return ['status' => 'error', 'msg' => TextConstants::BAD_REQUEST];
			}

			if (count($params['product_rates'])) {

                /** @var \DDD\Dao\Apartment\Rate  $productRateDao */
                $productRateDao = $this->getServiceLocator()->get('dao_apartment_rate');

				$productRateDao->clearCubilisLinks($params['product_room']['room_id']);

				foreach ($params['product_rates'] as $room) {
					if (isset($room['rate_id']) && isset($room['cubilis_rate_id']) && (int)$room['rate_active']) {
						$productRateDao->updateCubilisLink($room['rate_id'], $room['cubilis_rate_id']);
					}
				}
			} else {
                return ['status' => 'error', 'msg' => TextConstants::BAD_REQUEST];
			}
		} else {
            return ['status' => 'error', 'msg' => TextConstants::BAD_REQUEST];
		}

        /**
         * @var \DDD\Service\Queue\InventorySynchronizationQueue $syncService
         * @var \DDD\Dao\Apartment\Inventory $inventoryDao
         */
        $inventoryDao = $this->getServiceLocator()->get('dao_apartment_inventory');
        $syncService = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');

        // get min max date from inventory
        $dates = $inventoryDao->getMinMaxDate();

        // sync to queue
        $syncService->push($apartmentId, $dates['min_date'], $dates['max_date']);

        return ['status' => 'success', 'msg' => TextConstants::SUCCESS_UPDATE];
	}

    /**
     * @return array
     */
    public function permissionChecker()
    {
        $serviceLocator = $this->getServiceLocator();
        $auth = $serviceLocator->get('library_backoffice_auth');
        $notPermission = [];
        if (!$auth->hasRole(Roles::ROLE_APARTMENT_INVENTORY_MANAGER) && !$auth->hasRole(Roles::ROLE_APARTMENT_INVENTORY_READER)) {
            $notPermission[] = 'calendar';
        }

        if (!$auth->hasRole(Roles::ROLE_APARTMENT_INVENTORY_MANAGER)) {
            $notPermission[] = 'inventory-range';
        }

        if (!$auth->hasRole(Roles::ROLE_APARTMENT_COSTS_READER)) {
            $notPermission[] = 'cost';
        }

        $teamUsageService     = $this->getServiceLocator()->get('service_team_usages_security');
        $hasDocViewPermission = $teamUsageService->getUserSecuredTeams($auth->getIdentity()->id);

        if (!$hasDocViewPermission->count() && !$auth->hasRole(Roles::ROLE_DOCUMENTS_MANAGEMENT_GLOBAL)) {
            $notPermission[] = 'document';
        }

        return $notPermission;
    }

    public function isLiveAndSelling($apartmentId)
    {
        $generalDao = $this->getApartmentGeneralDao();
        $result = $generalDao->isLiveAndSelling($apartmentId);
        $text = '';
        //general
        if(!$result['name'] || !$result['currency_id'] || !$result['max_capacity'] || !$result['general_description']){
            $text .= TextConstants::APARTMENT_IS_LIVE_SELLING_GENRAL;
        }
        //rate
        if(!$result['rate_id']){
           $text .= TextConstants::APARTMENT_IS_LIVE_SELLING_RATE;
        }
        //location
        if(!$result['country_id'] || !$result['province_id'] || !$result['city_id'] || !$result['address'] || !$result['x_pos'] || !$result['y_pos']){
            $text .= TextConstants::APARTMENT_IS_LIVE_SELLING_LOCATION;
        }
        //location
        if(!$result['img1']){
            $text .= TextConstants::APARTMENT_IS_LIVE_SELLING_IMG;
        }
        if ($text) {
            $text = TextConstants::APARTMENT_IS_LIVE_SELLING . $text;
        }
        return $text;
    }

    public function disableApartment($apartmentId)
    {
        $apartmentGeneralDao = $this->getApartmentGeneralDao();
        $apartmentRateDao = $this->getApartmentRateDao();
        $apartmentDetailsDao = $this->getApartmentDetailsDao();
        $apartmentInventoryDao = $this->getApartmentInventoryDao();
        $apartmentGroupItemsDao = $this->getApartmentGroupItemsDao();

        // clear building_id field; set disable date
        $apartmentGeneralDao->update(
            [
                'building_id' => 0,
                'building_section_id' => 0,
                'disable_date' => date('Y-m-d')
            ],
            ['id' => $apartmentId]
        );
        $apartmentGroupItemsDao->delete(['apartment_id' => $apartmentId]); // remove from all groups
        $apartmentInventoryDao->delete(['apartment_id' => $apartmentId]); // delete all availabilities
        $apartmentRateDao->delete(['apartment_id' => $apartmentId]); // delete all rates
        $apartmentDetailsDao->update(['sync_cubilis' => 0], ['apartment_id' => $apartmentId]); // disable cubilis connection
    }

    /**
     * @param Int $apartmentId
     * @param String $dateFrom
     * @param String $dateTo
     * @param Int $pax
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    public function getPossibleMoveDestinations($apartmentId, $dateFrom, $dateTo, $rateOccupancy)
    {
        $apartmentGeneralDao = $this->getApartmentGeneralDao('ArrayObject');
        $results = $apartmentGeneralDao->getPossibleMoveDestinations($apartmentId, $dateFrom, $dateTo, $rateOccupancy);

        return $results;
    }

    /**
	 * @access private
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Location
	 */
	private function getApartmentLocationDao($domain = '\DDD\Domain\Apartment\Location\Location')
    {
		return new \DDD\Dao\Apartment\Location($this->getServiceLocator(), $domain);
	}

	/**
	 * @access private
	 * @param string $domain
	 * @return \DDD\Dao\Accommodation\Accommodations
	 */
	private function getAccommodationsDao($domain = 'DDD\Domain\Accommodation\Accommodations') {
		return new \DDD\Dao\Accommodation\Accommodations($this->getServiceLocator(), $domain);
	}

	/**
	 * @access private
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\General
	 */
	private function getApartmentGeneralDao($domain = 'ArrayObject') {
		return new \DDD\Dao\Apartment\General($this->getServiceLocator(), $domain);
	}

	/**
	 * @access private
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Details
	 */
	private function getApartmentDetailsDao($domain = 'ArrayObject') {
		return new \DDD\Dao\Apartment\Details($this->getServiceLocator(), $domain);
	}

	/**
	 * @access private
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Room
	 */
	private function getApartmentRoomDao($domain = 'ArrayObject') {
		return new \DDD\Dao\Apartment\Room($this->getServiceLocator(), $domain);
	}

	/**
	 * @access private
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Description
	 */
	private function getApartmentDescriptionDao($domain = 'ArrayObject') {
		return new \DDD\Dao\Apartment\Description($this->getServiceLocator(), $domain);
	}

	/**
	 * @access public
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Textline
	 */
	public function getApartmentTextlineDao($domain = 'ArrayObject') {
		return new \DDD\Dao\Apartment\Textline($this->getServiceLocator(), $domain);
	}

	/**
	 * @access public
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Rate
	 */
	public function getApartmentRateDao($domain = 'ArrayObject') {
		return new \DDD\Dao\Apartment\Rate($this->getServiceLocator(), $domain);
	}

	/**
	 * @access public
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Media
	 */
	public function getApartmentMediaDao($domain = 'ArrayObject') {
		return new \DDD\Dao\Apartment\Media($this->getServiceLocator(), $domain);
	}

    /**
     * @access private
     * @param string $domain
     * @return \DDD\Dao\Apartment\Inventory
     */
    private function getApartmentInventoryDao($domain = 'ArrayObject') {
        return new \DDD\Dao\Apartment\Inventory($this->getServiceLocator(), $domain);
    }

	/**
	 * @param $apartmentId
	 * @return array
	 */
	public function getBuildingFacilitiesByApartmentId($apartmentId)
	{
		$buildingFacilitiesDao = $this->getServiceLocator()->get('dao_building_facilities');
		$apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');

        $result = [];
		$currentApartmentBuilding   = $apartmentGeneralDao->getBuildingFacilitiesByApartmentId($apartmentId);
		$allPossibleFacilitiesArray = $buildingFacilitiesDao->getAllPossibleBuildingFacilitiesArray();

		foreach($allPossibleFacilitiesArray as $key => $value) {
			if(in_array($key,$currentApartmentBuilding)){
                $result[] = $value;
			}
		}

		return $result;
	}

    public function getAllApartmentsWithLock($lockId)
    {
        $apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');
        return $apartmentGeneralDao->getAllApartmentsWithLock($lockId);
    }

	/**
	 * @param $apartmentId
	 * @return array
	 */
	public function getLockId($apartmentId)
	{
		$apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');
		return $apartmentGeneralDao->getLockId($apartmentId);

	}

    /**
     * @param $apartmentId
     * @return array
     */
    public function getInfoForDetailsController($apartmentId)
    {
        $apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');
        return $apartmentGeneralDao->getInfoForDetailsController($apartmentId);

    }

	public function saveLock($apartmentId,$lockId)
	{
		$apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');
		$apartmentGeneralDao->save(['lock_id'=>$lockId],['id'=>$apartmentId]);

	}

    public function getApartmentSearch($txt, $onlyLiveAndSelling = true) {
        $apartmentDao   = $this->getServiceLocator()->get('dao_apartment_general');
        return $apartmentDao->getApartmentSearch($txt, $onlyLiveAndSelling);
    }

    /**
     * @access private
     * @param string $domain
     * @return \DDD\Dao\ApartmentGroup\ApartmentGroupItems
     */
    private function getApartmentGroupItemsDao($domain = 'ArrayObject') {
        return new \DDD\Dao\ApartmentGroup\ApartmentGroupItems($this->getServiceLocator(), $domain);
    }
}
