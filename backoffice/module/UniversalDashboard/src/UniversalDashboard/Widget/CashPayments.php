<?php

namespace UniversalDashboard\Widget;

use Library\Constants\Objects;
use Library\Constants\DomainConstants;

use UniversalDashboard\AbstractUDWidget;

use DDD\Service\Booking;

/**
 * Show all those reservations which departure date + 5 AND NOT marked as Settled
 * @author Tigran Petrosyan
 */
final class CashPayments extends AbstractUDWidget
{
    public function __construct()
    {
        $this->columns = [
            ['name' => 'res_number', 'title' => 'R#', 'sortable' => false, 'width' => '70'],
            ['name' => 'apartment', 'title' => 'Apartment', 'sortable' => true],
            ['name' => 'guest', 'title' => 'Guest', 'sortable' => false,'class'=>'hidden-xs'],
            ['name' => 'user', 'title' => 'Received By', 'sortable' => true, 'class'=>'hidden-xs'],
            ['name' => 'amount', 'title' => 'Amount', 'class' => 'text-right', 'sortable' => false],
            ['name' => 'date', 'title' => 'Transaction Date', 'sortable' => true,'class'=>'hidden-xs'],
            ['name' => 'actions', 'title' => '&nbsp;', 'sortable' => false, 'width' => '150']
        ];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-cash-payments';
    }
}
