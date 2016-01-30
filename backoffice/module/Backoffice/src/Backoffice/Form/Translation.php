<?php
namespace Backoffice\Form;

use Zend\Form\Element;
use Library\Form\FormBase;
use Library\Constants\Objects;

class Translation extends FormBase
{

    public function __construct($name = null, $data, $options, $getparams, $pageTypes, $selectedPages = [])
    {
        parent::__construct($name);

        $name = $this->getName();
        if (null === $name) {
            $this->setName('translation_form');
        }

        if(is_object($data)){

            $this->add(array(
                'name' => 'textline-type',
                'options' => array(
                    'label' => '',
                    'value_options' => $pageTypes
                ),
                'type' => 'Zend\Form\Element\Select',
                'attributes' => array(
                    'id' => 'textline-type',
                    'class' => 'form-control'
                )
            ));
        } else {
            $pages = array();
            foreach ($options['pages'] as $row){
                $pages[$row->getId()] = $row->getName();
            }

            $this->add(array(
                'name' => 'pages',
                'options' => array(
                    'label' => '',
                    'value_options' => $pages
                ),
                'type' => 'Zend\Form\Element\Select',
                'attributes' => array(
                    'id' => 'pages',
                    'class' => 'form-control'
                )
            ));
        }

        $this->add(array(
            'name' => 'publish_translation',
            'options' => array(
                'label' => 'Save Textline',
            ),
            'attributes' => array(
                'type' => 'button',
                'class' => 'btn btn-primary state col-sm-2 col-xs-12 pull-right',
                'data-loading-text' => 'Publishing...',
                'id' => 'publish_translation',
            ),
        ));

        $this->add(array(
            'name' => 'content',
            'options' => array(
                'label' => '',
            ),
            'attributes' => array(
                'type' => 'textarea',
                'class' => 'tinymce',
                'rows' => '4',
                'id' => 'content',
            ),
        ));

        $this->add([
            'name'    => 'description',
            'options' => [
                'label'    => 'Description',
                'required' => false,
            ],
            'attributes' => [
                'type'  => 'textarea',
                'id'    => 'description',
                'class' => 'form-control',
            ],
        ]);

        $this->add(array(
            'name' => 'lang_code',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'lang_code',
            ),
        ));

        $this->add(array(
            'name' => 'edit_id',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'edit_id',
            ),
        ));

        $this->add(array(
            'name' => 'type_translation',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'type_translation',
            ),
        ));

        $this->add(array(
            'name' => 'location_option',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'location_option',
            ),
        ));
        $this->add(array(
            'name' => 'locationType',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'locationType',
            ),
        ));
        $this->add(array(
            'name' => 'hash',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'hash',
            ),
        ));

        $this->add(
            array(
                'name' => 'textline-type',
                'type' => 'Zend\Form\Element\Select',
                'placeholder' => 'Description',
                'options' => array(
                    'label' => false,
                    'value_options' =>  $pageTypes,
                ),
                'attributes' => array(
                    'data-placeholder' => 'Page Type',
                    'class'    => 'form-control selectize',
                    'id'       => 'textline-type',
                    'multiple' => true
                ),
            )
        );

        if(is_object($data)) {
            $objectData = new \ArrayObject();
            $objectData['language']         = $getparams['language'];
            $objectData['lang_code']        = $getparams['language'];
            $objectData['type_translation'] = $getparams['type_translation'];
            $objectData['type_translation'] = $getparams['type_translation'];
            $objectData['location_option']  = $getparams['locationOption'];
            $objectData['locationType']     = $getparams['locationType'];
            $objectData['description']      = $data->getDescription();
            $objectData['edit_id']          = $data->getId();
            $objectData['content']          = $data->getContent();
            $objectData['textline-type']    = $selectedPages;
            $this->bind($objectData);
        }
    }
}
