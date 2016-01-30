<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class PendingTransfer extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'date_created', 'title' => 'Creation Date', 'width' => '10%'],
            ['name' => 'account_from', 'title' => 'Account From', 'width' => '20%'],
            ['name' => 'account_to', 'title' => 'Account To', 'width' => '20%'],
            ['name' => 'description', 'title' => 'Description'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '138px']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-pending-transfers';
    }
}
