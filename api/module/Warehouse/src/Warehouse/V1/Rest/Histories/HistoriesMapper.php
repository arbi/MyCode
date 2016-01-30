<?php
namespace Warehouse\V1\Rest\Histories;

use ZF\ApiProblem\ApiProblem;
use ZF\Rest\AbstractResourceListener;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Paginator\Adapter\DbSelect;

use Application\Entity\Error;

use Library\ActionLogger\Logger;
use Library\Constants\DbTables;

class HistoriesMapper
{
    protected $adapter;
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function fetchAll($assetId)
    {
        $where = new Where();
        $where
            ->equalTo(DbTables::TBL_ACTION_LOGS . '.module_id', Logger::MODULE_ASSET_VALUABLE)
            ->equalTo(DbTables::TBL_ACTION_LOGS . '.identity_id', $assetId);

        $select = new Select(DbTables::TBL_ACTION_LOGS);

        $select
            ->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                DbTables::TBL_ACTION_LOGS . '.user_id = users.id',
                ['username' => new Expression('CONCAT(firstname, " ", lastname)')],
                Select::JOIN_LEFT
            )
            ->where($where)
            ->order([DbTables::TBL_ACTION_LOGS . '.timestamp DESC', DbTables::TBL_ACTION_LOGS . '.id DESC']);

        $paginatorAdapter = new DbSelect($select, $this->adapter);
        $collection       = new HistoriesCollection($paginatorAdapter);
        return $collection;
    }
}
