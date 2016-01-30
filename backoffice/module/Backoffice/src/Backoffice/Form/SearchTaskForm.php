<?php
namespace Backoffice\Form;

use Zend\Form\Form;
use Library\Constants\Objects;
use DDD\Service\Task;

final class SearchTaskForm extends Form
{
	/**
	 * form resources needed, for example to fill select element options
	 * @var array
	 */

	public function __construct($name, $options = null) {
        // set the form's name
        parent::__construct($name);

        // set the method
        $this->setAttribute('method', 'post');

        // Title
        $this->add([
            'name' => 'title',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Title',
                'class' => 'form-control'
            ],
        ]);
        // Status
        $this->add(
        		array(
        				'name' => 'status',
        				'type' => 'Zend\Form\Element\Select',
        				'options' => array(
        						'label' => false,
        						'value_options' =>$this->SetStatusSelect(),
        				),
        				'attributes' => array(
                            'value' => Task::STATUS_ALL_OPEN,
                            'class' => 'form-control',
                            'id' => 'status'
        				),
        		)
        );
        // Priority
        $this->add(
        		array(
        				'name' => 'priority',
        				'type' => 'Zend\Form\Element\Select',
        				'options' => array(
        						'label' => false,
        						'value_options' =>$this->SetSelect('-- All Priorities --', Task::getTaskPriority()),
        				),
        				'attributes' => array(
                            'value' => 0,
                            'class' => 'form-control'
        				),
        		)
        );
        // Type
        $this->add(
        		array(
        				'name' => 'type',
        				'type' => 'Zend\Form\Element\Select',
        				'options' => array(
        						'label' => false,
        						'value_options' => $options['task_types'],
        				),
        				'attributes' => array(
                            'value' => 0,
                            'class' => 'form-control'
        				),
        		)
        );
        // Team
        $this->add([
            'name' => 'team_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => false,
                'value_options' => $options['teams'],
            ],
            'attributes' => [
                'value' => 0,
                'class' => 'form-control',
                'id'    => 'team_id'
            ],
        ]);

        $userOptions = [];
        foreach ($options['users'] as $userId => $user) {
            $userOptions[$userId] = $user['name'];
        }

        // Creator id
        $this->add([
            'name' => 'creator_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'data-placeholder' => 'Creator',
                'class' => 'ginosik-selectize form-control',
                'id' => 'creator_id'
            ],
            'options' => [
                'label' => false,
                'value_options' => $userOptions
            ],
        ]);

        // Responsible id
        $this->add([
            'name' => 'responsible_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'data-placeholder' => 'Responsible',
                'class' => 'ginosik-selectize form-control',
                'id' => 'responsible_id'
            ],
            'options' => [
                'label' => false,
                'value_options' => $userOptions
            ],
        ]);

        // Verifier Id
        $this->add([
            'name' => 'verifier_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'data-placeholder' => 'Verifier',
                'class' => 'ginosik-selectize form-control',
                'id' => 'verifier_id'
            ],
            'options' => [
                'label' => false,
                'value_options' => $userOptions
            ],
        ]);

        // Follower Id
        $this->add([
            'name' => 'follower_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'data-placeholder' => 'Follower',
                'class' => 'ginosik-selectize form-control',
                'id' => 'follower_id'
            ],
            'options' => [
                'label' => false,
                'value_options' => $userOptions
            ],
        ]);

        // Helper id
        $this->add([
            'name' => 'helper_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'data-placeholder' => 'Helper',
                'class' => 'ginosik-selectize form-control',
                'id' => 'helper_id'
            ],
            'options' => [
                'label' => false,
                'value_options' => $userOptions
            ],
        ]);

        // Tags
        $this->add([
            'name' => 'tags',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => [
                'data-placeholder' => 'Tags',
                'class' => 'form-control',
                'id' => 'tags',
            ],
            'options' => [
                'label' => false,
            ],
        ]);

        $this->add([
            'name' => 'property',
            'type' => 'Zend\Form\Element\Text',
            'options' => array(
                    'label' => false,
            ),
            'attributes' => array(
                'placeholder' => 'Apartment',
                'class' => 'form-control',
                'id'=>'property',
            ),
        ]);

        $this->add(array(
            'name' => 'property_id',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'id' => 'property_id'
            ),
            'options' => array(
                'label' => false,
            ),
        ));

        $this->add(array(
            'name' => 'property_type',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'id' => 'property_type',
                'value' => 0
            ),
            'options' => array(
                'label' => false
            ),
        ));

        $this->add(array(
            'name' => 'quick_task_id',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'id' => 'quick_task_id',
                'value' => 0,
            ),
            'options' => array(
                'label' => false,
            ),
        ));

        $this->add(
            array(
                'name' => 'building',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Building',
                    'class' => 'form-control',
                    'id'=>'building',
                ),
            )
        );

        $this->add(array(
            'name' => 'building_id',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'id' => 'building_id'
            ),
            'options' => array(
                'label' => false,
            ),
        ));

        $this->add(
            array(
                'name' => 'creation_date',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Creation Date',
                    'class' => 'form-control pull-right',
                    'id'=>'creation_date',
                ),
            )
        );

        $this->add(
            array(
                'name' => 'end_date',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Due Date',
                    'class' => 'form-control pull-right',
                    'id'=>'end_date',
                ),
            )
        );

        $this->add(
            array(
                'name' => 'done_date',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Done Date',
                    'class' => 'form-control pull-right',
                    'id'=>'done_date',
                ),
            )
        );

	}

    private function setStatusSelect()
    {
        return [0 => '-- All Statuses --', Task::STATUS_ALL_OPEN => '-- All Open --'] + Task::getTaskStatus();
    }

    private function SetSelect($txt, $array, $val = 0)
    {
        return [$val => $txt] + $array;
    }
}
