<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class OrdersToBeRefunded extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'status', 'title' => 'Status', 'sortable' => true],
            ['name' => 'shipping', 'title' => 'Shipping', 'sortable' => true],
            ['name' => 'quantity', 'title' => 'Quantity', 'sortable' => true],
            ['name' => 'category', 'title' => 'Category', 'sortable' => true],
            ['name' => 'location', 'title' => 'Delivery Location', 'sortable' => true],
            ['name' => 'order_date', 'title' => 'Order Date', 'sortable' => true],
            ['name' => 'supplier', 'title' => 'Supplier', 'sortable' => true],
            ['name' => 'transaction_id', 'title' => 'Trans ID', 'sortable' => false],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '40']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-orders-to-be-refunded';
        $this->sorting = [4, 'desc'];

    }
}
