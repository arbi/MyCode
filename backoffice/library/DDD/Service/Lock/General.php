<?php

namespace DDD\Service\Lock;

use DDD\Dao\Lock\Types;
use DDD\Service\ServiceBase;
use Zend\Log\Logger;
use DDD\Domain\Lock\ForSelect;
use Zend\Db\Sql\Where;
use Library\Constants\DbTables;
use DDD\Service\Lock\General as LockService;

/**
 * Service class providing methods to work with locks
 * @author Hrayr Papikyan
 * @package core
 * @subpackage core/service
 */
class General extends ServiceBase
{
	const SETTING_ITEM_TYPE_INPUT      		 = 'input';
    const SETTING_ITEM_TYPE_INPUT_MONTH 	 = 'inputMonth';
    const SETTING_ITEM_TYPE_OFFICES_DROPDOWN = 'officesDropdown';

	const SETTING_TYPE_LOCK_BOX   = 1;
	const SETTING_TYPE_PIN        = 2;
	const SETTING_TYPE_ASC        = 3;
	const SETTING_TYPE_MECHANICAL = 4;
	const SETTING_TYPE_NONE       = 5;
	const FREE_ENTRY		      = 83;

	public static $typeWithoutCode = [
		self::SETTING_TYPE_MECHANICAL,
		self::SETTING_TYPE_NONE
	];

	const TYPE_PIN_MASTER_PREFIX   = 1;
	const TYPE_PIN_MASTER_SUFFIX   = 2;
	const TYPE_PIN_MASTER_PASSWORD = 3;

	const USAGE_APARTMENT = 'usage_apartment';
	const USAGE_BUILDING  = 'usage_building';
	const USAGE_PARKING   = 'usage_parking';

	const USAGE_APARTMENT_TYPE = 1;
	const USAGE_BUILDING_TYPE  = 2;
	const USAGE_PARKING_TYPE   = 3;
	/**
	 * @return array
	 */
    public  function getAllLockTypesForSelect()
	{
		/**
		 * @var Types $lockTypesDao
		 */
      $lockTypesDao = $this->getServiceLocator()->get('dao_lock_types');
	  $allLockTypes = $lockTypesDao->getAllLockTypesForSelect();
	  $allLockTypesArray = [0 => '-- Please Select --'];
	  foreach ($allLockTypes as $lockType) {
		$allLockTypesArray[$lockType->getId()] = $lockType->getName();
	  }
	  return $allLockTypesArray;
	}

	/**
	 * @return array
	 */
	public  function getAllLockTypeExplanations()
	{
		/**
		 * @var Types $lockTypesDao
		 */
		$lockTypesDao = $this->getServiceLocator()->get('dao_lock_types');
		$allLockTypes = $lockTypesDao->getAllLockTypesForSelect();
		$allLockTypesArray = [];
		foreach ($allLockTypes as $lockType) {
			$allLockTypesArray[$lockType->getId()] = $lockType->getExplanation();
		}
		return $allLockTypesArray;
	}

	/**
	 * @param $lockTypeId
	 * @return string
	 */
	public function getHtmlOfSettingsForType($lockTypeId)
	{
        $lockSettingsDao = $this->getServiceLocator()->get('dao_lock_type_setting_items');
		$result = $lockSettingsDao->getLockTypeSettings($lockTypeId);
		$html = $this->makeHtml($result);
		return $html;
	}

	/**
	 * @param $lockTypeSettingsDomain
	 * @return string
	 */
	protected function makeHtml($lockTypeSettingsDomain)
	{
        $html = '';
		$eachType = [];
		$lastType = '';
        foreach ($lockTypeSettingsDomain as $item) {
			if($lastType != $item->getInputType() && $lastType!=''){
				$html .= '</div>';
			}
			if(!isset($eachType[$item->getInputType()])) {
				$eachType[$item->getInputType()] = 1;
			}
			else {
				$eachType[$item->getInputType()]++;
			}

			$html .= $this->{'make' . ucfirst($item->getInputType()) . 'Html'}($item,$eachType[$item->getInputType()]);

			$lastType = $item->getInputType();
		}

		return $html;
	}

