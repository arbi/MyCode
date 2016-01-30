<?php

namespace Backoffice\Controller;

use DDD\Service\ApartmentGroup as ApartmentGroupService;
use DDD\Service\ApartmentGroup;
use DDD\Service\HouseKeeping\HouseKeeping;
use DDD\Service\Task;
use DDD\Service\Team\Team;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Utility\Debug;
use Library\Utility\Helper;
use Zend\Http\Request;
use Zend\Stdlib\ArrayObject;
use Zend\Authentication\AuthenticationService;
use Zend\View\Model\JsonModel;
use Library\Constants\Constants;
use Library\Constants\DomainConstants;
use Library\Constants\TextConstants;
use Library\Controller\ControllerBase;
use Library\Constants\Roles;
use Library\ActionLogger\Logger;
use Zend\View\Model\ViewModel;
use DDD\Service\User as UserService;

class HousekeepingTasksController extends ControllerBase {

    public function indexAction()
    {
	    /**
         * @var \DDD\Service\Team\Usages\Frontier $frontierTeamService
         * @var ApartmentGroup $apartmentGroupService
	     */
        $frontierTeamService   = $this->getServiceLocator()->get('service_team_usages_frontier');
        $auth                  = $this->getServiceLocator()->get('library_backoffice_auth');
        $authId                = (int)$auth->getIdentity()->id;

        if ($auth->hasPermission(Roles::ROLE_GLOBAL_HOUSEKEEPING_MANAGER)) {
            $teams = $frontierTeamService->getTeamsByUsage();
        } elseif ($auth->hasPermission(Roles::ROLE_HOUSEKEEPING)) {
            $teams = $frontierTeamService->getUserFrontierTeams($authId);
            if (!$teams->count()) {
                return $this->redirect()->toRoute('universal-dashboard');
            }
        } else {
            return $this->redirect()->toRoute('universal-dashboard');
        }

        // @ToDo add feature to manage teams if use is the teams manager
        if ($teams->count() == 1) {
            $team = $teams->current();
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'housekeeping-tasks', 'action' => 'view', 'id' => $team->getId()]);
        }

        return new ViewModel([
            'teams' => $teams,
	        'managableList' => [], //$managableList,
        ]);
    }

    public function viewAction()
    {
        /**
         * @var \DDD\Service\Team\Team $teamService
         * @var \DDD\Dao\Team\Team $teamDao
         * @var \DDD\Dao\Team\TeamStaff $teamStaffDao
         * @var BackofficeAuthenticationService $authenticationService
         * @var \DDD\Service\User $userService
         */
        $teamService           = $this->getServiceLocator()->get('service_team_team');
        $teamDao               = $this->getServiceLocator()->get('dao_team_team');
        $teamStaffDao          = $this->getServiceLocator()->get('dao_team_team_staff');
        $userService           = $this->getServiceLocator()->get('service_user');
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');

        $teamId = (int)$this->params()->fromRoute('id', 0);
        $team   = $teamDao->getTeamBasicInfo($teamId);

        if ($teamId && !$team->getName()) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'housekeeping-tasks', 'action' => 'index']);
        }

        $userIdentity = $authenticationService->getIdentity();
        $roleInTeam   = $teamService->getUserPositionInTeam($userIdentity->id, $teamId);

        if ($authenticationService->hasPermission(Roles::ROLE_GLOBAL_HOUSEKEEPING_MANAGER)) {
            $isGlobal = true;
        } else if (!isset($roleInTeam[0])) {
            Helper::setFlashMessage(['error' => 'Access denied']);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'housekeeping-tasks', 'action' => 'index']);
        }

        $usersArr = $userService->getUsersList(false, true);
        $allUsers = [];

        foreach ($usersArr as $row) {
            $avatar = $userService->getAvatarForSelectize($row->getId(), $row->getAvatar());

            $allUsers[$row->getId()] = [
                'id'     => $row->getId(),
                'name'   => $row->getFullName(),
                'avatar' => $avatar
            ];
        }

        $officers = $teamStaffDao->getTeamOfficerList($teamId);
        $officersArr = [];
        if ($officers->count()) {
            foreach ($officers as $officer) {
                array_push($officersArr, [
                    'id'     => $officer->getUserId(),
                    'name'   => $officer->getFullName(),
                    'avatar' => $userService->getAvatarForSelectize($officer->getUserId(), $officer->getAvatar())
                ]);
            }
        }
        $officersWithoutAutoVerify = $officersArr;

        array_push($officersArr, $allUsers[UserService::AUTO_VERIFY_USER_ID]);

        $members  = $teamStaffDao->getTeamMemberList($teamId);
        $membersArr = [];
        if ($members->count()) {
            foreach ($members as $member) {
                array_push($membersArr, [
                    'id'     => $member->getUserId(),
                    'name'   => $member->getFullName(),
                    'avatar' => $userService->getAvatarForSelectize($member->getUserId(), $member->getAvatar())
                ]);
            }
        }

        return new ViewModel([
            'apartmentGroupName'        => $team->getName(),
            'teamId'                    => $teamId,
            'roleInTeam'                => $roleInTeam,
            'members'                   => $membersArr,
            'officers'                  => $officersArr,
            'officersWithoutAutoVerify' => $officersWithoutAutoVerify,
            'allUsers'                  => $allUsers,
            'userId'                    => $userIdentity->id,
            'imgPath'                   => [
                'default'   => '//' . DomainConstants::BO_DOMAIN_NAME . Constants::VERSION . 'img/',
                'original'  => '//' . DomainConstants::IMG_DOMAIN_NAME . '/profile/',
            ]
        ]);
    }

    public function ajaxGetHkDashboardDataAction()
    {
        /** @var \DDD\Service\Task $taskService */
        $taskService           = $this->getServiceLocator()->get('service_task');
        /** @var \DDD\Service\Team\Team $teamService */
        $teamService           = $this->getServiceLocator()->get('service_team_team');
        /** @var \DDD\Dao\Team\Team $teamDao */
        $teamDao               = $this->getServiceLocator()->get('dao_team_team');
        /** @var \DDD\Dao\Team\TeamStaff $teamStaffDao */
        $teamStaffDao          = $this->getServiceLocator()->get('dao_team_team_staff');
        /** @var \DDD\Service\User $userService */
        $userService           = $this->getServiceLocator()->get('service_user');
        /** @var BackofficeAuthenticationService $authenticationService */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $request = $this->getRequest();

        $teamId     = (int)$request->getPost('teamId', 0);
        $categories = $request->getPost('categories', []);
        $sortId     = $request->getPost('sortId', 0);
        $result     = [];

        try {
            if ($request->isXmlHttpRequest() && $teamId) {
                if (count($categories)) {
                    $userIdentity = $authenticationService->getIdentity();
                    $roleInTeam   = $teamService->getUserPositionInTeam($userIdentity->id, $teamId);
                    $isGlobal     = false;

                    if ($authenticationService->hasPermission(Roles::ROLE_GLOBAL_HOUSEKEEPING_MANAGER)) {
                        $isGlobal = true;
                    } else if (!isset($roleInTeam[0])) {
                        Helper::setFlashMessage(['error' => 'Access denied']);
                        return $this->redirect()->toRoute('backoffice/default', ['controller' => 'housekeeping-tasks', 'action' => 'index']);
                    }

                    if (is_array($roleInTeam)) {
                        $roleInTeam = array_shift($roleInTeam);
                    }

                    $team   = $teamDao->getTeamBasicInfo($teamId);
                    $localTime = new \DateTime('now', new \DateTimeZone($team->getTimezone()));
                    $currentDate = $localTime->format('Y-m-d');

                    $usersArr = $userService->getUsersList(false, true);
                    $allUsers = [];

                    foreach ($usersArr as $row) {
                        $avatar = $userService->getAvatarForSelectize($row->getId(), $row->getAvatar());

                        $allUsers[$row->getId()] = [
                            'id'     => $row->getId(),
                            'name'   => $row->getFullName(),
                            'avatar' => $avatar
                        ];
                    }

                    $partial = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');
                    $result = $tasks = [];
                    foreach ($categories as $category) {
                        $tasks[$category] = $taskService->getResForHousekeeperBasedOnTasks($team, $roleInTeam, $isGlobal, $category, $sortId);
                        $result[$category] = $partial('backoffice/housekeeping-tasks/partial/task-list', [
                            'tasksByDays' => $tasks[$category],
                            'allUsers'    => $allUsers,
                            'currentDate' => $currentDate,
                            'isGlobal'    => $isGlobal,
                            'roleInTeam'  => $roleInTeam,
                            'category'    => $category
                        ]);
                    }
                    $result['status'] = 'success';
                }
            } else {
                $result['status'] = 'error';
                $result['msg']    = 'Bad Request';
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return  new JsonModel($result);
    }

    public function ajaxReportIncidentAction()
    {
        /**
         * @var \DDD\Dao\Booking\Booking $reservationDao
         * @var \DDD\Service\Task $taskService
         */
        $user        = $this->getServiceLocator()->get('library_backoffice_auth');
        $taskService = $this->getServiceLocator()->get('service_task');
        $request     = $this->getRequest();
        $result      = [
            'status' => 'success',
            'msg'    => 'Incident report has been created.',
            'title'  => '',
            'taskId' => 0
        ];

        $taskId = (int)$request->getPost('task_id', 0);
        
        try {
            if ($request->isXmlHttpRequest() && $taskId) {
                $task                        = $taskService->getTaskDao()->getTaskById($taskId);
                $apartmentId                 = $task->getProperty_id();
                $buildingId                  = $task->getBuildingId();
                $resId                       = $task->getResId();
                $timezone                    = $task->getTimezone();
                $currentDateCity             = Helper::getCurrenctDateByTimezone($timezone, 'Y-m-d H:i:s');
                $ninetySixHoursLaterDateCity = Helper::incrementDateByTimezone($timezone, 96, 'Y-m-d H:i:s');
                $userId                      = $user->getIdentity()->id;
                $creator                     = $user->getIdentity()->id;

                switch ((int)$request->getPost('incident_type', 0)) {
                    case HouseKeeping::INCIDENT_REPORT_EVIDENCE_OF_SMOKING:
                        $damageType = 'There was evidence of smoking';
                        break;
                    case HouseKeeping::INCIDENT_REPORT_KEYS_WERE_NOT_RETURNED:
                        $damageType = 'The keys were not returned';
                        break;
                    case HouseKeeping::INCIDENT_REPORT_DIRTY_HOUSE:
                        $damageType = 'The house was left in a unusually dirty condition';
                        break;
                    case HouseKeeping::INCIDENT_REPORT_BROKEN_FURNITURE:
                        $damageType = 'There was broken furniture';
                        break;
                    case HouseKeeping::INCIDENT_REPORT_OTHER:
                        $damageType = $request->getPost('description', 'Incident Report');
                        break;
                }
                $title = $damageType;
                $description = '';


                $incidentTaskId = $taskService->createReportIncidentTask(
                    $resId,
                    $apartmentId,
                    $buildingId,
                    $currentDateCity,
                    $ninetySixHoursLaterDateCity,
                    $title,
                    $creator,
                    $description,
                    $userId,
                    $taskId
                );

                $result['title']  = $title;
                $result['taskId'] = $incidentTaskId;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }
        return new JsonModel($result);
    }

    public function ajaxChangeSubtaskStatusAction()
    {
        /** @var \DDD\Dao\Task\Subtask $subtaskDao */
        $subtaskDao = $this->getServiceLocator()->get('dao_task_subtask');
        /** @var \DDD\Service\Task $taskService */
        $taskService = $this->getServiceLocator()->get('service_task');
        /** @var Logger $logger */
        $logger = $this->getServiceLocator()->get('ActionLogger');

        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR
        ];

        $subtaskId = (int)$request->getPost('subtask_id', 0);
        $subtaskDescription = $request->getPost('subtask_description', '');
        $taskId = (int)$request->getPost('task_id', 0);
        $status = (int)$request->getPost('status', 0);
        try {
            if ($request->isXmlHttpRequest() && $subtaskId && $taskId) {
                $permissionSet = $taskService->composeUserTaskPermissions($taskId);

                if (isset($permissionSet[Task::ACTION_CHANGE_STATUS])) {
                    $subtaskDao->save(['status' => $status], ['id' => $subtaskId]);

                    $logger->save(
                        Logger::MODULE_TASK,
                        $taskId,
                        Logger::ACTION_TASK_SUBTASK,
                        'Marked subtask <b>' . $subtaskDescription . '</b> ' .
                        ($status ? 'Done' : 'Undone')
                    );

                    $result = [
                        'status' => 'success',
                        'msg' => 'Subtask successfully saved.'
                    ];
                } else {
                    $result = [
                        'status' => 'error',
                        'msg' => 'No permission.'
                    ];
                }
            }
        } catch (\Exception $e) {
            // Do nothing
        }
        return new JsonModel($result);
    }

    public function ajaxSaveCommentAction()
    {
        /**
         * @var Logger $logger
         */
        $logger      = $this->getServiceLocator()->get('ActionLogger');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR
        ];
        $taskId = (int)$request->getPost('task_id', 0);
        $msg = $request->getPost('message', '');
        try {
            if ($request->isXmlHttpRequest() && $taskId && $msg) {
                $logger->save(Logger::MODULE_TASK, $taskId, Logger::ACTION_COMMENT, $msg);
                $logger->setOutputFormat(Logger::OUTPUT_HTML);

                $comments = $logger->get(Logger::MODULE_TASK, $taskId, Logger::ACTION_COMMENT);
                $result = [
                    'status' => 'success',
                    'msg' => 'Comment successfully saved.',
                    'comments' => $comments
                ];
            }
        } catch (\Exception $e) {
            // Do nothing
        }
        return new JsonModel($result);
    }

    public function getTaskBodyAction()
    {
        /**
         * @var \DDD\Service\Task $taskService
         * @var \DDD\Dao\Task\Subtask $subtaskDao
         */
        $taskService = $this->getServiceLocator()->get('service_task');
        $subtaskDao  = $this->getServiceLocator()->get('dao_task_subtask');
        $teamService = $this->getServiceLocator()->get('service_team_team');
        $userService = $this->getServiceLocator()->get('library_backoffice_auth');
        $userId      = $userService->getIdentity()->id;
        $request     = $this->getRequest();
        $result      = [
            'status' => 'success',
        ];
        try {
            if ($request->isXmlHttpRequest()) {
                $taskId = $request->getPost('task_id');
                $teamId = $request->getPost('team_id');
                if ($taskId > 0) {
                    $permissionSet = $taskService->composeUserTaskPermissions($taskId);

                    if (isset($permissionSet[Task::ACTION_CHANGE_STATUS]) && $permissionSet[Task::ACTION_CHANGE_STATUS] == 2) {
                        $taskService->setViewed($taskId);
                    }

                    $task = $taskService->getHousekeepingTask($taskId);

                    $task['subtasks'] = $subtaskDao->getTaskSubtasks($taskId);

                    $task['incident_reports'] = $taskService->getIncidentReports([
                        'task_id' => $taskId,
                        'res_id'  => $task['res_id']
                    ]);

                    $partialViewHelper = $this->getServiceLocator()->get('ViewHelperManager')->get('partial');
                    $result = [
                        'status' => 'success',
                        'html' => $partialViewHelper('backoffice/housekeeping-tasks/partial/task-body', [
                            'task' => $task,
                            'permissionSet' => $permissionSet,
                        ]),
                    ];
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }
}
