<?php
namespace DDD\Dao\Task;

use DDD\Service\User;
use Library\DbManager\TableGatewayManager;
use Library\ActionLogger\Logger;
use Library\Constants\Constants;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;
use DDD\Service\Task as TaskService;
use DDD\Service\Team\Team as TeamService;

class Task extends TableGatewayManager
{
    protected $table = DbTables::TBL_TASK;

    public function __construct($sm, $domain = 'DDD\Domain\Task\Task')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $id
     * @return \DDD\Domain\Task\Task
     */
    public function getTaskById($id)
    {
        return $this->fetchOne(function (Select $select) use($id) {
    		$select
                ->join(
                    ['task_creators' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_creators.task_id AND task_creators.type = ' . TaskService::STAFF_CREATOR),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['users_creators'=>DbTables::TBL_BACKOFFICE_USERS],
                    'task_creators.user_id = users_creators.id',
                    [
                        'creator_name' => new Expression("CONCAT(users_creators.firstname, ' ', users_creators.lastname)"),
                        'creator_id' => 'id',
                        'creator_avatar' => 'avatar'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_responsibles' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_responsibles.task_id AND task_responsibles.type = ' . TaskService::STAFF_RESPONSIBLE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['users_responsibles'=>DbTables::TBL_BACKOFFICE_USERS],
                    'task_responsibles.user_id = users_responsibles.id',
                    [
                        'responsible_id' => 'id',
                        'responsible_name' => new Expression("CONCAT(users_responsibles.firstname, ' ', users_responsibles.lastname)"),
                        'responsible_avatar' => 'avatar'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_verifiers' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_verifiers.task_id AND task_verifiers.type = ' . TaskService::STAFF_VERIFIER),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['users_verifiers'=>DbTables::TBL_BACKOFFICE_USERS],
                    'task_verifiers.user_id = users_verifiers.id',
                    [
                        'verifier_id' => 'id',
                        'verifier_name' => new Expression("CONCAT(users_verifiers.firstname, ' ', users_verifiers.lastname)"),
                        'verifier_avatar' => 'avatar'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['product'=>DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.property_id = product.id',
                    ['property_name' => 'name', 'building_id', 'apartment_unit_number' => 'unit_number'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment_groups'=>DbTables::TBL_APARTMENT_GROUPS],
                    $this->getTable() . '.building_id = apartment_groups.id',
                    ['building_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['reservations'=>DbTables::TBL_BOOKINGS],
                    $this->getTable() . '.res_id = reservations.id',
                    ['res_number', 'arrival_status', 'res_date_from' => 'date_from'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['cities' => DbTables::TBL_CITIES],
                    'product.city_id = cities.id',
                    ['timezone'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ["det1" => DbTables::TBL_LOCATION_DETAILS],
                    'cities.detail_id = det1.id',
                    ['city' => 'name'],
                    Select::JOIN_LEFT
                )
                ->where(array($this->getTable() .'.id'=>$id));
    	});
    }

    /**
     * @param int $reservationId
     * @param int $type
     * @param bool|false $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getReservationAutoCreatedTask($reservationId, $type, $apartmentId = false)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $res = $this->fetchOne(function (Select $select) use($apartmentId, $reservationId, $type) {
            $select->columns(['id','property_id', 'res_id','task_status', 'start_date', 'priority']);
            $where = new Where();
            $where
                ->equalTo($this->getTable() .'.res_id', $reservationId)
                ->equalTo($this->getTable() .'.is_hk', TaskService::TASK_IS_HOUSEKEEPING)
                ->equalTo($this->getTable() .'.task_type', $type);

            if ($apartmentId != false) {
                $where
                    ->equalTo($this->getTable() .'.property_id', $apartmentId);
            }
         $select->where($where);
        });
        $this->setEntity($prototype);
        return $res;
    }

    public function getNextReservationExtraInspectionTask($reservationId)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $res = $this->fetchOne(function (Select $select) use($reservationId) {
            $select->columns(['id','property_id', 'res_id','task_status', 'start_date', 'priority']);
            $where = new Where();
            $where
                ->equalTo($this->getTable() .'.res_id', $reservationId)
                ->equalTo($this->getTable() .'.extra_inspection', TaskService::TASK_EXTRA_INSPECTION);

            $select->where($where);
        });
        $this->setEntity($prototype);
        return $res;
    }

    public function getReservationTasksByTitle($reservationId, $title, $useLike = false)
    {
        $result = $this->fetchAll(function (Select $select) use($reservationId, $title, $useLike) {
            $select->columns([
                'id','title', 'res_id','task_status', 'task_type'
            ]);

            $select->where
                ->equalTo('task_type', TaskService::TYPE_RESERVATION)
                ->equalTo('res_id', $reservationId)
                ->notIn('task_status', [TaskService::STATUS_DONE, TaskService::STATUS_VERIFIED, TaskService::STATUS_CANCEL]);

            if ($useLike) {
                $select->where->like('title', $title);
            } else {
                $select->where->equalTo('title', $title);
            }

            $select->order('id DESC');
        });

        return $result;
    }

    public function getReservationTasksByType($reservationId, $type, $onlyActive = true)
    {
        $result = $this->fetchAll(function (Select $select) use($reservationId, $type, $onlyActive) {
            $select->columns([
                'id','title', 'res_id','task_status', 'task_type'
            ]);

            $select->where
                ->equalTo($this->getTable() .'.res_id', $reservationId)
                ->equalTo($this->getTable() .'.task_type', $type);

            if ($onlyActive) {
                $select->where
                    ->notIn('task_status', [
                        TaskService::STATUS_DONE,
                        TaskService::STATUS_VERIFIED,
                        TaskService::STATUS_CANCEL
                    ]);
            }
        });

        return $result;
    }

    /**
     * @param $taskId
     * @param $deleteDate
     * @param $apartmentId
     */
    public function deleteAllSameDayAutoGeneratedTasks($taskId, $deleteDate ,$apartmentId, $taskType)
    {
        $where = new Where();
        $where
            ->expression('DATE(`start_date`) = DATE("' . $deleteDate . '") ',[])
            ->equalTo('property_id', $apartmentId)
            ->equalTo('is_hk', TaskService::TASK_IS_HOUSEKEEPING)
            ->equalTo('task_type', $taskType)
            ->notEqualTo('id', $taskId);
        $this->delete($where);
    }


    /**
     * @param $userId
     * @param $type
     * @return \DDD\Domain\Task\Task[]
     */
    public function getUDList($userId, $type)
    {
        return $this->fetchAll(function (Select $select) use($userId, $type) {
            $select
                ->join(
                    ['task_types' => DbTables::TBL_TASK_TYPE],
                    $this->getTable() . '.task_type = task_types.id',
                    ['task_type_name' => 'name'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['task_creators' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_creators.task_id AND task_creators.type = ' . TaskService::STAFF_CREATOR),
                    ['creator_id' => 'user_id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['users_creators' => DbTables::TBL_BACKOFFICE_USERS],
                    'task_creators.user_id = users_creators.id',
                    ['creator_name' => new Expression("CONCAT(users_creators.firstname, ' ', users_creators.lastname)")],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_responsibles' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_responsibles.task_id AND task_responsibles.type = ' . TaskService::STAFF_RESPONSIBLE),
                    ['responsible_id' => 'user_id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['users_responsibles' => DbTables::TBL_BACKOFFICE_USERS],
                    'task_responsibles.user_id = users_responsibles.id',
                    ['responsible_name' => new Expression("CONCAT(users_responsibles.firstname, ' ', users_responsibles.lastname)")],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_helpers' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_helpers.task_id AND task_helpers.type = ' . TaskService::STAFF_HELPER),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_verifiers' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_verifiers.task_id AND task_verifiers.type = ' . TaskService::STAFF_VERIFIER),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_followers' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_followers.task_id AND task_followers.type = ' . TaskService::STAFF_FOLLOWER),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['teams' => DbTables::TBL_TEAMS],
                    $this->getTable() . '.team_id = teams.id',
                    ['team_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.property_id = apartments.id',
                    [
                        'property_name' => 'name',
                        'apartment_unit_number' => 'unit_number',
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartments_group' => DbTables::TBL_APARTMENT_GROUPS],
                    $this->getTable() . '.building_id = apartments_group.id',
                    ['building_name' => 'name'],
                    Select::JOIN_LEFT
                );

            $where = new Where();
            $where->lessThanOrEqualTo($this->getTable() . '.start_date', date('Y-m-d H:i:s', strtotime('+3 day')));
            $where->notIn(
                'task_status',
                [TaskService::STATUS_CANCEL, TaskService::STATUS_VERIFIED]
            );

            $expression = false;

            switch ($type) {
                case 'team':
                    $select->join(
                        ['team_staff' => DbTables::TBL_TEAM_STAFF],
                        new Expression(
                            $this->getTable() . '.team_id = team_staff.team_id
                        AND team_staff.user_id = ' . $userId . '
                        AND team_staff.type IN (' . TeamService::STAFF_MANAGER . ', ' . TeamService::STAFF_OFFICER . ')'
                        ),
                        [],
                        Select::JOIN_INNER
                    );

                    $where->notEqualTo('is_hk', TaskService::TASK_IS_HOUSEKEEPING);
                    $expression = 'task_responsibles.id IS NULL';

                    break;
                case 'team_assigned':
                    $select->join(
                        ['team_staff' => DbTables::TBL_TEAM_STAFF],
                        new Expression(
                            $this->getTable() . '.team_id = team_staff.team_id
                        AND team_staff.user_id = ' . $userId . '
                        AND team_staff.type IN (' . TeamService::STAFF_MANAGER . ', ' . TeamService::STAFF_OFFICER . ', ' . TeamService::STAFF_MEMBER . ')'
                        ),
                        [],
                        Select::JOIN_INNER
                    );
                    $expression = 'task_responsibles.user_id = ' . User::ANY_TEAM_MEMBER_USER_ID;
                    break;
                case 'created':
                    $expression = 'task_creators.user_id = ' . $userId;
                    break;
                case 'doing':
                    $expression = '(task_responsibles.user_id = ' . $userId .
                        ' OR task_helpers.user_id = ' . $userId . ') AND task_status <> ' . TaskService::STATUS_DONE;
                    break;
                case 'verifying':
                    $expression = 'task_verifiers.user_id = ' . $userId .
                        ' AND task_status = ' . TaskService::STATUS_DONE;
                    break;
                case 'following':
                    $select->join(
                        ['following_team_staff' => DbTables::TBL_TEAM_STAFF],
                        new Expression(
                            'task_followers.user_id = ' . $userId . ' OR (' .
                            $this->getTable() . '.following_team_id = following_team_staff.team_id
                        AND following_team_staff.user_id = ' . $userId . '
                        AND following_team_staff.type IN (' . TeamService::STAFF_MANAGER . ', ' . TeamService::STAFF_OFFICER . ', ' . TeamService::STAFF_MEMBER . ')
                        AND is_hk = 0)'
                        ),
                        [],
                        Select::JOIN_INNER
                    );
                    $expression = 'task_status != ' . TaskService::STATUS_DONE;
                    break;
            }

            if ($expression) {
                $where->expression($expression, []);
            }

            $select
                ->where($where)
                ->order([$this->getTable() . '.priority' => 'DESC']);

            //Get rid of duplicates caused by multifollowers and multihelpers
            $select->group($this->getTable() . '.id');
        });
    }

    public function getUDListCount($userId, $type)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use($userId, $type) {
            $where = new Where();
            $where->lessThanOrEqualTo($this->getTable() . '.start_date', date('Y-m-d H:i:s', strtotime('+3 day')));
            $where->notIn(
                'task_status',
                [TaskService::STATUS_CANCEL, TaskService::STATUS_VERIFIED]
            );

            $expression = false;

            switch ($type) {
                case 'team':
                    $select
                        ->join(
                            ['team_staff' => DbTables::TBL_TEAM_STAFF],
                            new Expression(
                                $this->getTable() . '.team_id = team_staff.team_id
                                AND team_staff.user_id = ' . $userId . '
                                AND team_staff.type IN (' . TeamService::STAFF_MANAGER . ', ' . TeamService::STAFF_OFFICER . ')'
                            ),
                            [],
                            Select::JOIN_INNER
                        )
                        ->join(
                            ['task_responsibles' => DbTables::TBL_TASK_STAFF],
                            new Expression($this->getTable() . '.id = task_responsibles.task_id AND task_responsibles.type = ' . TaskService::STAFF_RESPONSIBLE),
                            [],
                            Select::JOIN_LEFT
                        );

                    $where->notEqualTo('is_hk', TaskService::TASK_IS_HOUSEKEEPING);
                    $expression = 'task_responsibles.id IS NULL';
                    break;
                case 'team_assigned':
                    $select
                        ->join(
                            ['team_staff' => DbTables::TBL_TEAM_STAFF],
                            new Expression(
                                $this->getTable() . '.team_id = team_staff.team_id
                                AND team_staff.user_id = ' . $userId . '
                                AND team_staff.type IN (' . TeamService::STAFF_MANAGER . ', ' . TeamService::STAFF_OFFICER . ', ' . TeamService::STAFF_MEMBER . ')'
                            ),
                            [],
                            Select::JOIN_INNER
                        )
                        ->join(
                            ['task_responsibles' => DbTables::TBL_TASK_STAFF],
                            new Expression($this->getTable() . '.id = task_responsibles.task_id AND task_responsibles.type = ' . TaskService::STAFF_RESPONSIBLE),
                            [],
                            Select::JOIN_LEFT
                        );
                    $expression = 'task_responsibles.user_id = ' . User::ANY_TEAM_MEMBER_USER_ID;
                    break;
                case 'created':
                    $select->join(
                        ['task_creators' => DbTables::TBL_TASK_STAFF],
                        new Expression($this->getTable() . '.id = task_creators.task_id AND task_creators.type = ' . TaskService::STAFF_CREATOR),
                        [],
                        Select::JOIN_LEFT
                    );
                    $expression = 'task_creators.user_id = ' . $userId;
                break;
                case 'verifying':
                    $select->join(
                        ['task_verifiers' => DbTables::TBL_TASK_STAFF],
                        new Expression($this->getTable() . '.id = task_verifiers.task_id AND task_verifiers.type = ' . TaskService::STAFF_VERIFIER),
                        [],
                        Select::JOIN_LEFT
                    );
                    $expression = 'task_verifiers.user_id = ' . $userId . ' AND task_status = ' . TaskService::STATUS_DONE;
                break;
                case 'doing':
                $select
                    ->join(
                        ['task_responsibles' => DbTables::TBL_TASK_STAFF],
                        new Expression($this->getTable() . '.id = task_responsibles.task_id AND task_responsibles.type = ' . TaskService::STAFF_RESPONSIBLE),
                        [],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['task_helpers' => DbTables::TBL_TASK_STAFF],
                        new Expression($this->getTable() . '.id = task_helpers.task_id AND task_helpers.type = ' . TaskService::STAFF_HELPER),
                        [],
                        Select::JOIN_LEFT
                    );
                    $expression = '(task_responsibles.user_id = ' . $userId . ' OR task_helpers.user_id = ' . $userId . ') AND task_status <> ' . TaskService::STATUS_DONE;
                break;
                case 'following':
                    $select->join(
                        ['task_followers' => DbTables::TBL_TASK_STAFF],
                        new Expression($this->getTable() . '.id = task_followers.task_id AND task_followers.type = ' . TaskService::STAFF_FOLLOWER),
                        [],
                        Select::JOIN_LEFT
                    );
                    $select->join(
                        ['following_team_staff' => DbTables::TBL_TEAM_STAFF],
                        new Expression(
                            'task_followers.user_id = ' . $userId . ' OR (' .
                            $this->getTable() . '.following_team_id = following_team_staff.team_id
                        AND following_team_staff.user_id = ' . $userId . '
                        AND following_team_staff.type IN (' . TeamService::STAFF_MANAGER . ', ' . TeamService::STAFF_OFFICER . ', ' . TeamService::STAFF_MEMBER . ')
                        AND is_hk = 0)'
                        ),
                        [],
                        Select::JOIN_INNER
                    );
                    $expression = 'task_status != ' . TaskService::STATUS_DONE;
                break;
            }

            if ($expression) {
                $where->expression($expression, []);
            }

            $select
                ->columns(['count' => new Expression('COUNT(DISTINCT ' . $this->getTable() . '.id' . ')')])
                ->where($where);
        });

        return $result['count'];
    }

    public function getTaskListForSearch(
        $authID,
        $iDisplayStart = null,
        $iDisplayLength = null,
        $filterParams = array(),
        $sortCol = 0,
        $sortDir = 'DESC',
        $taskManger
    )
    {
        $where = new Where();
        foreach ($filterParams as $key => $row) {
            if (!is_array($row)) {
                $filterParams[$key] = trim($row);
            }
        }

        if ($filterParams["title"] != '') {
            $where->like($this->getTable() . '.title', '%' . $filterParams["title"] . '%');
        }

        if ($filterParams["status"] > 0) {
            $statusArray = [$filterParams["status"]];

            if ($filterParams["status"] == TaskService::STATUS_ALL_OPEN) {
                $statusArray = [TaskService::STATUS_NEW, TaskService::STATUS_VIEWED, TaskService::STATUS_BLOCKED, TaskService::STATUS_STARTED];
            }

            $where->in($this->getTable() . '.task_status', $statusArray);
        }

        if ($filterParams["priority"] > 0) {
            $where->equalTo($this->getTable() . '.priority', $filterParams["priority"]);
        }

        if ($filterParams["type"] > 0) {
            $where->equalTo($this->getTable() . '.task_type', $filterParams["type"]);
        }

        if ($filterParams["creator_id"] > 0) {
            $where->equalTo('task_creators.user_id', (int)$filterParams["creator_id"]);
        }

        if ($filterParams["responsible_id"] > 0) {
            $where->equalTo('task_responsibles.user_id', (int)$filterParams["responsible_id"]);
        }

        if ($filterParams["responsible_id"] < 0) {
            $where->isNull('task_responsibles.user_id');
        }

        if ($filterParams["verifier_id"] > 0) {
            $where->equalTo('task_verifiers.user_id', (int)$filterParams["verifier_id"]);
        }

        if ($filterParams["helper_id"] > 0) {
            $where->equalTo('task_helpers.user_id', (int)$filterParams["helper_id"]);
        }

        if ($filterParams["follower_id"] > 0) {
            $where->equalTo('task_followers.user_id', (int)$filterParams["follower_id"]);
        }

        if ($filterParams["property_id"] > 0 && $filterParams['property']) {
            $where->equalTo($this->getTable() . '.property_id', $filterParams["property_id"]);
        }

        if ($filterParams["team_id"]) {
            $where->equalTo($this->getTable() . '.team_id', $filterParams["team_id"]);
        }

        if (isset($filterParams['tags']) && !empty($filterParams['tags']) ) {
            $where->in('task_tag.tag_id', explode(',',$filterParams['tags']));
        }

        if ($filterParams["end_date"] != '') {
	    	$dates = explode(' - ', $filterParams["end_date"]);
	    	$rangeStart = $dates[0];
	    	$rangeEnd = $dates[1];
	    	$where->lessThanOrEqualTo($this->getTable() . ".end_date", $rangeEnd);
	    	$where->greaterThanOrEqualTo($this->getTable() . ".end_date", $rangeStart);
	    }

        if ($filterParams["creation_date"] != '') {
	    	$dates = explode(' - ', $filterParams["creation_date"]);
            $rangeStart = $dates[0] . ' 00:00';
            $rangeEnd = $dates[1] . ' 23:59';
	    	$where->lessThanOrEqualTo($this->getTable() . ".creation_date", $rangeEnd);
	    	$where->greaterThanOrEqualTo($this->getTable() . ".creation_date", $rangeStart);
	    }

        if ($filterParams["done_date"] != '') {
	    	$dates = explode(' - ', $filterParams["done_date"]);
            $rangeStart = $dates[0];
            $rangeEnd = $dates[1];
	    	$where->lessThanOrEqualTo($this->getTable() . ".done_date", $rangeEnd);
	    	$where->greaterThanOrEqualTo($this->getTable() . ".done_date", $rangeStart);
	    }

        $buildingId = false;
        if ($filterParams["building_id"] > 0 && $filterParams['building']) {
            $buildingId = $filterParams["building_id"];
        }

    	$sortColumns = array(
            'priority',
            'task_status',
            'start_date',
            'end_date',
    		'title',
            'apartment_name',
    		'responsible_name',
    		'verifier_name',
            'task_type'
    	);

    	$result = $this->fetchAll(function (Select $select) use($sortColumns, $iDisplayStart, $iDisplayLength, $where, $sortCol, $sortDir, $authID, $taskManger, $buildingId) {

    		$select
                ->join(
                    ['task_types' => DbTables::TBL_TASK_TYPE],
                    $this->getTable() . '.task_type = task_types.id',
                    ['task_type_name' => 'name'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['task_creators' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_creators.task_id and task_creators.type=' . TaskService::STAFF_CREATOR),
                    [],
                    Select::JOIN_LEFT
                )
                ->join([
                    'users_creators'=>DbTables::TBL_BACKOFFICE_USERS],
                    'task_creators.user_id = users_creators.id',
                    [
                        'creator_id' => 'id',
                        'creator_name' => new Expression("CONCAT(users_creators.firstname, ' ', users_creators.lastname)")
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_responsibles' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_responsibles.task_id and task_responsibles.type=' . TaskService::STAFF_RESPONSIBLE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['users_responsibles'=>DbTables::TBL_BACKOFFICE_USERS],
                    'task_responsibles.user_id = users_responsibles.id',
                    [
                        'responsible_id' => 'id',
                        'responsible_name' => new Expression("CONCAT(users_responsibles.firstname, ' ', users_responsibles.lastname)")
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_verifiers' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_verifiers.task_id and task_verifiers.type=' . TaskService::STAFF_VERIFIER),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['users_verifiers'=>DbTables::TBL_BACKOFFICE_USERS],
                    'task_verifiers.user_id = users_verifiers.id',
                    [
                        'verifier_id' => 'id',
                        'verifier_name' => new Expression("CONCAT(users_verifiers.firstname, ' ', users_verifiers.lastname)")
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_helpers' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_helpers.task_id and task_helpers.type=' . TaskService::STAFF_HELPER),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['users_helpers'=>DbTables::TBL_BACKOFFICE_USERS],
                    'task_helpers.user_id = users_helpers.id',
                    [
                        'helper_id' => 'id',
                        'helper_name' => new Expression("CONCAT(users_helpers.firstname, ' ', users_helpers.lastname)")
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_followers' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = task_followers.task_id and task_followers.type=' . TaskService::STAFF_FOLLOWER),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_team_staff' => DbTables::TBL_TEAM_STAFF],
                    new Expression($this->getTable() . '.team_id = task_team_staff.team_id and task_team_staff.type NOT IN (' . TeamService::STAFF_CREATOR . ', ' . TeamService::STAFF_DIRECTOR . ')'),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_following_team_staff' => DbTables::TBL_TEAM_STAFF],
                    new Expression($this->getTable() . '.following_team_id = task_following_team_staff.team_id and task_following_team_staff.type NOT IN (' . TeamService::STAFF_CREATOR . ', ' . TeamService::STAFF_DIRECTOR . ')'),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['users_followers'=>DbTables::TBL_BACKOFFICE_USERS],
                    'task_followers.user_id = users_followers.id',
                    [
                        'follower_id' => 'id',
                        'follower_name' => new Expression("CONCAT(users_followers.firstname, ' ', users_followers.lastname)")
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment1'=>DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.property_id = apartment1.id',
                    [
                        'apartment_name' => 'name',
                        'apartment_unit_number' => 'unit_number'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['subtask' => DbTables::TBL_TASK_SUBTASK],
                    'subtask.task_id = ' . $this->getTable() . '.id',
                    ['subtask_description' => new Expression('GROUP_CONCAT(subtask.description)')],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['task_tag' => DbTables::TBL_TASK_TAG],
                    new Expression($this->getTable() . '.id = task_tag.task_id'),
                    [],
                    Select::JOIN_LEFT
                );

            if($buildingId) {
                $select->join(['apartment'=>DbTables::TBL_APARTMENTS], new Expression ( $this->getTable() . '.property_id = apartment.id AND apartment.building_id = ' . $buildingId), []);
            }
    		if(!$taskManger) {
                $where->expression('(users_creators.id = '.$authID.' OR users_responsibles.id = '.$authID.' OR task_verifiers.user_id = '.$authID.' OR users_helpers.id = '.$authID.' OR users_followers.id = '.$authID.' OR task_team_staff.user_id = ' . $authID . ' OR task_following_team_staff.user_id = ' . $authID . ')', []);
            }
    		if ($where !== null) {
    			$select->where($where);
    		}

            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
            if ($iDisplayLength !== null && $iDisplayStart !== null) {
                $select->limit((int)$iDisplayLength);
                $select->offset((int)$iDisplayStart);
            }
            $select->group($this->getTable() . '.id');
            $select->order($sortColumns[$sortCol].' '.$sortDir);
      });
      $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
      $result2 = $statement->execute();
      $row = $result2->current();
      $total = $row['total'];
      return ['result'=>$result, 'count'=>$total];
    }

    /**
     * @param int $apartmentId
     * @param bool $showAll
     * @return \DDD\Domain\Task\Task[]
     */
    public function getFrontierTasksOnApartment($apartmentId, $showAll = false)
    {
        return $this->fetchAll(function (Select $select) use($apartmentId, $showAll) {
            $where = new Where();
            if (!$showAll) {
                $where->in(
                    'task_status',
                    [TaskService::STATUS_NEW, TaskService::STATUS_VIEWED, TaskService::STATUS_BLOCKED, TaskService::STATUS_STARTED]
                );

            }
            $where->equalTo($this->getTable() . '.property_id', $apartmentId);

            // only frontier related tasks
            $where->equalTo('task_types.group', TaskService::TASK_GROUP_FRONTIER);

            $select
                ->columns(['id', 'priority', 'title', 'task_type'])
                ->join(
                    ['task_types' => DbTables::TBL_TASK_TYPE],
                    $this->getTable() . '.task_type = task_types.id',
                    [],
                    Select::JOIN_INNER
                )
                ->where($where)
                ->order(['priority DESC', $this->getTable() . '.title']);
        });
    }

    /**
     * @param int $buildingId
     * @param bool $showAll
     * @return \DDD\Domain\Task\Task[]
     */
    public function getTasksOnBuilding($buildingId, $showAll = false)
    {
        return $this->fetchAll(function (Select $select) use($buildingId, $showAll) {
            $where = new Where();
            if (!$showAll) {
                $where->in(
                    'task_status',
                    [TaskService::STATUS_NEW, TaskService::STATUS_VIEWED, TaskService::STATUS_BLOCKED, TaskService::STATUS_STARTED]
                );
            }
            $where->equalTo($this->getTable() . '.building_id', $buildingId);

            $select
                ->columns(['id', 'priority', 'title'])
                ->where($where)
                ->order(['priority DESC', $this->getTable() . '.title']);
        });
    }

    /**
     * @param int $reservationId
     * @param bool $showAll
     * @return \DDD\Domain\Task\Task[]
     */
    public function getTasksOnReservation($reservationId, $showAll = false)
    {
        return $this->fetchAll(function (Select $select) use($reservationId, $showAll) {
            $where = new Where();
            if (!$showAll) {
                $where->in(
                    'task_status',
                    [TaskService::STATUS_NEW, TaskService::STATUS_VIEWED, TaskService::STATUS_BLOCKED, TaskService::STATUS_STARTED]
                );
            }
            $where->equalTo('reservations.id', $reservationId);

            $select
                ->columns(['id', 'priority', 'title', 'task_type'])
                ->join(
                    ['reservations' => DbTables::TBL_BOOKINGS],
                    $this->getTable() . '.res_id = reservations.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->where($where)
                ->order(['priority DESC', $this->getTable() . '.title']);
        });
    }

    /**
     * @param int $reservationId
     * @param bool $all
     * @return int
     */
    public function getTasksCountOnReservation($reservationId, $all = false)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();

        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result =  $this->fetchOne(function (Select $select) use($reservationId, $all) {
            $where = new Where();
            if (!$all) {
                $where->notIn(
                    'task_status',
                    [TaskService::STATUS_DONE, TaskService::STATUS_VERIFIED, TaskService::STATUS_CANCEL]
                );
            }
            $where->equalTo('reservations.id', $reservationId);

            $select->columns(['count' => new Expression('count(*)')])
                ->join(
                    ['reservations' => DbTables::TBL_BOOKINGS],
                    $this->getTable() . '.res_id = reservations.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->where($where);
        });

        $count = 0;
        if ($result) {
            $count = $result['count'];
        }
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $count;
    }

    /**
     * @param int $resId
     * @param int $start
     * @param int $length
     * @param array $order
     * @param array $search
     * @param int $status
     * @return \DDD\Domain\Task\Task[]
     */
    public function getTasksOnReservationForDatatable($resId, $start, $length, $order, $search, $status)
    {
        $result = $this->fetchAll(function (Select $select) use($resId, $start, $length, $order, $search, $status) {
            $like = $search['value'];
            $where = new Where();
            if ($status == 1) {
                $where->notIn(
                    'task_status',
                    [TaskService::STATUS_CANCEL, TaskService::STATUS_VERIFIED, TaskService::STATUS_DONE]
                );
            } else if ($status == 2) {
                $where->in(
                    'task_status',
                    [TaskService::STATUS_CANCEL, TaskService::STATUS_VERIFIED, TaskService::STATUS_DONE]
                );
            }
            $where->equalTo('reservations.id', $resId);

            $columns      = ['priority', 'title', 'task_status', 'start_date', 'end_date', 'id', 'task_type'];
            $orderColumns = ['priority', 'task_status','start_date','end_date', 'title', 'task_type',  'creator_name', 'responsible_name'];

            $nestedWhere = new Where();
            $nestedWhere
                ->like('title', '%' . $like . '%')
                ->or
                ->like($this->getTable() . '.start_date', '%' . $like . '%')
                ->or
                ->like($this->getTable() . '.end_date', '%' . $like . '%')
                ->or
                ->like('creator_users.firstname', '%' . $like . '%')
                ->or
                ->like('creator_users.lastname', '%' . $like . '%');
            $where->addPredicate($nestedWhere);

            $orderList = [];
            foreach ($order as $entity) {
                $orderList[] = $orderColumns[$entity['column']] . ' ' . $entity['dir'];
            }

            $select
                ->columns($columns)
                ->join(
                    ['reservations' => DbTables::TBL_BOOKINGS],
                    $this->getTable() . '.res_id = reservations.id',
                    [],
                    Select::JOIN_INNER
                )
                ->join(
                    ['task_types' => DbTables::TBL_TASK_TYPE],
                    $this->getTable() . '.task_type = task_types.id',
                    ['task_type_name' => 'name'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['creators' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = creators.task_id AND creators.type = ' . TaskService::STAFF_CREATOR),
                    [],
                    Select::JOIN_INNER
                )
                ->join(
                    ['creator_users' => DbTables::TBL_BACKOFFICE_USERS],
                    'creators.user_id = creator_users.id',
                    [
                        'creator_id' => 'id',
                        'creator_name' => new Expression('CONCAT(creator_users.firstname, " ", creator_users.lastname)')
                    ],
                    Select::JOIN_INNER
                )
                ->join(
                    ['responsibles' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = responsibles.task_id AND responsibles.type = ' . TaskService::STAFF_RESPONSIBLE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['responsible_users' => DbTables::TBL_BACKOFFICE_USERS],
                    'responsibles.user_id = responsible_users.id',
                    [
                        'responsible_id' => 'id',
                        'responsible_name' => new Expression('CONCAT(responsible_users.firstname, " ", responsible_users.lastname)')
                    ],
                    Select::JOIN_LEFT
                )
                ->where($where)
                ->order($orderList)
                ->group($this->getTable() . '.id')
                ->offset((int)$start)
                ->limit((int)$length)
                ->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
        });

        $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $result2   = $statement->execute();
        $row       = $result2->current();
        $total     = $row['total'];

        return [
            'result' => $result,
            'total'  => $total
        ];
    }

    /**
     * @param $apartmentId
     * @param $currentDate
     * @return \ArrayObject
     */
    public function getHousekeepingTask($taskId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result =  $this->fetchOne(function (Select $select) use($taskId) {
            $where = new Where();
            $where->equalTo($this->getTable() . '.id', $taskId)
                  ->nest()
                  ->equalTo($this->getTable() . '.is_hk', TaskService::TASK_IS_HOUSEKEEPING)
                  ->or
                  ->equalTo($this->getTable() . '.task_type', TaskService::TYPE_CLEANING)
                  ->unnest();
            $select
                ->columns([
                    'id'          => 'id',
                    'title'       => 'title',
                    'description' => 'description',
                    'start_date'  => 'start_date',
                    'end_date'    => 'end_date',
                    'task_status' => 'task_status',
                    'comments'    => new Expression("(
                    SELECT
                    GROUP_CONCAT(
                        CONCAT('<blockquote class=\"comment-blockquote\">', '<p>', CONCAT('\n', value), '</p><footer>', users.firstname, ' ', users.lastname, ', ',  `timestamp`, ' (Amsterdam Time)', '</footer></blockquote>') SEPARATOR ''
                    )
                    FROM " . DbTables::TBL_ACTION_LOGS . "
                    LEFT JOIN " . DbTables::TBL_BACKOFFICE_USERS . " AS users ON users.id = " . DbTables::TBL_ACTION_LOGS . ".user_id
                    WHERE module_id = " . Logger::MODULE_TASK . " AND identity_id = " . $taskId . " AND action_id = " . Logger::ACTION_COMMENT . "
                )"),
                ])
                ->where($where)
                ->join(
                    ['verifiers' => DbTables::TBL_TASK_STAFF],
                    new Expression('verifiers.task_id = ' . $this->getTable() . '.id AND verifiers.type=' . TaskService::STAFF_VERIFIER),
                    ['verifier_id' => 'user_id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['reservations' => DbTables::TBL_BOOKINGS],
                    'reservations.id = ' . $this->getTable() . '.res_id',
                    [
                        'res_id' => 'id',
                        'res_number'
                    ],
                    Select::JOIN_LEFT
                )
                ->group($this->getTable() . '.id');
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    /**
     * @param $param
     * @return \ArrayObject
     */
    public function getResForHousekeeperBasedOnTasks($param)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result =  $this->fetchAll(function (Select $select) use($param) {
            $select->where
                ->expression('DATE('.$this->getTable() . '.start_date) = "'.$param['currentDay'].'"', [])
                ->nest()
                ->equalTo($this->getTable() . '.is_hk', TaskService::TASK_IS_HOUSEKEEPING)
                ->or
                ->equalTo($this->getTable() . '.task_type', TaskService::TYPE_CLEANING)
                ->unnest()
                ->equalTo($this->getTable().'.team_id', $param['teamId'])
                ->notEqualTo($this->getTable().'.property_id', 0)
                ->isNotNull($this->getTable().'.property_id')
                ->isNotNull($this->getTable().'.res_id');

            //checking the housekeeper to be directly responsible or verifier of the task
            // or in case of GEM only unassigned tasks and the ones that he's verifier for
            if (!$param['isGlobal']) {
                //if the user is housekeeper
                if (TeamService::STAFF_MEMBER == $param['roleInTeam']) {
                    $select->where
                        ->isNotNull('task_staff.id')
                        ->in('task_staff.type', [TaskService::STAFF_RESPONSIBLE, TaskService::STAFF_HELPER]);

                    $select
                        ->join(
                            ['task_staff' => DbTables::TBL_TASK_STAFF],
                            new Expression(
                                'task_staff.task_id = '.$this->getTable() . '.id
                            AND
                            task_staff.user_id=' . $param['userId']
                            ),
                            [],
                            Select::JOIN_LEFT
                        );
                //if the user is GEM
                } else  if (TeamService::STAFF_OFFICER == $param['roleInTeam']) {
                }
            }

            $select
                ->columns(['id', 'property_id', 'task_status', 'res_id', 'end_date', 'start_date', 'task_type', 'priority'])
                ->join(
                    ['responsible' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = responsible.task_id AND responsible.type = ' . TaskService::STAFF_RESPONSIBLE),
                    ['responsible_id' => 'user_id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['verifier' => DbTables::TBL_TASK_STAFF],
                    new Expression($this->getTable() . '.id = verifier.task_id AND verifier.type = ' . TaskService::STAFF_VERIFIER),
                    ['verifier_id' => 'user_id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.property_id = apartments.id',
                    [
                        'apartment_name' => 'name',
                        'unit_number'    => 'unit_number'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['incidents' => DbTables::TBL_TASK],
                    new Expression(
                        'incidents.task_type = ' . TaskService::TYPE_INCIDENT_REPORT . '
                        AND
                        incidents.task_status NOT IN(' . TaskService::STATUS_VERIFIED . ' ,' . TaskService::STATUS_CANCEL . ')
                        AND
                        (' .
                            $this->getTable() . '.id = incidents.related_task OR ' . $this->getTable() . '.res_id = incidents.res_id
                        )'
                    ),
                    ['incidents' => new Expression('count(incidents.id)')],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['reservations' => DbTables::TBL_BOOKINGS],
                    $this->getTable() . '.res_id = reservations.id',
                    ['arrival_status' => 'arrival_status'],
                    Select::JOIN_LEFT
                )
                ->group($this->getTable() . '.id');
            if (isset($param['sortId']) && (int)$param['sortId']) {
                if ((int)$param['sortId'] == TaskService::STAFF_RESPONSIBLE) {
                    $select->join(
                        ['users' => DbTables::TBL_BACKOFFICE_USERS],
                        'responsible.user_id = users.id',
                        [],
                        Select::JOIN_LEFT
                    );

                } elseif((int)$param['sortId'] == TaskService::STAFF_VERIFIER) {
                    $select->join(
                        ['users' => DbTables::TBL_BACKOFFICE_USERS],
                        'verifier.user_id = users.id',
                        [],
                        Select::JOIN_LEFT
                    );
                }
                $select->order([
                    'users.firstname' => 'ASC',
                    'users.lastname' => 'ASC',
                    $this->getTable() . '.start_date' => 'ASC',
                ]);
            } else {
                $select->order([
                    $this->getTable() . '.start_date' => 'ASC',
                    $this->getTable() . '.priority'   => 'DESC',
                    $this->getTable() . '.id'         => 'ASC',
                ]);
            }

        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    /**
     * @param array $filter
     * @return \DDD\Domain\Task\Minimal[]
     */
    public function getIncidentReports($filter)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();

        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Task\Minimal());
        $result =  $this->fetchAll(function (Select $select) use($filter) {
            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.task_type', TaskService::TYPE_INCIDENT_REPORT)
                ->notIn(
                    'task_status',
                    [TaskService::STATUS_CANCEL, TaskService::STATUS_VERIFIED]
                );

            if (!empty($filter['res_id'])) {
                $where->equalTo($this->getTable() . '.res_id', $filter['res_id']);
            }

            if (!empty($filter['related_task_id'])) {
                $where->equalTo($this->getTable() . '.related_task', $filter['related_task']);
            }

            $select
                ->columns(['id', 'title', 'priority'])
                ->where($where);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    /**
     * @param int $taskId
     * @return strint|bool
     */
    public function getTaskTypeGroup($taskId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use($taskId) {
            $select->columns(['id','property_id', 'res_id','task_status']);
            $select
                ->join(
                    ['task_types' => DbTables::TBL_TASK_TYPE],
                    $this->getTable() . '.task_type = task_types.id',
                    ['group'],
                    Select::JOIN_INNER
                )
                ->where([
                    $this->getTable() .'.id' => $taskId,
                ]);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return ($result ? $result['group'] : false);
    }

    public function checkExistTaskByParams($resId, $taskType, $teamId, $creatorId)
    {
        return $this->fetchOne(function (Select $select) use ($resId, $taskType, $teamId, $creatorId) {
            $select->columns(['id'])
            ->join(
                ['task_creators' => DbTables::TBL_TASK_STAFF],
                new Expression($this->getTable() . '.id = task_creators.task_id AND task_creators.type = ' . TaskService::STAFF_CREATOR),
                [],
                Select::JOIN_INNER
            );

            $select->where
                ->equalTo($this->getTable() . '.res_id', $resId)
                ->equalTo($this->getTable() . '.task_type', $taskType)
                ->equalTo($this->getTable() . '.team_id', $teamId)
                ->equalTo('task_creators.user_id', $creatorId);
        });
    }
}
