<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class OTAConnectionIssues extends AbstractUDWidget
{
	public function __construct()
    {
        $this->columns = [
            ['name' => 'partner_name', 'title' => 'Partner', 'sortable' => true],
            ['name' => 'name', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'name', 'title' => 'City', 'sortable' => true],
            ['name' => 'date_edited', 'title' => 'Date Edited', 'sortable' => false, 'width' => '150px'],
            ['name' => 'reference', 'title' => 'Reference', 'sortable' => false,'class'=>'hidden-xs'],
            ['name' => 'event', 'title' => '', 'sortable' => false, 'width' => '1']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-ota-connection-issues';
	}
}
