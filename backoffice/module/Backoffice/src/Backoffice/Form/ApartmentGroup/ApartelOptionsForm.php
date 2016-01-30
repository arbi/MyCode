<?php
namespace Backoffice\Form\ApartmentGroup;

use Library\Form\FormBase;

/**
 * Class ApartelOptionsForm
 * @package Backoffice\Form\ApartmentGroup
 */
class ApartelOptionsForm extends FormBase
{
    /**
     * @param int|null|string $options
     */
    public function __construct($options)
    {
        parent::__construct('form-apartment-group-apartel-options');

        $this->setAttributes([
            'action'  => '',
            'method'  => 'post',
            'class'   => 'form-horizontal',
            'id'      => 'form-apartment-group-apartel-options',
            'enctype' => 'multipart/form-data'
        ]);
        $this->setName('form-apartment-group-apartel-options');
    }

}
