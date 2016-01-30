<?php
namespace DDD\Dao\ApartmentGroup;

use DDD\Domain\ApartmentGroup\ApartmentGroupTableRow;
use DDD\Service\Warehouse\Asset as AssetService;
use DDD\Domain\ApartmentGroup\ForSelect;
use DDD\Service\Psp;

use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Library\Constants\Objects;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Zend\Stdlib\ArrayObject;

class ApartmentGroup extends TableGatewayManager
{
    protected $table = DbTables::TBL_APARTMENT_GROUPS;
    public function __construct($sm, $domain = 'DDD\Domain\ApartmentGroup\ApartmentGroup')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $id
     *
     * @return \DDD\Domain\ApartmentGroup\ApartmentGroup
     */
    public function getRowById($id) {
        $this->setEntity(new \DDD\Domain\ApartmentGroup\ApartmentGroup());

        $result = $this->fetchOne(function (Select $select) use ($id) {
            $select->columns([
                'id',
                'name',
                'email',
                'active',
                'timezone',
                'usage_concierge_dashboard',
                'group_manager_id',
                'usage_cost_center',
                'usage_building',
                'usage_apartel',
                'usage_performance_group',
                'country_id',
                'psp_id',
            ]);

            $select->join(
                ['building_details' => DbTables::TBL_BUILDING_DETAILS],
                $this->getTable() . '.id = building_details.apartment_group_id',
                [
                    'building_phone',
                    'ki_page_type',
                    'assigned_office_id',
                ],
                Select::JOIN_LEFT
            )->join(
                ['building_section' => DbTables::TBL_BUILDING_SECTIONS],
                $this->getTable() . '.id = building_section.building_id',
                [
                    'building_section_id' => 'id',
                ],
                Select::JOIN_LEFT
            );

            $select->where(array($this->getTable().'.id' => $id));
        });

        return $result;
    }

    public function checkGroupName($name, $id = 0, $nameIsChanged = false){
        $result = $this->fetchOne(function (Select $select) use($name, $id, $nameIsChanged) {
            $select->where
                ->equalTo('name', $name);

            if ($nameIsChanged && $id > 0) {
                $select->where
                    ->equalTo('id', $id);
            } elseif ($id > 0) {
                $select->where
                    ->notEqualTo('id', $id);
            }
        });

        return $result;
    }

    public function getConciergeDashboards($userId = false)
    {
        $result = $this->fetchAll(function (Select $select) use($userId) {
            $select
                ->columns(['id', 'name', 'usage_apartel', 'email', 'timezone'])
                ->where(['usage_concierge_dashboard' => 1])
                ->order(['name ASC']);

            if ($userId) {
                $select->join(
                    ['user_concierges' => DbTables::TBL_CONCIERGE_DASHBOARD_ACCESS],
                    'user_concierges.apartment_group_id = ' . $this->getTable() . '.id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->where('(' . $this->getTable() . '.active = 1 OR user_concierges.user_id = ' . $userId . ')');
            } else {
                $select->where(['active' => 1]);
            }
        });

        return $result;
    }

    public function getAllGroups($hasDevTestRole = false)
    {
        $result = $this->fetchAll(
            function (Select $select) use($hasDevTestRole){
                $select->columns(['id', 'name', 'usage_apartel'])
                    ->where(['active' => 1])
                    ->order(['name ASC']);

                if (!$hasDevTestRole) {
                    $select->where->notEqualTo(
                        $this->getTable() . '.id',
                        Constants::TEST_APARTMENT_GROUP
                    );
                }
            }
        );
        return $result;
    }

    public function getGroupListByManagersList($managersList)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchAll(function (Select $select) use ($managersList) {
            $select
                ->columns(['id', 'name'])
                ->where
                ->in('group_manager_id', $managersList);
        });
    }

    /**
     * Get buildings list to populate select elements
     *
     * @access public
     * @return ArrayObject
     */
    public function getBuildingsListForSelect($search = false, $countryId = false, $extraColumn = false)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $locationType = AssetService::ENTITY_TYPE_BUILDING;

