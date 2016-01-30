<?php

namespace Library\DbManager;

use Zend\Db\Sql\Where;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Validator\Db\NoRecordExists;

class TableGatewayManager extends AbstractTableGateway
{
	/**
	 * @access protected
	 * @var Adapter
	 */
    protected $adapter = null;

    /**
     * Possibility to process not nested but non conflicting tansactions
     * @var int $transactionCounter
     */
    static protected $transactionCounter = 0;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $class
     */
    public function __construct($sm, $class)
    {
        if (is_null($this->adapter)) {
            $this->adapter = $sm->get('dbadapter');
            $this->resultSetPrototype = new ResultSet();
            $this->resultSetPrototype->setArrayObjectPrototype(new $class());
            $this->initialize();
        }
    }

    /**
     * @param \Zend\Db\Sql\Where|array|string $where
     * @param array $columns
     * @param array $order
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function fetchAll($where = null, $columns = [], $order = null)
    {
        if ($where instanceof \Closure) {
           return $this->select($where);
        }

        return $this->select(function (Select $select) use ($where, $columns, $order) {
            if (!is_null($where)) {
                $select->where($where);
            }

            if (count($columns)) {
                $select->columns($columns);
            }

            if (!is_null($order)) {
                $select->order($order);
            }
        });
    }

    public function fetchOne($where = null, $columns = [])
    {
        if ($where instanceof \Closure) {
            $rowset = $this->select($where);
        } else {
            $rowset = $this->select(function (Select $select) use ($where, $columns) {
                if (!is_null($where)) {
                    $select->where($where);
                }

                if (count($columns)) {
                    $select->columns($columns);
                }
            });
        }

        return $rowset->current();
    }

    /**
     * @param mixed $entity
     */
	public function setEntity($entity)
    {
		$this->resultSetPrototype->setArrayObjectPrototype($entity);
	}

    /**
     * @return \ArrayObject
     */
	public function getEntity()
    {
		return $this->resultSetPrototype->getArrayObjectPrototype();
	}

	/**
	 * @param array $data
	 * @param bool|Where|\Closure|array $where
	 *
	 * @return int
	 * @throws \Exception
	 */
	public function save($data, $where = false)
    {
        if (!$where || (is_array($where) && !count($where))) {
            $this->insert($data);

            return $this->lastInsertValue;
        } else {
            return $this->update($data, $where);
        }
    }

    /**
     * @param bool|array $where
     */
    public function deleteWhere($where = false)
    {
        if ($where) {
            $this->delete($where);
        }
    }

    public function checkRowExist($table, $field, $value)
    {
    	if ($table === null) {
    		$table = $this->getTable();
    	}

        $params = ['adapter' => $this->adapter, 'table' => $table, 'field' => $field];
        $validator = new NoRecordExists($params);

        if ($validator->isValid($value)) {
        	return false;
        }

        return true;
    }

    public function getCount($where = null)
    {
        return $this->fetchOne(function (Select $select) use($where) {
    		$select->columns(['count' => new \Zend\Db\Sql\Expression('COUNT(*)')]);

    		if ($where !== null) {
    			$select->where($where);
    		}
    	});
    }

	public function beginTransaction()
    {
        if (!self::$transactionCounter) {
            $connection = $this->adapter->getDriver()->getConnection();
            $connection->beginTransaction();
        }

        self::$transactionCounter++;
	}

	public function commitTransaction()
    {
        self::$transactionCounter--;

        if (!self::$transactionCounter) {
            $connection = $this->adapter->getDriver()->getConnection();
            $connection->commit();
        }
	}

	public function rollbackTransaction()
    {
        self::$transactionCounter--;

        if (!self::$transactionCounter) {
            $connection = $this->adapter->getDriver()->getConnection();
            $connection->rollback();
        }
	}

    /**
     * Push data into table with multi-insert query.
     * Return affected rows count
     * @param array $data
     * @param bool $ignore
     * @return int
     */
    public function multiInsert($data, $ignore = false)
    {
        $count = 0;
        if (count($data)) {
            $ignore = $ignore ? 'IGNORE' : '';
            $columns = (array)current($data);
            $columns = array_keys($columns);
            $columnsCount = count($columns);
            $platform = $this->adapter->platform;
            foreach ($columns as &$column) {
                $column = $platform->quoteIdentifier($column);
            }
            $columns = "(" . implode(',', $columns) . ")";

            $placeholder = array_fill(0, $columnsCount, '?');
            $placeholder = "(" . implode(',', $placeholder) . ")";
            $placeholder = implode(',', array_fill(0, count($data), $placeholder));

            $values = array();
            foreach ($data as $row) {
                foreach ($row as $key => $value) {
                    $values[] = $value;
                }
            }


            $table = $platform->quoteIdentifier($this->getTable());
            $q = "INSERT $ignore INTO $table $columns VALUES $placeholder";
            $result = $this->adapter->query($q)->execute($values);
            $count = $result->count();
        }
        return $count;
    }
}
