<?php

namespace DDD\Service\Team\Usages;

/**
 * Class Frontier
 * @package DDD\Service\Team\Usages
 *
 * @author Tigran Petrosyan
 */
class Frontier extends Base
{
    /**
     * @param int $usage
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\Team\ForSelect[]
     */
    public function getTeamsByUsage($usage = Base::TEAM_USAGE_FRONTIER, $deactivatedIncluded = false)
    {
        return parent::getTeamsByUsage($usage, $deactivatedIncluded);
    }

    /**
     * @param int $userId
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\Team\ForSelect[]
     */
    public function getUserFrontierTeams($userId, $deactivatedIncluded = false)
    {
        return parent::getUserTeamsByUsage($userId, Base::TEAM_USAGE_FRONTIER, $deactivatedIncluded);
    }
}
