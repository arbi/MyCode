<?php
namespace DDD\Dao\WebsiteLanguage;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class Language extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_WEBSITE_LANGUAGES;
    
    /**
     * @param ServiceLocator $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\WebsiteLanguage\Language'){
        parent::__construct($sm, $domain);
    }
    
    public function getEnabledLanguage(){
        $result = $this->fetchAll(function (Select $select) {
            $select->where
                   ->equalTo('enabled', 1);

			$select->columns(array('id', 'name', 'iso_code'))
                   ->order('ordering');
		});

		return $result;
    }
}