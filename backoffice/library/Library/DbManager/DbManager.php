<?php

namespace Library\DbManager;

use Library\DbManager\DbErrorHandler;
use Zend\Db\Exception;

class DbManager
{
    protected $adapter;

    protected $sm;

    public function __construct($sm)
    {
        $this->sm       = $sm;
        $this->adapter  = $sm->get('dbadapter');
    }

    public function execute($query, array $params = [])
    {
        try {
            $statement = $this->adapter->createStatement($query, $params);
            return $statement->execute();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function getFirstRow($query, array $params = [])
    {
        if (empty($query)) {
            throw new \InvalidArgumentException('Query was not set');
        }

        return $this->execute($query, $params)->current();
    }

    public function getAllRow($query, array $params = []) {
        if (empty($query)) {
            throw new \InvalidArgumentException('Query was not set');
        }

        return $this->execute($query, $params)->getResource()->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function executeRows($query, array $params) {
        if (empty($query)) {
            throw new \InvalidArgumentException('Query was not set');
        }

        if (empty($params)) {
            throw new \InvalidArgumentException('Params were not set');
        }

        return $this->execute($query, $params)->current();
    }

    public function beginTransaction() {
        $connection = $this->adapter->getDriver()->getConnection();
        $connection->beginTransaction();
    }

    public function commitTransaction() {
        $connection = $this->adapter->getDriver()->getConnection();
        $connection->commit();
    }

    public function rollbackTransaction() {
        $connection = $this->adapter->getDriver()->getConnection();
        $connection->rollback();
    }
}
