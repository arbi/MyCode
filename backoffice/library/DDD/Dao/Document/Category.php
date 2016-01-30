<?php
namespace DDD\Dao\Document;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

/**
 * Class Category
 * @package DDD\Dao\Apartment
 */
class Category extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_DOCUMENT_TYPES;
   
    public function __construct($sm, $domain = 'DDD\Domain\Document\Type')
    {
        parent::__construct($sm, $domain);
    }
    
    public function getList()
    {
        $result = $this->fetchAll(function (Select $select) {
            $select->order('name ASC');
        });
        return $result; 
    }
}