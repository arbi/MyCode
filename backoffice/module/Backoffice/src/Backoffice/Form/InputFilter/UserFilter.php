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
use Library\InputFilter\InputFilterBase;
use Zend\I18n\Validator\Float;

class UserFilter extends InputFilterBase
{
    public function __construct($id = 0)
    {


        $password = array(
            'name'       => 'password',
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
        );
        $email = array(
            'name'       => 'email',
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
        );
        $email = array(
            'name'       => 'alt_email',
            'required'   => false,
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
        );

        if($id > 0){
//            $email['required'] = false;
            $password['required'] = false;
        }

        $this->add($email);
        $this->add($password);



        $this->add(array(
            'name'       => 'position',
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
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 35',
                        ),
                    ),
                ),
            ),
        ));


        $this->add(array(
            'name'       => 'firstname',
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
                        'max'      => 35,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 35',
                        ),
                    ),
                ),
            ),
        ));

        $this->add(array(
            'name'       => 'lastname',
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
                        'max'      => 35,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 35',
                        ),
                    ),
                ),
            ),
        ));

        $this->add(array(
        		'name'       => 'birthday',
        		'required'   => false,
        		'filters'    => array(
        				array('name'    => 'StringTrim'),
        				array('name' => 'StripTags'),
        		),
        ));

        $this->add(array(
            'name'       => 'reporting_office_id',
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
            'name'       => 'department',
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
            'name'       => 'manager',
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
            'name'       => 'country',
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
            'name'       => 'timezone',
            'required'   => true,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
        ));

        $this->add(array(
            'name'       => 'startdate',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
        ));

        $this->add(array(
            'name'       => 'vacationdays',
            'required'   => false,
            'filters'    => array(
                array('name' => 'StringTrim'),
                array('name' => 'StripTags')
            ),
            'validators' => array(
                array(
                        'name' => 'Float',
                        'options' => array(
	                        'min' => 0,
	                        'messages' => array(
		                        Float::NOT_FLOAT => 'Can contain only digits.',
	                        ),
                        ),
                    ),
               array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 16,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 16',
                        ),
                    ),
                ),
             ),
        ));

        $this->add(array(
            'name'       => 'vacation_days_per_year',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            ),
            'validators' => array(

                    array(
                        'name' => 'Float',
                        'options' => array(
	                        'min' => 0,
	                        'messages' => array(
		                        Float::NOT_FLOAT => 'Can contain only digits.',
	                        ),
                        ),
                    ),
                array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 6,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 6',
                        ),
                    ),
                ),
             ),
        ));

        $this->add(array(
            'name'       => 'employment',
            'required'   => false,
            'filters'    => array(
                array('name' => 'StringTrim'),
                array('name' => 'StripTags')
            ),
            'validators' => array(
                array(
                        'name' => 'Int',
                        'options' => array(
                            'min' => 0,
                            'messages' => array(
                                Int::NOT_INT => 'Can contain only digits.',
                            ),
                        ),
                    ),
               array(
                    'name'    => 'StringLength',
                    'options' => array(
                        'encoding' => 'UTF-8',
                        'max'      => 3,
                        'messages' => array(
                            StringLength::TOO_LONG  => 'maximum symbols 3',
                        ),
                    ),
                ),
             ),
        ));

        $this->add(array(
            'name'       => 'personalphone',
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
            'name'       => 'asana_id',
            'required'   => false,
            'filters'    => array(
                array('name' => 'StringTrim'),
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
                        'max'      => 25,
                        'messages' => array(
                            StringLength::TOO_LONG => 'maximum symbols 25',
                        ),
                    ),
                ),
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
            'name'       => 'accounts',
            'required'   => false
        ));
        $this->add(array(
        		'name'       => 'dashboards',
        		'required'   => false
        ));
        $this->add(array(
            'name'       => 'conciergegroups',
            'required'   => false
        ));

       $this->add(array(
            'name'       => 'weekFrom1',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekTo1',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekFrom2',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekTo2',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekFrom3',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekTo3',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekFrom4',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekTo4',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekFrom5',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekTo5',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekFrom6',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekTo6',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekFrom7',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

       $this->add(array(
            'name'       => 'weekTo7',
            'required'   => false,
            'filters'    => array(
                array('name'    => 'StringTrim'),
                array('name' => 'StripTags'),
            )
        ));

        $this->add(array(
		    'name' => 'system',
		    'required' => false,
		    'filters' => array(
			    array('name' => 'StringTrim'),
			    array('name' => 'StripTags'),
		    ),
		    'validators' => array(
			    array(
				    'name' => 'InArray',
				    'options' => array(
					    'haystack' => [1, 0],
				    ),
			    ),
		    ),
	    ));
    }
}
