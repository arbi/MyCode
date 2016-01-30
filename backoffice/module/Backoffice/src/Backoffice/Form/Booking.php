<?php

namespace Backoffice\Form;

use DDD\Domain\Booking\BookingTicket;
use DDD\Service\Booking\BookingTicket as ReservationTicketService;
use DDD\Domain\Booking\Statuses;
use Library\Form\FormBase;
use Library\Constants\Objects;
use DDD\Service\Partners as PartnerService;

class Booking extends FormBase
{

    /**
     * @param BookingTicket|bool $data
     * @param array $options
     */
    public function __construct($data, $options)
    {
        parent::__construct('booking-form');
        $this->setAttributes(array(
            'action' => 'booking/edit',
            'method' => 'post',
            'class'  => 'form-horizontal',
            'id'     => 'booking-form'
        ));
        $this->setName('booking-form');

        /**
         * @todo use constants
         */
        $current_finance_booked_state = ($data->getArrivalStatus() == 3 ? 2 : $data->getArrivalStatus());

        $this->add(array(
            'name'       => 'ginosi_collect_debt_customer_currency',
            'attributes' => array(
                'type' => 'hidden',
                'id'   => 'ginosi_collect_debt_customer_currency',
            ),
        ));

        $this->add(array(
            'name'       => 'ginosi_collect_debt_apartment_currency',
            'attributes' => array(
                'type' => 'hidden',
                'id'   => 'ginosi_collect_debt_apartment_currency',
            ),
        ));

        $this->add(array(
            'name'       => 'partner_collect_debt_customer_currency',
            'attributes' => array(
                'type' => 'hidden',
                'id'   => 'partner_collect_debt_customer_currency',
            ),
        ));

        $this->add(array(
            'name'       => 'partner_collect_debt_apartment_currency',
            'attributes' => array(
                'type' => 'hidden',
                'id'   => 'partner_collect_debt_apartment_currency',
            ),
        ));

        $this->add(array(
            'name'       => 'guest_name',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'guest_name',
                'maxlength' => 150,
            ),
        ));

