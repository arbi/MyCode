<?php

namespace DDD\Domain\Recruitment\Interview;

/**
 * Class InterviewParticipant
 * @package DDD\Domain\Recruitment\Interview
 *
 * @author Tigran Petrosyan
 */
class InterviewParticipant
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $interviewId;

    /**
     * @var int
     */
    protected $interviewerId;

    public function exchangeArray($data)
    {
        $this->id               = (isset($data['id'])) ? $data['id'] : null;
        $this->interviewId      = (isset($data['interview_id'])) ? $data['interview_id'] : null;
        $this->interviewerId    = (isset($data['interviewer_id'])) ? $data['interviewer_id'] : null;
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
     * @param int $interviewId
     */
    public function setInterviewId($interviewId)
    {
        $this->interviewId = $interviewId;
    }

    /**
     * @return int
     */
    public function getInterviewId()
    {
        return $this->interviewId;
    }

    /**
     * @param int $interviewerId
     */
    public function setInterviewerId($interviewerId)
    {
        $this->interviewerId = $interviewerId;
    }

    /**
     * @return int
     */
    public function getInterviewerId()
    {
        return $this->interviewerId;
    }
}
