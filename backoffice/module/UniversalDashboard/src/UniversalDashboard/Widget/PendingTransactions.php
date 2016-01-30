<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;


final class PendingTransactions extends AbstractUDWidget
{
    public function __construct()
    {

        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'sortable' => false, 'width' => '70'],
            ['name' => 'apartment', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'guest', 'title' => 'Guest', 'sortable' => false,'class'=>'hidden-xs'],
            ['name' => 'status', 'title' => 'Type', 'sortable' => true, 'class'=>'hidden-xs'],
            ['name' => 'amount', 'title' => 'Amount', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'date', 'title' => 'Transaction Date', 'sortable' => true,'class'=>'hidden-xs'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '300']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-pending-transactions';
    }
}