	/**
	 * @param $lockTypeSettingsDomain
	 * @param $count
	 * @return string
	 */
	protected function makeInputHtml($lockTypeSettingsDomain, $count)
	{  $html = '';
		if(($count % 2) ==1) {
			$html .= '<div class="form-group">';
		}
	   $html .= '<label for="setting_' . $lockTypeSettingsDomain->getId() . '" class="col-xs-5 col-sm-2 control-label">';
	   $html .= $lockTypeSettingsDomain->getName();
	   if ($lockTypeSettingsDomain->isRequired()) {
		   $html .=  ' <span class="text-danger required" data-toggle="tooltip" title="" data-original-title="Required">*</span>';
	   }
	   $html .= '</label>';
	   $html .= '<div class="col-xs-5 col-sm-3">';
	   $html .= '<input type="text" name="setting_' . $lockTypeSettingsDomain->getId() . '" class="form-control generated-setting';
	   if ($lockTypeSettingsDomain->isRequired()) {
		   $html .= ' generated-setting-required';
	   }
		$html .= '" >';
	   $html .= '</div>';
		if (($count % 2) ==0 ) {
			$html .= '</div>';
		}
	   return $html;
	}

	/**
	 * @param $lockTypeSettingsDomain
	 * @param $count
	 * @return string
	 */
	protected function makeInputMonthHtml($lockTypeSettingsDomain, $count)
	{
		$html = '';
		if (($count % 3) ==1) {
			$html = '<div class="form-group">';
		}
		$html .= '<label for="setting_' . $lockTypeSettingsDomain->getId() . '" class="col-xs-5 col-sm-2 control-label">';
		$html .= $lockTypeSettingsDomain->getName();
		if ($lockTypeSettingsDomain->isRequired()) {
			$html .=  ' <span class="text-danger required" data-toggle="tooltip" title="" data-original-title="Required">*</span>';
		}
		$html .= '</label>';
		$html .= '<div class="col-xs-5 col-sm-1">';
		$html .= '<input type="text" name="setting_' . $lockTypeSettingsDomain->getId() . '" class="form-control generated-setting';
		if ($lockTypeSettingsDomain->isRequired()) {
			$html .= ' generated-setting-required';
		}
		$html .= '" >';
		$html .= '</div>';
		if (($count % 3) ==0 ) {
			$html .= '</div>';
		}
		return $html;
	}

	/**
	 * @param $lockTypeSettingsDomain
	 * @param $count
	 * @return string
	 */
	protected function makeOfficesDropdownHtml($lockTypeSettingsDomain, $count)
	{
		$html = '';
		if(($count % 2) ==1) {
			$html .= '<div class="form-group">';
		}
		$html .= '<label for="setting_' . $lockTypeSettingsDomain->getId() . '" class="col-xs-5 col-sm-2 control-label">';
		$html .= $lockTypeSettingsDomain->getName();
		if ($lockTypeSettingsDomain->isRequired()) {
			$html .=  ' <span class="text-danger required" data-toggle="tooltip" title="" data-original-title="Required">*</span>';
		}
		$html .= '</label>';
		$html .= '<div class="col-xs-5 col-sm-3">';
		$html .= '<select name="setting_' . $lockTypeSettingsDomain->getId() . '" class="form-control generated-setting';
		if ($lockTypeSettingsDomain->isRequired()) {
			$html .= ' generated-setting-required';
		}
		$html .= '" >';
		$officesService = $this->getServiceLocator()->get('service_office');
		$selectOptions = $officesService->getOfficeSelectOptions();
		foreach ($selectOptions as $key=>$value) {
			$html .= '<option value="' . $key . '">' . $value . '</option>';
		}
		$html .= '</select>';
		$html .= '</div>';
		if (($count % 2) ==0 ) {
			$html .= '</div>';
		}
		return $html;
	}

	/**
	 * @param $data
	 */
	public function saveNewLock($data)
	{
		$locksDao = $this->getServiceLocator()->get('dao_lock_locks');
		$dataArray = (array) $data;
		$lastInsertedId = $locksDao->saveNewLock($dataArray);
		if (isset($data['additional_settings'])) {
			$lockSettingDao = $this->getServiceLocator()->get('dao_lock_settings');
			$lockSettingDao->saveNewLockSettings($lastInsertedId,$data['additional_settings']);
		}
	}

