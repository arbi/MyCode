<?php

namespace Backoffice\Form;

use DDD\Service\User;
use Library\Form\FormBase;
use DDD\Service\Task as TaskService;
use Zend\Form\Element;
use Library\Constants\Constants;

class Task extends FormBase
{
    public function __construct($data, $options, $actionsSet, $autoFilledData = [])
    {
        parent::__construct('task-form');

        /**
         * @var \DDD\Domain\Task\Task $taskMain
         */
        $taskMain = null;

        if (!empty($data)) {
            $taskMain = $data->get('taskMain');
        }

        $this->setAttributes([
            'action' => 'task/edit',
            'method' => 'post',
            'class' => 'form-horizontal',
            'id' => 'task-form'
        ]);
        $this->setName('task-form');

        $title = [
            'type' => 'text',
            'class' => 'form-control',
            'id' => 'title',
        ];

        $dateStart = [
            'type' => 'text',
            'class' => 'form-control datetimepicker',
            'id' => 'start_date',
        ];

        $dateEnd = [
            'type' => 'text',
            'class' => 'form-control datetimepicker',
            'id' => 'end_date',
        ];

        $property_name = [
            'type' => 'text',
            'class' => 'form-control',
            'id' => 'property_name',
            'maxlength' => 150,
        ];

        $building_name = [
            'type' => 'text',
            'class' => 'form-control',
            'id' => 'building_name',
            'maxlength' => 150,
        ];

        $task_status = [
            'id' => 'task_status',
            'class' => 'form-control',
        ];

        $task_priority = [
            'id' => 'task_priority',
            'class' => 'form-control',
        ];

        $relatedTask = [
            'type' => 'text',
            'class' => 'form-control',
            'id' => 'related_task',
            'maxlength' => 50,
        ];

        $resNumber = [
            'type' => 'text',
            'class' => 'form-control',
            'id' => 'res_number',
            'maxlength' => 50,
        ];

        $task_type = [
            'id' => 'task_type',
             'class' => 'form-control',
        ];

        $description = [
            'type' => 'textarea',
            'class' => 'form-control',
            'rows' => '4',
            'id' => 'description',
        ];

        $comments = [
            'type' => 'textarea',
            'class' => 'form-control hidden-print',
            'rows' => '1',
            'id' => 'comments',
            'placeholder' => 'Write your comment...'
        ];

        $team = [
            'id' => 'team_id',
            'class' => 'form-control'
        ];
        $followingTeam = [
            'id' => 'following_team_id',
            'class' => 'form-control'
        ];

        $responsible_id = [
            'id' => 'responsible_id',
            'class' => 'ginosik-selectize form-control'
        ];
        $verifier_id = [
            'id' => 'verifier_id',
            'class' => 'ginosik-selectize form-control'
        ];
        $helper_ids = [
            'id' => 'helper_ids',
            'class' => 'ginosik-selectize form-control',
            'multiple' => 'multiple'
        ];
        $follower_ids = [
            'id' => 'follower_ids',
            'class' => 'ginosik-selectize form-control',
            'multiple' => 'multiple'
        ];
        $tags = [
            'id' => 'tags',
            'class' => 'form-control',
            'multiple' => 'multiple',
        ];

        if (empty($actionsSet[TaskService::ACTION_CHANGE_DETAILS])) {
            $title['disabled'] = true;
            $description['disabled'] = true;
            $task_priority['disabled'] = true;
            $task_type['disabled'] = true;
            $team['disabled'] = true;
            $followingTeam['disabled'] = true;
            $property_name['disabled'] = true;
            $building_name['disabled'] = true;
            $relatedTask['disabled'] = true;
            $resNumber['disabled'] = true;
            $permissionToEnter['disabled'] = true;
            $dateStart['disabled'] = true;
            $dateEnd['disabled'] = true;
        }

        if (empty($actionsSet[TaskService::ACTION_MANAGE_STAFF])) {
            $responsible_id['disabled'] = true;
            $verifier_id['disabled'] = true;
            $follower_ids['disabled'] = true;
            $helper_ids['disabled'] = true;
        }

        if (empty($actionsSet[TaskService::ACTION_MANAGE_STAFF])) {
            $tags['disabled'] = true;
        }

        if (empty($actionsSet[TaskService::ACTION_CHANGE_STATUS])) {
            $task_status['disabled'] = true;
        }

        if (empty($actionsSet[TaskService::ACTION_COMMENT])) {
            $comments['disabled'] = true;
        }

        $userOptions = [];
        foreach ($options['users'] as $index => $user) {
            $userOptions[$user['id']] = $user['name'];
        }

        $this->add([
            'name' => 'title',
            'options' => [
                'label' => '',
            ],
            'attributes' => $title
        ]);

        $this->add([
            'name' => 'creator_id',
            'type' => 'select',
            'attributes' => [
                'id' => 'creator_id',
                'class' => 'ginosik-selectize form-control',
                'disabled' => 'disabled'
            ],
        ]);

        $this->add([
            'name' => 'responsible_id',
            'type' => 'select',
            'options'    => [
                'value_options' => $userOptions
            ],
            'attributes' => $responsible_id,
        ]);

        $this->add([
            'name' => 'verifier_id',
            'type' => 'select',
            'options'    => [
                'value_options' => $userOptions
            ],
            'attributes' => $verifier_id
        ]);

        $this->add([
            'name' => 'helper_ids',
            'type' => 'select',
            'options'    => [
                'value_options' => $userOptions
            ],
            'attributes' => $helper_ids
        ]);

        $this->add([
            'name' => 'follower_ids',
            'type' => 'select',
            'options'    => [
                'value_options' => $userOptions
            ],
            'attributes' => $follower_ids
        ]);

        $this->add([
            'name' => 'start_date',
            'type' => 'text',
            'attributes' => $dateStart
        ]);

        $this->add([
            'name' => 'end_date',
            'type' => 'text',
            'attributes' => $dateEnd
        ]);

        $this->add([
        		'name' => 'creation_date',
        		'options' => [
        				'label' => '',
        		],
        		'attributes' => [
        				'type' => 'hidden',
        				'class' => 'form-control',
        				'id' => 'creation_date'
        		],
        ]);

        $this->add([
        		'name' => 'done_date',
        		'options' => [
        				'label' => '',
        		],
        		'attributes' => [
        				'type' => 'text',
        				'class' => 'form-control',
        				'id' => 'done_date',
                    'disabled' => true
        		],
        ]);

        $this->add([
            'name' => 'property_name',
            'options' => [
                'label' => '',
            ],
            'attributes' => $property_name
        ]);

        $this->add([
            'name' => 'property_id',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'property_id',
            ],
        ]);

