<?php

namespace DDD\Service\Website;

use DDD\Service\ServiceBase;
use DDD\Service\Recruitment\Job as JobService;

/**
 * Class Job
 * @package DDD\Service\Recruitment
 */
final class Job extends ServiceBase
{
    /** @var \DDD\Dao\Recruitment\Job\Job $daoJob */
    private $daoJob = null;
    /** @var \DDD\Dao\Recruitment\Applicant\Applicant $daoApplicant */
    private $daoApplicant = null;

    public function getJobsForWebsite()
    {
        $this->getJobDao();
        return $this->daoJob->getJobsForWebsite();
    }

    /**
     * @param $slug
     * @return \DDD\Domain\Recruitment\Job\Job | bool
     */
    public function getJobAnnouncementBySlugDate($slug)
    {
        $this->getJobDao();

        if ($slug) {
            return $this->daoJob->fetchOne(['slug' => $slug, 'status' => JobService::LIVE_STATUS]);
        } else {
            return false;
        }
    }

    public function saveApplicant($data) {
        $this->getApplicantDao();
        $applicantId = $this->daoApplicant->save($data);

        return $applicantId;
    }

    /**
     * @return \DDD\Dao\Recruitment\Job\Job
     */
    public function getJobDao()
    {
        if (!($this->daoJob)) {
            $this->daoJob = $this->getServiceLocator()->get('dao_recruitment_job_job');
        }

        return $this->daoJob;
    }

    /**
     * @return \DDD\Dao\Recruitment\Applicant\Applicant
     */
    public function getApplicantDao()
    {
        if (!($this->daoApplicant)) {
            $this->daoApplicant = $this->getServiceLocator()->get('dao_recruitment_applicant_applicant');
        }
        
        return $this->daoApplicant;
    }
}
