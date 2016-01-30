<?php

namespace DDD\Dao\User\Evaluation;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Zend\Db\Sql\Select;

class EvaluationValues extends TableGatewayManager
{
    protected $table = DbTables::TBL_USER_EVALUATION_VALUES;

    public function __construct(
            $sm,
            $domain = 'DDD\Domain\User\Evaluation\EvaluationValues')
    {
        parent::__construct($sm, $domain);
    }

    /**
     *
     * @param int $evaluationId
     * @return \DDD\Domain\User\Evaluation\EvaluationValues
     */
    public function getValuesByEvaluationId($evaluationId)
    {
        $result = $this->fetchAll(function (Select $select) use($evaluationId) {
            $select->columns([
                'id', 'evaluation_id', 'item_id', 'value'
            ]);

            $select->where([
                'evaluation_id' => $evaluationId
            ]);
        });

        return $result;
    }

    /**
     * @param int $evaluationId
     * @return \DDD\Domain\User\Evaluation\EvaluationValues[]|\ArrayObject
     */
    public function getValuesFullByEvaluationId($evaluationId)
    {
        $result = $this->fetchAll(function (Select $select) use($evaluationId) {
            $select->columns([
                'id', 'value'
            ]);

            $select->join(
                ['items' => DbTables::TBL_USER_EVALUATION_ITEMS],
                $this->getTable() . '.item_id = items.id',
                ['item' => 'title'],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable() . '.evaluation_id' => $evaluationId
            ]);
        });

        return $result;
    }
}
