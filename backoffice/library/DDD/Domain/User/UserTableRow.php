<?php

namespace DDD\Domain\User;
use Library\Utility\DateLocal;
use Library\Constants\Constants;
/**
 * Domain class to use in all cases when we need basic information about user not all row.
 * For example user's table.
 * @final
 * @category core
 * @package domain
 *
 * @author Tigran Petrosyan
 */
final class UserTableRow
{
	/**
	 * @var int
	 */
    protected $id;

    /**
     * @var int
     */
    protected $managerID;

    /**
     * @var string
     */
    protected $firstName;

    /**
     * @var string
     */
    protected $lastName;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $position;

    /**
     * @var string
     */
    protected $department;

    /**
     * @var string
     */
    protected $startDate;

    /**
     * @var string
     */
    protected $endDate;

    /**
     * @var float
     */
    protected $vacationDaysLeft;

    /**
     * @var int
     */
    protected $vacationDaysPerYear;

    /**
     * @var string
     */
    protected $previousEvaluation;

    /**
     * @var string
     */
    protected $nextEvaluation;

    /**
     * @var int
     */
    protected $periodOfEvaluation;

    /**
     * @var string
     */
    protected $disabled;

    /**
     * @var string
     */
    protected $avatar;

    /**
     * This method called automatically when returning something from DAO.
     *
     * @param array $data
     */
    public function exchangeArray($data)
    {
        $this->id                  = (isset($data['id'])) ? $data['id'] : null;
        $this->firstName           = (isset($data['firstname'])) ? $data['firstname'] : null;
        $this->lastName            = (isset($data['lastname'])) ? $data['lastname'] : null;
        $this->city                = (isset($data['city'])) ? $data['city'] : null;
        $this->position            = (isset($data['position'])) ? $data['position'] : null;
        $this->department          = (isset($data['department'])) ? $data['department'] : null;
        $this->managerID           = (isset($data['manager_id'])) ? $data['manager_id'] : null;
        $this->startDate           = (isset($data['start_date'])) ? $data['start_date'] : null;
        $this->endDate             = (isset($data['end_date'])) ? $data['end_date'] : null;
        $this->vacationDaysLeft    = (isset($data['vacation_days'])) ? $data['vacation_days'] : null;
        $this->vacationDaysPerYear = (isset($data['vacation_days_per_year'])) ? $data['vacation_days_per_year'] : null;
        $this->previousEvaluation  = (isset($data['previous_evaluation'])) ? $data['previous_evaluation'] : null;
        $this->nextEvaluation      = (isset($data['next_evaluation'])) ? $data['next_evaluation'] : null;
        $this->periodOfEvaluation  = (isset($data['period_of_evaluation'])) ? $data['period_of_evaluation'] : null;
        $this->disabled            = (isset($data['disabled'])) ? $data['disabled'] : null;
        $this->avatar              = (isset($data['avatar'])) ? $data['avatar'] : null;
    }

    /**
     * Get user id
     *
     * @return int
     */
    public function getId()
    {
    	return (int)$this->id;
    }

    /**
     * Get user manager id
     *
     * @return int
     */
    public function getManagerId()
    {
    	return (int)$this->managerID;
    }

    /**
     * Get user first name
     *
     * @return string
     */
    public function getFirstName()
    {
    	return $this->firstName;
    }

    /**
     * Get user last name
     *
     * @return string
     */
    public function getLastName()
    {
    	return $this->lastName;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Get user position
     *
     * @return string
     */
    public function getPosition()
    {
    	return $this->position;
    }

    /**
     * Get user department
     *
     * @return string
     */
    public function getDepartment()
    {
    	return $this->department;
    }

    /**
     * @return string
     */
    public function getEndDate()
    {
        return $this->endDate == '0000-00-00' ? '--' : date(Constants::GLOBAL_DATE_FORMAT, strtotime($this->endDate));
    }

    /**
     * @return string
     */
    public function getStartDate()
    {
        return $this->startDate == '0000-00-00' ? '--' : date(Constants::GLOBAL_DATE_FORMAT, strtotime($this->startDate));
    }

    /**
     * @return int
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     *
     * @return float
     */
    function getVacationDaysLeft()
    {
        return $this->vacationDaysLeft;
    }

    /**
     *
     * @return int
     */
    function getVacationDaysPerYear()
    {
        return $this->vacationDaysPerYear;
    }

    /**
     *
     * @return string
     */
    function getPreviousEvaluation()
    {
        return $this->previousEvaluation;
    }

    /**
     *
     * @return string
     */
    function getNextEvaluation()
    {
        return $this->nextEvaluation;
    }

    /**
     *
     * @return int
     */
    function getPeriodOfEvaluation()
    {
        return $this->periodOfEvaluation;
    }

    /**
     *
     * @return string
     */
    function getAvatar()
    {
        return $this->avatar;
    }
}
