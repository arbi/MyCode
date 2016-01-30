<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class UnresolvedComments extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'date', 'title' => 'Date', 'sortable' => false, 'width' => '90'],
            ['name' => 'R#', 'title' => 'R#', 'sortable' => false, 'width' => '70'],
            ['name' => 'message', 'title' => 'Message', 'sortable' => false],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'class' => 'twoButtonActionsWidth', 'width' => '115']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-unresolved-comments';
    }
}
