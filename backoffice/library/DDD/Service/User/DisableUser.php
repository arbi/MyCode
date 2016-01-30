<?php

namespace DDD\Service\User;

use DDD\Dao\Booking\Booking;
use DDD\Dao\User\UserManager;
use DDD\Service\ServiceBase;
use DDD\Service\Task as TaskService;
use DDD\Service\User as UserService;
use DDD\Service\Team\Team as TeamService;
use DDD\Service\Notifications as NotificationsService;
use DDD\Service\User\Evaluations as EvaluationService;
use DDD\Service\Contacts\Contact;

use Library\Constants\DbTables;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Where;

/**
 * Class DisableUser
 * @package DDD\Service\User
 *
 * @author Tigran Petrosyan
 */
class DisableUser extends ServiceBase
{

    /**
     * @todo #get_manager_id #set_manager #get_subordinates
     *
     * Imagine that X employee is managing Y and Y is managing W and Z.
     * If Y employee is going to be disabled, then W and Z employees should be managed by X.
     *
     * @param $backofficeUserId
     * @return bool
     */
    public function changeSubOrdinatesManager($backofficeUserId)
    {
        /**
         * @var UserManager $backofficeUsersDao
         */
        $backofficeUsersDao = $this->getServiceLocator()->get('dao_user_user_manager');

        $toBeDisabledUserManager = $backofficeUsersDao->fetchOne(
            ['id' => $backofficeUserId],
            ['manager_id']
        );

        $toBeDisabledUserManagerId = $toBeDisabledUserManager->getManager_id();

        $toBeDisabledUserSubOrdinates = $backofficeUsersDao->fetchAll(
            ['manager_id' => $backofficeUserId],
            ['id']
        );

        if (count($toBeDisabledUserSubOrdinates) > 0) {
            $backofficeUsersDao->update(
                ['manager_id' => $toBeDisabledUserManagerId],
                ['manager_id' => $backofficeUserId]
            );
        }

        return true;
    }

    /**
     * @param $backofficeUserId int
     * @return bool
     */
    public function unassignUserFromTasks($backofficeUserId)
    {
        /**
         * @var UserManager $backofficeUsersDao
         */
        $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $taskStaffDao   = $this->getServiceLocator()->get('dao_task_staff');

        $userInfo = $userManagerDao->fetchOne(['id' => $backofficeUserId]);

        $where = new Where();
        $where
            ->in('type', [TaskService::STAFF_HELPER, TaskService::STAFF_FOLLOWER])
            ->equalTo('user_id', $backofficeUserId);
        $taskStaffDao->deleteWhere($where);

        $taskStaffs = $taskStaffDao->getStaffTasks($backofficeUserId);
        $managerId  = $userInfo->getManager_id();
        $oneMonthBeforeNow = strtotime('-1 month');
        if ($taskStaffs->count()) {
            foreach ($taskStaffs as $taskStaff) {

                if ($taskStaff->getType() == TaskService::STAFF_VERIFIER) {
                    if (strtotime($taskStaff->getStartDate()) >=  $oneMonthBeforeNow) {

                        $taskStaffDao->save(
                            ['user_id' => $managerId],
                            ['id'      => $taskStaff->getIId()]
                        );
                    } else {
                        $taskStaffDao->save(
                            ['user_id' => UserService::AUTO_VERIFY_USER_ID],
                            ['id'      => $taskStaff->getIId()]
                        );
                    }
                }

                if ($taskStaff->getType() == TaskService::STAFF_RESPONSIBLE) {
                    if (   ($taskStaff->getTaskStatus() != TaskService::STATUS_DONE)
                        && ($taskStaff->getTaskStatus() != TaskService::STATUS_VERIFIED)
                    ) {
                        $taskStaffDao->deleteWhere(
                            [
                                'user_id' => $backofficeUserId,
                                'id'      => $taskStaff->getIId()
                            ]
                        );
                    }
                }
            }
        }
        return true;
    }

    /**
     * @param $userId
     * @return bool
     */
    public function removeUserFromTeams($userId)
    {
        /**
         * @var UserManager $backofficeUsersDao
         * @var \DDD\Dao\Team\TeamStaff $teamStaffDao
         */
        $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $teamStaffDao   = $this->getServiceLocator()->get('dao_team_team_staff');

        $userInfo  = $userManagerDao->fetchOne(['id' => $userId]);
        $managerId = $userInfo->getManager_id();

        $where = new Where();

        $where
            ->equalTo('user_id', $userId)
            ->in('type', [
                TeamService::STAFF_MEMBER,
                TeamService::STAFF_OFFICER,
                TeamService::STAFF_MANAGER
            ]);

        $teamStaffDao->delete($where);

        $teamStaffDao->save(
            ['user_id' => $managerId],
            ['user_id' => $userId]
        );

        return true;
    }

    /**
     * @param int $userId
     * @return boolean
     */
    public function removeUserEvaluationNotifications($userId)
    {
        /**
         * @var $notificationsDao \DDD\Dao\Notifications\Notifications
         */
        $notificationsDao = $this->getServiceLocator()->get('dao_notifications_notifications');
        $notificationsDService = new NotificationsService();

        return $notificationsDao->deleteNotificationsBySenderIdAndSender($userId, $notificationsDService::$peopleEvaluations);
    }

    /**
     * @param int $userId
     * @return boolean
     */
    public function cancelUpcomingEvaluations($userId)
    {
        $evaluationsDao = $this->getServiceLocator()->get('dao_user_evaluation_evaluations');

        return $evaluationsDao->save(
            ['status'  => EvaluationService::USER_EVALUATION_STATUS_CANCELLED],
            ['user_id' => $userId]
        );
    }

    public function deleteUserPersonalContacts($userId)
    {
        /**
         * @var \DDD\Service\Contacts\Contact $contactsService
         */
        $contactsService = $this->getServiceLocator()->get('service_contact_contact');

        return $contactsService
            ->deleteContactByCreatorIdAndScope($userId, Contact::SCOPE_PERSONAL);
    }

    /**
     * @param  string $userEmail
     * @return Null
     */
    public function deleteUserFromOauth($userEmail)
    {
        $daoOuathUsers = $this->getServiceLocator()->get('dao_oauth_oauth_users');
        $daoOuathUsers->deleteWhere(['username' => $userEmail]);
    }
}
