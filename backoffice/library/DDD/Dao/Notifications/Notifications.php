<?php

namespace DDD\Dao\Notifications;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;

use DDD\Service\Notifications as NotificationsService;

class Notifications extends TableGatewayManager
{
    protected $table = DbTables::TBL_NOTIFICATIONS;

    public function __construct($sm, $domain = 'DDD\Domain\Notifications\Notifications')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $userId
     * @param bool/array $params
     * @return \DDD\Domain\Notifications\Notifications|ResultSet
     */
    public function getActualNotificationsForUser($userId, $params = false)
    {
        $today     = date('Y-m-d');
        $actualDay = date('Y-m-d', strtotime("+7 day", strtotime($today)));

        $notifications = $this->fetchAll(
            function (Select $select) use (
                $userId,
                $today,
                $actualDay,
                $params
            ) {
                $like = $params['search']['value'];
                $select
                    ->columns([
                        'id', 'user_id', 'sender',
                        'sender_id', 'message',
                        'show_date',  'type', 'url', 'done_date'
                    ])
                    ->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));

                if (isset($params['sender']) &&  $params['sender'] != NotificationsService::FROM_ALL) {
                    $select->where->equalTo('sender', $params['sender']);
                } else {
                    $select->where->notIn('sender', NotificationsService::getNotInboxNotifications());
                }
                    $select
                        ->where
                        ->equalTo('user_id', $userId);
                        if (!$params) {
                            $select->where->isNull('done_date');
                        } else {
                            if ($like) {
                                $select
                                    ->where
                                    ->NEST
                                    ->like('message', '%' . $like . '%')
                                    ->OR
                                    ->like('sender', '%' . $like . '%')
                                    ->UNNEST;
                            }

                            if (isset($params['active_archived']) && $params['active_archived'] == NotificationsService::STATUS_ARCHIVED) {
                                $select->where->isNotNull('done_date');
                            } else {
                                $select->where->isNull('done_date');
                            }
                        }
                    $select
                        ->where
                        ->expression('date(`show_date`) <= IF(`sender` IN (' . "'" .implode('\',\'',NotificationsService::getNotificationsList()) . "'" .'),' . "'" .$today . "'" . ',' . "'" . $actualDay . "'" . ')',[]);

                $select->order(['show_date DESC, sender, message']);
            }
        );

        $notifications->buffer();
        $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $result2   = $statement->execute();
        $row       = $result2->current();
        $total     = $row['total'];

        return [
            'result' => $notifications,
            'total'  => $total
        ];
    }

    /**
     * @param int $userId
     * @param bool|array $params
     * @return \DDD\Domain\Notifications\Notifications
     */
    public function getActualNotificationsCountForUser($userId, $params = false)
    {
        $today     = date('Y-m-d');
        $actualDay = date('Y-m-d', strtotime("+7 day", strtotime($today)));
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchOne(
            function (Select $select) use ($userId, $today, $actualDay, $params) {
                $select->columns(['count' => new Expression('COUNT(*)')]);

                if (isset($params['sender']) &&  $params['sender'] != NotificationsService::FROM_ALL) {
                    $select->where->equalTo('sender', $params['sender']);
                } else {
                    $select->where->notIn('sender',NotificationsService::getNotInboxNotifications());
                }

                $select
                    ->where
                    ->equalTo('user_id', $userId);

                if (!$params) {
                    $select->where->isNull('done_date');
                } else {
                    if (isset($params['active_archived']) && $params['active_archived'] == NotificationsService::STATUS_ARCHIVED) {
                        $select->where->isNotNull('done_date');
                    } else {
                        $select->where->isNull('done_date');
                    }
                }

                $select
                    ->where
                    ->expression('date(`show_date`) <= IF(`sender` IN (' . "'" .implode('\',\'',NotificationsService::getNotificationsList()) . "'" .'),' . "'" .$today . "'" . ',' . "'" . $actualDay . "'" . ')',[]);

                $select->order(['show_date DESC, sender, message']);
            }
        );
        $this->setEntity($prototype);

        return $result['count'];
    }


    /**
     *
     * @param int $senderId
     * @param string $sender
     * @return \DDD\Domain\Notifications\Notifications
     */
    public function getAllNotificationsBySenderId($senderId, $sender)
    {
        $result = $this->fetchAll(
                function (Select $select) use ($senderId, $sender)
        {
            $select->columns([
                'id', 'user_id', 'sender', 'sender_id', 'message',
                'show_date',  'type'
                ]);

            $select->where
                    ->equalTo('sender_id', $senderId)
                    ->and
                    ->equalTo('sender', $sender);
        });

        return $result;
    }

    /**
     * @param int $id
     * @param int $userId
     * @return \DDD\Domain\Notifications\Notifications
     */
    public function getNotificationByIdAndUser($id, $userId)
    {
        $result = $this->fetchOne(function (Select $select) use ($id, $userId) {
            $select->columns([
                'id', 'user_id', 'sender', 'sender_id', 'message',
                'show_date',  'type'
            ]);

            $select->where
                    ->equalTo('id', $id)
                    ->and
                    ->equalTo('user_id', $userId);
        });
        return $result;
    }

    /**
     *
     * @param int $id
     * @return bool
     */
    public function deleteNotification($id)
    {
        try {
            $this->deleteWhere([
                'id' => $id,
            ]);

            return TRUE;
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param int $senderId
     * @param int $sender
     * @return boolean
     */
    public function deleteNotificationsBySenderIdAndSender($senderId, $sender)
    {
        try {
            $this->deleteWhere([
                'sender_id' => $senderId,
                'sender' => $sender,
                'done_date' => NULL
            ]);

            return TRUE;
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * @param int $sender
     * @return boolean
     */
    public function deleteNotificationsBySender($sender)
    {
        try {
            $this->deleteWhere([
                'sender' => $sender,
                'done_date' => NULL
            ]);

            return TRUE;
        } catch (\Exception $e) {
            return FALSE;
        }
    }
}
