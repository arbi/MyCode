<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class AwaitingTransfer extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'creator', 'title' => 'Creator', 'sortable' => true],
            ['name' => 'manager', 'title' => 'Manager', 'sortable' => true],
            ['name' => 'supplier', 'title' => 'Supplier', 'sortable' => true],
            ['name' => 'date', 'title' => 'Date Transacted', 'sortable' => true],
            ['name' => 'amount', 'title' => 'Amount', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'purpose', 'title' => 'Purpose', 'sortable' => false],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '120']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-awaiting-transfer';
    }
}
