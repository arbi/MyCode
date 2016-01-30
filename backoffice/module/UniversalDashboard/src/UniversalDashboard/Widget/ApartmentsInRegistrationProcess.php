<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;


final class ApartmentsInRegistrationProcess extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'name', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'city', 'title' => 'City', 'sortable' => true],
            ['name' => 'status', 'title' => 'Status', 'sortable' => true],
            ['name' => 'created', 'title' => 'Creation Date', 'sortable' => true, 'width' => '110px'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '40']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-apartments-in-registration-process';
    }
}
