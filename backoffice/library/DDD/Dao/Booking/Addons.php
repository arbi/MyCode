<?php

namespace DDD\Dao\Booking;

use Library\DbManager\TableGatewayManager;
use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Library\Constants\DbTables;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

class Addons extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_BOOKING_ADDONS;

    /**
     * @param ServiceLocatorAwareInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Booking\Addons')
    {
    	parent::__construct($sm, $domain);
    }

    /**
     * @return \DDD\Domain\Booking\Addons[]|\ArrayObject
     */
    public function getAllAddons()
    {
        return $this->fetchAll(function (Select $select) {
        	$select->columns([
                'id'			=> 'id',
                'name'			=> 'name',
                'location_join'	=> 'location_join',
                'value'			=> 'value',
                'currency_id'	=> 'currency_id',
                'std'			=> 'std',
                'cxl_apply' 	=> 'cxl_apply',
                'default_commission' 	=> 'default_commission',
            ]);
        	$select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['currency_code' => 'code', 'currency_rate' => 'value'],
                Select::JOIN_LEFT
            );
        });
    }


    public function getTaxesList()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'name'			=> 'name',
                'location_join'	=> 'location_join',
            ]);
            $select->where->isNotNull('location_join')->notEqualTo('location_join', '');
        });
    }
}
