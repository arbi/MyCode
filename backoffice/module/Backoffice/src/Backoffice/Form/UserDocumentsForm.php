<?php

namespace Backoffice\Form;

use Library\Form\FormBase;

class UserDocumentsForm extends FormBase
{
    public function __construct($name = NULL, $data)
    {
        parent::__construct($name);

        $this->setAttributes(array(
                'action' => '/user/ajax-add-document',
                'method' => 'post',
                'class' => 'form-horizontal',
                'id' => 'add-document-form'
                ));


        $this->add(array(
            'name' => 'document_id',
            'type' => 'Hidden',
            'attributes' => array(
                'id' => 'document_id'
            )
        ));

        $this->add(array(
            'name' => 'document_user_id',
            'type' => 'Hidden',
            'attributes' => array(
                'id' => 'document_user_id'
            )
        ));

        $this->add(array(
            'name' => 'document_creator_id',
            'type' => 'Hidden',
            'attributes' => array(
                'id' => 'document_creator_id'
            )
        ));

        $this->add(array(
            'name' => 'document_type_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'value_options' => $data['documentTypes'],
            ),
            'attributes' => array(
                'id' => 'document_type_id',
                'class' => 'form-control',
            )
        ));

        $this->add(array(
            'name' => 'document_date_created',
            'type' => 'Hidden',
            'attributes' => array(
                'id' => 'document_date_created'
            )
        ));

        $this->add(array(
            'name' => 'document_description',
            'type' => 'Zend\Form\Element\Textarea',
            'options' => array(

            ),
            'attributes' => array(
                'id' => 'document_description',
                'class' => 'form-control document-description',
                'rows' => 10            )
        ));

        $this->add(array(
            'name' => 'document_attachment',
            'type' => 'Zend\Form\Element\File',
            'attributes' => array(
                'id' => 'document_attachment',
                'class' => 'form-control definitely-hidden',
                'accept' => 'image/*,application/pdf, application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'data-max-size' => '52428800'
            )
        ));

        $this->add(array(
            'name' => 'document_file_name',
            'type' => 'Hidden',
            'attributes' => array(
                'id' => 'document_file_name'
            )
        ));

        $this->add(array(
            'name' => 'document_url',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'id' => 'document_url',
                'class' => 'form-control',
            )
        ));
    }
}
