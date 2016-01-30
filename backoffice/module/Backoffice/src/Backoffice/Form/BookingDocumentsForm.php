<?php
namespace Backoffice\Form;

use Library\Form\FormBase;
use Library\Constants\Objects;
use Library\Utility\Helper;

class BookingDocumentsForm extends FormBase
{

    public function __construct($options)
    {
        parent::__construct('booking-form');
        $this->setAttributes([
                'action'  =>'',
                'method'  =>'post',
                'class'   =>'form-horizontal',
                'id'      =>'document-form',
                'enctype' =>'multipart/form-data'
                ]);
        $this->setName('booking-form');

        $this->add([
            'name' => 'doc_description',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type'  => 'textarea',
                'class' => 'form-control tinymce',
                'rows'  => '12',
                'id'    => 'doc_description',
            ]
        ]);

       $this->add([
            'name' => 'uploaded_files',
            'options' => [
                'label' => '',
            ],
            'attributes' => [
                'type'          => 'file',
                'class'         => 'hidden-file-input uploaded_files',
                'data-max-size' => '52428800',
                'multiple'      => true
            ],
        ]);

        $buttonname = 'Add Document';

        $this->add([
            'name' => 'save_button',
            'type' => 'Zend\Form\Element\Button',
            'options' => [
                'label' => $buttonname,
            ],
            'attributes' => [
                'class'             => 'btn btn-primary pull-right margin-left-5',
                'data-loading-text' => 'Saving...',
                'id'                => 'save_button',
            ],
        ]);

        $this->add(
            [
                'name' => 'delete_data',
                'attributes' => [
                    'type' => 'hidden',
                    'id'   => 'delete_data',
                ],
            ]
        );
    }

}