	/**
	 * @param $data
	 */
	public function editLock($data)
	{
		$locksDao = $this->getServiceLocator()->get('dao_lock_locks');
		$dataArray = (array) $data;
		$locksDao->editLock($dataArray);
		if (isset($data['additional_settings'])) {
			$lockSettingDao = $this->getServiceLocator()->get('dao_lock_settings');
			$lockSettingDao->editLockSettings($data['additional_settings']);
		}
	}

	/**
	 * @param $lockId
	 */
	public function deleteLock($lockId)
	{
		$locksDao = $this->getServiceLocator()->get('dao_lock_locks');
		$locksDao->deleteLock($lockId);
		$lockSettingDao = $this->getServiceLocator()->get('dao_lock_settings');
		$lockSettingDao->deleteLockSettings($lockId);
		return true;
	}

	/**
	 * @param $lockId
	 * @return array
	 */
	public function getLockInfo($lockId)
	{
		$locksDao       = $this->getServiceLocator()->get('dao_lock_locks');
		$lockSettingDao = $this->getServiceLocator()->get('dao_lock_settings');
		$lockInfo       = $locksDao->getLockById($lockId);
        if ($lockInfo === false) {
			return false;
		}
		$lockSettings = $lockSettingDao->getLockSettingsByLockId($lockId);
		$formData = [
			'id'          => $lockInfo->getId(),
			'type_id'     => $lockInfo->getTypeId(),
			'name'        => $lockInfo->getName(),
			'description' => $lockInfo->getDescription(),
			'explanation' => $lockInfo->getExplanation(),
			'is_physical' => $lockInfo->isPhysical()
		];
		$settingsWithNames = [];
		foreach ($lockSettings as $item) {
			$formData['setting_' . $item->getId()] = $item->getValue();

			$settingsWithNames[$item->getId()] = [
				'label'      => $item->getSettingName(),
				'type'       => $item->getSettingType(),
				'isRequired' => $item->isSettingRequired()
			];

			if ($item->getSettingType() == self::SETTING_ITEM_TYPE_OFFICES_DROPDOWN) {
				$officesService = $this->getServiceLocator()->get('service_office');
				$settingsWithNames[$item->getId()]['options'] = $officesService->getOfficeSelectOptions();
			}
		}

		$result = [
				'formData'          => $formData,
				'settingsWithNames' => $settingsWithNames
		];

	    return $result;
	}

	/**
	 * @return array
	 */
	public function getAllLocksForSelect()
	{
		$locksDao = $this->getServiceLocator()->get('dao_lock_locks');
		$locksDao->getResultSetPrototype()->setArrayObjectPrototype(new ForSelect());
		$allLocks = $locksDao->fetchAll();
		$allLocksArray = [0 => '-- Choose The Lock --'];
		foreach ($allLocks as $item) {
			$allLocksArray[$item->getId()] = $item->getName();
		}

		return $allLocksArray;
	}

	/**
	 * @param array $filterParams
	 * @return mixed
	 */
	public function getLocksSearchResults($filterParams = [])
	{
		/**
		 * @var \DDD\Dao\Lock\Locks $locksDao
		 */
		$locksDao = $this->getServiceLocator()->get('dao_lock_locks');
		$where = $this->constructWhereFromFilterParams($filterParams);
		$result = $locksDao->getLocksSearchResults($where);

		return $result;
	}

	/**
	 * @param $filterParams
	 * @return Where
	 */
    protected function constructWhereFromFilterParams($filterParams)
	{
		$where = new Where();

        if (isset($filterParams['usage']) && $filterParams['usage']!='' ) {
			$where->equalTo('lock_types.' . $filterParams['usage'],1);
		}

		if (isset($filterParams['type_id']) && $filterParams['type_id']!='' && (int)$filterParams['type_id'] != 0) {
			$where->equalTo(DbTables::TBL_LOCKS . '.type_id',(int)$filterParams['type_id']);
		}

        if (isset($filterParams['sSearch']) && !empty($filterParams['sSearch'])) {
            $searchQuery = trim(strip_tags($filterParams['sSearch']));

            if (!empty($searchQuery)) {
                $where->nest()
                    ->like(DbTables::TBL_LOCKS . '.name', '%' . $searchQuery . '%')
                    ->or
                    ->like(DbTables::TBL_LOCKS . '.description', '%' . $searchQuery . '%')
                    ->unnest();
            }
        }

		return $where;
	}

