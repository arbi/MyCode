<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

/**
 * Show all those reservations which departure date + 5 AND NOT marked as Settled
 * @author Tigran Petrosyan
 */
final class ToBeSettledReservations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'sortable' => false, 'width' => '70'],
            ['name' => 'status', 'title' => 'ST', 'sortable' => true, 'width' => '32', 'class'=>'hidden-xs'],
            ['name' => 'departure_date', 'title' => 'Check-out', 'sortable' => true, 'width' => '90','class'=>'hidden-xs'],
            ['name' => 'apartment', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'guest', 'title' => 'Guest', 'sortable' => false, 'class'=>'hidden-xs'],
            ['name' => 'guest_balance', 'title' => 'Guest Balance', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'partner_balance', 'title' => 'Partner Balance', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '105']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-to-be-settled-reservations';
    }
}
