<?php

namespace Apartment\Form;

use Zend\Form\Form;
use Library\Constants\Objects;
use DDD\Service\Apartment\Document;

class SearchApartmentDocumentForm extends Form
{
    protected $resources;

    public function __construct($name = 'search_document', $resources = [], $legalEntitiesArray=[], $signatoriesArray=[])
    {
        parent::__construct($name);

        $this->resources = $resources;
        $this->setAttribute('method', 'post');

        // Apartment Status
        $this->add(
            [
                'name'       => 'status',
                'type'       => 'Zend\Form\Element\Select',
                'options'    => [
                    'label'         => false,
                    'value_options' => $this->getStatuses()
                ],
                'attributes' => [
                    'class' => 'form-control',
                ],
            ]
        );

        // legal Entities
        $this->add(
            [
                'name'       => 'legal_entity_id',
                'type'       => 'Zend\Form\Element\Select',
                'options'    => [
                    'label'         => false,
                    'value_options' => $legalEntitiesArray
                ],
                'attributes' => [
                    'class' => 'form-control',
                ],
            ]
        );

        // Signers
        $this->add(
            [
                'name'       => 'signatory_id',
                'type'       => 'Zend\Form\Element\Select',
                'options'    => [
                    'label'         => false,
                    'value_options' => $signatoriesArray
                ],
                'attributes' => [
                    'class' => 'form-control',
                ],
            ]
        );

        // Building
        $this->add(
            [
                'name'       => 'building',
                'type'       => 'Zend\Form\Element\Text',
                'options'    => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'class'       => 'form-control pull-right',
                    'placeholder' => 'All Groups, Country',
                    'id'          => 'building',
                ),
            ]
        );

        $this->add(
            [
                'name'       => 'building_id',
                'type'       => 'Zend\Form\Element\Hidden',
                'attributes' => [
                    'id'    => 'building_id',
                    'value' => '0'
                ],
            ]
        );

        // Address
        $this->add(
            [
                'name'       => 'address',
                'type'       => 'Zend\Form\Element\Text',
                'options'    => [
                    'label' => false,
                ],
                'attributes' => [
                    'class'       => 'form-control',
                    'placeholder' => 'Apartment name, Address, Postal Code or Unit Number',
                ],
            ]
        );

        // Account Number
        $this->add(
            [
                'name'       => 'account_number',
                'type'       => 'Zend\Form\Element\Text',
                'options'    => [
                    'label' => false,
                ],
                'attributes' => [
                    'class'       => 'form-control',
                    'placeholder' => 'Account Number',
                ],
            ]
        );

        // Account Holder
        $this->add(
            [
                'name'       => 'account_holder',
                'type'       => 'Zend\Form\Element\Text',
                'options'    => [
                    'label' => false,
                ],
                'attributes' => [
                    'class'       => 'form-control',
                    'placeholder' => 'Account Holder',
                ],
            ]
        );

        // Create date
        $this->add(
            [
                'name'       => 'createdDate',
                'type'       => 'Zend\Form\Element\Text',
                'options'    => [
                    'label' => false,
                ],
                'attributes' => [
                    'class'       => 'form-control pull-right',
                    'placeholder' => 'Created Date',
                    'id'          => 'createdDate',
                ],
            ]
        );

        // document validation range
        $this->add(
            [
                'name'       => 'validation-range',
                'type'       => 'Zend\Form\Element\Text',
                'options'    => [
                    'label' => false,
                ],
                'attributes' => [
                    'class'       => 'form-control pull-right',
                    'placeholder' => 'Document validation range',
                    'id'          => 'validation-range',
                ],
            ]
        );

        // Document Type
        $this->add([
            'name'       => 'document_type',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'label'         => false,
                'value_options' => $this->getDocumentTypes()
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        // Security level
        $this->add([
            'name'       => 'security_level',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'label'         => false,
                'value_options' => $this->getSecurityLevels()
            ],
            'attributes' => [
                'class' => 'form-control',
            ],
        ]);

        // Suppliers
        $this->add(
            [
                'name'       => 'supplier',
                'type'       => 'Zend\Form\Element\Text',
                'options'    => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'class'       => 'form-control pull-right',
                    'placeholder' => 'Supplier',
                    'id'          => 'supplier',
                ),
            ]
        );

