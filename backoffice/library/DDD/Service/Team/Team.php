<?php

namespace DDD\Service\Team;

use DDD\Dao\User\UserManager;
use DDD\Service\ServiceBase;
use DDD\Service\User;
use DDD\Service\Team\Team as TeamService;

use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Constants;

use Library\Constants\TextConstants;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;

/**
 *
 * @Author Arbi Bach
 */
class Team extends ServiceBase
{
    const STAFF_CREATOR  = 1;
    const STAFF_MEMBER   = 2;
    const STAFF_OFFICER  = 3;
    const STAFF_MANAGER  = 4;
    const STAFF_DIRECTOR = 5;

    const TEAM_FINANCE           = 2;
    const TEAM_OPERATIONS        = 3;
    const TEAM_ACQUISITIONS      = 4;
    const TEAM_MARKETING         = 5;
    const TEAM_CONTACT_CENTER    = 6;
    const TEAM_QUALITY_ASSURANCE = 15;
    const TEAM_PROCUREMENT       = 16;
    const TEAM_LEGAL             = 27;
    const TEAM_COMMERCIAL        = 29;
    const TEAM_ACCOUNT_PAYABLE   = 46;

    public static $teamsThatAllowedToChooseAffiliateForWebsiteReservations = [
        self::TEAM_COMMERCIAL,
        self::TEAM_CONTACT_CENTER
    ];

    const IS_ACTIVE_TEAM   = 0;
    const IS_FRONTIER_TEAM = 1;

    /**
     * @var $_dao_teams \DDD\Dao\Team\Team
     */
    public $_dao_teams = false;

    /**
     * @var $_dao_team_staff \DDD\Dao\Team\TeamStaff
     */
    public $_dao_team_staff = false;

    /**
     * @var $_dao_team_frontier_apartments \DDD\Dao\Team\TeamFrontierApartments
     */
    public $_dao_team_frontier_apartments = false;

    /**
     * @var $_dao_team_frontier_buildings \DDD\Dao\Team\TeamFrontierBuildings
     */
    public $_dao_team_frontier_buildings = false;

    public function getTeamListDetails($start, $limit, $sortCol, $sortDir, $like, $all)
    {
        return $this->getTeamDao()->getTeamListDetails($start, $limit, $sortCol, $sortDir, $like, $all);
    }

    public function getTeamListCount($like, $all)
    {
        return $this->getTeamDao()->getTeamListCount($like, $all);
    }

    /**
     * @param int $directorId
     * @param int $isDepartment
     * @param bool $active
     * @param bool $isTaskable
     * @param bool $isSecurity
     * @return \DDD\Domain\Team\Team[]
     */
    public function getTeamList($directorId = null, $isDepartment = 0, $active = false, $isTaskable = false, $isSecurity = false)
    {
        /**
         * @var $teamDAO \DDD\Dao\Team\Team
         */
        $teamDAO = $this->getServiceLocator()->get('dao_team_team');
        $result  = $teamDAO->getTeamList($directorId, $isDepartment, $active, $isTaskable, $isSecurity);
        return $result;
    }

    /**
     * Return user id list who belong to mentioned team
     *
     * @param int $teamId
     * @return array
     */
    public function getTeamManagerAndOfficerList($teamId)
    {
        /**
         * @var \DDD\Dao\Team\TeamStaff $teamStaffDao
         */
        $teamStaffDao = $this->getServiceLocator()->get('dao_team_team_staff');
        $userIdList = [];

        $teamAssociate = $teamStaffDao->getTeamManagerAndOfficerList($teamId);

        if ($teamAssociate->count()) {
            foreach ($teamAssociate as $user) {
                array_push($userIdList, $user->getUserId());
            }
        }

        return $userIdList;
    }

    public function getTeamCreator($teamId)
    {
        $this->getTeamStaffDao();

        $result  = $this->_dao_team_staff->getTeamCreator($teamId);
        return $result;
    }

    public function getTeamDirector($teamId)
    {
        $result = $this->getTeamStaffDao()->getTeamDirector($teamId);
        return $result;
    }

    public function getTeamManagers($teamId, $respById = false)
    {
        $this->getTeamDao();

        $result  = $this
            ->_dao_team_staff->getTeamManagers(
                $teamId,
                $respById
        );

        return $result;
    }

