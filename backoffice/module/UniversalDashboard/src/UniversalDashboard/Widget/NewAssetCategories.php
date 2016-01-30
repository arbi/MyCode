<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class NewAssetCategories extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'type', 'title' => 'Type', 'sortable' => true, 'width' => '1%'],
            ['name' => 'name', 'title' => 'Name', 'sortable' => true],
            ['name' => 'creator', 'title' => 'Creator', 'sortable' => true],
            ['name' => 'action_archive', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1%'],
            ['name' => 'action_view', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1%']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-new-asset-categories';
        // $this->sorting = [4, 'desc'];
    }
}
