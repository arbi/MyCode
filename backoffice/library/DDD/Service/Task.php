<?php
namespace DDD\Service;

use DDD\Dao\Finance\Expense\Expenses;
use DDD\Dao\Task\Staff as TaskStaffDAO;
use DDD\Dao\Task\Tag;
use DDD\Dao\Task\Task as TaskDAO;

use DDD\Dao\Task\Type as TaskTypeDAO;
use DDD\Domain\Booking\Booking as BookingDomain;
use DDD\Domain\Booking\BookingTicket;
use DDD\Service\Team\Team as TeamService;
use DDD\Service\Team\Team;
use DDD\Service\User\Main as UserMain;
use DDD\Service\User as UserService;
use DDD\Service\Lock\General as LockGeneral;

use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Roles;
use Library\Constants\Constants;
use Library\Constants\TextConstants;
use Library\Upload\Files;
use Library\Utility\Helper;
use Library\Constants\DbTables;

use FileManager\Constant\DirectoryStructure;

class Task extends ServiceBase
{
    const STATUS_NEW      = 1;
    const STATUS_VIEWED   = 2;
    const STATUS_BLOCKED  = 3;
    const STATUS_STARTED  = 4;
    const STATUS_DONE     = 5;
    const STATUS_VERIFIED = 6;
    const STATUS_CANCEL   = 7;
    const STATUS_ALL_OPEN = 10;

    const STAFF_CREATOR     = 1;
    const STAFF_RESPONSIBLE = 2;
    const STAFF_HELPER      = 3;
    const STAFF_FOLLOWER    = 4;
    const STAFF_VERIFIER    = 5;
    const STAFF_MANAGER     = 6;

    const ACTION_VIEW                = 1;
    const ACTION_COMMENT             = 2;
    const ACTION_MANAGE_STAFF        = 3;
    const ACTION_MANAGE_SUBTASKS     = 4;
    const ACTION_CHANGE_STATUS       = 5;
    const ACTION_CHANGE_DETAILS      = 6;
    const ACTION_MANAGE_ATTACHMENTS  = 7;
    const ACTION_TAG                 = 8;

    const TYPE_CONTENT           = 1;
    const TYPE_REPAIR            = 2;
    const TYPE_CLEANING          = 3;
    const TYPE_APT_SERVICE       = 4;
    const TYPE_KEYFOB            = 5;
    const TYPE_INCIDENT_REPORT   = 6;
    const TYPE_PURCHASE_DELIVERY = 8;
    const TYPE_OTHER             = 9;
    const TYPE_RESERVATION       = 10;
    const TYPE_FINANCIAL         = 11;
    const TYPE_GUEST_SERVICE     = 12;
    const TYPE_CCCA              = 13;

    const DATE_ACTION = 1;
    const DATE_DUE    = 2;

    const TASK_PRIORITY_NORMAL    = 2;
    const TASK_PRIORITY_HIGH      = 5;
    const TASK_PRIORITY_IMPORTANT = 8;
    const TASK_PRIORITY_CRITICAL  = 9;

    const INCIDENT_REPORT_SMOKING = 1;
    const INCIDENT_REPORT_KEYS    = 3;
    const INCIDENT_REPORT_DIRTY   = 4;
    const INCIDENT_REPORT_BROKEN  = 5;
    const INCIDENT_REPORT_OTHER  = 999;

    const TASK_GROUP_FRONTIER   = 'Frontier';
    const TASK_IS_HOUSEKEEPING  = 1;
    const TASK_EXTRA_INSPECTION = 1;

    // Cases used to prepare task description for inspection or cleaning
    const CASE_CANCEL = 'canceled';
    const CASE_MOVE = 'moved';

    const ENTITY_TYPE_INCIDENT = 1;

    public static function getModuleTypes()
    {
        return [self::ENTITY_TYPE_INCIDENT => 'Incident'];
    }

    public static function getStatusesInWords()
    {
        return [
            self::STATUS_NEW      => 'New',
            self::STATUS_VIEWED   => 'Viewed',
            self::STATUS_BLOCKED  => 'Blocked',
            self::STATUS_STARTED  => 'Started',
            self::STATUS_DONE     => 'Done',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_CANCEL   => 'Canceled'
        ];
    }

    public function getData($id)
    {
        /**
         * @var \DDD\Dao\Task\Attachments $taskAttachmentsDao
         * @var \DDD\Domain\Finance\Expense\Expenses $expenseDomain
         * @var Expenses $expenseDao
         */
        $taskDao = $this->getTaskDao();
        $taskStaffDao = $this->getTaskStaffDao();
        $taskSubtasksDao = $this->getTaskSubtasksDao();
        $taskAttachmentsDao = $this->getServiceLocator()->get('dao_task_attachments');
        $expenseDao = $this->getServiceLocator()->get('dao_finance_expense_expenses');
        $taskTagDao = $this->getServiceLocator()->get('dao_task_tag');


        $task = $taskDao->getTaskById((int)$id);
        if (!$task) {
            throw new \Exception("Could not find row");
        }

        $this->registry->set('taskMain', $task);

        $taskFollowers = $taskStaffDao->getTaskFollowers($id);
        $taskHelpers = $taskStaffDao->getTaskHelpers($id);
        $taskSubtasks = $taskSubtasksDao->getTaskSubtasks($id);
        $taskAttachments = $taskAttachmentsDao->fetchAll(['task_id' => $id]);

        $followerIds = [];
        foreach ($taskFollowers as $follower) {
            $followerIds[] = $follower->getId();
        }

        $helperIds = [];
        foreach ($taskHelpers as $helper) {
            $helperIds[] = $helper->getId();
        }
        $taskTags = $taskTagDao->getTagsAttachedToTask($id);
        $tagIds = [];
        foreach($taskTags as $tag) {
            array_push($tagIds, $tag->getTagId());
        };
        $this->registry->set('followerIds', $followerIds);
        $this->registry->set('helperIds', $helperIds);
        $this->registry->set('taskSubtasks', $taskSubtasks);
        $this->registry->set('taskAttachments', $taskAttachments);
        $this->registry->set('selectedTags', $tagIds);
        return $this->registry;
    }

    public function getOptions()
    {
        /**
         * @var \DDD\Service\Team\Team $teamService
         * @var \DDD\Service\User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');

        $teamService = $this->getServiceLocator()->get('service_team_team');

        $taskTypes = $this->getTaskTypesForSelect();

        $teams       = $teamService->getTeamList(null, 0, true, true);
        $allTeamsRes = $teamService->getTeamList(null, 0, false);

        $coTeams     = [];
        $coTeams[0] = '-- Teams --';
        foreach ($teams as $team) {
            $coTeams[$team->getId()] = $team->getName();
        }

        $params['all_teams'] = [];
        foreach ($allTeamsRes as $team) {
            $params['all_teams'][$team->getId()] = $team->getName();
        }

        $params['teams'] = $coTeams;
        $params['task_types'] = $taskTypes;

        $res   = $userService->getUsersList(false, true);
        $params['users'] = [];

        foreach ($res as $row){
            $avatar = $userService->getAvatarForSelectize($row->getId(), $row->getAvatar());

            $params['users'][$row->getId()] = [
                'id' => $row->getId(),
                'name' => $row->getFullName(),
                'avatar' => $avatar
            ];
        }
        $tagsService = $this->getServiceLocator()->get('service_tag_tag');
        $params['tags'] = $tagsService->getAllTagsAsArray();


        return $params;
    }


    public function taskSave($data, $actionsSet, $isSystemUser = false)
    {
        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var Logger $logger
         * @var \DDD\Domain\Task\Task $oldDataGeneral
         * @var \DDD\Dao\Task\Staff $taskStaffDao
         * @var \DDD\Dao\Task\Subtask $taskSubtaskDao
         * @var \DDD\Dao\Task\Task $taskDao
         * @var \DDD\Dao\Task\Attachments $attachmentsDao
         * @var \DDD\Domain\Task\Task $oldDataGeneral
         * @var \DDD\Domain\Task\Staff[] $oldDataStaff
         * @var \DDD\Dao\Task\Tag $taskTagDao;
         * @var \DDD\Dao\Tag\Tag $tagDao;
         * @var \DDD\Dao\Team\Team $teamsDao;
         */
        $taskDao        = $this->getTaskDao();
        $taskStaffDao   = $this->getTaskStaffDao();
        $taskSubtaskDao = $this->getTaskSubtasksDao();
        $attachmentsDao = $this->getServiceLocator()->get('dao_task_attachments');
        $taskTagDao     = $this->getServiceLocator()->get('dao_task_tag');
        $tagDao         = $this->getServiceLocator()->get('dao_tag_tag');
        $teamsDao       = $this->getServiceLocator()->get('dao_team_team');

        $logger = $this->getServiceLocator()->get('ActionLogger');
        if (!$isSystemUser) {
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $authId = $auth->getIdentity()->id;
        }
        else {
            $authId =  UserMain::SYSTEM_USER_ID;
        }

        if (isset($data['edit_id']) && $data['edit_id'] > 0) {
            $oldDataGeneral = $taskDao->getTaskById($data['edit_id']);
            $oldDataStaff   = $taskStaffDao->getTaskStaff($data['edit_id']);
        }

        $saveData = [];
        if (isset($data['follower_ids']) && !($data['follower_ids'])) {
            $data['follower_ids'] = [];
        }

        if (isset($data['helper_ids']) && !($data['helper_ids'])) {
            $data['helper_ids'] = [];
        }

        if (isset($data['tags']) && !($data['tags'])) {
            $data['tags'] = [];
        } else if (isset($data['tags'])){
            $data['tags'] = explode(',', $data['tags']);
        }

        if (!empty($actionsSet[self::ACTION_CHANGE_STATUS]) && isset($data['task_status'])) {
            $saveData['task_status'] = (int)($data['task_status'] ? $data['task_status'] : self::STATUS_NEW);

            if ((int)$data['task_status'] == self::STATUS_DONE) {
                $saveData['done_date'] = date('Y-m-d');
                if (isset($data['verifier_id']) &&
                    (
                        $data['verifier_id'] == User::AUTO_VERIFY_USER_ID
                    ||
                        (isset($data['responsible_id']) && $authId == $data['verifier_id'] && $authId == $data['responsible_id'])
                    )
                ) {
                    $saveData['task_status'] = self::STATUS_VERIFIED;
                }
            } elseif (isset($oldDataGeneral)
                && in_array($oldDataGeneral->getTask_status(), [self::STATUS_DONE, self::STATUS_VERIFIED])
                && !in_array($data['task_status'], [self::STATUS_DONE, self::STATUS_VERIFIED])
            ) {
                $saveData['done_date'] = null;
            }

            if ((int)$data['task_status'] == self::STATUS_DONE || (int)$data['task_status'] == self::STATUS_VERIFIED) {
                if (
                    (isset($data['responsible_id']) && $data['responsible_id'] == User::ANY_TEAM_MEMBER_USER_ID)
                    ||
                    (!isset($data['responsible_id']) && $oldDataGeneral->getResponsibleId() == User::ANY_TEAM_MEMBER_USER_ID)
                ) {
                    $data['responsible_id'] = $authId;
                }
            }
        }

        if (!empty($data['last_update_time'])) {
            $saveData['last_update_time'] = $data['last_update_time'];
        } else {
            $data['last_update_time'] = $saveData['last_update_time'] = date('Y:m:d H:i:s');
        }

        if (!empty($actionsSet[self::ACTION_CHANGE_DETAILS])) {

            if (isset($data['title'])) {
                $saveData['title'] = $data['title'];
            }

            if (isset($data['task_type'])) {
                $saveData['task_type'] = (int)$data['task_type'];
            }

            if (isset($data['task_priority'])) {
                $saveData['priority'] = (int)$data['task_priority'];
            }

            if (isset($data['description'])) {
                $saveData['description'] = $data['description'];
            }

            if (isset($data['start_date'])) {
                $saveData['start_date'] = $data['start_date'] ? date('Y-m-d H:i:00', strtotime($data['start_date'])) : null;
            }

            if (isset($data['end_date'])) {
                $saveData['end_date'] = $data['end_date'] ? date('Y-m-d H:i:00', strtotime($data['end_date'])) : null;
            }

            if (isset($data['property_id'])) {
                $saveData['property_id'] = (int)$data['property_id'];
            }

            if (isset($data['building_id'])) {
                $saveData['building_id'] = (int)$data['building_id'];
            }

            if (isset($data['related_task'])) {
                $saveData['related_task'] = $data['related_task'] ? (int)$data['related_task'] : null;
            }

            if (isset($data['res_id'])) {
                $saveData['res_id'] = $data['res_id'] ? (int)$data['res_id'] : null;
            }

            if (isset($data['team_id'])) {
                $saveData['team_id'] = (int)$data['team_id'];
            }

            if (isset($data['following_team_id'])) {
                $saveData['following_team_id'] = (int)$data['following_team_id'];
            }
        }

        if (
            isset($data['team_id'])
            && ($data['team_id'] == TeamService::TEAM_CONTACT_CENTER || $data['task_type'] == self::TYPE_KEYFOB)
            && (!isset($data['responsible_id']) || $data['responsible_id'] == '')
        ) {
            $data['responsible_id'] = User::ANY_TEAM_MEMBER_USER_ID;
        }

        if (isset($data['edit_id']) && $data['edit_id'] > 0) {
            $id = $data['edit_id'];
            if (isset($data['task_status']) && $data['task_status'] == self::STATUS_DONE && empty($actionsSet[self::ACTION_MANAGE_STAFF]) && $oldDataGeneral->getVerifierId() == User::AUTO_VERIFY_USER_ID) {
                $saveData['task_status'] = self::STATUS_VERIFIED;
            }
            $this->setComment($logger, $oldDataGeneral, $oldDataStaff, $data, $actionsSet);

            if (count($saveData)) {
                $taskDao->save($saveData, ['id' => (int)$id]);
            }
        } else {
            $data['creation_date'] = $saveData['creation_date'] = date('Y-m-d H:i:s');

            if (isset($data['responsible_id']) && $data['responsible_id'] == $authId) {
                $saveData['task_status'] = self::STATUS_VIEWED;
            }

            if (isset($data['creator_id']) && $data['creator_id'] == UserMain::SYSTEM_USER_ID) {
                $creatorId = $data['creator_id'];
            } else {
                $creatorId = $authId;
            }

            if (isset($data['is_hk'])) {
                $saveData['is_hk'] = $data['is_hk'];
            }

            if (isset($data['extra_inspection'])) {
                $saveData['extra_inspection'] = $data['extra_inspection'];
            }

            $id = $taskDao->save($saveData);
            $taskStaffDao->save([
                'task_id' => $id,
                'type' => self::STAFF_CREATOR,
                'user_id' => $creatorId
            ]);
        }

        if (!empty($actionsSet[self::ACTION_MANAGE_SUBTASKS]) && isset($data['subtask_description'])) {
            $existingSubtasks = $taskSubtaskDao->fetchAll(['task_id' => $id]);

            if (count($existingSubtasks)) {
                /** @var \DDD\Domain\Task\Subtask[] $existingSubtask */
                foreach ($existingSubtasks as $existingSubtask) {
                    // Remove subtask
                    if (empty($data['subtask_description']) || !isset($data['subtask_description'][$existingSubtask->getId()])) {
                        $taskSubtaskDao->delete(['id' => $existingSubtask->getId()]);
                        $logger->save(
                            Logger::MODULE_TASK,
                            $id,
                            Logger::ACTION_TASK_SUBTASK,
                            'Removed subtask <b>' . $existingSubtask->getDescription() . '</b>'
                        );
                        // Update Subtask
                    } else {
                        if (
                            $data['subtask_description'][$existingSubtask->getId()] != $existingSubtask->getDescription()
                            ||
                            (bool)$data['subtask_status'][$existingSubtask->getId()] != (bool)$existingSubtask->getStatus()
                        ) {
                            $taskSubtaskDao->save(
                                [
                                    'description' => $data['subtask_description'][$existingSubtask->getId()],
                                    'status'      => $data['subtask_status'][$existingSubtask->getId()],
                                ], [
                                    'id' => $existingSubtask->getId()
                                ]
                            );

                            if ($data['subtask_description'][$existingSubtask->getId()] != $existingSubtask->getDescription()) {
                                $logger->save(
                                    Logger::MODULE_TASK,
                                    $id,
                                    Logger::ACTION_TASK_SUBTASK,
                                    'Changed subtask description from <b>' . $existingSubtask->getDescription() . '</b> to <b>' .
                                    $data['subtask_description'][$existingSubtask->getId()] . '</b>'
                                );
                            }

                            if ((bool)$data['subtask_status'][$existingSubtask->getId()] != (bool)$existingSubtask->getStatus()) {
                                $logger->save(
                                    Logger::MODULE_TASK,
                                    $id,
                                    Logger::ACTION_TASK_SUBTASK,
                                    'Marked subtask <b>' . $data['subtask_description'][$existingSubtask->getId()] . '</b> ' .
                                    ($data['subtask_status'][$existingSubtask->getId()] ? 'Done' : 'Undone')
                                );
                            }
                        }

                        unset($data['subtask_description'][$existingSubtask->getId()]);
                        unset($data['subtask_status'][$existingSubtask->getId()]);
                    }
                }
            }

            if (!empty($data['subtask_description']) && count($data['subtask_description'])) {
                foreach ($data['subtask_description'] as $index => $subtaskDescription) {
                    if ($subtaskDescription) {
                        $taskSubtaskDao->save([
                            'task_id' => $id,
                            'description' => $subtaskDescription,
                            'status' => !empty($data['subtask_status'][$index]) ? 1 : 0
                        ]);

                        if ($id) {
                            $logger->save(
                                Logger::MODULE_TASK,
                                $id,
                                Logger::ACTION_TASK_SUBTASK,
                                'Added subtask <b>' . $subtaskDescription . '</b>'
                            );
                        }
                    }
                }
            }
        }

        if (!empty($actionsSet[self::ACTION_COMMENT]) && !empty($data['comments'])) {
            $logger->save(Logger::MODULE_TASK, $id, Logger::ACTION_COMMENT, $data['comments']);
        }

        if (!empty($actionsSet[self::ACTION_MANAGE_STAFF])) {

            if (isset($data['responsible_id'])) {
                $taskStaffDao->delete([
                    'task_id' => $id,
                    'type' => self::STAFF_RESPONSIBLE
                ]);
                if ($data['responsible_id']) {
                    $taskStaffDao->save([
                        'task_id' => $id,
                        'type' => self::STAFF_RESPONSIBLE,
                        'user_id' => $data['responsible_id']
                    ]);
                }
            }

            if (isset($data['verifier_id'])) {
                $taskStaffDao->delete([
                    'task_id' => $id,
                    'type' => self::STAFF_VERIFIER
                ]);

                if (
                    (!($data['verifier_id']) || $data['verifier_id'] == UserMain::SYSTEM_USER_ID)
                    &&
                    (empty($oldDataGeneral) || (isset($data['responsible_id']) && $oldDataGeneral->getResponsibleId() != $data['responsible_id']))
                ) {
                    $taskStaffDao->save([
                        'task_id' => $id,
                        'type' => self::STAFF_VERIFIER,
                        'user_id' => $authId
                    ]);
                } else if ($data['verifier_id']) {
                    $taskStaffDao->save([
                        'task_id' => $id,
                        'type' => self::STAFF_VERIFIER,
                        'user_id' => $data['verifier_id']
                    ]);
                }
            }

            if (isset($data['follower_ids'])) {
                $taskStaffDao->delete([
                    'task_id' => $id,
                    'type' => self::STAFF_FOLLOWER
                ]);
                if (count($data['follower_ids'])) {
                    foreach ($data['follower_ids'] as $followerId) {
                        $taskStaffDao->save([
                            'task_id' => $id,
                            'type' => self::STAFF_FOLLOWER,
                            'user_id' => $followerId
                        ]);
                    }
                }
            }

            if (isset($data['helper_ids'])) {
                $taskStaffDao->delete([
                    'task_id' => $id,
                    'type' => self::STAFF_HELPER
                ]);
                if (count($data['helper_ids'])) {
                    foreach ($data['helper_ids'] as $helperId) {
                        $taskStaffDao->save([
                            'task_id' => $id,
                            'type' => self::STAFF_HELPER,
                            'user_id' => $helperId
                        ]);
                    }
                }
            }
        }

        if (isset($data['tags']) && !empty($actionsSet[self::ACTION_TAG])) {
            $taskTagDao->delete([
                'task_id' => $id,
            ]);
            if (count($data['tags'])) {
                foreach ($data['tags'] as $tag) {
                    if (!is_numeric($tag)) {
                        $tagId = $tagDao->save(
                            [
                                'name' => $tag,
                                'style' => 'label-grey',
                            ]
                        );
                    } else {
                        $tagId = $tag;
                    }

                    $taskTagDao->save(
                        [
                            'task_id' => $id,
                            'tag_id'  => $tagId,
                        ]
                    );

                }
            }
        }

        if (!empty($actionsSet[self::ACTION_MANAGE_ATTACHMENTS])) {
            if (!empty($data['attachment_names'])) {
                $uploadFolder = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_UPLOADS_ROOT
                    . DirectoryStructure::FS_UPLOADS_TASK_ATTACHMENTS
                    . date('Y/m/d', strtotime($data['creation_date'])) . '/' . $id . '/';

                $attachmentNames = explode('###', $data['attachment_names']);

                foreach ($attachmentNames as $attachmentName) {
                    if ($attachmentName) {
                        $destination = $uploadFolder . $attachmentName;

                        $response = Files::moveFile(DirectoryStructure::FS_GINOSI_ROOT
                            . DirectoryStructure::FS_UPLOADS_ROOT
                            . DirectoryStructure::FS_UPLOADS_TMP
                            . $attachmentName, $destination);

                        if ($response) {
                            $attachmentsDao->save([
                                'task_id' => $id,
                                'file' => pathinfo($destination, PATHINFO_BASENAME)
                            ]);
                        }
                    }
                }
            }
        }

        return $id;
    }

