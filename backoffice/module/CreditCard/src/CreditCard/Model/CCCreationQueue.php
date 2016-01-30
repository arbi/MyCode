<?php

namespace CreditCard\Model;

use CreditCard\Entity\CompleteData;
use Library\Finance\Customer;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class CCCreationQueue extends TableGatewayManager
{
    protected $table = DbTables::TBL_CC_CREATION_QUEUE;

    public function __construct($sm, $domain = 'CreditCard\Entity\CompleteData')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $limit
     * @return CompleteData[]
     */
    public function getItems($limit)
    {
        return $this->fetchAll(function(Select $select) use($limit) {
            $select->columns([
                'id' => 'id',
                'date_provided' => 'date_inserted',
                'attempts' => 'attempts',
                'pan' => 'pan',
                'holder' => 'holder',
                'security_code' => 'security_code',
                'exp_year' => 'exp_year',
                'exp_month' => 'exp_month',
                'brand' => 'brand',
                'partner_id' => 'partner_id',
                'customer_id' => 'customer_id',
                'source' => 'source',
                'status' => 'status',
            ]);
            $select->where->equalTo('attempts', 0);
            $select->limit($limit);
        });
    }
}
