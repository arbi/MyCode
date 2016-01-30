<?php

namespace DDD\Domain\Recruitment\Applicant;

/**
 * Class Applicant
 * @package DDD\Domain\Recruitment\Applicant
 */
class Applicant
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $jobId;

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
    protected $email;

    /**
     * @var string
     */
    protected $phone;

    /**
     * @var string
     */
    protected $skype;

    /**
     * @var string
     */
    protected $motivation;

    /**
     * @var string
     */
    protected $referredBy;

    /**
     * @var string
     */
    protected $cvFileName;

    /**
     * @var string
     */
    protected $cvFileUrl;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var int
     */
    protected $dateApplied;

    /**
     * @var string
     */
    protected $position;

    /**
     * @var string
     */
    protected $jobCity;

    /**
     * @var
     */
    protected $hiringTeamId;

    public function exchangeArray($data)
    {
        $this->id          = (isset($data['id'])) ? $data['id'] : null;
        $this->jobId       = (isset($data['job_id'])) ? $data['job_id'] : null;
        $this->firstName   = (isset($data['firstname'])) ? $data['firstname'] : null;
        $this->lastName    = (isset($data['lastname'])) ? $data['lastname'] : null;
        $this->email       = (isset($data['email'])) ? $data['email'] : null;
        $this->phone       = (isset($data['phone'])) ? $data['phone'] : null;
        $this->skype       = (isset($data['skype'])) ? $data['skype'] : null;
        $this->motivation  = (isset($data['motivation'])) ? $data['motivation'] : null;
        $this->referredBy  = (isset($data['referred_by'])) ? $data['referred_by'] : null;
        $this->cvFileName  = (isset($data['cv'])) ? $data['cv'] : null;
        $this->cvFileUrl  = (isset($data['cv'])) ?
             str_replace('-', '/', substr($data['date_applied'], 0, strrpos($data['date_applied'], '-'))) . '/' . $data['cv'] : null;
        $this->status      = (isset($data['status'])) ? $data['status'] : null;
        $this->dateApplied = (isset($data['date_applied'])) ? $data['date_applied'] : null;
        $this->position    = (isset($data['position'])) ? $data['position'] : null;
        $this->jobCity    = (isset($data['job_city'])) ? $data['job_city'] : null;
        $this->hiringTeamId = (isset($data['hiring_team_id'])) ? $data['hiring_team_id'] : null;
    }

    /**
     * @param string $cvFileName
     */
    public function setCvFileName($cvFileName)
    {
        $this->cvFileName = $cvFileName;
    }

    /**
     * @return string
     */
    public function getCvFileName()
    {
        return $this->cvFileName;
    }

    /**
     * @param string $cvFileUrl
     */
    public function setCvFileUrl($cvFileUrl)
    {
        $this->cvFileUrl = $cvFileUrl;
    }

    /**
     * @return string
     */
    public function getCvFileUrl()
    {
        return $this->cvFileUrl;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $firstName
     */
    public function setFirstname($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstName;
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
     * @param string $lastName
     */
    public function setLastname($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastName;
    }

    /**
     * @param string $motivation
     */
    public function setMotivation($motivation)
    {
        $this->motivation = $motivation;
    }

    /**
     * @return string
     */
    public function getMotivation()
    {
        return $this->motivation;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $referredBy
     */
    public function setReferredBy($referredBy)
    {
        $this->referredBy = $referredBy;
    }

    /**
     * @return string
     */
    public function getReferredBy()
    {
        return $this->referredBy;
    }

    /**
     * @param string $skype
     */
    public function setSkype($skype)
    {
        $this->skype = $skype;
    }

    /**
     * @return string
     */
    public function getSkype()
    {
        return $this->skype;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $jobId
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * @return int
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * @param string $dateApplied
     */
    public function setDateApplied($dateApplied)
    {
        $this->dateApplied = $dateApplied;
    }

    /**
     * @return string
     */
    public function getDateApplied()
    {
        return $this->dateApplied;
    }

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function getJobCity()
    {
        return $this->jobCity;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->firstName . ' ' . $this->getLastname();
    }

    /**
     * @return mixed
     */
    public function getHiringTeamId()
    {
        return $this->hiringTeamId;
    }
}
