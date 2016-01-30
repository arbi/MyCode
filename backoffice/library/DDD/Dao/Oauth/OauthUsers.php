<?php
namespace DDD\Dao\Oauth;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Zend\Stdlib\ArrayObject;

use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Library\Constants\Objects;

class OauthUsers extends TableGatewayManager
{
    protected $table = DbTables::TBL_OAUTH_USERS;
    public function __construct($sm, $domain = 'ArrayObject')
    {
        parent::__construct($sm, $domain);
    }
}
