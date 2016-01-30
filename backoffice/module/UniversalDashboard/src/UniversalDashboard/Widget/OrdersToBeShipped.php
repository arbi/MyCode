<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class OrdersToBeShipped extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'status', 'title' => 'Approval', 'sortable' => true],
            ['name' => 'name', 'title' => 'Name', 'sortable' => true],
            ['name' => 'category', 'title' => 'Category', 'sortable' => true],
            ['name' => 'quantity', 'title' => 'Quantity', 'sortable' => true],
            ['name' => 'location', 'title' => 'Delivery Location', 'sortable' => true],
            ['name' => 'order_date', 'title' => 'Order Date', 'sortable' => true],
            ['name' => 'action_archive', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1%'],
            ['name' => 'action_view', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1%']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-orders-to-be-shipped';
        $this->sorting = [4, 'desc'];
    }
}
