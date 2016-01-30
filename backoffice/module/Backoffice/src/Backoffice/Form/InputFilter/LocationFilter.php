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

class LocationFilter extends InputFilterBase
{
    public function __construct($type, $id)
    {
        $this->add([
            'name'       => 'name',
            'required'   => true,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 50,
                        'messages' => [
                            StringLength::TOO_LONG  => 'maximum symbols 50',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'timezone',
            'required'   => false,
        ]);

        $autocomplete_txt = [
            'name'       => 'autocomplete_txt',
            'required'   => true,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 50,
                        'messages' => [
                            StringLength::TOO_LONG  => 'maximum symbols 50',
                        ],
                    ],
                ],
            ],
        ];

        $autocomplete_id = [
            'name'       => 'autocomplete_id',
            'required'   => true,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
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
        ];

        if ($id > 0) {
            $autocomplete_txt['required'] = false;
            $autocomplete_id['required'] = false;
        }

        $this->add($autocomplete_txt);
        $this->add($autocomplete_id);

        $this->add([
            'name'       => 'latitude',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);

        $this->add([
            'name'       => 'longitude',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);

        $this->add([
            'name'       => 'information',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
            ]
        ]);

        $this->add([
            'name'       => 'iso',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'encoding' => 'UTF-8',
                        'max'      => 10,
                        'messages' => [
                            StringLength::TOO_LONG  => 'maximum symbols 10',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'is_searchable',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);

        $this->add([
            'name'       => 'tot_included',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);

        $this->add([
            'name'       => 'vat_included',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);

        $this->add([
            'name'       => 'city_tax_included',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);

        $this->add([
            'name'       => 'sales_tax_included',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);

        $this->add([
            'name'       => 'tot',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'float',
                    'options' => [
                        'messages' => [
                            Float::INVALID => 'Can contain only float.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'tot_additional',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'float',
                    'options' => [
                        'messages' => [
                            Float::INVALID => 'Can contain only float.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'vat',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'float',
                    'options' => [
                        'messages' => [
                            Float::INVALID => 'Can contain only float.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'vat_additional',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'float',
                    'options' => [
                        'messages' => [
                            Float::INVALID => 'Can contain only float.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'sales_tax',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'float',
                    'options' => [
                        'messages' => [
                            Float::INVALID => 'Can contain only float.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'sales_tax_additional',
            'required'   => false,
            'filters'    => [
                ['name' => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'float',
                    'options' => [
                        'messages' => [
                            Float::INVALID => 'Can contain only float.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'city_tax',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'float',
                    'options' => [
                        'messages' => [
                            Float::INVALID => 'Can contain only float.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'city_tax_additional',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ],
            'validators' => [
                [
                    'name' => 'float',
                    'options' => [
                        'messages' => [
                            Float::INVALID => 'Can contain only float.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->add([
            'name'       => 'tot_type',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);
        $this->add([
            'name'       => 'vat_type',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);
        $this->add([
            'name'       => 'sales_tax_type',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);
        $this->add([
            'name'       => 'city_tax_type',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);

        $this->add([
            'name'       => 'poi_type',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
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

        $this->add([
            'name'       => 'edit_id',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
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

        $this->add([
            'name'       => 'type_location',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);

        $this->add([
            'name'       => 'cover_image_post',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);

        $this->add([
            'name'       => 'thumbnail_post',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);

        $this->add([
            'name'       => 'province_short_name',
            'required'   => false,
            'filters'    => [
                ['name'    => 'StringTrim'],
                ['name' => 'StripTags'],
            ]
        ]);
    }
}
