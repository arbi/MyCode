<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class ReadyToBeSettledPO extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'manager', 'title' => 'Manager', 'sortable' => true],
            ['name' => 'date_created', 'title' => 'Date Created', 'sortable' => true],
            ['name' => 'purpose', 'title' => 'Purpose', 'sortable' => false],
            ['name' => 'amount', 'title' => 'Amount', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-ready-to-be-settled-po';
    }
}
