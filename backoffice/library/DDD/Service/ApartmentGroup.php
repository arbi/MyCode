<?php

namespace DDD\Service;

use DDD\Dao\ApartmentGroup\ApartmentGroup as ApartmentGroupDAO;
use DDD\Dao\ApartmentGroup\ApartmentGroupItems as ApartmentGroupItemsDAO;
use DDD\Dao\ApartmentGroup\ConciergeView;
use DDD\Domain\ApartmentGroup\ApartmentGroup as ApartmentGroupDomain;
use DDD\Domain\ApartmentGroup\ApartmentGroupTableRow;
use DDD\Service\Booking\BookingTicket as ReservationTicketService;
use DDD\Service\Accommodations as ApartmentService;
use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\TextConstants;
use Library\Finance\CreditCard\CreditCard;
use Library\Utility\Helper;
use Library\Constants\DbTables;
use Library\ActionLogger\Logger as ActionLogger;
use Library\Constants\Roles;

use Zend\Db\Sql\Where;

class ApartmentGroup extends ServiceBase
{
    protected $_concierge              = null;
    protected $_conciergeaccommodation = null;
    protected $_accommodation          = null;
    protected $_conciergeView          = null;
    protected $_conciergeGroup         = null;
    protected $_user                   = null;
    protected $_booking                = null;

    const APARTMENT_GROUP_CASTELLDEFELS = 44;

    const SHOW_AS_BLACKLISTED_IN_CONCIERGE_DASHBOARD_IF_MORE_THAN_SCORE = 100;

    /**
     * @param $apartmentGroupId
     * @return array
     *
     * @author Tigran Petrosyan
     */
    public function getApartmentGroupItems($apartmentGroupId)
    {
        /**
         * @var ApartmentGroupItems $apartmentGroupItemsDao
         */
        $apartmentGroupItemsDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group_items');

        $apartments = $apartmentGroupItemsDao->getApartmentGroupItems($apartmentGroupId);
        $apartmentIds = [];

        foreach ($apartments as $apartment) {
            $apartmentIds[] = $apartment->getApartmentId();
        }

        return $apartmentIds;
    }

    /**
     * @param      $start
     * @param      $limit
     * @param      $sortCol
     * @param      $sortDir
     * @param      $search
     * @param      $all
     * @param null $managerId
     *
     * @return \Zend\Db\ResultSet\ResultSet|ApartmentGroupTableRow[]
     */
    public function getApartmentGroupsList(
        $start,
        $limit,
        $sortCol,
        $sortDir,
        $search,
        $all,
        $managerId = null
    ) {
        $auth = $this
            ->getServiceLocator()
            ->get('library_backoffice_auth');

        $hasDevTestRole = $auth->hasRole(Roles::ROLE_DEVELOPMENT_TESTING);

        /**
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $apartmentGroupDAO
         */
        $apartmentGroupDAO = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

        return $apartmentGroupDAO->getApartmentGroupsList(
            $start,
            $limit,
            $sortCol,
            $sortDir,
            $search,
            $all,
            $managerId,
            $hasDevTestRole
        );
    }

    /**
     * @param int $apartmentGroupId
     * @return string
     */
    public function getApartmentGroupNameById($apartmentGroupId)
    {
        /**
         * @var ApartmentGroupDAO $apartmentGroupDAO
         * @var ApartmentGroupDomain $row
         */
        $apartmentGroupDAO = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

        $row = $apartmentGroupDAO->fetchOne([
            'id' => $apartmentGroupId
        ], ['name']);

        return ($row ? $row->getName() : '');
    }

    /**
     * @return array
     */
    public function getManageableList()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var User $userService
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $userService = $this->getServiceLocator()->get('service_user');
        $currentUserId = $auth->getIdentity()->id;
        $managersList = [$currentUserId];
        $groupList = [];

        $managees = $userService->getUserManagees($currentUserId);

        if ($managees->count()) {
            foreach ($managees as $managee) {
                array_push($managersList, $managee['id']);
            }
        }

        $groupRawList = $this->getGroupListByManagersList($managersList);

        if ($groupRawList->count()) {
            foreach ($groupRawList as $group) {
                array_push($groupList, $group['id']);
            }
        }

