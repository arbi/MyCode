<?php

namespace Apartel\Form;

use Library\Form\FormBase;
use DDD\Service\Apartment\Rate as RateService;

class Rate extends FormBase
{

	public function __construct($data, $name = 'apartel-rate')
    {
		parent::__construct ($name);

		$this->setName($name);
		$this->setAttribute('method', 'POST');
        $this->setAttribute('class', 'form-horizontal');

		$isParent  = $data['is_parent'];
		$nameValue = $isParent ? 'Standard Rate' : '';

		// Rate Id
		$this->add([
			'name' => 'rate_id',
			'attributes' => [
				'type'  => 'hidden',
				'value' => 0
			],
		]);

        // Type Id
        $this->add([
            'name' => 'type_id',
            'attributes' => [
                'type'  => 'hidden',
                'value' => $data['type_id']
            ],
        ]);

		// Name
		$this->add([
			'name' => 'rate_name',
			'options' => [
				'label' => 'Name'
			],
			'attributes' => [
                'type'  => 'text',
                'id'    => 'rate_name',
                'value' => $nameValue,
                'class' => 'form-control'
			]
		]);

		// Active
		$this->add([
			'name' => 'active',
			'type' => 'Zend\Form\Element\Select',
			'options' => [
				'label' => 'Active',
				'value_options' => $this->getActiveOptions($isParent)
			],
			'attributes' =>[
                'id'    => 'active',
                'class' => 'form-control'
			]
		]);

        // type
        $this->add([
            'name' => 'type',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label'         => 'Type',
                'value_options' => $this->getTypeOptions($isParent)
            ],
            'attributes' =>[
                'disabled' => $isParent,
                'id'       => 'type',
                'class'    => 'form-control'
            ]
        ]);

		// Capacity
		$this->add([
			'name' => 'capacity',
			'type' => 'Zend\Form\Element\Number',
			'options' => [
				'label' => 'Capacity'
			],
			'attributes' => [
                'type'              => 'number',
                'id'                => 'capacity',
                'data-max-capacity' => $data['apartment_max_pax'],
                'class'             => 'form-control'
			]
		]);

		// Week Day Price
		$this->add([
			'name' => 'week_price',
			'type' => 'Zend\Form\Element\Text',
			'options' => [
				'label' => 'Week Day Price',
                'required' => true
			],
			'attributes' => [
				'id' => 'week_price',
                'class' => 'form-control current-rate-price'
			]
		]);

		// Weekend Price
		$this->add([
			'name' => 'weekend_price',
			'type' => 'Zend\Form\Element\Text',
			'options' => [
				'label' => 'Weekend Price',
                'required' => true
			],
			'attributes' =>[
				'id' => 'weekend_price',
                'class' => 'form-control current-rate-price'
			]
		]);

		// Minimum Stay
		$this->add([
			'name' => 'min_stay',
			'type' => 'Zend\Form\Element\Number',
			'options' => [
				'label' => 'MIN',
                'required' => true
			],
			'attributes' => [
				'type' => 'number',
				'id' => 'min_stay',
                'class' => 'form-control'
			]
		]);

		// Maximum Stay
		$this->add([
			'name' => 'max_stay',
			'type' => 'Zend\Form\Element\Number',
			'options' => [
				'label' => 'MAX',
                'required' => true
			],
			'attributes' => [
				'type' => 'number',
				'id' => 'max_stay',
                'class' => 'form-control'
			]
		]);

		// Release window start
		$this->add([
			'name' => 'release_window_start',
			'type' => 'Zend\Form\Element\Number',
			'options' => [
                'label'    => 'Start',
                'required' => true
			],
			'attributes' => [
				'placeholder' => '',
                'type'  => 'text',
                'id'    => 'release_window_start',
                'class' => 'form-control'
			]
		]);

		// Release window end
		$this->add([
			'name' => 'release_window_end',
			'type' => 'Zend\Form\Element\Number',
			'options' => [
                'label'    => 'End',
                'required' => true
			],
			'attributes' => [
                'placeholder' => '',
                'type'        => 'text',
                'id'          => 'release_window_end',
                'class'       => 'form-control'
			]
		]);

