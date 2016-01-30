<?php

namespace Finance\Form;

use DDD\Service\Booking;
use DDD\Service\User;
use DDD\Service\Partners as PartnerService;
use Library\Constants\Constants;
use Library\Form\FormBase;
use Library\Utility\Debug;
use Zend\Db\ResultSet\ResultSet;
use Zend\ServiceManager\ServiceLocatorInterface;

class Charge extends FormBase {
	protected $resources = [];

    /**
     * @param int|null|string $resources
     * @param array $addons
     * @param ResultSet|array[] $cities
     */
    public function __construct($resources, $addons, $cities) {
		parent::__construct('charge');
		$this->setAttribute('method', 'post');

        $this->resources = $resources;

		$this->add(array(
			'name' => 'charge_date',
			'attributes' => array(
				'type' => 'text',
                'placeholder' => 'Charge Date',
				'class' => 'form-control daterange pull-right',
				'id' => 'charge_date',
			),
		));

        // Reservation number
        $this->add(array(
            'name' => 'res_number',
            'type' => 'Zend\Form\Element\Text',
            'options' => array(
                'label' => false,
            ),
            'attributes' => array(
                'placeholder' => 'Reservation Number',
                'class' => 'form-control',
                'id' => 'res_number'
            ),
        ));

        // Status
        $this->add(array(
            'name' => 'status',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => false,
                'value_options' => $this->getStatuses(),
            ),
            'attributes' => array(
                'value' => 1,
                'class' => 'form-control',
                'id' => 'status'
            ),
        ));

        // Booking date
        $this->add(
            array(
                'name' => 'booking_date',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Booking Date',
                    'class' => 'form-control daterange pull-right',
                    'id' => 'booking_date'
                ),
            )
        );

        // Arrival date
        $this->add(
            array(
                'name' => 'arrival_date',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Arrival Date',
                    'class' => 'form-control daterange pull-right',
                    'id' => 'arrival_date',
                ),
            )
        );

        // Departure date
        $this->add(
            array(
                'name' => 'departure_date',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Departure Date',
                    'class' => 'form-control daterange pull-right',
                    'id' => 'departure_date',
                ),
            )
        );

        // Origin Product id
        $this->add(array(
            'name' => 'product_id',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'id'    => 'product_id',
                'class' => 'form-control'
            ),
            'options' => array(
                'label' => false,
            ),
        ));

        // Origin product_type
        $this->add(array(
            'name' => 'product_type',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'id' => 'product_type',
                'class' => 'form-control',
                'value' => 0

            ),
            'options' => array(
                'label' => false,
            ),
        ));

        // Assigned Product address
        $this->add([
            'name'    => 'assigned_product',
            'type'    => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'id'           => 'assigned_product',
                'placeholder'  => 'Assigned Apartment name, Address, Postal Code or Unit Number',
                'class'        => 'form-control'
            ],
        ]);

        // Assigned Product id
        $this->add(array(
            'name' => 'assigned_product_id',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'id'    => 'assigned_product_id',
                'class' => 'form-control'
            ),
            'options' => array(
                'label' => false,
            ),
        ));

        // Assigned product_type
        $this->add(array(
            'name' => 'assigned_product_type',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'id' => 'assigned_product_type',
                'class' => 'form-control',
                'value' => 0

            ),
            'options' => array(
                'label' => false,
            ),
        ));

        // Origin Product address
        $this->add([
            'name'    => 'product',
            'type'    => 'Zend\Form\Element\Text',
            'options' => [
                'label' => false,
            ],
            'attributes' => [
                'id'           => 'product',
                'placeholder'  => 'Origin Apartment name, Address, Postal Code or Unit Number',
                'class'        => 'form-control'
            ],
        ]);

        // Partners
        $this->add(array(
            'name' => 'partner_id',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => false,
                'value_options' => $this->getPartners(),
            ),
            'attributes' => array(
                'class' => 'form-control'
            ),
        ));

        // Payment Model
        $this->add(
            array(
                'name' => 'payment_model',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'label' => false,
                    'value_options' => $this->getBusinessModels(),
                ),
                'attributes' => array(
                    'value' => 0,
                    'class' => 'form-control'
                ),
            )
        );

        // No collection
        $this->add(array(
            'name' => 'no_collection',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => false,
                'value_options' => [
                    1 => '-- All Standings --',
                    2 => 'No Collections',
                    3 => 'Collections',
                ],
            ),
            'attributes' => array(
                'value' => 0,
                'class' => 'form-control'
            ),
        ));

        // Cities
        $this->add(array(
            'name' => 'city',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => false,
                'value_options' => $this->getCityListAsArray($cities),
            ),
            'attributes' => array(
                'class' => 'form-control'
            ),
        ));

        // Apartment Group
        $this->add(
            array(
                'name' => 'group',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'label' => false,
                    'value_options' => $this->getApartmentGroups()
                ),
                'attributes' => array(
                    'class' => 'form-control',
                ),
            )
        );

        // Checkboxes
        foreach ($addons as $typeId => $typeName) {
            $this->add(array(
                'name' => 'charge_type[' . $typeId . ']',
                'type' => 'Zend\Form\Element\Hidden',
                'attributes' => array(
                    'data-id' => 'charge_type_' . $typeId,
                    'class' => 'hide checkbox-trigger',
                    'value' => 1,
                ),
            ));
        }

        $objectData = new \ArrayObject();
        $objectData['apartel_id'] = -2;

        $this->bind($objectData);
    }

    private function getPartners() {
        $partners = ['-- All Partners --'];

        foreach ($this->resources['partners'] as $partner) {
            $partners[$partner->getGid()] = $partner->getPartnerName();
        }

        return $partners;
    }

    public function getApartmentGroups()
    {
        $groups = $this->resources['groups'];

        $groupsArray = ['-- All Groups --'];

        foreach ($groups as $group) {
            $groupsArray[$group->getId()] = $group->getName();
        }

        return $groupsArray;
    }

    public function getApartelList()
    {
        $apartels = $this->resources['apartels'];

        $apartelsArray = [
            -2 => '-- Normal & Apartel --',
            0 => '-- Non Apartel --',
            -1 => '-- Unknown Apartel --'
        ];
        foreach ($apartels as $apartel) {
            $apartelsArray[$apartel->getId()] = $apartel->getName();
        }

        return $apartelsArray;
    }

    private function getStatuses() {
        return Booking::$bookingStatusForChart;
    }

    /**
     * Options to populate "Business Model" select element
     * @return array
     */
    private function getBusinessModels() {
        return [0 => '-- All Models --'] + PartnerService::getBusinessModels();
    }

    /**
     * @param ResultSet|array[] $cities
     * @return array
     */
    private function getCityListAsArray($cities)
    {
        $cityList = ['-- All Cities --'];

        if ($cities->count()) {
            foreach ($cities as $city) {
                $cityList[$city['id']] = $city['name'];
            }
        }

        return $cityList;
    }
}
