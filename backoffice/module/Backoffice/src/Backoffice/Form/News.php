<?php
namespace Backoffice\Form;

use Library\Constants\Constants;
use Zend\Form\Element;
use Library\Form\FormBase;
use Library\Constants\Objects;

class News extends FormBase
{
    
    public function __construct($name = null, $data)
    {
        parent::__construct($name);
        
        $name = $this->getName();
        if (null === $name) {
            $this->setName('news');
        }
        
        $this->add(array(
            'name' => 'title',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control col-md-12',
                'id' => 'title',
                'maxlength' => 150,
            ),
        ));
        
       $this->add(array(
            'name' => 'body',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'textarea',
                'class' => 'tinymce',
                'rows' => '4',
                'id' => 'body',
            ),
        ));
       
       $this->add(array(
            'name' => 'edit_id',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'edit_id',
            ),
        ));
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
        $buttons_save = 'Create News Post';
        if(is_object($data)){
            $buttons_save = 'Save Changes';
        }
       $this->add(array(
            'name' => 'save_button',
            'options' => array(
                'label' => $buttons_save,
            ),
            'attributes' => array(
                'type' => 'button',
                'class' => 'btn btn-primary col-sm-2 col-xs-12 margin-left-10 pull-right',
                'data-loading-text' => 'Saving...',
                'id' => 'save_button',
                'value' => 'Save',
            ),
        ));
       if(is_object($data)) {
            $objectData = new \ArrayObject();
            $objectData['title'] = $data->getEn_title();
            $objectData['body'] = $data->getEn();
            $objectData['edit_id'] = $data->getId();
            $objectData['date'] = date(Constants::GLOBAL_DATE_FORMAT, strtotime($data->getDate()));
            $this->bind($objectData);    
       }
    }
}
