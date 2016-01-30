<?php

namespace Recruitment\Controller;

use DDD\Dao\Location\City;
use DDD\Dao\Recruitment\Job\Job;
use DDD\Dao\User\UserManager;
use DDD\Service\Recruitment\ApplicantComment;
use DDD\Service\Recruitment\Applicant;

use FileManager\Constant\DirectoryStructure;
use Library\Authentication\BackofficeAuthenticationService;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

use Library\Constants\Constants;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Library\Constants\Roles;
use Library\Upload\Files;

use Recruitment\Form\Applicant as ApplicantForm;
use Recruitment\Form\Interview as InterviewForm;
use Recruitment\Form\InputFilter\InterviewFilter;
use Recruitment\Form\SearchApplicants as SearchApplicantsForm;
use Recruitment\Form\InputFilter\ApplicantCommentFilter;
use DDD\Service\Notifications as NotificationService;
use DDD\Service\Team\Usages\Base as TeamBase;

class ApplicantsController extends ControllerBase
{
    public function indexAction()
    {
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');

        if ($authenticationService->hasRole(Roles::ROLE_APPLICANT_MANAGEMENT)) {
            $form = new SearchApplicantsForm('search-applicants');

            return new ViewModel([
                'ajaxSourceUrl' => '/recruitment/applicants/get-json',
                'searchForm'    => $form,
            ]);

        } else {
            return $this->redirect()->toUrl('/');
        }
    }

    public function getJsonAction()
    {
        /**
         * @var \DDD\Service\Recruitment\Applicant $applicantService
         * @var \DDD\Service\Team\Usages\Base $teamBaseService
         */
        $applicantService          = $this->getServiceLocator()->get('service_recruitment_applicant');
        $authenticationService     = $this->getServiceLocator()->get('library_backoffice_auth');
        $applicantInterviewService = $this->getServiceLocator()->get('service_recruitment_interview');
        $userManagerDao            = $this->getServiceLocator()->get('dao_user_user_manager');
        $cityDao                   = $this->getServiceLocator()->get('dao_geolocation_city');
        $jobDao                    = $this->getServiceLocator()->get('dao_recruitment_job_job');
        $teamBaseService           = $this->getServiceLocator()->get('service_team_usages_base');

        $request = $this->params();

        $results       = [];
        $userId        = $authenticationService->getIdentity()->id;
        $userInfo      = $userManagerDao->getUserById($userId);
        $userCountry   = $cityDao->getCountryIDByCityID($userInfo->getCity_id());

        $userTeams = $teamBaseService->getUserTeamsByUsage($userId, TeamBase::TEAM_USAGE_HIRING);

        $userTeamIds = [];
        if (count($userTeams)) {
            foreach ($userTeams as $team) {
                $userTeamIds[] = $team->getId();
            }
        }

        $isGlobManager = $authenticationService->hasRole(Roles::ROLE_HIRING_MANAGER);
        $hiringCountryId = false;

        if (!$isGlobManager) {
            if ($authenticationService->hasRole(Roles::ROLE_HIRING_COUNTRY_MANAGER)) {
                $hiringCountryId = $userCountry->getCountry_id();
            }
        }

        $applicantLists = $applicantService->getApplicantList(
            (int)$request->fromQuery('iDisplayStart'),
            (int)$request->fromQuery('iDisplayLength'),
            (int)$request->fromQuery('iSortCol_0'),
            $request->fromQuery('sSortDir_0'),
            $request->fromQuery('sSearch'),
            $request->fromQuery('status', '1,2,3,4,5,6'),
            [],
            $isGlobManager,
            $hiringCountryId,
            $userId,
            $userTeamIds
        );

        $applicantCount = $applicantService->getApplicantCount(
            $request->fromQuery('sSearch'),
            $request->fromQuery('status', '1,2,3,4,5,6'),
            $isGlobManager,
            $hiringCountryId,
            $userId,
            $userTeamIds
        );

        foreach ($applicantLists as $applicant) {
            $action = '<a href="/recruitment/applicants/edit/' . $applicant->getId() . '" class="btn btn-xs btn-primary" data-html-content="Manage"></a>';
            $name = $applicant->getFirstName() . ' ' . $applicant->getLastName();

            $status     = Applicant::$status;
            $statusName = $status[$applicant->getStatus()];

            array_push($results, [
                $statusName,
                $name,
                $applicant->getPosition(),
                $applicant->getJobCity(),
                date(Constants::GLOBAL_DATE_FORMAT, strtotime($applicant->getDateApplied())),
                $applicant->getPhone(),
                '<a href="mailto:' . $applicant->getEmail() .'" target="_blank">' . $applicant->getEmail() . '</a>',
                $action,
            ]);
        }

        if (!isset($results)) {
            array_push($result, [' ', '', '', '', '', '', '', '', '']);
        }

        $resultArray = [
            'sEcho'                => $request->fromQuery('sEcho'),
            'iTotalRecords'        => count($results),
            'iTotalDisplayRecords' => $applicantCount,
            'iDisplayStart'        => $request->fromQuery('iDisplayStart'),
            'iDisplayLength'       => (int)$request->fromQuery('iDisplayLength'),
            'aaData'               => $results,
        ];

        return new JsonModel($resultArray);
    }

