<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class OverbookingReservations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number',    'title' => 'R#',            'sortable' => false, 'width' => '70'],
            ['name' => 'city',          'title' => 'City',          'sortable' => true],
            ['name' => 'apartment',     'title' => 'Apartment',     'sortable' => true],
            ['name' => 'guest',         'title' => 'Guest',         'sortable' => false,    'class'=>'hidden-xs'],
            ['name' => 'arrival_date',  'title' => 'Check-in',     'sortable' => true],
            ['name' => 'actions',       'title' => '&nbsp;',        'sortable' => false, 'width' => '1%', 'class' => 'lastMinuteBooksWidgetActionsColumn']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-overbooking-reservations';

        $this->sorting = [4, 'desc'];
    }
}
