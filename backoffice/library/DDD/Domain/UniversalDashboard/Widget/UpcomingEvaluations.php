<?php

namespace DDD\Domain\UniversalDashboard\Widget;

/**
 * Class UpcomingEvaluations
 * @package DDD\Domain\User\Evaluation
 */
class UpcomingEvaluations
{
    /**
     * @var int
     */
    private $nextPlannedEvaluationId;

    /**
     * @var int
     */
    private $employeeId;

    /**
     * @var string
     */
    protected $employeeFirstName;

    /**
     * @var string
     */
    protected $employeeLastName;

    /**
     * @var string
     */
    protected $employeeFullName;

    /**
     * @var string
     */
    protected $creatorFirstName;

    /**
     * @var string
     */
    protected $creatorLastName;

    /**
     * @var string
     */
    protected $creatorFullName;

    /**
     * @var string
     */
    private $datePlanned;

    /**
     * @var int
     */
    private $disabled;

    /**
     * @param $data []
     */
    public function exchangeArray($data)
    {
        $this->nextPlannedEvaluationId = (isset($data['id'])) ? $data['id'] : null;
        $this->employeeId              = (isset($data['employee_id'])) ? $data['employee_id'] : null;
        $this->employeeFirstName       = (isset($data['employee_first_name'])) ? $data['employee_first_name'] : null;
        $this->employeeLastName        = (isset($data['employee_last_name'])) ? $data['employee_last_name'] : null;
        $this->creatorFirstName        = (isset($data['creator_first_name'])) ? $data['creator_first_name'] : null;
        $this->creatorLastName         = (isset($data['creator_last_name'])) ? $data['creator_last_name'] : null;
        $this->datePlanned             = (isset($data['date_created'])) ? $data['date_created'] : null;
        $this->disabled                = (isset($data['disabled'])) ? $data['disabled'] : null;
    }

    /**
     * @return string
     */
    public function getCreatorFirstName()
    {
        return $this->creatorFirstName;
    }

    /**
     * @return string
     */
    public function getCreatorFullName()
    {
        return $this->getCreatorFirstName() . ' ' . $this->getCreatorLastName();
    }

    /**
     * @return string
     */
    public function getCreatorLastName()
    {
        return $this->creatorLastName;
    }

    /**
     * @return string
     */
    public function getDatePlanned()
    {
        return $this->datePlanned;
    }

    /**
     * @return string
     */
    public function getEmployeeFirstName()
    {
        return $this->employeeFirstName;
    }

    /**
     * @return string
     */
    public function getEmployeeFullName()
    {
        return $this->getEmployeeFirstName() . ' ' . $this->getEmployeeLastName();
    }

    /**
     * @return int
     */
    public function getEmployeeId()
    {
        return $this->employeeId;
    }

    /**
     * @return string
     */
    public function getEmployeeLastName()
    {
        return $this->employeeLastName;
    }

    /**
     * @return int
     */
    public function getNextPlannedEvaluationId()
    {
        return $this->nextPlannedEvaluationId;
    }

    /**
     * @return int
     */
    public function getDisabled()
    {
        return $this->disabled;
    }
}
