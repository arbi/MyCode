<?php

namespace DDD\Dao\User\Evaluation;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Zend\Db\Sql\Select;

/**
 * Class EvaluationTypes
 * @package DDD\Dao\User\Evaluation
 */
class EvaluationTypes extends TableGatewayManager
{
    protected $table = DbTables::TBL_USER_EVALUATION_TYPES;

    public function __construct($sm, $domain = 'DDD\Domain\User\Evaluation\EvaluationType')
    {
        parent::__construct($sm, $domain);
    }
}
