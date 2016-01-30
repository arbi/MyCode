<?php

namespace DDD\Domain\Recruitment\Interview;

/**
 * Class Interview
 * @package DDD\Domain\Recruitment\Interview
 *
 * @author Tigran Petrosyan
 */
class Interview
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $applicantId;

    private $status;
    private $from;
    private $to;
    private $place;
    private $interviewerId;
    private $interviewerFirstName;
    private $interviewerLastName;

    /**
     * @var string
     */
    protected $date;

    public function exchangeArray($data)
    {
        $this->id                   = (isset($data['id'])) ? $data['id'] : null;
        $this->applicantId          = (isset($data['applicant_id'])) ? $data['applicant_id'] : null;
        $this->status               = (isset($data['status'])) ? $data['status'] : null;
        $this->from                 = (isset($data['from'])) ? $data['from'] : null;
        $this->to                   = (isset($data['to'])) ? $data['to'] : null;
        $this->place                = (isset($data['place'])) ? $data['place'] : null;
        $this->interviewerId        = (isset($data['interviewer_id'])) ? $data['interviewer_id'] : null;
        $this->interviewerFirstName = (isset($data['interviewer_first_name'])) ? $data['interviewer_first_name'] : null;
        $this->interviewerLastName  = (isset($data['interviewer_last_name'])) ? $data['interviewer_last_name'] : null;
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
     * @return int
     */
    public function getInterviewerId()
    {
        return $this->interviewerId;
    }

    /**
     * @return int
     */
    public function getInterviewerName()
    {
        return $this->interviewerFirstName . ' ' . $this->interviewerLastName;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
