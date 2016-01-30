<?php

namespace DDD\Dao\User\Evaluation;

use DDD\Domain\User\Evaluation\EvaluationItem;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Zend\Db\Sql\Select;

/**
 * Class EvaluationItems
 * @package DDD\Dao\User\Evaluation
 */
class EvaluationItems extends TableGatewayManager
{
    protected $table = DbTables::TBL_USER_EVALUATION_ITEMS;

    public function __construct($sm, $domain = 'DDD\Domain\User\Evaluation\EvaluationItem')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @return EvaluationItem[]
     */
    public function getEvaluationItems()
    {
        $result = $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'title',
                'description'
            ]);

            $select->order('id ASC');
        });

        return $result;
    }
}
