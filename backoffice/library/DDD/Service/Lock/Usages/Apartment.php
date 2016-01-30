<?php

namespace DDD\Service\Lock\Usages;

/**
 * Class Apartment
 * @package DDD\Service\Lock\Usages
 *
 * @author Hrayr Papikyan
 */
class Apartment extends Base
{
    /**
     * @param int $usage
     * @return \DDD\Domain\Lock\ForSelect[]
     */
    public function getLockByUsage($apartmentId, $usage = Base::LOCK_USAGE_APARTMENT)
    {
        return parent::getLockByUsage($apartmentId, $usage);
    }
}
