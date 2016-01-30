<?php

namespace DDD\Dao\Apartel;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;
use Library\OTACrawler\OTACrawler;
use Zend\Db\Sql\Expression;

class OTADistribution extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTEL_OTA_DISTRIBUTION;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    public function getApartelOTAList($apartelId)
    {
    	return $this->fetchAll(function (Select $select) use($apartelId) {
    		$select->columns([
                'id',
                'reference',
                'url',
                'status',
                'partner_id',
                'apartel_id',
                'date_listed',
                'date_edited',
            ]);

    		$select->join(['partner' => DbTables::TBL_BOOKING_PARTNERS], $this->table . '.partner_id = partner.gid', ['partner_name'], 'LEFT');
    		$select->where([$this->table . '.apartel_id' => $apartelId]);
    	});
    }

    public function getApartelOTAListFromArray($apartelIdList, $acceptableOTAList)
    {
        return $this->fetchAll(function (Select $select) use($apartelIdList, $acceptableOTAList) {
            $select->columns([
                'id',
                'reference',
                'url',
                'status',
                'partner_id',
                'apartel_id',
                'date_listed',
                'date_edited',
            ]);

            $select->join(['partner' => DbTables::TBL_BOOKING_PARTNERS], $this->table . '.partner_id = partner.gid', ['partner_name'], 'LEFT');

            if (count($apartelIdList)) {
                $select->where->in($this->table . '.apartel_id', $apartelIdList);
            }

            if (count($acceptableOTAList)) {
                $select->where->in($this->getTable() . '.partner_id', $acceptableOTAList);
            }
        });
    }

    public function getIssueConnections()
    {
        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'identity_id' => 'apartel_id',
                'product' => new Expression('"apartel"'),
                'reference',
                'url',
                'status',
                'date_listed',
                'date_edited',
                'ota_status',
                'city_name' => new Expression('"Not City"'),
            ]);

            $select->join(
                ['partnerY' => DbTables::TBL_BOOKING_PARTNERS],
                DbTables::TBL_APARTEL_OTA_DISTRIBUTION . '.partner_id = partnerY.gid',
                ['partner_name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['apartel' => DbTables::TBL_APARTMENT_GROUPS],
                DbTables::TBL_APARTEL_OTA_DISTRIBUTION . '.apartel_id = apartel.id',
                ['name'],
                Select::JOIN_LEFT
            );

            $select->where([DbTables::TBL_APARTEL_OTA_DISTRIBUTION . '.status' => OTACrawler::STATUS_ISSUE]);
        });
    }

    public function getIssueConnectionsCount()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) {
            $select->columns(['count' => new Expression('COUNT(*)')]);
            $select->join(
                ['partnerY' => DbTables::TBL_BOOKING_PARTNERS],
                DbTables::TBL_APARTEL_OTA_DISTRIBUTION . '.partner_id = partnerY.gid',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['apartel' => DbTables::TBL_APARTMENT_GROUPS],
                DbTables::TBL_APARTEL_OTA_DISTRIBUTION . '.apartel_id = apartel.id',
                [],
                Select::JOIN_LEFT
            );

            $select->where([DbTables::TBL_APARTEL_OTA_DISTRIBUTION . '.status' => OTACrawler::STATUS_ISSUE]);
        });
        return $result['count'];
    }
}