    /**
     * @return \DDD\Domain\Team\ForSelect[]
     */
    public function getPermanentTeams()
    {
        /**
         * @var \DDD\Dao\Team\Team $teamsDao
         */
        $teamsDao = $this->getServiceLocator()->get('dao_team_team');

        return $teamsDao->getPermanentTeams();
    }

    public function getTeamOptions()
    {
        /**
         * @var User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');

        $accommodationDao = $this->getServiceLocator()->get('dao_accommodation_accommodations');

        $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

        $accommodationList = $accommodationDao->fetchAll(
            'status <> 9 AND id NOT IN (' .
            Constants::TEST_APARTMENT_1 . ', ' .
            Constants::TEST_APARTMENT_2 . ') ORDER BY `name`'
        );
        $this->registry->set('accommodationList', $accommodationList);

        $apartments = [];
        $apartmentDomainList = $accommodationDao->fetchAll();

        if ($apartmentDomainList->count()) {
            foreach ($apartmentDomainList as $apartmentDomain) {
                $apartments[$apartmentDomain->getId()] = $apartmentDomain->getName();
            }
        }

        $this->registry->set('apartmentList', $apartments);

        $apartmentGroups = $apartmentGroupDao->fetchAll(['active' => 1, 'usage_building' => 1]);

        $accommodationGroupsArray = [];
        foreach ($apartmentGroups as $apartmentGroup) {
            $accommodationGroupsArray[$apartmentGroup->getId()] = $apartmentGroup->getNameWithApartelUsage();
        }

        $this->registry->set('apartmentGroups', $accommodationGroupsArray);

        $managerId = false;

        $ginosiks = $userService->getPeopleList($managerId);

        $this->registry->set('ginosiksList', $ginosiks);

        return $this->registry;
    }

    public function getData($id)
    {
        $general  = $this->getTeamDao()->getTeamBasicInfo($id);
        $tManager = $this->getTeamStaffDao()->getTeamManagerList($id);
        $officers = $this->getTeamStaffDao()->getTeamOfficerList($id);
        $members  = $this->getTeamStaffDao()->getTeamMemberList($id);
        $creator  = $this->getTeamStaffDao()->getTeamCreator($id);
        $director = $this->getTeamStaffDao()->getTeamDirector($id);

        $allUsersRaw = (new \DDD\Dao\User\UserManager($this->getServiceLocator(), '\ArrayObject'))->fetchAll();
        $allUsers = [];

        foreach ($allUsersRaw as $user) {
            $allUsers[$user['id']] = $user['firstname'] . ' ' . $user['lastname'];
        }

        $frontierApartments = $frontierBuildings = false;
        if ($general->IsFrontier()) {
            $frontierApartments = $this->getTeamFrontierApartmentsDao()->getFrontierTeamApartments($id);
            $frontierBuildings  = $this->getTeamFrontierBuildingsDao()->getFrontierTeamBuildings($id);
        }

        $this->registry->set('general', $general);
        $this->registry->set('teamManagers', $tManager);
        $this->registry->set('teamMembers', $members);
        $this->registry->set('teamOfficers', $officers);
        $this->registry->set('frontierApartments', $frontierApartments);
        $this->registry->set('frontierBuildings', $frontierBuildings);
        $this->registry->set('creator', $creator);
        $this->registry->set('director', $director);
        $this->registry->set('allUsers', $allUsers);

        return $this->registry;

    }

    public function teamSave($data, $id, $global, $isDirector)
    {
        $apartmentDao   = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $auth           = $this->getServiceLocator()->get('library_backoffice_auth');

        $data = (array)$data;

        $teamData = [
            'usage_department'      => $data['usage_department'],
            'usage_notifiable'      => $data['usage_notifiable'],
            'usage_frontier'        => $data['usage_frontier'],
            'usage_taskable'        => $data['usage_taskable'],
            'usage_security'        => $data['usage_security'],
            'usage_hiring'          => $data['usage_hiring'],
            'usage_storage'         => $data['usage_storage'],
            'extra_inspection'      => isset($data['extra_inspection']) ? $data['extra_inspection'] : 0,
            'timezone'              => $data['timezone']
        ];

        if ($global) {

            $teamData['name']        = $data['name'];
            $teamData['description'] = $data['description'];
        }

        if ($id > 0) {
            $teamData['modified_date'] = date('Y-m-d');
            $this->getTeamDao()->save($teamData, ['id' => (int)$id]);
        } else {
            $teamData['created_date']  = date('Y-m-d');
            $teamData['modified_date'] = null;
            $id = $this->getTeamDao()->save($teamData);

            $this->getTeamStaffDao()->insert([
                'type'    => self::STAFF_CREATOR,
                'team_id' => $id,
                'user_id' => $auth->getIdentity()->id
            ]);
        }

        $diffTimezone = [];

        if ($id > 0) {

            $teamInfo = $this->getTeamDao()->fetchOne(['id' => $id]);

            // Remove duplication if user is set as both manager and member && its not frontier team
            if ($teamInfo && $teamInfo->isFrontier() != self::IS_FRONTIER_TEAM) {
                if (isset($data['members'])) {
                    $managers = $data['managers'];
                    $members  = $data['members'];
                    foreach ($managers as $manager) {
                        if (array_search($manager, $members) !== false) {
                            $key = array_search($manager, $members);
                            unset($data['members'][$key]);
                        }
                    }
                }
            }

            // Delete all staff and save again to avoid complicated select, check, insert & delete actions
            $staffDeleteCondition = new Where();
            $staffDeleteCondition->equalTo('team_id', $id);
            // Do not delete creator
            $staffDeleteCondition->notEqualTo('type', self::STAFF_CREATOR);
            // Change (delete + insert) director only if global
            if (!$global) {
                $staffDeleteCondition->notEqualTo('type', self::STAFF_DIRECTOR);
            }
            // Change (delete + insert) managers only if global or director
            if (!$global && !$isDirector) {
                $staffDeleteCondition->notEqualTo('type', self::STAFF_MANAGER);
            }

            $this->getTeamStaffDao()->delete($staffDeleteCondition);

            // Insert director, if global
            if ($global && $data['director']) {
                $this->getTeamStaffDao()->insert([
                    'type'    => self::STAFF_DIRECTOR,
                    'team_id' => $id,
                    'user_id' => $data['director']
                ]);
            }

            // Insert members passed by form
            if (!empty($data['members'])) {
                foreach ($data['members'] as $member) {
                    $this->getTeamStaffDao()->insert([
                        'type'       => self::STAFF_MEMBER,
                        'team_id'    => $id,
                        'user_id'    => $member
                    ]);
                }
            }

            // Insert officers passed by form
            if (!empty($data['officers'])) {
                foreach ($data['officers'] as $officer) {
                    $this->_dao_team_staff->insert([
                        'type'       => self::STAFF_OFFICER,
                        'team_id'    => $id,
                        'user_id'    => $officer
                    ]);
                }
            }

            // Insert managers passed by form
            if (!empty($data['managers'])) {
                foreach ($data['managers'] as $manager) {
                    $this->_dao_team_staff->insert([
                        'type'    => self::STAFF_MANAGER,
                        'team_id' => $id,
                        'user_id' => $manager
                    ]);
                }
            }

            // Delete all apartments and buildings to avoid complexity of selecting existing, comparison, deletion and addition
            if ($global || $isDirector) {
                $apartmentsDeleteCondition = new Where();
                $apartmentsDeleteCondition->equalTo('team_id', $id);

                $this->getTeamFrontierApartmentsDao()->delete($apartmentsDeleteCondition);
                $this->getTeamFrontierBuildingsDao()->delete($apartmentsDeleteCondition);

                // Insert apartments passed by form
                if (!empty($data['frontier_apartments'])) {
                    foreach ($data['frontier_apartments'] as $frontierApartment) {

                        $isDuplicate = $this->getTeamFrontierApartmentsDao()->checkDuplicateApartment($id, $frontierApartment);

                        if (!$isDuplicate) {
                            $this->getTeamFrontierApartmentsDao()->insert([
                                'team_id'      => $id,
                                'apartment_id' => $frontierApartment
                            ]);

                            $apartmentTimezone = $apartmentDao->getApartmentTimezone($frontierApartment);

                            if ($data['timezone'] !== $apartmentTimezone['timezone']) {
                                array_push($diffTimezone, $apartmentTimezone['name']);
                            }
                        }
                    }
                }

                // Insert buildings passed by form
                if (!empty($data['frontier_buildings'])) {
                    foreach ($data['frontier_buildings'] as $frontierBuilding) {
                        $this->getTeamFrontierBuildingsDao()->insert([
                            'team_id' => $id,
                            'building_id' => $frontierBuilding
                        ]);
                    }
                }
            }

            return [
                'id'           => $id,
                'diffTimezone' => implode(',', $diffTimezone)
            ];
        }
    }

    public function deleteTeam($id)
    {
        $this->getTeamDao()->deleteWhere(['id' => $id]);
        $this->getTeamStaffDao()->deleteWhere(['team_id' => $id]);

        return true;

    }

    public function checkName($name, $id)
    {
        return $this->getTeamDao()->checkName($name, $id);
    }

    /**
     * @param $id
     * @return \DDD\Domain\Team\Team[]
     */
    public function getUserTeams($id)
    {
        return $this->getTeamDao()->getUserTeams($id);
    }

    /**
     * Returns true if user is this team's director or one of team's managers
     * @param $teamId
     * @param $userId
     * @return bool
     */
    public function isTeamManagerOrDirector($teamId, $userId)
    {
        $where = new Where();
        $nestedWhere = new Predicate();
        $where
            ->equalTo('team_id', $teamId)
            ->equalTo('user_id', $userId);
        $nestedWhere
            ->equalTo('type', self::STAFF_MANAGER)
            ->or
            ->equalTo('type', self::STAFF_DIRECTOR);
        $where->addPredicate($nestedWhere);

        $result = $this->getTeamStaffDao()->fetchOne($where, ['id']);

        if (!$result) {
            return false;
        }

        return true;
    }

    /**
     * @param $storageName
     * @return int
     */
    public function createTeamFromStorage ($storageName)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $insertId = $this->getTeamDao()->save([
            'name' => sprintf(TextConstants::STORAGE_TEAM_NAME, $storageName),
            'description' => sprintf(TextConstants::STORAGE_TEAM_DESCRIPTION, $storageName),
            'created_date' => date('Y-m-d'),
            'is_permanent' => 1,
            'usage_storage' => 1
        ]);

        $this->getTeamStaffDao()->insert([
            'type'    => self::STAFF_CREATOR,
            'team_id' => $insertId,
            'user_id' => $auth->getIdentity()->id
        ]);

        return $insertId;
    }

