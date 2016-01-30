<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

use DDD\Service\Booking;

/**
 * Show all those reservations which customer balance is < 0.00 and "no collection" is not marked and "settled" is not marked
 * @author Tigran Petrosyan
 */
final class PayToCustomerReservations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'sortable' => false, 'width' => '85'],
            ['name' => 'status', 'title' => 'ST', 'sortable' => true, 'width' => '35'],
            ['name' => 'arrival_date', 'title' => 'Check-in', 'sortable' => true, 'width' => '85', 'class'=>'hidden-xs'],
            ['name' => 'apartment', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'guest', 'title' => 'Guest', 'sortable' => false,'class'=>'hidden-xs'],
            ['name' => 'valid_cc', 'title' => 'Credit', 'sortable' => true,'class'=>'hidden-xs'],
            ['name' => 'waiting_for_cc', 'title' => 'Waiting for CC', 'width' => '100', 'sortable' => false],
            ['name' => 'guest_balance', 'title' => 'Guest Balance', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'last_agent', 'title' => 'Last Agent', 'sortable' => false,'class'=>'hidden-xs'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '40']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-pay-to-customer-reservations';
    }
}
