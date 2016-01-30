<?php
namespace DDD\Dao\Api;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Zend\Stdlib\ArrayObject;

use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Library\Constants\Objects;

class ApiRequests extends TableGatewayManager
{
    protected $table = DbTables::TBL_API_REQUESTS;
    public function __construct($sm, $domain = 'ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    public function deleteExpiredRequest()
    {
        $sql = "DELETE FROM " . $this->table . " WHERE request_date <= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";

        $statement = $this->adapter->createStatement($sql);
        $statement->execute();
    }
}
