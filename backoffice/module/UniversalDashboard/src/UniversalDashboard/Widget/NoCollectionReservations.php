<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

/**
 * Show all those reservations which are marked as "No Collection" and are not marked as "Settled"
 * @author Tigran Petrosyan
 */
final class NoCollectionReservations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'sortable' => false,  'width' => '70'],
            ['name' => 'status', 'title' => 'ST', 'sortable' => true, 'width' => '30'],
            ['name' => 'arrival_date', 'title' => 'Check-in', 'sortable' => true, 'width' => '90','class'=>'hidden-xs'],
            ['name' => 'departure_date', 'title' => 'Check-out', 'sortable' => true, 'width' => '90','class'=>'hidden-xs'],
            ['name' => 'apartment', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'guest', 'title' => 'Guest', 'sortable' => false, 'class'=>'hidden-xs'],
            ['name' => 'valid_cc', 'title' => 'Credit', 'sortable' => true, 'width' => '7%','class'=>'hidden-xs'],
            ['name' => 'guest_balance', 'title' => 'Guest Balance', 'class' => 'text-right', 'sortable' => false, 'width' => '11%'],
            ['name' => 'last_agent', 'title' => 'Last Agent', 'sortable' => false,'class'=>'hidden-xs'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '40']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-no-collection-reservations';
    }
}
