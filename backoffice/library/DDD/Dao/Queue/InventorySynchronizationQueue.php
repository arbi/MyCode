<?php
namespace DDD\Dao\Queue;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Utility\Debug;
use Zend\Config\Processor\Queue;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use DDD\Service\Queue\InventorySynchronizationQueue as SyncService;
use Zend\Json\Expr;


/**
 * Class InventorySynchronizationQueue
 * @package DDD\Dao\Queue
 */
class InventorySynchronizationQueue extends TableGatewayManager
{
    protected $table = DbTables::TBL_INVENTORY_SYNCHRONIZATION_QUEUE;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Queue\InventorySynchronizationQueue');
    }

    public function remove($id)
    {
        $this->delete(['id' => $id]);
    }

    /**
     * @param array $ids
     * @return int
     */
    public function bulkDelete($ids)
    {
        $where = new Where();
        $where->in('id', $ids);

        return $this->delete($where);
    }

    /**
     * @param $ids
     * @param $limitAttempt
     * @return int
     */
    public function incrementAttempts($ids, $limitAttempt)
    {
        if ($limitAttempt) {
            $sql = "IF(attempts >= {$limitAttempt} , $limitAttempt, attempts + 1)";
        } else {
            $sql = "attempts + 1";
        }

        $where = new Where();
        $where->in('id', $ids);
        return $this->update(
            ['attempts' => new Expression($sql)],
            $where
        );
    }

    /**
     *
     * @return int
     */
    public function removeDateExpiredRows()
    {
        $where = new Where();

        $where->lessThan($this->getTable() . '.date', date('Y-m-d'));

        return $this->delete($where);
    }

    /**
     * @return int
     */
    public function removeDisconnectedEntity()
    {
        // for apartment
        $sql = " DELETE queue FROM {$this->getTable()} queue " .
               " INNER JOIN " . DbTables::TBL_APARTMENTS_DETAILS . " apartment ON queue.entity_id = apartment.apartment_id " .
               " WHERE  queue.entity_type = " . SyncService::ENTITY_TYPE_APARTMENT . " AND apartment.sync_cubilis = 0; ";

        // for apartel
        $sql .= " DELETE queue FROM {$this->getTable()} queue " .
                " INNER JOIN " . DbTables::TBL_APARTEL_TYPE . " room_type ON queue.entity_id = room_type.id " .
                " INNER JOIN " . DbTables::TBL_APARTELS . " apartel ON room_type.apartel_id = apartel.id" .
                " WHERE queue.entity_type = " . SyncService::ENTITY_TYPE_APARTEL . " AND apartel.sync_cubilis = 0; ";

        $result = $this->getAdapter()->getDriver()->getConnection()->execute($sql);

        return $result->count();
    }

    /**
     * @return int
     */
    public function getLowAttemptEntity()
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) {
            $select->columns([
                'entity_id',
                'entity_type',
                'attempts',
                'date',
                'minute_for_send' => new Expression('TIMESTAMPDIFF(MINUTE, update_date, NOW())')
            ]);
            $select->order(['attempts ASC', 'date ASC'])
                   ->limit(1);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

    /**
     * @param $entityId
     * @param $entityType
     * @param $attempts
     * @param $startDate
     * @param $endDate
     * @return ResultSet
     */
    public function getQueueItemsByEntityId($entityId, $entityType, $attempts, $startDate, $endDate)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Queue\InventorySynchronizationQueue());
        if ($entityType == SyncService::ENTITY_TYPE_APARTEL) {

            $result = $this->fetchAll(function (Select $select) use ($entityId, $attempts, $startDate, $endDate) {
                $select->columns([
                    'id',
                    'date',
                    'attempts',
                ]);

                $select->join(
                    ['inventory' => DbTables::TBL_APARTEL_INVENTORY],
                    new Expression($this->getTable() . '.entity_id = inventory.apartel_type_id AND ' . $this->getTable() . '.date = inventory.date'),
                    [
                        'availability',
                        'price',
                        'rate_id',
                        'room_id' => 'apartel_type_id'
                    ],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['apartel' => DbTables::TBL_APARTELS],
                    'inventory.apartel_id = apartel.id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['rate' => DbTables::TBL_APARTEL_RATES],
                    'inventory.rate_id = rate.id',
                    [
                        'cubilis_rate_id' => 'cubilis_id'
                    ],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['room' => DbTables::TBL_APARTEL_TYPE],
                    'inventory.apartel_type_id = room.id',
                    [
                        'cubilis_room_id' => 'cubilis_id'
                    ],
                    Select::JOIN_LEFT
                );

                $select->where
                    ->equalTo($this->getTable() . '.entity_id', $entityId)
                    ->equalTo($this->getTable() . '.attempts', $attempts)
                    ->equalTo('rate.active', 1)
                    ->isNotNull('rate.cubilis_id')
                    ->isNotNull('room.cubilis_id')
                    ->notEqualTo('apartel.sync_cubilis', 0)
                    ->greaterThanOrEqualTo($this->getTable() . '.date', $startDate)
                    ->lessThanOrEqualTo($this->getTable() . '.date', $endDate)
                ;
            });
        } else {
            $result = $this->fetchAll(function (Select $select) use ($entityId, $attempts, $startDate, $endDate) {
                $select->columns([
                    'id',
                    'date',
                    'attempts',
                ]);

                $select->join(
                    ['apartment_det' => DbTables::TBL_APARTMENTS_DETAILS],
                    $this->getTable() . '.entity_id = apartment_det.apartment_id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['inventory' => DbTables::TBL_APARTMENT_INVENTORY],
                    new Expression($this->getTable() . '.entity_id = inventory.apartment_id AND ' . $this->getTable() . '.date = inventory.date'),
                    [
                        'availability',
                        'price',
                        'rate_id',
                        'room_id'
                    ],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['rate' => DbTables::TBL_APARTMENT_RATES],
                    'inventory.rate_id = rate.id',
                    [
                        'cubilis_rate_id' => 'cubilis_id'
                    ],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['room' => DbTables::TBL_PRODUCT_TYPES],
                    'inventory.room_id = room.id',
                    [
                        'cubilis_room_id' => 'cubilis_id'
                    ],
                    Select::JOIN_LEFT
                );

                $select->where
                    ->equalTo($this->getTable() . '.entity_id', $entityId)
                    ->equalTo($this->getTable() . '.attempts', $attempts)
                    ->equalTo('rate.active', 1)
                    ->isNotNull('rate.cubilis_id')
                    ->isNotNull('room.cubilis_id')
                    ->notEqualTo('apartment_det.sync_cubilis', 0)
                    ->greaterThanOrEqualTo($this->getTable() . '.date', $startDate)
                    ->lessThanOrEqualTo($this->getTable() . '.date', $endDate);
            });
        }
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }


}