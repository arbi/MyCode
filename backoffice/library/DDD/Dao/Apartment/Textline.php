<?php

namespace DDD\Dao\Apartment;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class Textline
 * @package DDD\Dao\Apartment
 */
class Textline extends TableGatewayManager
{
    protected $table = DbTables::TBL_PRODUCT_TEXTLINES;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'ArrayObject') {
        parent::__construct($sm, $domain);
    }
}
