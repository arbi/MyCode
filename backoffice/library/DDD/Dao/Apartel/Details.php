<?php

namespace DDD\Dao\Apartel;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;

class Details extends TableGatewayManager
{
    protected $table = DbTables::TBL_APARTELS_DETAILS;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = '\DDD\Domain\Apartel\Details\Details')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $apartelId
     * @return false|\DDD\Domain\Apartel\Details\Details
     */
    public function getApartelDetailsById($apartelId)
    {
        $result = $this->fetchOne(function (Select $select) use ($apartelId) {
            $select->columns([
                'id',
                'apartel_id',
                'content_textline_id',
                'moto_textline_id',
                'meta_description_textline_id',
                'bg_image',
                'default_availability'
            ]);

            $select->join(
                ['apartels' => DbTables::TBL_APARTELS],
                $this->getTable() . '.apartel_id = apartels.id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['apartment_group' => DbTables::TBL_APARTMENT_GROUPS],
                'apartels.apartment_group_id = apartment_group.id',
                ['name'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.apartel_id', $apartelId);
        });

        return $result;
    }

    /**
     * @param int $apartelTypeId
     * @return false|\DDD\Domain\Apartel\Details\Details
     */
    public function getDefaultAvailabilityByTypeId($apartelTypeId)
    {
        $result = $this->fetchOne(function (Select $select) use ($apartelTypeId) {
            $select->columns(['default_availability']);

            $select->join(
                ['type' => DbTables::TBL_APARTEL_TYPE],
                $this->getTable() . '.apartel_id = type.apartel_id',
                [],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo('type.id', $apartelTypeId);
        });

        return $result;
    }
}
