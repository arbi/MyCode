<?php

namespace DDD\Service\Team\Usages;

/**
 * Class Hiring
 * @package DDD\Service\Team\Usages
 */
class Hiring extends Base
{
    /**
     * @param int $usage
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\Team\ForSelect[]
     */
    public function getTeamsByUsage($usage = Base::TEAM_USAGE_HIRING, $deactivatedIncluded = false)
    {
        return parent::getTeamsByUsage(Base::TEAM_USAGE_HIRING, $deactivatedIncluded);
    }
}