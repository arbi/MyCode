<?php

namespace DDD\Service\User;

use DDD\Dao\User\UserManager;
use DDD\Service\ServiceBase;
use DDD\Service\Contacts\Contact;


/**
 * Class Main
 * @package DDD\Service\User
 *
 * @author Tigran Petrosyan
 */
class Main extends ServiceBase
{
    const SYSTEM_USER_ID = 277;
    const UNIT_TESTER_USER_ID = 13;

    /**
     * @param $data[]
     */
    public function createUser($data)
    {

    }

    /**
     * @param int $backofficeUserId
     * @param string|null $deactivationDate
     * @return bool
     */
    public function disableUser($backofficeUserId, $deactivationDate, $userEmail)
    {
        /**
         * @var UserManager $backofficeUsersDao
         * @var DisableUser $disableUserService
         * @var Permissions $userPermissionsService
         */
        $backofficeUsersDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $disableUserService = $this->getServiceLocator()->get('service_user_disable_user');
        $userPermissionsService = $this->getServiceLocator()->get('service_user_permissions');

        $updateQuery = ['disabled' => 1];

        if (!is_null($deactivationDate)) {
            $updateQuery['end_date'] =  $deactivationDate;
        }

        // set as disabled
        $backofficeUsersDao->update(
            $updateQuery,
            ['id' => $backofficeUserId]
        );

        // set manager for subordinates
        $disableUserService->changeSubOrdinatesManager($backofficeUserId);

        // Unlink user from tasks where he is follower or helper
        // Unassign user from not finished tasks where he is responsible,
        // verifier or manager and pass those tasks to his line manager
        $disableUserService->unassignUserFromTasks($backofficeUserId);

        // Remove User from all teams
        // If he was a team director replace him with his line manager
        $disableUserService->removeUserFromTeams($backofficeUserId);

        // remove notifications about this user evaluation
        $disableUserService->removeUserEvaluationNotifications($backofficeUserId);

        // cancel upcoming evaluation for this user
        $disableUserService->cancelUpcomingEvaluations($backofficeUserId);

        // delete user's all personal contacts
        $disableUserService->deleteUserPersonalContacts($backofficeUserId);

        // change po item manager
        $userPermissionsService->changePOItemManager($backofficeUserId);

        // take all permissions and accesses
        $userPermissionsService->takeAllRoles($backofficeUserId);
        $userPermissionsService->takeAllDashboardsAccess($backofficeUserId);
        $userPermissionsService->takeAllMoneyAccountsAccess($backofficeUserId);
        $userPermissionsService->takeAllConciergeDashboardsAccess($backofficeUserId);

        $disableUserService->deleteUserFromOauth($userEmail);

        return true;
    }

    /**
     * @param int $backofficeUserId
     * @return bool
     */
    public function activateUser($backofficeUserId, $username, $password)
    {
        /**
         * @var UserManager $backofficeUsersDao
         */
        $backofficeUsersDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $daoOuathUsers = $this->getServiceLocator()->get('dao_oauth_oauth_users');

        // set as disabled
        $backofficeUsersDao->update(
            ['disabled' => 0],
            ['id' => $backofficeUserId]
        );

        $daoOuathUsers->save(['username' => $username, 'password' => $password]);

        return true;
    }

    /**
     * @param int $backofficeUserId
     * @return int
     * @throws \Exception
     */
    public function getUserManagerId($backofficeUserId)
    {
        /**
         * @var UserManager $backofficeUsersDao
         * @var \DDD\Domain\User\User $userDomain
         */
        $backofficeUsersDao = $this->getServiceLocator()->get('dao_user_user_manager');

        $userDomain = $backofficeUsersDao->fetchOne(
            ['id' => $backofficeUserId],
            ['manager_id' => 'manager_id']
        );

        if (!$userDomain) {
            throw new \Exception('The user has no manager.');
        }

        return $userDomain->getManager_id();
    }

    /**
     * @param int $backofficeUserId
     * @return int
     */
    public function getUserDepartmentId($backofficeUserId)
    {
        /**
         * @var UserManager $usersDao
         */
        $usersDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $result = $usersDao->getUserDepartment($backofficeUserId);
        $departmentId = 0;

        if ($result) {
            $departmentId = $result['department_id'];
        }

        return $departmentId;
    }

    /**
     * @param $userId
     * @return array|\ArrayObject|null
     */
    public function getUserInfoForGoogleAnalytics($userId)
    {
        /**
         * @var UserManager $usersDao
         */
        $usersDao = $this->getServiceLocator()->get('dao_user_user_manager');

        return $usersDao->getUserInfoForGoogleAnalytics($userId);
    }

    /**
     * @param int $userId
     * @return \ArrayObject[]|\Zend\Db\ResultSet\ResultSet
     */
    public function getUserTrackingInfo($userId)
    {
        /**
         * @var UserManager $usersDao
         */
        $usersDao = $this->getServiceLocator()->get('dao_user_user_manager');
        return $usersDao->getUserTrackingInfo($userId);
    }

    public function searchContacts($searchQuery)
    {
        /**
         * @var UserManager $usersDao
         */
        $usersDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $result = $usersDao->searchContacts($searchQuery);
        $resultArray = [];
        foreach ($result as $row) {

            array_push($resultArray,
                [
                    'id'    => $row->getId() . '_' . Contact::TYPE_EMPLOYEE,
                    'type'  => Contact::TYPE_EMPLOYEE,
                    'label'  => Contact::LABEL_NAME_EMPLOYEE,
                    'labelClass'  => Contact::LABEL_CLASS_EMPLOYEE,
                    'text'  => $row->getFullName() . ', ' . $row->getPosition(),
                    'info'  =>''
                ]);
        }

        return $resultArray;
    }

    public function getUserForContactInfo($id)
    {
        /**
         * @var \DDD\Dao\User\UserManager $usersDao
         */
        $usersDao = $this->getServiceLocator()->get('dao_user_user_manager');
        return $usersDao->getUserForContactInfo($id);
    }
}
