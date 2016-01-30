<?php
namespace Website\Form\InputFilter;

use DDD\Service\Location;
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
use Library\Utility\Helper;

class BookingFilter extends InputFilterBase
{
    public function __construct($data, $postalCodeStatus)
    {
        $this->add(array(
                'name'       => 'first-name',
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
                'name'       => 'last-name',
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
            'name'       => 'email',
            'required'   => true,
            'filters'    => array(
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                array(
                    'name'    => 'EmailAddress',
                    'options' => array(
                        'useDomainCheck' => false,
                        'messages' => array(
                            EmailAddress::INVALID => 'Please provide correct email address',
                            EmailAddress::INVALID_FORMAT => 'Please provide correct formated email address',
                        ),
                    ),
                ),
            ),
        ));

        if(Helper::isBackofficeUser()){
            $requiredPhone = false;
        } else {
            $requiredPhone = true;
            // Has filtered in booking process
        }

        $this->add(array(
            'name'       => 'phone',
            'required'   => $requiredPhone,
            'filters'    => array(
                array('name'    => 'StringTrim'),
            )
        ));
        $this->add(array(
                'name'       => 'remarks',
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
                            'max'      => 1000,
                            'messages' => array(
                                StringLength::TOO_LONG  => 'maximum symbols 1000',
                            ),
                        ),
                    ),
                ),
            )
        );

        $this->add(array(
            'name'       => 'aff-id',
            'required'   => false,
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
                'name'       => 'aff-ref',
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
                            'max'      => 200,
                            'messages' => array(
                                StringLength::TOO_LONG  => 'maximum symbols 200',
                            ),
                        ),
                    ),
                ),
            )
        );

        $this->add(array(
                'name'       => 'address',
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
                            'max'      => 400,
                            'min'      => 2,
                            'messages' => array(
                                StringLength::TOO_LONG  => 'maximum symbols 400',
                            ),
                        ),
                    ),
                ),
            )
        );
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
                'required'   => $postalCodeStatus == Location::POSTAL_CODE_REQUIRED ? true : false,
                'filters'    => array(
                    array('name' => 'StringTrim'),
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

        $isBackofficeUser = (Helper::isBackofficeUser() && isset($data['noCreditCard'])) ? true : false;
        $this->add(array(
            'name'       => 'number',
            'required'   => !$isBackofficeUser,
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
            'required'   => !$isBackofficeUser,
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
            'required'   => !$isBackofficeUser,
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
            ),
        ));

        $this->add(array(
            'name'       => 'cvc',
            'required'   => false,
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
        $validatorYear = array(
            'name' => 'Date',
            'options' => array(
                'format' => 'Y',
                'messages' => array(
                    Date::INVALID => 'Invalid data',
                    Date::INVALID_DATE => 'Invalid data',
                    Date::FALSEFORMAT => 'Invalid data',
                ),
            ),
        );
        $validatorMonth =  array(
            'name' => 'Date',
            'options' => array(
                'format' => 'm',
                'messages' => array(
                    Date::INVALID => 'Invalid data',
                    Date::INVALID_DATE => 'Invalid data',
                    Date::FALSEFORMAT => 'Invalid data',
                ),
            ),
        );
        if(!$isBackofficeUser) {
            $validatorYear[] = array(
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
            );
            $validatorMonth[] = array(
                'name' => 'Callback',
                'options' => array(
                    'messages' => array(
                        Callback::INVALID_VALUE => 'Invalid data',
                        Callback::INVALID_CALLBACK => 'Invalid data',
                    ),
                    'callback' => function($value, $context = array()) {
                            if($context['year'] == date('Y') && $value < date('m')) {
                                return false;
                            }
                            return true;
                        },
                ),
            );
        }

        $this->add(array(
            'name'       => 'year',
            'required'   => $isBackofficeUser,
            'filters'    => array(
                array('name'    => 'StringTrim'),
            ),
            'validators' => array(
                $validatorYear
            ),
        ));

        $this->add(array(
            'name'       => 'month',
            'required'   => $isBackofficeUser,
            'filters'    => array(
                array('name'    => 'StringTrim'),
            ),
            'validators' => array(
                $validatorMonth
            )
        ));

        $this->add([
            'name'       => 'apartel',
            'required'   => false,
            'filters'    => [
                array('name'    => 'StringTrim'),
            ],
            'validators' => [
                [
                    'name' => 'Digits',
                    'options' => [
                        'messages' => [
                            Digits::NOT_DIGITS => 'Can contain only digits.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add(array(
            'name'       => 'not_send_mail',
            'required'   => false,
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
            'name'       => 'noCreditCard',
            'required'   => false,
        ));
    }
}