        $this->add([
            'name' => 'building_name',
            'options' => [
                'label' => '',
            ],
            'attributes' => $building_name
        ]);

        $this->add([
            'name' => 'building_id',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'building_id',
            ],
        ]);

        $this->add([
            'name' => 'tags',
            'type' => 'text',
            'attributes' => $tags
        ]);

        $taskStatusList = [];
        foreach ($this->getStatuses($taskMain, $actionsSet) as $key=>$row){
            $taskStatusList[$key] = $row;
        }

        if (!empty($taskMain) && empty($taskStatusList[$taskMain->getTask_status()]) && $taskMain->getTask_status() != TaskService::STATUS_NEW)
        {
            $taskStatusList[$taskMain->getTask_status()] = TaskService::getTaskStatus()[$taskMain->getTask_status()];
        }
        $this->add([
            'name' => 'task_status',
            'options' => [
                'label' => '',
                'value_options' => $taskStatusList
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => $task_status,
        ]);

        $task_priority_list = [];
        foreach (TaskService::getTaskPriority() as $key=>$row){
            $task_priority_list[$key] = $row;
        }
        $this->add([
            'name' => 'task_priority',
            'options' => [
                'label' => '',
                'value_options' => $task_priority_list
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => $task_priority,
        ]);

        if ($taskMain && $taskMain->getTeamId() > 0 && empty($options['teams'][$taskMain->getTeamId()])) {
            $options['teams'][$taskMain->getTeamId()] = $options['all_teams'][$taskMain->getTeamId()];
        }
        $this->add([
            'name' => 'team_id',
            'options' => [
                'label' => '',
                'value_options' => $options['teams']
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => $team,
        ]);
        if ($taskMain && $taskMain->getFollowingTeamId() > 0 && empty($options['teams'][$taskMain->getFollowingTeamId()])) {
            $options['teams'][$taskMain->getFollowingTeamId()] = $options['all_teams'][$taskMain->getFollowingTeamId()];
        }

        $this->add([
            'name' => 'following_team_id',
            'options' => [
                'label' => '',
                'value_options' => $options['teams']
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => $followingTeam,
        ]);

        $this->add([
            'name' => 'related_task',
            'options' => [
                'label' => '',
            ],
            'attributes' => $relatedTask
        ]);

        $this->add([
            'name' => 'res_number',
            'options' => [
                'label' => '',
            ],
            'attributes' => $resNumber
        ]);

        $this->add([
            'name' => 'res_id',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'res_id'
            ]
        ]);

        $this->add([
            'name' => 'task_type',
            'options' => [
                'label' => '',
                'value_options' => $options['task_types']
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => $task_type
        ]);

        $this->add([
            'name' => 'description',
            'options' => [
                'label' => '',
            ],
            'attributes' => $description
        ]);

        $this->add([
            'name' => 'comments',
            'options' => [
                'label' => '',
            ],
            'attributes' => $comments
        ]);

        $buttonname = 'Add Task';
        if (is_object($data)) {
            $buttonname = 'Save Changes';
        }

        $this->add([
            'name' => 'save_button',
            'options' => [
                'label' => $buttonname,
            ],
            'attributes' => [
                'type' => 'button',
                'class' => 'btn btn-primary col-sm-2 col-xs-12 margin-left-10 pull-right',
                'data-loading-text' => 'Saving...',
                'id' => 'save_button',
            ],
        ]);

        $this->add([
            'name' => 'download_button',
            'options' => [
                'label' => $buttonname,
            ],
            'attributes' => [
                'type' => 'button',
                'class' => 'hide self-submitter',
                'id' => 'download_button',
            ],
        ]);

        $this->add([
            'name' => 'edit_id',
            'attributes' => [
                'type' => 'hidden',
                'id' => 'edit_id',
            ],
        ]);

        $this->add([
            'name' => 'subtask_id',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'subtask_description',
            'attributes' => [
                'type' => 'text',
            ],
        ]);

        $this->add([
            'name' => 'subtask_status',
            'attributes' => [
                'type' => 'checkbox',
            ],
        ]);

        $this->add([
            'name'       => 'attachment_names',
            'options'    => [
                'label' => '',
            ],
            'type'       => 'Zend\Form\Element\Hidden',
            'attributes' => [
                'id' => 'attachment_names'
            ]
        ]);

        $objectData = new \ArrayObject();
        if(is_object($taskMain)) {
            $objectData['edit_id'] = $taskMain->getId();
            $objectData['creator_id'] = $taskMain->getCreatorId();
            $objectData['responsible_id'] = $taskMain->getResponsibleId();
            $objectData['verifier_id'] = $taskMain->getVerifierId();
            $objectData['creation_date'] = ($taskMain->getCreation_date()) ? date(Constants::GLOBAL_DATE_FORMAT . ' H:i', strtotime($taskMain->getCreation_date())) : '';
            $objectData['done_date'] = ($taskMain->getDone_date()) ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($taskMain->getDone_date())) : '';
            $objectData['start_date'] = ($taskMain->getStartDate()) ? date(Constants::GLOBAL_DATE_FORMAT . ' H:i', strtotime($taskMain->getStartDate())) : '';
            $objectData['end_date'] = ($taskMain->getEndDate()) ? date(Constants::GLOBAL_DATE_FORMAT . ' H:i', strtotime($taskMain->getEndDate())) : '';
            $objectData['property_name'] = $taskMain->getProperty_name() . ($taskMain->getCity() ? ' - ' . $taskMain->getCity() : '') . ($taskMain->getUnit_number() ? ' - ' . $taskMain->getUnit_number() : '');
            $objectData['property_id'] = $taskMain->getProperty_id();
            $objectData['building_name'] = $taskMain->getBuildingName();
            $objectData['building_id'] = $taskMain->getBuildingId();
            $objectData['task_status'] = $taskMain->getTask_status();
            $objectData['task_priority'] = $taskMain->getPriority();
            $objectData['description'] = $taskMain->getDescription();
            $objectData['related_task'] = $taskMain->getRelatedTask();
            $objectData['res_id'] = $taskMain->getResId();
            $objectData['res_number'] = $taskMain->getResNumber();
            $objectData['title'] = $taskMain->getTitle();
            $objectData['task_type'] = $taskMain->getTaskTypeId();
            $objectData['team_id'] = $taskMain->getTeamId();
            $objectData['following_team_id'] = $taskMain->getFollowingTeamId();

            $this->bind($objectData);
        } else {
            $objectData['start_date'] = date(Constants::GLOBAL_DATE_FORMAT . ' H:i');
            $objectData['end_date'] = date(Constants::GLOBAL_DATE_FORMAT . ' H:i', time() + 7200);
            if (!empty($autoFilledData) && count($autoFilledData)) {
                foreach ($autoFilledData as $field => $value) {
                    if ($value) {
                        $objectData[$field] = $value;
                    }
                }
            }
            $this->bind($objectData);
        }
    }

    /**
     * @param $data
     * @param array $actionsSet
     * @return array
     */
    private function getStatuses($data, $actionsSet){
        $statuses = TaskService::getTaskStatus();
        if (!$data) {
            $statuses = [TaskService::STATUS_NEW => 'New'];
        } else if (!empty($actionsSet[TaskService::ACTION_CHANGE_STATUS])) {
            $statuses = TaskService::getTaskStatus();
            if ($actionsSet[TaskService::ACTION_CHANGE_STATUS] == 2) {
                unset($statuses[TaskService::STATUS_NEW]);
                unset($statuses[TaskService::STATUS_VERIFIED]);
                unset($statuses[TaskService::STATUS_CANCEL]);
            }
        }

        return $statuses;
    }
}