        $this->add(array(
            'name'       => 'guest_last_name',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'guest_lastname',
                'maxlength' => 150,
            ),
        ));

        $this->add(array(
            'name'       => 'guest_email',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'      => 'Zend\Form\Element\Email',
                'class'     => 'form-control',
                'id'        => 'guest_email',
                'maxlength' => 255,
            ),
        ));

        $this->add(array(
            'name'       => 'second_guest_email',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'      => 'Zend\Form\Element\Email',
                'class'     => 'form-control',
                'id'        => 'second_guest_email',
                'maxlength' => 255,
            ),
        ));

        $this->add(array(
            'name'       => 'selected-email',
            'attributes' => array(
                'type'  => 'hidden',
                'id'    => 'selected-email',
                'value' => $data->getGuestEmail()
            ),
        ));

        $countries = array();
        foreach ($options['countries'] as $row) {
            $countries[0]             = 'Undetected Country';
            $countries[$row->getId()] = $row->getName();
        }

        $this->add(array(
            'name'       => 'guest_country',
            'options'    => array(
                'label'         => '',
                'value_options' => $countries
            ),
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id'    => 'guest_country',
                'class' => 'form-control'
            ),
        ));

        $this->add(array(
            'name'       => 'guest_city',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'guest_city',
                'maxlength' => 150,
            ),
        ));

        $this->add(array(
            'name'       => 'guest_address',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'guest_address',
                'maxlength' => 150,
            ),
        ));

        $this->add(array(
            'name'       => 'guest_zipcode',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'guest_zipcode',
                'maxlength' => 50,
            ),
        ));

        $this->add(array(
            'name'       => 'guest_phone',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'guest_phone',
                'maxlength' => 50,
            ),
        ));

        $this->add(array(
            'name'       => 'guest_travel_phone',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'guest_travel_phone',
                'maxlength' => 50,
            ),
        ));

        $partners = array();
        foreach ($options['partners'] as $row) {
            $partners[$row->getGid()] = $row->getPartnerName();
        }

        $this->add(array(
            'name'       => 'booking_partners',
            'options'    => array(
                'label'         => '',
                'value_options' => $partners
            ),
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id'    => 'booking_partners',
                'class' => 'form-control'
            ),
        ));


        $this->add(array(
            'name'       => 'booking_affiliate_reference',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'booking_affiliate_reference',
                'maxlength' => 150,
            ),
        ));

        $this->add(array(
            'name'       => 'overbooking_status',
            'options'    => array(
                'label'         => '',
                'value_options' => ReservationTicketService::$overbookingOptions
            ),
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id'    => 'overbooking_status',
                'class' => 'form-control'
            ),
        ));

        $apartels = ['-1' => 'Unknown', '0' => ' Non Apartel'];
        foreach ($options['apartels'] as $row) {
            $apartels[$row->getId()] = $row->getName();
        }

        $this->add([
            'name'       => 'apartel_id',
            'options'    => [
                'label'         => 'Apartel',
                'value_options' => $apartels,
            ],
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'apartel_id',
                'class' => 'form-control'
            ]
        ]);


        $occupancy = array_combine(
            range(1, $data->getApartmentCapacity()),
            range(1, $data->getApartmentCapacity())
        );

        if ($data->getOccupancy() > $data->getApartmentCapacity()) {
            $occupancy[$data->getOccupancy()] = $data->getOccupancy();
        }

        $this->add([
            'name'       => 'occupancy',
            'options'    => [
                'label'         => 'Occupancy',
                'value_options' => $occupancy,
            ],
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'occupancy',
                'class' => 'form-control'
            ]
        ]);

        $this->add(array(
            'name'       => 'booking_arrival_time',

            'type'       => 'Zend\Form\Element\Text',
            'attributes' => array(
                'id'    => 'booking_arrival_time',
                'class' => 'form-control',
            ),
        ));

        /** @var Statuses|bool $row */
        $statuses = array();
        foreach ($options['statuses'] as $row) {
            // "Canceled Unknown" status can be shown only if ticket status set "Unknown"
            if ($data->getStatus() != \DDD\Service\Booking::BOOKING_STATUS_CANCELLED_PENDING) {
                if ($row->getId() == \DDD\Service\Booking::BOOKING_STATUS_CANCELLED_PENDING) {
                    continue;
                }
            }

            $statuses[$row->getId()] = $row->getName();
        }

        $this->add(array(
            'name'       => 'booking_statuses',
            'options'    => array(
                'label'         => '',
                'value_options' => $statuses
            ),
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id'    => 'booking_statuses',
                'value' => 1,
                'class' => 'form-control'
            ),
        ));

        $this->add(array(
            'name'       => 'charge_comment',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'  => 'textarea',
                'class' => 'span5',
                'rows'  => '4',
                'id'    => 'charge_comment',
            ),
        ));

        $this->add(array(
            'name'       => 'booking_id',
            'attributes' => array(
                'type' => 'hidden',
                'id'   => 'booking_id',
            ),
        ));

        $this->add(array(
            'name'       => 'booking_res_number',
            'attributes' => array(
                'type' => 'hidden',
                'id'   => 'booking_res_number',
            ),
        ));

        $this->add(array(
            'name'       => 'finance_valid_card',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => array(
                'label'         => '',
                'value_options' => Objects::getCreditCardStatuses(),
            ),
            'attributes' => array(
                'id'       => 'finance_valid_card',
                'class'    => 'form-control',
                'value'    => $data->getFunds_confirmed(),
                'disabled' => isset($options['hasCreditCardViewer']) ? false : true,
            ),
        ));

        $key_instructions = array(
            'id'                 => 'finance_key_instructions',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0,
            'class'              => 'margin-top-10'
        );
        if ($data->isKiViewed() == 1) {
            $key_instructions['checked'] = 'checked';
        }

        $this->add(array(
            'name'       => 'finance_key_instructions',
            'options'    => array(
                'label' => '',
            ),
            'type'       => 'Zend\Form\Element\Checkbox',
            'attributes' => $key_instructions
        ));

        $reservation_settled = array(
            'id'                 => 'finance_reservation_settled',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0,
            'class'              => 'margin-top-10'
        );
        if ($data->getPayment_settled() == 1) {
            $reservation_settled['checked'] = 'checked';
        }
        if (!$options['hasFinanceRole']) {
            $reservation_settled['disabled'] = true;
        }
        $this->add(array(
            'name'       => 'finance_reservation_settled',
            'options'    => array(
                'label' => '',
            ),
            'type'       => 'Zend\Form\Element\Checkbox',
            'attributes' => $reservation_settled
        ));

        $cccAVerified = array(
            'id'                 => 'ccca_verified',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0,
            'class'              => 'margin-top-10'
        );
        if ($data->getCccaVerified() == 1) {
            $cccAVerified['checked'] = 'checked';
        }
        $this->add(array(
            'name'       => 'ccca_verified',
            'options'    => array(
                'label' => '',
            ),
            'type'       => 'Zend\Form\Element\Checkbox',
            'attributes' => $cccAVerified
        ));

        $paid_affiliate = array(
            'id'                 => 'finance_paid_affiliate',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0,
            'class'              => 'margin-top-10'
        );

        if ($data->getPartnerId() == 1) {
            $paid_affiliate['disabled'] = 'disabled';
        } elseif ($data->getPartnerSettled() == 1) {
            $paid_affiliate['checked'] = 'checked';
        }

        $this->add(array(
            'name'       => 'finance_paid_affiliate',
            'options'    => array(
                'label' => '',
            ),
            'type'       => 'Zend\Form\Element\Checkbox',
            'attributes' => $paid_affiliate
        ));

        /**
         *
         */
        $this->add(array(
            'name'       => 'partner_paid_expense_id',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'        => 'text',
                'class'       => 'form-control width-100 margin-left-5',
                'id'          => 'partner_paid_expense_id',
                'placeholder' => 'Expense ID'
            ),
        ));

        $no_collection = array(
            'id'                 => 'finance_no_collection',
            'use_hidden_element' => false,
            'checked_value'      => 1,
            'unchecked_value'    => 0,
            'class'              => 'margin-top-10',
        );

        if ($data->getNo_collection() == 1) {
            $no_collection['checked'] = 'checked';
        }

        $this->add(array(
            'name'       => 'finance_no_collection',
            'options'    => array(
                'label' => '',
            ),
            'type'       => 'Zend\Form\Element\Checkbox',
            'attributes' => $no_collection
        ));

        $this->add(array(
            'name'       => 'finance_booked_state',
            'options'    => array(
                'label'         => '',
                'value_options' => ReservationTicketService::$arrivalStatuses
            ),
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id'    => 'finance_booked_state',
                'class' => 'form-control'
            ),
        ));

        $this->add(array(
            'name'       => 'finance_booked_state_changed',
            'attributes' => array(
                'id'           => 'finance_booked_state_changed',
                'data-current' => $current_finance_booked_state,
                'value'        => 0,
                'type'         => 'hidden',
            ),
        ));

        $this->add(array(
            'name'       => 'acc_currency_rate',
            'attributes' => array(
                'type' => 'hidden',
                'id'   => 'acc_currency_rate',
            ),
        ));

        $this->add(array(
            'name'       => 'acc_currency_sign',
            'attributes' => array(
                'type' => 'hidden',
                'id'   => 'acc_currency_sign',
            ),
        ));

        $this->add(array(
            'name'       => 'customer_currency_rate',
            'attributes' => array(
                'type' => 'hidden',
                'id'   => 'customer_currency_rate',
            ),
        ));

        $this->add(array(
            'name'       => 'booking_ginosi_comment',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'        => 'textarea',
                'class'       => 'form-control',
                'rows'        => '3',
                'id'          => 'booking_ginosi_comment',
                'placeholder' => 'Write comments here',
            ),
        ));

        $teams = [0 => '-- Select Team --'];
        foreach ($options['teams'] as $row) {
            $teams[$row->getId()] = $row->getName();
        }

        $this->add([
            'name'       => 'booking_ginosi_comment_team',
            'options'    => [
                'label'         => '',
                'value_options' => $teams
            ],
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'booking_ginosi_comment_team',
                'class' => 'form-control'
            ],
        ]);

        $this->add([
            'name'       => 'booking_ginosi_comment_frontier',
            'type'       => 'Zend\Form\Element\Checkbox',
            'attributes' => [
                'id'    => 'booking_ginosi_comment_frontier'
            ],
        ]);

        $this->add(array(
            'name'       => 'housekeeping_comment',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'        => 'textarea',
                'class'       => 'form-control',
                'placeholder' => 'Write housekeeping comments here',
                'rows'        => '3',
                'id'          => 'housekeeping_comment',
            ),
        ));

        $this->add(array(
            'name'       => 'booking_ginosi_comment_view',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'     => 'textarea',
                'class'    => 'span10',
                'rows'     => '7',
                'readonly' => 'readonly',
                'id'       => 'booking_ginosi_comment_view'
            ),
        ));

        $this->add(array(
            'name'       => 'housekeeping_comment_view',
            'options'    => array(
                'label' => '',
            ),
            'attributes' => array(
                'type'     => 'textarea',
                'style'    => 'width: 535px;',
                'rows'     => '7',
                'readonly' => 'readonly'
            ),
        ));


        $this->add(array(
            'name'       => 'model',
            'options'    => array(
                'label'         => '',
                'value_options' => $this->getModel($options['partner_data']),
            ),
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => array(
                'id'    => 'model',
                'class' => 'form-control',
            ),
        ));

        $this->add(array(
            'name' => 'res-dates',
            'attributes' => array(
                'type' => 'text',
                'class' => 'form-control',
                'id' => 'res-dates',
            ),
        ));

       if(is_object($data)) {
            $objectData = new \ArrayObject();
            $objectData['guest_name']                  = $data->getGuestFirstName();
            $objectData['guest_last_name']             = $data->getGuestLastName();
            $objectData['guest_email']                 = $data->getGuestEmail();
            $objectData['second_guest_email']          = $data->getSecondaryEmail();
            $objectData['guest_country']               = $data->getGuestCountryId();
            $objectData['guest_city']                  = $data->getGuestCityName();
            $objectData['guest_address']               = $data->getGuestAddress();
            $objectData['guest_zipcode']               = $data->getGuestZipCode();
            $objectData['guest_phone']                 = $data->getGuestPhone();
            $objectData['booking_partners']            = $data->getPartnerId();
            $objectData['booking_affiliate_reference'] = $data->getPartnerRef();
            $objectData['booking_id']                  = $data->getId();
            $objectData['booking_res_number']          = $data->getResNumber();
            $objectData['finance_booked_state']        = ($data->getArrivalStatus() == 3 ? 2: $data->getArrivalStatus());
            $objectData['acc_currency_rate']           = $data->getAcc_currency_rate();
            $objectData['acc_currency_sign']           = $data->getAcc_currency_sign();
            $objectData['customer_currency_rate']      = $data->getCurrency_rate();
            $objectData['model']                       = $data->getModel();
            $objectData['guest_travel_phone']          = $data->getGuestTravelPhone();
            $objectData['booking_statuses']            = $data->getStatus();
            $objectData['overbooking_status']          = (int)$data->getOverbookingStatus();
            $objectData['apartel_id']                  = $data->getApartelId();
            $objectData['occupancy']                   = $data->getOccupancy();

            if (is_null($data->getGuestArrivalTime())) {
                $objectData['booking_arrival_time'] = date('H:i', strtotime($options['apartment']['check_in']));
            } else {
                $objectData['booking_arrival_time'] = date('H:i', strtotime($data->getGuestArrivalTime()));
            }

            $this->bind($objectData);
       }
    }

    /**
     * @param $partner
     * @return array
     */
    private function getModel($partner)
    {
        $ticketModel = [PartnerService::BUSINESS_MODEL_GINOSI_COLLECT => PartnerService::getBusinessModels()[PartnerService::BUSINESS_MODEL_GINOSI_COLLECT]];
        // get partner model

        if ($partner->getBusinessModel() && isset(PartnerService::getBusinessModels()[$partner->getBusinessModel()])) {
            $ticketModel += [$partner->getBusinessModel() => PartnerService::getBusinessModels()[$partner->getBusinessModel()]];
        }
        return $ticketModel;
    }

    /**
     *
     * @param string $guestArrivalTime
     * @return array
     */
    private function getArrivalTimeValues($guestArrivalTime)
    {
        $arrivalTime = date('H:i', strtotime($guestArrivalTime));

        if (in_array($arrivalTime, Objects::getTime())) {
            $result = array_merge([''], Objects::getTime());
        } else {
            $result = array_merge(
                [
                    '',
                    $arrivalTime => $arrivalTime
                ],
                Objects::getTime()
            );
        }

        return $result;
    }
}
