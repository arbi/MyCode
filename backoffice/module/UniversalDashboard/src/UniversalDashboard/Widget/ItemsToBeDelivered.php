<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class ItemsToBeDelivered extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'Approval', 'title' => 'Approval', 'sortable' => true],
            ['name' => 'name', 'title' => 'Name', 'sortable' => true],
            ['name' => 'status', 'title' => 'Status', 'sortable' => true],
            ['name' => 'quantity', 'title' => 'Qty', 'sortable' => true],
            ['name' => 'category', 'title' => 'Category', 'sortable' => true],
            ['name' => 'location', 'title' => 'Delivery Location', 'sortable' => true],
            ['name' => 'order_date', 'title' => 'Order Date', 'sortable' => true],
            ['name' => 'delivery', 'title' => 'Delivery', 'sortable' => true],
            ['name' => 'supplier', 'title' => 'Supplier', 'sortable' => true],
            ['name' => 'tracking_url', 'title' => 'Tracking Url', 'sortable' => false, 'class' => 'text-center'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '40']
        ];
        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-items-to-be-delivered';
        $this->sorting = [4, 'desc'];
    }
}
