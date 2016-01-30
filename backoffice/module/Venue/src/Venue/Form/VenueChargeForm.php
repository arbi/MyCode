<?php

namespace Venue\Form;

use Library\Form\FormBase;
use DDD\Service\Venue\Charges as VenueCharges;


class VenueChargeForm extends FormBase
{
    /**
     * VenueChargeForm constructor.
     * @param string $name
     * @param array $data
     */
    public function __construct($data = [], $chargeData = [])
    {
        parent::__construct();

        $this->setName('venue-charge')
            ->setAttribute('method', 'post')
            ->setAttribute('class', 'form-horizontal');

        $this->add([
            'name' => 'id',
            'type' => 'Zend\Form\Element\Number',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);

        $this->add([
            'name' => 'venue_id',
            'type' => 'Zend\Form\Element\Number',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);

        $this->add([
            'name' => 'status_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'status_id',
                'class' => 'form-control',
                'data-placeholder'  => 'Select Status',
                'data-id'           => (empty($chargeData)) ? '' : $chargeData->getStatus()
            ],
            'options' => [
                'label' => 'Payment',
                'value_options' => VenueCharges::getChargeStatuses()
            ]
        ]);

        $this->add([
            'name' => 'order_status_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'order_status_id',
                'class' => 'form-control',
                'data-placeholder'  => 'Select Order Status',
                'data-id'           => (empty($chargeData)) ? '' : $chargeData->getOrderStatus()
            ],
            'options' => [
                'label'         => 'Order',
                'value_options' => VenueCharges::getChargeOrderStatuses()
            ]
        ]);

        $this->add([
            'name' => 'description',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'id'        => 'description',
                'class'     => 'form-control',
                'rows'      => 8,
                'maxlength' => 5000,
                'value' => (empty($chargeData)) ? '' : $chargeData->getDescription()
            ],
            'options' => [
                'label' => 'Description',
            ]
        ]);

        $this->add([
            'name' => 'amount',
            'type' => 'Zend\Form\Element\Number',
            'attributes' => [
                'id'    => 'amount',
                'class' => 'form-control',
                'step'  => '0.01',
                'value' => (empty($chargeData)) ? '' : $chargeData->getAmount()
            ],
            'options' => [
                'label' => 'Amount'
            ],
        ]);

        $this->add([
            'name' => 'is_archived',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => [
                'id'    => 'is_archived',
                'value' => (empty($chargeData)) ? '' : $chargeData->getIsArchived(),
                'class' => 'hidden'
            ],
            'options' => [
                'label' => 'Archived'
            ],
        ]);

        $usersList = [];
        if (isset($data['users_list']) && !empty($data['users_list'])) {
            foreach ($data['users_list'] as $user) {
                $usersList[$user['id']] = $user['firstname'] . ' ' . $user['lastname'];
            }
        }

        $this->add([
            'name' => 'charged_user_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'                => 'charged_user_id',
                'class'             => 'form-control',
                'data-placeholder'  => 'Select a User',
                'data-id'           => (empty($chargeData)) ? '' : $chargeData->getChargedUserId()
            ],
            'options' => [
                'label' => 'Charged User',
                'disable_inarray_validator' => true,
                'value_options'     => $usersList
            ]
        ]);
    }
}