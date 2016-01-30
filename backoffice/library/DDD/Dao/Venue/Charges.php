<?php

namespace DDD\Dao\Venue;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;
use DDD\Service\Venue\Charges as VenueCharges;

class Charges extends TableGatewayManager
{
    protected $table   = DbTables::TBL_VENUE_CHARGES;

    public function __construct($sm, $domain = 'DDD\Domain\Venue\Charges')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $venueId
     * @param bool|true $onlyNotArchived
     * @return \DDD\Domain\Venue\Charges[]
     */
    public function getChargesByVenueId($venueId, $onlyNotArchived = true)
    {
        $result = $this->fetchAll(function (Select $select) use ($venueId, $onlyNotArchived) {
            $select->columns([
                'id',
                'venue_id',
                'creator_id',
                'date_created_server',
                'date_created_client',
                'status',
                'order_status',
                'description',
                'charged_user_id',
                'amount',
                'is_archived'
            ]);

            $select->join(
                ['venue' => DbTables::TBL_VENUES],
                $this->getTable() . '.venue_id = venue.id',
                [
                    'venue_name' => 'name',
                    'currency_id'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                'venue.currency_id = currency.id',
                ['currency_code' => 'code'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.venue_id', $venueId);

            if ($onlyNotArchived) {
                $select->where
                    ->equalTo($this->getTable() . '.is_archived', 0);
            }
        });

        return $result;
    }

    /**
     * @param int $chargeId
     * @return \DDD\Domain\Venue\Charges
     */
    public function getChargeById($chargeId)
    {
        $result = $this->fetchOne(function (Select $select) use ($chargeId) {
            $select->columns([
                'id',
                'venue_id',
                'creator_id',
                'date_created_server',
                'date_created_client',
                'status',
                'order_status',
                'description',
                'charged_user_id',
                'amount',
                'is_archived'
            ]);

            $select->join(
                ['venue' => DbTables::TBL_VENUES],
                $this->getTable() . '.venue_id = venue.id',
                ['venue_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.id', $chargeId);
        });

        return $result;
    }

    /**
     * @param int $userId
     * @param array $statuses
     * @param array $orderStatuses
     * @param int $currencyId
     * @param string $startDate
     *
     * @return \DDD\Domain\Venue\GinocoinCharges[]
     */
    public function getChargesByChargedUserId(
        $userId,
        $statuses = [],
        $orderStatuses = [],
        $currencyId = false,
        $startDate = false
    ) {
        $result = $this->fetchAll(function (Select $select) use ($userId, $statuses, $orderStatuses, $currencyId, $startDate) {
            $select->columns([
                'id',
                'amount'
            ]);

            $select->join(
                ['venue' => DbTables::TBL_VENUES],
                $this->getTable() . '.venue_id = venue.id',
                ['perday_max_price'],
                Select::JOIN_LEFT
            );

            if (count($statuses)) {
                $select->where
                    ->in($this->getTable() . '.status', $statuses);
            }

            if (count($orderStatuses)) {
                $select->where
                    ->in($this->getTable() . '.order_status', $orderStatuses);
            }

            if ($currencyId) {
                $select->where
                    ->equalTo('venue.currency_id', $currencyId);
            }

            if ($startDate) {
                $select->where
                    ->greaterThanOrEqualTo($this->getTable() . '.date_created_client', $startDate);
            }

            $select->where
                ->equalTo($this->getTable() . '.charged_user_id', $userId);
        });

        return $result;
    }

    /**
     * Get New Charges and Items by VenueId for rest
     *
     * @param $venueId
     * @return array
     */
    public function getNewChargeItemsByVenueId($venueId)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($venueId) {
            $select->columns([
                'id',
                'venue_id',
                'creator_id',
                'date_created_server',
                'date_created_client',
                'order_status',
                'description',
                'user_id' => 'charged_user_id',
                'amount',
                'is_archived'
            ]);

            $select->join(
                ['venue' => DbTables::TBL_VENUES],
                $this->getTable() . '.venue_id = venue.id',
                [
                    'venue_name' => 'name',
                    'currency_id'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                'venue.currency_id = currency.id',
                ['currency_code' => 'code'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['charge_items' => DbTables::TBL_LUNCHROOM_ORDER_ARCHIVE],
                $this->getTable() . '.id = charge_items.venue_charge_id',
                [
                    'item_id'       => 'id',
                    'item_price'    => 'item_price',
                    'item_name'     => 'item_name',
                    'item_quantity' => 'item_quantity',
                ],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo('venue.id', $venueId)
                ->equalTo($this->getTable() . '.status', \DDD\Service\Venue\Charges::CHARGE_STATUS_NEW)
                ->equalTo($this->getTable() . '.order_status', \DDD\Service\Venue\Charges::ORDER_STATUS_NEW)
                ->equalTo($this->getTable() . '.is_archived', 0);
        });

        $this->setEntity($prototype);

        $charges = [];
        foreach ($result as $item) {
            $charges[$item['id']]['id']                  = $item['id'];
            $charges[$item['id']]['date_created_server'] = $item['date_created_server'];
            $charges[$item['id']]['date_created_client'] = $item['date_created_client'];
            $charges[$item['id']]['user_id']             = $item['user_id'];
            if (empty($item['item_id'])) {
                $charges[$item['id']]['items'] = [];
            } else {
                $charges[$item['id']]['items'][]             = [
                    'item_id'       => $item['item_id'],
                    'item_name'     => $item['item_name'],
                    'item_quantity' => $item['item_quantity'],
                    'item_price'    => $item['item_price'],
                    'currency_code' => $item['currency_code'],
                    'currency_id'   => $item['currency_id'],
                ];
            }
        }

        return $charges;
    }
}