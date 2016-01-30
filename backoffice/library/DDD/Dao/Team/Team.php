<?php
namespace DDD\Dao\Team;

use DDD\Domain\Team\ForSelect;
use DDD\Domain\Team\PeopleTeamsTableRow;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use DDD\Service\Team\Team as TeamService;
use DDD\Service\User as UserService;

use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;

class Team extends TableGatewayManager
{
    protected $table = DbTables::TBL_TEAMS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Team\Team');
    }

    public function getTeamListDetails($offset, $limit, $sortCol, $sortDir, $like, $deactivatedIncluded = '1')
    {
        $oldEntityPrototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new PeopleTeamsTableRow());

        $result = $this->fetchAll(
            function (Select $select) use($offset, $limit, $sortCol, $sortDir, $like, $deactivatedIncluded) {
                $sortColumns = [
                    'is_disable',
                    'name',
                    'description',
                    'size',
                    'usage_department',
                    'usage_notifiable',
                    'usage_frontier',
                    'usage_security',
                    'usage_taskable',
                    'usage_procurement',
                    'usage_hiring',
                    'usage_storage',
                ];

                $select
                    ->columns(
                        [
                            'id'                => 'id',
                            'is_active'         => 'is_disable',
                            'name'              => 'name',
                            'description'       => 'description',
                            'usage_department'  => 'usage_department',
                            'usage_notifiable'  => 'usage_notifiable',
                            'usage_frontier'    => 'usage_frontier',
                            'usage_security'    => 'usage_security',
                            'usage_taskable'    => 'usage_taskable',
                            'usage_procurement' => 'usage_procurement',
                            'usage_hiring'      => 'usage_hiring',
                            'usage_storage'     => 'usage_storage',
                        ]
                    )
                    ->join(
                        ['creator' => DbTables::TBL_TEAM_STAFF],
                        new Expression($this->getTable() . '.id = creator.team_id AND creator.type =' . TeamService::STAFF_CREATOR),
                        []
                    )
                    ->join(
                        ['staff' => DbTables::TBL_TEAM_STAFF],
                        new Expression($this->getTable() . '.id = staff.team_id AND staff.type !=' . TeamService::STAFF_CREATOR .
                            ' AND staff.type !=' . TeamService::STAFF_DIRECTOR .
                            ' AND staff.user_id !=' . UserService::ANY_TEAM_MEMBER_USER_ID),
                        ['size' => new Expression('COUNT(distinct(staff.user_id))')],
                        Select::JOIN_LEFT
                );

                switch ($deactivatedIncluded) {
                    case '2':
                        $select->where->equalTo($this->getTable() . '.is_disable', 1); // only inactive ones
                        break;
                    case '1':
                        $select->where->equalTo($this->getTable() . '.is_disable', 0); // only active ones
                        break;
                    case '0':
                        // all
                        break;
                }

                if ($like !== '') {
                    $nestedWhere = new Predicate();
                    $nestedWhere->like($this->getTable() . '.description', '%' . $like . '%');
                    $nestedWhere->or;
                    $nestedWhere->like($this->getTable() . '.name', '%' . $like . '%');

                    $select->where->andPredicate($nestedWhere);
                }

                $select
                    ->group($this->getTable() . '.id')
                    ->order($sortColumns[$sortCol] . ' ' . $sortDir)
                    ->offset((int)$offset)
                    ->limit((int)$limit);
            }
        );

        $this->resultSetPrototype->setArrayObjectPrototype($oldEntityPrototype);

        return $result;
    }

    public function getTeamListCount($like, $all = 1)
    {
        if ($all == '1') {
            $disable = ' is_disable = 0';
        } elseif ($all == '2') {
            $disable = ' is_disable = 1';
        } else {
            $disable = null;
        }

        $result = $this->fetchAll(
            function (Select $select) use($like, $disable){
                $select
                    ->columns(['id'])
                    ->join(
                        ['creator' => DbTables::TBL_TEAM_STAFF],
                        new Expression($this->getTable() . '.id = creator.team_id AND creator.type =' . TeamService::STAFF_CREATOR),
                        []
                    )->join(
                        ['user2' => DbTables::TBL_BACKOFFICE_USERS],
                        'creator.user_id = user2.id',
                        ['creator_name' => new Expression('CONCAT(user2.firstname, " ", user2.lastname)')],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['director' => DbTables::TBL_TEAM_STAFF],
                        new Expression($this->getTable() . '.id = director.team_id AND director.type=' . TeamService::STAFF_DIRECTOR),
                        ['director_id' => 'user_id'],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['user' => DbTables::TBL_BACKOFFICE_USERS],
                        'director.user_id = user.id',
                        ['user_name' => new Expression('CONCAT(user.firstname, " ", user.lastname)'), 'firstname', 'lastname'],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['staff' => DbTables::TBL_TEAM_STAFF],
                        new Expression($this->getTable() . '.id = staff.team_id AND staff.type !=' . TeamService::STAFF_CREATOR .
                            ' AND staff.type !=' . TeamService::STAFF_DIRECTOR .
                            ' AND staff.user_id !=' . UserService::ANY_TEAM_MEMBER_USER_ID),
                        ['count' => new Expression('COUNT(distinct(staff.user_id))')],
                        Select::JOIN_LEFT
                );

                $select->where->and->nest
                    ->like($this->getTable() . '.name', '%' . $like . '%')
                    ->or->like($this->getTable() . '.description', '%' . $like . '%')
                    ->or->expression('CONCAT(user.firstname, " ", user.lastname) like "%'.$like.'%"',[])
                ->unnest;

                (!is_null($disable)) ?  $select->where->and->nest->expression($disable,[]) : '';
                $select->group($this->getTable() . '.id');
            }
        );

        return $result->count();
    }

    public function getTeamList($directorId, $isDepartment = null, $active = false, $isTaskable = false, $isSecurity = false, $isHiring = false)
    {
        return $this->fetchAll(
            function (Select $select) use($directorId, $isDepartment, $active, $isTaskable, $isSecurity, $isHiring) {
                $select
                    ->columns(
                        [
                            'id',
                            'name',
                            'description',
                            'usage_department',
                            'usage_notifiable',
                            'is_disable',
                            'created_date'
                        ]
                    )
                    ->join(
                        ['director' => DbTables::TBL_TEAM_STAFF],
                        new Expression($this->getTable() . '.id = director.team_id AND director.type=' . TeamService::STAFF_DIRECTOR),
                        ['director_id' => 'user_id'],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['user' => DbTables::TBL_BACKOFFICE_USERS],
                        'director.user_id = user.id',
                        ['firstname', 'lastname'],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['staff' => DbTables::TBL_TEAM_STAFF],
                        new Expression($this->getTable() . '.id = staff.team_id AND staff.type !=' . TeamService::STAFF_CREATOR),
                        ['count' => new Expression('COUNT(staff.id)')],
                        Select::JOIN_LEFT
                );

                $condition = [];
                if ($directorId > 0) {
                    $condition['director.user_id'] = $directorId;
                }

                if ($isDepartment) {
                    $condition[$this->getTable() . '.usage_department'] = $isDepartment;
                }

                if ($isTaskable) {
                    $condition[$this->getTable() . '.usage_taskable'] = 1;
                }

                if ($isSecurity) {
                    $condition[$this->getTable() . '.usage_security'] = 1;
                }

                if ($isHiring) {
                    $condition[$this->getTable() . '.usage_hiring'] = 1;
                }

                if ($active) {
                    $condition[$this->getTable() . '.is_disable'] = 0;
                }

                $select->where($condition);
                $select->group($this->getTable() . '.id');
                $select->order($this->getTable() . '.name');
            }
        );
    }

    /**
     * @param $userId
     * @return \DDD\Domain\Team\Team[]
     */
    public function getUserTeams($userId, $isSecurity = false)
    {
        $result = $this->fetchAll(function (Select $select) use($userId, $isSecurity){
            $columns = ['id', 'name'];
            $select
                ->join(
                    ['staff' => DbTables::TBL_TEAM_STAFF],
                    new Expression('staff.team_id = ' . $this->getTable() . '.id AND staff.type NOT IN (' . TeamService::STAFF_CREATOR . ', ' . TeamService::STAFF_DIRECTOR . ')'),
                    ['staff_type' => new Expression('max(staff.type)')],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['director' => DbTables::TBL_TEAM_STAFF],
                    new Expression($this->getTable() . '.id = director.team_id AND director.type = ' . TeamService::STAFF_DIRECTOR),
                    ['director_id' => 'id'],
                    Select::JOIN_LEFT
            );

            $where = new Where();

            $where->equalTo('staff.user_id', $userId);

            if ($isSecurity) {
                $where->equalTo($this->getTable() . '.usage_security', 1);
            }
            $select->where($where);
            $select->group($this->getTable() . '.id')
                ->order('staff.type DESC')
                ->columns($columns);
        });
        return $result;
    }

    public function checkName($name, $id){
        $result = $this->fetchOne(
            function (Select $select) use($name, $id) {
                $select->where(['name' => $name]);
                if($id > 0) {
                    $select->where('id <> '. (int)$id);
                }
            }
        );
        return !empty($result) ? $result : null;
    }

    /**
     * @param int $id
     * @return \DDD\Domain\Team\Team
     */
    public function getTeamBasicInfo($id)
    {
        $result =  $this->fetchOne(
            function (Select $select) use($id) {
                $select
                    ->join(
                        ['director' => DbTables::TBL_TEAM_STAFF],
                        new Expression($this->getTable() . '.id = director.team_id AND director.type = ' . TeamService::STAFF_DIRECTOR),
                        ['director_id' => 'user_id'],
                        Select::JOIN_LEFT
                    )
                    ->where(
                        [$this->getTable() . '.id' => $id]
                );
            }
        );
        return $result;
    }

    /**
     * @param $apartmentId
     * @return \DDD\Domain\Team\Team
     */
    public function getFrontierTeamByApartment($apartmentId)
    {
        $result =  $this->fetchOne(
            function (Select $select) use($apartmentId) {
                $where = new Where();
                $nestedWhere = new Where();
                $nestedWhere
                    ->equalTo('team_apartments.apartment_id', $apartmentId)
                    ->or
                    ->equalTo('building_apartments.apartment_id', $apartmentId);

                $where
                    ->equalTo('usage_frontier', 1)
                    ->notEqualTo('is_disable', 1)
                    ->addPredicate($nestedWhere);

                $select
                    ->columns(['id', 'name'])
                    ->join(
                        ['team_apartments' => DbTables::TBL_TEAM_FRONTIER_APARTMENTS],
                        $this->getTable() . '.id = team_apartments.team_id',
                        [],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['team_buildings' => DbTables::TBL_TEAM_FRONTIER_BUILDINGS],
                        $this->getTable() . '.id = team_buildings.team_id',
                        [],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['building_apartments' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                        'team_buildings.building_id = building_apartments.apartment_group_id',
                        [],
                        Select::JOIN_LEFT
                    )
                ->where($where);
            }
        );
        return $result;
    }

    public function getFrontierTeamByBuilding($buildingId)
    {
        $result =  $this->fetchOne(
            function (Select $select) use($buildingId) {
                $where = new Where();

                $where
                    ->equalTo('usage_frontier', 1)
                    ->notEqualTo('is_disable', 1)
                    ->equalTo('team_buildings.building_id', $buildingId);

                $select
                    ->columns(['id', 'name'])
                    ->join(
                        ['team_buildings' => DbTables::TBL_TEAM_FRONTIER_BUILDINGS],
                        $this->getTable() . '.id = team_buildings.team_id',
                        [],
                        Select::JOIN_LEFT
                    )
                ->where($where);
            }
        );
        return $result;
    }

    /**
     * @param $teamId
     * @return string
     */
    public function getTeamNameById($teamId)
    {
        $oldEntityPrototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result =  $this->fetchOne(
            function (Select $select) use($teamId) {
                $select->columns([
                    'name'
                ]);

                $select->where->equalTo('id', $teamId);
            }
        );

        $this->resultSetPrototype->setArrayObjectPrototype($oldEntityPrototype);

        return $result['name'];
    }

    /**
     * @return ForSelect[]
     */
    public function getPermanentTeams()
    {
        $oldEntityPrototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new ForSelect());

        $result = $this->fetchAll(
            function (Select $select) {
                $select->columns([
                    'id',
                    'name'
                ]);

                $select->where->equalTo('is_permanent', 1);
            }
        );

        $this->resultSetPrototype->setArrayObjectPrototype($oldEntityPrototype);

        return $result;
    }
}
