<?php

namespace DDD\Service;

use DDD\Dao\Finance\Transaction\TransactionAccounts;
use DDD\Dao\User\Dashboards as UserDashboardsDAO;
use DDD\Dao\User\UserGroup as UserGroupDAO;
use DDD\Dao\User\UserGroup;
use DDD\Dao\User\UserGroups;
use DDD\Dao\User\UserManager;
use DDD\Dao\User\Vacationdays;

use DDD\Service\Team\Team as TeamService;
use DDD\Service\User\Main as UserMainService;
use DDD\Service\User\Permissions;

use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Finance\Base\Account;
use Library\Utility\DateLocal;
use Library\Utility\Helper;

use Library\Constants\Roles;
use Library\Constants\DbTables;
use Library\Constants\DomainConstants;
use Library\Constants\Constants;
use Library\Controller\ControllerBase;

use Zend\Db\Sql\Where;
use Zend\Form\Form;

class User extends ServiceBase
{
    protected $serviceLocator;
    protected $_vacationdays   = null;
    protected $_vacationReq    = null;
    protected $_userManagerDao = null;

    /**
     * @var \DDD\Dao\User\Users $_userDao
     */
    protected $_userDao = null;

    /**
     * @var \DDD\Dao\User\UserGroups $_userGroupsDao
     */
    protected $_userGroupsDao = null;

    const DASHBOARD_KI_NOT_VIEWED                      = 2;
    const DASHBOARD_NO_COLLECTION                      = 3;
    const DASHBOARD_PAY_TO_PARTNER                     = 5;
    const DASHBOARD_MARK_AS_SETTLED                    = 6;
    const DASHBOARD_COLLECT_FROM_PARTNER               = 7;
    const DASHBOARD_VALIDATE_CC                        = 8;
    const DASHBOARD_CASH_PAYMENTS                      = 9;
    const DASHBOARD_HK_COMMENTS                        = 11;
    const DASHBOARD_NEW_REVIEWS                        = 12;
    const DASHBOARD_AWAITING_TRANSFERS                 = 13;
    const DASHBOARD_APPROVED_UNPAID_EXPENSES           = 14;
    const DASHBOARD_AWAITING_PAYMENT_DETAILS           = 15;
    const DASHBOARD_LAST_MINUTE_BOOKINGS               = 16;
    const DASHBOARD_PAYABLE_EXPENSES                   = 17;
    const DASHBOARD_PENDING_CANCELLATION                = 18;
    const DASHBOARD_APPROVED_VACATIONS                 = 20;
    const DASHBOARD_APARTMENTS_IN_REGISTRATION_PROCESS = 21;
    const DASHBOARD_OTA_CONNECTION_ISSUES              = 22;
    const DASHBOARD_FRONTIER_CHARGE_REVIEWED           = 23;
    const DASHBOARD_RESERVATION_ISSUES                 = 24;
    const DASHBOARD_SUSPENDED_APARTMENTS               = 25;
    const DASHBOARD_COLLECT_FROM_CUSTOMER              = 26;
    const DASHBOARD_PAY_TO_CUSTOMER                    = 27;
    const DASHBOARD_TRANSACTION_PENDING                = 30;
    const DASHBOARD_CHARGE_APARTEL_RESERVATIONS        = 31;
    const DASHBOARD_OVERBOOKING_RESERVATIONS           = 34;
    const DASHBOARD_PO_READY_TO_BE_SETTLED             = 37;
    const DASHBOARD_NEW_APPLICANTS                     = 38;
    const DASHBOARD_UNRESOLVED_EVALUATIONS             = 39;
    const DASHBOARD_PENDING_BUDGET                     = 40;
    const DASHBOARD_PENDING_ASSETS                     = 41;
    const DASHBOARD_PO_AWAITING_APPROVAL               = 42;
    const DASHBOARD_ITEMS_TO_BE_ORDERED                = 43;
    const DASHBOARD_ITEMS_TO_BE_DELIVERED              = 44;
    const DASHBOARD_ORDERS_TO_REFUNDED                 = 45;
    const DASHBOARD_UNPAID_INVOICES                    = 46;
    const DASHBOARD_ORDERS_TO_BE_SHIPPED               = 47;
    const DASHBOARD_NEW_ASSET_CATEGORIES               = 48;

    const PERMISSION_MODULE   = 1;
    const PERMISSION_ROLE     = 2;
    const PERMISSION_FUNCTION = 3;

    const USER_DISABLED       = 1;

    const USER_TYPE_SYSTEM    = 1;

    const ANY_TEAM_MEMBER_USER_ID = 339;
    const AUTO_VERIFY_USER_ID     = 340;
    const SYSTEM_USER_ID          = 277;
    const USER_GUEST              = 292;

    const UNLIMITED_SICK_DAYS = -1;

    public function getUsersGroup($user_id = 0, $group_id = false)
    {
        $dao    = $this->getUserDao();
        $result = $dao->getUsersGroup($user_id, $group_id);

        return $result;
    }

	/**
     * @todo Duplicate method (User Profile Service > getUserSubordinates())
     *
	 * @param $userId
	 * @return \ArrayObject
	 */
	public function getUserManagees($userId)
    {
		$dao = $this->getUserManagerDao('\ArrayObject');

		return $dao->getUsersByManagerId($userId);
	}

	/**
     * @param int $userId
     * @param bool $active
     * @param bool $withoutExternalUsers
	 * @return \DDD\Domain\User\User[]|\ArrayObject
	 */
	public function getPeopleList($userId = null, $active = true, $withoutExternalUsers = true, $countryId = false)
    {
		$dao    = $this->getUserManagerDao('\ArrayObject');
		$result = $dao->getPeopleList($userId, $active, $withoutExternalUsers, $countryId);

		return $result;
	}

    /**
     * @param int $userId
     * @param bool $active
     * @param bool $withoutExternalUsers
     * @return \DDD\Domain\User\User[]|\ArrayObject
     */
    public function getPeopleListById($userId = null, $active = true, $withoutExternalUsers = true, $countryId = false)
    {
        $dao    = $this->getUserManagerDao('\ArrayObject');
        $result = $dao->getPeopleList($userId, $active, $withoutExternalUsers, $countryId);

        $usersById = [];

        foreach ($result as $user) {
            $usersById[$user['id']] = $user;
        }

        return $usersById;
    }

