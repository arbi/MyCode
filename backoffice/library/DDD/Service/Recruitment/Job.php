<?php

namespace DDD\Service\Recruitment;

use DDD\Service\ServiceBase;

final class Job extends ServiceBase
{
    const DRAFT_STATUS    = 1;
    const LIVE_STATUS     = 2;
    const INACTIVE_STATUS = 3;

    public function getJobList($offset, $limit, $sortCol, $sortDir, $like, $all) {
        return $this->getJobDao()->getJobList($offset, $limit, $sortCol, $sortDir, $like, $all);
    }

    public function getJobCount($like, $all)
    {
        return $this->getJobDao()->getJobCount($like, $all);
    }

    public function getJobOptions($currentData = null)
    {
        /**
         * @var \DDD\Service\User $userService
         * @var \DDD\Service\Team\Usages\Hiring $teamUsageHiringService
         */
        $userService = $this->getServiceLocator()->get('service_user');
        $teamUsageHiringService = $this->getServiceLocator()->get('service_team_usages_hiring');

        $peopleList = $userService->getPeopleList();
        $teamList   = $teamUsageHiringService->getTeamsByUsage();

        return [
            'ginosiksList'  => $peopleList,
            'teamList'      => $teamList
        ];
    }

    public function getData($id)
    {
        $userService = $this->getServiceLocator()->get('service_user');
        $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

        $activeUsers = $userService->getPeopleList();
        $job = $this->getJobDao()->fetchOne(['id' => $id]);
        $hiringManagerId = null;
        $users = [];

        if (!$job) {
            return false;
        }

        foreach ($activeUsers as $user) {
            $users[$user['id']] = $user['firstname'] . ' ' . $user['lastname'];
        }

        if (!array_key_exists($job->getHiringManagerId(), $users)) {
            $disabledUser = $userManagerDao->fetchOne([
                'id'       => $job->getHiringManagerId(),
                'disabled' => 1,
            ]);

            if ($disabledUser) {
                $hiringManagerId = [
                    $disabledUser->getId() => $disabledUser->getFirstName() . ' ' . $disabledUser->getLastName(),
                ];
             }
        }

        return [
            'job' => $job,
            'disabledHM' => $hiringManagerId,
        ];

    }

    /**
     * @param $id
     * @return array|\DDD\Dao\Recruitment\Job\Job|null
     */
    public function getJobById($id)
    {
        return $this->getJobDao()->fetchOne(['id' => $id]);
    }

    public function jobSave($data, $id)
    {
        $jobDao = $this->getJobDao();

        if ($id) {
            $jobDao->save($data, ['id' => (int)$id]);
            return true;
        } else {
            return $jobDao->save($data);
        }
    }

    public function deleteJob($id)
    {
        if ($id) {
            $this->getJobDao()->deleteWhere(['id' => (int)$id]);

            return true;
        }

        return false;
    }

    public function changeActStatusJob($data, $id)
    {
        if ($id) {
            $this->getJobDao()->save($data,['id' => (int)$id]);
            return true;
        }

        return false;
    }

    /**
     * @return \DDD\Dao\Recruitment\Job\Job
     */
    public function getJobDao()
    {
        if (!isset($this->daoJob)) {
            $this->daoJob = $this->getServiceLocator()->get('dao_recruitment_job_job');
        }

        return $this->daoJob;
    }
}
