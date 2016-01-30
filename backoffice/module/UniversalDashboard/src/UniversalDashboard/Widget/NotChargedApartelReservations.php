<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

/**
 * Class NotChargedApartelReservations
 * @package UniversalDashboard\Widget
 *
 * @author Tigran Petrosyan
 */
final class NotChargedApartelReservations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'sortable' => false, 'width' => '70'],
            ['name' => 'apartel', 'title' => 'Apartel', 'sortable' => true, 'width' => '200'],
            ['name' => 'apartment', 'title' => 'Apartment', 'sortable' => true, 'width' => '200'],
            ['name' => 'guest', 'title' => 'Guest', 'sortable' => true],
            ['name' => 'checkin_date', 'title' => 'Check-in', 'sortable' => true, 'width' => '90','class'=>'hidden-xs'],
            ['name' => 'checkout_date', 'title' => 'Check-out', 'sortable' => true, 'width' => '90','class'=>'hidden-xs'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '40']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-not-charged-apartel-reservations';
    }
}
