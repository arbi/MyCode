<?php

namespace DDD\Dao\Finance;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class LegalEntities extends TableGatewayManager
{
    protected $table = DbTables::TBL_LEGAL_ENTITIES;
    public function __construct($sm, $domain = 'DDD\Domain\Finance\LegalEntities')
    {
        parent::__construct($sm, $domain);
    }

	public function getForSelect($legalEntityId = 0) {
        $result = $this->fetchAll(function (Select $select) use($legalEntityId) {
			$select->columns(array('id', 'name', 'country_id'))
				->where->equalTo('active', '1')
                    ->or->equalTo('id', $legalEntityId);

			$select->order('name');
		});

        $resultArray = [0 => '-- Please Select --'];
        foreach ($result as $row) {
            $resultArray[$row->getId()] = $row->getName();
        }
		return $resultArray;
	}

    /**
     *
     * @param string $like
     * @param boolean $onlyActive
     * @param boolean $asArray
     * @return \DDD\Domain\Finance\LegalEntity[]|\ArrayObject
     */
    public function getAllSuppliers($like = false, $onlyActive = false, $asArray = false)
    {
        if ($asArray) {
            $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        }

        return $this->fetchAll(function (Select $select) use ($like, $onlyActive) {
            $select->columns(['id', 'name', 'country_id']);

            if ($like) {
                $select->where->like('name', '%'.$like.'%');
                $select->where->like('country', '%'.$like.'%');
            }

            if ($onlyActive) {
                $select->where->equalTo('active', 1);
            }

            $select->order('name');
        });
    }

    public function getLegalEntitiesList($offset, $limit, $sortCol, $sortDir, $like, $all = '1')
    {
        if ($all === '1') {
		    $whereAll = ' active = 1';
	    } elseif ($all === '2') {
		    $whereAll = ' active = 0';
	    } else {
		    $whereAll = null;
	    }

        $sortColumns = ['active', 'name', 'country', 'description', 'id'];

        $result = $this->fetchAll(function (Select $select) use ($offset, $limit, $sortCol, $sortDir, $like, $whereAll, $sortColumns) {
            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.country_id = country.id',
                [],
                Select::JOIN_LEFT
            )->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                'details.id = country.detail_id',
                ['country' => 'name'],
                Select::JOIN_LEFT
            );

            $select->where->and->nest
               ->like($this->getTable() . '.name', '%'.$like.'%')
               ->or
               ->like($this->getTable() . '.description', '%'.$like.'%')
               ->or
               ->like('details.name', '%'.$like.'%')
               ->unnest();

            if (!is_null($whereAll)) {
                $select->where($whereAll);
            }

            $select->order($sortColumns[$sortCol].' '.$sortDir)
                ->offset((int)$offset)
                ->limit((int)$limit);

		});
		return $result;
    }

    public function getLegalEntitiesCount($like, $all = '1')
    {
        if ($all === '1') {
		    $whereAll = 'AND active = 1';
	    } elseif ($all === '2') {
		    $whereAll = 'AND active = 0';
	    } else {
		    $whereAll = ' ';
	    }

        $columns = array('name');

        $result = $this->fetchAll(function (Select $select) use ($like, $whereAll, $columns) {
            $select->where("(name like '".$like."%'
                OR description like '".$like."%')
                $whereAll");

            $select->columns($columns);
		});

        return $result->count();
    }



    public function getLegalEntityById($id)
    {
        $columns = array('*');

        $result = $this->fetchOne(function (Select $select) use ($id, $columns) {
            $select->where
                   ->equalTo('id', $id);

            $select->columns($columns);
        });

        return $result;
    }

    public function checkName($id, $name, $countryId)
    {
        $result = $this->fetchOne(function (Select $select) use ($id, $name, $countryId) {
            $select->where->equalTo('name', $name);
            $select->where->equalTo('country_id', $countryId);
            if ($id) {
                $select->where->notEqualTo('id', $id);
            }
        });

        return $result;
    }

    public function getCountryNameByLegalEntityId($legalEntityId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($legalEntityId) {
           $select->join(
               ['countries' => DbTables::TBL_COUNTRIES],
               $this->getTable() . '.country_id = countries.id',
               [],
               Select::JOIN_INNER
           )
               ->join(
                   ['details' => DbTables::TBL_LOCATION_DETAILS],
                   'countries.detail_id = details.id',
                   ['country_name' => 'name'],
                   Select::JOIN_INNER
               );
            $select->where([$this->getTable() . '.id' => $legalEntityId]);

        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result['country_name'];
    }
}