		// Refundable or Non Refundable
		$this->add([
            'name' => 'is_refundable',
            'options' => [
                'label' => 'This rate is',
                'value_options' => [
                    '2' => 'Non Refundable ',
                    '1' => 'Refundable'
                ]
            ],
            'type' => 'Zend\Form\Element\Radio',
            'attributes' => [
                'id'    => 'is_refundable',
                'value' => 1
            ]
	    ]);

		// Refundable before hours
		for($days = 1; $days <= 7; $days ++) {
			$hourOptions [$days * 24] = $days . ' days';
		}
		$hourOptions [14 * 24] = '14 days';
		$hourOptions [30 * 24] = '30 days';

		$this->add([
            'name' => 'refundable_before_hours',
            'options' => [
                'label' => 'Before',
                'value_options' => $hourOptions
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'refundable_before_hours',
                'class' => 'form-control'
            ]
		]);

		// Refundable type
		$this->add([
            'name' => 'penalty_type',
            'options' => [
                'label' => false,
                'value_options' => [
                    '1' => 'Percent Penalty',
                    '2' => 'Fixed Penalty',
                    '3' => 'Nights Penalty'
                ]
            ],
            'type' => 'Zend\Form\Element\Radio',
            'attributes' => [
                'id' => 'penalty_type',
                'value' => 1,
            ]
		]);

		// Refund percent
		$this->add([
            'name' => 'penalty_percent',
            'options' => [
                'label' => false,
                'appendText' => '%'
            ],
            'attributes' => [
                'placeholder' => '',
                'type' => 'text',
                'id' => 'penalty_percent',
                'class' => 'form-control'
            ]
		]);

		// Refund amount
		$this->add([
            'name' => 'penalty_fixed_amount',
            'options' => [
                'label'      => false,
                'appendText' => $data['currency']
            ],
            'attributes' =>[
                'placeholder' => '',
                'type'        => 'text',
                'id'          => 'penalty_fixed_amount',
                'class'       => 'form-control'
            ]
		]);

		// Refund nights
		$nightOptions = [
			1 => '1 night',
			2 => '2 nights',
			3 => '3 nights',
			4 => '4 nights',
			5 => '5 nights',
			6 => '6 nights'
		];

		$this->add([
            'name' => 'penalty_nights',
            'options' => [
                'label' => false,
                'value_options' => $nightOptions
            ],
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id' => 'penalty_nights',
                'class' => 'form-control'
            ]
		]);

		$this->add([
            'name' => 'save_button',
            'options' =>[
                    'label' => false
            ],
            'attributes' => [
                'type'              => 'submit',
                'data-loading-text' => 'Saving...',
                'id'                => 'save_button',
                'value'             => 'Save',
                'class'             => 'btn btn-primary col-sm-2 col-xs-12 margin-left-10 pull-right'
            ]
		]);

		$this->add(
            [
                'name' => 'delete_button',
                'type' => 'Zend\Form\Element\Button',
                'options' => [
                    'label' => 'Delete Rate',
                ],
                'attributes' => [
                    'data-toggle' => 'modal',
                    'data-target' => '#delete-modal',
                    'class'       => 'btn btn-danger col-sm-2 col-xs-12 margin-left-10 pull-right'
                ],
            ],
            [
                'name' => 'delete_button',
                'priority' => 9
            ]
        );
	}

    /**
     * @param $isParent
     * @return array
     */
    private function getTypeOptions($isParent)
    {
        $rateList = RateService::getRateTypes();
        if (!$isParent) {
            unset($rateList[RateService::TYPE1]);
        }
        return [0 => '-- Choose Type --'] + $rateList;
    }

    private function getActiveOptions($isParent)
    {
        if ($isParent) {
            return [
                '1' => 'YES'
            ];
        }

        return [
            '0' => 'NO',
            '1' => 'YES',
        ];
    }
}
