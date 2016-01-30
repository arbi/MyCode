<?php
namespace Backoffice\Form\InputFilter;

use Library\Validator\ClassicValidator;
use Zend\Validator\Regex;
use Zend\Validator\StringLength;
use Zend\Validator\Digits;
use Zend\Validator\Identical;
use Library\InputFilter\InputFilterBase;

class ProfilePasswordFilter extends InputFilterBase
{
   public function __construct()
    {
        $password = array(
            'name'       => 'password',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'min' => 6,
                        'max'      => 20,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 20',
                            StringLength::TOO_SHORT => 'minimum symbols 6',
                        ),
                    ),
                ),
            ),
        );

        $passwordVerify = array(
            'name'       => 'passwordVerify',
            'required'   => false,
            'filters'    => array(array('name' => 'StringTrim')),
            'validators' => array(
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'min' => 6,
                        'max' => 25,
                        'messages' => array(
                            StringLength::TOO_SHORT => 'minimum symbols 6',
                            StringLength::TOO_LONG  => 'maximum symbols 20',
                            StringLength::INVALID   => 'invalid',
                        ),
                    ),
                ),
                array(
                    'name'    => 'Identical',
                    'options' => array(
                        'token' => 'password',
                        'messages' => array(
                            Identical::NOT_SAME => 'Verify password do not match with password.'
                        )
                    ),
                ),
            ),
        );
        $password['required'] = true;
        $passwordVerify['required'] = true;

        $this->add($password);
        $this->add($passwordVerify);

         $this->add(array(
            'name'       => 'userId',
            'required'   => true,
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

    }
}
