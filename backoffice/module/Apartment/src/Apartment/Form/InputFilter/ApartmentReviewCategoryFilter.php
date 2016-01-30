<?php

namespace Apartment\Form\InputFilter;

use Zend\Validator\EmailAddress;
use Zend\Validator\StringLength;
use Zend\Validator\NotEmpty;
use Zend\Validator\Digits;
use Zend\Validator\Date;
use Zend\Validator\Between;
use Zend\Validator\Hostname as HostnameValidator;
use Zend\I18n\Validator\Alnum;
use Zend\I18n\Validator\Float;
use Zend\I18n\Validator\Int;
use Library\InputFilter\InputFilterBase;

class ApartmentReviewCategoryFilter extends InputFilterBase
{
    public function __construct() {

	    $this->add(array(
            'name'       => 'name',
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
                        'max'      => 250,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 250',
                        ),
                    ),
                ),
            ),
            )
        );

        $this->add(array(
            'name'       => 'type',
            'required'   => true,
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
