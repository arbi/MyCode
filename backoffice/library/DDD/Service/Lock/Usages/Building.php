<?php

namespace DDD\Service\Lock\Usages;

/**
 * Class Building
 * @package DDD\Service\Lock\Usages
 *
 * @author Hrayr Papikyan
 */
class Building extends Base
{
    /**
     * @param $buildingId
     * @param int $usage
     * @return \DDD\Domain\Lock\ForSelect[]
     * @throws \Exception
     */
    public function getLockByUsage($buildingId, $usage = Base::LOCK_USAGE_BUILDING)
    {
        return parent::getLockByUsage($buildingId, $usage);
    }
}
