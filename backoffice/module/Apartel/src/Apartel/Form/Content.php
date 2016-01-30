<?php

namespace Apartel\Form;

use DDD\Service\Apartel\General as GeneralService;

use Library\Form\FormBase;

class Content extends FormBase
{
    public function __construct($apartelId, $name = 'apartel_content', $data = [])
    {
        parent::__construct($name);

        if (count($data)) {
            $this->setBuildingOptions($data['buildingOptions']);
        }

        $this->setAttribute('method', 'POST');
        $this->setAttribute('action', 'general/save');
        $this->setAttribute('class', 'form-horizontal');

        // ID
        $this->add([
            'name' => 'id',
            'attributes' => [
                'type'  => 'hidden',
                'value' => 0,
                'id'=>'aId'
            ],
        ]);

        $this->add([
            'name' => 'content_textline',
            'options' => [
                'label' => 'Content',
                'required' => true,
            ],
            'attributes' => [
                'type' => 'textarea',
                'id' => 'content_textline',
                'class' => 'form-control tinymce',
            ],
        ]);

        $this->add([
            'name' => 'moto_textline',
            'options' => [
                'label' => 'Moto',
                'required' => true
            ],
            'attributes' => [
                'type' => 'textarea',
                'id' => 'moto_textline',
                'class' => 'form-control tinymce',
                'rows'  => 2
            ],
        ]);

        $this->add([
            'name' => 'meta_description_textline',
            'options' => [
                'label' => 'Meta Description',
                'required' => true
            ],
            'attributes' => [
                'type' => 'textarea',
                'id' => 'meta_description_textline',
                'class' => 'form-control tinymce',
            ],
        ]);

        $this->add([
            'name' => 'bg_image',
            'options' => [
                'label' => 'Background Image',
            ],
            'attributes' => [
                'type'   => 'File',
                'id'     => 'bg_image',
                'class'  => 'form-control definitely-hidden',
                'accept' => 'image/*',
                'data-max-size' => '10240'
            ],
        ]);

        $this->add([
            'name' => 'bg_image_file_name',
            'type' => 'Hidden',
            'attributes' => [
                'id' => 'bg_image_file_name'
            ]
        ]);

        $this->add([
            'name' => 'save_button',
            'type' => 'Button',
            'options' => [
                'label' => 'Save Changes'
            ],
            'attributes' => [
                'data-loading-text' => 'Saving...',
                'id' => 'save_button',
                'value' => 'Save Changes',
                'class' => 'btn btn-primary col-sm-2 col-xs-12 pull-right',
            ],
        ]);
    }
}
