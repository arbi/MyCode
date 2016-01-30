<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PartnerForm
 *
 * @author developer
 */

namespace Backoffice\Form;

use Zend\Form\Form;
use DDD\Service\Partners as PartnerService;

class PartnerForm extends Form
{
    public function __construct($name = 'partner')
    {
        parent::__construct($name);

        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('id', 'partner');
        $this->setAttribute('data-loading-text', 'It creates ...');

        $this->add([
            'name'       => 'partner_name',
            'type'       => 'Zend\Form\Element\Text',
            'options'    => [
                'label'    => 'Partner Name',
                'required' => true
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'partner_name'
            ]
        ]);

        $this->add([
            'name'       => 'business_model',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'label'         => 'Business Model',
                'value_options' => PartnerService::getBusinessModels(),
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'business_model'
            ]
        ]);

        $this->add([
            'name'       => 'contact_name',
            'type'       => 'Zend\Form\Element\Text',
            'options'    => [
                'label'    => 'Contact Name',
                'required' => true
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'contact_name'
            ]
        ]);

        $this->add([
            'name'       => 'email',
            'type'       => 'Zend\Form\Element\Text',
            'options'    => [
                'label'    => 'Email',
                'required' => false
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'email'
            ]
        ]);

        $this->add([
            'name'       => 'mobile',
            'type'       => 'Zend\Form\Element\Text',
            'options'    => [
                'label'       => 'Mobile',
                'prependText' => '+',
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'mobile'
            ]
        ]);

        $this->add([
            'name'       => 'phone',
            'type'       => 'Zend\Form\Element\Text',
            'options'    => [
                'label'       => 'Phone',
                'prependText' => '+',
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'phone'
            ]
        ]);

        $this->add([
            'name'       => 'cubilis_id',
            'attributes' => [
                'id' => 'cubilis_id',
            ],
            'type'       => 'Zend\Form\Element\Text',
            'options'    => [
                'label' => 'Cubilis ID'
            ],
        ]);

        $this->add([
            'name'       => 'commission',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'label'         => 'Default (%)',
                'value_options' => $this->getSelectPercentValues(),
                'required'      => true
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'commission'
            ]
        ]);

        $this->add([
            'name'       => 'additional_tax_commission',
            'type'       => 'Zend\Form\Element\Checkbox',
            'options'    => [
                'use_hidden_element' => true,
                'checked_value'      => '1',
                'unchecked_value'    => '0'
            ],
            'attributes' => [
                'id' => 'additional_tax_commission'
            ],
        ]);

        $this->add([
            'name'       => 'is_ota',
            'type'       => 'Zend\Form\Element\Checkbox',
            'options'    => [
                'label'              => 'Schema Available',
                'use_hidden_element' => true,
                'checked_value'      => '1',
                'unchecked_value'    => '0'
            ],
            'attributes' => [
                'id' => 'is_ota'
            ]
        ]);

        $this->add([
            'name'       => 'apply_fuzzy_logic',
            'type'       => 'Zend\Form\Element\Checkbox',
            'options'    => [
                'label'              => 'Take Channel Price',
                'use_hidden_element' => true,
                'checked_value'      => '1',
                'unchecked_value'    => '0'
            ],
            'attributes' => [
                'id' => 'apply_fuzzy_logic'
            ]
        ]);

        $this->add([
            'name'       => 'is_deducted_commission',
            'type'       => 'Zend\Form\Element\Checkbox',
            'options'    => [
                'label'              => 'Deducted Commission',
                'use_hidden_element' => true,
                'checked_value'      => '1',
                'unchecked_value'    => '0'
            ],
            'attributes' => [
                'id' => 'is_deducted_commission'
            ]
        ]);

        $this->add([
            'name'       => 'show_partner',
            'type'       => 'Zend\Form\Element\Checkbox',
            'options'    => [
                'label'              => 'Display Name',
                'use_hidden_element' => true,
                'checked_value'      => '1',
                'unchecked_value'    => '0'
            ],
            'attributes' => [
                'id' => 'show_partner'
            ]
        ]);

        $this->add([
            'name'       => 'account_holder_name',
            'type'       => 'Zend\Form\Element\Text',
            'options'    => [
                'label' => 'Account Holder Name'
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'account_holder_name'
            ]
        ]);

        $this->add([
            'name'       => 'bank_bsr',
            'type'       => 'Zend\Form\Element\Text',
            'options'    => [
                'label' => 'BIC/SWIFT/Routing'
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'bank_bsr'
            ]
        ]);

        $this->add([
            'name'       => 'bank_account_num',
            'type'       => 'Zend\Form\Element\Text',
            'options'    => [
                'label' => 'Account Number (IBAN)'
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'bank_account_num'
            ]
        ]);

        $this->add([
            'name'       => 'discount_num',
            'type'       => 'Zend\Form\Element\Text',
            'options'    => [
                'label' => 'Discount (%)'
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'discount_num'
            ]
        ]);

        $this->add([
            'name'       => 'create_date',
            'type'       => 'Zend\Form\Element\Text',
            'attributes' => [
                'disabled' => true,
            ],
            'options'    => [
                'label' => 'Create Date',
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'create_date'
            ]
        ]);

        $this->add([
            'name'       => 'partner-number',
            'type'       => 'Zend\Form\Element\Text',
            'options'    => [
                'label' => 'GID',
            ],
            'attributes' => [
                'class'    => 'form-control',
                'id'       => 'partner-number',
                'readonly' => 'readonly'
            ]
        ]);

        $this->add([
            'name'       => 'notes',
            'type'       => 'Zend\Form\Element\Textarea',
            'attributes' => [
                'id'    => 'notes',
                'rows'  => '10',
                'class' => 'form-control'
            ],
            'options'    => [
                'label' => 'Notes'
            ],
        ]);

        $this->add([
            'name'       => 'open',
            'type'       => 'Zend\Form\Element\Button',
            'attributes' => [
                'id' => 'openPartner',
            ],
            'options'    => [
                'label' => 'Website as affiliate',
            ],
        ]);

        $this->add([
            'name'       => 'submit',
            'type'       => 'Zend\Form\Element\Button',
            'attributes' => [
                'id'                => 'savePartner',
                'data-loading-text' => 'Saving...',
                'class'             => 'col-sm-2 col-xs-12 btn btn-primary margin-left-10 pull-right administration-tab-btn commission-tab-btn',
                'value'             => 'Save Changes',
            ],
            'options'    => [
                'label' => 'Save Changes',
            ],
        ]);

        $this->add([
            'name'       => 'delete',
            'type'       => 'Zend\Form\Element\Button',
            'attributes' => [
                'id'          => 'deletePartner',
                'data-toggle' => 'modal',
                'href'        => '#deletePartner',
                'value'       => '#deletePartner',
            ],
            'options'    => [
                'label' => 'Delete Partner',
            ],
        ]);

    }

    private static function getSelectPercentValues()
    {
        for ($i = 0; $i <= 100; $i++) {
            $val[] = $i;
        }

        return $val;
    }
}
