<?php

namespace CreditCard\Model;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class CCTestNumbers extends TableGatewayManager
{
    protected $table = DbTables::TBL_CC_TEST_NUMBERS;

    public function __construct($sm, $domain = 'ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    public function getAllTestCCNumbers()
    {
        $results = $this->fetchAll();

        $cCTestNumbers = [];
        foreach ($results as $row) {
            $cCTestNumbers[] = $row['number'];
        }

        return $cCTestNumbers;
    }
}