        $result = $this->fetchAll(function (Select $select) use ($search, $countryId, $extraColumn, $locationType) {
            $select->columns([ 'id', 'name' ]);

            if ($extraColumn) {
                $select->columns([
                    'id',
                    'name',
                    'countryId'    => 'country_id',
                    'locationType' => new Expression("{$locationType}")]);
            }

            $select
                ->where(['usage_building' => 1, 'active' => 1])
                ->order(['name ASC']);

            if ($search) {
                $select->where->like('name', '%' . $search . '%');
            }

            if ($countryId) {
                $select->where->equalTo('country_id', $countryId);
            }
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    /**
     * Get apartment group list to populate select elements
     *
     * @access public
     * @return ArrayObject
     */
    public function getApartmentGroupsListForSelect()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) {
            $select
                ->columns([
                    'id',
                    'name',
                    'usage_apartel'
                ])
                ->join(
                    ['countries' => DbTables::TBL_COUNTRIES],
                    $this->getTable() . '.country_id = countries.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['location_details' => DbTables::TBL_LOCATION_DETAILS],
                    'countries.detail_id = location_details.id',
                    ['country' => 'name'],
                    Select::JOIN_LEFT
                )
                ->where(['active' => 1])
                ->order(['name ASC']);
        });
        return $result;
    }

    /**
     * @param int $selectedId
     * @return \ArrayObject
     */
    public function getBuildingListForSelectize($selectedId = 0)
    {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use($selectedId) {
            $where = new Where();
            $where->equalTo('usage_building', 1);

            if ($selectedId) {
                $where
                    ->NEST
                    ->equalTo($this->getTable() . '.active', 1)
                    ->OR
                    ->equalTo($this->getTable() . '.id', $selectedId)
                    ->UNNEST;
            } else {
                $where->equalTo('active', 1);
            }

            $select
                ->columns([
                    'id',
                    'name'
                ])
                ->join(
                    ['countries' => DbTables::TBL_COUNTRIES],
                    $this->getTable() . '.country_id = countries.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['location_details' => DbTables::TBL_LOCATION_DETAILS],
                    'countries.detail_id = location_details.id',
                    ['country' => 'name'],
                    Select::JOIN_LEFT
                )
                ->where($where)
                ->order(['name ASC']);
        });
        $this->setEntity($entity);
        return $result;
    }


    public function getBuildingsByAutocomplate(
        $query,
        $isBuilding     = true,
        $isActive       = false,
        $object         = false,
        $isApartel      = false,
        $hasDevTestRole = true
    ){

        if (!$object) {
            $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        }

        $result = $this->fetchAll(function (Select $select) use (
            $query,
            $isBuilding,
            $isActive,
            $isApartel,
            $hasDevTestRole
        ) {
            $select->columns(array('id', 'name', 'usage_apartel'));

            if ($isBuilding) {
                $select->where->equalTo('usage_building', 1);
            }

            if ($isActive) {
                $select->where->equalTo('active', 1);
            }

            if (!$hasDevTestRole) {
                $select->where->notEqualTo(
                    $this->getTable() . '.id',
                    Constants::TEST_APARTMENT_GROUP
                );
            }

            if ($isApartel) {
                $select->where->equalTo('usage_apartel', 1);
            }

            $select->where
                   ->like('name', '%' . $query . '%');
            $select->order(array('name ASC'));
        });
        return $result;
    }

    /**
     * @param string $query
     * @param int $limit
     * @param $hasDevTestRole
     * @return \DDD\Domain\ApartmentGroup\ApartmentGroup[]
     */
    public function getApartmentGroupsForOmnibox($query, $limit, $hasDevTestRole) {

        $result = $this->fetchAll(function (Select $select) use ($query, $limit, $hasDevTestRole) {
            $select->columns(array('id', 'name', 'usage_apartel'));
            $select->where->equalTo('active', 1);

            if (!$hasDevTestRole) {
                $select->where->notEqualTo(
                    $this->getTable() . '.id',
                    Constants::TEST_APARTMENT_GROUP
                );
            }

            $select->where->like('name', '%' . $query . '%');
            $select->order(['name ASC']);
            $select->limit($limit);
        });
        return $result;
    }

    /**
     * @param bool $hasDevTestRole
     * @return \Zend\Db\ResultSet\ResultSet|\DDD\Domain\ApartmentGroup\ApartmentGroup[]
     */
    public function getApartelList($hasDevTestRole = false, $onlyActive = true)
    {
        $result = $this->fetchAll(
            function (Select $select) use($hasDevTestRole, $onlyActive) {
                $select->columns([
                    'id',
                    'name',
                    'usage_apartel']);

                $select->where
                    ->equalTo('usage_apartel', 1);

                if ($onlyActive) {
                    $select->where
                        ->equalTo('active', 1);
                }

                $select->order(['name ASC']);

                if (!$hasDevTestRole) {
                    $select->where->notEqualTo(
                        $this->getTable() . '.id',
                        Constants::TEST_APARTMENT_GROUP
                    );
                }
            }
        );
        return $result;
    }

    public function getApartelListByQ($query)
    {
        return $this->fetchAll(
            function (Select $select) use ($query) {
                $select->columns(['id', 'name', 'usage_apartel']);
                $select->where(['usage_apartel' => 1, 'active' => 1]);
                $select->where->like('name', "%{$query}%");
                $select->order(['name ASC']);
            }
        );
    }

    public function getBuildingNameByApartmentId($apartmentId)
    {
        return $this->fetchOne(function (Select $select) use($apartmentId) {
            $select->columns(['name']);

            $select->join(
                ['group_item' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                $this->getTable() . '.id = group_item.apartment_group_id',
                [],
                Select::JOIN_RIGHT
            );

            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                'apartment.id = group_item.apartment_id',
                [],
                Select::JOIN_RIGHT
            );

            $select->where([
                'apartment.id' => $apartmentId,
                $this->getTable() . '.usage_building' => 1,
            ]);
        });
    }

    /**
     * @param $apartmentId
     * @return ForSelect[]
     */
    public function getApartelsByApartmentId($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new ForSelect());

        return $this->fetchAll(function(Select $select) use($apartmentId) {
            $select->columns(['id', 'name', 'usage_apartel']);

            $select->join(
                ['agi' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                $this->getTable() . '.id = agi.apartment_group_id',
                [],
                Select::JOIN_RIGHT
            );

            $select->join(
                ['a' => DbTables::TBL_APARTMENTS],
                'a.id = agi.apartment_id',
                [],
                Select::JOIN_RIGHT
            );

            $select->where([
                'a.id' => $apartmentId,
                $this->getTable() . '.usage_apartel' => 1,
            ]);
        });
    }

    /**
     * @param $apartmentId1
     * @param $apartmentId2
     * @param int $apartelId
     * @return bool
     */
    public function checkApartmentsInSameApartel($apartmentId1, $apartmentId2, $apartelId = 0)
    {
        $result = $this->fetchAll(function(Select $select) use($apartmentId1, $apartmentId2, $apartelId) {
            $select->columns(['id', 'name']);

            $select->join(
                ['agi' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                new Expression($this->getTable() . '.id = agi.apartment_group_id AND agi.apartment_id = ' . $apartmentId1),
                []
            );

            $select->join(
                ['agi2' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                new Expression($this->getTable() . '.id = agi2.apartment_group_id AND agi2.apartment_id = ' . $apartmentId2),
                []
            );

            $select->where->equalTo($this->getTable() . '.usage_apartel', 1);

            if ($apartelId > 0) {
                $select->where->equalTo($this->getTable() . '.id', $apartelId);
            }
        });

        if ($result->count()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param        $offset
     * @param        $limit
     * @param        $sortCol
     * @param        $sortDir
     * @param        $like
     * @param string $all
     * @param        $managerId
     * @param bool   $hasDevTestRole
     *
     * @return \Zend\Db\ResultSet\ResultSet|ApartmentGroupTableRow[]
     */
    public function getApartmentGroupsList(
        $offset,
        $limit,
        $sortCol,
        $sortDir,
        $like,
        $all = '1',
        $managerId,
        $hasDevTestRole = false
    ) {
        $this->resultSetPrototype->setArrayObjectPrototype(new ApartmentGroupTableRow());

        if ($all === '1') {
            $whereAll = ' active = 1';
        } elseif ($all === '2') {
            $whereAll = ' active = 0';
        } else {
            $whereAll = false;
        }

        $result = $this->fetchAll(
            function (Select $select) use (
                $offset,
                $limit,
                $sortCol,
                $sortDir,
                $like,
                $whereAll,
                $managerId,
                $hasDevTestRole
            ) {
                $columns = [
                    'active',
                    'name',
                    'group_manager_id',
                    'usage_cost_center',
                    'usage_concierge_dashboard',
                    'usage_building',
                    'usage_performance_group',
                    'usage_apartel',
                    'id'
                ];

                $sortColumns = [
                    'name',
                    'count',
                    'country',
                    'usage_cost_center',
                    'usage_concierge_dashboard',
                    'usage_building',
                    'usage_performance_group',
                    'usage_apartel',
                ];

                $select
                    ->join(
                        ['user' => DbTables::TBL_BACKOFFICE_USERS],
                        $this->getTable() . '.group_manager_id = user.id',
                        [
                            'first_name'   => 'firstname',
                            'last_name'    => 'lastname'
                        ],
                        Select::JOIN_LEFT
                    )->join(
                        ['group_items' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                        $this->getTable() . '.id = group_items.apartment_group_id',
                        ['count' => new Expression('COUNT(group_items.id)')],
                        Select::JOIN_LEFT
                    )->join(
                        ['c' => DbTables::TBL_COUNTRIES],
                        $this->getTable() . '.country_id = c.id',
                        [],
                        Select::JOIN_LEFT
                    )->join(
                        ['d' => DbTables::TBL_LOCATION_DETAILS],
                        'c.detail_id = d.id',
                        ['country' => 'name'],
                        Select::JOIN_LEFT
                    )->join(
                        ['apartel' => DbTables::TBL_APARTELS],
                        $this->getTable() . '.id = apartel.apartment_group_id',
                        ['apartel_id' => 'id'],
                        Select::JOIN_LEFT
                    );

                if (!is_null($managerId)) {
                    if ($hasDevTestRole) {
                        $select->where->nest
                            ->equalTo($this->getTable() . '.group_manager_id', $managerId)
                            ->or->equalTo(
                            $this->getTable() . '.id',
                            Constants::TEST_APARTMENT_GROUP
                        )->unnest();
                    } else {
                        $select->where->equalTo($this->getTable() . '.group_manager_id', $managerId);
                    }
                }

                $select->group($this->getTable() . '.id');

                $select->where->nest
                    ->like($this->getTable() . ".name", '%'. $like .'%')
                    ->or->like('d.name', '%' . $like . '%')
                ->unnest;

                if ($whereAll) {
                    $select->where($whereAll);
                }
                $select->columns($columns);

                $select->order($sortColumns[$sortCol-1] . ' ' . $sortDir);

                if ($limit != -1) {
                    $select
                        ->offset((int)$offset)
                        ->limit((int)$limit);
                }
            }
        );
        return $result;
    }

    public function prepareFormResources($hasDevTestRole = false)
    {
        $concierges = $this->fetchAll(
            function (Select $select) use($hasDevTestRole) {
                $select->where(['active' => 1]);

                if (!$hasDevTestRole) {
                    $select->where->notEqualTo(
                        $this->getTable() . '.id',
                        Constants::TEST_APARTMENT_GROUP
                    );
                }
            }
        );

        return $concierges;
    }

    /**
     * @param int $apartmentGroupId
     * @return array|\ArrayObject|null
     */
    public function getContactPhone($apartmentGroupId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $groupContactInfo = $this->fetchOne(
            function (Select $select) use($apartmentGroupId) {
                $select->join(
                    ['c' => DbTables::TBL_COUNTRIES],
                    $this->getTable() . '.country_id = c.id',
                    ['contact_phone', 'detail_id'],
                    Select::JOIN_LEFT
                );
                $select->join(
                    ['d' => DbTables::TBL_LOCATION_DETAILS],
                    'c.detail_id = d.id',
                    ['name'],
                    Select::JOIN_LEFT
                );
                $select->where->equalTo($this->getTable() . '.id', $apartmentGroupId);
            }
        );

        return $groupContactInfo;
    }

    /**
     * @param string $query
     * @param stdClass $user
     * @param int $limit
     * @return \DDD\Domain\ApartmentGroup\FrontierCard[]
     */
    public function getFrontierCardList($query, $user, $limit)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\ApartmentGroup\FrontierCard());
        // Execute for search purposes only
        if (!$query) {
            return false;
        }

        return $this->select(function (Select $select) use ($query, $user, $limit) {
            $columns = [
                'id', 'name'
            ];

            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.active', 1)
                ->equalTo($this->getTable() . '.usage_building', 1)
                ->like($this->getTable() . '.name', '%' . $query . '%');
            $select
                ->columns($columns)
                ->join(
                    ['country' => DbTables::TBL_COUNTRIES],
                    'country.id = ' . $this->getTable() . '.country_id',
                    []
                )
                ->join(
                    ['provinces' => DbTables::TBL_PROVINCES],
                    'provinces.country_id = country.id',
                    []
                )
                ->join(
                    ['cities' => DbTables::TBL_CITIES],
                    new Expression('cities.province_id = provinces.id AND cities.id = ' . $user->city_id),
                    []
                )
                ->where($where);
            if ($limit) {
                $select->limit($limit);
            }
        });
    }

    /**
     * @param int $id
     * @param bool|array $search
     * @return \DDD\Domain\ApartmentGroup\FrontierCard[]
     */
    public function getTheCard($id, $search = false)
    {
        $this->setEntity(new \DDD\Domain\ApartmentGroup\FrontierCard());
        // Execute for search purposes only
        if (!$id || !is_numeric($id)) {
            return false;
        }
        return $this->fetchAll(function (Select $select) use ($id, $search) {
            $columns = [
                'id', 'name'
            ];

            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.id', $id)
                ->equalTo($this->getTable() . '.usage_building', 1)
                ->equalTo($this->getTable() . '.active', 1)
                ->in('apartments.status', [Objects::PRODUCT_STATUS_LIVEANDSELLIG, Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE]);

            if ($search) {
                if (!is_numeric($search)) {
                    $where->NEST
                        ->like('apartments.name', '%' . $search . '%')
                        ->or
                        ->like('apartments.unit_number', '%' . $search . '%')
                        ->UNNEST;
                } else {
                    $where->NEST
                        ->like('apartments.name', '%' . $search . '%')
                        ->or
                        ->like('apartments.unit_number', '%' . $search . '%')
                        ->or
                        ->equalTo('apartments.bedroom_count', $search )
                        ->UNNEST;
                }
            }

            $select
                ->columns($columns)
                ->join(
                    ['building_apartments' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                    $this->getTable() . '.id = building_apartments.apartment_group_id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    'apartments.id = building_apartments.apartment_id',
                    [
                        'apartment_id' => 'id',
                        'apartment_name' => 'name',
                        'unit_number' => 'unit_number',
                        'bedroom_count' => 'bedroom_count',
                    ],
                    Select::JOIN_LEFT
                )
                ->where($where);
        });
    }

    /**
     * @param int $groupId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getApartmentsWithCurrencyByGroupId($groupId)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function (Select $select) use ($groupId) {
            $select->columns([]);
            $select->join(
                ['rel_groups' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                $this->getTable() . '.id = rel_groups.apartment_group_id',
                [],
                Select::JOIN_RIGHT
            );
            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                'apartments.id = rel_groups.apartment_id',
                ['id', 'currency_id'],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable() . '.id' => $groupId]);
        });
    }

    /**
     * @param int $managerId
     * @return int
     */
    public function getManagerGroupCount($managerId)
    {
        $entity = $this->getEntity();
        $this->setEntity(new ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($managerId) {
            $select->columns(['count' => new Expression('count(*)')]);
            $select->where(['group_manager_id' => $managerId]);
        });

        $this->setEntity($entity);

        return $result['count'];
    }

    /**
     * @param int $groupId
     * @return int
     * @todo take out business logic from DAO
     */
    public function getPsp($groupId)
    {
        /** @var \DDD\Domain\ApartmentGroup\ApartmentGroup $apartmentGroup */
        $apartmentGroup = $this->fetchOne(function (Select $select) use ($groupId) {
            $select->columns(['psp_id']);
            $select->where(['id' => $groupId]);
        });

        if ($apartmentGroup && $apartmentGroup->getPspId()) {
            return $apartmentGroup->getPspId();
        } else {
            return Psp::PSP_SQUARE_ID;
        }
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllGroupNamesButApartelsAtFirst()
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchAll(function(Select $select){
            $select->columns(['id', 'name', 'usage_apartel']);
            $select->where->equalTo($this->getTable() . '.active', 1);
            $select->order($this->getTable() . '.usage_apartel DESC, ' . $this->getTable() . '.name ASC');
        });
        $this->setEntity($prototype);
        return $result;
    }

}
