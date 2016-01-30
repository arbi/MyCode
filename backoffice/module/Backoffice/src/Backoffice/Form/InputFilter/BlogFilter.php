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

class BlogFilter extends InputFilterBase
{
    public function __construct()
    {
        $this->add(array(
            'name'       => 'title',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 150,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum number of symbols 150',
                        ),
                    ),
                ),
            ),
        ));
        
        $this->add(array(
            'name'       => 'body',
            'required'   => true,
            'filters'    => array(
                array('name' => 'StringTrim'),
            )
        ));
        
        $this->add(array(
            'name'       => 'date',
            'required'   => true,
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
     
        $this->add(array(
            'name'       => 'img_post',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));
    }
}
