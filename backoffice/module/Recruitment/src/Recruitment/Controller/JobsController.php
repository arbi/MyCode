<?php

namespace Recruitment\Controller;

use DDD\Service\Recruitment\Job;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

use Library\Constants\Constants;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Library\Constants\Roles;

use Recruitment\Form\Job as JobForm;
use Recruitment\Form\InputFilter\JobFilter;

use DDD\Service\Location as LocationService;

class JobsController extends ControllerBase
{
    public function indexAction()
    {
        $auth   = $this->getServiceLocator()->get('library_backoffice_auth');
        $jobs   = null;

        if ($auth->hasRole(Roles::ROLE_JOB_MANAGEMENT)) {
            return new ViewModel(['ajaxSourceUrl' => '/recruitment/jobs/get-json']);
        } else {
            return $this->redirect()->toUrl('/');
        }
    }

    public function getJsonAction()
    {
        $auth       = $this->getServiceLocator()->get('library_backoffice_auth');
        $jobService = $this->getServiceLocator()->get('service_recruitment_job');
        $cityDao    = $this->getServiceLocator()->get('dao_geolocation_city');
        $usermngDao = $this->getServiceLocator()->get('dao_user_user_manager');

        $userId     = $auth->getIdentity()->id;
        $userInfo   = $usermngDao->getUserById($userId);

        $request = $this->params();

        $jobLists = $jobService->getJobList(
            (int)$request->fromQuery('iDisplayStart'),
            (int)$request->fromQuery('iDisplayLength'),
            (int)$request->fromQuery('iSortCol_0'),
            $request->fromQuery('sSortDir_0'),
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '2')
        );
        $jobCount = $jobService->getJobCount(
            $request->fromQuery('sSearch'),
            $request->fromQuery('all', '2')
        );

        $results = [];

        foreach ($jobLists as $jobList) {
            if (   $auth->hasRole(Roles::ROLE_JOB_MANAGEMENT)
                && !$auth->hasRole(Roles::ROLE_HIRING_MANAGER)
                && !$auth->hasRole(Roles::ROLE_HIRING_COUNTRY_MANAGER)
                && ($userId != $jobList->getHiringManagerId())
            ) {
                continue;
            }

            if (   $auth->hasRole(Roles::ROLE_JOB_MANAGEMENT)
                && !$auth->hasRole(Roles::ROLE_HIRING_MANAGER)
                && $auth->hasRole(Roles::ROLE_HIRING_COUNTRY_MANAGER)
            ) {
                $userCountry = $cityDao->getCountryIDByCityID($userInfo->getCity_id());

                if ($userCountry->getCountry_id() != $jobList->getCountryId() && $userId != $jobList->getHiringManagerId()) {
                    continue;
                }
            }

            $action = '<a href="/recruitment/jobs/edit/' . $jobList->getId() . '" class="btn btn-xs btn-primary" data-html-content="Edit"></a>';
            $description = strip_tags($jobList->getDescription());

            $limitDesc = $description;
            $descArray = explode(' ', $description);

            if (count($descArray) > 18) {
                $limitDesc = implode(' ', array_splice($descArray, 0, 18));
                $limitDesc .= ' ...';
            }

            array_push($results, [
                Constants::$jobStatus[$jobList->getStatus()],
                $jobList->getTitle(),
                $jobList->getDepartment(),
                $jobList->getCity(),
                $jobList->getStartDate(),
                htmlentities($limitDesc),
                $action,
            ]);
        }

        if (!isset($results)) {
            array_push($result, [' ', '', '', '', '', '', '', '', '']);
        }

