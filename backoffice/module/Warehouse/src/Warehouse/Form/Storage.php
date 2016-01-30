<?php

namespace Warehouse\Form;

use Library\Form\FormBase;
use DDD\Service\Warehouse\Category as CategoryService;

class Storage extends FormBase {

    /**
     * @param int|null|string $categoryId
     * @param array $cities
     */

    public function __construct($categoryId, $cities) {
        parent::__construct('storage-form');

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
            'name' => 'city',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $this->cityList($cities),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'city',
            ),
        ));

        $this->add(array(
            'name' => 'address',
            'attributes' => array(
                'type'  => 'text',
                'class' => 'form-control',
                'id'    => 'address',
            ),
        ));

        $this->add(array(
            'name' => 'save',
            'attributes' => array(
                'type' => 'submit',
                'class' => 'btn btn-primary state save-bank-account col-sm-2 col-xs-12 margin-left-10 pull-right',
                'value' => ($categoryId > 0) ? 'Save Changes' : 'Add New Storage',
            ),
        ));
    }

    private function cityList($cities)
    {
        $cityList = [0 => '-- Choose City --'];
        foreach ($cities as $city) {
            $cityList[$city['id']] = $city['name'];
        }
        return $cityList;
    }
}
