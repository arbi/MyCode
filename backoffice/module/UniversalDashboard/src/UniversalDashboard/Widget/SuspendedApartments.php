<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class SuspendedApartments extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'apartment_name', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'date_created', 'title' => 'Creation Date', 'sortable' => true],
            ['name' => 'country', 'title' => 'Country', 'sortable' => true],
            ['name' => 'city', 'title' => 'City', 'sortable' => true],
            ['name' => 'address', 'title' => 'Address', 'sortable' => false,'class'=>'hidden-xs'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-suspended-apartments';
    }
}
