<?php

namespace DDD\Domain\User\Evaluation;

/**
 * Class Evaluation
 * @package DDD\Domain\User\Evaluation
 */
class Evaluation
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var int
     */
    private $creatorId;

    /**
     * @var int
     */
    private $status;

    /**
     * @var int
     */
    private $typeId;

    /**
     * @var string
     */
    private $dateCreated;

    /**
     * @var text
     */
    private $description;

    /**
     * @param $data []
     */
    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->userId = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->creatorId = (isset($data['creator_id'])) ? $data['creator_id'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->typeId = (isset($data['type_id'])) ? $data['type_id'] : null;
        $this->dateCreated = (isset($data['date_created'])) ? $data['date_created'] : null;
        $this->description = (isset($data['description'])) ? $data['description'] : null;
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
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @return \DDD\Domain\User\Evaluation\text
     */
    public function getDescription()
    {
        return $this->description;
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
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }
}