        return $groupList;
    }

    /**
     * @param array $managersList
     * @return \ArrayObject
     */
    public function getGroupListByManagersList($managersList)
    {
        /**
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $dao
         */
        $dao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $result = $dao->getGroupListByManagersList($managersList);

        return $result;
    }

    public function getConciergeManageById($id)
    {
        /**
         * @var User $usersService
         */
        $accGroupManageDao = $this->getConcierge();

        if (!$accGroupManageDao->checkRowExist(DbTables::TBL_APARTMENT_GROUPS, 'id', $id)) {
            throw new \Exception("Could not find row");
        }

        $accGroupManageMain  = $accGroupManageDao->getRowById($id);
        $this->registry->set('accGroupManageMain', $accGroupManageMain);

        $accommodationDao  = $this->getConciergeAccommodation();
        $accommodations    = $accommodationDao->getApartmentGroupItems((int)$id, true, true);
        $this->registry->set('accommodationsList', $accommodations);

        $users           = [];
        $usersDao        = $this->getUser();
        $usersDomainList = $usersDao->fetchAll();

        if ($usersDomainList->count()) {
            foreach ($usersDomainList as $usersDomain) {
                $users[$usersDomain->getId()] = $usersDomain->getFirstName() . ' ' . $usersDomain->getLastName();
            }
        }

        $this->registry->set('usersList', $users);

        return $this->registry;
    }

    /**
     * @param \DDD\Domain\ApartmentGroup\ApartmentGroup | bool $data
     * @return bool|\Library\Registry\Registry
     * @throws \Exception
     */
    public function getConciergeOptions($data = false)
    {
        /** @var User $usersService */
        $usersService = $this->getServiceLocator()->get('service_user');
        /** @var \DDD\Service\Location $generalLocationService */
        $generalLocationService = $this->getServiceLocator()->get('service_location');
        /** @var \DDD\Dao\Accommodation\Accommodations $accommodationDao */
        $accommodationDao = $this->getAccommodation();
        /** @var \DDD\Dao\Psp\Psp $pspDao */
        $pspDao = $this->getServiceLocator()->get('dao_psp_psp');

        $countries              = $generalLocationService->getAllActiveCountries();
        $accommodationList = $accommodationDao->fetchAll(
            'status <> ' . ApartmentService::APARTMENT_STATUS_DISABLED . ' ORDER BY `name`'
        );

        $this->registry->set('accommodationList', $accommodationList);

        $people = $usersService->getPeopleList();
        $this->registry->set('peopleList', $people);

        $apartments = [];
        $apartmentDomainList = $accommodationDao->fetchAll();

        if ($apartmentDomainList->count()) {
            foreach ($apartmentDomainList as $apartmentDomain) {
                $apartments[$apartmentDomain->getId()] = $apartmentDomain->getName();
            }
        }
        $countryOptions = ['' => '-- Choose Country --'];
        if ($countries->count()) {
            foreach ($countries as $country) {
                if ($country->getChildrenCount() != '') {
                    $countryOptions[$country->getID()] = $country->getName();
                }
            }
        }

        $pspId = $data ? $data->getPspId() : false;
        $pspList = $pspDao->getPsps(false, $pspId);

        $this->registry->set('apartmentList', $apartments);
        $this->registry->set('countryList', $countryOptions);
        $this->registry->set('pspList', $pspList);
        return $this->registry;
    }

    public function checkGroupName($name, $id)
    {
        $accGroupsManagementDao = $this->getConcierge();
        $rs = $accGroupsManagementDao->checkGroupName($name, $id);

        return $rs;
    }

    public function conciergeSave($data, $id, $global)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroupItems $apartmentGroupItemsDao
         * @var \Library\ActionLogger\Logger $actionLogger
         */
        $actionLogger = $this->getServiceLocator()->get('ActionLogger');
        $accGroupsManagementDao = $this->getConcierge();
        $insert_id = $id;

        if (!$global) {
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $conciergeData = $accGroupsManagementDao->getRowById($id);

            if ($conciergeData->getGroupManagerId() == $auth->getIdentity()->id) {
                $global = true;
            }
        }

        if (!isset($data['check_users']) || !(int)$data['check_users']) {
            $data['concierge_email'] = null;
        }

        if ($global) {
            $accGroupData = array(
                'name'                      => $data['name'],
                'timezone'                  => $data['timezone'],
                'group_manager_id'          => isset($data['group_manager_id']) ? $data['group_manager_id'] : 0,
                'usage_concierge_dashboard' => (isset($data['check_users']) && (int) $data['check_users'] > 0) ? 1 : 0,
                'usage_cost_center'         => (isset($data['usage_cost_center']) && (int) $data['usage_cost_center'] > 0) ? 1 : 0,
                'usage_building'            => (isset($data['usage_building']) && (int) $data['usage_building'] > 0) ? 1 : 0,
                'usage_apartel'             => (isset($data['usage_apartel']) && (int) $data['usage_apartel'] > 0) ? 1 : 0,
                'usage_performance_group'   => (isset($data['usage_performance_group']) && (int) $data['usage_performance_group'] > 0) ? 1 : 0,
                'email'                     => (!empty($data['concierge_email'])) ? $data['concierge_email'] : null,
                'country_id'                => (!empty($data['country_id'])) ? $data['country_id'] : null,
            );

            $buildingDetailsDao = $this->getServiceLocator()->get('dao_apartment_group_building_details');
            $accommodationsDao = $this->getAccommodationsDao();

            if ($id > 0) {
                $apartmentGroupCurrentState = $accGroupsManagementDao->getRowById($id);

                // check for change Apartment Group name
                if ($apartmentGroupCurrentState->getName() != $data['name']) {
                    $actionLogger->save(
                        ActionLogger::MODULE_APARTMENT_GROUPS,
                        $id,
                        ActionLogger::ACTION_APARTMENT_GROUPS_NAME,
                        'The Apartment Group name change from "'
                            .$apartmentGroupCurrentState->getName()
                            .'" to "'
                            .$data['name'].'"'
                    );
                }

                // check for change Usage - Cost Center
                if ($apartmentGroupCurrentState->getCostCenter() != $data['usage_cost_center']) {
                    $value = ($data['usage_cost_center']) ? 'is set as' : 'is set as non';

                    $actionLogger->save(
                        ActionLogger::MODULE_APARTMENT_GROUPS,
                        $id,
                        ActionLogger::ACTION_APARTMENT_GROUPS_USAGE,
                        'Group\'s usage ' . $value . ' Cost Center'
                    );
                }

                // check for change Usage - Concierge Dashboard
                if ($apartmentGroupCurrentState->getIsArrivalsDashboard() != $data['check_users']) {
                    $value = ($data['check_users']) ? 'is set as' : 'is set as non';

                    $actionLogger->save(
                        ActionLogger::MODULE_APARTMENT_GROUPS,
                        $id,
                        ActionLogger::ACTION_APARTMENT_GROUPS_USAGE,
                        'Group\'s usage ' . $value . ' Concierge Dashboard'
                    );
                }

                // check for change Usage - Building
                if ($apartmentGroupCurrentState->isBuilding() != $data['usage_building']) {
                    $value = ($data['usage_building']) ? 'is set as' : 'is set as non';

                    $actionLogger->save(
                        ActionLogger::MODULE_APARTMENT_GROUPS,
                        $id,
                        ActionLogger::ACTION_APARTMENT_GROUPS_USAGE,
                        'Group\'s usage ' . $value . ' Building'
                    );
                }

                // check for change Usage - Apartel
                if ($apartmentGroupCurrentState->getIsApartel() != $data['usage_apartel']) {
                    $value = ($data['usage_apartel']) ? 'is set as' : 'is set as non';

                    $actionLogger->save(
                        ActionLogger::MODULE_APARTMENT_GROUPS,
                        $id,
                        ActionLogger::ACTION_APARTMENT_GROUPS_USAGE,
                        'Group\'s usage ' . $value . ' Apartel'
                    );
                }

                if (!$apartmentGroupCurrentState->getIsApartel() && $data['usage_apartel']) {
                    /**
                     * @var \DDD\Service\ApartmentGroup\Usages\Apartel $apartelUsageService
                     */
                    $apartelUsageService = $this->getServiceLocator()->get('service_apartment_group_usages_apartel');
                    $apartelUsageService->save([
                        'group_id' => $id
                    ]);
                }

                // check for change Usage - Performance
                if ($apartmentGroupCurrentState->getIsPerformanceGroup() != $data['usage_performance_group']) {
                    $value = ($data['usage_performance_group']) ? 'is set as' : 'is set as non';

                    $actionLogger->save(
                        ActionLogger::MODULE_APARTMENT_GROUPS,
                        $id,
                        ActionLogger::ACTION_APARTMENT_GROUPS_USAGE,
                        'Group\'s usage ' . $value . ' Performance'
                    );
                }

                $apartmentGroupItemsDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group_items');
                $apartmentsCurrentList = $apartmentGroupItemsDao->getApartmentGroupItems($id);

                if (isset($data['accommodations']) && is_array($data['accommodations'])) {
                    $newApartmentsList = $data['accommodations'];
                } else {
                    $newApartmentsList = [];
                }

                // check for removing Apartments from group
                foreach ($apartmentsCurrentList as $currentApartment) {
                    if (($key = array_search($currentApartment->getApartmentId(), $newApartmentsList)) === FALSE) {
                        $accommodationsDao->save(['building_id' => 0], ['id' => $currentApartment->getApartmentId()]);

                        $actionLogger->save(
                            ActionLogger::MODULE_APARTMENT_GROUPS,
                            $id,
                            ActionLogger::ACTION_APARTMENT_GROUPS_APARTMENT_LIST,
                            'Apartment ' . $currentApartment->getApartmentName() .
                            ' (id: ' . $currentApartment->getApartmentId() . ') removed from group'
                        );
                    } else {
                        unset($newApartmentsList[$key]);
                    }
                }

                // check for adding Apartments to group
                if (!empty($newApartmentsList)) {
                    $apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');

                    foreach ($newApartmentsList as $addedApartmentId) {
                        $apartmentGeneral = $apartmentGeneralService->getApartmentGeneral($addedApartmentId);

                        $actionLogger->save(
                            ActionLogger::MODULE_APARTMENT_GROUPS,
                            $id,
                            ActionLogger::ACTION_APARTMENT_GROUPS_APARTMENT_LIST,
                            'Apartment '
                                .$apartmentGeneral['name']
                                . ' (id: '
                                .$addedApartmentId
                                .') added to group'
                        );
                    }
                }

                $accGroupsManagementDao->save($accGroupData, ['id' => (int)$id]);
            } else {
                $insert_id = $accGroupsManagementDao->save($accGroupData);
            }
            if (isset($data['usage_building']) && $data['usage_building']) {
                if ($buildingDetailsDao->fetchOne(['apartment_group_id' => (int)$insert_id])) {
                    $buildingDetailsDao->save($buildingDetailsData, ['apartment_group_id' => (int)$insert_id]);
                }
                else {
                    $buildingDetailsDao->save(array_merge($buildingDetailsData, ['apartment_group_id' => (int)$insert_id]));
                }
            }

            $accGroupsAccommodationDao = $this->getConciergeAccommodation();
            $accGroupsAccommodationDao->deleteWhere(['apartment_group_id' => (int)$insert_id]);
            $buildingFacilityItemsDao = $this->getServiceLocator()->get('dao_building_facility_items');

            if (isset($data['usage_building']) && $data['usage_building'] == 0) {
                if ($accommodationsDao->fetchOne(['building_id' => (int)$insert_id])) {
                    $accommodationsDao->save(['building_id' => 0], ['building_id' => (int)$insert_id]);
                }

                $buildingFacilityItemsDao->deleteWhere(['building_id' => $insert_id]);
            }

            if (isset($data['usage_building']) && $data['usage_building'] == 1) {
                $buildingFacilityItemsDao->deleteWhere(['building_id' => $insert_id]);

                if (isset($data['facilities'])) {
                    foreach ($data['facilities'] as $facilityId => $isSet) {
                        if ($isSet) {
                            $buildingFacilityItemsDao->save([
                                'facility_id' => $facilityId,
                                'building_id' => $insert_id
                            ]);
                        }
                    }
                }
            }

            if (isset($data['accommodations']) && !empty($data['accommodations'])) {
                foreach ($data['accommodations'] as $row) {
                    $accGroupsAccommodationDao->save([
                        'apartment_group_id' => (int)$insert_id,
                        'apartment_id' => (int)$row,
                    ]);
                }

                //update all accommmodations for this group and set building_id to this group id
                if (isset($data['usage_building']) && $data['usage_building']) {
                    $where = new Where();
                    $where->in('id', $data['accommodations']);
                    $accommodationsDao->update([
                        'building_id' => (int)$insert_id,
                    ], $where);
                }
            }
        } else {
            if ($id > 0) {
                $accGroupsManagementDao->setEntity(new \DDD\Domain\ApartmentGroup\ApartmentGroup());
            }
        }

        return $insert_id;
    }

    public function getConciergeView($accList, $timezone)
    {
        /**
         * @var ConciergeView $conciergeViewDao
         * @var Encrypt $encryptService
         * @var \DDD\Service\Fraud $fraudService
         */

        $fraudService = $this->getServiceLocator()->get('service_fraud');
        $encryptService = $this->getServiceLocator()->get('service_encrypt');
        $conciergeViewDao = $this->getConciergeViewDao();
        $datetime = new \DateTime('now');
        $datetime->setTimezone(new \DateTimeZone($timezone));

        // today
        $dateToday = $datetime->format('Y-m-d');

        // timestamp
        $strtotime = $datetime->format('D M j, g:i a');

        // yesterday
        $datetime->modify('-1 day');
        $dateYesterday = $datetime->format('Y-m-d');

        // tomorrow
        $datetime->modify('+2 day');
        $dateTomorrow = $datetime->format('Y-m-d');

        $conciergeObj = [];
        $accList[]    = 0;

        $conciergeObj['currentStays'] = $conciergeViewDao->getCurrentStays($accList, $dateToday);
        $arrivalsYesterday = $conciergeViewDao->getArrivalsByDay($accList, $dateYesterday);
        $conciergeObj['arrivalsYesterday'] = [];
        foreach ($arrivalsYesterday as $row) {
            if ($row->getFirstDigits()) {
                $decrypted = $encryptService->decrypt($row->getFirstDigits(), $row->getSalt());
                $row->setFirstDigits($decrypted);
            }
            $row->setFraudScore($fraudService->getFraudForReservation($row->getId())['value']);
            $conciergeObj['arrivalsYesterday'][] = $row;
        }

        $arrivalsToday = $conciergeViewDao->getArrivalsByDay($accList, $dateToday);
        $conciergeObj['arrivalsToday'] = [];
        foreach ($arrivalsToday as $row) {
            if ($row->getFirstDigits()) {
                $decrypted = $encryptService->decrypt($row->getFirstDigits(), $row->getSalt());
                $row->setFirstDigits($decrypted);
            }
            $row->setFraudScore($fraudService->getFraudForReservation($row->getId())['value']);
            $conciergeObj['arrivalsToday'][] = $row;
        }

        $arrivalsTomorrow = $conciergeViewDao->getArrivalsByDay($accList, $dateTomorrow);
        $conciergeObj['arrivalsTomorrow'] = [];
        foreach ($arrivalsTomorrow as $row) {
            if ($row->getFirstDigits()) {
                $decrypted = $encryptService->decrypt($row->getFirstDigits(), $row->getSalt());
                $row->setFirstDigits($decrypted);
            }
            $row->setFraudScore($fraudService->getFraudForReservation($row->getId())['value']);
            $conciergeObj['arrivalsTomorrow'][] = $row;
        }

        $conciergeObj['checkoutsYesterday'] = [];
        $checkoutsYesterday = $conciergeViewDao->getCheckoutByDay($accList, $dateYesterday);
        foreach ($checkoutsYesterday as $row) {
            $row->setFraudScore($fraudService->getFraudForReservation($row->getId())['value']);
            $conciergeObj['checkoutsYesterday'][] = $row;
        }

        $conciergeObj['checkoutsToday'] = [];
        $checkoutsToday = $conciergeViewDao->getCheckoutByDay($accList, $dateToday);
        foreach ($checkoutsToday as $row) {
            $row->setFraudScore($fraudService->getFraudForReservation($row->getId())['value']);
            $conciergeObj['checkoutsToday'][] = $row;
        }

        $conciergeObj['checkoutsTomorrow'] = [];
        $checkoutsTomorrow = $conciergeViewDao->getCheckoutByDay($accList, $dateTomorrow);
        foreach ($checkoutsTomorrow as $row) {
            $row->setFraudScore($fraudService->getFraudForReservation($row->getId())['value']);
            $conciergeObj['checkoutsTomorrow'][] = $row;
        }

        $conciergeObj['dateInTimezone']     = $strtotime;

        return $conciergeObj;
    }

    public function getAccommodationByUser($user_id)
    {
        $conciergeAccommodation = $this->getConciergeAccommodation();
        $accommodations = $conciergeAccommodation->getAccommodationByUser($user_id);
        $accommodationsArray = [];

        foreach ($accommodations as $row){
            $accommodationsArray[] = $row->getApartmentId();
        }

        return $accommodationsArray;
    }

    public function getConciergeByGroupId($group_id)
    {
        $results = $this->getConcierge();

        return $results->fetchOne(['id' => $group_id]);
    }

    public function getConciergeByUserId($user_id)
    {
        $results = $this->getConcierge();

        return $results->fetchOne(['user_id' => $user_id]);
    }

    public function getGroupListByUserId()
    {
        $dao = $this->getConcierge();
        $result = $dao->getConciergeDashboards();

        return $result;
    }

    public function checkGroupForUser($id, $userId)
    {
        if (!$userId) {
            return true;
        }

        $dao = $this->getConciergeGroup();
        $result = $dao->fetchOne(['user_id' => $userId, 'apartment_group_id' => $id]);

        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * @access public
     * @return \ArrayObject
     */
    public function getBuildingsListForSelect($search = false, $countryId = false)
    {
        $apartmentGroupDao = $this->getConcierge();
        $buildings = $apartmentGroupDao->getBuildingsListForSelect($search, $countryId);

        return $buildings;
    }

    /**
     * @access public
     * @return \ArrayObject
     */
    public function getApartmentGroupsListForSelect()
    {
        $apartmentGroupDao = $this->getConcierge();
        $apartmentGroups = $apartmentGroupDao->getApartmentGroupsListForSelect();

        return $apartmentGroups;
    }


    public function getBuildingsByAutocomplate(
        $query,
        $isBuilding     = true,
        $isActive       = false,
        $object         = false,
        $isApartel      = false,
        $hasDevTestRole = true
    ) {
        $apartmentGroupDao = $this->getConcierge();

        $buildings = $apartmentGroupDao->getBuildingsByAutocomplate(
            $query,
            $isBuilding,
            $isActive,
            $object,
            $isApartel,
            $hasDevTestRole
        );

        $autocompleteArray = ['rc' => '00', 'result'=> []];
        $data = [];

        if ($object) {
            foreach ($buildings as $key => $item) {
                $data[$key]['id']   = $item->getId();
                $data[$key]['name'] = $item->getNameWithApartelUsage();
            }
        } else {
        	foreach ($buildings as $key => $item) {
        		$data[$key]['id'] = $item['id'];
        		$data[$key]['name'] = $item['name'];
        	}
        }

    	$autocompleteArray['result'] = $data;

        return $autocompleteArray;
    }

    public function getApartmentGroupsForOmnibox($query, $limit, $hasDevTestRole) {
        $apartmentGroupDao = $this->getConcierge();

        $apartmentGroups = $apartmentGroupDao->getApartmentGroupsForOmnibox($query, $limit, $hasDevTestRole);

        $data = [];

        if ($apartmentGroups->count()) {
            foreach ($apartmentGroups as $key => $item) {
                $data[$key]['id']   = $item->getId();
                $data[$key]['name'] = $item->getNameWithApartelUsage();
            }
        }

        return $data;
    }

    public function getAccommodation()
    {
        if ($this->_accommodation === null) {
            $this->_accommodation = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        }

        return $this->_accommodation;
    }

    /**
     * @return \DDD\Dao\ApartmentGroup\ApartmentGroup
     */
    public function getConcierge()
    {
        return $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
    }

    public function getConciergeAccommodation()
    {
        if ($this->_conciergeaccommodation === null) {
            $this->_conciergeaccommodation = $this->getServiceLocator()->get('dao_apartment_group_apartment_group_items');
        }

        return $this->_conciergeaccommodation;
    }

    public function getAccommodationNotInBuilding($accommodations, $building_id)
    {
        $accommodationsDao = $this->getAccommodationsDao();
        $missmatched_accommodations = $accommodationsDao->getAccommodationNotInBuilding($accommodations, $building_id);

        return $missmatched_accommodations;
    }

    public function getAccommodationsDao()
    {
        return $this->getServiceLocator()->get('dao_accommodation_accommodations');
    }

    public function getConciergeViewDao()
    {
        if ($this->_conciergeView === null) {
            $this->_conciergeView = $this->getServiceLocator()->get('dao_apartment_group_concierge_view');
        }

        return $this->_conciergeView;
    }

    public function getConciergeGroup()
    {
        if ($this->_conciergeGroup === null) {
            $this->_conciergeGroup = $this->getServiceLocator()->get('dao_apartment_group_concierge_dashboard_access');
        }

        return $this->_conciergeGroup;
    }

    public function getUser()
    {
        if ($this->_user === null) {
            $this->_user = $this->getServiceLocator()->get('dao_user_user_manager');
        }

        return $this->_user;
    }

    public function getBooking($domain)
    {
        if ($this->_booking === null) {
            $this->_booking = new \DDD\Dao\Booking\Booking($this->getServiceLocator(), $domain);
        }

        return $this->_booking;
    }



    public function getBuildingName($apartmentId) {
        try {
            $apartmentGroupsDao = new \DDD\Dao\ApartmentGroup\ApartmentGroup($this->getServiceLocator(), '\ArrayObject');
            $result = $apartmentGroupsDao->getBuildingNameByApartmentId($apartmentId);

            if ($result) {
                return $result['name'];
            }
        } catch (\Exception $ex) {
            // do nothing, return empty string
        }

        return '';
    }

    /**
     *
     * @param int $groupId
     * @return \ArrayObject|\ArrayObject[]
     */
    public function getApartmentGroupLogs($groupId)
    {
        /**
         * @var \DDD\Dao\ActionLogs\ActionLogs $actionLogsDao
         */
        $actionLogsDao = $this->getServiceLocator()->get('dao_action_logs_action_logs');

        return $actionLogsDao->getByApartmentGroupId($groupId);
    }

    public function getBuildingsByCountry($countries)
    {
        $accGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $accGroupNames = [];
        try {
            if (isset($countries[0])) {
                foreach ($countries as $country) {
                    $result = $accGroupDao->fetchAll(
                        [
                            'country_id' => $country['id'],
                            'active'     => 1
                        ]
                    );
                    foreach ($result as $row) {
                        $accGroupNames[] = [
                            'category' => $country['name'],
                            'id'       => $row->getId(),
                            'name'     => $row->getNameWithApartelUsage()
                        ];
                    }
                }
            }
            return $accGroupNames;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $apartmentGroupId
     * @return array|\ArrayObject|null
     */
    public function getContactPhone($apartmentGroupId)
    {
        /**
         * @var ApartmentGroupDAO $apartmentGroupDao
         */
        $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

        return $apartmentGroupDao->getContactPhone($apartmentGroupId);
    }

    /**
     * @param int $groupId
     * @return array
     */
    public function getApartmentsForExpenseByGroupId($groupId)
    {
        /**
         * @var ApartmentGroupDAO $groupDao
         */
        $groupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $apartments = $groupDao->getApartmentsWithCurrencyByGroupId($groupId);
        $apartmentList = [];

        if ($apartments->count()) {
            foreach ($apartments as $apartment) {
                array_push($apartmentList, [
                    'id' => $apartment['id'],
                    'currencyId' => $apartment['currency_id'],
                    'type' => 'apartment', // 1 as an apartment. It's a conventional value. Wanna change - sync with js file
                ]);
            }
        }

        return $apartmentList;
    }

    /**
     * @param int $managerId
     * @return int
     */
    public function getManagerGroupCount($managerId)
    {
        /**
         * @var ApartmentGroupDAO $apartmentGroupDao
         */
        $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

        return $apartmentGroupDao->getManagerGroupCount($managerId);
    }
}
