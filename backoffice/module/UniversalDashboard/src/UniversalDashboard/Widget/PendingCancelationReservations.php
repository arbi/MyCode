<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

/**
 * Show all canceled pending reservations
 */
final class PendingCancelationReservations extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'sortable' => false, 'width' => '70'],
            ['name' => 'cancelation_date', 'title' => 'Cancelation date', 'sortable' => true, 'class'=>'hidden-xs'],
            ['name' => 'aff_name', 'title' => 'Affiliate', 'sortable' => true, 'class'=>'hidden-xs'],
            ['name' => 'partner_ref', 'title' => 'Reference', 'sortable' => false],
            ['name' => 'acc_name', 'title' => 'Apartment', 'sortable' => false, 'class'=>'hidden-xs'],
            ['name' => 'apartel', 'title' => 'Apartel', 'sortable' => true, 'class'=>'hidden-xs'],
            ['name' => 'guest_balance', 'title' => 'Guest Balance', 'sortable' => true, 'class' => 'text-right'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '130']
        ];

        $this->sorting = ['1', 'desc'];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-pending-cancellation-reservations';
    }
}
