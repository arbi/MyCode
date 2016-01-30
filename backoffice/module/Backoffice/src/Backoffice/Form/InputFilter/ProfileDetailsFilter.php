<?php
namespace Backoffice\Form\InputFilter;

use Library\Validator\ClassicValidator;
use Zend\Validator\Regex;
use Zend\Validator\StringLength;
use Zend\Validator\Digits;
use Zend\Validator\Identical;
use Library\InputFilter\InputFilterBase;

class ProfileDetailsFilter extends InputFilterBase
{
   public function __construct()
    {

        $this->add(array(
            'name'       => 'businessphone',
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
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 15,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 15',
                        ),
                    ),
                ),
             ),
        ));

        $this->add(array(
            'name'       => 'emergencyphone',
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
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 15,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 15',
                        ),
                    ),
                ),
             ),
        ));

        $this->add(array(
            'name'       => 'housephone',
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
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 15,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 15',
                        ),
                    ),
                ),
             ),
        ));

	    $this->add(array(
		    'name'       => 'birthday',
		    'required'   => false,
		    'filters'    => array(
			    array('name' => 'StringTrim'),
			    array('name' => 'StripTags'),
		    ),
		    'validators' => array(
		    ),
	    ));

        $this->add(array(
            'name'       => 'address_permanent',
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
                        'max'      => 250,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 250',
                        ),
                    ),
                ),
             ),
        ));

        $this->add(array(
            'name'       => 'address_residence',
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
                        'max'      => 250,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 250',
                        ),
                    ),
                ),
            ),
        ));

        $this->add(array(
            'name'       => 'housephone',
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
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 15,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 15',
                        ),
                    ),
                ),
             ),
        ));

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
