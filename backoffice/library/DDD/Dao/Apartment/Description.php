<?php
namespace DDD\Dao\Apartment;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\ServiceManager\ServiceLocatorInterface;

class Description extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_PRODUCT_DESCRIPTIONS;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'ArrayObject'){
        parent::__construct($sm, $domain);
    }
}
