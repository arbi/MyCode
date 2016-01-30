<?php
namespace Backoffice\Form;

use Zend\Form\Form;
use Library\Constants\Constants;
use DDD\Service\Booking;
use DDD\Service\Booking\BookingTicket as ReservationTicket;
use DDD\Service\Partners as PartnerService;

/**
 * Reservations filter form
 * @final
 * @category backoffice
 * @package backoffice_forms
 * @subpackage backoffice_forms_filterforms
 *
 * @author Tigran Petrosyan
 */
final class SearchReservationForm extends Form
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
    public function __construct($name = 'search_reservation', $resources = array()) {
        // set the form's name
        parent::__construct($name);

            $this->resources = $resources;

        // set the method
        $this->setAttribute('method', 'post');

        // Reservation number
        $this->add(
            array(
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
            )
        );

        // Status
        $this->add(
            array(
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
            )
        );

        // Arrival Status
        $this->add(
            array(
                'name' => 'arrival_status',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'label' => false,
                    'value_options' => $this->getArrivalStatusOptions(),
                ),
                'attributes' => array(
                    'class' => 'form-control',
                    'id' => 'arrival_status'
                ),
            )
        );

        // State (Overbooking Status)
        $this->add(
            array(
                'name' => 'overbooking_status',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'label' => false,
                    'value_options' => $this->getStateOptions(),
                ),
                'attributes' => array(
                    'class' => 'form-control',
                    'id' => 'overbooking_status'
                ),
            )
        );

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
                    'class' => 'form-control pull-right',
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
                    'class' => 'form-control pull-right',
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
                    'class' => 'form-control pull-right',
                    'id' => 'departure_date',
                ),
            )
        );

        // Guest First Name
        $this->add(
            array(
                'name' => 'guest_first_name',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'First Name',
                    'class' => 'form-control'
                ),
            )
        );

        // Guest Last Name
        $this->add(
            array(
                'name' => 'guest_last_name',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Last Name',
                    'class' => 'form-control'
                ),
            )
        );

        // Guest Phone
        $this->add(
            array(
                'name' => 'guest_phone',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Phone',
                    'class' => 'form-control',
                    'id' => 'guest_phone'
                ),
            )
        );

        // Guest Email
        $this->add(
            array(
                'name' => 'guest_email',
                'type' => 'Zend\Form\Element\Email',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Email',
                    'class' => 'form-control',
                    'id' => 'guest_email'
                ),
            )
        );

        // Guest secondary Email
        $this->add(
            array(
                'name' => 'guest_secondary_email',
                'type' => 'Zend\Form\Element\Email',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Secondary Email',
                    'class' => 'form-control',
                    'id' => 'guest_secondary_email'
                ),
            )
        );

        // Guest Country
        $this->add(
            array(
                'name' => 'guest_country',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'id' => 'guest_country',
                    'placeholder' => 'Country',
                    'class' => 'form-control'
                ),
            )
        );

        // Guest country id
        $this->add(array(
            'name' => 'guest_country_id',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'id' => 'guest_country_id',
                    'class' => 'form-control'
            ),
            'options' => array(
                'label' => false,
            ),
        ));

        // Reservation types
        $this->add(
            array(
                'name' => 'apartel_id',
                'type' => 'Zend\Form\Element\Select',
                'options' => array(
                    'label' => false,
                    'value_options' => $this->getApartelList(),
                ),
                'attributes' => array(
                    'value' => -1,
                    'class' => 'form-control'
                ),
            )
        );

        // Apartment Location
        $this->add(array(
            'name' => 'apt_location',
            'type' => 'Zend\Form\Element\Text',
            'options' => array(
                'label' => false,
            ),
            'attributes' => array(
                'id' => 'apt_location',
                'placeholder' => 'Apartment Location',
                'class' => 'form-control'
            ),
        ));

        // Product country id
        $this->add(array(
            'name' => 'apt_location_id',
            'type' => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'id' => 'apt_location_id',
                'class' => 'form-control'
            ),
            'options' => array(
                'label' => false,
            ),
        ));

        // Origin Product address
        $this->add(
            [
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
            ]
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
        $this->add(
            [
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
            ]
        );

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

        // Affiliate Reference
        $this->add(array(
            'name' => 'partner_reference',
            'type' => 'Zend\Form\Element\Text',
            'options' => array(
                'label' => false,
            ),
            'attributes' => array(
                'placeholder' => 'Affiliate Reference',
                'class' => 'form-control'
            ),
        ));

        // Channel res id
        $this->add(array(
            'name' => 'channel_res_id',
            'type' => 'Zend\Form\Element\Text',
            'options' => array(
                'label' => false,
            ),
            'attributes' => array(
                'placeholder' => 'Channel Res. ID',
                'class' => 'form-control'
            ),
        ));
        // Ginosi Comment
        $this->add(array(
            'name' => 'comment',
            'type' => 'Zend\Form\Element\Text',
            'options' => array(
                'label' => false,
            ),
            'attributes' => array(
                'placeholder' => 'Comment',
                'class' => 'form-control'
            ),
        ));


        // Room Count
        $this->add(array(
            'name' => 'rooms_count',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => false,
                'value_options' => [
                    0 => '- All Room Types -',
                    1 => 'Studio',
                    2 => 'One bedroom',
                    3 => 'Two + bedroom',
                ],
            ),
            'attributes' => array(
                'value' => 0,
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

        // Transaction Amount
        $this->add(
            array(
                'name' => 'transaction_amount',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Transaction Amount',
                    'class' => 'form-control'
                ),
            )
        );

        // Charge Authorization
        $this->add(
            array(
                'name' => 'charge_auth_number',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                    'placeholder' => 'Transaction Auth. Code',
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

        // Apartment Group
        $this->add(
            [
                'name' => 'group',
                'type' => 'Zend\Form\Element\Text',
                'options' => array(
                    'label' => false,
                ),
                'attributes' => array(
                        'class'       => 'form-control',
                        'placeholder' => 'All Groups, Country',
                        'id'          => 'group',
                ),
            ]
        );

        $this->add(
            [
                'name' => 'group_id',
                'type' => 'Zend\Form\Element\Hidden',
                'attributes' => [
                    'id'    => 'group_id',
                    'value' => '0'
                ],
            ]
        );

        $objectData = new \ArrayObject();
        $objectData['apartel_id'] = -2;

        $this->bind($objectData);
    }

    private function getPartners() {
        $partners = array(
            "0" => "-- All Partners --"
        );
        foreach ($this->resources['partners'] as $partner) {
            $partners[$partner->getGid()] = $partner->getPartnerName();
        }

        return $partners;
    }

	public function getApartmentGroups()
    {
		$groups = $this->resources['groups'];

        $groupsArray = array(
			0 => "-- All Groups --"
		);
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
            0  => '-- Non Apartel --',
            -1 => '-- Unknown Apartel --'
		];
		foreach ($apartels as $apartel) {
            $apartelsArray[$apartel->getId()] = $apartel->getName();
		}

		return $apartelsArray;
	}

    private function getStatuses() {
        $statuses = [
            Booking::BOOKING_STATUS_ALL                    => "-- All Statuses --",
            Booking::BOOKING_STATUS_BOOKED                 => "Booked",
            Booking::BOOKING_STATUS_CANCELLED_MOVED        => "Canceled (Moved)",
            Booking::BOOKING_STATUS_CANCELLED_BY_CUSTOMER  => "Canceled by Customer",
            Booking::BOOKING_STATUS_CANCELLED_BY_GINOSI    => "Canceled by Ginosi",
            Booking::BOOKING_STATUS_CANCELLED_EXCEPTION    => 'Canceled by Exception',
            Booking::BOOKING_STATUS_CANCELLED_INVALID      => "Canceled (Invalid)",
            Booking::BOOKING_STATUS_CANCELLED_TEST_BOOKING => "Canceled (Test Booking)",
            Booking::BOOKING_STATUS_CANCELLED_PENDING      => "Cancelled (Pending)",
            Booking::BOOKING_STATUS_CANCELLED_UNWANTED     => "Canceled (Unwanted)",
            Booking::BOOKING_STATUS_CANCELLED_FRAUDULANT   => "Canceled (Fraudulent)",
            Booking::BOOKING_STATUS_CANCELLED_NOSHOW       => "Canceled (No Show)",
            Constants::NOT_BOOKED_STATUS                   => "Canceled",
        ];

        return $statuses;
    }

    private function getArrivalStatusOptions()
    {
        $arrivalStatusOptions = [-1 => '-- All Arrival Statuses --'] + ReservationTicket::$arrivalStatuses;

        return $arrivalStatusOptions;
    }

    private function getStateOptions()
    {
        $stateOptions = [-1 => '-- All States --'] + Booking\BookingTicket::$overbookingOptions;

        return $stateOptions;
    }

    /**
     * Options to populate "Business Model" select element
     * @return array
     */
    private function getBusinessModels() {
        return [0 => '-- All Models --'] + PartnerService::getBusinessModels();
    }
}
