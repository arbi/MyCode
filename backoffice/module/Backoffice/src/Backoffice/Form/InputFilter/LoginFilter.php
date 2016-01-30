<?php
namespace Backoffice\Form\InputFilter;

use Zend\Validator\EmailAddress;
use Zend\Validator\StringLength;
use Zend\Validator\Hostname as HostnameValidator;
use Library\InputFilter\InputFilterBase;

class LoginFilter extends InputFilterBase
{
    public function __construct()
    {
        $this->add(array(
            'name'       => 'user_email',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array(
                    'name'    => 'EmailAddress',
                    'options' => array(
                        'allow'  => HostnameValidator::ALLOW_DNS,
                        'domain' => true,
                        'min' => 3,
                        'max' => 100,
                        'messages' => array(
                              EmailAddress::INVALID => 'Please provide correct email address',
                              EmailAddress::INVALID_FORMAT => 'Please provide correct formated email address',
                        ),
                    ),
                ),
            ),
        ));
        $this->add(array(
            'name'       => 'user_password',
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
                        'max'      => 20,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 20',
                        ),
                    ),
                ),
            ),
        ));
    }
}
