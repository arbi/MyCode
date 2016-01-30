<?php
namespace Finance\Form;

use Zend\Form\Form;

/**
 *
 * @author developer
 *
 */
class ExpenseItemCategoriesForm extends Form
{
    public function __construct($name = 'category')
    {
        parent::__construct($name);

        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('id', 'category-form');

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
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'Submit',
                'class' => 'btn btn-primary pull-right col-xs-12 col-sm-2 margin-left-10'
            ),
            'options'    => array(
                'primary' => true,
            ),
        ));

    }
}
