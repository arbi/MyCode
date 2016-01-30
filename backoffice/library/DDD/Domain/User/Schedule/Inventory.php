<?php

namespace DDD\Domain\User\Schedule;
use DDD\Service\User\Vacation;

/**
 * Class Inventory
 * @package DDD\Domain\User\Inventory
 */
class Inventory
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
     * @var int|null $officeId
     */
    private $officeId;

    /**
     * @var string|null $userFirstName
     */
    private $userFirstName;

    /**
     * @var string|null $userLastName
     */
    private $userLastName;

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
     * @var string|null $date
     */
    private $date;

    /**
     * @var int|null $availability
     */
    private $availability;

    /**
     * @var int|null $isChanged
     */
    private $isChanged;

    /**
     * @var int|null $managerId
     */
    private $managerId;

    /**
     * @var int|null $vacationType
     */
    private $vacationType;
    /**
     * @var string|null $note
     */
    private $note;

    /**
     * @var int $inventoryColorId
     */
    private $inventoryColorId;

    public function exchangeArray($data)
    {
        $this->id               = (isset($data['id']))             ? $data['id'] : null;
        $this->userId           = (isset($data['user_id']))        ? $data['user_id'] : null;
        $this->officeId         = (isset($data['office_id']))      ? $data['office_id'] : null;
        $this->userFirstName    = (isset($data['user_firstname'])) ? $data['user_firstname'] : null;
        $this->userLastName     = (isset($data['user_lastname']))  ? $data['user_lastname'] : null;
        $this->timeFrom1        = (isset($data['time_from1']))     ? $data['time_from1'] : null;
        $this->timeFrom2        = (isset($data['time_from2']))     ? $data['time_from2'] : null;
        $this->timeTo1          = (isset($data['time_to1']))       ? $data['time_to1'] : null;
        $this->timeTo2          = (isset($data['time_to2']))       ? $data['time_to2'] : null;
        $this->date             = (isset($data['date']))           ? $data['date'] : null;
        $this->availability     = (isset($data['availability']))   ? $data['availability'] : null;
        $this->isChanged        = (isset($data['is_changed']))     ? $data['is_changed'] : null;
        $this->managerId        = (isset($data['manager_id']))     ? $data['manager_id'] : null;
        $this->vacationType     = (isset($data['vacation_type']))  ? $data['vacation_type'] : null;
        $this->inventoryColorId = (isset($data['color_id']))       ? $data['color_id'] : 0;
        $this->note             = (isset($data['note']))       ? $data['note'] : 0;
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
     * @return int|null
     */
    public function getOfficeId()
    {
        return $this->officeId;
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
    public function isAvailable()
    {
        return $this->availability ? true : false;
    }

    /**
     * @return float
     */
    public function getAvailability()
    {
        return $this->availability;
    }

    /**
     * @return int|null
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return null|string
     */
    public function getUserFirstName()
    {
        return $this->userFirstName;
    }

    /**
     * @return null|string
     */
    public function getUserLastName()
    {
        return $this->userLastName;
    }

    /**
     * @return null|string
     */
    public function getUserFullName()
    {
        return $this->userFirstName . ' ' . $this->userLastName;
    }

    /**
     * @return int|null
     */
    public function isChanged()
    {
        return $this->isChanged;
    }

    /**
     * @return null|int
     */
    public function getManagerId()
    {
        return $this->managerId;
    }

    /**
     * @return int|null
     */
    public function getVacationType()
    {
        return $this->vacationType;
    }

    /**
     * @return string|null
     */
    public function getVacationTypeText()
    {
        return ($this->vacationType ? Vacation::getVacationTypeOptions()[$this->vacationType] : '');
    }

    /**
     * @return int
     */
    public function getInventoryColorId()
    {
        return $this->inventoryColorId;
    }

    /**
     * @return int
     */
    public function getNote()
    {
        return $this->note;
    }
}
