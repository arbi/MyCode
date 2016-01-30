<?php

namespace DDD\Service\Team\Usages;

/**
 * Class Department
 * @package DDD\Service\Team\Usages
 *
 * @author Tigran Petrosyan
 */
class Department extends Base
{
    /**
     * @param int $usage
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\Team\ForSelect[]
     */
    public function getTeamsByUsage($usage = Base::TEAM_USAGE_DEPARTMENT, $deactivatedIncluded = false)
    {
        return parent::getTeamsByUsage($usage, $deactivatedIncluded);
    }
}
