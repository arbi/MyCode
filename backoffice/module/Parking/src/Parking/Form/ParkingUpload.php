<?php

namespace Parking\Form;

use Zend\InputFilter;
use Zend\Form\Form;
use Zend\Form\Element;
use Zend\Validator\File\Extension;
use FileManager\Constant\DirectoryStructure;

class ParkingUpload extends Form
{
    public function __construct($name = null, $options = array())
    {
        parent::__construct($name, $options);
        $this->addElements();
        $this->setInputFilter($this->createInputFilter());
    }

    public function addElements()
    {
        // File Input
        $file = new Element\File('file');
        $file
            ->setLabel('File Input')
            ->setAttributes([
                'id'       => 'file',
                'accept'   => 'image/*'
            ]);
        $this->add($file);
        // Progress ID hidden input is only added with a view helper,
        // not as an element to the form.
    }

    public function createInputFilter()
    {
        $inputFilter = new InputFilter\InputFilter();
        // File Input
        $file = new InputFilter\FileInput('file');
        $file->setRequired(true);

        $file->getFilterChain()->attachByName(
            'filerenameupload',
            [
                'target'          => DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_IMAGES_ROOT
                    . DirectoryStructure::FS_IMAGES_TEMP_PATH,
                'overwrite'       => true,
                'randomize'       => true,
                'use_upload_name' => true
            ]
        );

        // Allowed extensions
        $file->getValidatorChain()->attachByName(
            'fileextension',
            [
                'png', 'jpg', 'gif'
            ]
        );
        $inputFilter->add($file);
        return $inputFilter;
    }
}