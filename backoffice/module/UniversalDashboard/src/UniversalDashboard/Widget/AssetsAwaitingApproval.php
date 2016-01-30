<?php
namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

class AssetsAwaitingApproval extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'location', 'title' => 'Location', 'sortable' => true],
            ['name' => 'category', 'title' => 'Category', 'sortable' => true],
            ['name' => 'user', 'title' => 'Added By', 'sortable' => true],
            ['name' => 'orders', 'title' => 'Related Orders', 'sortable' => false],
            ['name' => 'quantity', 'title' => 'Quantity', 'sortable' => true],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '165px']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-assets-awaiting-approval';
    }
}
