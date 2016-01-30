<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

/**
 * Show Reservation if  Settled = 1 AND Partner Settled = 0  AND Partner Balance < 0
 * @author Tigran Petrosyan
 */
final class CollectFromPartnerReservations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'sortable' => false, 'width' => '70'],
            ['name' => 'status', 'title' => 'ST', 'sortable' => true, 'width' => 30,'class'=>'hidden-xs'],
            ['name' => 'booking_date', 'title' => 'Booking', 'sortable' => true, 'width' => 90,'class'=>'hidden-xs'],
            ['name' => 'departure_date', 'title' => 'Check-out', 'sortable' => true, 'width' => 90,'class'=>'hidden-xs'],
            ['name' => 'apartment', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'partner', 'title' => 'Partner', 'sortable' => true],
            ['name' => 'partner_balance', 'title' => 'Partner Balance', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-collect-from-partner-reservations';
    }
}
