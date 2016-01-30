<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Service\ServiceBase;

final class ResolveComments extends ServiceBase
{
    public function getUnresolvedCommentsForUser($uid)
    {
        /* @var $logsTeamDao \DDD\Dao\ActionLogs\LogsTeam */
        $logsTeamDao = $this->getServiceLocator()->get('dao_action_logs_logs_team');

        return $logsTeamDao->getUnresolvedCommentsForUser($uid);
    }
    public function getUnresolvedCommentsCountForUser($uid)
    {
        /* @var $logsTeamDao \DDD\Dao\ActionLogs\LogsTeam */
        $logsTeamDao = $this->getServiceLocator()->get('dao_action_logs_logs_team');

        return $logsTeamDao->getUnresolvedCommentsCountForUser($uid);
    }
}