    /**
     * Get Cities by id
     *
     * @return array
     */
    public function getUserCountriesById()
    {
        /**
         * @var \DDD\Dao\User\UserManager $userManagerDao
         */
        $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

        $countries     = $userManagerDao->getUsersCountries();
        $countriesById = [];

        foreach ($countries as $country) {
            $countriesById[$country->getId()] = $country;
        }

        return $countriesById;
    }

    /**
     * @param int $userId
     * @param bool $active
     * @return array|array[]
     */
    public function getPeopleListAsArray($userId = null, $active = true)
    {
        $list = $this->getPeopleList($userId, $active);
        $users = [];

        if ($list->count()) {
            foreach ($list as $user) {
                $users[$user['id']] = $user['firstname'] . ' ' . $user['lastname'];
            }
        }

        return $users;
    }

    /**
     * @param int $userId
     * @param bool $active
     * @return array|array[]
     */
    public function getPeopleListWithManagersAsArray($userId = null, $active = true)
    {
        $list = $this->getPeopleList($userId, $active);
        $users = [];

        if ($list->count()) {
            foreach ($list as $user) {
                $users[$user['id']] = [
                    'manager_id' => $user['manager_id'],
                    'name' => $user['firstname'] . ' ' . $user['lastname'],
                ];
            }
        }

        return $users;
    }

    /**
     * @param int|bool $userId
     * @return array
     */
    public function getPeopleListWithManagersForSelect($userId = false)
    {
        $users = $this->getPeopleWithBudgetHolderManagers($userId);
        $userList = [];

        if (count($users)) {
            foreach ($users as $user) {
                array_push($userList, [
                    'attributes' => [
                        'data-data' => json_encode([
                            'value' => $user['id'],
                            'text' => $user['name'],
                            'data-manager-id' => $user['budget_holder_manager_id']
                        ]),
                    ],
                    'value' => $user['id'],
                    'label' => $user['name'],
                ]);
            }
        }

        return $userList;
    }

    /**
     * @attention RECURSION
     *
     * @param int $userId
     * @param array $users
     * @param array $budgetHolders
     *
     * @return int
     */
    public function getBudgetHolderUserManagerId($userId, $users = [], $budgetHolders = [])
    {
        if (empty($users) || empty($budgetHolders)) {
            $users = $this->getPeopleListWithManagersAsArray(null, false);
            $budgetHolders = $this->getBudgetHolderList();
        }

        // Impossible situation
        if (!count($budgetHolders) || !count($users)) {
            return $userId;
        }

        // Prevent loop
        if (isset($users[$userId]['manager_id']) && $userId == $users[$userId]['manager_id']) {
            return $userId;
        }

        // If someone hasn't a manager
        if (!array_key_exists($userId, $users)) {
            return $userId;
        }

        $managerId = $users[$userId]['manager_id'];

        if (array_key_exists($managerId, $budgetHolders)) {
            return $managerId;
        } else {
            return $this->getBudgetHolderUserManagerId($managerId, $users, $budgetHolders);
        }
    }

    /**
     * @param null|int $selectedPeopleId
     * @return array
     */
    public function getPeopleWithBudgetHolderManagers($selectedPeopleId = null)
    {
        $dao = $this->getUserManagerDao(new \ArrayObject());
        $peopleList = iterator_to_array($dao->getExtendedPeopleList($selectedPeopleId));
        $users = $this->getPeopleListWithManagersAsArray(null, false);
        $budgetHolders = $this->getBudgetHolderList();
        $newList = [];

        if (count($peopleList)) {
            foreach ($peopleList as $people) {
                array_push($newList, [
                    'id' => $people['id'],
                    'name' => $people['name'],
                    'manager_id' => $people['manager_id'],
                    'budget_holder_manager_id' => $this->getBudgetHolderUserManagerId($people['id'], $users, $budgetHolders),
                ]);
            }
        }

        return $newList;
    }

    /**
     * @param int|null $peopleId
     * @return array
     */
    public function getBudgetHolderList($peopleId = null)
    {
        $dao = $this->getUserManagerDao(new \ArrayObject());
        $peopleDomainList = $dao->getForTicketManager($peopleId);
        $peopleList = [];

        if ($peopleDomainList->count()) {
            foreach ($peopleDomainList as $peopleDomain) {
                $peopleList[$peopleDomain['id']] = $peopleDomain['firstname'] . ' ' . $peopleDomain['lastname'];
            }
        }

        return $peopleList;
    }

    /**
     * @param bool $withOutExternalUsers
     * @return array
     */
    public function getAllActiveUsersArray($withOutExternalUsers = true, $selectedId = 0)
    {
        $dao    = $this->getUserManagerDao();
        $result = $dao->getAllActiveUsersArray($withOutExternalUsers, $selectedId);

        return $result;
    }

	/**
     * @param bool $withOutSystemUsers
     * @param bool $all
     * @param bool $withOutExternalUsers
	 * @return \DDD\Domain\User\User[]|\ArrayObject
	 */
	public function getUsersList($withOutSystemUsers = false, $all = false, $withOutExternalUsers = true)
    {
        $dao = $this->getUserManagerDao();

        $where = new Where();

        if ($withOutSystemUsers) {
            $where->equalTo('system', '0');
        }

        if ($withOutExternalUsers) {
            $where->equalTo('external', '0');
        }

        if (!$all) {
            $where->equalTo('disabled', '0');
        }

        $result = $dao->fetchAll($where);

        return $result;
    }

    /**
     * Returns active people id list
     * @return array
     */
    public function getActivePeopleIdList()
    {
        $userDao = $this->getUserManagerDao(new \ArrayObject());

        $entity = $userDao->getEntity();
        $userDao->setEntity(new \ArrayObject());

        $users = $userDao->getForSelect();
        $userDao->setEntity($entity);
        $userIdList = [];

        if ($users->count()) {
            foreach ($users as $user) {
                array_push($userIdList, $user['id']);
            }
        }

        return $userIdList;
    }

