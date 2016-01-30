<?php

namespace DDD\Service\ApartmentGroup\Usages;

use DDD\Dao\ApartmentGroup\ApartmentGroup as ApartmentGroupDAO;
use DDD\Domain\ApartmentGroup\ForSelect;
use DDD\Service\ServiceBase;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;

/**
 * Class Base
 * @package DDD\Service\ApartmentGroup\Usages
 *
 * @author Tigran Petrosyan
 */
class Base extends ServiceBase
{
    const APARTMENT_GROUP_USAGE_APARTEL = 1;
    const APARTMENT_GROUP_USAGE_BUILDING = 2;
    const APARTMENT_GROUP_USAGE_CONCIERGE = 4;
    const APARTMENT_GROUP_USAGE_COST_CENTER = 16;
    const APARTMENT_GROUP_USAGE_PERFORMANCE = 32;

    /**
     * @param $usage
     * @param bool $deactivatedIncluded
     * @return \DDD\Domain\ApartmentGroup\ForSelect[]
     * @throws \Exception
     */
    public function getApartmentGroupsByUsage($usage, $deactivatedIncluded = false, $hasNotDevAccess)
    {
        /**
         * @var ApartmentGroupDAO $apartmentGroupDao
         */
        $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

        switch ($usage) {
            case self::APARTMENT_GROUP_USAGE_APARTEL:
                $usageField = 'usage_apartel';
                break;
            case self::APARTMENT_GROUP_USAGE_BUILDING:
                $usageField = 'usage_building';
                break;
            case self::APARTMENT_GROUP_USAGE_CONCIERGE:
                $usageField = 'usage_concierge_dashboard';
                break;
            case self::APARTMENT_GROUP_USAGE_COST_CENTER:
                $usageField = 'usage_cost_center';
                break;
            case self::APARTMENT_GROUP_USAGE_PERFORMANCE:
                $usageField = 'usage_performance_group';
                break;
            default:
                throw new \Exception('Wrong apartment group usage passed');
        }

        $where = new Where();

        $where->equalTo($usageField, 1);

        if (!$deactivatedIncluded) {
            $where->equalTo('active', 1);
        }

        if ($hasNotDevAccess) {
            $where->notEqualTo('id', 1);
        }

        $apartmentGroupDao->getResultSetPrototype()->setArrayObjectPrototype(new ForSelect());

        /**
         * @var ForSelect[] $apartmentGroups
         */
        $apartmentGroups = $apartmentGroupDao->fetchAll(
            $where,
            [
                'id',
                'name',
                'usage_apartel'
            ]
        );

        return $apartmentGroups;
    }
}
