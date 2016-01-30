<?php

namespace DDD\Service\Team\Usages;

use DDD\Dao\Team\Team as TeamDAO;
use DDD\Domain\Team\ForSelect;
use DDD\Service\ServiceBase;
use DDD\Service\Team\Team;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

/**
 * Class Base
 * @package DDD\Service\Team\Usages
 *
 * @author Tigran Petrosyan
 */
class Base extends ServiceBase
{
    const TEAM_USAGE_DEPARTMENT     = 1;
    const TEAM_USAGE_NOTIFIABLE     = 2;
    const TEAM_USAGE_FRONTIER       = 3;
    const TEAM_USAGE_SECURITY       = 4;
    const TEAM_USAGE_TASKABLE       = 5;
    const TEAM_USAGE_PROCUREMENT    = 6;
    const TEAM_USAGE_HIRING         = 7;
    const TEAM_USAGE_STORAGE        = 8;

    /**
     * @param $usage
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\Team\ForSelect[]
     * @throws \Exception
     */
    public function getTeamsByUsage($usage, $deactivatedIncluded = false)
    {
        /**
         * @var TeamDAO $teamDao
         */
        $teamDao = $this->getServiceLocator()->get('dao_team_team');

        switch ($usage) {
            case self::TEAM_USAGE_DEPARTMENT:
                $usageField = 'usage_department';
                break;
            case self::TEAM_USAGE_NOTIFIABLE:
                $usageField = 'usage_notifiable';
                break;
            case self::TEAM_USAGE_FRONTIER:
                $usageField = 'usage_frontier';
                break;
            case self::TEAM_USAGE_SECURITY:
                $usageField = 'usage_security';
                break;
            case self::TEAM_USAGE_TASKABLE:
                $usageField = 'usage_security';
                break;
            case self::TEAM_USAGE_PROCUREMENT:
                $usageField = 'usage_procurement';
                break;
            case self::TEAM_USAGE_HIRING:
                $usageField = 'usage_hiring';
                break;
            case self::TEAM_USAGE_STORAGE:
                $usageField = 'usage_storage';
                break;
            default:
                throw new \Exception('Wrong team usage passed');
        }

        $where = [
            $usageField => 1
        ];

        if (!$deactivatedIncluded) {
            $where['is_disable'] = 0;
        }

        $teamDao->getResultSetPrototype()->setArrayObjectPrototype(new ForSelect());

        $teams = $teamDao->fetchAll(
            $where,
            [
                'id',
                'name'
            ]
        );

        return $teams;
    }

    /**
     * @param $usages array
     * @param bool $deactivatedIncluded
     * @return \Zend\Db\ResultSet\ResultSet
     * @throws \Exception
     */
    public function getTeamsBySeveralUsages($usages, $deactivatedIncluded = false)
    {
        /**
         * @var TeamDAO $teamDao
         */
        $teamDao = $this->getServiceLocator()->get('dao_team_team');

        $where = new Where();

        $usagesCount = count($usages);
        $arrayIndex = 0;

        foreach ($usages as $usage) {
            switch ($usage) {
                case self::TEAM_USAGE_DEPARTMENT:
                    $usageField = 'usage_department';
                    break;
                case self::TEAM_USAGE_NOTIFIABLE:
                    $usageField = 'usage_notifiable';
                    break;
                case self::TEAM_USAGE_FRONTIER:
                    $usageField = 'usage_frontier';
                    break;
                case self::TEAM_USAGE_SECURITY:
                    $usageField = 'usage_security';
                    break;
                case self::TEAM_USAGE_TASKABLE:
                    $usageField = 'usage_security';
                    break;
                case self::TEAM_USAGE_PROCUREMENT:
                    $usageField = 'usage_procurement';
                    break;
                case self::TEAM_USAGE_HIRING:
                    $usageField = 'usage_hiring';
                    break;
                case self::TEAM_USAGE_STORAGE:
                    $usageField = 'usage_storage';
                    break;
                default:
                    throw new \Exception('Wrong team usage passed');
            }

            $where->equalTo($usageField, 1);

            if (++$arrayIndex != $usagesCount) {
                $where->or;
            }
        }

        if (!$deactivatedIncluded) {
            $where->equalTo('is_disable', 0);
        }

        $teamDao->getResultSetPrototype()->setArrayObjectPrototype(new ForSelect());

        $teams = $teamDao->fetchAll(
            $where,
            [
                'id',
                'name'
            ],
            ['name' => 'ASC']
        );

        return $teams;
    }

    /**
     * @param int $userId
     * @param int $usage
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\Team\ForSelect[]
     * @throws \Exception
     */
    public function getUserTeamsByUsage($userId, $usage, $deactivatedIncluded = false)
    {
        /**
         * @var TeamDAO $teamDao
         */
        $teamDao = $this->getServiceLocator()->get('dao_team_team');

        $prototype = $teamDao->getResultSetPrototype()->getArrayObjectPrototype();
        $teamDao->getResultSetPrototype()->setArrayObjectPrototype(new ForSelect());

        /**
         * @var ForSelect[] $apartmentGroups
         */
        $teams = $teamDao->fetchAll(function (Select $select) use($userId, $usage, $deactivatedIncluded) {
            $columns = [
                'id',
                'name'
            ];

            switch ($usage) {
                case self::TEAM_USAGE_DEPARTMENT:
                    $usageField = 'usage_department';
                    break;
                case self::TEAM_USAGE_NOTIFIABLE:
                    $usageField = 'usage_notifiable';
                    break;
                case self::TEAM_USAGE_FRONTIER:
                    $usageField = 'usage_frontier';
                    break;
                case self::TEAM_USAGE_SECURITY:
                    $usageField = 'usage_security';
                    break;
                case self::TEAM_USAGE_TASKABLE:
                    $usageField = 'usage_security';
                    break;
                case self::TEAM_USAGE_PROCUREMENT:
                    $usageField = 'usage_procurement';
                    break;
                case self::TEAM_USAGE_HIRING:
                    $usageField = 'usage_hiring';
                    break;
                case self::TEAM_USAGE_STORAGE:
                    $usageField = 'usage_storage';
                    break;
                default:
                    throw new \Exception('Wrong team usage passed');
            }

            $where = new Where();

            $where->equalTo($usageField, 1);

            if (!$deactivatedIncluded) {
                $where->equalTo('is_disable', 0);
            }

            if ($userId) {
                $where->in('staff.type', [Team::STAFF_MANAGER, Team::STAFF_OFFICER, Team::STAFF_MEMBER]);

                $select->join(
                    ['staff' => DbTables::TBL_TEAM_STAFF],
                    new Expression(DbTables::TBL_TEAMS . '.id = staff.team_id and user_id = ' . $userId),
                    [],
                    Select::JOIN_INNER
                );
            }

            $select
                ->columns($columns)
                ->where($where)
                ->group(DbTables::TBL_TEAMS . '.id');
        });

        $teamDao->getResultSetPrototype()->setArrayObjectPrototype($prototype);

        return $teams;
    }
}
