<?php

namespace DDD\Dao\Textline;

use DDD\Service\Translation;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;

/**
 * Class Group
 * @package DDD\Dao\Textline
 */
class Group extends TableGatewayManager
{
    /**
     * @var string
     */
    protected $table = DbTables::TBL_PRODUCT_TEXTLINES;

    /**
     * @param \Zend\ServiceManager\ServiceLocatorInterface $sm
     */
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Textline\Group');
    }

    /**
     * Get group using information
     *
     * @param $groupId
     * @return array|\ArrayObject|null
     */
    public function getGroupUsageById($groupId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($groupId) {
            $select->columns([
                'id'       => 'id',
                'group_id' => 'entity_id',
                'en'       => 'en',
            ]);

            $select->where
                ->equalTo('entity_id', $groupId)
                ->equalTo('entity_type', Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_USAGE);
        });
    }

    /**
     * Get group facility information
     *
     * @param  $groupId
     * @return array|\ArrayObject|null
     */
    public function getGroupFacilityById($groupId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($groupId) {
            $select->columns([
                'id'       => 'id',
                'group_id' => 'entity_id',
                'en'       => 'en',
            ]);

            $select->where
                ->equalTo('entity_id', $groupId)
                ->equalTo('entity_type', Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_FACILITIES);
        });
    }

    /**
     * Get group facility information
     *
     * @param  $groupId
     * @return array|\ArrayObject|null
     */
    public function getGroupPolicyById($groupId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($groupId) {
            $select->columns([
                'id'       => 'id',
                'group_id' => 'entity_id',
                'en'       => 'en',
            ]);

            $select->where
                ->equalTo('entity_id', $groupId)
                ->equalTo('entity_type', Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_POLICIES);
        });
    }
}