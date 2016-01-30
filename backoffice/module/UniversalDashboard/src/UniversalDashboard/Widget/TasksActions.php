<?php

namespace UniversalDashboard\Widget;

use UniversalDashboard\AbstractUDWidget;

final class TasksActions extends AbstractUDWidget
{
    public function __construct($type)
    {
        $this->columns = [
            ['name' => 'priority', 'title' => 'P', 'sortable' => true, 'width' => '1'],
            ['name' => 'status', 'title' => 'St', 'sortable' => true],
            ['name' => 'creation', 'title' => 'Created', 'sortable' => true, 'width' => '90', 'class' => 'hidden-xs'],
            ['name' => 'description', 'title' => 'Title', 'sortable' => false],
            ['name' => 'responsible', 'title' => 'Responsible', 'sortable' => true],
            ['name' => 'location', 'title' => 'Location', 'sortable' => true],
            ['name' => 'type', 'title' => 'Type', 'sortable' => true, 'class' => 'hidden-xs'],
            ['name' => 'date', 'title' => 'Due Date', 'sortable' => true, 'width' => '110'],
            ['name' => 'team', 'title' => 'Team', 'sortable' => true, 'class' => 'hidden-xs'],
            ['name' => 'event', 'title' => '', 'sortable' => false, 'class' => 'text-center' . ($type == 'verifying' ? ' twoButtonActionsWidth' : '')],
        ];

        $this->sorting = ['7', 'asc'];

        $this->ajaxSourceUrl = '/ud/universal-dashboard-data/get-tasks?type=' . $type;
    }
}
