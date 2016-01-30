<?php

namespace DDD\Service\Team\Usages;

/**
 * Class Notifiable
 * @package DDD\Service\Team\Usages
 *
 * @author Tigran Petrosyan
 */
class Notifiable extends Base
{
    /**
     * @param int $usage
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\Team\ForSelect[]
     */
    public function getTeamsByUsage($usage = Base::TEAM_USAGE_NOTIFIABLE, $deactivatedIncluded = false)
    {
        return parent::getTeamsByUsage($usage, $deactivatedIncluded);
    }
}
