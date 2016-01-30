<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class LastMinuteBookings extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'check_in', 'title' => 'Check-in', 'class'=>'hidden-xs'],
            ['name' => 'apartment', 'title' => 'Apartment'],
            ['name' => 'city', 'title' => 'City'],
            ['name' => 'guest', 'title' => 'Guest', 'sortable' => false, 'class'=>'hidden-xs'],
            ['name' => 'pax', 'title' => 'PAX', 'class' => 'text-center hidden-xs', 'sortable' => false],
            ['name' => 'guest_balance', 'title' => 'Guest Balance', 'class' => 'text-right hidden-xs', 'sortable' => false],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '120', 'class' => 'lastMinuteBooksWidgetActionsColumn']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-last-minute-bookings';
    }
}
