<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

/**
 * Show all those reservations in which is marked "Pinned Reservations"
 */
final class PinnedReservations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'width' => '70', 'sortable' => false],
            ['name' => 'guest_name', 'title' => 'Guest', 'sortable' => true],
            ['name' => 'apartment', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '12%']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-pinned-reservations';
    }
}
