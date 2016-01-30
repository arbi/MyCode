<?php
namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

class ReservationIssues extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'reservation_id', 'title' => 'R#', 'sortable' => false, 'width' => '70'],
            ['name' => 'partner_ref', 'title' => 'Ref Number', 'sortable' => false, 'width' => '90'],
            ['name' => 'date', 'title' => 'Date of detection', 'sortable' => true, 'width' => '15%', 'class'=>'hidden-xs'],
            ['name' => 'arrival_date', 'title' => 'Arrival Date', 'sortable' => true],
            ['name' => 'partner_name', 'title' => 'Partner Name', 'sortable' => true],
            ['name' => 'message', 'title' => 'Issue', 'sortable' => true],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '1%']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-reservation-issues';
    }
}