    public function setViewed($id)
    {
        /**
         * @var Logger $logger
         * @var \DDD\Domain\Task\Task $result
         */
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $dao = $this->getTaskDao();
        $result = $dao->fetchOne(['id' => $id]);

        if ($result->getTask_status() == self::STATUS_NEW) {
            $logger->save(Logger::MODULE_TASK, $id, Logger::ACTION_VIEWED, 1);
            $dao->save(['task_status' => self::STATUS_VIEWED], ['id' => $id]);
        }
    }

    /**
     * @param $taskId int
     * @return array
     */
    public function composeUserTaskPermissions($taskId)
    {
        /**
         * @var BackofficeAuthenticationService $authenticationService
         * @var TeamService $teamService
         * @var TaskDAO $taskDao
         * @var TaskStaffDAO $taskStaffDao
         * @var \DDD\Domain\Task\Task $taskDomain
         * @var \DDD\Domain\Task\Staff[] $taskStaff
         * @var \DDD\Service\Task $taskService
         */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $teamService           = $this->getServiceLocator()->get('service_team_team');
        $taskDao               = $this->getServiceLocator()->get('dao_task_task');
        $taskStaffDao          = $this->getServiceLocator()->get('dao_task_staff');
        $taskService           = $this->getServiceLocator()->get('service_task');

        $taskTypeGroup = $taskDao->getTaskTypeGroup($taskId);

        $actionsSet = [];

        // If Global Task Manager or Task Creator give all permissions
        if ($authenticationService->hasRole(Roles::ROLE_GLOBAL_TASK_MANAGER) || !$taskId || ($taskTypeGroup == 'Frontier' && $authenticationService->hasRole(Roles::ROLE_GLOBAL_HOUSEKEEPING_MANAGER))) {
            $actionsSet[self::ACTION_MANAGE_SUBTASKS]    = true;
            $actionsSet[self::ACTION_CHANGE_STATUS]      = 1;
            $actionsSet[self::ACTION_CHANGE_DETAILS]     = true;
            $actionsSet[self::ACTION_MANAGE_STAFF]       = true;
            $actionsSet[self::ACTION_MANAGE_ATTACHMENTS] = true;
            $actionsSet[self::ACTION_VIEW]               = true;
            $actionsSet[self::ACTION_COMMENT]            = true;
            $actionsSet[self::ACTION_TAG]                = true;
        } else {

            if (!$taskService->getTaskDao()->checkRowExist(DbTables::TBL_TASK, 'id', $taskId)) {
                return $actionsSet;
            }

            $loggedInUserId = $authenticationService->getIdentity()->id;
            $userTeams = $teamService->getUserTeams($loggedInUserId);
            $taskDomain = $taskDao->fetchOne(
                ['id' => $taskId],
                ['team_id', 'following_team_id']
            );
            $taskTeamId = $taskDomain->getTeamId();
            $taskFollowingTeamId = $taskDomain->getFollowingTeamId();

            $taskResponsible = $taskStaffDao->fetchOne(['task_id' => $taskId, 'type' => self::STAFF_RESPONSIBLE]);

            // Give permissions based on user role in team linked to the task
            foreach ($userTeams as $userTeam) {
                if ($userTeam->getId() == $taskTeamId) {
                    switch ($userTeam->getStaffType()) {
                        case TeamService::STAFF_MANAGER:
                            $actionsSet[self::ACTION_MANAGE_SUBTASKS] = true;
                            $actionsSet[self::ACTION_CHANGE_STATUS] = 1;
                            $actionsSet[self::ACTION_CHANGE_DETAILS] = true;
                            $actionsSet[self::ACTION_MANAGE_ATTACHMENTS] = true;
                        case TeamService::STAFF_OFFICER:
                            $actionsSet[self::ACTION_MANAGE_STAFF] = true;
                            $actionsSet[self::ACTION_MANAGE_ATTACHMENTS] = true;
                        case TeamService::STAFF_MEMBER:
                            $actionsSet[self::ACTION_VIEW] = true;
                            $actionsSet[self::ACTION_COMMENT] = true;
                            if ($taskResponsible && $taskResponsible->getId() == User::ANY_TEAM_MEMBER_USER_ID && !isset($actionsSet[self::ACTION_CHANGE_STATUS])) {
                                $actionsSet[self::ACTION_CHANGE_STATUS] = 2;
                                $actionsSet[self::ACTION_MANAGE_SUBTASKS] = true;
                                $actionsSet[self::ACTION_MANAGE_ATTACHMENTS] = true;
                                $actionsSet[self::ACTION_TAG] = true;

                            }
                    }
                } else if ($userTeam->getId() == $taskFollowingTeamId) {
                    $actionsSet[self::ACTION_VIEW] = true;
                    $actionsSet[self::ACTION_COMMENT] = true;
                    $actionsSet[self::ACTION_MANAGE_ATTACHMENTS] = true;
                    $actionsSet[self::ACTION_TAG] = true;
                }
            }
            $taskStaff = $taskStaffDao->fetchAll(['task_id' => $taskId]);

            // Give permissions based on user role(s) for that task
            foreach ($taskStaff as $taskStaffMember) {
                if ($loggedInUserId == $taskStaffMember->getId()) {
                    switch ($taskStaffMember->getType()) {
                        case self::STAFF_MANAGER:
                        case self::STAFF_CREATOR:
                            $actionsSet[self::ACTION_MANAGE_STAFF] = true;
                            $actionsSet[self::ACTION_CHANGE_DETAILS] = true;
                            $actionsSet[self::ACTION_MANAGE_ATTACHMENTS] = true;
                        case self::STAFF_VERIFIER:
                            $actionsSet[self::ACTION_CHANGE_STATUS] = 1;
                            $actionsSet[self::ACTION_MANAGE_ATTACHMENTS] = true;
                        case self::STAFF_HELPER:
                        case self::STAFF_RESPONSIBLE:
                            if (!isset($actionsSet[self::ACTION_CHANGE_STATUS])) {
                                $actionsSet[self::ACTION_CHANGE_STATUS] = 2;
                                $actionsSet[self::ACTION_MANAGE_ATTACHMENTS] = true;
                            }
                            $actionsSet[self::ACTION_MANAGE_SUBTASKS] = true;
                        case self::STAFF_FOLLOWER:
                            $actionsSet[self::ACTION_VIEW] = true;
                            $actionsSet[self::ACTION_COMMENT] = true;
                            $actionsSet[self::ACTION_TAG] = true;
                            $actionsSet[self::ACTION_MANAGE_ATTACHMENTS] = true;
                    }
                }
            }
        }

        return $actionsSet;
    }