    public function getUsersBasicInfo($iDisplayStart, $iDisplayLength, $filterParams = [], $sortCol, $sortDir)
    {
    	$dao = $this->getUserManagerDao('DDD\Domain\User\UserTableRow');
    	$where = $this->constructWhereFromFilterParams($filterParams);
    	$users = $dao->getUsersBasicInfo($iDisplayStart, $iDisplayLength, $sortCol, $sortDir, $where);

    	return $users;
    }

    public function getCount($filterParams)
    {
    	$dao = $this->getUserManagerDao('DDD\Domain\Count');

    	$where = $this->constructWhereFromFilterParams($filterParams);
    	$count = $dao->getCount($where);

    	return $count;
    }

    public function constructWhereFromFilterParams($filterParams)
    {
	    $where = new Where();

	    $table = DbTables::TBL_BACKOFFICE_USERS;

	    if( isset($filterParams["group"]) && $filterParams["group"] != '0' ) {
		    $where->expression( $filterParams["group"] . ' IN (SELECT `group_id` FROM ' . DbTables::TBL_BACKOFFICE_USER_GROUPS . ' WHERE `user_id` = ' . $table . '.id ) ', []);
	    }

        if( isset($filterParams["ud"]) && $filterParams["ud"] != '0' ) {
            $where->expression( $filterParams["ud"] . ' IN (SELECT `dashboard_id` FROM ' . DbTables::TBL_BACKOFFICE_USER_DASHBOARDS . ' WHERE `user_id` = ' . $table . '.id ) ', []);
        }

        if( isset($filterParams["team"]) && $filterParams["team"] != '0' ) {
            $where->expression( $filterParams["team"]  . ' IN (SELECT `team_id` FROM ' . DbTables::TBL_TEAM_STAFF . ' WHERE `user_id` = ' . $table . '.id AND `type` NOT IN (' . TeamService::STAFF_CREATOR . ', ' . TeamService::STAFF_DIRECTOR . ')) ', []);
        }

        if (isset($filterParams['city']) && $filterParams['city'] > 0) {
            $where->EqualTo('city_id', $filterParams['city']);
        }

        if (isset($filterParams["system-user-status"])) {
            if ($filterParams["system-user-status"] == 1) {
                $where->and->EqualTo('system', 0);
            } elseif ($filterParams["system-user-status"] == 2) {
                $where->and->EqualTo('system', 1);
            }

        }

        if (isset($filterParams["external-user-status"])) {
            if ($filterParams["external-user-status"] == 1) {
                $where->and->EqualTo('external', 0);
            } elseif ($filterParams["external-user-status"] == 2) {
                $where->and->EqualTo('external', 1);
            }

        }

        if (isset($filterParams["active-user-status"])) {
            if ($filterParams["active-user-status"] == 1) {
                $where->and->EqualTo('disabled', 0);
            } elseif ($filterParams["active-user-status"] == 2) {
                $where->and->EqualTo('disabled', 1);
            }
        }

	    if (isset($filterParams["sSearch"]) && $filterParams["sSearch"] != '') {
		    $nestedWhere = new \Zend\Db\Sql\Predicate\Predicate();
		    $nestedWhere->like(
                $table.'.firstname', '%' . $filterParams["sSearch"] . '%'
            );

		    $nestedWhere->OR;
		    $nestedWhere->like(
                $table.'.lastname', '%' . $filterParams["sSearch"] . '%'
            );

		    $nestedWhere->OR;
		    $nestedWhere->like(
                $table.'.position', '%' . $filterParams["sSearch"] . '%'
            );

            $nestedWhere->OR;
            $nestedWhere->like(
                'details'.'.name', '%' . $filterParams["sSearch"] . '%'
            );

            $nestedWhere->OR;
            $nestedWhere->like(
                'teams'.'.name', '%' . $filterParams["sSearch"] . '%'
            );

		    $where->addPredicate($nestedWhere);
	    }

	    return $where;
    }

    public function getUsersById($backofficeUserId, $isManager = false)
    {
        /**
         * @var UserMainService $userMainService
         * @var \DDD\Service\User\Schedule $scheduleService
         */
        $conciergeDashboardAccessDao = $this->getServiceLocator()->get('dao_apartment_group_concierge_dashboard_access');
        $udWidgetAccessDao = $this->getServiceLocator()->get('dao_user_user_dashboards');
        $userMainService = $this->getServiceLocator()->get('service_user_main');
        $permissionDao = $this->getServiceLocator()->get('dao_user_user_groups');
        $officeService = $this->getServiceLocator()->get('service_office');
        $scheduleService = $this->getServiceLocator()->get('service_user_schedule');

        $backofficeUserId = intval($backofficeUserId);

        $dao = $this->getUserManagerDao();

        $userDataWithCurrency = true;

        if (!$user_main = $dao->getUserById($backofficeUserId, $isManager, [], $userDataWithCurrency)) {
            return false;
        }

        $scheduleData  = $scheduleService->getUserSchedule($backofficeUserId);
        $permissions = $permissionDao->getUserGroupList($backofficeUserId);
        $udWidgets = $udWidgetAccessDao->getUserDashboardList($backofficeUserId);
        $conciergeDashboardAccess = $conciergeDashboardAccessDao->getUserConciergeGroupsList($backofficeUserId);

        $this->registry->set('user_main', $user_main);
        $this->registry->set('user_groups', $permissions);
        $this->registry->set('user_dashboards', $udWidgets);
        $this->registry->set('user_conciergegroups', $conciergeDashboardAccess);
        $this->registry->set('schedule_data', $scheduleData);

        $officesInfo = [];
        $officeLists = $officeService->getOfficeList();

        foreach ($officeLists as $office) {
            $officesInfo[$office->getId()] = $office->getName();
        }

        $userOffice = null;
        $userOfficeId = $user_main->getReportingOfficeId();

        if (!array_key_exists($userOfficeId, $officesInfo)) {
            $disabledOffice = $officeService->getOfficeList($userOfficeId);

            if ($disabledOffice) {
                $userOffice[$disabledOffice->getId()] = $disabledOffice->getName();
                $this->registry->set('userOffice', $userOffice);
            }
        }

        $loggedInUserDepartmentId = $userMainService->getUserDepartmentId($backofficeUserId);
        $this->registry->set('userDepId', $loggedInUserDepartmentId);

        return $this->registry;
    }

