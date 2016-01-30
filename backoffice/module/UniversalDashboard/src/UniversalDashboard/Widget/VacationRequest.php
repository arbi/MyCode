<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class VacationRequest extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'name', 'title' => 'Name', 'sortable' => false],
            ['name' => 'type', 'title' => 'Type'],
            ['name' => 'dates', 'title' => 'Dates', 'sortable' => false],
            ['name' => 'work_days', 'title' => 'Work Days'],
            ['name' => 'days_left', 'title' => 'Days Left', 'sortable' => false, 'class' => 'text-center'],
            ['name' => 'purpose', 'title' => 'Purpose', 'sortable' => false, 'class' => 'text-center'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'class' => 'twoButtonActionsWidth text-center', 'width' => '110']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-vacation-requests';
    }
}
