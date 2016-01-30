<?php

namespace DDD\Domain\User\Schedule;

/**
 * Class Schedule
 * @package DDD\Domain\User\Schedule
 */
class Schedule
{
    /**
     * @var int|null $id
     */
    private $id;

    /**
     * @var int|null $userId
     */
    private $userId;

    /**
     * @var string|null $timeFrom1
     */
    private $timeFrom1;

    /**
     * @var string|null $timeFrom2
     */
    private $timeFrom2;

    /**
     * @var string|null $timeTo1
     */
    private $timeTo1;

    /**
     * @var string|null $timeTo2
     */
    private $timeTo2;

    /**
     * @var int|null $day
     */
    private $day;

    /**
     * @var int|null $active
     */
    private $active;

    public function exchangeArray($data)
    {
        $this->id        = (isset($data['id']))         ? $data['id'] : null;
        $this->userId    = (isset($data['user_id']))    ? $data['user_id'] : null;
        $this->timeFrom1 = (isset($data['time_from1'])) ? $data['time_from1'] : null;
        $this->timeFrom2 = (isset($data['time_from2'])) ? $data['time_from2'] : null;
        $this->timeTo1   = (isset($data['time_to1']))   ? $data['time_to1'] : null;
        $this->timeTo2   = (isset($data['time_to2']))   ? $data['time_to2'] : null;
        $this->day       = (isset($data['day']))        ? $data['day'] : null;
        $this->active    = (isset($data['active']))     ? $data['active'] : null;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return null|string
     */
    public function getTimeFrom1()
    {
        return $this->timeFrom1;
    }

    /**
     * @return null|string
     */
    public function getTimeFrom2()
    {
        return $this->timeFrom2;
    }

    /**
     * @return null|string
     */
    public function getTimeTo1()
    {
        return $this->timeTo1;
    }

    /**
     * @return null|string
     */
    public function getTimeTo2()
    {
        return $this->timeTo2;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active ? true : false;
    }

    /**
     * @return int|null
     */
    public function getDay()
    {
        return $this->day;
    }
}
