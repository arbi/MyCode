<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class HousekeepingComments extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'description', 'title' => 'GEM', 'sortable' => true, 'class'=>'hidden-xs'],
            ['name' => 'amount', 'title' => 'Apartment', 'sortable' => true, 'class' => 'hkWidgetApartmentColumn'],
            ['name' => 'person_res', 'title' => 'Comment', 'sortable' => false],
            ['name' => 'actions', 'title' => 'Actions', 'sortable' => false, 'width' => '150', 'class' => 'hkWidgetActionsColumn text-center']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-houskeeping-comments';
    }
}
