<?php

namespace DDD\Dao\Office;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;

use DDD\Service\Office as OfficeService;
use DDD\Service\Warehouse\Asset as AssetService;

class OfficeManager extends TableGatewayManager
{
    protected $table = DbTables::TBL_OFFICES;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Office\OfficeManager');
    }

    public function getOfficeList($id = null, $onlyActive = true)
    {
        if (!is_null($id)) {
            return $this->fetchOne(function (Select $select) use ($id) {
                $select
                    ->where([$this->getTable() . '.id' => $id])
                    ->order($this->getTable() . '.created_date DESC');
            });
        } else {
            return $this->fetchAll(function (Select $select) use ($onlyActive) {
                if ($onlyActive) {
                    $select->where([$this->getTable() . '.disable' => 0]);
                }

                $select->order($this->getTable() . '.created_date DESC');
            });
        }
    }

    /**
     * @param int $id
     * @param bool|true $onlyActive
     * @return \ArrayObject|null
     */
    public function getOfficeDetailsById($id, $onlyActive)
    {
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($id, $onlyActive) {
            $select->columns([
                'id',
                'name',
                'disable',
                'address',
                'phone',
                'city_id'
            ]);

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                $this->getTable() . '.city_id = city.id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['location_details' => DbTables::TBL_LOCATION_DETAILS],
                'city.detail_id = location_details.id',
                ['city_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.id', $id);

            if ($onlyActive) {
                $select->where
                    ->equalTo($this->getTable() . '.disable', OfficeService::STATUS_ENABLE);
            }
        });

        return $result;
    }

    /**
     * @param $offset
     * @param $limit
     * @param $sortCol
     * @param $sortDir
     * @param $like
     * @param int $all
     * @return \DDD\Domain\Office\OfficeManager[]
     */
    public function getOfficeListDetail($offset, $limit, $sortCol, $sortDir, $like, $all = 1)
    {
	    if ($all == '1') {
		    $disable = ' AND ' . $this->getTable() . '.disable = 0';
	    } elseif ($all == '2') {
		    $disable = ' AND ' . $this->getTable() . '.disable = 1';
	    } else {
		    $disable = ' ';
	    }

        return $this->fetchAll(function (Select $select) use ( $offset, $limit, $sortCol, $sortDir, $like, $disable) {
            $sortColumns = ['disable', 'name', 'city', 'address'];

            $select->where('(' . $this->getTable() . '.name like "%' . $like . '%"
                OR ' . $this->getTable() . '.description like "%' . $like . '%")
                ' . $disable);
            $select
                ->join(
                    ['cities' => DbTables::TBL_CITIES],
                    $this->getTable() . '.city_id = cities.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['geo' => DbTables::TBL_LOCATION_DETAILS],
                    'cities.detail_id = geo.id',
                    ['city' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['countries' => DbTables::TBL_COUNTRIES],
                    $this->getTable() . '.country_id = countries.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['geo2' => DbTables::TBL_LOCATION_DETAILS],
                    'countries.detail_id = geo2.id',
                    ['country' => 'name'],
                    Select::JOIN_LEFT
                )
                ->group($this->getTable() . '.id')
                ->order($sortColumns[$sortCol].' '.$sortDir)
                ->offset((int)$offset)
                ->limit((int)$limit);
        });
    }

    public function getOfficeCount($like, $all = 1)
    {
        $column = ['id'];

	    if ($all == '1') {
		    $disable = ' AND disable = 0';
	    } elseif ($all == '2') {
		    $disable = ' AND disable = 1';
	    } else {
		    $disable = ' ';
	    }

        $results = $this->fetchAll(function (Select $select) use($like, $disable, $column) {
            $select->columns($column);
            $select->where("(name like '%" . $like.
                "%' OR description like '%" . $like . "%')
                $disable"
            );
        });

        return $results->count();
    }

    public function checkName($name, $id)
    {
        $result = $this->fetchOne(
            function (Select $select) use ($name, $id) {
                $select->where(['name' => $name]);

                if ($id > 0) {
                    $select->where->notEqualTo('id', (int)$id);
                }
            }
        );

        return !empty($result) ? $result : null;
    }

    /**
     * @param string $name
     * @param bool|true $onlyActive
     *
     * @return \DDD\Domain\Office\OfficeManager[]
     */
    public function searchOfficeByName($name, $onlyActive = true)
    {
        $result = $this->fetchAll(function (Select $select) use ($name, $onlyActive) {
            $select->columns([
                'id',
                'name',
                'disable'
            ]);

            $select
                ->join(
                    ['cities' => DbTables::TBL_CITIES],
                    $this->getTable() . '.city_id = cities.id',
                    [],
                    Select::JOIN_LEFT)
                ->join(
                    ['geo' => DbTables::TBL_LOCATION_DETAILS],
                    'cities.detail_id = geo.id',
                    ['city' => 'name'],
                    Select::JOIN_LEFT);

            if (FALSE !== $name) {
                $select->where
                    ->like($this->getTable() . '.name', '%' . $name . '%');
            }


            if ($onlyActive) {
                $select->where
                    ->equalTo($this->getTable() . '.disable', OfficeService::STATUS_ENABLE);
            }
        });

        return $result;
    }

    public function getOfficeListByCity($cityId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $locationType = AssetService::ENTITY_TYPE_OFFICE;

        $result = $this->fetchAll(function (Select $select) use ($cityId, $locationType) {
            $select->columns([
                'id',
                'name',
                'description',
                'address',
                'countryId'    => 'country_id',
                'cityId'       => 'city_id',
                "locationType" => new Expression("{$locationType}")]);

            $select->where->equalTo('city_id', $cityId);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    public function searchContacts($searchQuery)
    {
        return $this->fetchAll(function (Select $select) use ($searchQuery) {
            $select->columns(['id', 'name']);
            $select->where->
                like($this->getTable() . '.name', '%' . $searchQuery . '%');
        });
    }

    public function getOfficeCurrency($officeId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($officeId) {
            $select->columns([]);
            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.country_id = country.id',
                ['currency_id'],
                Select::JOIN_LEFT
            );
            $select->where->equalTo($this->getTable() . '.id', $officeId);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }
}
