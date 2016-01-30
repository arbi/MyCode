<?php
namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

class Applicants extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'name', 'title' => 'Name', 'sortable' => true],
            ['name' => 'position', 'title' => 'Position', 'sortable' => true],
            ['name' => 'city', 'title' => 'City', 'sortable' => true],
            ['name' => 'date', 'title' => 'Applied Date', 'sortable' => true, 'width' => 100],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => true, 'width' => 1]
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-new-applicants';
    }
}