    /**
     * @param $userId
     * @return mixed
     */
    public function getManagerIdByUserId($userId)
    {
        $dao = $this->getUserManagerDao();
        $userData = $dao->getManagerId($userId);

        return $userData->getManager_id();
    }

    public function getUserOptions($id)
    {
        /**
         * @var \DDD\Dao\User\Dashboards $dashboardsDao
         */
        $teamService     = $this->getServiceLocator()->get('service_team_team');

        $dashboardsDao = $this->getServiceLocator()->get('dao_user_dashboards');
        $dashboards = $dashboardsDao->getDashboardsList();

        $apartmentGroupDAO   = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

        $concierges      = $apartmentGroupDAO->getConciergeDashboards($id);
        $managerId = false;
        if($id > 0){
            $checkManagerDisabled = $this->getServiceLocator()->get('dao_user_user_manager')->checkManagerDisabled($id);
            if($checkManagerDisabled && $checkManagerDisabled->getDisabled() == 1)
                $managerId = $checkManagerDisabled->getId();
        }

        $officeService = $this
            ->getServiceLocator()
            ->get('service_office');

        $officesInfo = [];
        $officeLists = $officeService->getOfficeList();

        foreach ($officeLists as $office) {
            $officesInfo[$office->getId()] = $office->getName();
        }

        $managers        = $this->getUserManagerDao()->getPeopleList($managerId);
        $dao_countries   = $this->getServiceLocator()->get('dao_geolocation_countries');
        $countries       = $dao_countries->getCountriesListWithCities();
        $dao_city        = $this->getServiceLocator()->get('dao_geolocation_city');
        $cities          = $dao_city->getCitiesForSelect();
        $isDepartment = 1;
        $departments = $teamService->getTeamList(null, $isDepartment);

        $coDepartments = [];
        $coDepartments[-1] = '-- Choose Department --';

        foreach ($departments as $department) {
            $coDepartments[$department->getId()] = $department->getName();
        }

        $this->registry->set('office', $officesInfo);
        $this->registry->set('countries', $countries);
        $this->registry->set('cities', $cities);
        $this->registry->set('managers', $managers);
        $this->registry->set('dashboards', $dashboards);
        $this->registry->set('concierges', $concierges);
        $this->registry->set('departments', $coDepartments);

        return $this->registry;
    }

    public function getCityByCountryId($id) {
        $cityDao = $this->getServiceLocator()->get('dao_geolocation_city');
        $country = $cityDao->getCityByCountryId($id);

        return $country;
    }

    public function checkEmail($email, $id) {
	    /** @var UserManager $usermanagerDao */
	    $usermanagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

        return $usermanagerDao->checkEmail($email, $id);
    }

    public function checkGinosikEmail($id, $email) {
        /** @var UserManager $usermanagerDao */
        $usermanagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

        return $usermanagerDao->checkGinosikEmail($id, $email);
    }

