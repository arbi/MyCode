<?php

namespace DDD\Service\Lock\Usages;

use DDD\Dao\Lock\Locks as LockDAO;
use DDD\Service\ServiceBase;
use Zend\Db\Sql\Where;

/**
 * Class Base
 * @package DDD\Service\Lock\Usages
 *
 * @author Hrayr Papikyan
 */
class Base extends ServiceBase
{
    const LOCK_USAGE_APARTMENT = 1;
    const LOCK_USAGE_BUILDING  = 2;
    const LOCK_USAGE_PARKING   = 3;

    /**
     * @param $usage
     * @return \DDD\Domain\Lock\ForSelect[]
     * @throws \Exception
     */
    public function getLockByUsage($usageId, $usage)
    {
        /**
         * @var LockDAO $lockDao
         * @var \DDD\Dao\ApartmentGroup\BuildingSections $buildingSectionsDao
         */
        $lockDao      = $this->getServiceLocator()->get('dao_lock_locks');
        $apartmentDao = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $buildingSectionsDao = $this->getServiceLocator()->get('dao_apartment_group_building_sections');
        $parkingDao   = $this->getServiceLocator()->get('dao_parking_general');

        $daoArray = [$apartmentDao, $buildingSectionsDao, $parkingDao];

        switch ($usage) {
            case self::LOCK_USAGE_APARTMENT:
                $usageField = 'usage_apartment';
                $selectedUsage = '\DDD\Dao\Accommodation\Accommodations';
                break;
            case self::LOCK_USAGE_BUILDING:
                $usageField = 'usage_building';
                $selectedUsage = '\DDD\Dao\ApartmentGroup\BuildingSections';
                break;
            case self::LOCK_USAGE_PARKING:
                $usageField = 'usage_parking';
                $selectedUsage = '\DDD\Dao\Parking\General';
                break;
            default:
                throw new \Exception('Wrong apartment group usage passed');
        }

        $locks = $lockDao->getForSelectByUsage($usageField);
        $locks = iterator_to_array($locks);
        foreach ($locks as $key => $value) {
            if ($value->isPhysical()) {
                foreach ($daoArray as $dao) {
                    $physicalUsedCount = $lockDao->getPhysicalUsage($dao, $usageId, $value->getId(), $selectedUsage);
                    if ((int)$physicalUsedCount['count']) {
                        unset($locks[$key]);
                        break;
                    }
                }
            }
        }

        return $this->makeArray($locks);
    }

    protected function makeArray($forSelectDomain)
    {
        $array = [0 => '-- Please Select --'];
        foreach($forSelectDomain as $row) {

            $array[$row->getId()] = $row->getName() . ' (' . $row->getTypeName() . ')';
        }

        return $array;
    }
}