    public function getTeamDao()
    {
        if (!($this->_dao_teams))
            $this->_dao_teams =
                $this->getServiceLocator()->get('dao_team_team');

        return $this->_dao_teams;
    }

    public function getTeamStaffDao()
    {
        if (!($this->_dao_team_staff))
            $this->_dao_team_staff =
                $this->getServiceLocator()->get('dao_team_team_staff');

        return $this->_dao_team_staff;
    }

    public function getTeamFrontierApartmentsDao()
    {
        if (!($this->_dao_team_frontier_apartments))
            $this->_dao_team_frontier_apartments =
                $this->getServiceLocator()->get('dao_team_team_frontier_apartments');

        return $this->_dao_team_frontier_apartments;
    }

    public function getTeamFrontierBuildingsDao()
    {
        if (!($this->_dao_team_frontier_buildings))
            $this->_dao_team_frontier_buildings =
                $this->getServiceLocator()->get('dao_team_team_frontier_buildings');

        return $this->_dao_team_frontier_buildings;
    }

    /**
     * @param int $userId
     * @param int $teamId
     * @return int|bool
     */
    public function getUserPositionInTeam($userId, $teamId)
    {
        /**
         * @var $staffDao \DDD\Dao\Team\TeamStaff
         */
        $staffDao = $this->getServiceLocator()->get('dao_team_team_staff');

        $positions = $staffDao->getUserPositionInTeam($userId, $teamId);

        $positionsArray = [];
        foreach ($positions as $position) {
            array_push($positionsArray, $position->getType());
        }

        return $positionsArray;
    }
}
