<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

/**
 * Class EvaluationLessEmployees
 * @package UniversalDashboard\Widget
 */
final class EvaluationLessEmployees extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'employee', 'title' => 'Employee'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '50px']

        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-evaluation-less-employees';
    }
}
