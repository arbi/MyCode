<?php
namespace Website\Form\InputFilter;

use Zend\Validator\Digits;
use Zend\Validator\Date;
use Zend\Validator\Regex;
use Zend\Validator\CreditCard;
use Zend\I18n\Validator\Alnum;
use Library\InputFilter\InputFilterBase;
use Library\Validator\ClassicValidator;
use Zend\Validator\StringLength;
use Zend\Validator\Callback;
use Zend\Validator\EmailAddress;
use Zend\Validator\Hostname as HostnameValidator;

class CCUpdateFilter extends InputFilterBase
{
    public function __construct()
    {

        $this->add(array(
            'name'       => 'number',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
            ),
            'validators' => array(
                array(
                        'name' => 'CreditCard',
                        'options' => array(
                            'type' => array(
                                CreditCard::AMERICAN_EXPRESS,
                                CreditCard::VISA,
                                CreditCard::MASTERCARD,
                                CreditCard::DISCOVER,
                                CreditCard::JCB,
                                CreditCard::DINERS_CLUB,
                            ),
                            'messages' => array(
                                CreditCard::CHECKSUM => "Invalid CreditCard",
                                CreditCard::CONTENT => "Invalid CreditCard",
                                CreditCard::INVALID => "Invalid CreditCard",
                                CreditCard::LENGTH => "Invalid CreditCard",
                                CreditCard::PREFIX => "Invalid CreditCard",
                                CreditCard::SERVICE => "Invalid CreditCard",
                                CreditCard::SERVICEFAILURE => "Invalid CreditCard"
                            ),
                        ),
                    ),
                ),
        ));

        $this->add(array(
            'name'       => 'credit_card_type',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
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
            'name'       => 'holder',
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 300,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 300',
                        ),
                    ),
                ),
//                array(
//                    "name"=>"regex",
//                    'options' => array(
//                        'pattern'=>  ClassicValidator::regexHolderName(),
//                        'messages' => array(
//                            Regex::INVALID=>"Invalid data",
//                            Regex::NOT_MATCH=> "Invalid data",
//                            Regex::ERROROUS=> "Internal error"
//                        )
//                    )
//                )
            ),
        ));

        $this->add(array(
            'name'       => 'cvc',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
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
                        'max'      => 4,
                        'min'      => 3,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 10',
                            StringLength::TOO_SHORT  => 'minimum symbols 3',
                        ),
                    ),
                ),
             ),
        ));

        $this->add(array(
            'name'       => 'year',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
            ),
            'validators' => array(
                array(
                        'name' => 'Date',
                        'options' => array(
                            'format' => 'Y',
                            'messages' => array(
                                Date::INVALID => 'Invalid data',
                                Date::INVALID_DATE => 'Invalid data',
                                Date::FALSEFORMAT => 'Invalid data',
                            ),
                        ),
                    ),
                array(
                        'name' => 'Callback',
                        'options' => array(
                            'messages' => array(
                                    Callback::INVALID_VALUE => 'Invalid data',
                                    Callback::INVALID_CALLBACK => 'Invalid data',
                            ),
                            'callback' => function($value, $context = array()) {
                                $current = date('Y');
                                return $value >= $current;
                            },
                        ),
                    ),

             ),
        ));

        $this->add(array(
            'name'       => 'month',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
            ),
            'validators' => array(
                array(
                        'name' => 'Date',
                        'options' => array(
                            'format' => 'm',
                            'messages' => array(
                                Date::INVALID => 'Invalid data',
                                Date::INVALID_DATE => 'Invalid data',
                                Date::FALSEFORMAT => 'Invalid data',
                            ),
                        ),
                    ),
                array(
                        'name' => 'Callback',
                        'options' => array(
                            'messages' => array(
                                    Callback::INVALID_VALUE => 'Invalid data',
                                    Callback::INVALID_CALLBACK => 'Invalid data',
                            ),
                            'callback' => function($value, $context = array()) {
                                if($context['year'] == date('Y') && $value < date('m'))
                                    return false;
                                return true;
                            },
                        ),
                    ),
             ),
        ));
        $this->add(array(
            'name'       => 'country',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
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
            'name'       => 'city',
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
                        'max'      => 100,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 100',
                        ),
                    ),
                ),
            ),
            )
        );

        $this->add(array(
            'name'       => 'zip',
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
                        'max'      => 50,
                        'min'      => 3,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 50',
                        ),
                    ),
                ),
            ),
            )
        );
    }
}