    public function getInterviewsAction()
    {
        /**
         * @var \DDD\Service\Recruitment\Interview $applicantInterviewService
         */
        $applicantInterviewService = $this->getServiceLocator()->get('service_recruitment_interview');
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest()) {
            $id = $this->params()->fromQuery('applicant_id', 0);

            $interviewsAaData = [];
            $interviews = $applicantInterviewService->getInterviewsForApplicant($id);

            if ($interviews->count()) {
                foreach ($interviews as $row) {
                    $interviewsAaData[$row->getId()][0] = (isset($interviewsAaData[$row->getId()][0])) ? $interviewsAaData[$row->getId()][0] . ', ' : '';
                    $interviewsAaData[$row->getId()][0] .= $row->getInterviewerName();
                    $interviewsAaData[$row->getId()][1] = date(Constants::GLOBAL_DATE_FORMAT . ' H:i', strtotime($row->getFrom()));
                    $interviewsAaData[$row->getId()][2] = $row->getTo() ? date(Constants::GLOBAL_DATE_FORMAT . ' H:i', strtotime($row->getTo())) : '';
                    $interviewsAaData[$row->getId()][3] = $row->getPlace();
                    $interviewsAaData[$row->getId()][4] = $row->getStatus();
                    $interviewsAaData[$row->getId()][5] = (isset($interviewsAaData[$row->getId()][5])) ? $interviewsAaData[$row->getId()][5] . ', ' : '';
                    $interviewsAaData[$row->getId()][5] .= $row->getInterviewerId();
                    $interviewsAaData[$row->getId()][6] = '<button class="btn btn-xs btn-primary edit-interview" data-id="' . $row->getId() . '">Edit</button>';
                }
            }

            return new JsonModel([
                'aaData' => array_values($interviewsAaData),
            ]);
        }