    public function userSave($data, $id)
    {
        /**
         * @var \DDD\Dao\User\UserDashboards $userDashboardsDao
         * @var TransactionAccounts $transactionAccountDao
         * @var \DDD\Dao\User\UserGroups $usergroups_dao
         * @var \DDD\Dao\Team\TeamStaff $teamStaffDao
         * @var \DDD\Dao\User\UserManager $userDao
         */
        $userDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');

        $usergroups_dao              = $this->getServiceLocator()->get('dao_user_user_groups');
        $conciergeDashboardAccessDao = $this->getServiceLocator()->get('dao_apartment_group_concierge_dashboard_access');
        $userDashboardsDao           = $this->getServiceLocator()->get('dao_user_user_dashboards');
        $teamStaffDao                = $this->getServiceLocator()->get('dao_team_team_staff');

        $data = (array)$data;
        $user_main_data = array(
            'manager_id'             => $data['manager'],
            'firstname'              => $data['firstname'],
            'lastname'               => $data['lastname'],
            'country_id'             => (isset($data['country']) && $data['country'] > 0) ? $data['country'] : null,
            'city_id'                => (isset($data['city'])) ? $data['city'] : '',
            'living_city'            => (isset($data['living_city'])) ? $data['living_city'] : '',
            'start_date'             => ($data['startdate']) ? date('Y-m-d', strtotime($data['startdate'])) : '',
            'end_date'               => ($data['end_date']) ? date('Y-m-d', strtotime($data['end_date'])) : '',
            'birthday'               => ($data['birthday'] ? date('Y-m-d', strtotime($data['birthday'])) : ''),
            'vacation_days_per_year' => $data['vacation_days_per_year'],
            'personal_phone'         => $data['personalphone'],
            'business_phone'         => $data['businessphone'],
            'emergency_phone'        => $data['emergencyphone'],
            'house_phone'            => $data['housephone'],
            'address_permanent'      => $data['address_permanent'],
            'address_residence'      => $data['address_residence'],
            'timezone'               => $data['timezone'],
            'position'               => $data['position'],
            'email'                  => $data['email'],
            'internal_number'        => $data['internal_number'],
            'alt_email'              => $data['alt_email'],
            'period_of_evaluation'   => $data['period_of_evaluation'],
            'reporting_office_id'    => $data['reporting_office_id'],
            'department_id'          => $data['department'],
            'asana_id'               => $data['asana_id'],
            'sick_days'              => $data['sick_days'],
            'external'               => $data['external'],
        );

        if (isset($data['vacationdays'])) {
            $user_main_data['vacation_days'] = $data['vacationdays'];
        }

        $oauthPass = false;
        if (!empty($data['password'])) {
            $user_main_data['password'] = Helper::bCryptPassword($data['password']);
            $oauthPass = $user_main_data['password'];
        }

        if (isset($data['system'])) {
            $user_main_data['system'] = $data['system'];
        }

        if (isset($data['employment'])) {
            $user_main_data['employment'] = $data['employment'];
        }

        if (!$id) {
            $user_main_data['disabled'] = 0;
        }

        $insertId = $id;

        if ($id > 0) {
            $oldData = $userDao->findUserById($id);
            $this->writeSaveLog($id, $oldData, $user_main_data);
            $userDao->save($user_main_data, ['id' => $id]);

            if (!empty($data['department']) && !$teamStaffDao->isUserInTeam($id, $data['department'])) {
                $teamStaffDao->save([
                    'team_id'     => $data['department'],
                    'user_id'     => $id,
                    'type'      => TeamService::STAFF_MEMBER,
                ]);
            }

            $oauthData = ['username' => $user_main_data['email']];
            if ($oauthPass) {
                $oauthData['password'] = $oauthPass;
            }

            $daoOuathUsers = $this->getServiceLocator()->get('dao_oauth_oauth_users');
            $daoOuathUsers->save($oauthData, ['username' => $oldData->getEmail()]);

        } else {
            // new user
            $insertId = $userDao->save($user_main_data);
            $transactionAccountDao->save([
                'type' => Account::TYPE_PEOPLE,
                'holder_id' => $insertId,
            ]);
            $teamStaffDao->save([
                'team_id'   => $data['department'],
                'user_id'   => $insertId,
                'type'      => TeamService::STAFF_MEMBER,
            ]);

            $daoOuathUsers = $this->getServiceLocator()->get('dao_oauth_oauth_users');
            $daoOuathUsers->save(['username' => $user_main_data['email'], 'password' => $user_main_data['password']]);

        }

        if ($auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT)) {
            // universal dashboard items
            if (isset($data['dashboards'])) {
                $userDashboardsDao->deleteWhere(['user_id' => $insertId]);

                foreach ($data['dashboards'] as $dashboardID) {
                    $dashboard = [
                        'user_id' => $insertId,
                        'dashboard_id'=> (int)$dashboardID,
                    ];

                    $userDashboardsDao->save($dashboard);
                }
            } else {
                $userDashboardsDao->deleteWhere(['user_id' => $insertId]);
            }

            if (isset($data['member_groups']) && !empty($data['member_groups'])) {
                $membersString = $data['member_groups'];
                $members = explode(',', $membersString);

                $usergroups_dao->deleteWhere(['user_id' => $insertId]);

                // get parent module
                $membersNew = $members;

                foreach ($membersNew as $row) {
                    $usergroups = [
                        'user_id' => $insertId,
                        'group_id'=> (int)$row,
                    ];
                    $usergroups_dao->save($usergroups);
                }
            } else {
                $usergroups_dao->deleteWhere(['user_id' => $insertId]);
            }

            // apartment groups
            if (isset($data['conciergegroups'])) {
                $conciergeDashboardAccessDao->deleteWhere(['user_id' => $insertId]);

                foreach ($data['conciergegroups'] as $row) {
                    $conciergeDashboardAccessDao->save([
                        'user_id' => $insertId,
                        'apartment_group_id'=> (int)$row,
                    ]);
                }
            } else {
                $conciergeDashboardAccessDao->deleteWhere(['user_id' => $insertId]);
            }
        }

