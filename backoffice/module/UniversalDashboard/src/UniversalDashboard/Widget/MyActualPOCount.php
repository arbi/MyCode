<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class MyActualPOCount extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'validity_date', 'title' => 'Validity Date (Expected Completion)', 'sortable' => true],
            ['name' => 'name', 'title' => 'Name', 'sortable' => false],
            ['name' => 'purpose', 'title' => 'Purpose', 'sortable' => false],
            ['name' => 'balance', 'title' => 'Balance', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'limit', 'title' => 'Limit', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-my-actual-po';
    }
}
