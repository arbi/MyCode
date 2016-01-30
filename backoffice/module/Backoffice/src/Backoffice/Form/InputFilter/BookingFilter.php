<?php
namespace Backoffice\Form\InputFilter;

use Zend\Validator\EmailAddress;
use Zend\Validator\StringLength;
use Zend\Validator\NotEmpty;
use Zend\Validator\Digits;
use Zend\Validator\Float;
use Zend\Validator\Date;
use Zend\Validator\Between;
use Zend\Validator\Hostname as HostnameValidator;
use Zend\I18n\Validator\Alnum;
use Zend\I18n\Validator\Int;
use Library\InputFilter\InputFilterBase;

class BookingFilter extends InputFilterBase
{
    public function __construct()
    {
         $this->add(array(
            'name'       => 'guest_name',
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
                            StringLength::TOO_LONG  => 'maximum symbols 150',
                        ),
                    ),
                ),
            ),
            )
        );
        $this->add(array(
            'name'       => 'guest_last_name',
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
                            StringLength::TOO_LONG  => 'maximum symbols 150',
                        ),
                    ),
                ),
            ),
            )
        );

        $this->add(array(
            'name'       => 'guest_email',
            'required'   => true,
            'filters'    => array(
                array('name' => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array(
                    'name'    => 'EmailAddress',
                    'options' => array(
                        'allow'  => HostnameValidator::ALLOW_DNS,
                        'domain' => true,
                        'messages' => array(
                              EmailAddress::INVALID => 'Please provide correct email address',
                              EmailAddress::INVALID_FORMAT => 'Please provide correct formated email address',
                        ),
                    ),
                ),
            ),
        ));

        $this->add(array(
            'name'       => 'second_guest_email',
            'required'   => false,
            'filters'    => array(
                array('name' => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(
                array(
                    'name'    => 'EmailAddress',
                    'options' => array(
                        'allow'  => HostnameValidator::ALLOW_DNS,
                        'domain' => true,
                        'messages' => array(
                              EmailAddress::INVALID => 'Please provide correct email address',
                              EmailAddress::INVALID_FORMAT => 'Please provide correct formated email address',
                        ),
                    ),
                ),
            ),
        ));

        $this->add(array(
            'name'       => 'guest_country',
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
        $this->add(array(
            'name'       => 'guest_city',
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
                        'max'      => 150,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 150',
                        ),
                    ),
                ),
            ),
            )
        );

        $this->add(array(
            'name'       => 'guest_address',
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
                        'max'      => 150,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 150',
                        ),
                    ),
                ),
            ),
            )
        );

        $this->add(array(
            'name'       => 'guest_zipcode',
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
                        'max'      => 20,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 20',
                        ),
                    ),
                ),
            ),
            )
        );
        $this->add(array(
            'name'       => 'guest_phone',
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
                        'max'      => 50,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 50',
                        ),
                    ),
                ),
            ),
            )
        );
        $this->add(array(
            'name'       => 'guest_travel_phone',
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
                        'max'      => 50,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 50',
                        ),
                    ),
                ),
            ),
            )
        );

        $this->add(array(
        		'name'       => 'booking_guest_remakrs',
        		'required'   => true,
        		'filters'    => array(
        				array('name'    => 'StringTrim'),
        				array('name' => 'StripTags'),
        		),
        		'validators' => array(
        		),
        ));

       $this->add(array(
            'name'       => 'booking_partners',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            )
        );

       $this->add(array(
            'name'       => 'booking_affiliate_reference',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            )
        );
       $this->add(array(
            'name'       => 'finance_paid_affiliate',
            'required'   => false,
       ));
       $this->add(array(
            'name'       => 'overbooking_status',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            )
        );
       $this->add(array(
            'name'       => 'booking_arrival_time',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            )
        );
       $this->add(array(
            'name'       => 'booking_guest_remakrs',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
            ),
            )
        );
       $this->add(array(
            'name'       => 'finance_valid_card',
            'required'   => false,
            )
        );

        $this->add(array(
                'name'       => 'finance_reservation_settled',
                'required'   => false,
            )
        );

        $this->add(array(
            'name'       => 'booking_id',
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

        $this->add(array(
            'name'       => 'booking_res_number',
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
                        'max'      => 30,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 30',
                        ),
                    ),
                ),
            ),
            )
        );
        $this->add(array(
            'name'       => 'finance_booked_state',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
        ));

    }
}