        return new JsonModel([
            'sEcho'                => $request->fromQuery('sEcho'),
            'iTotalRecords'        => $jobCount,
            'iTotalDisplayRecords' => $jobCount,
            'iDisplayStart'        => $request->fromQuery('iDisplayStart'),
            'iDisplayLength'       => (int)$request->fromQuery('iDisplayLength'),
            'aaData'               => $results,
        ]);
    }

    public function editAction()
    {
        /**
         * @var \DDD\Service\Recruitment\Job $jobService
         * @var \DDD\Dao\Team\Team $userTeamDao
         */
        $auth        = $this->getServiceLocator()->get('library_backoffice_auth');
        $userService = $this->getServiceLocator()->get('service_user');
        $jobService  = $this->getServiceLocator()->get('service_recruitment_job');
        $cityDao     = $this->getServiceLocator()->get('dao_geolocation_city');
        $usermngDao  = $this->getServiceLocator()->get('dao_user_user_manager');
        $userTeamDao = $this->getServiceLocator()->get('dao_team_team');

        $userId      = $auth->getIdentity()->id;
        $userInfo    = $usermngDao->getUserById($userId);

        $id = (int)$this->params()->fromRoute('id', 0);

        $isGlobManager = null;
        $jobOption     = null;
        $form          = null;
        $jobStatus     = null;
        $hiringMng     = null;
        $isCountryMngr = null;
        $isHiringTeam  = false;
        $hiringTeam    = false;
        $hiringTeamId  = null;

        if ($id) {
            $job = $jobService->getJobById($id);

            if (!$job) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                return $this->redirect()->toRoute('recruitment/jobs');
            }

            $jobSlug = $job->getSlug();
        } else {
            $jobSlug = NULL;
        }

        $form = $this->getForm($id);

        $statusOptions = $form->get('status')->getValueOptions();
        $hrMngOptions  = $form->get('hiring_manager_id')->getValueOptions();

        if ($id) {
            $jobCountryId  = $form->get('country_id')->getValue();
            $hiringMngId   = $form->get('hiring_manager_id')->getValue();
            $jobStatus     = $form->get('status')->getValue();
            $jobOption     = $statusOptions[$jobStatus];
            $hiringMng     = $hrMngOptions[$hiringMngId];
            $isHiringTeam  = ($job->getHiringTeamId()) ? true : false;

            if ($isHiringTeam) {
                $hiringTeam = $userTeamDao->getTeamNameById($job->getHiringTeamId());
                $hiringTeamId = $job->getHiringTeamId();
            }
        }

        if ($auth->hasRole(Roles::ROLE_HIRING_MANAGER)) {
            $isGlobManager = true;
        }

        if ($auth->hasRole(Roles::ROLE_HIRING_COUNTRY_MANAGER)) {
            $isCountryMngr = true;
        }

        if (!$isGlobManager) {
            $jobOption   = 'Draft';
            $jobStatus   = 1;
            $hiringMngId = $userId;
            $hiringMng   = $hrMngOptions[$userId];
        }

        if (!$form && ($id)) {
            return $this->redirect()->toUrl('/');
        }

        if (   $id
            && $auth->hasRole(Roles::ROLE_JOB_MANAGEMENT)
            && !$isGlobManager
            && !$isCountryMngr
            && ($userId != $hiringMngId)
        ) {
            return $this->redirect()->toUrl('/');
        }

        if (   $id
            && $auth->hasRole(Roles::ROLE_JOB_MANAGEMENT)
            && !$isGlobManager
            && $isCountryMngr
        ) {
            $userCountry = $cityDao->getCountryIDByCityID($userInfo->getCity_id());

            if ($userCountry->getCountry_id() != $jobCountryId && $userId != $hiringMngId) {
                return $this->redirect()->toUrl('/');
            }
        }

        $ginosiks = $userService->getUsersList();

        return new ViewModel([
            'jobStatus'     => $jobStatus,
            'jobOption'     => $jobOption,
            'hiringMng'     => $hiringMng,
            'hiringMngId'   => !empty($hiringMngId) ? $hiringMngId : false,
            'userId'        => $userId,
            'jobForm'       => $form,
            'id'            => $id,
            'ginosikList'   => $ginosiks,
            'isGlobManager' => $isGlobManager,
            'isCountryMngr' => $isCountryMngr,
            'isHiringTeam'  => $isHiringTeam,
            'hiringTeam'    => $hiringTeam,
            'hiringTeamId'  => $hiringTeamId,
            'jobSlug'       => $jobSlug
        ]);
    }

    public function ajaxSaveAction()
    {
        /**
         * @var $countryDao \DDD\Dao\Geolocation\Countries
         * @var $countryDetails \DDD\Domain\Geolocation\Countries
         * @var $cityDao \DDD\Dao\Geolocation\City
         * @var $cityDetails \DDD\Domain\Geolocation\City
         */
        $jobService = $this->getServiceLocator()->get('service_recruitment_job');
        $countryDao = $this->getServiceLocator()->get('dao_geolocation_countries');
        $cityDao = $this->getServiceLocator()->get('dao_geolocation_city');

        $request = $this->getRequest();
        $result  = [
            'result' => [],
            'id'     => 0,
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $id       = (int)$request->getPost('job_id');
                $form     = $this->getForm($id);
                $messages = '';

                $data = $request->getPost();

                $form->setData($data);
                $form->setInputFilter(new JobFilter());

                if ($form->isValid()) {
                    $data = $form->getData();
                    $startDate = $data['start_date'] ? date('Y-m-d', strtotime($data['start_date'])) : '';

                    if ($result['status'] != 'error') {
                        if (empty($data['description']))  {
                            $result['status'] = 'error';
                            $result['msg'] = TextConstants::SERVER_ERROR;

                            return new JsonModel($result);
                        }

                        $countryDetails = $countryDao->getCountryById((int)$data['country_id']);
                        $cityDetails = $cityDao->getCityById((int)$data['city_id']);

                        $title = str_replace(
                            ' ',
                            '-',
                            strtolower(
                                preg_replace('/[^a-zA-Z0-9 -]/', '', $data['title'])
                            )
                        );

                        $slug = $countryDetails->getSlug() . '--' . $cityDetails->getSlug() . '/' . $title;

                        $saveData = [
                            'title'             => $data['title'],
                            'subtitle'          => $data['subtitle'],
                            'hiring_manager_id' => $data['hiring_manager_id'],
                            'start_date'        => $startDate,
                            'country_id'        => $data['country_id'],
                            'province_id'       => $data['province_id'],
                            'city_id'           => $data['city_id'],
                            'description'       => $data['description'],
                            'requirements'      => $data['requirements'],
                            'department_id'     => $data['department_id'],
                            'status'            => $data['status'],
                            'slug'              => $slug,
                            'cv_required'       => $data['cv_required'],
                            'meta_description'  => $data['meta_description'],
                            'notify_manager'    => $data['notify_manager'],
                            'hiring_team_id'    => ($data['hiring_team_id']) ? $data['hiring_team_id'] : null
                        ];

                        $responseDb = $jobService->jobSave($saveData, $id);

                        if ($responseDb) {
                            if (!$id) {
                                $result['id']  = $responseDb;

                                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_ADD]);
                            }
                        } else {
                            $result['status'] = 'error';
                            $result['msg'] = TextConstants::SERVER_ERROR;
                        }
                    }
                } else {
                    $errors = $form->getMessages();

                    foreach ($errors as $key => $row) {
                        if (!empty($row)) {
                            $messages .= ucfirst($key) . ' ';
                            $messages_sub = '';

                            foreach ($row as $keyer => $rower) {
                                $messages_sub .= $rower;
                            }

                            $messages .= $messages_sub . '<br>';
                        }
                    }

                    $result['status'] = 'error';
                    $result['msg'] = $messages;
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function getForm($id)
    {
        /**
         * @var Job $jobService
         * @var \DDD\Domain\Recruitment\Job\Job $job
         * @var \DDD\Dao\Team\Team $teamDao
         */
        $jobService = $this->getServiceLocator()->get('service_recruitment_job');
        $teamDao    = $this->getServiceLocator()->get('dao_team_team');

        $previousData = null;

        if ($id) {
            $previousData = $jobService->getData($id);
            $job = $previousData['job'];

            if (!$previousData) {
                return false;
            }

            $countryId = $job->getCountryId();
            $provinceId = $job->getProvinceId();
            $hiringTeamId = $job->getHiringTeamId();
        } else {
            $countryId = 1;
            $provinceId = 1;
            $hiringTeamId = 0;
        }

        $prepareOption = $jobService->getJobOptions($previousData);

        $locationInfo = $this->prepareFormContent($countryId, $provinceId);
        $previousData['location'] = $locationInfo;

        if ($hiringTeamId) {
            $previousData['hiring_team'] = [$hiringTeamId => $teamDao->getTeamNameById($hiringTeamId)];
        }

        /**
         * @todo fix performance, do not fetchAll, use usage classes to fetch departments
         */
        $departmentsId = $teamDao->fetchAll(['usage_department' => 1]);

        return new JobForm(
            'jobs',
            $previousData,
            $prepareOption,
            $departmentsId
        );
    }

    public function getProvinceOptionsAction()
    {
        $generalLocationService = $this->getServiceLocator()->get('service_location');
        $countryId = (int)$this->params()->fromQuery('country', 0);

        $provinceOptions = [
            'id'   => 0,
            'name' => '--',
        ];

        if ($countryId) {
            $provinces = $generalLocationService->getActiveChildLocations(
                LocationService::LOCATION_TYPE_PROVINCE,
                $countryId
            );

            if ($provinces->count()) {
                $provinceOptions = [];

                foreach ($provinces as $province) {
                    array_push($provinceOptions, [
                        'id'   => $province->getID(),
                        'name' => $province->getName(),
                    ]);
                }
            }
        }

        return new JsonModel($provinceOptions);
    }

    public function getCityOptionsAction()
    {
        $generalLocationService = $this->getServiceLocator()->get('service_location');
        $provinceId = (int)$this->params()->fromQuery('province', 0);

        $cityOptions = [
            'id'   => 0,
            'name' => '--',
        ];

        if ($provinceId) {
            $cities = $generalLocationService->getActiveChildLocations(
                LocationService::LOCATION_TYPE_CITY,
                $provinceId
            );

            if ($cities->count()) {
                $cityOptions = [];

                foreach ($cities as $city) {
                    array_push($cityOptions, [
                        'id'   => $city->getID(),
                        'name' => $city->getName(),
                    ]);
                }
            }
        }

        return new JsonModel($cityOptions);
    }

    /**
     * Prepare needed data before form construction, especially options for select elements
     *
     * @param int $countryId
     * @param int $provinceId
     * @return array
     */
    private function prepareFormContent($countryId, $provinceId)
    {
        $generalLocationService = $this->getServiceLocator()->get('service_location');

        // country options
        $countries = $generalLocationService->getAllActiveCountries();
        $countryOptions = ['-- Choose --'];
        $content = [];

        foreach ($countries as $country) {
            if ($country->getChildrenCount() != '') {
                $countryOptions[$country->getID()] = $country->getName();
            }
        }

        $content['countryOptions'] = $countryOptions;
        $provinceOptions = [];
        $cityOptions = [];

        // province options
        $provinces = $generalLocationService->getActiveChildLocations(
            LocationService::LOCATION_TYPE_PROVINCE,
            $countryId
        );

        foreach ($provinces as $province) {
            $provinceOptions[$province->getID()] = $province->getName();
        }

        // city options
        $cities = $generalLocationService->getActiveChildLocations(
            LocationService::LOCATION_TYPE_CITY,
            $provinceId
        );

        foreach ($cities as $city) {
            $cityOptions[$city->getID()] = $city->getName();
        }

        $content['provinceOptions'] = $provinceOptions;
        $content['cityOptions'] = $cityOptions;

        return $content;
    }

    public function ajaxDeleteJobAction()
    {
        $result = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE,
        ];

        $request = $this->getRequest();

        try {
            if ($request->isXmlHttpRequest()) {
                $id         = (int)$request->getPost('id');
                $jobService = $this->getServiceLocator()->get('service_recruitment_job');

                $return = $jobService->deleteJob($id);

                if ($return) {
                    Helper::setFlashMessage(['success' => TextConstants::SUCCESS_DELETE]);
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxChangeActivationJobAction()
    {
        $auth        = $this->getServiceLocator()->get('library_backoffice_auth');
        $jobService  = $this->getServiceLocator()->get('service_recruitment_job');
        $request = $this->getRequest();
        $result = [
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $id         = (int)$request->getPost('id');
                $disabled   = (int)$request->getPost('jobStatus');

                if ($id && !$auth->hasRole(Roles::ROLE_HIRING_MANAGER)) {
                    $jobInfo = $jobService->getJobById($id);

                    if ($jobInfo) {
                        if ($auth->getIdentity()->id != $jobInfo->getHiringManagerId()) {
                            return $this->redirect()->toUrl('/recruitment/jobs');
                        }
                    }
                }

                if ($disabled != 3) {
                    $disabled = 3;
                } elseif ($disabled == 3) {
                    $disabled = 1;
                }

                $data = ['status' => $disabled];

                $return = $jobService->changeActStatusJob($data, $id);

                if ($return) {
                    $msg = ($disabled == 3)
                        ? TextConstants::SUCCESS_DEACTIVATE
                        : TextConstants::SUCCESS_ACTIVATE;

                    Helper::setFlashMessage(['success' => $msg]);
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }
}
