<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class PendingReservations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'sortable' => false, 'width' => '70'],
            ['name' => 'status', 'title' => 'ST', 'sortable' => true,'class'=>'hidden-xs'],
            ['name' => 'arrival_date', 'title' => 'Check-in', 'sortable' => true, 'width' => '90'],
            ['name' => 'acc_name', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'guest', 'title' => 'Guest', 'sortable' => false, 'class'=>'hidden-xs'],
            ['name' => 'guest_balance', 'title' => 'Guest Balance', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'last_agent', 'title' => 'Last Agent', 'sortable' => false, 'class' => 'actionReservationsWidgetAgentColumn hidden-xs'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '40']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-pending-reservations';
    }
}
