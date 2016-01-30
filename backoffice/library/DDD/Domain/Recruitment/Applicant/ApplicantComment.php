<?php

namespace DDD\Domain\Recruitment\Applicant;

/**
 * Class ApplicantComment
 * @package DDD\Domain\Recruitment\Applicant
 */
class ApplicantComment
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $applicantId;

    /**
     * @var int
     */
    protected $commenterId;

    /**
     * @var string
     */
    protected $commenterFirstName;

    /**
     * @var string
     */
    protected $commenterLastName;

    /**
     * @var string
     */
    protected $comment;

    /**
     * @var string
     */
    protected $commenterPosition;

    /**
     * @var string
     */
    protected $date;

    /**
     * @var bool
     */
    protected $hrOnly;

    public function exchangeArray($data)
    {
        $this->id          = (isset($data['id'])) ? $data['id'] : null;
        $this->applicantId = (isset($data['applicant_id'])) ? $data['applicant_id'] : null;
        $this->commenterId = (isset($data['commenter_id'])) ? $data['commenter_id'] : null;
        $this->commenterPosition    = (isset($data['position'])) ? $data['position'] : null;
        $this->commenterFirstName   = (isset($data['commenter_first_name'])) ? $data['commenter_first_name'] : null;
        $this->commenterLastName    = (isset($data['commenter_last_name'])) ? $data['commenter_last_name'] : null;
        $this->comment     = (isset($data['comment'])) ? $data['comment'] : null;
        $this->date        = (isset($data['date'])) ? $data['date'] : null;
        $this->hrOnly      = (isset($data['hr_only_comment'])) ? $data['hr_only_comment'] : null;
    }

    /**
     * @param int $applicantId
     */
    public function setApplicantId($applicantId)
    {
        $this->applicantId = $applicantId;
    }

    /**
     * @return int
     */
    public function getApplicantId()
    {
        return $this->applicantId;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $commenterFirstName
     */
    public function setCommenterFirstName($commenterFirstName)
    {
        $this->commenterFirstName = $commenterFirstName;
    }

    /**
     * @return string
     */
    public function getCommenterFirstName()
    {
        return $this->commenterFirstName;
    }

    /**
     * @param int $commenterId
     */
    public function setCommenterId($commenterId)
    {
        $this->commenterId = $commenterId;
    }

    /**
     * @return int
     */
    public function getCommenterId()
    {
        return $this->commenterId;
    }

    /**
     * @param string $commenterLastName
     */
    public function setCommenterLastName($commenterLastName)
    {
        $this->commenterLastName = $commenterLastName;
    }

    /**
     * @return string
     */
    public function getCommenterLastName()
    {
        return $this->commenterLastName;
    }

    /**
     * @param string $commenterPosition
     */
    public function setCommenterPosition($commenterPosition)
    {
        $this->commenterPosition = $commenterPosition;
    }

    /**
     * @return string
     */
    public function getCommenterPosition()
    {
        return $this->commenterPosition;
    }

    /**
     * @param string $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCommenterFullName()
    {
        return $this->commenterFirstName . " " . $this->commenterLastName;
    }

    /**
     * @return boolean
     */
    public function getHrOnly()
    {
        return $this->hrOnly;
    }
}
