<?php
namespace Lock\Form;

use Zend\Form\Form;

/**
 * Class LockForm
 * @package Lock\Form
 * @author Hrayr Papikyan
 */
class LockForm extends Form
{
	/**
	 *
	 * @param string $name
     * @param array $allLockTypes
	 */
    public function __construct($allLockTypes, $name = 'locks'){
        parent::__construct($name);
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('id', 'locks-form');

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
                'value' => 'Submit',
                'class' => 'btn btn-primary pull-right col-xs-12 col-sm-2 margin-left-10',
                'data-loading-text' => 'Saving...'
            ),
            'options'    => array(
                'primary' => true,
            ),
        ));

    }
}