        return new JsonModel([
            'aaData' => [],
        ]);
    }

    public function editAction()
    {
        /**
         * @var \DDD\Service\Recruitment\Applicant $applicantService
         * @var \DDD\Service\Recruitment\Interview $applicantInterviewService
         * @var ApplicantComment $applicantCommentsService
         * @var \DDD\Dao\User\UserManager $userManagerDao
         * @var BackofficeAuthenticationService $authenticationService
         * @var City $cityDao
         * @var Job $jobDao
         * @var \DDD\Service\Team\Usages\Base $teamBaseService
         */
        $applicantService          = $this->getServiceLocator()->get('service_recruitment_applicant');
        $applicantInterviewService = $this->getServiceLocator()->get('service_recruitment_interview');
        $applicantCommentsService  = $this->getServiceLocator()->get('service_recruitment_applicant_comment');
        $userManagerDao            = $this->getServiceLocator()->get('dao_user_user_manager');
        $authenticationService     = $this->getServiceLocator()->get('library_backoffice_auth');
        $cityDao                   = $this->getServiceLocator()->get('dao_geolocation_city');
        $jobDao                    = $this->getServiceLocator()->get('dao_recruitment_job_job');
        $teamBaseService           = $this->getServiceLocator()->get('service_team_usages_base');

        $applicantId   = (int)$this->params()->fromRoute('id', 0);
        $loggedInUserId= $authenticationService->getIdentity()->id;
        $isGlobManager = $authenticationService->hasRole(Roles::ROLE_HIRING_MANAGER);
        $applicantInfo = $applicantService->getApplicantById($applicantId);

        if (!$applicantInfo) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('recruitment/applicants');
        }

        $isInterviewer = $applicantInterviewService->isInterviewer($loggedInUserId, $applicantId);
        $loggedInUser  = $userManagerDao->getUserById($loggedInUserId);
        $userCountry   = $cityDao->getCountryIDByCityID($loggedInUser->getCity_id());
        $jobInfo       = $jobDao->fetchOne(['id' => $applicantInfo->getJobId()]);
        $jobCountry    = $jobInfo->getCountryId();
        $users         = $userManagerDao->getForSelect();

        $userTeams = $teamBaseService->getUserTeamsByUsage($loggedInUserId, TeamBase::TEAM_USAGE_HIRING);

        $userTeamIds = [];
        if (count($userTeams)) {
            foreach ($userTeams as $team) {
                $userTeamIds[] = $team->getId();
            }
        }

        $interviewManager   = false;
        $isHiringManager    = ($loggedInUserId == $jobInfo->getHiringManagerId());
        $isCountryManager   = (
            $authenticationService->hasRole(Roles::ROLE_HIRING_COUNTRY_MANAGER)
            && ($userCountry->getCountry_id() == $jobCountry)
        );
        $isHiringTeam       = in_array($applicantInfo->getHiringTeamId(), $userTeamIds);

        if ($isGlobManager || $isCountryManager || $isHiringManager) {
            $interviewManager = true;
        } else {
            if (!$isInterviewer && !$isHiringTeam) {
                return $this->redirect()->toUrl('/');
            }
        }

        $form = $this->getForm($applicantId);

        $isLoggedInUserHiringManager = false;
        if ($loggedInUserId == $jobInfo->getHiringManagerId()) {
            $isLoggedInUserHiringManager = true;
        }

        $comments = $applicantCommentsService->getApplicantCommentsById($applicantId, $isLoggedInUserHiringManager);
        $preparedCommentsData = [];

        foreach ($comments as $comment) {
            if ($interviewManager || $comment->getCommenterId() == $loggedInUserId) {
                $datatableCommentRow = [
                    '0' => date(Constants::GLOBAL_DATE_TIME_FORMAT, strtotime($comment->getDate())),
                    '1' => '<b>' . $comment->getCommenterFullName() . '</b> (' . $comment->getCommenterPosition() . ')',
                    '2' => $comment->getComment(),
                    'DT_RowClass' => $comment->getHrOnly() ? 'warning' : ''
                ];
                array_push($preparedCommentsData, $datatableCommentRow);
            }
        }

        $commentsData = json_encode($preparedCommentsData);

        // get previous applicants
        $previousApplicants = false;
        if ($applicantInfo->getEmail()) {
            $previousApplicants = $applicantService->getApplicantsByEmail(
                $applicantInfo->getEmail(),
                $applicantInfo->getId()
            );
        }

        if ($applicantInfo->getCvFileName()) {
            $applicantService->addDownloadButton($applicantInfo->getId(), $form, $this);
        }

        return new ViewModel([
            'applicantInfo'             => $applicantInfo,
            'form'                      => $form,
            'comment'                   => $commentsData,
            'id'                        => $applicantId,
            'interviewManager'          => $interviewManager,
            'users'                     => $users,
            'previousApplicants'        => $previousApplicants,
            'hasPeopleManagementHrRole' => $authenticationService->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR),
            'isGlobManager'             => $isGlobManager,
            'isHiringManager'           => $isHiringManager,
            'isCountryManager'          => $isCountryManager,
            'isHiringTeam'              => $isHiringTeam
        ]);
    }

    public function downloadCvAction()
    {
        /**
         * @var \DDD\Dao\Recruitment\Applicant\Applicant $applicantsDao
         * @var \DDD\Domain\Recruitment\Applicant\Applicant $applicantRow
         */
        $applicantsDao = $this->getServiceLocator()->get('dao_recruitment_applicant_applicant');
        $applicantId = $this->params()->fromRoute('id', 0);
        $applicantRow = $applicantsDao->fetchOne(['id' => $applicantId], [
            'cv',
            'date_applied'
        ]);

        $filePath = DirectoryStructure::FS_UPLOADS_HR_APPLICANT_DOCUMENTS . $applicantRow->getCvFileUrl();
        ini_set('memory_limit', '512M');

        /**
         * @var \FileManager\Service\GenericDownloader $genericDownloader
         */
        $genericDownloader = $this->getServiceLocator()->get('fm_generic_downloader');

        $genericDownloader->downloadAttachment($filePath);

        if ($genericDownloader->hasError()) {
            Helper::setFlashMessage(['error' => $genericDownloader->getErrorMessages(true)]);

            $url = $this->getRequest()->getHeader('Referer')->getUri();
            $this->redirect()->toUrl($url);
        }

        return true;
    }

    public function getForm()
    {
        /**
         * @var \DDD\Service\Recruitment\Applicant $applicantService
         */
        $applicantService = $this->getServiceLocator()->get('service_recruitment_applicant');
        $prepareOption = $applicantService->getApplicantOptions();

        $previousData = null;

        return new ApplicantForm(
            'applicant',
            $previousData,
            $prepareOption
        );
    }

    public function ajaxSaveAction()
    {
        /**
         * @var \DDD\Service\Recruitment\Applicant $applicantService
         * @var ApplicantComment $applicantCommentsService
         * @var \DDD\Service\Recruitment\Interview $applicantInterviewService
         * @var UserManager $userManagerDao
         * @var City $cityDao
         * @var Job $jobDao
         * @var BackofficeAuthenticationService $authenticationService
         */
        $applicantService           = $this->getServiceLocator()->get('service_recruitment_applicant');
        $applicantCommentsService   = $this->getServiceLocator()->get('service_recruitment_applicant_comment');
        $applicantInterviewService  = $this->getServiceLocator()->get('service_recruitment_interview');
        $userManagerDao             = $this->getServiceLocator()->get('dao_user_user_manager');
        $cityDao                    = $this->getServiceLocator()->get('dao_geolocation_city');
        $jobDao                     = $this->getServiceLocator()->get('dao_recruitment_job_job');
        $authenticationService      = $this->getServiceLocator()->get('library_backoffice_auth');

        $request = $this->getRequest();

        $result  = [
            'result' => [],
            'id'     => 0,
            'status' => 'success',
            'msg'    => TextConstants::SUCCESS_UPDATE,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $id     = (int)$request->getPost('applicant_id');
                $form   = $this->getForm($id);

                $form->setInputFilter(new ApplicantCommentFilter());

                $data = $request->getPost();
                $applicantInfo = $applicantService->getApplicantById($id);
                $userId        = $authenticationService->getIdentity()->id;
                $userInfo      = $userManagerDao->getUserById($userId);
                $jobInfo       = $jobDao->fetchOne(['id' => $applicantInfo->getJobId()]);
                $jobCountryId  = $jobInfo->getCountryId();
                $isInterviewer = $applicantInterviewService->isInterviewer($userId, $id);

                /**
                 * @var \DDD\Service\Team\Usages\Base $teamBaseService
                 */
                $teamBaseService           = $this->getServiceLocator()->get('service_team_usages_base');
                $userTeams = $teamBaseService->getUserTeamsByUsage($userId, TeamBase::TEAM_USAGE_HIRING);

                $userTeamIds = [];
                if (count($userTeams)) {
                    foreach ($userTeams as $team) {
                        $userTeamIds[] = $team->getId();
                    }
                }

                if ($result['status'] != 'error') {
                    if (!$authenticationService->hasRole(Roles::ROLE_HIRING_MANAGER)) {
                        if ($authenticationService->hasRole(Roles::ROLE_HIRING_COUNTRY_MANAGER)) {
                            $userCountry = $cityDao->getCountryIDByCityID($userInfo->getCity_id());

                            if ($userCountry->getCountry_id() != $jobCountryId && ($userId != $jobInfo->getHiringManagerId())) {
                                if (!in_array($applicantInfo->getHiringTeamId(), $userTeamIds)) {
                                    return $this->redirect()->toUrl('/');
                                }
                            }
                        } elseif ($userId != $jobInfo->getHiringManagerId() && !$isInterviewer) {
                            if (!in_array($applicantInfo->getHiringTeamId(), $userTeamIds)) {
                                return $this->redirect()->toUrl('/');
                            }
                        }
                    }

                    $date = date('Y-m-d H:i:s');
                    $responseDb = null;

                    if (!empty($data['comment'])) {
                        $hrOnlyComment = 0;

                        if ($authenticationService->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR) && isset($data['hr_only_comment'])) {
                            $hrOnlyComment = $data['hr_only_comment'];
                        }

                        $responseDb = $applicantCommentsService->save([
                            'applicant_id' => $data['applicant_id'],
                            'commenter_id' => $userId,
                            'position' => $userInfo->getPosition(),
                            'comment' => htmlspecialchars($data['comment']),
                            'hr_only_comment' => $hrOnlyComment,
                            'date' => $date,
                        ]);
                    }

                    if ($responseDb) {
                        $result['id'] = $data['applicant_id'];
                    } else {
                        $result['status'] = 'error';
                        $result['msg'] = TextConstants::SERVER_ERROR;
                    }
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxSaveInterviewAction()
    {
        /**
         * @var \DDD\Service\Recruitment\Interview $interviewService
         * @var \DDD\Service\Recruitment\ApplicantComment $applicantCommentsService
         * @var \DDD\Service\Notifications $notificationsService
         * @var \DDD\Dao\User\UserManager $userManagerDao
         */
        $interviewService           = $this->getServiceLocator()->get('service_recruitment_interview');
        $applicantService           = $this->getServiceLocator()->get('service_recruitment_applicant');
        $notificationsService       = $this->getServiceLocator()->get('service_notifications');
        $userManagerDao             = $this->getServiceLocator()->get('dao_user_user_manager');
        $authenticationService      = $this->getServiceLocator()->get('library_backoffice_auth');
        $applicantCommentsService   = $this->getServiceLocator()->get('service_recruitment_applicant_comment');

        $sender  = NotificationService::$notifications;
        $request = $this->getRequest();
        $userId  = $authenticationService->getIdentity()->id;

        $user = $authenticationService->getIdentity()->firstname . ' ' . $authenticationService->getIdentity()->lastname;
        $userPosition = $authenticationService->getIdentity()->position;

        try {
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $users = $userManagerDao->getForSelect();

                $form = new InterviewForm('interviewForm', ['users' => $users]);
                $params = $request->getPost();
                $form->setData($params);
                $form->setInputFilter(new InterviewFilter());

                if ($form->isValid()) {
                    $data = $form->getData();
                    $applicantInfo = $applicantService->getApplicantById($data['applicant_id']);

                    $alreadySavedParticipants = (!$data['id']) ? [] : $interviewService->getInterviewParticipantsArray($data['id']);

                    $interviewId = $interviewService->saveInterview([
                        'id'           => $data['id'],
                        'from'         => date('Y-m-d H:i:s', strtotime($data['from'])),
                        'to'           => $data['to'] ? date('Y-m-d H:i:s', strtotime($data['to'])) : NULL,
                        'place'        => $data['place'],
                        'applicant_id' => $data['applicant_id'],
                    ]);
                    $interviewPart = $interviewService->saveParticipants(
                        $data['participants'],
                        $interviewId
                    );

                    if ($interviewPart) {
                        foreach ($data['participants'] as $participant) {
                            if (in_array($participant, $alreadySavedParticipants)) {
                                continue;
                            }

                            $message = 'You have an Interview on  ' .
                                date(Constants::GLOBAL_DATE_FORMAT, strtotime($data['from'])) . ' at ' .
                                date('H:i', strtotime($data['from'])) . ' with ' .
                                $applicantInfo->getFirstname() . ' ' . $applicantInfo->getLastName() . ' in ' .
                                $data['place'];

                            $showDate = date(
                                'Y-m-d H:i:s',
                                strtotime('-3 day', strtotime($data['from']))
                            );

                            if (strtotime($data['from']) - time() < 259200) {
                                $showDate = date('Y-m-d H:i:s');
                            };

                            $url = '/recruitment/applicants/edit/' . $data['applicant_id'];
                            $notificationData = [
                                'recipient' => $participant,
                                'sender'    => $sender,
                                'sender_id' => $userId,
                                'message'   => $message,
                                'url'       => $url,
                                'show_date' => $showDate,
                            ];

                            $notificationsService->createNotification($notificationData);
                        }
                    }

                    if ($data['id']) {
                        $comment = '<b>' . $user . '</b> made changes on interview.';
                    } else {
                        $comment = '<b>' . $user . '</b> added an interview.';
                    }

                    $applicantCommentsService->save([
                        'applicant_id' => $params['applicant_id'],
                        'commenter_id' => $userId,
                        'position'     => $userPosition,
                        'comment'      => $comment,
                        'date'         => date('Y-m-d H:i:s'),
                    ]);

                    $result['status'] = 'success';
                    $result['msg'] = 'Interview successfully saved';
                } else {
                    $result['status'] = 'error';
                    $result['msg'] = 'Form data problems.';
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        Helper::setFlashMessage([$result['status'] => $result['msg']]);

        return new JsonModel([1]);
    }

    public function changeApplicantStatusAction()
    {
        $applicantService         = $this->getServiceLocator()->get('service_recruitment_applicant');
        $authenticationService    = $this->getServiceLocator()->get('library_backoffice_auth');
        $userManagerDao           = $this->getServiceLocator()->get('dao_user_user_manager');
        $applicantCommentsService = $this->getServiceLocator()->get('service_recruitment_applicant_comment');

        $userId   = $authenticationService->getIdentity()->id;
        $userInfo = $userManagerDao->getUserById($userId);

        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try{
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $params = $request->getPost();
                if (!$params['id'] || !$params['status']) {
                    throw new \Exception('Invalid parameters');
                } else {
                    $preApplicantInfo = $applicantService->getApplicantById($params['id']);
                    $applicantService->saveApplicantStatus($params['id'], $params['status']);

                    $comment = '<b>' . $userInfo->getFirstName() . ' ' . $userInfo->getLastName() . '</b> has changed status from <b>' .
                        Applicant::$status[$preApplicantInfo->getStatus()] .
                        '</b> to <b>' .
                        Applicant::$status[$params['status']] . '</b>.';

                    $saveData = [
                        'applicant_id' => $params['id'],
                        'commenter_id' => $userId,
                        'position'     => $userInfo->getPosition(),
                        'comment'      => $comment,
                        'date'         => date('Y-m-d H:i:s'),
                    ];

                    $applicantCommentsService->save($saveData);

                    $result['status'] = 'success';
                    $result['msg']    = 'Applicant status changed successfully.';

                    Helper::setFlashMessage([$result['status'] =>  $result['msg']]);
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = 'Failed to save applicant status.';
        }

        return new JsonModel($result);
    }


    public function ajaxUploadCvAction()
    {
        $applicantDao     = $this->getServiceLocator()->get('dao_recruitment_applicant_applicant');
        $applicantService = $this->getServiceLocator()->get('service_recruitment_applicant');

        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try{
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $data          = $request->getPost();
                $fileInfo      = $request->getFiles()->toArray();
                $filename      = $fileInfo['cv']['name'];
                $applicantInfo = $applicantDao->fetchOne(['id' => $data['id']]);
                $filesObj      = new Files($fileInfo);
                $fileType      = $filesObj->getFileType($filename);

                $acceptedFileTypes = ['pdf', 'doc', 'docx', 'odt', 'rtf'];
                $cvName = null;

                if (!in_array($fileType, $acceptedFileTypes)) {
                    $result = [
                        'status' => 'error',
                        'msg'    => TextConstants::FILE_TYPE_NOT_TRUE,
                    ];
                } else {
                    $savedFile = $filesObj->saveFiles(
                        DirectoryStructure::FS_GINOSI_ROOT
                            . DirectoryStructure::FS_UPLOADS_ROOT
                            . DirectoryStructure::FS_UPLOADS_HR_APPLICANT_DOCUMENTS
                            . date('Y/m/', strtotime($applicantInfo->getDateApplied())),
                        $acceptedFileTypes,
                        false,
                        true
                    );

                    if ($savedFile['cv']) {
                        $cvName = $savedFile['cv'];
                    }

                    if (!is_null($cvName)) {
                        $preApplicantInfo = $applicantService->getApplicantById($data['id']);

                        if ($preApplicantInfo->getCvFileName()) {
                            $filePath  = DirectoryStructure::FS_GINOSI_ROOT
                                . DirectoryStructure::FS_UPLOADS_ROOT
                                . DirectoryStructure::FS_UPLOADS_HR_APPLICANT_DOCUMENTS
                                . $preApplicantInfo->getCvFileUrl();

                            if (is_readable($filePath)) {
                                @unlink($filePath);
                            }
                        }

                        $applicantDao->save(['cv' => $cvName],['id' => $data['id']]);

                        $result = [
                            'status' => 'success',
                            'msg'    => TextConstants::SUCCESS_UPDATE
                        ];

                        Helper::setFlashMessage(['success'=>  TextConstants::SUCCESS_UPDATE]);
                    }
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function removeAttachmentAction()
    {
        $id            = $this->params()->fromRoute('id', 0);
        $applicantDao  = $this->getServiceLocator()->get('dao_recruitment_applicant_applicant');
        $applicantInfo = $applicantDao->fetchOne(['id' => $id]);
        $filePath      = DirectoryStructure::FS_GINOSI_ROOT
            . DirectoryStructure::FS_UPLOADS_ROOT
            . DirectoryStructure::FS_UPLOADS_HR_APPLICANT_DOCUMENTS
            . $applicantInfo->getCvFileUrl();

        if (is_readable($filePath)) {
            if (@unlink($filePath)) {
                $applicantDao->save(['cv' => ''], ['id' => $id]);

                Helper::setFlashMessage(['success' => TextConstants::SUCCESS_UPDATE]);
            }
        } else {
            Helper::setFlashMessage(['error' => TextConstants::SERVER_ERROR]);
        }

        $url = $this->getRequest()->getHeader('Referer')->getUri();
        $this->redirect()->toUrl($url);
    }
}
