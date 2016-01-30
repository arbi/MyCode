<?php
namespace Backoffice\Controller;

use DDD\Service\Finance\Expense\ExpenseTicket;
use DDD\Service\User;
use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Controller\ControllerBase;
use Library\Utility\Helper;
use Library\Constants\TextConstants;
use Library\Constants\Roles;
use Library\Constants\DbTables;
use Library\Constants\DomainConstants;
use Library\Constants\Constants;
use FileManager\Constant\DirectoryStructure;

use DDD\Service\Booking\BookingTicket as ReservationStatus;
use DDD\Service\Task as TaskService;
use DDD\Service\User as UserService;
use DDD\Service\HouseKeeping\HouseKeeping;
use DDD\Service\Frontier as FrontierService;

use Zend\Http\Request;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\ProgressBar;

use Backoffice\Form\Task as TaskForm;
use Backoffice\Form\TaskUpload as TaskUploadForm;
use Backoffice\Form\SearchTaskForm as SearchTaskForm;
use Backoffice\Form\InputFilter\TaskFilter;

/**
 *
 */
class TaskController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var BackofficeAuthenticationService $user
         * @var \DDD\Service\Team\Team $teamService
         * @var \DDD\Service\User\Main $userMainService
         * @var \DDD\Service\User $userService
         * @var \DDD\Service\Task $taskService
         */
        $user = $this->getServiceLocator()->get('library_backoffice_auth');
        $userMainService = $this->getServiceLocator()->get('service_user_main');
        $userService = $this->getServiceLocator()->get('service_user');
        $teamService = $this->getServiceLocator()->get('service_team_team');
        $taskService = $this->getServiceLocator()->get('service_task');
        $tagService  = $this->getServiceLocator()->get('service_tag_tag');

        $request = $this->getRequest();
        $tagId   = $request->getQuery('tag_id', 0);
        $departmentId = $userMainService->getUserDepartmentId($user->getIdentity()->id);

        // define columns
        $router = $this->getEvent()->getRouter();
        $ajaxSourceUrl = $router->assemble(['controller' => 'task', 'action' => 'get-task-json'], ['name' => 'backoffice/default']);


        $teams  = $teamService->getTeamList(null, 0, false, true);

        $coTeams     = [];
        $coTeams[0] = '-- All Teams --';
        foreach ($teams as $team) {
            $coTeams[$team->getId()] = $team->getName();
        }

        $taskTypes = $taskService->getTaskTypesForSelect();
        $taskTypes[0] = '-- All Types --';

        $noAvatar = '//' . DomainConstants::BO_DOMAIN_NAME . Constants::VERSION . 'img/no40.gif';
        $usersDomain   = $taskService->getUsers();
        $users = [];
        array_push($users, [
            'id'     => -1,
            'name'   => 'Unassigned',
            'avatar' => $noAvatar
        ]);

        foreach ($usersDomain as $row){
            $avatar = $userService->getAvatarForSelectize($row->getId(), $row->getAvatar());

            array_push($users, [
                'id'     =>  $row->getId(),
                'name'   => $row->getFirstName() . ' ' . $row->getLastName(),
                'avatar' => $avatar
            ]);
        }

        $usersRes = $userService->getUsersList(false, true);

        $allUsers = [];
        foreach ($usersRes as $userRow) {
            $avatar = $userService->getAvatarForSelectize($userRow->getId(), $userRow->getAvatar());

            $allUsers[$userRow->getId()] = [
                'id'     => $userRow->getId(),
                'name'   => $userRow->getFullName(),
                'avatar' => $avatar,
            ];
        }

        $anyTeamMember = $allUsers[User::ANY_TEAM_MEMBER_USER_ID];

        $options = [
            'teams'      => $coTeams,
            'task_types' => $taskTypes,
            'users'      => $users
        ];

  	    $form         = new SearchTaskForm('search-task', $options);
  	    $formTemplate = 'form-templates/search-task';

  	    //Source code
  	    $viewModelForm = new ViewModel();
  	    $viewModelForm->setVariables(
            [
                'form'  => $form
  	        ]
        );

  	    $viewModelForm->setTemplate($formTemplate);

        $viewModel = new ViewModel([
            'ajaxSourceUrl' => $ajaxSourceUrl,
            'identity'      => $user->getIdentity(),
            'departmentId'  => $departmentId,
            'users'         => json_encode($users, true),
            'allUsers'      => $allUsers,
            'anyTeamMember' => $anyTeamMember,
            'tags'          =>$tagService->getAllTagsAsArray(),
            'tagId'         =>$tagId
        ]);

      	$viewModel->addChild($viewModelForm, 'formOutput');
      	$viewModel->setTemplate('backoffice/task/index');

      	return $viewModel;
    }

    public function getTaskJsonAction()
    {
    	/**
		 * @var \DDD\Service\Task $service
    	 */
    	$service = $this->getTaskService();

    	// get query parameters
    	$queryParams = $this->params()->fromQuery();

        $iDisplayStart  = $queryParams["iDisplayStart"];
        $iDisplayLength = $queryParams["iDisplayLength"];
        $sortCol        = (int)$queryParams['iSortCol_0'];
        $sortDir        = $queryParams['sSortDir_0'];

    	// get reservations data
    	$tasks = $service->getTaskBasicInfo($iDisplayStart, $iDisplayLength, $queryParams, $sortCol, $sortDir);

        $aaData = $tasks['result'];
    	$count  = $tasks['count'];

    	return new JsonModel([
            'iTotalRecords'        => $count,
            'iTotalDisplayRecords' => $count,
            'iDisplayStart'        => $iDisplayStart,
            'iDisplayLength'       => (integer)$iDisplayLength,
            "aaData"               => $aaData,
        ]);
    }

    protected function getFormData($actionsSet, $id, $autoFilledData = [])
    {
	    $params  = [];
        $data    = false;
        $service = $this->getTaskService();
        $options = $service->getOptions();

        if ($id > 0) {
            $data = $service->getData($id);
        }

        $form = new TaskForm($data, $options, $actionsSet, $autoFilledData);

        $params['form'] = $form;
        $params['data'] = $data;
        $params['options'] = $options;

        return $params;
    }

    public function editAction()
    {
        /**
         * @var Logger $logger
         * @var TaskService $service
         * @var \DDD\Service\User $userService
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         */
        $logger         = $this->getServiceLocator()->get('ActionLogger');
        $auth           = $this->getServiceLocator()->get('library_backoffice_auth');
        $userService    = $this->getServiceLocator()->get('service_user');
        $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');

        $request = $this->getRequest();
        $autoFilledData = [];

        $autoFilledData['res_id']            = $request->getQuery('res_id', false);
        $autoFilledData['res_number']        = $request->getQuery('res_number', false);
        $autoFilledData['property_id']       = $request->getQuery('apartment_id', false);
        $autoFilledData['property_name']     = $request->getQuery('apartment_name', false);
        $autoFilledData['building_id']       = $request->getQuery('building_id', false);
        $autoFilledData['building_name']     = $request->getQuery('building_name', false);
        $autoFilledData['task_type']         = $request->getQuery('type', false);
        $autoFilledData['team_id']           = $request->getQuery('team', false);
        $autoFilledData['following_team_id'] = $request->getQuery('following_team', false);
        $autoFilledData['title']             = $request->getQuery('title', false);
        $autoFilledData['related_task']      = $request->getQuery('related_task', false);

        if ($autoFilledData['res_id']) {
            $bookingInfo = $reservationDao->fetchOne(['id' => $autoFilledData['res_id']]);
            if ($bookingInfo) {
                $tempStart = strtotime(date('Y-m-d', strtotime($bookingInfo->getDateFrom())) . ' ' . date('H:i:s', strtotime('+1 hours')));
                if ($tempStart < time()) {
                    $tempStart = time();
                    $tempStartFormatted = date('Y-m-d H:i:s');
                } else {
                    $tempStartFormatted = date('Y-m-d H:i:s', $tempStart);
                }
                $autoFilledData['start_date'] = $tempStartFormatted;
                $autoFilledData['end_date']   = date('Y-m-d H:i:s', $tempStart+3600);
            }
        }

        $id       = $this->params()->fromRoute('id', 0);
        $service  = $this->getTaskService();
        $required = 'no';

        $logData = $logger->getDatatableData(
            Logger::MODULE_TASK,
            $id
        );

        if ($id > 0) {
            if (!$service->getTaskDao()->checkRowExist(DbTables::TBL_TASK, 'id', $id)) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
                return $this->redirect()->toRoute('backoffice/default', ['controller' => 'task']);
            }
        }
        $actionsSet = $service->composeUserTaskPermissions($id);
        $hasTagManagerRole = $auth->hasRole(Roles::ROLE_TAG_MANAGEMENT) ? 1 : 0;
        if ($id > 0) {
            if (empty($actionsSet[TaskService::ACTION_VIEW])) {
                Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_VIEW_PERMISSION]);
                return $this->redirect()->toRoute('backoffice/default', ['controller' => 'task']);
            }
            // Responsible and Helpers
            if (isset($actionsSet[TaskService::ACTION_CHANGE_STATUS]) && $actionsSet[TaskService::ACTION_CHANGE_STATUS] == 2) {
                $service->setViewed($id);
            }
        }

        $formData = $this->getFormData($actionsSet, $id, $autoFilledData);
        $usersRes = $userService->getUsersList(true, false);

        $users = [];
        foreach ($usersRes as $user) {
            $avatar = $userService->getAvatarForSelectize($user->getId(), $user->getAvatar());
            array_push($users, [
                'id' => $user->getId(),
                'name' => $user->getFullName(),
                'avatar' => $avatar,
            ]);
        }

        $anyTeamMemberId  = User::ANY_TEAM_MEMBER_USER_ID;
        $autoVerifyUserId = User::AUTO_VERIFY_USER_ID;

        $uploadForm = new TaskUploadForm('task-files');

        // Apartment Url
        $apartmentId = $formData['form']->get('property_id')->getValue();

        if (!empty($apartmentId) && $auth->hasRole(Roles::ROLE_APARTMENT_MANAGEMENT)) {
            $apartmentUrl = $this->url()->fromRoute(
                'apartment',
                ['apartment_id' => $apartmentId]
            );
        } elseif (!empty($apartmentId) && $auth->hasRole(Roles::ROLE_FRONTIER_MANAGEMENT)) {
            $apartmentUrl = $this->url()->fromRoute(
                'frontier',
                [],
                ['query' => [
                    'id' => FrontierService::CARD_APARTMENT . '_' . $apartmentId
                ]]
            );
        } else {
            $apartmentUrl = FALSE;
        }

        // Building Url
        $buildingId = $formData['form']->get('building_id')->getValue();

        if (!empty($buildingId) && $auth->hasRole(Roles::ROLE_FRONTIER_MANAGEMENT)) {
            $buildingUrl = $this->url()->fromRoute(
                'frontier',
                [],
                ['query' => [
                    'id' => FrontierService::CARD_BUILDING . '_' . $buildingId
                ]]
            );
        } else {
            $buildingUrl = FALSE;
        }

        // Reservation Url
        $reservationId = $formData['form']->get('res_id')->getValue();

        if (!empty($reservationId) && $auth->hasRole(Roles::ROLE_BOOKING_MANAGEMENT)) {
            $reservationUrl = $this->url()->fromRoute(
                'backoffice/default',
                [
                    'controller' => 'booking',
                    'action' => 'edit',
                    'id' => $formData['form']->get('res_number')->getValue()
                ]
            );
        } elseif (!empty($reservationId) && $auth->hasRole(Roles::ROLE_FRONTIER_MANAGEMENT)) {
            $reservationUrl = $this->url()->fromRoute(
                'frontier',
                [],
                ['query' => [
                    'id' => FrontierService::CARD_RESERVATION . '_' . $reservationId
                ]]
            );
        } else {
            $reservationUrl = FALSE;
        }

        return new ViewModel([
            'uploadForm'       => $uploadForm,
            'taskForm'         => $formData['form'],
            'data'             => $formData['data'],
            'required_field'   => $required,
            'actionsSet'       => $actionsSet,
            'id'               => $id,
            'historyData'      => (
                $id
                ? json_encode($logData)
                : '{}'
            ),
            'allUsers'         => $formData['options']['users'],
            'allTags'          => $formData['options']['tags'],
            'users'            => $users,
            'anyTeamMemberId'  => $anyTeamMemberId,
            'autoVerifyUserId' => $autoVerifyUserId,
            'apartmentUrl'     => $apartmentUrl,
            'buildingUrl'      => $buildingUrl,
            'reservationUrl'   => $reservationUrl,
            'canAddTag'        => $hasTagManagerRole,
        ]);
    }

    public function ajaxsaveAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'success',
            'msg'    => $request->getPost('edit_id', false) ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_ADD,
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $service        = $this->getTaskService();
                $id             = $request->getPost('edit_id');
                $noFlushMessage = $request->getPost('no_flush_message', 0);
                $actionsSet     = $service->composeUserTaskPermissions($id);

                if ($id > 0) {

                    if (empty($actionsSet)) {
                        $result['status'] = 'error';
                        $result['msg']    = TextConstants::SERVER_ERROR;

                        return new JsonModel($result);
                    }
                }

                $formData = $this->getFormData($actionsSet, $id);
                $form     = $formData['form'];
                $messages = '';
                $form->setInputFilter(new TaskFilter($actionsSet));

                if ($request->isPost()) {
                    $data = $request->getPost();
                    $form->setData($data);
                    if ($form->isValid()) {
                        $vData    = $form->getData();
                        $response = $service->taskSave((array)$vData, $actionsSet);
                        if (!$noFlushMessage) {
                            Helper::setFlashMessage(['success' => $id ? TextConstants::SUCCESS_UPDATE : TextConstants::SUCCESS_ADD]);
                        }
                        $result['id'] = $response;
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
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    //@ToDo This method is deprecated. Instead use ajaxChangeStatusAction
    public function ajaxMarkTaskAsDoneAction()
    {
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            $request = $this->getRequest();

            if ($request->isXmlHttpRequest()) {
                $taskId = (int)$request->getPost('taskId', false);

                $taskService = $this->getTaskService();

                $taskPermissions = $taskService->composeUserTaskPermissions($taskId);

                if (!isset($taskPermissions[TaskService::ACTION_CHANGE_STATUS])) {
                    $result = [
                        'status' => 'error',
                        'msg'    => TextConstants::TASK_ERROR_NO_PERMISSION_CHANGE_STATUS
                    ];
                } else {
                    $taskService->changeTaskStatus($taskId, TaskService::STATUS_DONE);

                    $result = [
                        'status' => 'success',
                        'msg'    => TextConstants::TASK_STATUS_CHANGE_MARKED_DONE
                    ];
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxChangeStatusAction()
    {
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::SERVER_ERROR,
        ];

        try {
            $request = $this->getRequest();

            if ($request->isXmlHttpRequest()) {
                $taskId = (int)$request->getPost('task_id', false);
                $status = (int)$request->getPost('status', false);

                $taskService = $this->getTaskService();

                $taskPermissions = $taskService->composeUserTaskPermissions($taskId);

                if (!isset($taskPermissions[TaskService::ACTION_CHANGE_STATUS])) {
                    $result = [
                        'status' => 'error',
                        'msg'    => TextConstants::TASK_ERROR_NO_PERMISSION_CHANGE_STATUS
                    ];
                } else {
                    $changeResult = $taskService->changeTaskStatus($taskId, $status);
                     if (is_array($changeResult)) {
                         $result = array_merge(
                             [
                             'status' => 'success',
                             'msg' => TextConstants::TASK_STATUS_CHANGED
                         ],
                             $changeResult
                         );
                     }
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxGetPropertyAction()
    {
        $request = $this->getRequest();
        $result = [
            'rc' => '00',
            'result' => [],
        ];

        try {
            if ($request->isXmlHttpRequest()) {
               $txt      = strip_tags(trim($request->getPost('txt')));
               $mode     = strip_tags(trim($request->getPost('mode')));
               $service  = $this->getServiceLocator()->get('service_accommodations');
               $result   = $service->getProductsForAutocomplete($txt, true, $mode);
            }
        } catch (\Exception $e) {
            $result['rc'] = '01';
        }

        return new JsonModel($result);
    }

    public function ajaxGetBuildingAction()
    {
        $request = $this->getRequest();
        $result = [
            'rc' => '00',
            'result' => [],
        ];

        try {
            if ($request->isXmlHttpRequest()) {
               $txt      = strip_tags(trim($request->getPost('txt')));
               $service  = $this->getServiceLocator()->get('service_apartment_group');
               $result   = $service->getBuildingsByAutocomplate($txt, true, true);
            }
        } catch (\Exception $e) {
            $result['rc'] = '01';
        }

        return new JsonModel($result);
    }

    public function ajaxAssignTeamBasedOnTypeAction()
    {
        /**
         * @var Request $request
         * @var TaskService $taskService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost('type')) {
                $type = (int)($request->getPost('type'));
                $apartmentId = (int)($request->getPost('apartmentId'));
                $buildingId = (int)($request->getPost('buildingId'));

                $taskService = $this->getTaskService();
                $teamId = $taskService->getTeamBasedOnType($type, $apartmentId, $buildingId);

                if ($teamId) {
                    $result = [
                        'team_id' => $teamId,
                        'status' => 'success'
                    ];
                } else {
                    $result = [
                        'team_id' => 0,
                        'status' => 'success'
                    ];
                }
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function ajaxCheckVerifiableBasedOnTypeAction()
    {
        /**
         * @var Request $request
         * @var TaskService $taskService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost('type')) {
                $type = (int)($request->getPost('type'));

                $taskService = $this->getTaskService();
                $isVerifiable = $taskService->checkVerifiableBasedOnType($type);

                $result = [
                    'is_verifiable' => $isVerifiable,
                    'status' => 'success'
                ];
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function ajaxGetSubtasksBasedOnTypeAction()
    {
        /**
         * @var Request $request
         * @var TaskService $taskService
         */
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost('type')) {
                $type = (int)($request->getPost('type'));

                $taskService = $this->getTaskService();
                $subtasks = $taskService->getSubasksBasedOnType($type);

                if ($subtasks) {
                    $result = [
                        'subtasks' => $subtasks,
                        'status' => 'success'
                    ];
                } else {
                    $result = [
                        'subtasks' => [],
                        'status' => 'success'
                    ];
                }
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function ajaxGetReservationDetailsAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Dao\Booking\Booking $reservationDao
         */
        $request = $this->getRequest();
        $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');

        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
        ];

        try {
            if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost('resNumber')) {
                $resNumber = $request->getPost('resNumber');

                $card = $reservationDao->getResDetailsForTask($resNumber);

                if ($card) {
                    $result = [
                        'id' => $card->getId(),
                        'status' => 'success',
                        'apartmentId' => $card->getApartmentAssignedId(),
                        'apartmentName' => $card->getApartmentAssigned(),
                        'buildingId' => $card->getBuildingId(),
                        'buildingName' => $card->getBuilding()
                    ];
                } else {
                    $result = [
                        'status' => 'error',
                        'msg' => 'Invalid Reservation Number'
                    ];
                }
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return new JsonModel($result);
    }

    public function ajaxCheckReservationNumberAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Dao\Booking\Booking $reservationDao
         */
        $request = $this->getRequest();
        $response = $this->getResponse();
        $response->setStatusCode(200);
        $response->setContent("false");
        $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');

        try {
            if ($request->isPost() && $request->isXmlHttpRequest() && $request->getPost('res_number')) {
                $resNumber = $request->getPost('res_number');

                $res = $reservationDao->fetchOne(['res_number' => $resNumber], ['id']);

                if ($res) {
                    $response->setContent("true");
                }
            }
        } catch (\Exception $ex) {
            // do nothing
        }

        return $response;
    }

    /**
     * @return \DDD\Service\Task
     */
    public function getTaskService()
    {
        return $this->getServiceLocator()->get('service_task');
    }

    public function uploadAttachmentsAction()
    {
        $form = new TaskUploadForm('task-files');
        $request = $this->getRequest();

        $files = [];
        $attachmentNames = [];
        if ($request->isPost()) {
            // Postback
            $data = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $form->setData($data);
            if ($form->isValid()) {
                $files = $form->getData()['file'];
                foreach ($files as $file) {
                    $attachmentNames[] = pathinfo($file['tmp_name'], PATHINFO_BASENAME);
                }
            }
        }

        if (count($files)) {
            return new JsonModel([
                'status' => 'success',
                'msg' => 'Attachment successfully added.',
                'attachments' => $attachmentNames
            ]);
        } else {
            return new JsonModel([
                'status' => 'error',
                'msg' => 'Failed to upload attachments.'
            ]);
        }
    }

    public function downloadAttachmentAction()
    {
        $attachmentId = (int)$this->params()->fromRoute('attachment_id', false);
        if ($attachmentId) {
            /** @var \DDD\Dao\Task\Attachments $taskAttachmentsDao */
            $taskAttachmentsDao = $this->getServiceLocator()->get('dao_task_attachments');
            /** @var \DDD\Domain\Task\Attachments $file */
            $file = $taskAttachmentsDao->getAttachmentById($attachmentId);

            $filePath = DirectoryStructure::FS_UPLOADS_TASK_ATTACHMENTS .
                            $file->getPath() . '/' .
                            $file->getTaskId() . '/' .
                            $file->getFile();

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
    }

    /**
     * @todo: refactor this method to return JsonModel on every case
     */
    public function ajaxDeleteAttachmentAction()
    {
        $request = $this->getRequest();
        $taskAttachmentsDao = $this->getServiceLocator()->get('dao_task_attachments');
        $attachmentId = (int)$request->getPost('attachmentId', 0);

        if ($request->isXmlHttpRequest() && $attachmentId) {
            $file = $taskAttachmentsDao->getAttachmentById($attachmentId);
            $fullPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_UPLOADS_ROOT
                . DirectoryStructure::FS_UPLOADS_TASK_ATTACHMENTS
                . $file->getPath() . '/'
                . $file->getTaskId() . '/'
                . $file->getFile();

            @unlink($fullPath);

            $taskAttachmentsDao->delete(['id' => $attachmentId]);

            return  new JsonModel([
                'status' => 'success',
                'msg' => 'Attachment successfully deleted.'
            ]);
        }
    }

    public function ajaxCheckoutAction()
    {
        /**
         * @var \DDD\Service\Location $cityService
         * @var \DDD\Dao\Booking\Booking $reservationDao
         * @var Logger $logger
         */
        $cityService = $this->getServiceLocator()->get('service_location');
        $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $request = $this->getRequest();
        $reservationId = (int)$request->getPost('reservationId', 0);
        $result = [
            'status' => 'success',
            'msg' => 'Guest arrival status saved.'
        ];

        try {
            if ($request->isXmlHttpRequest() && $reservationId) {
                $res = $reservationDao->fetchOne(['id' => $reservationId]);
                $currentDateCity = $cityService->getCurrentDateCity($res->getApartmentCityId());
                $saveArray = [
                    'arrival_status' => ReservationStatus::BOOKING_ARRIVAL_STATUS_CHECKED_OUT,
                    'departure_date' => $currentDateCity
                ];
                $reservationDao->save($saveArray, ['id' => $reservationId, 'departure_date' => null]);
                $logger->save(Logger::MODULE_BOOKING, $reservationId, Logger::ACTION_CHECK_IN, ReservationStatus::BOOKING_ARRIVAL_STATUS_CHECKED_OUT);
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxReportIncidentAction()
    {
        /**
         * @var \DDD\Service\Location $cityService
         * @var \DDD\Dao\Booking\Booking $reservationDao
         */
        $cityService = $this->getServiceLocator()->get('service_location');
        $reservationService = $this->getServiceLocator()->get('service_booking');
        $user = $this->getServiceLocator()->get('library_backoffice_auth');
        $taskService = $this->getServiceLocator()->get('service_task');
        $request = $this->getRequest();
        $reservationId = (int)$request->getPost('res_id', 0);
        $result = [
            'status' => 'success',
            'msg' => 'Incident report has been created.'
        ];

        try {
            if ($request->isXmlHttpRequest() && $reservationId) {
                $res = $reservationService->getBasicInfoForAutoTaskCreationById($reservationId);
                $apartmentId = $res['apartment_id_assigned'];
                $buildingId  = $res['building_id'];
                $currentDateCity = $cityService->getCurrentDateCity($res['city_id']);
                $ninetySixHoursLaterDateCity = $cityService->getIncrementedDateCity($res['city_id'],96);
                $gemId = (int)$res['gem_id'];
                $creator = $user->getIdentity()->id;

                switch ((int)$request->getPost('val', 0)) {
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
                        $damageType = $request->getPost('description', 0);
                        break;
                }
                $title = $damageType;
                    $description = '';


                $taskService->createReportIncidentTask(
                    $reservationId,
                    $apartmentId,
                    $buildingId,
                    $currentDateCity,
                    $ninetySixHoursLaterDateCity,
                    $title,
                    $creator,
                    $description,
                    $gemId
                    );
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxQuickTaskCreateAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR
        ];

        try {
            if ($request->isXmlHttpRequest()) {
                $postData = $request->getPost();
                $taskService = $this->getTaskService();
                $response = $taskService->createTaskFromFrontier($postData);
                if ($response) {
                    $result['status'] = 'success';
                    $result['msg'] = TextConstants::SUCCESS_CREATED;
                }
            }
        } catch (\Exception $e) {
            // do nothing
        }

        return new JsonModel($result);
    }
}
