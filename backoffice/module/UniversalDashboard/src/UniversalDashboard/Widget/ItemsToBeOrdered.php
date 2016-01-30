<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class ItemsToBeOrdered extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'approval', 'title' => 'Approval', 'sortable' => true],
            ['name' => 'name', 'title' => 'Name', 'sortable' => true],
            ['name' => 'category', 'title' => 'Category', 'sortable' => true],
            ['name' => 'quantity', 'title' => 'Quantity', 'sortable' => true],
            ['name' => 'location', 'title' => 'Delivery Location', 'sortable' => true],
            ['name' => 'order_date', 'title' => 'Order Date', 'sortable' => true],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '40']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-items-to-be-ordered';
        $this->sorting = [3, 'desc'];

    }
}
