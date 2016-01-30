<?php
namespace Backoffice\Form\InputFilter;

use Zend\Validator\EmailAddress;
use Zend\Validator\StringLength;
use Zend\Validator\NotEmpty;
use Zend\Validator\Digits;
use Zend\Validator\Date;
use Zend\Validator\Between;
use Zend\Validator\Hostname as HostnameValidator;
use Zend\I18n\Validator\Alnum;
use Zend\I18n\Validator\Int;
use Zend\I18n\Validator\Float;
use Library\InputFilter\InputFilterBase;

class TranslationFilter extends InputFilterBase
{
    public function __construct()
    {
        
        $this->add(array(
            'name'       => 'content',
            'required'   => false,
            'filters'    => array(
                array('name' => 'StringTrim'),
            )
        ));
     
        $this->add(array(
            'name'       => 'edit_id',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array(
                        'name' => 'Digits',
                        'options' => array(
                            'messages' => array(
                                Digits::NOT_DIGITS => 'Can contain only digits.',
                            ),
                        ),
                    ),
                ),
        ));
     
//        $this->add(array(
//            'name'       => 'viewCountField',
//            'required'   => false,
//            'filters'    => array(
//                array('name'    => 'StringTrim'),
//                array('name' => 'StripTags'),
//            ),
//            'validators' => array(
//                array(
//                        'name' => 'Digits',
//                        'options' => array(
//                            'messages' => array(
//                                Digits::NOT_DIGITS => 'Can contain only digits.',
//                            ),
//                        ),
//                    ),
//                ),
//        ));
        
        $this->add(array(
            'name'       => 'not_translated',
            'required'   => false
        ));
        
        $this->add(array(
            'name'       => 'native_language',
            'required'   => false
        ));
        
        $this->add(array(
            'name'       => 'dont_change_status',
            'required'   => false
        ));
        
        $this->add(array(
            'name'       => 'language',
            'required'   => false
        ));
        
        $this->add(array(
            'name'       => 'textline-type',
            'required'   => false
        ));
    }
}
