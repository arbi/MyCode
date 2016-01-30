<?php

namespace DDD\Domain\Recruitment\Job;

use Library\Constants\Constants;

/**
 * Class Job
 * @package DDD\Domain\Job\Job
 *
 * @author Tigran Petrosyan
 */
class Job
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $countryId;

    /**
     * @var int
     */
    protected $provinceId;

    /**
     * @var int
     */
    protected $cityId;


    /**
     * @var int
     */
    protected $hiringManagerId;

    /**
     * @var int
     */
    protected $departmentId;

    /**
     * @var string
     */
    protected $startDate;

    /**
     * @var string
     */
    protected $title;

     /**
     * @var string
     */
    protected $subtitle;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $metaDescription;

    /**
     * @var string
     */
    protected $requirements;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $department;

    /**
     * @var int
     */
    protected $cvRequired;

    /**
     * @var int
     */
    protected $notifyManager;
    /**
     * @var int
     */
    protected $status;

    /**
     * @var string
     */
    protected $slug;

    /**
     * @var string
     */
    protected $hiringTeamId;

    public function exchangeArray($data)
    {
        $this->id              = (isset($data['id'])) ? $data['id'] : null;
        $this->countryId       = (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->provinceId      = (isset($data['province_id'])) ? $data['province_id'] : null;
        $this->cityId          = (isset($data['city_id'])) ? $data['city_id'] : null;
        $this->hiringManagerId = (isset($data['hiring_manager_id'])) ? $data['hiring_manager_id'] : null;
        $this->departmentId    = (isset($data['department_id'])) ? $data['department_id'] : null;
        $this->startDate       = (isset($data['start_date'])) ? $data['start_date'] : null;
        $this->title           = (isset($data['title'])) ? $data['title'] : null;
        $this->subtitle        = (isset($data['subtitle'])) ? $data['subtitle'] : null;
        $this->description     = (isset($data['description'])) ? $data['description'] : null;
        $this->metaDescription = (isset($data['meta_description'])) ? $data['meta_description'] : null;
        $this->requirements    = (isset($data['requirements'])) ? $data['requirements'] : null;
        $this->city            = (isset($data['city'])) ? $data['city'] : null;
        $this->department      = (isset($data['department'])) ? $data['department'] : null;
        $this->status          = (isset($data['status'])) ? $data['status'] : null;
        $this->cvRequired      = (isset($data['cv_required'])) ? $data['cv_required'] : null;
        $this->notifyManager   = (isset($data['notify_manager'])) ? $data['notify_manager'] : null;
        $this->slug            = (isset($data['slug'])) ? $data['slug'] : null;
        $this->hiringTeamId    = (isset($data['hiring_team_id'])) ? $data['hiring_team_id'] : null;
    }

     /**
     * @param int $countryId
     */
    public function setCountryId($countryId)
    {
        $this->countryId = $countryId;
    }

    /**
     * @return int
     */
    public function getCountryId()
    {
        return $this->countryId;
    }

     /**
     * @param int $provinceId
     */
    public function setProvinceId($provinceId)
    {
        $this->provinceId = $provinceId;
    }

    /**
     * @return int
     */
    public function getProvinceId()
    {
        return $this->provinceId;
    }


    /**
     * @param int $cityId
     */
    public function setCityId($cityId)
    {
        $this->cityId = $cityId;
    }

    /**
     * @return int
     */
    public function getCityId()
    {
        return $this->cityId;
    }

    /**
     * @param int $departmentId
     */
    public function setDepartmentId($departmentId)
    {
        $this->departmentId = $departmentId;
    }

    /**
     * @return int
     */
    public function getDepartmentId()
    {
        return $this->departmentId;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * @param int $hiringManagerId
     */
    public function setHiringManagerId($hiringManagerId)
    {
        $this->hiringManagerId = $hiringManagerId;
    }

    /**
     * @return int
     */
    public function getHiringManagerId()
    {
        return $this->hiringManagerId;
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
     * @param string $requirements
     */
    public function setRequirements($requirements)
    {
        $this->requirements = $requirements;
    }

    /**
     * @return string
     */
    public function getRequirements()
    {
        return $this->requirements;
    }

    /**
     * @param string $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return string
     */
    public function getStartDate()
    {
        if ($this->startDate != '0000-00-00') {
            $this->startDate = date(Constants::GLOBAL_DATE_FORMAT, strtotime($this->startDate));
        } else {
            $this->startDate = '';
        }
        return $this->startDate;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $subtitle
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
    }

    /**
     * @return string
     */
    public function getSubtitle()
    {
        return $this->subtitle;
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
     * @param int $cvRequired
     */
    public function setCvRequired($cvRequired)
    {
        $this->cvRequired = $cvRequired;
    }

    /**
     * @return int
     */
    public function getCvRequired()
    {
        return $this->cvRequired;
    }

    /**
     * @return int
     */
    public function getNotifyManager()
    {
        return $this->notifyManager;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getHiringTeamId()
    {
        return $this->hiringTeamId;
    }
}
