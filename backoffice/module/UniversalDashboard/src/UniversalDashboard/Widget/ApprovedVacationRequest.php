<?php
namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

class ApprovedVacationRequest extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'name', 'title' => 'Name', 'sortable' => true],
            ['name' => 'type', 'title' => 'Type', 'sortable' => true],
            ['name' => 'dates', 'title' => 'Dates'],
            ['name' => 'work_days', 'title' => 'Work Days', 'sortable' => true, 'width' => '50'],
            ['name' => 'days_left', 'title' => 'Days Left', 'sortable' => true, 'width' => '50'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '110']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-approved-vacations';
    }
}