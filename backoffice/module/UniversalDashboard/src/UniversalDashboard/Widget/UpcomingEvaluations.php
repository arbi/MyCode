<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

/**
 * Class UpcomingEvaluations
 * @package UniversalDashboard\Widget
 */
final class UpcomingEvaluations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'creator', 'title' => 'Creator', 'width' => '200px'],
            ['name' => 'employee', 'title' => 'Employee'],
            ['name' => 'planned_date', 'title' => 'Date', 'width' => '110'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '116px']

        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-upcoming-evaluations';
    }
}
