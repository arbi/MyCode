<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class PendingBudgets extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'user', 'title' => 'User', 'sortable' => true],
            ['name' => 'name', 'title' => 'Name', 'sortable' => true],
            ['name' => 'period', 'title' => 'Period', 'sortable' => true],
            ['name' => 'amount', 'title' => 'Amount', 'sortable' => true],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '14%']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-pending-budgets';
    }
}
