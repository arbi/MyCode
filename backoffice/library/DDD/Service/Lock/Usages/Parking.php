<?php

namespace DDD\Service\Lock\Usages;

/**
 * Class Parking
 * @package DDD\Service\Lock\Usages
 *
 * @author Hrayr Papikyan
 */
class Parking extends Base
{
    /**
     * @param int $usage
     * @return \DDD\Domain\Lock\ForSelect[]
     */
    public function getLockByUsage($parkingId, $usage = Base::LOCK_USAGE_PARKING)
    {
        return parent::getLockByUsage($parkingId, $usage);
    }
}
