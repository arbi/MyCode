<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

/**
 * Show all those reservations which CC card is
 * Not marked as "Valid" AND is NOT marked as "No Collection" AND is NOT marked as "Settled" AND NOT ( Customer Balance >= 0 AND Departure Date is in the past)
 * @author Tigran Petrosyan
 */
final class ValidateCCReservations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'sortable' => false, 'width' => '70'],
            ['name' => 'booking', 'title' => 'Booking', 'sortable' => true, 'width' => '90','class'=>'hidden-xs'],
            ['name' => 'checkin', 'title' => 'Check-in', 'sortable' => true, 'width' => '90','class'=>'hidden-xs'],
            ['name' => 'acc_name', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'guest', 'title' => 'Guest', 'sortable' => false],
            ['name' => 'guest_balance', 'title' => 'Guest Balance', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'last_agent', 'title' => 'Last Agent', 'sortable' => false,'class'=>'hidden-xs'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '40']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-validate-cc-reservations';
    }
}