        return $insertId;
    }


    public function vacationdaysSave($data, $id)
    {
        $vacationdays_dao = $this->getVacationdaysDao();

        return $vacationdays_dao->save([
            'from'         => ($data['from']) ? date('Y-m-d', strtotime($data['from'])) : '',
            'to'           => ($data['to']) ? date('Y-m-d', strtotime($data['to'])) : '',
            'total_number' => $data['total_number'],
            'comment'      => $data['comment'],
            'is_approved'  => 2,
            'user_id'      => $id,
            'type'         => (int)$data['vacation_type']
        ]);
    }

    public function getUserVacationRequest($userId)
    {
        /**
         * @var Vacationdays $vacationdaysDao
         */
        $vacationdaysDao = $this->getVacationdaysDao();
        $old = $vacationdaysDao->getUserVacationRequestOld($userId);
        $new = $vacationdaysDao->getUserVacationRequestNew($userId);

        return ['new' => $new,'old' => $old];
    }

    public function getUserDataNameID($id)
    {
        $dao = $this->getUserManagerDao();
        $data = $dao->fetchOne(['id' => $id]);

        if ($data) {
            return $data->getFirstName() . ' ' . $data->getLastName();
        }

        return 'No User';
    }

    public function getVacationdaysDao()
    {
        if (is_null($this->_vacationdays)) {
            return $this->getServiceLocator()->get('dao_user_vacation_days');
        }

        return $this->_vacationdays;
    }

    public function getVacationReqDao()
    {
        if (is_null($this->_vacationReq)) {
            return $this->getServiceLocator()->get('dao_user_vacation_request');
        }

        return $this->_vacationReq;
    }

    /**
     * @param string $domain
     * @return \DDD\Dao\User\UserManager
     */
    public function getUserManagerDao($domain = 'DDD\Domain\User\User') {
    	return new \DDD\Dao\User\UserManager($this->getServiceLocator(), $domain);
    }

    /**
     * @return \DDD\Dao\User\Users
     */
    public function getUserDao()
    {
    	if (is_null($this->_userDao)) {
    		$this->_userDao = $this->getServiceLocator()->get('dao_user_users');
        }

    	return $this->_userDao;
    }

    /**
     *
     * @return \DDD\Dao\User\UserGroups
     */
    public function getUserGropusDao() {
    	if (is_null($this->_userGroupsDao)) {
            $this->_userGroupsDao = $this->getServiceLocator()->get('dao_user_user_groups');
        }

    	return $this->_userGroupsDao;
    }

    public function getUserProfile($id = null) {
        try {
            $dao = $this->getUserManagerDao();

            if (!$dao->checkRowExist(DbTables::TBL_BACKOFFICE_USERS, 'id', $id)){
                throw new \Exception("Could not find user");
            }

            $userMain   = $dao->getUserById($id);

            $cityDao    = $this->getServiceLocator()->get('dao_geolocation_city');
            $cityId     = $userMain->getCity_id();

            if (isset($cityId) AND $cityId > 0) {
                $country    = $cityDao->getCountryIDByCityID($userMain->getCity_id());
                $userMain->setCountry_id($country->getCountry_id());
            }

            $this->registry->set('userMain', $userMain);

            $usergroups_dao   = $this->getServiceLocator()->get('dao_user_user_groups');
            $usergroups       = $usergroups_dao->fetchAll(array('user_id'=>(int)$id));
            $this->registry->set('user_groups', $usergroups);

            return $this->registry;
        } catch (\Exception $e) {
            throw new \Exception('Could not get user profile');
        }
    }

    public function setPassword($userId, $newPassword)
    {
        $userDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $data['password'] = Helper::bCryptPassword($newPassword);

        $userDao->save($data, ['id' => $userId]);
    }

    public function getUsersJSON($query, $all = false)
    {
        /**
         * @var \DDD\Domain\User\User[]|\ArrayObject $result
         */
        $dao = $this->getUserManagerDao();
        $result = $dao->getUsers($query, $all);

	    return $this->_toArray($result);
    }

    public function getUsersForOmnibox($query, $limit)
    {
        /**
         * @var \DDD\Domain\User\User[]|\ArrayObject $result
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $authUserIdentity = $auth->getIdentity();
        $dao = $this->getUserManagerDao();
        $result = $dao->getUsersForOmnibox($query, $limit);
        $out = [];

        if ($result->count()) {
            foreach ($result as $item) {
                $avatar = $this->getAvatarForSelectize($item->getId(), $item->getAvatar());

                if ($item->getInternalNumber() > 0) {
                    $text = $item->getFirstName() . ' ' . $item->getLastName() . ' (' . $item->getInternalNumber() . ')';
                } else {
                    $text = $item->getFirstName() . ' ' . $item->getLastName();
                }

                array_push($out, [
                    'id'        => $item->getId(),
                    'text'      => $text,
                    'label'     => 'employee',
                    'avatar'    => $avatar,
                    'canManage' => ($auth->hasRole(Roles::ROLE_PEOPLE_DIRECTORY) && (
                        $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) ||
                        $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR) ||
                        $item->getManager_id() == $authUserIdentity->id
                    )),
                ]);
            }
        }

        return $out;
    }

    /**
     * @param \DDD\Domain\User\User[]|\ArrayObject $data
     * @return array
     */
    private function _toArray($data) {
        /**
         * @var BackofficeAuthenticationService $auth
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $authUserIdentity = $auth->getIdentity();
		$out = [];

		if ($data->count()) {
			foreach ($data as $item) {
                $avatar = $this->getAvatarForSelectize($item->getId(), $item->getAvatar());

				array_push($out, [
                    'id' => $item->getId(),
                    'text' => $item->getFirstName() . ' ' . $item->getLastName(),
                    'label' => 'employee',
                    'avatar' => $avatar,
                    'canManage' => (
                        $auth->hasRole(Roles::ROLE_PEOPLE_DIRECTORY) && (
							$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) || $auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR) || $item->getManager_id() == $authUserIdentity->id
						)
					),
                ]);
			}
		}

		return $out;
	}

    public function changeDetails($data)
    {
        $user_main   = $this->getServiceLocator()->get('dao_user_user_manager');
        $data        = (array)$data;

        $user_main->save([
            'personal_phone' => $data['personalphone'],
            'business_phone' => $data['businessphone'],
            'emergency_phone' => $data['emergencyphone'],
            'house_phone' => $data['housephone'],
            'birthday' => date(Constants::DATABASE_DATE_FORMAT, strtotime($data['birthday'])),
            'address_permanent' => $data['address_permanent'],
            'address_residence' => $data['address_residence']
        ], ['id' => $data['userId']]);
    }

    public function changePassword($data)
    {
        $user_main   = $this->getServiceLocator()->get('dao_user_user_manager');
        $data        = (array)$data;

        $user_main_data['password'] = Helper::bCryptPassword($data['password']);

        $user_main->save($user_main_data, ['id' => $data['userId']]);
    }

    /**
     * @return array
     */
    public function prepareSearchFormResources()
    {
        /**
         * @var UserGroupDAO $userGroupsDao
         * @var UserDashboardsDAO $dashboardsDao
         */
        $userGroupsDao = $this->getServiceLocator()->get('dao_user_user_group');
        $dashboardsDao = $this->getServiceLocator()->get('dao_user_dashboards');

        $citiesDao = new UserManager(
            $this->getServiceLocator(),
            '\ArrayObject'
        );

        $teamService = $this->getServiceLocator()->get('service_team_team');

    	$permissions= $userGroupsDao->getGroupsList();
        $dashboards = $dashboardsDao->getDashboardsList();
        $cities     = $citiesDao->getUsersCountries();

        $isDepartment = false;
        $teams = $teamService->getTeamList(null, $isDepartment);

    	return [
    		'user_groups'   => $permissions,
    		'ud_dashboards' => $dashboards,
    		'cities'        => $cities,
            'teams'         => $teams
    	];
    }

    public function getUserGroups($userId)
    {
        /**
         * @var UserGroups $userGroupsDao
         * @var UserGroup  $groupsDao
         */
        $groupsDao = $this->getServiceLocator()->get('dao_user_user_group');
        $permissions= $groupsDao->getGroupsList();
        $userSelectedGroupsArray = [];
        if ($userId) {
            $userGroupsDao = $this->getServiceLocator()->get('dao_user_user_groups');
            $userSelectedGroups = $userGroupsDao->getUserGroupListSimplified($userId);
            foreach ($userSelectedGroups as $userSelectedGroup) {
                array_push($userSelectedGroupsArray, $userSelectedGroup->getGroup_id());
            }
        }
        $permissionsChildrenArray = [];
        $permissionsHierarchichalArray = [];

        foreach ($permissions as $permission) {
            $name = $permission->getName() . ' - ' . Permissions::$permissionTypes[$permission->getType()];

            if ($permission->getParentId() == 0) {
                $permissionsHierarchichalArray[$permission->getId()] = [
                    'id' => $permission->getId(),
                    'name' => $name,
                    'description' => $permission->getDescription(),
                    'parent_id' => $permission->getParentId(),
                    'is_selected' => in_array($permission->getId(), $userSelectedGroupsArray),
                    'children' => []
                ];
            } else {
                array_push($permissionsChildrenArray,
                    [
                        'id' => $permission->getId(),
                        'name' => $name,
                        'description' => $permission->getDescription(),
                        'parent_id' => $permission->getParentId(),
                        'is_selected' => in_array($permission->getId(), $userSelectedGroupsArray),
                    ]
                );
            }

        }

        foreach ($permissionsChildrenArray as $child) {
            $permissionsHierarchichalArray[$child['parent_id']]['children'][$child['id']] = $child;
        }

        return $permissionsHierarchichalArray;
    }

    /**
     * Check dashboard permission for given user
     * @param int $backofficeUserID
     * @param int $dashboardID
     * @return boolean
     */
    public function checkUserDashboardAvailability($backofficeUserID, $dashboardID) {
    	$userDashboardsDao = $this->getServiceLocator()->get('dao_user_user_dashboards');

    	return $userDashboardsDao->getUserDashboards($backofficeUserID, $dashboardID);
    }

    /**
     * @param int $id
     * @return boolean/ArrayObject
     */
    public function getUsersDataForMail($id) {
        if (!$id) {
            return false;
        }

        $dao = $this->getUserManagerDao('ArrayObject');
        $userData = $dao->getUserById($id);

        if (!$userData) {
            return false;
        }

        return $userData;
    }

    /**
     * @param int $length
     * @return string
     */
    public function generatePassword($length = 6) {
        $password = "";
        $possible = "2346789bcdfghjkmnpqrtvwxyzBCDFGHJKLMNPQRTVWXYZ*";
        $maxlength = strlen($possible);
        $i = 0;

        if ($length > $maxlength) {
            $length = $maxlength;
        }

        while ($i < $length) {
            $char = substr($possible, mt_rand(0, $maxlength - 1), 1);

            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }
        return $password;
    }

    public function sendMail($id)
    {
        $output = shell_exec('ginosole user send-login-details --id=' . escapeshellarg($id) . ' -v');

        if (strstr(strtolower($output), 'error')) {
            return false;
        }

        return true;
    }

    public function updateLastLogin($userId, $datetime = NULL)
    {
        $userManagerDao = $this->getUserManagerDao();

        if (is_null($datetime)) {
            $datetime = date('Y-m-d H:i:s');
        }

        return $userManagerDao->update(
            ['last_login' => $datetime],
            ['id' => $userId]
        );
    }

    public function getCloneUser($txt, $editableUserId)
    {
        /**
         * @var UserManager $userManagerDao
         */
        $newList = [];

        if ($txt) {
            $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');
            $userList = $userManagerDao->getUsersWithPermission($txt, $editableUserId);

            foreach ($userList as $key => $row) {
                if (isset($newList[$row['id']])) {
                    $newList[$row['id']]['group'][] = $row['group_id'];
                } else {
                    $newList[$row['id']] = [
                        'group' => [$row['group_id']],
                        'name' => $row['firstname'] . ' ' . $row['lastname']
                    ];
                }
            }
        }

        return $newList;
    }

    public function addDownloadButton($docId, Form $form, ControllerBase $controller)
    {
        $router      = $controller->getEvent()->getRouter();
        $downloadUrl = $router->assemble([
            'controller' => 'user',
            'action'     => 'download-document-attachment',
            'id'         => $docId
        ], ['name' => 'backoffice/default']);
        $removeUrl = $router->assemble([
            'controller' => 'user',
            'action'     => 'removeAttachment',
            'id'         => $docId
        ], ['name' => 'backoffice/default']);

        $form->add(
            [
                'name' => 'download',
                'type' => 'Zend\Form\Element\Button',
                'attributes' => [
                    'value' => $downloadUrl,
                    'id'    => 'download-attachment',
                    'class' =>'btn btn-info btn-large pull-left self-submitter state hidden-file-input'
                ],
                'options' => [
                    'label'         => 'Download Attachment',
                    'download-icon' => 'icon-download-alt icon-white',
                    'remove-icon'   => 'icon-remove icon-white',
                    'remove-url'    => $removeUrl
                ],
            ],
            [
                'name'     => 'download',
                'priority' => 9
            ]
        );
    }

    /**
     * @param int $userId
     * @return int
     */
    public function getVacationDaysCountUsedInThisYear($userId)
    {
        /**
         * @var Vacationdays $vacationdaysDao
         */
        $vacationdaysDao = $this->getVacationdaysDao();
        $vacationsResult = $vacationdaysDao->getUserVacationRequestPerThisYesr($userId, true);

        $daysCount = 0;

        if ($vacationsResult->count() > 0) {
            foreach ($vacationsResult as $vacation) {
                $daysCount += $vacation->getTotal_number();
            }
        }

        return $daysCount;
    }

    /**
     * @param int $totalDaysPerYear
     * @param int $vacationDaysLeft
     * @param bool $returnString
     * @return boolean|int
     */
    public function calculateVacationCashableDaysCount($totalDaysPerYear, $vacationDaysLeft, $returnString = false)
    {
        /**
         * Here it is necessary to add the logic of counting days...
         * that a person does not work eg was absent due care to maternity leave
         */
        $currentDayNumber = date("z") + 1;
        $vacationDaysCountApproximatelyUntil31March = $totalDaysPerYear / ((date('L')) ? 366 : 365) * $currentDayNumber;

        if ($vacationDaysLeft > $vacationDaysCountApproximatelyUntil31March) {
            $cashingDays = round(($vacationDaysLeft - $vacationDaysCountApproximatelyUntil31March), 2);
        } else {
            $cashingDays = false;
        }

        if ($cashingDays && $returnString) {
            $cashingDays = "If unused $cashingDays days will be paid out by March 31.";
        }

        return $cashingDays;
    }

    /**
     * @param int $documentId
     * @return array
     */
    public function getUserProfileByDocumentId($documentId)
    {
        return $this->getUserManagerDao('\ArrayObject')->getUserByDocumentId($documentId);
    }

    /**
     * @return \DDD\Domain\User\User
     */
    public function getAnyTeamMemberSystemUser()
    {
        /**
         * @var UserManager $usersDao
         */
        $usersDao = $this->getServiceLocator()->get('dao_user_user_manager');
        return $usersDao->findUserById(self::ANY_TEAM_MEMBER_USER_ID);
    }

    /**
     * @param int $userId
     * @param string $avatar
     * @return string
     */
    public function getAvatarForSelectize($userId, $avatar)
    {
        $avatar = str_replace('_150', '_18', $avatar);

        if (empty($avatar) || !file_exists('/ginosi/images/profile/' . $userId . '/' . $avatar)) {
            $avatar = '//' . DomainConstants::BO_DOMAIN_NAME . Constants::VERSION . 'img/no40.gif';
        } else{
            $avatar = '//' . DomainConstants::IMG_DOMAIN_NAME . '/profile/' . $userId . '/' . $avatar;
        }

        return $avatar;
    }

    public function getUserFullAddress($userId)
    {
        $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $fullAddress = $userManagerDao->getUserFullAddress($userId);
        $fullAddressArray = [];

        if ($fullAddress['address_permanent']) {
            $fullAddressArray[] = $fullAddress['address_permanent'];
        }

        if ($fullAddress['living_city']) {
            $fullAddressArray[] = $fullAddress['living_city'];
        }

        if ($fullAddress['country']) {
            $fullAddressArray[] = $fullAddress['country'];
        }

        return implode(', ',$fullAddressArray);
    }

    /**
     * @param int $userId
     * @param \DDD\Domain\User\User $oldData
     * @param array $newData
     * @return bool
     */
    public function writeSaveLog($userId, $oldData, $newData)
    {
        /**
         * @var \Library\ActionLogger\Logger $actionLogger
         */
        $logger = $this->getServiceLocator()->get('ActionLogger');

        if ($oldData->getVacation_days() != $newData['vacation_days']) {
            $logger->save(
                Logger::MODULE_USER,
                $userId,
                Logger::ACTION_USER_VACATION,
                'Changed <b>Vacation Vested</b> from <b>' . round($oldData->getVacation_days(), 2) . '</b> to <b>' . $newData['vacation_days'] . '</b>'
            );
        }

        if ($oldData->getEmail() != $newData['email']) {
            $logger->save(
                Logger::MODULE_USER,
                $userId,
                Logger::ACTION_USER_VACATION,
                'Changed <b>Email</b> from <b>' . $oldData->getEmail() . '</b> to <b>' . $newData['email'] . '</b>'
            );
        }

        if ((float)$oldData->getVacation_days_per_year() != (float)$newData['vacation_days_per_year']) {
            $logger->save(
                Logger::MODULE_USER,
                $userId,
                Logger::ACTION_USER_VACATION,
                'Changed <b>Vacation / Year</b> from <b>' . $oldData->getVacation_days_per_year() . '</b> to <b>' . $newData['vacation_days_per_year'] . '</b>'
            );
        }

        if ($oldData->getSickDays() != $newData['sick_days']) {
            $logger->save(
                Logger::MODULE_USER,
                $userId,
                Logger::ACTION_USER_VACATION,
                'Changed <b>Sick Days</b> from <b>' . $oldData->getSickDays() . '</b> to <b>' . $newData['sick_days'] . '</b>'
            );
        }

        if (isset($newData['employment']) && ((int)$oldData->getEmployment() != (int)$newData['employment'])) {
            $logger->save(
                Logger::MODULE_USER,
                $userId,
                Logger::ACTION_USER_VACATION,
                'Changed <b>Employment %</b> from <b>' . $oldData->getEmployment() . '</b> to <b>' . $newData['employment'] . '</b>'
            );
        }
    }

    /**
     * @param  String $token
     * @return Array
     */
    public function getUserInfoByToken($token)
    {
        $userManagerDao = $this->getUserManagerDao('ArrayObject');
        $userInfo       = $userManagerDao->getUserInfoByToken($token);

        if (!$userInfo) {
            return false;
        }

        $userInfoArray = iterator_to_array($userInfo);

        return $userInfoArray;
    }

    public function deleteUserToken($email)
    {
        /**
         * @var Adapter $dbAdapter
         */
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');

        // Get Items Statement
        $statement = $dbAdapter->createStatement('
            DELETE FROM oauth_access_tokens WHERE user_id = ?
        ');

        $statement->execute([$email]);
    }

    public function getAuthenticatedUserInfo()
    {
        $auth       = $this->getServiceLocator()->get('library_backoffice_auth');
        $userDomain = new \DDD\Domain\User\User();

        if (!$auth->getIdentity()) {
            return false;
        }

        $userInfo = $auth->getIdentity();

        $userDomain->setId($userInfo->id);
        $userDomain->setFirstname($userInfo->firstname);
        $userDomain->setLastname($userInfo->lastname);
        $userDomain->setEmail($userInfo->email);
        $userDomain->setCity_id($userInfo->cityId);
        $userDomain->setCountry_id($userInfo->countryId);
        $userDomain->setAvatar($userInfo->avatar);

        $userInfoArray = [
            'id'         => $userDomain->getId(),
            'firstname'  => $userDomain->getFirstname(),
            'lastname'   => $userDomain->getLastname(),
            'email'      => $userDomain->getEmail(),
            'cityId'     => $userDomain->getCity_id(),
            'countryId'  => $userDomain->getCountry_id()
        ];

        $incidentPermission  = $auth->hasRole(Roles::ROLE_MOBILE_INCIDENT_REPORT) ? true : false;
        $warehousePermission = $auth->hasRole(Roles::ROLE_MOBILE_ASSET_MANAGER) ? true : false;

        $userInfoArray['permissions']['warehouse'] = $warehousePermission;
        $userInfoArray['permissions']['incident']  = $incidentPermission;
        $userInfoArray['profileUrl']               = 'https:' . Helper::getUserAvatar($userDomain, 'big');

        return $userInfoArray;
    }
}