   private function setComment(Logger $logger, $oldDataGeneral, $oldDataStaff, $newData, $actionsSet = [])
   {
       /**
        * @var \DDD\Domain\Task\Task $oldDataGeneral
        * @var \DDD\Domain\Task\Staff[] $oldDataStaff
        * @var \DDD\Dao\User\UserManager $usersDao
        * @var \DDD\Dao\Team\Team $teamsDao
        * @var \DDD\Domain\User\User $user
        * @var \DDD\Domain\Team\Team $oldTeam
        */
       $auth = $this->getServiceLocator()->get('library_backoffice_auth');
       $usersDao = $this->getServiceLocator()->get('dao_user_user_manager');

       $authId = $auth->getIdentity()->id;
       $timestamp = $newData['last_update_time'];

       // Log Status Changes
       if (isset($newData['task_status']) && $oldDataGeneral->getTask_status() != $newData['task_status']) {
           $logger->save(Logger::MODULE_TASK, $oldDataGeneral->getId(), self::getTaskStatusMapping()[$newData['task_status']], 1, $authId, $timestamp);
           if ($newData['task_status'] == self::STATUS_DONE
               &&
               (
                (isset($newData['verifier_id']) && $newData['verifier_id'] == User::AUTO_VERIFY_USER_ID)
                ||
                (empty($actionsSet[self::ACTION_MANAGE_STAFF]) && $oldDataGeneral->getVerifierId() == User::AUTO_VERIFY_USER_ID)
               )
           ) {
               $logger->save(Logger::MODULE_TASK, $oldDataGeneral->getId(), Logger::ACTION_TASK_AUTO_VERIFY, 'Auto Verified', UserMain::SYSTEM_USER_ID, $timestamp);
           }
       }

       // Log Priority Changes
       if (isset($newData['task_priority']) && $oldDataGeneral->getPriority() != $newData['task_priority']) {
           $logger->save(Logger::MODULE_TASK, $oldDataGeneral->getId(), Logger::ACTION_PRIORITY, self::getTaskPriorityMapping()[$newData['task_priority']], $authId, $timestamp);
       }

       // Log Team Changes
       if (isset($newData['team_id']) && $oldDataGeneral->getTeamId() != $newData['team_id']) {
           $teamsDao  = $this->getServiceLocator()->get('dao_team_team');
           $oldTeam   = $teamsDao->fetchOne(['id' => $oldDataGeneral->getTeamId()]);
           $newTeam   = $teamsDao->fetchOne(['id' => $newData['team_id']]);

           if ($oldTeam) {
                if ($newTeam) {
                    $teamValue = '<b>' . $oldTeam->getName() . '</b> to <b>' . $newTeam->getName() . '</b>';
                } else {
                    $teamValue = '<b>' . $oldTeam->getName() . '</b> to <b>Unassigned</b>.';
                }

                $logger->save(Logger::MODULE_TASK, $oldDataGeneral->getId(), Logger::ACTION_TASK_TEAM, $teamValue, $authId, $timestamp);
           }
       }

       // Log Date Changes
       if (isset($newData['date'])) {
           $date = ($newData['date']) ? date('Y-m-d', strtotime($newData['date'])): null;

           if ($oldDataGeneral->getDate() != $date) {
               $logger->save(Logger::MODULE_TASK, $oldDataGeneral->getId(), Logger::ACTION_TASK_DATE_SET, $date, $authId, $timestamp);
           }
       }

       if (!empty($actionsSet[self::ACTION_MANAGE_STAFF])) {
           $staffKeyMapping = [
               self::STAFF_RESPONSIBLE => 'responsible_id',
               self::STAFF_VERIFIER => 'verifier_id',
               self::STAFF_HELPER => 'helper_ids',
               self::STAFF_FOLLOWER => 'follower_ids',
           ];

           $ifNoOldStaffButNew = [
               'responsible_id' => 'responsible_id',
                'verifier_id'   => 'verifier_id'
           ];

           if ($oldDataStaff) {
               foreach ($oldDataStaff as $oldStaffPerson) {
                   switch ($oldStaffPerson->getType()) {
                       case self::STAFF_RESPONSIBLE:
                       case self::STAFF_VERIFIER:
                           if (isset($newData[$staffKeyMapping[$oldStaffPerson->getType()]])) {
                               if (
                                   !($newData[$staffKeyMapping[$oldStaffPerson->getType()]])
                                   ||
                                   $oldStaffPerson->getId() != $newData[$staffKeyMapping[$oldStaffPerson->getType()]]
                               ) {
                                   $changedTo = 'nobody';
                                   if ($newData[$staffKeyMapping[$oldStaffPerson->getType()]]) {
                                       $user = $usersDao->fetchOne(['id' => $newData[$staffKeyMapping[$oldStaffPerson->getType()]] ]);
                                       $changedTo = $user->getFullName();
                                   }
                                   $logger->save(
                                       Logger::MODULE_TASK,
                                       $oldDataGeneral->getId(),
                                       Logger::ACTION_TASK_STAFF_CHANGE,
                                       [
                                           str_replace('_id', '', $staffKeyMapping[$oldStaffPerson->getType()]),
                                           $oldStaffPerson->getName(),
                                           $changedTo
                                       ],
                                       $authId,
                                       $timestamp
                                   );
                                   if (isset($ifNoOldStaffButNew[$staffKeyMapping[$oldStaffPerson->getType()]])) {
                                       unset($ifNoOldStaffButNew[$staffKeyMapping[$oldStaffPerson->getType()]]);
                                   }
                               } else {
                                   unset($newData[$staffKeyMapping[$oldStaffPerson->getType()]]);
                               }
                           }
                           break;
                       case self::STAFF_HELPER:
                       case self::STAFF_FOLLOWER:
                           if (isset($newData[$staffKeyMapping[$oldStaffPerson->getType()]])) {
                               if (
                                   !$newData[$staffKeyMapping[$oldStaffPerson->getType()]]
                                   ||
                                   !in_array($oldStaffPerson->getId(), $newData[$staffKeyMapping[$oldStaffPerson->getType()]])
                               ) {
                                   $logger->save(
                                       Logger::MODULE_TASK,
                                       $oldDataGeneral->getId(),
                                       Logger::ACTION_TASK_STAFF_REMOVE,
                                       [
                                           str_replace('_id', '', $staffKeyMapping[$oldStaffPerson->getType()]),
                                           $oldStaffPerson->getName()
                                       ],
                                       $authId,
                                       $timestamp
                                   );
                               } else {
                                   $newData[$staffKeyMapping[$oldStaffPerson->getType()]] = array_flip($newData[$staffKeyMapping[$oldStaffPerson->getType()]]);
                                   unset($newData[$staffKeyMapping[$oldStaffPerson->getType()]][$oldStaffPerson->getId()]);
                                   $newData[$staffKeyMapping[$oldStaffPerson->getType()]] = array_flip($newData[$staffKeyMapping[$oldStaffPerson->getType()]]);
                               }
                               break;
                           }
                   }
               }
           }
           //if there was no old data, but there is new one
           foreach($ifNoOldStaffButNew as  $item) {
               if (!empty($newData[$item])) {
                   $user = $usersDao->fetchOne(['id' => $newData[$item]]);
                   $logger->save(
                       Logger::MODULE_TASK,
                       $oldDataGeneral->getId(),
                       Logger::ACTION_TASK_STAFF_CHANGE,
                       [
                           str_replace('_id', '',$item),
                           'nobody',
                           $user->getFullName()

                       ],
                       $authId,
                       $timestamp
                   );
               }
           }


           if (isset($newData['helper_ids']) && count($newData['helper_ids'])) {
               foreach ($newData['helper_ids'] as $helperId) {
                   $user = $usersDao->fetchOne(['id' => $helperId]);
                   $logger->save(
                       Logger::MODULE_TASK,
                       $oldDataGeneral->getId(),
                       Logger::ACTION_TASK_STAFF_ADD,
                       [
                           'helper',
                           $user->getFirstName() . ' ' . $user->getLastName()
                       ],
                       $authId,
                       $timestamp
                   );
               }
           }

           if (isset($newData['follower_ids']) && count($newData['follower_ids'])) {
               foreach ($newData['follower_ids'] as $followerId) {
                   $user = $usersDao->fetchOne(['id' => $followerId]);
                   $logger->save(
                       Logger::MODULE_TASK,
                       $oldDataGeneral->getId(),
                       Logger::ACTION_TASK_STAFF_ADD,
                       [
                           'follower',
                           $user->getFirstName() . ' ' . $user->getLastName()
                       ],
                       $authId,
                       $timestamp
                   );
               }
           }
       }
	}

   public function getUDList($backofficeUserID, $type)
   {
       $taskDao = $this->getTaskDao();
       $udList = $taskDao->getUDList($backofficeUserID, $type);

       return $udList;
   }

    public function getUDListCount($backofficeUserID, $type)
    {
        $taskDao = $this->getTaskDao();
        $count = $taskDao->getUDListCount($backofficeUserID, $type);

        return $count;
    }

    public function getTaskBasicInfo($iDisplayStart = null, $iDisplayLength = null, $filterParams = [], $sortCol = 0, $sortDir = 'ASC')
    {
        $dao           = $this->getTaskDao();
        $filteredArray = [];
        $auth          = $this->getServiceLocator()->get('library_backoffice_auth');
        $authId        = $auth->getIdentity()->id;
        $taskManger    = false;

        if ($auth->hasRole(Roles::ROLE_GLOBAL_TASK_MANAGER)) {
            $taskManger = true;
        }

        $response = $dao->getTaskListForSearch($authId, $iDisplayStart, $iDisplayLength, $filterParams, $sortCol, $sortDir, $taskManger);

        /**
         * @var \DDD\Domain\Task\Task $taskRow
         */
        foreach ($response['result'] as $taskRow) {
            $rowClass = '';
            if (strtotime($taskRow->getEndDate()) <= strtotime(date('Y-m-j 23:59')) && $taskRow->getTask_status() < 5) {
                $rowClass = 'danger';
            }

            $permissions = $this->composeUserTaskPermissions($taskRow->getId());
            $subTasks = null;

            if (!is_null($taskRow->getSubtaskDescription())) {
                $subtaskDesc = explode(',', $taskRow->getSubtaskDescription());
                $subtaskDesc = array_unique($subtaskDesc);
                $subTasks = '';
                foreach ($subtaskDesc as $row) {
                    $subTasks .= '<li>' . $row . '</li>';
                }

                $subTasks = '<ul><b>Sub Tasks</b>' . $subTasks . '</ul>';
            }

            $title   = (strlen($taskRow->getTitle()) > 25) ? substr($taskRow->getTitle(), 0, 25) . '...' : $taskRow->getTitle();
            if(strlen(htmlspecialchars($taskRow->getDescription())) > 0) {
                $descriptionLi = '<b>Description</b><li>' . htmlspecialchars($taskRow->getDescription()) . '</li>';
            } else {
                $descriptionLi = '';
            }
            $tooltip = '<span class="glyphicon glyphicon-info-sign pull-right" data-toggle="popover" data-content="<ul><b>Title</b><li>' .
                $taskRow->getTitle() .
                '</li></ul><ul><b>Priority</b><li>' . self::getTaskPriority()[$taskRow->getPriority()] . '</li></ul><ul>' .
                $descriptionLi .
                '</ul>' . $subTasks . '"></span>';

            if ($taskRow->getApartmentName()) {
                $apartmentAndUnit = $taskRow->getApartmentName();
                if($taskRow->getUnit_number()){
                    $apartmentAndUnit.=' ['.$taskRow->getUnit_number().']';
                }
            } else {
                $apartmentAndUnit = '';
            }

            $dtRow = [
                self::getTaskPriorityLabeled()[$taskRow->getPriority()],
                self::getTaskStatus()[$taskRow->getTask_status()],
                $taskRow->getStartDate() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($taskRow->getEndDate())) : '',
                $taskRow->getEndDate() ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($taskRow->getEndDate())) : '',
                $title . ' ' . $tooltip,
                $apartmentAndUnit,
                $taskRow->getResponsibleId(),
                $taskRow->getVerifierId(),
                $taskRow->getTaskType(),
                $taskRow->getId(),
                !isset($permissions[self::ACTION_MANAGE_STAFF]) ? 0 : 1,
                $taskRow->getTeamId(),
                "DT_RowClass" => $rowClass
            ];

