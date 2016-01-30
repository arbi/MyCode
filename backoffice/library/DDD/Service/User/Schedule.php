<?php

namespace DDD\Service\User;

use DDD\Service\User as UserBase;
use Zend\Db\Sql\Where;

/**
 * Class Schedule
 * @package DDD\Service\User
 */
class Schedule extends UserBase
{
    const SCHEDULE_TYPE_PART_TIME    = 0.5;
    const SCHEDULE_TYPE_WORK         = 1;
    const SCHEDULE_TYPE_AVAILABILITY = 2;

    const INVENTORY_COLOR_0 = '#EFEFEF';
    const INVENTORY_COLOR_1 = '#89E4C3';
    const INVENTORY_COLOR_2 = '#48C0CE';
    const INVENTORY_COLOR_3 = '#7490D2';
    const INVENTORY_COLOR_4 = '#C592EA';
    const INVENTORY_COLOR_5 = '#D472B5';

    public static function getInventoryColors($colorId = null)
    {
        $colors = [
            0 => self::INVENTORY_COLOR_0,
            1 => self::INVENTORY_COLOR_1,
            2 => self::INVENTORY_COLOR_2,
            3 => self::INVENTORY_COLOR_3,
            4 => self::INVENTORY_COLOR_4,
            5 => self::INVENTORY_COLOR_5
        ];

        if (!is_null($colorId)) {
            return $colors[$colorId];
        }

        return $colors;
    }

    /**
     * @param $userId
     * @return \DDD\Domain\User\Schedule\Schedule[]
     */
    public function getUserSchedule($userId)
    {
        /**
         * @var \DDD\Dao\User\Schedule\Schedule $scheduleDao
         */
        $scheduleDao = $this->getServiceLocator()->get('dao_user_schedule_schedule');

        return $scheduleDao->fetchAll([
            'user_id' => $userId
        ]);
    }

    /**
     * @param array $days
     * @param int $userId
     * @return int
     */
    public function saveSchedule($days, $userId)
    {
        /**
         * @var \DDD\Dao\User\Schedule\Schedule $scheduleDao
         */
        $scheduleDao = $this->getServiceLocator()->get('dao_user_schedule_schedule');

        // Removing existing schedule data
        $scheduleDao->delete(['user_id' => $userId]);

        // Preparing for multi-insert
        $saveData = [];
        foreach ($days as $day => $schedule) {
            array_push($saveData, [
                'user_id' => $userId,
                'day' => $day,
                'active' => $schedule['active'],
                'time_from1' => ($schedule['active']) ? $schedule['time_from1'] : '',
                'time_to1'   => ($schedule['active']) ?
                    ('00:00' != $schedule['time_to1'] ? $schedule['time_to1'] : '24:00') :
                    '',
                'time_from2' => ($schedule['active'] && isset($schedule['time_from2'])) ? $schedule['time_from2'] : '',
                'time_to2' => ($schedule['active'] && isset($schedule['time_to2'])) ?
                    ('00:00' != $schedule['time_to2'] ? $schedule['time_to2'] : '24:00' ) :
                    '',
            ]);
        }

        return $scheduleDao->multiInsert($saveData);
    }

    public function fillInventory($userId, $startDate, $officeId)
    {
        /** @var \DDD\Dao\User\Schedule\Inventory $inventoryDao */
        $inventoryDao = $this->getServiceLocator()->get('dao_user_schedule_inventory');

        $deleteWhere = new Where();
        $deleteWhere
            ->equalTo('user_id', $userId)
            ->greaterThanOrEqualTo('date', date('Y-m-d', strtotime($startDate)))
            ->notEqualTo('is_changed', 1);

        $inventoryDao->delete($deleteWhere);

        $scheduleData = $this->getUserSchedule($userId);
        $scheduleArr = [];
        if ($scheduleData) {
            foreach ($scheduleData as $daySchedule) {
                $scheduleArr[$daySchedule->getDay()] = $daySchedule;
            }
        }

        // Fill up to 3 months ahead
        $endDate = date('Y-m-d', strtotime('+92 days'));
        $dateIterator = date('Y-m-d', strtotime($startDate));

        $insertArray = [];
        while ($dateIterator < $endDate) {
            /** @var \DDD\Domain\User\Schedule\Schedule $daySchedule */
            if (count($scheduleArr)) {
                for ($scheduleIndex = 1; $scheduleIndex <= count($scheduleArr); $scheduleIndex++) {
                    $daySchedule = $scheduleArr[$scheduleIndex];

                    array_push($insertArray, [
                        'user_id'      => $userId,
                        'office_id'    => $officeId,
                        'date'         => $dateIterator,
                        'availability' => $daySchedule->isActive(),
                        'time_from1'   => $daySchedule->isActive() ? $daySchedule->getTimeFrom1() : '',
                        'time_to1'     => $daySchedule->isActive() ? $daySchedule->getTimeTo1() : '',
                        'time_from2'   => $daySchedule->isActive() ? $daySchedule->getTimeFrom2() : '',
                        'time_to2'     => $daySchedule->isActive() ? $daySchedule->getTimeTo2() : ''
                    ]);

                    $dateIterator = date('Y-m-d', strtotime($dateIterator . '+1 days'));
                }
            // The case when user has no schedule scheme. Fill the inventory with 0 availability
            } else {
                array_push($insertArray, [
                    'user_id'      => $userId,
                    'office_id'    => $officeId,
                    'date'         => $dateIterator,
                    'availability' => 0,
                    'time_from1'   => '',
                    'time_to1'     => '',
                    'time_from2'   => '',
                    'time_to2'     => ''
                ]);
                $dateIterator = date('Y-m-d', strtotime($dateIterator . '+1 days'));
            }
        }

        $inventoryDao->multiInsert($insertArray, true);
    }

    /**
     * @param int $teamId
     * @param string $from
     * @param string $to
     * @return \DDD\Domain\User\Schedule\Inventory[]
     */
    public function getScheduleTable($teamId = 0, $from = '', $to = '', $scheduleTypeId = 0, $officeId = 0)
    {
        /** @var \DDD\Dao\User\Schedule\Inventory $inventoryDao */
        $inventoryDao = $this->getServiceLocator()->get('dao_user_schedule_inventory');

        return $inventoryDao->getScheduleTable($teamId, $from, $to, $scheduleTypeId, $officeId);
    }

    /**
     * @param int $userId
     * @param string $from
     * @param string $to
     * @return \DDD\Domain\User\Schedule\Inventory[]
     */
    public function getUserScheduleInRange($userId = 0, $from = '', $to = '')
    {
        /** @var \DDD\Dao\User\Schedule\Inventory $inventoryDao */
        $inventoryDao = $this->getServiceLocator()->get('dao_user_schedule_inventory');

        return $inventoryDao->getUserScheduleInRange($userId, $from, $to);
    }

    /**
     * @param int $userId
     * @param \DateTime $datetime
     * @return bool
     */
    public function isUserWorking($userId, $datetime)
    {
        /** @var \DDD\Dao\User\Schedule\Inventory $inventoryDao */
        $inventoryDao = $this->getServiceLocator()->get('dao_user_schedule_inventory');

        return $inventoryDao->isUserWorking($userId, $datetime);
    }
}
