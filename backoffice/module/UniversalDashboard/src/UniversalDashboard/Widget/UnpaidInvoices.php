<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class UnpaidInvoices extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'date_created', 'title' => 'Date', 'sortable' => true],
            ['name' => 'amount', 'title' => 'Amount', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'purpose', 'title' => 'Purpose', 'sortable' => false],
            ['name' => 'type', 'title' => 'Type', 'sortable' => false],
            ['name' => 'status', 'title' => 'Status', 'sortable' => true, 'width' => '1'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '110']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-unpaid-invoices';
    }
}
