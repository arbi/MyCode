<?php
namespace Backoffice\Form\InputFilter;

use Zend\Validator\StringLength;
use Zend\Validator\Digits;
use Zend\Validator\Date;
use Zend\Validator\Regex;
use Library\InputFilter\InputFilterBase;
use Library\Validator\ClassicValidator;
use Zend\I18n\Validator\Float;
use Zend\I18n\Validator\Int;
use DDD\Service\User\Vacation as VacationService;

class VacationdaysFilter extends InputFilterBase
{
    public function __construct($data)
    {

        $this->add(array(
            'name'       => 'from',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array(
                    "name"=>"Date",
                    'options' => array(
                        'format'=>  'Y-m-d',
                        'messages' => array(
                            \Zend\Validator\Date::INVALID => 'Invalid data!!!',
                            \Zend\Validator\Date::INVALID_DATE => 'Invalid data!!!',
                            \Zend\Validator\Date::FALSEFORMAT => 'Invalid data!!!',
                        )
                    )
                )
            ),
        ));

        $this->add(array(
            'name'       => 'to',
            'required'   => true,
            'filters'    => array(
                array('name' => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array(
                    'name' => 'Date',
                    'options' => array(
                        'format'=>  'Y-m-d',
                        'messages' => array(
                            \Zend\Validator\Date::INVALID => 'Invalid data!!!',
                            \Zend\Validator\Date::INVALID_DATE => 'Invalid data!!!',
                            \Zend\Validator\Date::FALSEFORMAT => 'Invalid data!!!',
                        )
                    )
                )
            ),
        ));

        if ($data['vacation_type'] == VacationService::VACATION_TYPE_SICK) {
            $numberValidator = [
                [
                    'name' => 'Int',
                    'options' => [
                        'min' => 0,
                        'messages' => [
                            Int::NOT_INT => 'Can contain only digits.',
                        ],
                    ],
                ]
            ];
        } else {
            $numberValidator = [
                [
                    'name' => 'Float',
                    'options' => [
                        'min' => 0,
                        'messages' => [
                            Float::NOT_FLOAT => 'Can contain only digits.',
                        ],
                    ],
                ]
            ];
        }

        $this->add(array(
            'name'       => 'total_number',
            'required'   => false,
            'filters'    => array(
                array('name' => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => $numberValidator
        ));

        $this->add(array(
            'name'       => 'vacation_type',
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
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 20,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 3',
                        ),
                    ),
                ),
             ),
        ));

        $this->add(array(
            'name' => 'comment',
            'required' => false,
            'filters' => array(
                array('name' => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 255,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 255',
                        ),
                    ),
                ),
            ),
        ));
    }
}
