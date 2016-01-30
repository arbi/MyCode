<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

/**
 * Show key instructions not viewed reservations
 * @author Tigran Petrosyan
 */
final class KINotViewedReservations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'sortable' => false, 'width' => '70'],
            ['name' => 'arrival_date', 'title' => 'Check-in', 'sortable' => true, 'width' => '90','class'=>'hidden-xs'],
            ['name' => 'apartment', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'guest', 'title' => 'Guest', 'sortable' => false],
            ['name' => 'last_agent', 'title' => 'Last Agent', 'sortable' => true, 'class'=>'hidden-xs'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '40']
        ];

        $this->order = ['1', 'desc'];
        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-ki-not-viewed-reservations';
    }
}
