<?php

namespace DDD\Service\Team\Usages;

/**
 * Class Security
 * @package DDD\Service\Team\Usages
 *
 * @author Tigran Petrosyan
 */
class Security extends Base
{
    /**
     * @param int $usage
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\Team\ForSelect[]
     */
    public function getTeamsByUsage($usage = Base::TEAM_USAGE_SECURITY, $deactivatedIncluded = false)
    {
        return parent::getTeamsByUsage($usage, $deactivatedIncluded);
    }

    /**
     * @param $userId
     * @return \DDD\Domain\Team\Team[]
     */
    public function getUserSecuredTeams($userId)
    {
        /* @var $teamDao \DDD\Dao\Team\Team */
        $teamDao      = $this->getServiceLocator()->get('dao_team_team');
        $securedTeams = $teamDao->getUserTeams($userId, $isSecurity = true);
        return $securedTeams;
    }
}
