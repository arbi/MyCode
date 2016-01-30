<?php
namespace Finance\Form;

use Zend\Form\Form;

final class SearchItemForm extends Form
{

	public function __construct($name, $users) {
        // set the form's name
        parent::__construct($name);

        // set the method
        $this->setAttribute('method', 'post');

        // Suppliers
        $this->add(
            array(
                'name' => 'item-search-supplier',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'class' => 'form-control item-search-supplier',
                    'data-placeholder' => 'Supplier'
                ),
            )
        );

        // Category
        $this->add(
            array(
                'name' => 'item-search-category',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'class' => 'form-control item-search-category',
                    'data-placeholder' => 'Category'
                ),
            )
        );
        //creator
        $this->add([
            'name' => 'creator_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'value_options' => $users,
            ],
            'attributes' => [
                'class' => 'form-control creator_id',
                'data-placeholder' => 'Created by',
            ],
        ]);

        // Period
        $this->add([
            'name' => 'item-search-period',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Period',
                'class' => 'form-control item-search-period drp',
            ],
        ]);

        // Cost Centers
        $this->add(
            array(
                'name' => 'item-search-cost-center',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'class' => 'form-control item-search-cost-center',
                    'data-placeholder' => 'Cost Center'
                ),
            )
        );

        // Creation Date
        $this->add([
            'name' => 'item-search-creation-date',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Creation Date',
                'class' => 'form-control item-search-creation-date',
            ],
        ]);

        // Reference
        $this->add([
            'name' => 'item-search-reference',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Reference',
                'class' => 'form-control item-search-reference',
            ],
        ]);

        // Amount
        $this->add([
            'name' => 'item-search-amount',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'placeholder' => 'Amount',
                'class' => 'form-control item-search-amount',
            ],
        ]);


    }

}
