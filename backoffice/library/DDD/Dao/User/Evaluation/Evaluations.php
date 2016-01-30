<?php

namespace DDD\Dao\User\Evaluation;

use DDD\Domain\UniversalDashboard\Widget\UpcomingEvaluations;
use DDD\Domain\User\Evaluation\EvaluationExtended;
use DDD\Service\User\Evaluations as EvaluationsService;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class Evaluations extends TableGatewayManager
{
    protected $table = DbTables::TBL_USER_EVALUATIONS;

    public function __construct($sm, $domain = 'DDD\Domain\User\Evaluation\EvaluationExtended')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $userId
     * @return EvaluationExtended[]
     */
    public function getUserEvaluations($userId)
    {
        $this->getResultSetPrototype()->setArrayObjectPrototype(new EvaluationExtended());

        $result = $this->fetchAll(function (Select $select) use($userId) {
            $select->columns([
                'id',
                'user_id',
                'creator_id',
                'status',
                'type_id',
                'average',
                'date_created',
                'description'
            ]);

            $select->join(
                ['employee' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = employee.id',
                [
                    'employee_first_name' => 'firstname',
                    'employee_last_name' => 'lastname'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['creator' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = creator.id',
                [
                    'creator_first_name' => 'firstname',
                    'creator_last_name' => 'lastname',
                    'creator_position' => 'position'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['type' => DbTables::TBL_USER_EVALUATION_TYPES],
                $this->getTable() . '.type_id = type.id',
                ['type_title' => 'title'],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable() . '.user_id' => $userId
            ]);

            $select->order($this->getTable() . '.date_created DESC');
        });

        return $result;
    }

    /**
     * @param int $evaluationId
     * @return \DDD\Domain\User\Evaluation\Evaluation
     */
    public function getEvaluationById($evaluationId)
    {
        $this->getResultSetPrototype()->setArrayObjectPrototype(new \DDD\Domain\User\Evaluation\Evaluation());

        $result = $this->fetchOne(function (Select $select) use($evaluationId) {
            $select->columns([
                'id',
                'user_id',
                'creator_id',
                'status',
                'type_id',
                'date_created',
                'description'
            ]);

            $select->where([
                'id' => $evaluationId
            ]);
        });

        return $result;
    }

    /**
     * @param int $evaluationId
     * @return \DDD\Domain\User\Evaluation\EvaluationExtended
     */
    public function getEvaluationsFullById($evaluationId)
    {
        $this->getResultSetPrototype()->setArrayObjectPrototype(new EvaluationExtended());

        $result = $this->fetchOne(function (Select $select) use($evaluationId) {
            $select->columns([
                'id',
                'user_id',
                'creator_id',
                'status',
                'type_id',
                'average',
                'date_created',
                'description'
            ]);

            $select->join(
                ['employee' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = employee.id',
                [
                    'employee_first_name' => 'firstname',
                    'employee_last_name' => 'lastname'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['creator' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = creator.id',
                [
                    'creator_first_name' => 'firstname',
                    'creator_last_name' => 'lastname',
                    'creator_position' => 'position'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['type' => DbTables::TBL_USER_EVALUATION_TYPES],
                $this->getTable() . '.type_id = type.id',
                ['type_title' => 'title'],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable() . '.id' => $evaluationId
            ]);
        });

        return $result;
    }

    /**
     * @param $managerId
     * @return UpcomingEvaluations[]
     */
    public function getUpcomingEvaluations($managerId)
    {
        $this->getResultSetPrototype()->setArrayObjectPrototype(new UpcomingEvaluations());
        $result = $this->fetchAll(function (Select $select) use($managerId) {
            $select->columns([
                'id',
                'user_id',
                'creator_id',
                'date_created',
                'description'
            ]);

            $select->join(
                ['employee' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = employee.id',
                [
                    'employee_id'         => 'id',
                    'employee_first_name' => 'firstname',
                    'employee_last_name'  => 'lastname',
                    'disabled'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['creator' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = creator.id',
                [
                    'creator_first_name' => 'firstname',
                    'creator_last_name'  => 'lastname',
                    'creator_position'   => 'position'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['type' => DbTables::TBL_USER_EVALUATION_TYPES],
                $this->getTable() . '.type_id = type.id',
                ['type_title' => 'title'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo('employee.manager_id', $managerId)
                ->equalTo($this->getTable() . '.status', EvaluationsService::USER_EVALUATION_STATUS_PLANNED)
                ->equalTo($this->getTable() . '.type_id', EvaluationsService::USER_EVALUATION_TYPE_EVALUATION)
                ->expression($this->getTable() . '.date_created <= adddate(NOW(), interval 2 week)', [])
            ;

            $select->order($this->getTable() . '.date_created ASC');
        });

        return $result;
    }

    /**
     * @param $managerId
     * @return int
     */
    public function getUpcomingEvaluationsCount($managerId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use($managerId) {
            $select->columns([
                'count' => new Expression('COUNT(*)')
            ]);
            $select->join(
                ['employee' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = employee.id',
                [],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo('employee.manager_id', $managerId)
                ->equalTo($this->getTable() . '.status', EvaluationsService::USER_EVALUATION_STATUS_PLANNED)
                ->equalTo($this->getTable() . '.type_id', EvaluationsService::USER_EVALUATION_TYPE_EVALUATION)
                ->expression($this->getTable() . '.date_created <= adddate(NOW(), interval 2 week)', [])
            ;
        });

        return $result['count'];
    }

    /**
     * @return \DDD\Domain\User\Evaluation\EvaluationExtended []
     */
    public function getNotResolvedEvaluations()
    {
        $result = $this->fetchAll(function (Select $select) {
            $select->columns([
                'id', 'user_id', 'description'
            ]);

            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = users.id',
                [
                    'employee_first_name' => 'firstname',
                    'employee_last_name' => 'lastname',
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['creator' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = creator.id',
                [
                    'creator_first_name' => 'firstname',
                    'creator_last_name' => 'lastname',
                ],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable() . '.status'      => EvaluationsService::USER_EVALUATION_STATUS_DONE,
                $this->getTable() . '.is_resolved' => 0,
                $this->getTable() . '.type_id' => EvaluationsService::USER_EVALUATION_TYPE_EVALUATION
            ]);
        });

        return $result;
    }
}
