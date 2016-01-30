<?php

namespace Backoffice\Form;

use Library\Form\FormBase;
use Library\Constants\Objects;
use Library\Utility\Debug;

class Team extends FormBase {
	public function __construct($name, $data, $options, $global, $isDirector, $alreadyAttachedApartments = []) {
		parent::__construct($name);

		$this->setName($name);

		$name_attr = [
			'type'      => 'text',
			'class'     => 'form-control',
			'id'        => 'name',
			'maxlength' => 50,
		];

		$desc_attr = [
			'type'      => 'text',
			'class'     => 'form-control',
			'id'        => 'description',
			'maxlength' => 255,
		];

		$members_attr = [
			'id'       => 'members',
			'class'    => 'form-control',
			'multiple' => true,
		];

		$officersAttr = [
			'id'       => 'officers',
			'class'    => 'form-control',
			'multiple' => true,
		];

        $managers_attr = [
			'class' => 'form-control selectize',
			'id'    => 'managers',
            'multiple' => true,
		];

        $isDirectorAttr = [
			'class' => 'form-control',
			'id'    => 'director',
		];

		$company_department = [
			'id' 				 => 'usage_department',
			'use_hidden_element' => false,
			'checked_value' 	 => 1,
			'unchecked_value'    => 0,
		];

		$comment_notifiable = [
			'id' 				 => 'usage_notifiable',
			'use_hidden_element' => false,
			'checked_value' 	 => 1,
			'unchecked_value'    => 0,
		];

        $isFrontier = [
            'id'                 => 'usage_frontier',
            'use_hidden_element' => true,
            'checked_value'      => 1,
            'unchecked_value'    => 0
        ];

        $isSecurity = [
            'id'                 => 'usage_security',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0
        ];

        $usageHiring = [
            'id'                 => 'usage_hiring',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0
        ];

        $usageStorage = [
            'id'                 => 'usage_storage',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0
        ];

        $isTaskable = [
            'id'                 => 'usage_taskable',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0
        ];

        $frontierApartmentsAttr = [
            'id'       => 'frontier-apartments',
            'class'    => 'form-control',
            'multiple' => true,
        ];

        $frontierBuildingsAttr = [
            'id'       => 'frontier-buildings',
            'class'    => 'form-control selectize',
            'multiple' => true,
        ];

		if (!$global) {
            $name_attr['disabled']      = true;
            $desc_attr['disabled']      = true;
            $isDirectorAttr['disabled'] = true;
		}

        if (!$global && !$isDirector) {
            $managers_attr['disabled']          = true;
            $company_department['disabled']     = true;
            $isFrontier['disabled']             = true;
            $isSecurity['disabled']             = true;
            $isTaskable['disabled']             = true;
            $comment_notifiable['disabled']     = true;
            $frontierApartmentsAttr['disabled'] = true;
            $frontierBuildingsAttr['disabled']  = true;
        }

		$this->add(
            [
                'name'       => 'name',
                'attributes' => $name_attr,
		    ]
        );

		$this->add(
            [
                'name'       => 'description',
                'attributes' => $desc_attr,
		    ]
        );

		$memberList    = [0 => '-- Choose --'];
		$memberRawList = $options->get('ginosiksList');

		if ($memberRawList->count()) {
			foreach ($memberRawList as $member) {
				$memberList[$member['id']] =
                    $member['firstname'] . ' ' .
                    $member['lastname'];
			}
		}

        $this->add(array(
            'name'       => 'director',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'value_options' => $memberList
            ],
            'attributes' => $isDirectorAttr,
        ));

        unset($memberList[0]);

