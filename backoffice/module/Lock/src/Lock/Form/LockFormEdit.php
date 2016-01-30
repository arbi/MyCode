<?php
namespace Lock\Form;

use Zend\Form\Form;
use DDD\Service\Lock\General as ServiceLockGeneral;

/**
 * Class LockFormEdit
 * @package Lock\Form
 * @author Hrayr Papikyan
 */
class LockFormEdit extends Form
{
    /**
     * @param array $allLockTypes
     * @param array $settingsWithNames
     * @param string $name
     */
    public function __construct($allLockTypes,$settingsWithNames, $name = 'locks'){
        // set the form's name
        parent::__construct($name);

        // set the method
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('id', 'locks-form');

        //General

        $this->add(array(
            'name' => 'id',
            'type' => 'Zend\Form\Element\Hidden',
        ));

        $this->add(array(
            'name' => 'name',
            'type' => 'Zend\Form\Element\Text',
            'attributes'    => array(
                'id' => 'name',
                'class' => 'form-control',
            ),
            'options'   => array(
                'label' => 'Name',
            ),
        ));


        $this->add(array(
            'name' => 'description',
            'type' => 'Zend\Form\Element\Textarea',
            'attributes' => array(
                'id' => 'description',
                'class' => 'form-control',
                'rows'  => '5'
            ),
            'options' => array(
                'label' => 'Description',
            ),
        ));

        $this->add(array(
            'name' => 'type_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $allLockTypes,
                'label'         => 'Type'
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id'    => 'type_id',
                'disabled' => 'disabled'
            ),
        ));

        $this->add(
            [
                'name'       => 'is_physical',
                'type'       => 'Zend\Form\Element\Checkbox',
                'attributes' => [
                    'id'                 => 'is_physical',
                    'use_hidden_element' => false,
                    'checked_value'      => 1,
                    'unchecked_value'    => 0,
                ]
            ]
        );

        //Submit button
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
            ),
            'attributes' => array(
                'value' => 'Submit',
                'class' => 'btn btn-primary pull-right col-xs-12 col-sm-2 margin-left-10',
            ),
            'options'    => array(
                'primary' => true,
            ),
        ));


        foreach ($settingsWithNames as $key=>$item) {
            $classIsRequired = '';
            if ($item['isRequired']) {
                $classIsRequired .= ' generated-setting-required';
            }
            if ($item['type'] == ServiceLockGeneral::SETTING_ITEM_TYPE_INPUT || $item['type'] == ServiceLockGeneral::SETTING_ITEM_TYPE_INPUT_MONTH) {
                $this->add(array(
                    'name' => 'setting_' . $key,
                    'type' => 'Zend\Form\Element\Text',
                    'attributes'    => array(
                        'id' => 'setting_' . $key,
                        'class' => 'form-control generated-setting' . $classIsRequired,
                    ),
                    'options'   => array(
                        'label' => $item['label'],
                    ),
                ));
            }
            elseif ($item['type'] == ServiceLockGeneral::SETTING_ITEM_TYPE_OFFICES_DROPDOWN) {
                $this->add(array(
                    'name' => 'setting_' . $key,
                    'type' => 'Zend\Form\Element\Select',
                    'options' => array(
                        'value_options' => $item['options'],
                        'label'         => $item['label']
                    ),
                    'attributes' => array(
                        'class' => 'form-control generated-setting' . $classIsRequired,
                        'id'    => 'setting_' . $key,
                    ),
                ));
            }
        }

    }
}
