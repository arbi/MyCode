<?php
namespace Venue\Form;

use Zend\Form\Form;
use DDD\Service\Venue\Venue as VenueService;

/**
 * Class    VenueForm
 * @package Lock\Form
 * @author  Harut Grigoryan
 */
class VenueForm extends Form
{
	/**
     * @param string $name
     * @param array  $userList
     * @param array  $currencies
     * @param array  $cities
     */
    public function __construct( $name = 'venue-form', $userList = [], $currencies = [], $cities = [])
    {
        parent::__construct($name);
        $this->setAttribute('method', 'post');
        $this->setAttribute('class', 'form-horizontal');
        $this->setAttribute('id', 'venue-form');

        $this->add([
            'name'       => 'id',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);

        $this->add([
            'name' => 'name',
            'type' => 'Zend\Form\Element\Text',
            'options'   => [
                'label' => 'Name',
            ],
            'attributes'    => [
                'id' => 'name',
                'class' => 'form-control',
            ],
        ]);

        // generate currency list
        $currencyList = [];
        foreach ($currencies as $currencyID => $currencyCode) {
            $currencyList[$currencyID] = $currencyCode;
        }

        $this->add([
            'name' => 'currencyId',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label'         => 'Currency',
                'value_options' => $currencyList
            ],
            'attributes' => [
                'id'    => 'currencyId',
                'class' => 'form-control',
                'data-placeholder'  => 'Choose currency',
            ],
        ]);

        // generate cities list
        $citiesList    = [];
        foreach ($cities as $city) {
            $citiesList[$city->getId()] = $city->getCity();
        }

        $this->add([
            'name' => 'cityId',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label'         => 'City',
                'value_options' => $citiesList
            ],
            'attributes' => [
                'id' => 'cityId',
                'class' => 'form-control',
                'data-placeholder'  => 'Choose city',
            ],
        ]);

        $this->add([
            'name' => 'thresholdPrice',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => 'Threshold Price'
            ],
            'attributes' => [
                'id'           => 'thresholdPrice',
                'class'        => 'form-control',
                'min'          => 0,
                'aria-invalid' => 'false',
            ],
        ]);

        $this->add([
            'name' => 'discountPrice',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => 'Discount Price'
            ],
            'attributes' => [
                'id'    => 'discountPrice',
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'perdayMinPrice',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => 'Per Day Min Price'
            ],
            'attributes' => [
                'id'    => 'perdayMinPrice',
                'class' => 'form-control',
            ],
        ]);

        $this->add([
            'name' => 'perdayMaxPrice',
            'type' => 'Zend\Form\Element\Text',
            'options' => [
                'label' => 'Per Day Max Price'
            ],
            'attributes' => [
                'id'    => 'perdayMaxPrice',
                'class' => 'form-control',
            ],
        ]);

        // generate member list
        $memberList = [];
        foreach ($userList as $member) {
            $memberList[$member['id']] =
                $member['firstname'] . ' ' .
                $member['lastname'];
        }

        $this->add([
            'name' => 'managerId',
            'type' => 'Zend\Form\Element\Select',
            'options' => [
                'label' => 'Manager',
                'value_options' => $memberList
            ],
            'attributes' => [
                'id'    => 'managerId',
                'class' => 'form-control',
                'data-placeholder'  => 'Choose manager',
            ],
        ]);

        $this->add(
            [
                'name' => 'cashierId',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'label'         => 'Cashier',
                    'value_options' => $memberList
                ),
                'attributes' => array(
                    'id'    => 'cashierId',
                    'class' => 'form-control',
                    'data-placeholder'  => 'Choose cashier',
                ),
            ]
        );

        // Venue Accept Order
        $this->add(
            [
                'name'       => 'acceptOrders',
                'type'       => 'Zend\Form\Element\Select',
                'options'    => [
                    'label'         => 'Accept Orders',
                    'value_options' => [
                        VenueService::VENUE_ACCEPT_ORDERS_ON  => 'On',
                        VenueService::VENUE_ACCEPT_ORDERS_OFF => 'Off',
                    ]
                ],
                'attributes' => [
                    'class' => 'form-control',
                    'id'    => 'acceptOrders',
                    'data-placeholder'  => 'Choose'
                ],
            ]
        );

        $this->add([
            'name'       => 'status',
            'attributes' => [
                'type' => 'hidden',
            ],
        ]);

        if (isset($data['supplier_data']) && !empty($data['supplier_data'])) {
            $supplierJsonStr = json_encode($data['supplier_data']);
        } else {
            $supplierJsonStr = '';
        }

        $this->add([
            'name' => 'account_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'                => 'account_id',
                'class'             => 'form-control',
                'data-placeholder'  => 'Choose supplier',
                'data-item'         => $supplierJsonStr,
                'data-id'           => (!empty($data)) ? $data->getSupplierId() : ''
            ],
            'options' => [
                'label' => 'Supplier',
                'disable_inarray_validator' => true,
            ]
        ]);

        $this->add([
            'name' => 'type',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'                => 'type',
                'class'             => 'form-control selectize',
                'data-placeholder'  => 'Choose type',
            ],
            'options' => [
                'label' => 'Type',
                'value_options' => VenueService::getVenueTypesForSelect(),
                'disable_inarray_validator' => true,
            ]
        ]);


        //Submit button
        $this->add([
            'name' => 'submit',
            'options' => [
                'primary' => true,
            ],
            'attributes' => [
                'value'             => 'Submit',
                'class'             => 'btn btn-primary pull-right col-xs-12 col-sm-2 margin-left-10',
                'data-loading-text' => 'Saving...'
            ],
        ]);
    }
}