	public static function getAllUsages()
	{
		return [
			'' => '-- All Usages --',
			self::USAGE_APARTMENT => 'Apartment',
			self::USAGE_BUILDING  => 'Building',
			self::USAGE_PARKING   => 'Parking',
		];
	}

	public function checkDuplicatePhysicalLock($usageId, $lockId, $selectedUsage, $notPhysical = false)
    {
		/**
		 * @var \DDD\Dao\ApartmentGroup\BuildingSections $buildingSectionsDao
		 */
        $lockDao      = $this->getServiceLocator()->get('dao_lock_locks');
		$buildingSectionsDao = $this->getServiceLocator()->get('dao_apartment_group_building_sections');
        $parkingDao   = $this->getServiceLocator()->get('dao_parking_general');
        $apartmentDao = $this->getServiceLocator()->get('dao_accommodation_accommodations');

		$result =  ['isDuplicate' => false];

        $daoArray = [$apartmentDao, $buildingSectionsDao, $parkingDao];
		$totalNum = 0;
        $lockInfo = $lockDao->fetchOne(['id' => $lockId]);

        if ($lockInfo && ($lockInfo->isPhysical() || $notPhysical)) {
            foreach ($daoArray as $dao) {
                $physicalUsedCount = $lockDao->getPhysicalUsage($dao, $usageId, $lockId, $selectedUsage, $notPhysical);
                //if notphysical is true: get total number of entites which this lock is assigned to.
                if (!$notPhysical) {
	                if ((int)$physicalUsedCount['count']) {
	                    $result =  [
							'isDuplicate' => true,
							'name'		  => $lockInfo->getName()
	                    ];
	                    return $result;
	                }
	            } else {
					$totalNum += (int)$physicalUsedCount['count'];
	            }
            }

            if ($notPhysical && (int)$totalNum) {
				$result =  [
					'isDuplicate' => true,
					'total'       => (int)$totalNum
				];

            }

            return $result;
        }
        return $result;
    }

	public function getLockInfoByUsage($usageId, $usage, $pin = false)
    {
        $response = [];

		$accommodationDao = $this->getServiceLocator()->get('dao_accommodation_accommodations');

        switch ($usage) {
            case self::USAGE_APARTMENT_TYPE:
				$apartmentInfo = $accommodationDao->fetchOne(['id' => $usageId], ['id']);

				if ($apartmentInfo) {
					$locksDao = $this->getServiceLocator()->get('dao_lock_locks');
					$lockInfo = $locksDao->getLockByUsage($usageId, self::USAGE_APARTMENT_TYPE);

					if ($lockInfo) {
						$lockData = iterator_to_array($lockInfo);
						if (!$pin) {
							return $lockData[0]['type_id'];
						}
					} else {
						return false;
					}
				}

                break;
            case self::USAGE_BUILDING_TYPE:
				//TODO: write its logic if needed
                break;
            case self::USAGE_PARKING_TYPE:
				//TODO: write its logic if needed
                break;
        }

        if ($lockData) {

            $lockType = $lockData[0]['type_id'];
            $timezone = $lockData[0]['timezone'];
            $lockCode = null;

            switch ($lockType) {
                case self::SETTING_TYPE_LOCK_BOX:
                    $time     = new \DateTime(null, new \DateTimeZone($timezone));
                    $curentMonth = $time->format('F');

                    foreach ($lockData as $row) {
                        if ($curentMonth == $row['name']) {
                            $lockCode['type'] = self::SETTING_TYPE_LOCK_BOX;
                            $lockCode['code'] = $row['value'];
                        }
                    }
                    break;
                case self::SETTING_TYPE_PIN:
                    foreach ($lockData as $value) {
                        if ($value['setting_item_id'] == self::TYPE_PIN_MASTER_PREFIX) {
                            $prefix = $value['value'];
                        }

                        if ($value['setting_item_id'] == self::TYPE_PIN_MASTER_SUFFIX) {
                            $suffix = $value['value'];
                        }
                    }

                    $lockCode['type'] = self::SETTING_TYPE_PIN;
                    $lockCode['code'] = $prefix . $pin . $suffix;
                    break;
                case self::SETTING_TYPE_ASC:
                    foreach ($lockData as $value) {
                        if (!empty($value['value'])) {
                            $lockCode['type'] = self::SETTING_TYPE_ASC;
                            $lockCode['code'] = $value['value'];
                        }
                    }
                    break;
                case self::SETTING_TYPE_MECHANICAL:
                    $lockCode['type'] = self::SETTING_TYPE_MECHANICAL;
                    $lockCode['code'] = 'Mechanical Key';
                    break;
                case self::SETTING_TYPE_NONE:
                    $lockCode['type'] = self::SETTING_TYPE_NONE;
                    $lockCode['code'] = 'Free Entrance';
                    break;
                default:
                    $lockCode['type'] = self::SETTING_TYPE_NONE;
                    $lockCode['code'] = 'Free Entrance';
            }

            $response[$usage] = $lockCode;
        }
        return $response;
    }