		$this->add(array(
			'name'       => 'members',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => $memberList
			],
			'attributes' => $members_attr,
		));

		$this->add(array(
			'name'       => 'officers',
			'type'       => 'Zend\Form\Element\Select',
			'options'    => [
				'value_options' => $memberList
			],
			'attributes' => $officersAttr,
		));

        $this->add(array(
            'name'       => 'managers',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'value_options' => $memberList
            ],
            'attributes' => $managers_attr,
        ));

        if (is_object($data)) {
			if ($data->get('general')->getIsDepartment()) {
				$company_department['checked'] = 'checked';
			}

            if ($data->get('general')->getUsageSecurity()) {
                $isSecurity['checked'] = 'checked';
            }

            if ($data->get('general')->getUsageHiring()) {
                $usageHiring['checked'] = 'checked';
            }

            if ($data->get('general')->getUsageStorage()) {
                $usageStorage['checked'] = 'checked';
            }

            if ($data->get('general')->isCommentNotifiable()) {
                $comment_notifiable['checked'] = 'checked';
            }

            if ($data->get('general')->isFrontier()) {
                $isFrontier['checked'] = 'checked';
            }

            if ($data->get('general')->isTaskable()) {
                $isTaskable['checked'] = 'checked';
            }
		}

		$this->add(
			[
				'name' 		 => 'usage_department',
				'type' 		 => 'Zend\Form\Element\Checkbox',
				'attributes' => $company_department
			]
		);

        $this->add(
            [
                'name'       => 'usage_security',
                'type'       => 'Zend\Form\Element\Checkbox',
                'attributes' => $isSecurity
            ]
        );

        $this->add(
            [
                'name'       => 'usage_hiring',
                'type'       => 'Zend\Form\Element\Checkbox',
                'attributes' => $usageHiring
            ]
        );

        $this->add(
            [
                'name'       => 'usage_storage',
                'type'       => 'Zend\Form\Element\Checkbox',
                'attributes' => $usageStorage
            ]
        );

        $this->add(
            [
                'name'       => 'usage_taskable',
                'type'       => 'Zend\Form\Element\Checkbox',
                'attributes' => $isTaskable
            ]
        );

		$this->add(
			[
				'name' 		 => 'usage_notifiable',
				'type' 		 => 'Zend\Form\Element\Checkbox',
				'attributes' => $comment_notifiable
			]
		);

		$this->add(
			[
				'name' 		 => 'usage_frontier',
				'type' 		 => 'Zend\Form\Element\Checkbox',
				'attributes' => $isFrontier
			]
		);

        foreach ($options->get('accommodationList') as $row) {
            $name = $row->getName();
            if (array_key_exists($row->getId(),$alreadyAttachedApartments)) {
                continue;
            }
            $accommodations[$row->getId()] = $name;
        }
        $this->add(array(
            'name'       => 'frontier_apartments',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'value_options' => $accommodations
            ],
            'attributes' => $frontierApartmentsAttr,
        ));

        $this->add(array(
            'name'       => 'frontier_buildings',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'value_options' => $options->get('apartmentGroups')
            ],
            'attributes' => $frontierBuildingsAttr,
        ));

        $this->add(
            [
                'name'       => 'extra_inspection',
                'type'       => 'Zend\Form\Element\Checkbox',
                'attributes' => [
                    'id'                 => 'extra_inspection',
                    'use_hidden_element' => false,
                    'checked_value'      => 1,
                    'unchecked_value'    => 0,
                ]
            ]
        );

        $this->add(
            [
                'name'    => 'timezone',
                'options' => [
                    'label'         => '',
                    'value_options' => ['' => '-- Choose Timezone --'] + Objects::getTimezoneOptions(),
                ],
                'type'       => 'Zend\Form\Element\Select',
                'attributes' => [
                    'id'    => 'timezone',
                    'class' => 'form-control'
                ],
            ]
        );

		$buttons_save = 'Create Team';

		if (is_object($data)) {
			$buttons_save = 'Save';
		}

		$this->add(
            [
                'name' => 'save_button',
                'options' => [
                    'label' => $buttons_save,
                ],
                'attributes' => [
                    'type'              => 'button',
                    'class'             =>
                        'btn btn-primary state col-sm-2 ' .
                        'col-xs-12 margin-left-10 pull-right',
                    'data-loading-text' => 'Saving...',
                    'id'                => 'save_button',
                    'value'             => 'Save',
                ],
		    ]
        );

		if ($global) {
			$this->add(
                [
                    'name'       => 'group_delete_button',
                    'options'    => [
                        'label' => 'Delete Group',
                    ],
                    'attributes' => [
                        'type'              => 'button',
                        'class'             =>
                            'btn btn-danger btn-large ' .
                            'pull-right helper-margin-left-04em state',
                        'data-loading-text' => 'Deleteing...',
                        'id'                => 'group_delete_button',
                        'value'             => 'Delete Group',
                    ],
                ]
            );
		}

		if (is_object($data)) {
            $objectData   = new \ArrayObject();
            $teamManagers = $data->get('teamManagers');
            $teamOfficers = $data->get('teamOfficers');
            $teamMembers  = $data->get('teamMembers');
            $allUsers     = $data->get('allUsers');

            $members = [];
            $memberListNew = $memberList;
            if (!empty($teamMembers)) {
                foreach ($teamMembers as $row){
                    $members[] = $row->getUserId();

                    if (!isset($memberListNew[$row->getUserId()])) {
                        $memberListNew[$row->getUserId()] = $allUsers[$row->getUserId()];
                    }
                }
            }
            if ($memberListNew != $memberList) {
                $this->get('members')->setOptions(['value_options' => $memberListNew]);
            }

            $managers = [];
            $managerListNew = $memberList;
            if (!empty($teamManagers)) {
                foreach ($teamManagers as $row){
                    $managers[] = $row->getUserId();
                    if (!isset($managerListNew[$row->getUserId()])) {
                        $managerListNew[$row->getUserId()] = $allUsers[$row->getUserId()];
                    }
                }
            }
            if ($managerListNew != $memberList) {
                $this->get('managers')->setOptions(['value_options' => $managerListNew]);
            }

            $officers = [];
            $officerListNew = $memberList;
            if (!empty($teamOfficers)) {
                foreach ($teamOfficers as $row){
                    $officers[] = $row->getUserId();
                    if (!isset($officerListNew[$row->getUserId()])) {
                        $officerListNew[$row->getUserId()] = $allUsers[$row->getUserId()];
                    }
                }
            }
            if ($officerListNew != $memberList) {
                $this->get('officers')->setOptions(['value_options' => $officerListNew]);
            }

            $apartments = [];
            if (($data->get('frontierApartments'))) {
                foreach ($data->get('frontierApartments') as $row){
                    $apartments[] = $row->getApartmentId();
                }
            }

            $buildings = [];
            if (($data->get('frontierBuildings'))) {
                foreach ($data->get('frontierBuildings') as $row){
                    $buildings[] = $row->getBuildingId();
                }
            }

			$objectData['members']             = $members;
			$objectData['officers']            = $officers;
			$objectData['frontier_buildings']  = $buildings;
			$objectData['frontier_apartments'] = $apartments;
			$objectData['managers']            = $managers;

            if ($data->get('director')) {
                $objectData['director']        = $data->get('director')->getUserId();
            }

            $objectData['name']             = $data->get('general')->getName();
            $objectData['description']      = $data->get('general')->getDescription();
            $objectData['extra_inspection'] = $data->get('general')->getExtraInspection();
            $objectData['timezone']         = $data->get('general')->getTimezone();

			$this->bind($objectData);

		}
	}
}
