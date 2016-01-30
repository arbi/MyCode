<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class ExpensesAwaitingApproval extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'creator', 'title' => 'Creator', 'sortable' => true],
            ['name' => 'manager', 'title' => 'Manager', 'sortable' => true],
            ['name' => 'trans_date', 'title' => 'Date Transacted', 'sortable' => true],
            ['name' => 'validity_date', 'title' => 'Validity', 'sortable' => true],
            ['name' => 'purpose', 'title' => 'Purpose', 'sortable' => false],
            ['name' => 'limit', 'title' => 'Limit', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-expenses-to-approve';
    }
}
