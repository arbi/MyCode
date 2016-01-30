<?php

namespace DDD\Dao\Partners;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Library\Constants\DbTables;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayObject;

class PartnerCityCommission extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_PARTNER_CITY_COMMISSION;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Partners\PartnerCityCommission')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $partnerId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getPartnerCityCommission($partnerId)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function(Select $select) use ($partnerId) {
            $select->columns([
                'id',
                'commission',
            ]);
            $select->join(
                ['city' => DbTables::TBL_CITIES],
                $this->getTable() . '.city_id = city.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                'city.detail_id = details.id',
                [
                    'name'
                ],
                Select::JOIN_LEFT
            );
            $select->where->equalTo($this->getTable() . '.partner_id', $partnerId);
            $select->order('details.name');
        });
    }

    /**
     * @param $partnerId
     * @param $apartmentId
     * @return bool | int
     */
    public function getPartnerCityCommissionByPartnerIdApartmentId($partnerId, $apartmentId)
    {
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function(Select $select) use ($partnerId, $apartmentId) {
            $select->columns([
                'commission',
            ]);

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                $this->getTable() . '.city_id = city.id',
                [],
                Select::JOIN_INNER
            );
            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                'apartment.city_id = city.id',
                [],
                Select::JOIN_INNER
            );
            $select->where->equalTo($this->getTable() . '.partner_id', $partnerId)
                          ->equalTo('apartment.id', $apartmentId);
        });

        return $result ? $result['commission'] : false;
    }



}
