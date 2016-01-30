<?php

namespace DDD\Dao\Finance;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class Supplier extends TableGatewayManager
{
    protected $table = DbTables::TBL_SUPPLIERS;

    public function __construct($sm, $domain = 'DDD\Domain\Finance\Supplier')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $supplierId
     * @return \DDD\Domain\Finance\Supplier[]|\ArrayObject
     */
    public function getForSelect($supplierId = 0) {
		$result = $this->fetchAll(function (Select $select) use($supplierId) {
			$select->columns(array('id', 'name'))
				->where->equalTo('active', '1')
                    ->or->equalTo('id', $supplierId);

			$select->order('name');
		});

		return $result;
	}

    /**
     *
     * @param string $like
     * @param boolean $onlyActive
     * @param boolean $asArray
     * @return \DDD\Domain\Finance\Supplier[]|\ArrayObject
     */
    public function getAllSuppliers($like = false, $onlyActive = false, $asArray = false)
    {
        if ($asArray) {
            $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        }

        return $this->fetchAll(function (Select $select) use ($like, $onlyActive) {
            $select->columns(['id', 'name']);

            if ($like) {
                $select->where->like('name', '%'.$like.'%');
            }

            if ($onlyActive) {
                $select->where->equalTo('active', 1);
            }

            $select->order('name');
        });
    }

    public function getSuppliersList($offset, $limit, $sortCol, $sortDir, $like, $all = '1')
    {
        if ($all === '1') {
		    $whereAll = 'AND active = 1';
	    } elseif ($all === '2') {
		    $whereAll = 'AND active = 0';
	    } else {
		    $whereAll = ' ';
	    }
        $columns = array('active', 'name', 'description', 'id');
        $result = $this->fetchAll(function (Select $select) use ($offset, $limit, $sortCol, $sortDir, $like, $whereAll, $columns) {
            $select->where("(name like '%".$like."%'
                OR description like '%".$like."%')
                $whereAll");

            $select->columns($columns)
                   ->order($columns[$sortCol].' '.$sortDir)
                   ->offset((int)$offset)
                   ->limit((int)$limit);
		});

		return $result;
    }

    public function getSuppliersCount($like, $all = '1')
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

    /**
     * @param int $id
     * @return \DDD\Domain\Finance\Supplier|null
     */
    public function getSupplierById($id)
    {
        return $this->fetchOne(function (Select $select) use ($id) {
            $select->where->equalTo('id', $id);
        });
    }

    /**
     * @param $name
     * @param $id
     * @param $status
     * @return \DDD\Domain\Finance\Supplier
     */
    public function activeOrInactiveDuplicateSupplierExistsOrNot($name,$id,$status)
    {
        return $this->fetchOne(function (Select $select) use ($name, $status, $id) {
            $select->columns(['id']);
            $select->where
                ->equalTo('name', $name)
                ->equalTo('active', $status);

            if ($id > 0) {
                $select->where->notEqualTo('id', $id);
            }
        });
    }
}
