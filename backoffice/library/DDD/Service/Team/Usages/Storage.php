<?php

namespace DDD\Service\Team\Usages;

/**
 * Class Storage
 * @package DDD\Service\Team\Usages
 */
class Storage extends Base
{
    /**
     * @param int $usage
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\Team\ForSelect[]
     */
    public function getTeamsByUsage($usage = Base::TEAM_USAGE_STORAGE, $deactivatedIncluded = false)
    {
        return parent::getTeamsByUsage(Base::TEAM_USAGE_STORAGE, $deactivatedIncluded);
    }
}