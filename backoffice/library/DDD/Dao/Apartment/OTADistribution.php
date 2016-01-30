<?php

namespace DDD\Dao\Apartment;

use DDD\Domain\Apartment\Details\Sync;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\OTACrawler\OTACrawler;
use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;

class OTADistribution extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTMENT_OTA_DISTRIBUTION;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    public function getApartmentOTAList($apartmentId)
    {
    	return $this->fetchAll(function (Select $select) use($apartmentId) {
    		$select->columns([
                'id',
                'reference',
                'url',
                'status',
                'partner_id',
                'apartment_id',
                'date_listed',
                'date_edited',
            ]);

    		$select->join(['partner' => DbTables::TBL_BOOKING_PARTNERS], $this->table . '.partner_id = partner.gid', ['partner_name'], 'LEFT');
    		$select->where([$this->table . '.apartment_id' => $apartmentId]);
    	});
    }

    public function getApartmentOTAListFromArray($apartmentIdList, $acceptableStatusList, $acceptableOTAList)
    {
        return $this->fetchAll(function (Select $select) use($apartmentIdList, $acceptableStatusList, $acceptableOTAList) {
            $select->columns([
                'id',
                'reference',
                'url',
                'status',
                'partner_id',
                'apartment_id',
                'date_listed',
                'date_edited',
            ]);

            $select->join(['partner' => DbTables::TBL_BOOKING_PARTNERS], $this->getTable() . '.partner_id = partner.gid', ['partner_name'], 'LEFT');
            $select->join(['general' => DbTables::TBL_APARTMENTS], $this->getTable() . '.apartment_id = general.id', [], 'LEFT');

            if (count($apartmentIdList)) {
                $select->where->in($this->getTable() . '.apartment_id', $apartmentIdList);
            }

            if (count($acceptableOTAList)) {
                $select->where->in($this->getTable() . '.partner_id', $acceptableOTAList);
            }

            $select->where->in('general.status', $acceptableStatusList);
        });
    }

    public function getIssueConnections()
    {

        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'identity_id' => 'apartment_id',
                'product' => new Expression('"apartment"'),
                'reference',
                'url',
                'status',
                'date_listed',
                'date_edited',
                'ota_status',
            ]);

            $select->join(
                ['partnerX' => DbTables::TBL_BOOKING_PARTNERS],
                $this->getTable() . '.partner_id = partnerX.gid',
                ['partner_name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id = apartment.id',
                ['name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                'apartment.city_id = city.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['location_details' => DbTables::TBL_LOCATION_DETAILS],
                'city.detail_id = location_details.id',
                ['city_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->where([$this->getTable() . '.status' => OTACrawler::STATUS_ISSUE]);

            // $apartelSelect = new Select(DbTables::TBL_APARTEL_OTA_DISTRIBUTION);
            // $apartelSelect->columns([
            //     'id',
            //     'identity_id' => 'apartel_id',
            //     'product' => new Expression('"apartel"'),
            //     'reference',
            //     'url',
            //     'status',
            //     'date_listed',
            //     'date_edited',
            //     'ota_status',
            //     'city_name' => new Expression('"Not City"'),
            // ]);
            //
            // $apartelSelect->join(
            //     ['partnerY' => DbTables::TBL_BOOKING_PARTNERS],
            //     DbTables::TBL_APARTEL_OTA_DISTRIBUTION . '.partner_id = partnerY.gid',
            //     ['partner_name'],
            //     Select::JOIN_LEFT
            // );
            //
            // $apartelSelect->join(
            //     ['apartel' => DbTables::TBL_APARTMENT_GROUPS],
            //     DbTables::TBL_APARTEL_OTA_DISTRIBUTION . '.apartel_id = apartel.id',
            //     ['name'],
            //     Select::JOIN_LEFT
            // );
            //
            // $apartelSelect->where([DbTables::TBL_APARTEL_OTA_DISTRIBUTION . '.status' => OTACrawler::STATUS_ISSUE]);
            //
            // $select->combine($apartelSelect);
            $select->order('product');

//            Debug::dumpSQLFromSelect($select);
        });
    }

    public function getIssueConnectionsCount()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) {
            $select->columns(['count' => new Expression('COUNT(*)')]);

            $select->join(
                ['partnerX' => DbTables::TBL_BOOKING_PARTNERS],
                $this->getTable() . '.partner_id = partnerX.gid',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id = apartment.id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                'apartment.city_id = city.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['location_details' => DbTables::TBL_LOCATION_DETAILS],
                'city.detail_id = location_details.id',
                [],
                Select::JOIN_LEFT
            );

            $select->where([$this->getTable() . '.status' => OTACrawler::STATUS_ISSUE]);
        });

        return $result['count'];
    }
}
