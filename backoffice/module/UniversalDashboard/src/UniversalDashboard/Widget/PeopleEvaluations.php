<?php
namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

class PeopleEvaluations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'message', 'title' => 'Message', 'sortable' => true],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '80']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-people-evaluations';
    }
}