        // Supplier id
        $this->add(
            [
                'name'       => 'supplier_id',
                'type'       => 'Zend\Form\Element\Hidden',
                'attributes' => [
                    'id'    => 'supplier_id',
                    'value' => '0'
                ],
            ]
        );

        // Author / User
        $this->add(
            [
                'name'       => 'author',
                'type'       => 'Zend\Form\Element\Text',
                'options'    => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'class'       => 'form-control pull-right',
                    'placeholder' => 'Document Author',
                    'id'          => 'author',
                ),
            ]
        );

        // Author id
        $this->add(
            [
                'name'       => 'author_id',
                'type'       => 'Zend\Form\Element\Hidden',
                'attributes' => [
                    'id'    => 'author_id',
                    'value' => '0'
                ],
            ]
        );

        // Description
        $this->add(
            [
                'name'       => 'description',
                'type'       => 'Zend\Form\Element\Text',
                'options'    => [
                    'label' => false,
                ],
                'attributes' => [
                    'class'       => 'form-control',
                    'placeholder' => 'Description',
                    'id'          => 'description'
                ],
            ]
        );

        // Has Attachment
        $this->add([
            'name'       => 'has_attachment',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'label'         => false,
                'value_options' => $this->getAttachmentOptions()
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'has_attachment'
            ],
        ]);

        // Has Url
        $this->add([
            'name'       => 'has_url',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'label'         => false,
                'value_options' => $this->getUrlOptions()
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'has_url'
            ],
        ]);

        // Has Url
        $this->add([
            'name'       => 'has_url',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'label'         => false,
                'value_options' => $this->getUrlOptions()
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'has_url'
            ],
        ]);


        // property type
        $this->add([
            'name'       => 'property_type',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'label'         => false,
                'value_options' => $this->getPropertyTypes()
            ],
            'attributes' => [
                'class' => 'form-control',
                'id'    => 'property_type'
            ],
        ]);

    }

    /**
     * Get Apartment Statuses to populate Select element
     *
     * @return array
     */
    public function getStatuses()
    {
        $statusesArray = array(
            0                                            => "-- Apartment Statuses --",
            Objects::PRODUCT_STATUS_SANDBOX              => "Sandbox",
            Objects::PRODUCT_STATUS_REGISTRATION         => "Registration",
            Objects::PRODUCT_STATUS_REVIEW               => "Review",
            Objects::PRODUCT_STATUS_SELLING              => "Selling",
            Objects::PRODUCT_STATUS_LIVEANDSELLIG        => "Live and Selling",
            Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE => "Selling Not Searchable",
            Objects::PRODUCT_STATUS_SUSPENDED            => "Suspended",
            Objects::PRODUCT_STATUS_LIVE_IN_UNIT         => "Live-in Unit",
            Objects::PRODUCT_STATUS_DISABLED             => "Disabled"
        );

        return $statusesArray;
    }

    public function getDocumentTypes()
    {
        $documentTypes = $this->resources['document_types'];

        $documentTypesArray = [
            0 => '-- Document Types --'
        ];

        foreach ($documentTypes as $type) {
            $documentTypesArray[$type->getId()] = $type->getName();
        }

        return $documentTypesArray;
    }

    public function getSecurityLevels()
    {
        $documentSecurityLevels = array_merge(
            [
                0 => '-- Security --'
            ],
            $this->resources['accessTeams']
        );

        return $documentSecurityLevels;
    }

    public function getAttachmentOptions()
    {
        return [
            0 => '-- Attachment --',
            1 => 'With Attachment',
            2 => 'Without Attachment'
        ];
    }

    public function getUrlOptions()
    {
        return [
            0 => '-- URL --',
            1 => 'With URL',
            2 => 'Without URL'
        ];
    }

    public function getPropertyTypes()
    {
        return [
            0 => '-- Property Types --',
            1 => 'Apartment Only',
            2 => 'Building Only'
        ];
    }

}
