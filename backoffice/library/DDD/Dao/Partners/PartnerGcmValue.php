<?php

namespace DDD\Dao\Partners;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Library\Constants\DbTables;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayObject;

class PartnerGcmValue extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_BOOKING_PARTNER_GCM_VALUES;

    /**
     * @param ServiceLocatorInterface $sm
     */
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Partners\PartnerGcmValue');
    }

    /**
     * Get list of all account IDs for given partner
     *
     * @param $partnerId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getByPartnerId($partnerId)
    {
        $result = $this->fetchAll(function (Select $select) use ($partnerId) {
            $select->where->equalTo($this->table . '.partner_id', $partnerId);
		});

		return $result;
    }
}
