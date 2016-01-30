<?php

namespace DDD\Dao\Apartment;

use DDD\Domain\Apartment\Room\Cubilis;
use Library\Constants\Objects;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class Room
 * @package DDD\Dao\Apartment
 */
class Room extends TableGatewayManager
{
    protected $table = DbTables::TBL_PRODUCT_TYPES;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Apartment\Room\Cubilis') {
        parent::__construct($sm, $domain);
    }

	/**
	 * @param int $cubilisRoomId
	 * @return \DDD\Domain\Apartment\Room\Cubilis
	 */
	public function getRoomByCubilisRoomId($cubilisRoomId)
    {
    	return $this->fetchOne(function(Select $select) use($cubilisRoomId) {
            $select->columns(['id', 'apartment_id', 'name', 'active', 'cubilis_id']);

            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id = apartment.id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['apartment_detail' => DbTables::TBL_APARTMENTS_DETAILS],
                $this->getTable() . '.apartment_id = apartment_detail.apartment_id',
                [],
                Select::JOIN_LEFT
            );

            $select->where->equalTo($this->getTable() . '.cubilis_id', $cubilisRoomId);

            // apartment is selling
            $select->where->in('apartment.status', [
                Objects::PRODUCT_STATUS_LIVEANDSELLIG,
                Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE
            ]);

            // apartment is connected to cubilis
            $select->where->equalTo('apartment_detail.sync_cubilis', 1);
        });
    }

	/**
	 * @param int $roomId
	 * @param int $cubilisRoomId
	 * @return int
	 */
	public function updateCubilisLink($roomId, $cubilisRoomId) {
		return $this->update(['cubilis_id' => $cubilisRoomId], ['id' => $roomId]);
	}

	/**
	 * @param $apartmentId
	 * @return Cubilis|null
	 */
	public function getById($apartmentId)
    {
		return $this->fetchOne(function(Select $select) use($apartmentId) {
			$select->columns(['id', 'apartment_id']);
			$select->where(['apartment_id' => $apartmentId]);
		});
	}

	/**
	 * @param $apartmentId
	 * @return Cubilis|null
	 */
	public function getMaxCapacity($apartmentId)
    {
		return $this->fetchOne(function(Select $select) use($apartmentId) {
			$select->columns(['max_capacity']);
			$select->where(['apartment_id' => $apartmentId]);
		});
	}
}
