<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class PendingPOItems extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'creator', 'title' => 'Creator', 'sortable' => true],
            ['name' => 'date_created', 'title' => 'Date', 'sortable' => true],
            ['name' => 'amount', 'title' => 'Amount', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'comment', 'title' => 'Purpose', 'sortable' => false],
            ['name' => 'type', 'title' => 'Type', 'sortable' => false],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-pending-po-items';
    }
}
