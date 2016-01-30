<?php
namespace Backoffice\Form;

use Library\Constants\Constants;
use Library\Form\FormBase;

class Blog extends FormBase
{

    public function __construct($name = null, $data)
    {
        parent::__construct($name);

        $name = $this->getName();
        if (null === $name) {
            $this->setName('blog');
        }

        $this->add(
            [
                'name' => 'title',
                'options' =>
                [
                    'label' => '',
                ],
                'attributes' =>
                [
                    'type'      => 'text',
                    'class'     => 'form-control',
                    'id'        => 'title',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'body',
                'options' =>
                [
                    'label' => '',
                ],
                'attributes' =>
                [
                    'type'  => 'textarea',
                    'class' => 'tinymce',
                    'rows'  => '4',
                    'id'    => 'body',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'img',
                'options' =>
                [
                    'label' => '',
                ],
                'attributes' =>
                [
                    'type'   => 'file',
                    'class'  => 'hidden-file-input',
                    'id'     => 'img',
                    'accept' => 'image/*',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'edit_id',
                'attributes' =>
                [
                    'type' => 'hidden',
                    'id'   => 'edit_id',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'edit_title',
                'attributes' =>
                [
                    'type' => 'hidden',
                    'id'   => 'edit_title',
                ],
            ]
        );

        $this->add(
            [
                'name' => 'img_post',
                'attributes' =>
                [
                    'type' => 'hidden',
                    'id'   => 'img_post',
                ],
            ]
        );
        
        $this->add(
            [
                'name' => 'date',
                'options' =>
                [
                    'label' => '',
                ],
                'attributes' =>
                [
                    'type'      => 'text',
                    'class'     => 'form-control',
                    'id'        => 'date',
                    'maxlength' => 50,
                ],
            ]
        );
        
        $buttons_save = 'Create Travel Blog Post';
        if (is_object($data)) {
            $buttons_save = 'Save Changes';
        }

        $this->add(
            [
                'name' => 'save_button',
                'options' =>
                [
                    'label' => $buttons_save,
                ],
                'attributes' =>
                [
                    'type'              => 'button',
                    'data-loading-text' => 'Saving...',
                    'id'                => 'save_button',
                    'value'             => 'Save',
                    'class'             =>
                        'btn btn-primary pull-right col-xs-12 col-sm-2 margin-left-10',
                ],
            ]
        );

        if (is_object($data)) {

            $objectData = new \ArrayObject();
            $objectData['title']      = $data->getTitle();
            $objectData['body']       = $data->getContent();
            $objectData['edit_id']    = $data->getId();
            $objectData['edit_title'] = $data->getTitle();
            $objectData['img_post']   = $data->getImg();
            $objectData['date']       = date(Constants::GLOBAL_DATE_FORMAT, strtotime($data->getDate()));

            $this->bind($objectData);
        }
    }
}
