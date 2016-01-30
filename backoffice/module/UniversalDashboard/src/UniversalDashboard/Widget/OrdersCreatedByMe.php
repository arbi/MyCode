<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class OrdersCreatedByMe extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'name', 'title' => 'Name', 'sortable' => true],
            ['name' => 'approval', 'title' => 'Approval', 'width' => '75', 'sortable' => true],
            ['name' => 'status', 'title' => 'Status', 'width' => '100', 'sortable' => true, 'class' => "my-orders-shipping-status"],
            ['name' => 'quantity', 'title' => 'Qty', 'width' => '50', 'sortable' => true],
            ['name' => 'location', 'title' => 'Delivery Location', 'sortable' => true],
            ['name' => 'order', 'title' => 'Order Date', 'width' => '110', 'sortable' => true],
            ['name' => 'dates', 'title' => 'Delivery', 'sortable' => true],
            ['name' => 'action_tracking_url', 'title' => 'Tracking Url', 'sortable' => false, 'class' => "text-center"],
            ['name' => 'action_received', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1%'],
            ['name' => 'action_archive', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1%'],
            ['name' => 'action_view', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1%']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-orders-created-by-me';
        $this->sorting = [4, 'desc'];

    }
}
