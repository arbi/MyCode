<?php

namespace DDD\Service\Team\Usages;

/**
 * Class Procurement
 * @package DDD\Service\Team\Usages
 */
class Procurement extends Base
{
    /**
     * @param int $usage
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\Team\ForSelect[]
     */
    public function getTeamsByUsage($usage = Base::TEAM_USAGE_PROCUREMENT, $deactivatedIncluded = false)
    {
        return parent::getTeamsByUsage(Base::TEAM_USAGE_PROCUREMENT, $deactivatedIncluded);
    }

    /**
     * @param int $userId
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\Team\ForSelect[]
     */
    public function getUserProcurementTeams($userId, $deactivatedIncluded = false)
    {
        return parent::getUserTeamsByUsage($userId, Base::TEAM_USAGE_PROCUREMENT, $deactivatedIncluded);
    }
}