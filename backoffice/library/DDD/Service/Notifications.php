<?php
/**
 * Work with Backoffice Notifications System (BNS)
 */

namespace DDD\Service;

class Notifications extends ServiceBase
{
    CONST STATUS_ACTIVE   = 0;
    CONST STATUS_ARCHIVED = 1;
    CONST FROM_ALL        = 'From All Senders';

    public static $apartmentsPerformance        = 'Apartments Performance';
    public static $apartmentDocumentsManagement = 'Apartments Documents Management';
    public static $groupPerformance             = 'Group Performance';
    public static $peopleEvaluations            = 'People Evaluations';
    public static $interview                    = 'Interview';
    public static $availabilityMonitoring       = 'Availability Monitoring';
    public static $notifications                = 'Notifications';
    public static $applicants                   = 'Applicants';
    public static $vacation                     = 'Vacation';
    public static $vacationReminder             = 'Vacation Reminder';
    public static $evaluation                   = 'Evaluation';
    public static $comment                      = 'Comment';
    public static $warning                      = 'Warning';
    public static $purchaseOrder                = 'Purchase Order';
    public static $subscription                 = 'Subscription';
    public static $transfer                     = 'Transfer';

    private $peopleIdList = false;

    /**
     * @return array
     */
    public static function getNotificationsList()
    {
        return [
            self::$notifications,
            self::$availabilityMonitoring,
            self::$applicants,
            self::$vacation,
            self::$vacationReminder,
            self::$evaluation,
            self::$comment,
            self::$warning,
            self::$apartmentDocumentsManagement,
            self::$purchaseOrder,
            self::$interview,
            self::$peopleEvaluations,
            self::$groupPerformance,
            self::$apartmentsPerformance,
            self::$subscription,
        ];
    }

    /**
     * @return array
     */
    public static function getAllSendersForSelect()
    {
        $arr = [
            self::FROM_ALL,
            self::$vacation,
            self::$apartmentsPerformance,
            self::$apartmentDocumentsManagement,
            self::$groupPerformance,
            self::$interview,
            self::$availabilityMonitoring,
            self::$notifications,
            self::$warning,
            self::$comment,
            self::$purchaseOrder,
            self::$peopleEvaluations,
            self::$evaluation,
            self::$vacationReminder,
            self::$applicants,
            self::$subscription,
        ];

        return array_combine($arr, $arr);
    }

    /**
     * @return array
     */
    public static function getNotInboxNotifications()
    {
        return [
            self::$peopleEvaluations,
            self::$vacationReminder
        ];
    }

    /**
     * @param array $data
     * <pre>
     * int <b>user_id</b>, Unknown field
     * int|array <b>recipient</b>, People id or list of ids
     * string <b>sender</b>, Module name. Take from static variables of this method
     * int <b>sender_id</b>,
     * string <b>message</b>,
     * string <b>url</b>, (Optional)
     * string <b>show_date</b>, (Optional)
     * </pre>
     *
     * @return bool
     */
    public function createNotification(array $data)
    {
        try {
            $peopleList = $this->peopleList();

            $data['sender_id']  = !isset($data['sender_id']) ? null : $data['sender_id'];
            $data['url']        = !isset($data['url']) ? null : $data['url'];

            if (empty($data['show_date'])) {
                $data['show_date'] = date('Y-m-d H:i:s');
            }

            // If one user id is written then make it array
            if (!is_array($data['recipient'])) {
                $data['recipient'] = [$data['recipient']];
            }

            if (count($data['recipient'])) {
                foreach ($data['recipient'] as $uid) {
                    if (!in_array($uid, $peopleList)) {
                        // not a people
                        continue;
                    }

                    $this->saveNotification(
                        $uid,
                        $data['sender'],
                        $data['sender_id'],
                        $data['message'],
                        $data['url'],
                        $data['show_date'],
                        null
                    );
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return array
     */
    public function peopleList()
    {
        /**
         * @var User $userService
         */
        if (!$this->peopleIdList) {
            $userService = $this->getServiceLocator()->get('service_user');
            $this->peopleIdList = $userService->getActivePeopleIdList();
        }

        return $this->peopleIdList;
    }

    /**
     * @param int $userId
     * @param string $sender
     * @param int $senderId
     * @param string $message
     * @param string $url
     * @param string $showDate
     * @param string $type
     */
    private function saveNotification(
        $userId, $sender, $senderId,
        $message, $url, $showDate, $type
    ) {
        /**
         * @var \DDD\Dao\Notifications\Notifications $notificationsDao
         */
        $notificationsDao = $this->getServiceLocator()->get('dao_notifications_notifications');

        $notificationsDao->save([
            'user_id'   => $userId,
            'sender'    => $sender,
            'sender_id' => $senderId,
            'message'   => $message,
            'url'       => $url,
            'show_date' => $showDate,
            'type'      => $type
        ]);
    }

    /**
     * @param int $userId
     * @param bool|array $params
     * @return \DDD\Domain\Notifications\Notifications
     */
    public function getUserNotifications($userId, $params = false)
    {
        /**
         * @var \DDD\Dao\Notifications\Notifications $notificationsDao
         */
        $notificationsDao = $this->getServiceLocator()->get('dao_notifications_notifications');
        $unsolvedNotifications = $notificationsDao->getActualNotificationsForUser($userId, $params);

        return $unsolvedNotifications;
    }


    /**
     * @param int $notificationId
     * @return bool
     */
    public function isNotificationBelongsToUser($notificationId)
    {
        /**
         * @var \DDD\Dao\Notifications\Notifications $notificationsDao
         */
        $notificationsDao = $this->getServiceLocator()->get('dao_notifications_notifications');

        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $userIdentity = $auth->getIdentity();

        return $notificationsDao->getNotificationByIdAndUser($notificationId, $userIdentity->id);
    }

    /**
     * @param int $notificationId
     * @return bool
     */
    public function archiveNotification($notificationId)
    {
        try {
            /**
             * @var \DDD\Dao\Notifications\Notifications $notificationsDao
             */
            $notificationsDao = $this->getServiceLocator()->get('dao_notifications_notifications');

            $auth = $this->getServiceLocator()->get('library_backoffice_auth');

            $notificationsDao->save(['done_date' => date('Y-m-d H:i:s')], [
                'id' => $notificationId,
                'user_id' => $auth->getIdentity()->id,
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param int $notificationId
     * @return bool
     */
    public function deleteNotification($notificationId)
    {
        /**
         * @var \DDD\Dao\Notifications\Notifications $notificationsDao
         */
        $notificationsDao = $this->getServiceLocator()->get('dao_notifications_notifications');

        return $notificationsDao->deleteNotification($notificationId);
    }

    /**
     * @param int $senderId
     * @param string $sender
     * @return bool
     */
    public function deleteSenderNotification($senderId, $sender)
    {
        /**
         * @var \DDD\Dao\Notifications\Notifications $notificationsDao
         */
        $notificationsDao = $this->getServiceLocator()->get('dao_notifications_notifications');

        return $notificationsDao->deleteNotificationsBySenderIdAndSender($senderId, $sender);
    }

    /**
     * @param string $sender
     * @return bool
     */
    public function deleteSenderAllNotifications($sender)
    {
        /**
         * @var \DDD\Dao\Notifications\Notifications $notificationsDao
         */
        $notificationsDao = $this->getServiceLocator()->get('dao_notifications_notifications');

        return $notificationsDao->deleteNotificationsBySender($sender);
    }
}
