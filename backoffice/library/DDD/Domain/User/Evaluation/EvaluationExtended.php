<?php

namespace DDD\Domain\User\Evaluation;

/**
 * Class EvaluationExtended
 * @package DDD\Domain\User\Evaluation
 */
class EvaluationExtended
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $dateCreated;

    /**
     * @var int
     */
    private $creatorId;

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
    private $creatorPosition;

    /**
     * @var
     */
    private $userId;

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
     * @var int
     */
    protected $status;

    /**
     * @var int
     */
    private $typeId;

    /**
     * @var float
     */
    private $averageScore;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $typeTitle;

    /**
     * @param $data []
     */
    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->dateCreated = (isset($data['date_created'])) ? $data['date_created'] : null;
        $this->creatorId = (isset($data['creator_id'])) ? $data['creator_id'] : null;
        $this->creatorFirstName = (isset($data['creator_first_name'])) ? $data['creator_first_name'] : null;
        $this->creatorLastName = (isset($data['creator_last_name'])) ? $data['creator_last_name'] : null;
        $this->creatorPosition = (isset($data['creator_position'])) ? $data['creator_position'] : null;
        $this->userId = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->employeeFirstName = (isset($data['employee_first_name'])) ? $data['employee_first_name'] : null;
        $this->employeeLastName = (isset($data['employee_last_name'])) ? $data['employee_last_name'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->typeId = (isset($data['type_id'])) ? $data['type_id'] : null;
        $this->averageScore = (isset($data['average'])) ? $data['average'] : null;
        $this->description = (isset($data['description'])) ? $data['description'] : null;
        $this->typeTitle = (isset($data['type_title'])) ? $data['type_title'] : null;
    }

    /**
     * @return float
     */
    public function getAverageScore()
    {
        return $this->averageScore;
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
     * @return int
     */
    public function getCreatorId()
    {
        return $this->creatorId;
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
    public function getCreatorPosition()
    {
        return $this->creatorPosition;
    }

    /**
     * @return string
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
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
     * @return string
     */
    public function getEmployeeLastName()
    {
        return $this->employeeLastName;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @return string
     */
    public function getTypeTitle()
    {
        return $this->typeTitle;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }
}