            $filteredArray[] = $dtRow;
        }

        $result['count'] = $response['count'];
        $result['result'] = $filteredArray;
        return $result;
    }

    public function getFrontierTasksOnApartment($apartmentId)
    {
        $taskDao = $this->getTaskDao();
        $tasks = $taskDao->getFrontierTasksOnApartment($apartmentId);

        return $tasks;
    }

    public function getTasksOnBuilding($buildingId)
    {
        $taskDao = $this->getTaskDao();
        $tasks = $taskDao->getTasksOnBuilding($buildingId);

        return $tasks;
    }

    /**
     * @return \DDD\Domain\User\User[]
     */
    public function getUsers()
    {
        /**
         * @var \DDD\Dao\User\UserManager $userDao
         */
        $userDao = $this->getServiceLocator()->get('dao_user_user_manager');
        return $userDao->getForSelect();
    }

    public function getTeamBasedOnType($typeId, $apartmentId, $buildingId)
    {
        /**
         * @var \DDD\Dao\Task\Type $taskTypesDao
         * @var \DDD\Dao\Team\Team $teamsDao
         * @var \DDD\Domain\Task\Type $taskType
         */
        $taskTypesDao = $this->getServiceLocator()->get('dao_task_type');
        $teamsDao     = $this->getServiceLocator()->get('dao_team_team');

        $taskType = $taskTypesDao->fetchOne(['id' => $typeId]);

        if ($taskType) {
            $teamId = $taskType->getAssociatedTeamId();
        } else {
            $teamId = false;
        }

        // If housekeeping department, then find team which has this apartment or building linked
        if ($teamId == TeamService::TEAM_OPERATIONS) {
            if ($apartmentId) {
                $team = $teamsDao->getFrontierTeamByApartment($apartmentId);
                if ($team) {
                    $teamId = $team->getId();
                }
            } else if ($buildingId) {
                $team = $teamsDao->getFrontierTeamByBuilding($buildingId);
                if ($team) {
                    $teamId = $team->getId();
                }
            }
        }

        // If can't find a frontier team responsible for given building/apartment
        // assign this task to quality assurance team
        if ($teamId == TeamService::TEAM_OPERATIONS) {
            $teamId = TeamService::TEAM_QUALITY_ASSURANCE;
        }

        return $teamId;

    }

    public function checkVerifiableBasedOnType($typeId)
    {
        /**
         * @var \DDD\Dao\Task\Type $taskTypesDao
         * @var \DDD\Domain\Task\Type $taskType
         */
        $taskTypesDao = $this->getServiceLocator()->get('dao_task_type');

        $taskType = $taskTypesDao->fetchOne(['id' => $typeId]);

        if ($taskType) {
            $autoVerifiable = $taskType->isAutoVerifiable();
        } else {
            $autoVerifiable = 0;
        }

        return $autoVerifiable;

    }

    public function getSubasksBasedOnType($typeId)
    {
        /**
         * @var \DDD\Dao\Task\Type $taskTypesDao
         * @var \DDD\Domain\Task\Type $taskType
         */
        $taskTypesDao = $this->getServiceLocator()->get('dao_task_type');

        $taskType = $taskTypesDao->fetchOne(['id' => $typeId]);

        if ($taskType) {
            $subtasks = explode(PHP_EOL, $taskType->getSubtasks());
        } else {
            $subtasks = false;
        }

        return $subtasks;

    }

    /**
     * @param string $domain
     * @return \DDD\Dao\Task\Task
     */
    public function getTaskDao($domain = 'DDD\Domain\Task\Task') {
        return new \DDD\Dao\Task\Task($this->getServiceLocator(), $domain);
    }

    public function getTaskStaffDao($domain = 'DDD\Domain\Task\Staff') {
        return new \DDD\Dao\Task\Staff($this->getServiceLocator(), $domain);
    }

    private function getTaskSubtasksDao($domain = 'DDD\Domain\Task\Subtask') {
        return new \DDD\Dao\Task\Subtask($this->getServiceLocator(), $domain);
    }

    public static function getTaskStatus()
    {
        return [
            self::STATUS_NEW      => 'New',
            self::STATUS_VIEWED   => 'Viewed',
            self::STATUS_BLOCKED  => 'Blocked',
            self::STATUS_STARTED  => 'Started',
            self::STATUS_DONE     => 'Done',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_CANCEL   => 'Canceled',
        ];
    }

    public static function getTaskStatusLabelClass($status)
    {
        $classes = [
            1 => 'label-info',
            2 => 'label-warning',
            3 => 'label-danger',
            4 => 'label-primary',
            5 => 'label-success',
            6 => 'label-light-green',
            7 => 'label-danger',
        ];

        return $classes[$status];
    }

    public static function getTaskDateTypes()
    {
        return [
            1 => 'Action',
            2 => 'Due'
        ];
    }

    public static function getTaskStatusMapping()
    {
        return [
            1 => 11,
            2 => 6,
            3 => 7,
            4 => 8,
            5 => 9,
            6 => 5,
            7 => 10,
        ];
    }

    public static function getTaskPriority()
    {
        return [
            self::TASK_PRIORITY_NORMAL    => 'Normal',
            self::TASK_PRIORITY_HIGH      => 'High',
            self::TASK_PRIORITY_IMPORTANT => 'Important',
            self::TASK_PRIORITY_CRITICAL  => 'Critical',
        ];
    }

    public static function getTaskPriorityLabeled()
    {
        // hidden span's for sorting
        return [
            self::TASK_PRIORITY_NORMAL    => '<span class="hidden">2</span><span class="task-label label label-info" title="Normal">N</span>',
            self::TASK_PRIORITY_HIGH      => '<span class="hidden">5</span><span class="task-label label label-primary" title="High">H</span>',
            self::TASK_PRIORITY_IMPORTANT => '<span class="hidden">8</span><span class="task-label label label-warning" title="Important">I</span>',
            self::TASK_PRIORITY_CRITICAL  => '<span class="hidden">9</span><span class="task-label label label-danger" title="Critical">C</span>',
        ];
    }

    public static function getTaskPriorityMapping()
    {
        return [
            self::TASK_PRIORITY_NORMAL    => 1,
            self::TASK_PRIORITY_HIGH      => 2,
            self::TASK_PRIORITY_IMPORTANT => 3,
            self::TASK_PRIORITY_CRITICAL  => 4,
        ];
    }

    /**
     * @return \DDD\Domain\Task\Type[]
     */
    public function getTaskTypesForSelect($where = null)
    {
        /**
         * @var \DDD\Dao\Task\Type $taskTypeDao
         */
        $taskTypeDao = $this->getServiceLocator()->get('dao_task_type');
        $taskTypes = $taskTypeDao->fetchAll($where, [], ['order' => 'ASC']);

        $taskTypeList[0] = '-- Choose Type --';
        foreach ($taskTypes as $row){
            $taskTypeList[$row->getGroup()]['label'] = $row->getGroup();
            $taskTypeList[$row->getGroup()]['options'][$row->getId()] = $row->getName();
        }

        return $taskTypeList;
    }

    /**
     * @param $resId
     * @param $user
     * @param $propertyId
     */
    public function createNoShowTask($resId, $user, $propertyId, $dateFrom)
    {
        $taskTypeDao  = $this->getServiceLocator()->get('dao_task_type');
        $taskStaffDao = $this->getServiceLocator()->get('dao_task_staff');
        $logger       = $this->getServiceLocator()->get('ActionLogger');

        $taskTypeReservation = $taskTypeDao->fetchOne(
            ['id' => self::TYPE_RESERVATION],
            ['associated_team_id']
        );

        $actionsSet = [
            self::ACTION_CHANGE_DETAILS  => 1,
            self::ACTION_CHANGE_STATUS   => 1,
            self::ACTION_MANAGE_STAFF    => 1,
            self::ACTION_MANAGE_SUBTASKS => 1
        ];

        $taskData = [
            'title'         => 'No show follow-up actions',
            'task_type'     => self::TYPE_RESERVATION,
            'team_id'       => $taskTypeReservation->getAssociatedTeamId(),
            'res_id'        => $resId,
            'property_id'   => $propertyId,
            'start_date'    => date('Y-m-d', strtotime($dateFrom)) . ' ' . date('H:i:s', strtotime('+1 hours')),
            'end_date'      => date('Y-m-d', strtotime($dateFrom)) . ' ' . date('H:i:s', strtotime('+2 hours')),
            'task_status'   => self::STATUS_NEW,
            'task_priority' => self::TASK_PRIORITY_NORMAL,
            'creator_id'    => UserMain::SYSTEM_USER_ID,
            'verifier_id'   => UserService::AUTO_VERIFY_USER_ID
        ];

        $taskId = $this->taskSave($taskData, $actionsSet);

        if ($taskId) {
            $taskStaffDao->save(
                [
                    'task_id' => $taskId,
                    'user_id' => UserService::ANY_TEAM_MEMBER_USER_ID,
                    'type'    => self::STAFF_RESPONSIBLE
                ]
            );
        }

        $logger->save(
            Logger::MODULE_TASK,
            $taskId,
            Logger::ACTION_TASK_SYSTYEM_GENERATED,
            'Arrival Status changed by ' . $user->firstname . ' ' . $user->lastname,
            UserMain::SYSTEM_USER_ID
        );
    }

    public function createReportIncidentTask(
        $reservationId,
        $apartmentId,
        $buildingId,
        $currentDateCity,
        $ninetySixHoursLaterDateCity,
        $title,
        $creator,
        $description,
        $gemId,
        $taskId = false,
        $attachments = false
    )
    {
        $taskStaffDao = $this->getServiceLocator()->get('dao_task_staff');
        $actionsSet = [
            self::ACTION_CHANGE_DETAILS  => 1,
            self::ACTION_CHANGE_STATUS   => 1,
            self::ACTION_MANAGE_STAFF    => 1,
            self::ACTION_MANAGE_SUBTASKS => 1
        ];

        $taskData = [
            'title'           => $title,
            'task_type'       => self::TYPE_INCIDENT_REPORT,
            'description'     => $description,
            'team_id'         => TeamService::TEAM_QUALITY_ASSURANCE,
            'res_id'          => $reservationId,
            'property_id'     => $apartmentId,
            'building_id'     => $buildingId,
            'related_task_id' => ($taskId ? $taskId : null),
            'start_date'      => $currentDateCity,
            'end_date'        => $ninetySixHoursLaterDateCity,
            'creation_date'   => $currentDateCity,
            'task_status'     => self::STATUS_NEW,
            'task_priority'   => self::TASK_PRIORITY_IMPORTANT,
            'creator_id'      => $creator,
            'verifier_id'     => UserService::AUTO_VERIFY_USER_ID,
        ];

        if ($attachments) {
            $actionsSet[self::ACTION_MANAGE_ATTACHMENTS] = 1;
            $taskData['attachment_names'] = $attachments;
        }

        if ($gemId) {
            $taskData['follower_ids'] = [$gemId];
        }

        $taskId = $this->taskSave($taskData, $actionsSet);
        if ($taskId) {
            $taskStaffDao->save(
                [
                    'task_id' => $taskId,
                    'user_id' => UserService::ANY_TEAM_MEMBER_USER_ID,
                    'type'    => self::STAFF_RESPONSIBLE
                ]
            );
            return $taskId;
        }
        return false;
    }

    /**
     * @param $data
     * @return bool
     */
    public function createTaskFromFrontier ($data)
    {
        /**
         * @var \DDD\Dao\Task\Type $taskTypeDao
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         */
        $currentDate   = date('Y-m-d');
        $taskType      = isset($data['task_type']) ? $data['task_type'] : 0;
        $taskName      = isset($data['task_name']) ? $data['task_name'] : '';
        $reservationId = isset($data['res_id']) ? $data['res_id'] : 0;
        $apartmentId   = isset($data['apartment_id']) ? $data['apartment_id'] : 0;
        $buildingId    = isset($data['building_id']) ? $data['building_id'] : 0;
        $checkTaskType = isset($data['check_task_type']) ? $data['check_task_type'] : 0;
        $endDate       = isset($data['end_date']) ? $data['end_date'] : date(Constants::GLOBAL_DATE_FORMAT . ' H:i', strtotime($currentDate) + 7200);

        $taskTypeDao   = $this->getServiceLocator()->get('dao_task_type');
        $auth          = $this->getServiceLocator()->get('library_backoffice_auth');
        $identity      = $auth->getIdentity();

        $taskTypeReservation = $taskTypeDao->fetchOne(
            ['id' => $taskType],
            [
                'associated_team_id',
                'auto_verifiable'
            ]
        );

        // check type
        if (!$taskTypeReservation || !$taskType || !$taskName) {
            return false;
        }

        $actionsSet = [
            self::ACTION_CHANGE_DETAILS  => 1,
            self::ACTION_CHANGE_STATUS   => 1,
            self::ACTION_MANAGE_STAFF    => 1,
            self::ACTION_MANAGE_SUBTASKS => 1
        ];
        $teamId = $this->getTeamBasedOnType($taskType, $apartmentId, $buildingId);

        $taskData = [
            'title'         => $taskName,
            'task_type'     => $taskType,
            'team_id'       => $teamId,
            'start_date'    => $currentDate,
            'end_date'      => $endDate,
            'task_status'   => self::STATUS_NEW,
            'creator_id'    => $identity->id,

        ];

        if (in_array($checkTaskType , [Frontier::FRONTIER_KEY_TASK, Frontier::FRONTIER_FOB_TASK])) {
            $taskData['task_priority'] = self::TASK_PRIORITY_HIGH;
            $taskData['description']   = sprintf(TextConstants::TASK_FOB_KEY_DESCRIPTION, ($checkTaskType == Frontier::FRONTIER_KEY_TASK ? 'key' : 'fob'));

        } else {
            $taskData['task_priority'] = self::TASK_PRIORITY_NORMAL;
        }

        if ($reservationId) {
            $taskData['res_id'] = $reservationId;
        }

        if ($apartmentId) {
            $taskData['property_id'] = $apartmentId;
        }

        if ($buildingId) {
            $taskData['building_id'] = $buildingId;
        }

        if ($taskTypeReservation->isAutoVerifiable() == 1) {
            $taskData['verifier_id'] = UserService::AUTO_VERIFY_USER_ID;
        }
        $this->taskSave($taskData, $actionsSet);
        return true;
    }

    /**
     * creates an empty cleaning task on the reservation's checkout day
     * @param $resId
     * @return int
     */
    public function createAutoTaskForCleaning($resId)
    {
        /**
         * @var BookingTicket $bookingTicket
         */
        $taskType             = self::TYPE_CLEANING;
        $taskName             = 'Clean The Apartment';
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $bookingTicket        = $bookingTicketService->getBookingThicketByReservationId($resId);
        $apartmentId          = $bookingTicket->getApartmentIdAssigned();
        $buildingId           = $bookingTicket->getApartmentAssignedBuildingId();
        $checkOutDate         = $bookingTicket->getDate_to();
        $checkOutTime         = (is_null($bookingTicket->getApartmentCheckOutTime())) ? '11:00:00' : $bookingTicket->getApartmentCheckOutTime();
        $checkOutDateTime     = $checkOutDate . ' ' .$checkOutTime;
        $teamId               = $this->getTeamBasedOnType($taskType, $apartmentId, $buildingId);
        $taskDao              = $this->getTaskDao();

        $actionsSet = [
            self::ACTION_CHANGE_DETAILS  => 1,
            self::ACTION_CHANGE_STATUS   => 1,
            self::ACTION_MANAGE_STAFF    => 1,
            self::ACTION_MANAGE_SUBTASKS => 1
        ];

        $taskData = [
            'title'          => $taskName,
            'task_type'      => $taskType,
            'team_id'        => $teamId,
            'following_team_id' => TeamService::TEAM_CONTACT_CENTER,
            'start_date'     => $checkOutDateTime,
            'end_date'       => date('Y-m-d H:i:s', strtotime($checkOutDateTime) + 7200), //2 hours for cleaning and change the key
            'task_status'    => self::STATUS_NEW,
            'creator_id'     => UserMain::SYSTEM_USER_ID,
            'task_priority'  => self::TASK_PRIORITY_NORMAL,
            'description'    => 'Clean The Apartment And Prepare For The Next Guest',
            'res_id'         => $resId,
            'property_id'    => $apartmentId,
            'building_id'    => $buildingId,
            'is_hk'          => self::TASK_IS_HOUSEKEEPING,
        ];

        $deleteDate = date("Y-m-d", strtotime($checkOutDateTime));

        $id =  $this->taskSave($taskData, $actionsSet, true);
        //delete All other extra Key Fob tasks for today on this apartment
        $taskDao->deleteAllSameDayAutoGeneratedTasks($id, $deleteDate ,$apartmentId, $taskType);
        return $id;
    }

    /**
     * @param $storageName
     * @return int
     */
    public function createStorageCreatedTask($storageName)
    {

        $actionsSet = [
            self::ACTION_CHANGE_DETAILS  => 1,
            self::ACTION_CHANGE_STATUS   => 1,
            self::ACTION_MANAGE_STAFF    => 1,
            self::ACTION_MANAGE_SUBTASKS => 1
        ];

        $taskData = [
            'title'          => sprintf(TextConstants::STORAGE_CREATED_TASK_TITLE, $storageName),
            'task_type'      => self::TYPE_OTHER,
            'team_id'        => Team::TEAM_QUALITY_ASSURANCE,
            'start_date'     => date('Y-m-d H:i:s'),
            'end_date'       => date('Y-m-d H:i:s', strtotime("+1 days")),
            'task_status'    => self::STATUS_NEW,
            'creator_id'     => UserMain::SYSTEM_USER_ID,
            'task_priority'  => self::TASK_PRIORITY_HIGH,
            'description'    => sprintf(TextConstants::STORAGE_CREATED_TASK_DESCRIPTION, $storageName),
        ];

        $id =  $this->taskSave($taskData, $actionsSet, true);
        return $id;
    }

    /**
     * @param $reservationId
     * @param $type
     * @param bool|false $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getReservationAutoCreatedTask($reservationId, $type, $apartmentId = false)
    {
        $taskDao  = $this->getServiceLocator()->get('dao_task_task');
        return $taskDao->getReservationAutoCreatedTask($reservationId, $type, $apartmentId);
    }

    /**
     * checks if last reservation keys are set right, if not,
     * creates an extra task for next reservation's checkin
     * @param $lastReservation
     * @param $nextReservation
     * @return bool
     */
    public function createExtraAutoTaskForKeyChange($lastReservation, $nextReservation)
    {
        if ($nextReservation['no_refresh'] == 1)  {
             return false;
        }

        $taskDao             = $this->getTaskDao();
        $lastReservationTask = $this->getReservationAutoCreatedTask($lastReservation['id'], self::TYPE_CLEANING, $lastReservation['apartment_id_assigned']);

        if (is_null($lastReservationTask) || !$lastReservationTask) {
            return false;
        }
        $taskType             = self::TYPE_APT_SERVICE;
        $nextReservationExtraTask = $this->getReservationAutoCreatedTask($lastReservation['id'], $taskType);
        if (!is_null($nextReservationExtraTask) && $nextReservationExtraTask) { //extra task is already created
            return false;
        }
        $taskSubtaskDao = $this->getTaskSubtasksDao();
        $setCodeToSubtask = $taskSubtaskDao->getSubtaskLike($lastReservationTask['id'], $nextReservation['pin'] . ' for next check-in');
        $occupancySubtask = $taskSubtaskDao->getSubtaskLike($lastReservationTask['id'], 'Occupancy: ' . $nextReservation['occupancy']);

        if (   (!$setCodeToSubtask && !in_array($nextReservation['lock_type'], LockGeneral::$typeWithoutCode))
            || (!$occupancySubtask)
        ) {
            //it means that originally the task was been created for another reservation and we have got a new reservation in a middle
            $taskName             = 'Inspect the apartment for new occupants';

            $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
            $bookingTicket        = $bookingTicketService->getBookingThicketByReservationId($nextReservation['id']);

            $apartmentId          = $bookingTicket->getApartmentIdAssigned();
            $buildingId           = $bookingTicket->getApartmentAssignedBuildingId();
            $checkInDate          = $bookingTicket->getDate_from();
            $checkInTime          = (is_null($bookingTicket->getApartmentCheckInTime())) ? '13:00:00' : $bookingTicket->getApartmentCheckInTime();
            $checkInDateTime      = $checkInDate . ' ' .$checkInTime;
            $checkinDateTimeMinus1Hour = date('Y-m-d H:i:s', strtotime($checkInDateTime) - 3600);
            $teamId               = $this->getTeamBasedOnType($taskType, $apartmentId, $buildingId);

            $actionsSet = [
                self::ACTION_CHANGE_DETAILS  => 1,
                self::ACTION_CHANGE_STATUS   => 1,
                self::ACTION_MANAGE_STAFF    => 1,
                self::ACTION_MANAGE_SUBTASKS => 1
            ];

            $occupancySubtaskOfLastReservation = $taskSubtaskDao->getSubtaskLike($lastReservationTask['id'], 'Occupancy: ');
            if (is_null($occupancySubtaskOfLastReservation['description'])
            || strpos(false === $occupancySubtaskOfLastReservation['description'], 'Occupancy: ')) {
                return false;
            }

            $alreadySetOccupancy = substr($occupancySubtaskOfLastReservation['description'], strlen('Occupancy: '));

            $subtaskDescription = [];

            if (in_array($nextReservation['lock_type'], LockGeneral::$typeWithoutCode)
            && $alreadySetOccupancy == $nextReservation['occupancy']) {
                return false;
            }

            $occupancySubtaskOfCreatingTask = 'Occupancy: ' . $nextReservation['occupancy'];
            if ($alreadySetOccupancy != $nextReservation['occupancy']) {
                $occupancySubtaskOfCreatingTask .= ' (old occupancy: ' . $alreadySetOccupancy . ')';
            }
            array_push(
                $subtaskDescription,
                $occupancySubtaskOfCreatingTask
            );

            if ($lastReservation['outside_door_code']) {
                array_push(
                    $subtaskDescription,
                    'Enter to building with ' . $lastReservation['outside_door_code']
                );
            }

            $setCodeToSubtaskSet = $taskSubtaskDao->getSubtaskLike($lastReservationTask['id'], ' for next check-in');
            if ($setCodeToSubtaskSet) {
                $SubTaskArray = explode(' for next check-in', $setCodeToSubtaskSet['description']) ;
                $subTaskFirstPart = $SubTaskArray[0];
                $subTaskFirstPartArray = explode('Set',$subTaskFirstPart);
                $EnterCode = $subTaskFirstPartArray[1];
            } else {
                $EnterCode = 'Mechanical Key';
            }
                array_push(
                    $subtaskDescription,
                    'Enter to apartment with ' . $EnterCode
                );



            if ($nextReservation['next_pin']) {
                array_push(
                    $subtaskDescription,
                    'Set ' . $nextReservation['next_pin'] . ' for next check-in'
                );
            }

            /* @var $apartmentDao \DDD\Dao\Apartment\General */
            $apartmentDao = $this->getServiceLocator()->get('dao_apartment_general');
            $apartmentData = $apartmentDao->getApartmentGeneralInfo($apartmentId);
            $apartmentToday = Helper::getCurrenctDateByTimezone($apartmentData['timezone']);

            $taskPriority = self::TASK_PRIORITY_NORMAL;
            if ($nextReservation['date_from'] <= $apartmentToday) {
                $taskPriority = self::TASK_PRIORITY_CRITICAL;
            }

            $startDateTime = $checkinDateTimeMinus1Hour;
            $endDateTime   = $checkInDateTime;

            $deleteDate = date("Y-m-d", strtotime($endDateTime));

            $taskData = [
                'title'               => $taskName,
                'task_type'           => $taskType,
                'team_id'             => $teamId,
                'following_team_id' => TeamService::TEAM_CONTACT_CENTER,
                'start_date'          => $startDateTime,
                'end_date'            => $endDateTime,
                'task_status'         => self::STATUS_NEW,
                'creator_id'          => UserMain::SYSTEM_USER_ID,
                'task_priority'       => $taskPriority,
                'description'         => 'Prepare For The Next Guest',
                'res_id'              => $lastReservation['id'],
                'property_id'         => $apartmentId,
                'building_id'         => $buildingId,
                'verifier_id'         => UserService::AUTO_VERIFY_USER_ID,
                'is_hk'               => self::TASK_IS_HOUSEKEEPING,
                'subtask_description' => $subtaskDescription
            ];

            $id = $this->taskSave($taskData, $actionsSet, true);
            //delete All other extra Key Fob tasks for today on this apartment
            $taskDao->deleteAllSameDayAutoGeneratedTasks($id, $deleteDate ,$apartmentId, $taskType);
            echo 'extra task created, id = ' . $id . "\e[0m\n\r";
        }

    }

    /**
     * @param $reservationId
     * @return array|\ArrayObject|null
     */
    public function getNextReservationExtraInspectionTask($reservationId)
    {
        $taskDao  = $this->getServiceLocator()->get('dao_task_task');
        return $taskDao->getNextReservationExtraInspectionTask($reservationId);
    }


    /**
     * checks if last reservation keys are set right, if not,
     * creates an extra task for next reservation's checkin
     * @param $lastReservation
     * @param $nextReservation
     * @param $lastReservationTask
     * @return bool
     */
    public function createExtraInspectionTask($lastReservation, $nextReservation, $lastReservationTask)
    {
        $taskType = self::TYPE_APT_SERVICE;
        $nextReservationExtraTask = $this->getReservationAutoCreatedTask($lastReservation['id'], $taskType);
        if ($nextReservationExtraTask) {
            //there is already extra task created
            return false;
        }

        $nextReservationExtraInspectionTask = $this->getNextReservationExtraInspectionTask($nextReservation['id']);

        $taskSubtaskDao = $this->getTaskSubtasksDao();

        $setCodeToSubtaskSet = $taskSubtaskDao->getSubtaskLike($lastReservationTask['id'], ' for next check-in');
        $enterToBuildingSubtask = $taskSubtaskDao->getSubtaskLike($lastReservationTask['id'], 'Enter to building with');

        if (in_array($nextReservation['lock_type'], LockGeneral::$typeWithoutCode)) {
            $EnterCode = 'Mechanical Key';
        } else {
            $SubTaskArray = explode(' for next check-in', $setCodeToSubtaskSet['description']);
            $subTaskFirstPart = $SubTaskArray[0];
            $subTaskFirstPartArray = explode('Set', $subTaskFirstPart);
            $EnterCode = $subTaskFirstPartArray[1];
        }

        $inspectionTaskOccupancySubTaskDescription = 'Occupancy: ' . $nextReservation['occupancy'];
        $inspectionTaskEnterToApartmentSubTaskDescription = 'Enter to apartment with ' . $EnterCode;
        $inspectionTaskEnterToBuildingSubTaskDescription = $enterToBuildingSubtask['description'];

        if (!$nextReservationExtraInspectionTask) {
            //need to be created

            $subtaskDescription = [];
            array_push(
                $subtaskDescription,
                $inspectionTaskOccupancySubTaskDescription
            );

            array_push(
                $subtaskDescription,
                $inspectionTaskEnterToBuildingSubTaskDescription
            );

            array_push($subtaskDescription,
                $inspectionTaskEnterToApartmentSubTaskDescription
            );


            $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
            $bookingTicket = $bookingTicketService->getBookingThicketByReservationId($nextReservation['id']);

            $apartmentId = $bookingTicket->getApartmentIdAssigned();
            $buildingId = $bookingTicket->getApartmentAssignedBuildingId();
            $checkInDate = $bookingTicket->getDate_from();
            $checkInTime = (is_null($bookingTicket->getApartmentCheckInTime())) ? '13:00:00' : $bookingTicket->getApartmentCheckInTime();
            $checkInDateTime = $checkInDate . ' ' . $checkInTime;
            $checkinDateTimeMinus1Hour = date('Y-m-d H:i:s', strtotime($checkInDateTime) - 3600);
            $teamId = $this->getTeamBasedOnType($taskType, $apartmentId, $buildingId);

            $actionsSet = [
                self::ACTION_CHANGE_DETAILS => 1,
                self::ACTION_CHANGE_STATUS => 1,
                self::ACTION_MANAGE_STAFF => 1,
                self::ACTION_MANAGE_SUBTASKS => 1
            ];

            $taskName = 'Inspect the apartment before check-in';
            $taskDescription = 'Prepare the apartment for the upcoming check-in.';
            $taskPriority = self::TASK_PRIORITY_NORMAL;
            $startDateTime = $checkinDateTimeMinus1Hour;
            $endDateTime = $checkInDateTime;

            $taskData = [
                'title' => $taskName,
                'task_type' => $taskType,
                'team_id' => $teamId,
                'following_team_id' => TeamService::TEAM_CONTACT_CENTER,
                'start_date' => $startDateTime,
                'end_date' => $endDateTime,
                'task_status' => self::STATUS_NEW,
                'creator_id' => UserMain::SYSTEM_USER_ID,
                'task_priority' => $taskPriority,
                'description' => $taskDescription,
                'res_id' => $nextReservation['id'],
                'property_id' => $apartmentId,
                'building_id' => $buildingId,
                'verifier_id' => UserService::AUTO_VERIFY_USER_ID,
                'is_hk' => self::TASK_IS_HOUSEKEEPING,
                'extra_inspection' => self::TASK_EXTRA_INSPECTION,
                'subtask_description' => $subtaskDescription,
            ];

            $id = $this->taskSave($taskData, $actionsSet, true);
            //delete All other extra Key Fob tasks for today on this apartment
            echo 'inspection task created, id = ' . $id . "\e[0m\n\r";
        } else {
            //refresh subtasks
            $inspectionTaskOccupancySubTaskId         = $taskSubtaskDao->getSubtaskLike($nextReservationExtraInspectionTask['id'], 'Occupancy: ')['id'];
            $inspectionTaskEnterToApartmentSubTaskkId = $taskSubtaskDao->getSubtaskLike($nextReservationExtraInspectionTask['id'], 'Enter to apartment with')['id'];
            $inspectionTaskEnterToBuildingSubTaskId   = $taskSubtaskDao->getSubtaskLike($nextReservationExtraInspectionTask['id'], 'Enter to building with')['id'];

            $taskSubtaskDao->save(
                ['description' => $inspectionTaskOccupancySubTaskDescription],
                ['id' => $inspectionTaskOccupancySubTaskId]
            );

            $taskSubtaskDao->save(
                ['description' => $inspectionTaskEnterToApartmentSubTaskDescription],
                ['id' => $inspectionTaskEnterToApartmentSubTaskkId]
            );

            $taskSubtaskDao->save(
                ['description' => $inspectionTaskEnterToBuildingSubTaskDescription],
                ['id' => $inspectionTaskEnterToBuildingSubTaskId]
            );

            echo 'inspection task refreshed, id = ' . $nextReservationExtraInspectionTask['id'] . "\e[0m\n\r";
        }
    }

    /**
     * @param int $apartmentId
     * @param int $resId
     * @return bool
     */
    public function createExtraTaskForLastMinuteReservation($apartmentId, $resId)
    {
        $apartmentDao   = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $bookingService = $this->getServiceLocator()->get('service_booking');
        $taskDao        = $this->getTaskDao();

        $apartmentTimezoneInfo = $apartmentDao->getApartmentTimezone($apartmentId);
        $apartmentTimezone = $apartmentTimezoneInfo['timezone'];
        $datetime = new \DateTime('now');
        $datetime->setTimezone(new \DateTimeZone($apartmentTimezone));
        $dateTimeToday         = $datetime->format('Y-m-d H:i:s');

        $lastReservation       = $bookingService->getReservationByIdForHousekeeping($resId);
        //if last minute
        if ($lastReservation && strtotime($dateTimeToday) >=  strtotime($lastReservation['date_from'] . ' ' . $apartmentTimezoneInfo['check_out'])) {
            $preReservation = $bookingService->getPreviousReservationForApartment(
                $lastReservation['id'],
                $apartmentId,
                $lastReservation['date_from']
            );

            $preReservationTask = $this->getReservationAutoCreatedTask($preReservation['id'], self::TYPE_CLEANING, $apartmentId);

            if (is_null($preReservationTask) || !$preReservationTask) {
                return false;
            }

            $taskSubtaskDao = $this->getTaskSubtasksDao();
            $setCodeToSubtask = $taskSubtaskDao->getSubtaskLike($preReservationTask['id'], ' for next check-in');
            if (!$setCodeToSubtask) {
                return false;
            }

            if (isset($setCodeToSubtask['description']) && !is_null($setCodeToSubtask['description'])) {
                $SubTaskArray = explode(' for next check-in', $setCodeToSubtask['description']) ;
                if (!isset($SubTaskArray[1])) {
                    return false;
                }
                $subTaskFirstPart = $SubTaskArray[0];
                $subTaskFirstPartArray = explode('Set',$subTaskFirstPart);
                if (!isset($subTaskFirstPartArray[1])) {
                    return false;
                }
                $Entercode = $subTaskFirstPartArray[1];


                $taskType             = self::TYPE_APT_SERVICE;
                $taskName             = 'Inspect the apartment for new occupants';

                $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
                $bookingTicket        = $bookingTicketService->getBookingThicketByReservationId($resId);

                $buildingId           = $bookingTicket->getApartmentAssignedBuildingId();
                $taskPriority         = self::TASK_PRIORITY_CRITICAL;
                $teamId               = $this->getTeamBasedOnType($taskType, $apartmentId, $buildingId);

                $actionsSet = [
                    self::ACTION_CHANGE_DETAILS  => 1,
                    self::ACTION_CHANGE_STATUS   => 1,
                    self::ACTION_MANAGE_STAFF    => 1,
                    self::ACTION_MANAGE_SUBTASKS => 1,
                ];

                $startDateTime = $dateTimeToday;
                $endDateTime   =  date('Y-m-d H:i:s', strtotime($dateTimeToday) + 3600);

                $subtaskDescription = [];

                if ($lastReservation['outside_door_code']) {
                    array_push(
                        $subtaskDescription,
                        'Enter to building with ' . $lastReservation['outside_door_code']
                    );
                }

                array_push(
                    $subtaskDescription,
                    'Enter to apartment with ' . $Entercode
                );

                if ($lastReservation['next_pin'] && !is_null($lastReservation['next_pin'])) {
                    array_push(
                        $subtaskDescription,
                        'Set ' . $lastReservation['next_pin'] . ' for next check-in'
                    );
                }

                $taskData = [
                    'title'               => $taskName,
                    'task_type'           => $taskType,
                    'team_id'             => $teamId,
                    'following_team_id'   => TeamService::TEAM_CONTACT_CENTER,
                    'start_date'          => $startDateTime,
                    'end_date'            => $endDateTime,
                    'task_status'         => self::STATUS_NEW,
                    'creator_id'          => UserMain::SYSTEM_USER_ID,
                    'task_priority'       => $taskPriority,
                    'description'         => 'Prepare for the next Guest because last minute reservation occured.',
                    'res_id'              => $resId,
                    'property_id'         => $apartmentId,
                    'building_id'         => $buildingId,
                    'verifier_id'         => UserService::AUTO_VERIFY_USER_ID,
                    'is_hk'               => self::TASK_IS_HOUSEKEEPING,
                    'subtask_description' => $subtaskDescription
                ];

                $deleteDate = date("Y-m-d", strtotime($endDateTime));

                $id = $this->taskSave($taskData, $actionsSet, true);
                //delete All other extra Key Fob tasks for today on this apartment
                $taskDao->deleteAllSameDayAutoGeneratedTasks($id, $deleteDate ,$apartmentId, $taskType);
            }
        }

        return true;
    }

    /**
     * Canceled or moved after checkin
     * Result: Create cleaning task and change door code
     *
     * @param int $resId
     * @param int $apartmentId
     * @param string $case Task::CASE_CANCEL or Task::CASE_MOVE
     * @return void/bool
     */
    public function createExtraCleaningCancelReservationAfterCheckin($resId, $apartmentId, $case)
    {
        $apartmentDao   = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $bookingService = $this->getServiceLocator()->get('service_booking');

        $apartmentTimezoneInfo = $apartmentDao->getApartmentTimezone($apartmentId);

        $datetime = new \DateTime('now');
        $datetime->setTimezone(new \DateTimeZone($apartmentTimezoneInfo['timezone']));

        $taskDateTime = new \DateTime(date('Y-m-d') . ' ' . $apartmentTimezoneInfo['check_out']);
        $taskDateTime->setTimezone(new \DateTimeZone($apartmentTimezoneInfo['timezone']));

        $dateToday       = $datetime->format('Y-m-d');
        $lastReservation = $bookingService->getReservationByIdForHousekeeping($resId);
        $taskDao         = $this->getTaskDao();

        //if reservation has already finished
        if (strtotime($datetime->format('Y-m-d H:i:s')) > strtotime($lastReservation['date_to'] . ' ' . $apartmentTimezoneInfo['check_out'])) {
            return false;
        }

        if ($taskDateTime > $apartmentTimezoneInfo['check_out']) {
            $taskDateReal = date('Y-m-d', strtotime('tomorrow')) . $apartmentTimezoneInfo['check_out'];
        } else {
            $taskDateReal = date('Y-m-d') . ' ' . $apartmentTimezoneInfo['check_out'];
        }

        if ($lastReservation) {
            $nextReservations = $bookingService->getNextReservationsForApartment(
                $apartmentId,
                $dateToday,
                $lastReservation
            );

            if (isset($nextReservations[0])) {
                $nextReservation = $nextReservations[0];

                $taskType             = self::TYPE_CLEANING;
                $taskName             = 'Clean The Apartment';
                $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
                $bookingTicket        = $bookingTicketService->getBookingThicketByReservationId($resId);
                $buildingId           = $bookingTicket->getApartmentAssignedBuildingId();
                $teamId               = $this->getTeamBasedOnType($taskType, $apartmentId, $buildingId);


                $actionsSet = [
                    self::ACTION_CHANGE_DETAILS  => 1,
                    self::ACTION_CHANGE_STATUS   => 1,
                    self::ACTION_MANAGE_STAFF    => 1,
                    self::ACTION_MANAGE_SUBTASKS => 1
                ];

                $taskDescription = sprintf(
                    'Clean the Apartment and prepare for the next Guest. Extra task has been created because %s reservation has been %s.',
                    $lastReservation['res_number'],
                    $case
                );

                $taskData = [
                    'title'          => $taskName,
                    'task_type'      => $taskType,
                    'team_id'        => $teamId,
                    'following_team_id' => TeamService::TEAM_CONTACT_CENTER,
                    'start_date'     => $taskDateReal,
                    'end_date'       => date('Y-m-d H:i:s', strtotime($taskDateReal) + 7200), //2 hours for cleaning and change the key
                    'task_status'    => self::STATUS_NEW,
                    'creator_id'     => UserMain::SYSTEM_USER_ID,
                    'task_priority'  => self::TASK_PRIORITY_NORMAL,
                    'description'    => $taskDescription,
                    'res_id'         => $resId,
                    'property_id'    => $apartmentId,
                    'building_id'    => $buildingId,
                    'is_hk'          => self::TASK_IS_HOUSEKEEPING,
                ];

                $taskId = $this->taskSave($taskData, $actionsSet, true);

                $deleteDate = date('Y-m-d', strtotime($taskDateReal));

                //delete All other extra Key Fob tasks for today on this apartment
                $taskDao->deleteAllSameDayAutoGeneratedTasks($taskId, $deleteDate ,$apartmentId, $taskType);

                $taskSubtaskDao = $this->getTaskSubtasksDao();

                if ($lastReservation['outside_door_code']) {
                    $subtaskData = [
                        'task_id'     => $taskId,
                        'description' => 'Enter to building with ' . $lastReservation['outside_door_code'],
                        'status'      => 0
                    ];
                    $taskSubtaskDao->save($subtaskData);
                }

                if ($case == self::CASE_MOVE) {
                    $lockGeneralService = $this->getServiceLocator()->get('service_lock_general');
                    $bookingDao         = $this->getServiceLocator()->get('dao_booking_booking');
                    $resInfo            = $bookingDao->fetchOne(['id' => $resId], ['pin']);

                    $oldLocationLockInfo = $lockGeneralService->getLockInfoByUsage(
                        $apartmentId,
                        LockGeneral::USAGE_APARTMENT_TYPE,
                        $resInfo->getPin()
                    );

                    if (isset($oldLocationLockInfo[1])) {
                        $subtaskData = [
                            'task_id'     => $taskId,
                            'description' => 'Enter to apartment with ' . $oldLocationLockInfo[1]['code'],
                            'status'      => 0
                        ];
                        $taskSubtaskDao->save($subtaskData);
                    }
                } elseif ($case != self::CASE_MOVE && $lastReservation['pin']) {
                    $subtaskData = [
                        'task_id'     => $taskId,
                        'description' => 'Enter to apartment with ' . $lastReservation['pin'],
                        'status'      => 0
                    ];
                    $taskSubtaskDao->save($subtaskData);
                }

                if ($nextReservation['next_pin']) {
                    $taskSubtaskDao->save(
                        [
                            'description' => 'Set ' . $nextReservation['next_pin'] . ' for next check-in',
                            'task_id'     => $taskId,
                            'status'      => 0
                        ]
                    );
                }

                $taskSubtaskDao->save(
                    [
                        'description' => 'Occupancy: ' . $nextReservation['occupancy'],
                        'task_id'     => $taskId,
                        'status'      => 0
                    ]
                );

                $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

                $bookingDao->save(
                    ['no_refresh' => 1],
                    ['id' => $nextReservation['id']]
                );
            }
        }
    }

    /**
     * Guest not checked in, but reservation has started
     * Result: Create Inspection task
     *
     * @param int $resId
     * @param int $apartmentId
     * @param string $case Task::CASE_CANCEL or Task::CASE_MOVE
     * @return void
     */
    public function checkAndCreateExtraTaskForStartedReservationsCancelation($resId, $apartmentId, $case)
    {
        $taskDao        = $this->getTaskDao();
        $apartmentDao   = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $bookingService = $this->getServiceLocator()->get('service_booking');

        $apartmentTimezoneInfo = $apartmentDao->getApartmentTimezone($apartmentId);
        $apartmentCheckInTime  = $apartmentTimezoneInfo['check_in'];
        $datetime              = new \DateTime('now');

        $datetime->setTimezone(new \DateTimeZone($apartmentTimezoneInfo['timezone']));

        $dateToday             = $datetime->format('Y-m-d');
        $dateTimeToday         = $datetime->format('Y-m-d H:i:s');

        $reservationInfo          = $bookingService->getReservationByIdForHousekeeping($resId);
        //if reservation has finished
        if (strtotime($dateTimeToday) > strtotime($reservationInfo['date_to'] . ' ' . $apartmentCheckInTime)) {
            return false;
        }
        $reservationDateFrom      = $reservationInfo['date_from'];
        $apartmentCheckInDateTime = $reservationDateFrom . ' ' . $apartmentCheckInTime;
        $previousReservation      = $bookingService->getPreviousReservationForApartment(
            $resId,
            $apartmentId,
            $reservationInfo['date_from']
        );
        $previousReservationId         = $previousReservation['id'];
        $previousReservationTask       = $this->getReservationAutoCreatedTask($previousReservationId, self::TYPE_CLEANING, $previousReservation['apartment_id_assigned']);
        $previousReservationTaskStatus = $previousReservationTask['task_status'];

        //checking if the reservation has started, or the previous reservation task has been done or verified
        if (   $previousReservationTaskStatus == self::STATUS_DONE
            || $previousReservationTaskStatus == self::STATUS_VERIFIED
            || strtotime($dateTimeToday) >= strtotime($apartmentCheckInDateTime)
        ) {
            $nextReservations = $bookingService->getNextReservationsForApartment(
                $apartmentId,
                $dateToday,
                $reservationInfo
            );

            if (isset($nextReservations[0])) {
                $nextReservation = $nextReservations[0];


                if ((in_array($nextReservation['lock_type'], LockGeneral::$typeWithoutCode)
                        && $reservationInfo['occupancy'] == $nextReservation['occupancy'])
                    || $nextReservation['no_refresh'] == 1
                ) {
                    return false;
                }

                $taskType             = self::TYPE_APT_SERVICE;
                $taskName             = 'Inspect the apartment for new occupants';

                $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
                $bookingTicket        = $bookingTicketService->getBookingThicketByReservationId($nextReservation['id']);
                $buildingId           = $bookingTicket->getApartmentAssignedBuildingId();
                $teamId               = $this->getTeamBasedOnType($taskType, $apartmentId, $buildingId);


                $actionsSet = [
                    self::ACTION_CHANGE_DETAILS  => 1,
                    self::ACTION_CHANGE_STATUS   => 1,
                    self::ACTION_MANAGE_STAFF    => 1,
                    self::ACTION_MANAGE_SUBTASKS => 1
                ];

                $subtaskDescription = [];

                if ($reservationInfo['occupancy'] !== $nextReservation['occupancy']) {
                    array_push(
                        $subtaskDescription,
                        'Occupancy: ' . $nextReservation['occupancy'] . ' (old occupancy: ' . $reservationInfo['occupancy'] . ')'
                    );
                }

                if ($reservationInfo['outside_door_code']) {
                    array_push(
                        $subtaskDescription,
                        'Enter to building with ' . $reservationInfo['outside_door_code']
                    );
                }

                if ($case == self::CASE_MOVE) {
                    $lockGeneralService = $this->getServiceLocator()->get('service_lock_general');
                    $bookingDao         = $this->getServiceLocator()->get('dao_booking_booking');
                    $resInfo            = $bookingDao->fetchOne(['id' => $resId], ['pin']);

                    $oldLocationLockInfo = $lockGeneralService->getLockInfoByUsage(
                        $apartmentId,
                        LockGeneral::USAGE_APARTMENT_TYPE,
                        $resInfo->getPin()
                    );

                    if (isset($oldLocationLockInfo[1])) {
                        array_push(
                            $subtaskDescription,
                            'Enter to apartment with ' . $oldLocationLockInfo[1]['code']
                        );
                    }
                } elseif ($case != self::CASE_MOVE && $reservationInfo['pin']) {
                    array_push(
                        $subtaskDescription,
                        'Enter to apartment with ' . $reservationInfo['pin']
                    );
                }

                if ($nextReservation['next_pin']) {
                    array_push(
                        $subtaskDescription,
                        'Set ' . $nextReservation['next_pin'] . ' for next check-in'
                    );
                }

                $taskPriority = self::TASK_PRIORITY_CRITICAL;

                $taskDescription = sprintf(
                    'Prepare For The Next Guest. Extra task has been created because %s reservation has been %s.',
                    $reservationInfo['res_number'],
                    $case
                );

                $taskData = [
                    'title'               => $taskName,
                    'task_type'           => $taskType,
                    'team_id'             => $teamId,
                    'following_team_id'   => TeamService::TEAM_CONTACT_CENTER,
                    'start_date'          => $dateTimeToday,
                    'end_date'            =>  date('Y-m-d H:i:s', strtotime($dateTimeToday) + 3600),
                    'task_status'         => self::STATUS_NEW,
                    'creator_id'          => UserMain::SYSTEM_USER_ID,
                    'task_priority'       => $taskPriority,
                    'description'         => $taskDescription,
                    'res_id'              => $resId,
                    'property_id'         => $apartmentId,
                    'building_id'         => $buildingId,
                    'verifier_id'         => UserService::AUTO_VERIFY_USER_ID,
                    'is_hk'               => self::TASK_IS_HOUSEKEEPING,
                    'subtask_description' => $subtaskDescription
                ];

                $id = $this->taskSave($taskData, $actionsSet, true);
                //delete All other extra Key Fob tasks for today on this apartment
                $taskDao->deleteAllSameDayAutoGeneratedTasks($id, $dateToday ,$apartmentId, $taskType);
                $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

                $bookingDao->save(
                    ['no_refresh' => 1],
                    ['id' => $nextReservation['id']]
                );

            }
        }
    }

    /**
     * corrects and fills the reservation cleaning tasks, based on keys
     * @param $preReservation
     * @param $lastReservation
     * @param $nextReservation
     */
    public function refreshCleaningTaskSubtasks($preReservation, $lastReservation, $nextReservation)
    {
        $taskDao               = $this->getTaskDao();
        $taskSubtaskDao        = $this->getTaskSubtasksDao();
        $lastReservationTask   = $this->getReservationAutoCreatedTask($lastReservation['id'], self::TYPE_CLEANING, $lastReservation['apartment_id_assigned']);
        $lastReservationTaskId = $lastReservationTask['id'];

        if (!$lastReservationTaskId) {
            //normally the task should be created on the reservation
            //this is an extra check and also for test issues,
            //as long as on alpha, release and local servers no reservations happening
            $lastReservationTaskId = $this->createAutoTaskForCleaning($lastReservation['id']);
        }

            $checkOutTime =
                (is_null($lastReservation['apartment_check_out_time']) || !($lastReservation['apartment_check_out_time']))
                ? '11:00:00' : $lastReservation['apartment_check_out_time'];
            $taskDao->save(
                [
                    'start_date' => $lastReservation['date_to'] . ' ' .$checkOutTime,
                    'end_date'   => date('Y-m-d H:i:s', strtotime($lastReservation['date_to'] . ' ' .$checkOutTime) + 7200),
                ],
                ['id' => $lastReservationTaskId]
            );

            $enterWithCodeSubtaskId = $taskSubtaskDao->getSubtaskIdLike($lastReservationTaskId, 'Enter to apartment with');

            if (is_null($enterWithCodeSubtaskId)) { //if the subtask is not created yet
                if ($lastReservation['outside_door_code']) {
                    $subtaskData = [
                        'task_id'     => $lastReservationTaskId,
                        'description' => 'Enter to building with ' . $lastReservation['outside_door_code'],
                        'status'      => 0
                    ];
                    $taskSubtaskDao->save($subtaskData);
                }

                if ($lastReservation['pin']) {
                    if ($preReservation['task_status'] == self::STATUS_CANCEL) {
                        $lastReservation['pin'] = $preReservation['pin'];
                    }

                    $subtaskData = [
                        'task_id'     => $lastReservationTaskId,
                        'description' => 'Enter to apartment with ' . $lastReservation['pin'],
                        'status'      => 0
                    ];
                    $taskSubtaskDao->save($subtaskData);
                }
            } else {
                if ($lastReservation['outside_door_code']) {
                    $taskSubtaskDao->save(
                        ['description' => 'Enter to building with ' . $lastReservation['outside_door_code']],
                        ['id' => $enterWithCodeSubtaskId]
                    );
                }
                if ($lastReservation['pin']) {
                    if ($preReservation['task_status'] == self::STATUS_CANCEL) {
                        $lastReservation['pin'] = $preReservation['pin'];
                    }
                    $taskSubtaskDao->save(
                        ['description' => 'Enter to apartment with ' . $lastReservation['pin']],
                        ['id' => $enterWithCodeSubtaskId]
                    );
                }
            }

            if (false !== $nextReservation) {
                $setCodeToSubtaskId = $taskSubtaskDao->getSubtaskIdLike($lastReservationTaskId, 'for next check-in');
                if (is_null($setCodeToSubtaskId)) { //if the subtask is not created yet
                    if ($nextReservation['next_pin']) {
                        $taskSubtaskDao->save(
                            [
                                'description' => 'Set ' . $nextReservation['next_pin'] . ' for next check-in',
                                'task_id'     => $lastReservationTaskId,
                                'status'      => 0
                            ]
                        );
                    }
                } else {
                    if ($nextReservation['next_pin']) {
                        $taskSubtaskDao->save(
                            ['description' => 'Set ' . $nextReservation['next_pin'] . ' for next check-in'],
                            ['id' => $setCodeToSubtaskId]
                        );
                    }
                }

                $occupancySubtaskId = $taskSubtaskDao->getSubtaskIdLike($lastReservationTaskId, 'Occupancy:');
                if (is_null($occupancySubtaskId)) { //if the subtask is not created yet
                    $taskSubtaskDao->save(
                        [
                            'description' => 'Occupancy: ' . $nextReservation['occupancy'],
                            'task_id'     => $lastReservationTaskId,
                            'status'      => 0
                        ]
                    );
                } else {
                    $taskSubtaskDao->save(
                        ['description' => 'Occupancy: ' . $nextReservation['occupancy']],
                        ['id' => $occupancySubtaskId]
                    );
                }
            }

    }

    /**
     * @param $lastReservationTaskId
     * @param $startDate
     * @param $endDate
     */
    public function changeReservationsStartDatetoNow($lastReservationTaskId, $startDate, $endDate)
    {
        $taskDao               = $this->getTaskDao();
        $taskDao->save(
            [
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'priority' => self::TASK_PRIORITY_IMPORTANT
            ],
            ['id' => $lastReservationTaskId]
        );
    }

    /**
     * @param int $taskId
     * @param int $statusId
     * @return bool
     */
    public function changeTaskStatus($taskId, $statusId)
    {
        try {
            $taskDao = $this->getTaskDao();

            $taskData = $taskDao->getTaskById($taskId);
            $finalStatus = $statusId;

            if ($taskData && array_key_exists($statusId, $this->getTaskStatus())) {
                if ($taskData->getVerifierId() == User::AUTO_VERIFY_USER_ID && $statusId == self::STATUS_DONE) {
                    $finalStatus = self::STATUS_VERIFIED;
                }
                $taskDao->save(
                    ['task_status' => (int)$finalStatus],
                    ['id' => (int)$taskId]
                );

                if ($finalStatus == self::STATUS_VERIFIED || $finalStatus == self::STATUS_DONE) {
                    $taskSubtaskDao = $this->getTaskSubtasksDao();
                    $taskSubtaskDao->save(
                        ['status' => 1],
                        ['task_id' => $taskId]
                    );
                }

                $auth = $this->getServiceLocator()->get('library_backoffice_auth');
                /** @var Logger $logger */
                $logger = $this->getServiceLocator()->get('ActionLogger');


                $logger->save(
                    Logger::MODULE_TASK,
                    $taskId,
                    self::getTaskStatusMapping()[$statusId],
                    self::getStatusesInWords()[$statusId],
                    $auth->getIdentity()->id
                );

                if ($statusId == self::STATUS_DONE && $finalStatus == self::STATUS_VERIFIED) {
                    $logger->save(Logger::MODULE_TASK, (int)$taskId, Logger::ACTION_TASK_AUTO_VERIFY, 'Auto Verified', UserMain::SYSTEM_USER_ID);
                }

                return [
                    'task_status' => self::getStatusesInWords()[$finalStatus],
                    'task_status_id' => [$finalStatus],
                ];
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot change task status', [
                'task_id'   => $taskId,
                'status_id' => $statusId
            ]);
        }

        return FALSE;
    }

    public function deleteTask($params)
    {
        $taskDao = $this->getTaskDao();
        $taskDao->delete($params);
    }

    public function getHousekeepingTask($taskId)
    {
        $taskDao = $this->getTaskDao();
        return $taskDao->getHousekeepingTask($taskId);
    }

    /**
     * @param $reservationId
     * @return bool
     */
    public function createAutoTaskReceiveCccaForm($reservationId)
    {
        /**
         * @var TaskTypeDAO $taskTypeDao
         * @var TaskStaffDAO $taskStaffDao
         * @var \DDD\Dao\Booking\Booking $reservationsDao
         */
        $taskTypeDao     = $this->getServiceLocator()->get('dao_task_type');
        $taskStaffDao    = $this->getServiceLocator()->get('dao_task_staff');
        $reservationsDao = $this->getServiceLocator()->get('dao_booking_booking');
        $logger          = $this->getServiceLocator()->get('ActionLogger');

        /**
         * @var BookingDomain $reservationData
         */
        $reservationData = $reservationsDao->fetchOne(
            ['id' => $reservationId],
            [
                'res_number',
                'date_from',
                'apartment_id_assigned'
            ]
        );

        $taskTypeCCCA = $taskTypeDao->fetchOne(
            ['id' => self::TYPE_CCCA],
            ['associated_team_id']
        );

        $actionsSet = [
            self::ACTION_CHANGE_DETAILS  => 1,
            self::ACTION_CHANGE_STATUS   => 1,
            self::ACTION_MANAGE_STAFF    => 1,
            self::ACTION_MANAGE_SUBTASKS => 1
        ];

        $currentDate = date(Constants::GLOBAL_DATE_FORMAT . ' H:i');

        $endDate = date(Constants::GLOBAL_DATE_FORMAT . ' H:i', strtotime('+36 hour'));
        if (strtotime($reservationData->getDateFrom()) < strtotime('+36 hour')) {
            $endDate = date(Constants::GLOBAL_DATE_FORMAT, strtotime($reservationData->getDateFrom())) . ' 23:59';
        }

        $taskData = [
            'title'             => 'Collect CCCA Form from customer ( R# ' . $reservationData->getResNumber() . ' )',
            'task_type'         => self::TYPE_CCCA,
            'team_id'           => $taskTypeCCCA->getAssociatedTeamId(),
            'following_team_id' => TeamService::TEAM_CONTACT_CENTER,
            'res_id'            => $reservationId,
            'property_id'       => $reservationData->getApartmentIdAssigned(),
            'start_date'        => $currentDate,
            'end_date'          => $endDate,
            'task_status'       => self::STATUS_NEW,
            'task_priority'     => self::TASK_PRIORITY_NORMAL,
            'creator_id'        => UserMain::SYSTEM_USER_ID,
            'verifier_id'       => UserService::AUTO_VERIFY_USER_ID
        ];

        $taskId = $this->taskSave($taskData, $actionsSet);

        if ($taskId) {
            $taskStaffDao->save(
                [
                    'task_id' => $taskId,
                    'user_id' => UserService::ANY_TEAM_MEMBER_USER_ID,
                    'type'    => self::STAFF_RESPONSIBLE
                ]
            );
        }

        /**
         * @var BackofficeAuthenticationService $authenticationService
         */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $loggedInUser = $authenticationService->getIdentity();

        $logger->save(
            Logger::MODULE_TASK,
            $taskId,
            Logger::ACTION_TASK_SYSTYEM_GENERATED,
            'CCCA Form sent to customer by ' . $loggedInUser->firstname . ' ' . $loggedInUser->lastname,
            UserMain::SYSTEM_USER_ID
        );

        return $taskId ? true : false;
    }

    /**
     * @param \DDD\Domain\Team\Team $team
     * @param $userIdentity
     * @param $roleInTeam
     * @return array
     */
    public function getResForHousekeeperBasedOnTasks($team, $roleInTeam, $isGlobal, $category, $sortId = 0)
    {
        /** @var \Library\Authentication\BackofficeAuthenticationService $auth */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        /** @var \DDD\Dao\Booking\Booking $bookingDao */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        /** @var \DDD\Dao\Task\Task $taskDao */
        $taskDao     = $this->getServiceLocator()->get('dao_task_task');

        $userIdentity = $auth->getIdentity();
        $timezone   = $userIdentity->timezone;

        if (!is_null($team->getTimezone())) {
            $timezone = $team->getTimezone();
        }

        $userId      = $userIdentity->id;
        $date        = new \DateTime('now', new \DateTimeZone($timezone));

        switch ($category) {
            case 'recent':
                // 2 days before today
                $end   = $date->modify('-1 days')->format('Y-m-d');
                $start = $date->modify('-1 days')->format('Y-m-d');
                break;
            case 'today':
                $end   = $date->format('Y-m-d');
                $start = $date->format('Y-m-d');
                break;
            // 5 days after today
            case 'upcoming':
                $start = $date->modify('+1 days')->format('Y-m-d');
                $end   = $date->modify('+4 days')->format('Y-m-d');
                break;
            default:
                return false;
        }

        $param['teamId']     = $team->getId();
        $param['userId']     = $userId;
        $param['roleInTeam'] = $roleInTeam;
        $param['isGlobal']   = $isGlobal;
        $param['sortId']     = $sortId;

        $taskList = [];
        $currDate = $start;
        while ($currDate <= $end) {
            $param['currentDay'] = $currDate;

            $res = $taskDao->getResForHousekeeperBasedOnTasks($param);

            $res = iterator_to_array($res);
            foreach ($res as $value) {
                $value['sameDayCheckin'] = false;
                $nextReservationSameDay = $bookingDao->getNextReservationByAcc(
                    $value['property_id'],
                    date('Y-m-d', strtotime($value['start_date'])),
                    $value['res_id']
                );

                if ($nextReservationSameDay) {
                    $value['sameDayCheckin'] = true;
                }
            }

            if (count($res)) {
                $taskList[$currDate] = $res;
            }

            $currDate = date('Y-m-d', strtotime($currDate . ' +1 day'));
        }

        return $taskList;
    }

    /**
     * [parkingIssueTask]
     * @param  int $reservation
     */
    public function parkingIssueTask($resId, $apartmentId, $startDate)
    {
        $accommodationDao   = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $taskDao            = $this->getServiceLocator()->get('dao_task_task');
        $alreadyCreatedTask = $taskDao->checkExistTaskByParams($resId, self::TYPE_RESERVATION, TeamService::TEAM_CONTACT_CENTER, UserService::SYSTEM_USER_ID);

        if ($alreadyCreatedTask) {
            return false;
        }

        $actionsSet = [
            self::ACTION_CHANGE_DETAILS  => 1,
            self::ACTION_CHANGE_STATUS   => 1,
            self::ACTION_MANAGE_STAFF    => 1,
            self::ACTION_MANAGE_SUBTASKS => 1
        ];

        $buildingData = $accommodationDao->fetchOne(['id' => $apartmentId], ['building_id']);
        $buildingId   = null;

        if ($buildingData) {
            $buildingId = $buildingData->getBuildingId();
        }

        $taskData = [
            'title'          => 'Resolve parking issue',
            'description'    => 'There is a parking purchased for this reservation. Add a parking charge please. For more details look in guest remarks.',
            'res_id'         => $resId,
            'property_id'    => $apartmentId,
            'start_date'     => date('Y-m-d'),
            'end_date'       => $startDate,
            'building_id'    => $buildingId,
            'task_type'      => self::TYPE_RESERVATION,
            'task_status'    => self::STATUS_NEW,
            'task_priority'  => self::TASK_PRIORITY_IMPORTANT,
            'team_id'        => TeamService::TEAM_CONTACT_CENTER,
            'creator_id'     => UserService::SYSTEM_USER_ID,
            'verifier_id'    => UserService::AUTO_VERIFY_USER_ID,
            'responsible_id' => UserService::ANY_TEAM_MEMBER_USER_ID
        ];

        $taskId = $this->taskSave($taskData, $actionsSet, true);
    }

    /**
     * @param array $filter
     * @return \DDD\Domain\Task\Minimal[]
     */
    public function getIncidentReports($filter)
    {
        /**
         * @var \DDD\Dao\Task\Task $taskDao
         */
        $taskDao = $this->getServiceLocator()->get('dao_task_task');

        return $taskDao->getIncidentReports($filter);
    }

    /**
     * @param $userFullName
     * @param $startDate
     * @param $endDate
     * @param $totalDaysCount
     * @return bool
     */
    public function createApprovedUnpaidVacationAutoTask($userFullName, $startDate, $endDate, $totalDaysCount)
    {
        $taskStaffDao = $this->getServiceLocator()->get('dao_task_staff');
        $actionsSet = [
            self::ACTION_CHANGE_DETAILS  => 1,
            self::ACTION_CHANGE_STATUS   => 1,
            self::ACTION_MANAGE_STAFF    => 1,
            self::ACTION_MANAGE_SUBTASKS => 1
        ];

        $descriptionFormat = '%s has taken an unpaid vacation from %s to %s for %s working days.';
        $description = sprintf($descriptionFormat, $userFullName, $startDate, $endDate, $totalDaysCount);

        $taskData = [
            'title'           => 'Approved Unpaid Vacation',
            'task_type'       => self::TYPE_FINANCIAL,
            'task_status'     => self::STATUS_NEW,
            'task_priority'   => self::TASK_PRIORITY_NORMAL,
            'description'     => $description,
            'team_id'         => TeamService::TEAM_ACCOUNT_PAYABLE,
            'start_date'      => $startDate,
            'end_date'        => date("Y-m-t", strtotime($endDate)),
            'creation_date'   => date('Y-m-d'),
            'creator_id'      => UserService::SYSTEM_USER_ID,
            'verifier_id'     => UserService::AUTO_VERIFY_USER_ID
        ];

        $taskId = $this->taskSave($taskData, $actionsSet);
        if ($taskId) {
            $taskStaffDao->save(
                [
                    'task_id' => $taskId,
                    'user_id' => UserService::ANY_TEAM_MEMBER_USER_ID,
                    'type'    => self::STAFF_RESPONSIBLE
                ]
            );
        }

        return true;
    }

    /**
     * @param $userFullName
     * @param $startDate
     * @param $endDate
     * @param $requestedDaysCount
     * @param $totalSickDays
     * @param $takenSickDays
     * @return bool
     */
    public function createSickDayPayOutAutoTask($userFullName, $startDate, $endDate, $requestedDaysCount, $totalSickDays, $takenSickDays)
    {
        $description = $userFullName . ' has taken Sick Days from ' .
            $startDate . ' to ' . $endDate . ' for ' . $requestedDaysCount .
            ' working days. Sick days Per Year - ' . $totalSickDays .
            '; Sick days Remaining after Approval - ' . ($totalSickDays - $takenSickDays) . '.';


        $now = date('Y-m-d H:i:s');
        $endDate = date('Y-m-28');

        if (strtotime($endDate) <= strtotime('now')) {
            $endDate = new \DateTime($endDate);
            $endDate = $endDate->modify('+1 month')->format('Y-m-d');
        }

        $taskData = [
            'title'         => 'Sick day pay out for ' . $userFullName,
            'task_type'     => self::TYPE_FINANCIAL,
            'team_id'       => TeamService::TEAM_ACCOUNT_PAYABLE,
            'start_date'    => date('Y-m-d H:i:s'),
            'end_date'      => $endDate,
            'creation_date' => date('Y-m-d', strtotime($now)),
            'task_status'   => self::STATUS_NEW,
            'task_priority' => self::TASK_PRIORITY_IMPORTANT,
            'creator_id'    => UserService::SYSTEM_USER_ID,
            'description'   => $description
        ];

        $actionsSet = [
            self::ACTION_CHANGE_DETAILS  => 1,
            self::ACTION_CHANGE_STATUS   => 1,
            self::ACTION_MANAGE_STAFF    => 1,
            self::ACTION_MANAGE_SUBTASKS => 1
        ];

        $this->taskSave($taskData, $actionsSet);

        return true;
    }

    public function changeTaskDate(
        $resId,
        $apartmentId,
        $currentDateCity,
        $resCheckoutDate,
        $taskType,
        $autoGenerated
    ) {
        $taskStatus = [self::STATUS_DONE, self::STATUS_VERIFIED];
        /** @var \DDD\Dao\Booking\Booking $bookingDao */
        $bookingDao   = $this->getServiceLocator()->get('dao_booking_booking');
        $checkOutDate = strtotime(date('Y-m-d', strtotime($currentDateCity)));

        $taskDao = $this->getTaskDao();

        $taskInfo = $taskDao->fetchOne(
            [
                'res_id'         => $resId,
                'property_id'    => $apartmentId,
                'task_type'      => $taskType,
                'is_hk'          => $autoGenerated
            ]
        );

        if ($taskInfo) {
            if (   ($checkOutDate < $resCheckoutDate)
                && !in_array($taskInfo->getTask_status(), $taskStatus)
            ) {

                $time     = date('H:i:s', strtotime($currentDateCity));
                $taskTime = date('H:i:s', strtotime($taskInfo->getStartDate()));

                $startDate = date('Y-m-d '. $taskTime, strtotime($currentDateCity));
                $checkoutNextDay = strtotime(date('Y-m-d' , strtotime($currentDateCity . '+1 days')));

                if (   (strtotime($time) > strtotime($taskTime))
                    && ($checkoutNextDay < $resCheckoutDate)
                ) {
                    $startDate = date('Y-m-d '. $taskTime, strtotime($currentDateCity . '+1 days'));
                }

                $taskDao->save(
                    [
                        'start_date'    => $startDate,
                        'end_date'      => date('Y-m-d H:i:s', strtotime($startDate) + 7200),
                        'creation_date' => date('Y-m-d'),
                        'task_status'   => self::STATUS_NEW
                    ],
                    ['id' => $taskInfo->getId()]
                );

                $bookingDao->save(
                    ['no_refresh' => 1],
                    ['id' => $resId]
                );
            }
        }
    }

    public function changeSubtaskOccupancy($resId, $occupancy)
    {
        $taskSubtaskDao = $this->getTaskSubtasksDao();
        $taskDao        = $this->getTaskDao();
        $tasks          = $taskDao->fetchAll(['res_id' => $resId]);

        foreach ($tasks as $task) {
            $taskSubtaskDao->changeSubtaskOccupancy($task->getId(), $occupancy);
        }
    }


    public function createParkingMoveTask($resId, $resNumber, $apartmentId)
    {
        $accommodationDao   = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $taskDao            = $this->getServiceLocator()->get('dao_task_task');

        $actionsSet = [
            self::ACTION_CHANGE_DETAILS  => 1,
            self::ACTION_CHANGE_STATUS   => 1,
            self::ACTION_MANAGE_STAFF    => 1,
            self::ACTION_MANAGE_SUBTASKS => 1
        ];

        $buildingData = $accommodationDao->fetchOne(['id' => $apartmentId], ['building_id']);
        $buildingId   = null;

        if ($buildingData) {
            $buildingId = $buildingData->getBuildingId();
        }

        $taskData = [
            'title'          => 'Add Parking for Moved Reservation',
            'description'    => '#' . $resNumber . ' have been moved, system reversed existing parking charges, please take corresponding actions.',
            'res_id'         => $resId,
            'property_id'    => $apartmentId,
            'start_date'     => date('Y-m-d'),
            'end_date'       => date('Y-m-d', strtotime('+1 day')),
            'building_id'    => $buildingId,
            'task_type'      => self::TYPE_RESERVATION,
            'task_status'    => self::STATUS_NEW,
            'task_priority'  => self::TASK_PRIORITY_IMPORTANT,
            'team_id'        => TeamService::TEAM_CONTACT_CENTER,
            'creator_id'     => UserService::SYSTEM_USER_ID,
            'verifier_id'    => UserService::AUTO_VERIFY_USER_ID,
            'responsible_id' => UserService::ANY_TEAM_MEMBER_USER_ID
        ];

        $taskId = $this->taskSave($taskData, $actionsSet, true);
    }

    /**
     * @param $reservationId
     * @return int
     */
    public function createAutoTaskForMissingRate($reservationId)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var \DDD\Dao\Apartment\Main $apartmentDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $apartmentDao = $this->getServiceLocator()->get('dao_apartment_main');
        $reservationData = $bookingDao->getDataById($reservationId, ['apartment_id_assigned']);
        if (!$reservationData) {
            return false;
        }

        $actionsSet = [
            self::ACTION_CHANGE_DETAILS  => 1,
            self::ACTION_CHANGE_STATUS   => 1,
            self::ACTION_MANAGE_STAFF    => 1,
            self::ACTION_MANAGE_SUBTASKS => 1
        ];
        $apartmentId = $reservationData['apartment_id_assigned'];
        $buildingData = $apartmentDao->getApartmentBuilding($apartmentId);
        $buildingId   = null;

        if ($buildingData) {
            $buildingId = $buildingData['building_id'];
        }

        $taskData = [
            'title'          => 'Rate was not found. ',
            'description'    => 'The rate of this reservation was not found, the Parent rate was taken instead. Please check and correct the prices. ',
            'res_id'         => $reservationId,
            'property_id'    => $apartmentId,
            'start_date'     => date('Y-m-d'),
            'end_date'       => date('Y-m-d', strtotime('+1 day')),
            'building_id'    => $buildingId,
            'task_type'      => self::TYPE_RESERVATION,
            'task_status'    => self::STATUS_NEW,
            'task_priority'  => self::TASK_PRIORITY_HIGH,
            'team_id'        => TeamService::TEAM_FINANCE,
            'creator_id'     => UserService::SYSTEM_USER_ID,
            'verifier_id'    => UserService::AUTO_VERIFY_USER_ID,
            'responsible_id' => UserService::ANY_TEAM_MEMBER_USER_ID
        ];

        return $this->taskSave($taskData, $actionsSet, true);
    }

}
