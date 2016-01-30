<?php

namespace Apartment\Form;

use Zend\Form\Form;
use DDD\Service\Accommodations;
use Library\Constants\Objects;

/**
 * Form for searching apartments
 * @final
 * @category apartment
 * @package apartment_forms
 * @subpackage apartment_search_forms
 *
 * @author Tigran Petrosyan
 */
final class SearchApartmentForm extends Form
{

    /**
     * form resources needed, for example to fill select element options
     * @var array
     */
    protected $resources;

    /**
     * form constructor
     * @param string $name
     * @param array $resources
     */
    public function __construct($name = 'search_product', $resources = [])
    {
        // set the form's name
        parent::__construct($name);

        $this->resources = $resources;

        // set the method
        $this->setAttribute('method', 'post');

        // define elements
        // Status
        $this->add(
            array(
                'name'       => 'status',
                'type'       => 'Zend\Form\Element\Select',
                'value'      => Objects::PRODUCT_STATUS_SELLING,
                'options'    => array(
                    'label'         => false,
                    'value_options' => $this->getStatuses()
                ),
                'attributes' => array(
                    'class' => 'form-control',
                ),
            )
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
            array(
                'name'       => 'address',
                'type'       => 'Zend\Form\Element\Text',
                'options'    => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'class'       => 'form-control',
                    'placeholder' => 'Apartment name, Address, Postal Code or Unit Number',
                ),
            )
        );
        // Create date
        $this->add(
            array(
                'name'       => 'createdDate',
                'type'       => 'Zend\Form\Element\Text',
                'options'    => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'class'       => 'form-control pull-right',
                    'placeholder' => 'Created Date',
                    'id'          => 'createdDate',
                ),
            )
        );
    }

    /**
     * Get Apartment Statuses to populate Select element
     *
     * @return array
     */
    public function getStatuses()
    {
        $statusesArray = array(
            0                                            => "-- All Statuses --",
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

    public function getProductGroups()
    {
        $concierges = $this->resources['concierges'];

        $accommodationGroupsArray = array(
            0 => "-- All Groups --"
        );
        foreach ($concierges as $concierge) {
            $accommodationGroupsArray[$concierge->getId()] = $concierge->getNameWithApartelUsage();
        }

        return $accommodationGroupsArray;
    }

}
