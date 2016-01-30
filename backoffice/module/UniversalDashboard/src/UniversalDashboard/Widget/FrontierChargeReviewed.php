<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

/**
 * Show all those reservations in which is marked "Issue Detected"
 * @author Tigran Petrosyan
 */
final class FrontierChargeReviewed extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'sortable' => false, 'width' => '70'],
            ['name' => 'apartment', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'guest', 'title' => 'Guest', 'sortable' => false, 'class' => 'hidden-xs'],
            ['name' => 'status', 'title' => 'Type', 'sortable' => true, 'class'=>'hidden-xs'],
            ['name' => 'amount', 'title' => 'Amount', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'date', 'title' => 'Transaction Date', 'sortable' => true, 'class'=>'hidden-xs'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '150']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-frontier-charge-reviewed';
    }
}
