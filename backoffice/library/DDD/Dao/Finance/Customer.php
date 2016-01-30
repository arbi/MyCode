<?php

namespace DDD\Dao\Finance;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

/**
 * Class Customer
 * @package DDD\Dao\Finance
 */
class Customer extends TableGatewayManager
{
    /**
     * @var string
     */
    protected $table = DbTables::TBL_CUSTOMERS;

    /**
     * @param \Zend\ServiceManager\ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Finance\Customer')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $customerId
     * @return \DDD\Domain\Finance\Customer|null
     */
    public function getCustomer($customerId)
    {
        return $this->fetchOne(function (Select $select) use ($customerId) {
            $select->where([
                'id' => $customerId
            ]);
        });
    }
}
