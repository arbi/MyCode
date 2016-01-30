<?php

namespace DDD\Dao\Partners;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Library\Constants\DbTables;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayObject;

class PartnerAccount extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_BOOKING_PARTNER_ACCOUNTS;

    /**
     * @param ServiceLocatorInterface $sm
     */
    public function __construct($sm) {
        parent::__construct($sm, 'ArrayObject');
    }

    /**
     * Get list of all account IDs for given partner
     *
     * @param int $partnerID
     * @return ArrayObject
     */
    public function getPartnerAccounts($partnerID) {

        $result = $this->fetchAll(function (Select $select) use ($partnerID) {
            $where = new Where();
            $where->equalTo($this->table . '.partner_id', $partnerID);
            $select->columns(['cubilis_id'])->where($where);
		});

        $result->setArrayObjectPrototype(new ArrayObject());

		return $result;
    }
}
