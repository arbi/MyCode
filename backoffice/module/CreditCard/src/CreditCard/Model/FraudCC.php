<?php

namespace CreditCard\Model;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

/**
 * Class FraudCC
 * @package CreditCard\Model
 *
 * @author Tigran Petrosyan
 */
class FraudCC extends TableGatewayManager
{
    /**
     * @var string
     */
    protected $table = DbTables::TBL_FRAUD_CC;

    /**
     * @param \Zend\ServiceManager\ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'ArrayObject')
    {
        parent::__construct($sm, $domain);
    }
}