	/**
	 * @param $apartmentId
	 * @param $pin
	 * @param $usages
	 * @param bool|false $getNull
	 * @return array
	 */
	public function getLockByReservationApartmentId($apartmentId, $pin, $usages, $getNull = false)
	{
		/**
		 * @var \DDD\Dao\Lock\Locks $locksDao
		 */
		$locksDao = $this->getServiceLocator()->get('dao_lock_locks');
		$response = [];

		foreach ($usages as $usage) {
			$lockData = $locksDao->getLockByReservationApartmentId($apartmentId, $usage);

			if ($lockData) {
				$lockData = iterator_to_array($lockData);
				$lockType = $lockData[0]['type_id'];
				$timezone = $lockData[0]['timezone'];
				$lockCode = null;

				switch ($lockType) {
					case LockService::SETTING_TYPE_LOCK_BOX:
						$time     = new \DateTime(null, new \DateTimeZone($timezone));
						$currentMonth = $time->format('F');

						foreach ($lockData as $row) {
							if ($currentMonth == $row['name']) {
								$lockCode['type'] = LockService::SETTING_TYPE_LOCK_BOX;
								$lockCode['code'] = $row['value'];
							}
						}
						break;
					case LockService::SETTING_TYPE_PIN:
						foreach ($lockData as $value) {
							if ($value['setting_item_id'] == LockService::TYPE_PIN_MASTER_PREFIX) {
								$prefix = $value['value'];
							}

							if ($value['setting_item_id'] == LockService::TYPE_PIN_MASTER_SUFFIX) {
								$suffix = $value['value'];
							}
						}

						$lockCode['type'] = LockService::SETTING_TYPE_PIN;
						$lockCode['code'] = $prefix . $pin . $suffix;
						break;
					case LockService::SETTING_TYPE_ASC:
						foreach ($lockData as $value) {
							if (!$getNull || !empty($value['value'])) {
								$lockCode['type'] = LockService::SETTING_TYPE_ASC;
								$lockCode['code'] = $value['value'];
							}
						}
						break;
					case LockService::SETTING_TYPE_MECHANICAL:
						if (!$getNull) {
							$lockCode['type'] = LockService::SETTING_TYPE_MECHANICAL;
							$lockCode['code'] = 'Mechanical Key';
						}
						break;
					case LockService::SETTING_TYPE_NONE:
						if (!$getNull) {
							$lockCode['type'] = LockService::SETTING_TYPE_NONE;
							$lockCode['code'] = 'Free Entrance';
						}
						break;
					default:
						$lockCode['type'] = LockService::SETTING_TYPE_NONE;
						$lockCode['code'] = 'Free Entrance';
				}

				$response[$usage] = $lockCode;
			}
		}
		return $response;
	}

}
