<?php

namespace Warehouse\Form;

use Library\Form\FormBase;
use DDD\Service\Warehouse\Category as CategoryService;

class Category extends FormBase {

    /**
     * @param int|null|string $categoryId
     */
    public function __construct($categoryId) {
        parent::__construct('asset-category-form');

        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('method', 'post');

        $this->add(array(
            'name' => 'name',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'name',
            ),
        ));

        $this->add(array(
            'name'    => 'type',
            'type'    => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => [0 => '-- Choose Type --'] + CategoryService::$categoryTypes,
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id'    => 'type',
            ),
        ));

        $this->add(array(
            'name' => 'sku_names[]',
            'attributes' => array(
                'type'      => 'text',
                'class'     => 'form-control sku-name',
                'maxlength' => 45,
            ),
        ));

        $this->add(array(
            'name' => 'aliases[]',
            'attributes' => array(
                'type'      => 'text',
                'class'     => 'form-control alias-name',
                'maxlength' => 45,
            ),
        ));

        $this->add(array(
            'name' => 'save',
            'attributes' => array(
                'type' => 'submit',
                'class' => 'btn btn-primary state save-bank-account col-sm-2 col-xs-12 margin-left-10 pull-right',
                'value' => ($categoryId > 0) ? 'Save Changes' : 'Add New Category',
            ),
        ));
    }
}
