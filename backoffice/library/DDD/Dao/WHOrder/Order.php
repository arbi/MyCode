<?php

namespace DDD\Dao\WHOrder;

use DDD\Service\Team\Team;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Finance\Base\Account;
use \Library\Constants\Constants;
use Zend\Captcha\Dumb;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use \DDD\Service\WHOrder\Order as OrderService;
use Zend\Form\Element\DateTime;

class Order extends TableGatewayManager
{
    protected $table = DbTables::TBL_WM_ORDERS;

    public function __construct($sm, $domain = 'DDD\Domain\WHOrder\Order') {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $id
     * @return \DDD\Domain\WHOrder\Order|null
     */
    public function getOrderById($id)
    {
        $this->setEntity(new \DDD\Domain\WHOrder\Order());
        $result = $this->fetchOne(function (Select $select) use ($id) {
            $select->columns([
                'id',
                'creator_id',
                'date_created',
                'price',
                'currency_id',
                'title',
                'asset_category_id',
                'target_id',
                'target_type',
                'status',
                'quantity',
                'quantity_type',
                'supplier_id',
                'supplier_tracking_number',
                'supplier_transaction_id',
                'url',
                'estimated_date_start',
                'estimated_date_end',
                'description',
                'received_date',
                'received_quantity',
                'order_date',
                'tracking_url',
                'team_id',
                'status_shipping',
                'po_item_id',
                'po_ra_item_id',
            ]);

            $select->join(
                ['asset_category' => DbTables::TBL_ASSET_CATEGORIES],
                $this->getTable() . '.asset_category_id = asset_category.id',
                ['asset_category_name' => 'name'],
                Select::JOIN_LEFT
            )->join(
                ['po_item' => DbTables::TBL_EXPENSE_ITEM],
                $this->getTable() . '.po_item_id = po_item.id',
                ['po_id' => 'expense_id'],
                Select::JOIN_LEFT
            )->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = users.id',
                ['user' => new Expression('CONCAT(users.firstname, " ", users.lastname)')],
                Select::JOIN_LEFT
            )->join(
                ['teams' => DbTables::TBL_TEAMS],
                $this->getTable() . '.team_id = teams.id',
                ['team_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->where->equalTo($this->getTable() . '.id', $id);
        });

        return $result;
    }

    /**
     * @param $offset
     * @param $limit
     * @param $sortCol
     * @param $sortDir
     * @param string $like
     * @param array $filters
     * @return array
     */
    public function getAllOrders($offset, $limit, $sortCol, $sortDir, $like = '', $filters = [])
    {
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($offset, $limit, $sortCol, $sortDir, $like, $filters) {
            $sortColumns = [
                'status',
                'status_shipping',
                'date_created',
                'category_name',
                'target_id',
                'estimated_date_start',
                'estimated_date_end',
                'order_date'
            ];

            $select->columns([
                'id',
                'status',
                'date_created',
                'title',
                'target_id',
                'target_type',
                'status_shipping',
                'supplier_id',
                'supplier_tracking_number',
                'url',
                'estimated_date_start',
                'estimated_date_end',
                'title',
                'description',
                'order_date'
            ]);

            $select->join(
                ['asset_category' => DbTables::TBL_ASSET_CATEGORIES],
                $this->getTable() . '.asset_category_id = asset_category.id',
                [
                    'category_name' => 'name',
                    'category_type' => 'type_id'
                ],
                Select::JOIN_LEFT
            );

            if (isset($filters['users_or_teams'])) {
                $select->where->expression('(' .$filters['users_or_teams']. ')', []);
            }

            if ($filters['status'] != '') {
                $select->where->equalTo($this->getTable() . '.status', $filters['status']);
            }

            if (!empty($filters['created_by'])) {
                $select->where->equalTo($this->getTable() . '.creator_id', $filters['created_by']);
            }

            if (!empty($filters['status_shipping'])) {
                $select->where
                    ->in($this->getTable() . '.status_shipping', $filters['status_shipping']);
            }

            if (!empty($filters['category_id'])) {
                $select->where
                    ->equalTo($this->getTable() . '.asset_category_id', $filters['category_id']);
            }

            if (!empty($filters['location'])) {
                $locationParts = explode('_', $filters['location']);
                $select->where
                    ->equalTo($this->getTable() . '.target_id', $locationParts[1])
                    ->end
                    ->equalTo($this->getTable() . '.target_type', $locationParts[0]);
            }

            if (!empty($filters['supplier_id'])) {
                $select->where
                    ->equalTo($this->getTable() . '.supplier_id', $filters['supplier_id']);
            }

            if (!empty($filters['estimated_date_start'])) {
                $dateStartParts = explode(' - ', $filters['estimated_date_start']);

                $dateStartParts[0] = date(\Library\Constants\Constants::DATABASE_DATE_TIME_FORMAT, strtotime($dateStartParts[0]));
                $dateStartParts[1] = date(\Library\Constants\Constants::DATABASE_DATE_TIME_FORMAT, strtotime($dateStartParts[1]));

                $select->where
                    ->nest()
                    ->greaterThanOrEqualTo($this->getTable() . '.estimated_date_start', $dateStartParts[0])
                    ->and
                    ->lessThanOrEqualTo($this->getTable() . '.estimated_date_start', $dateStartParts[1])
                    ->unnest();
            }

            if (!empty($filters['estimated_date_end'])) {
                $dateEndParts = explode(' - ', $filters['estimated_date_end']);

                $dateEndParts[0] = date(\Library\Constants\Constants::DATABASE_DATE_TIME_FORMAT, strtotime($dateEndParts[0]));
                $dateEndParts[1] = date(\Library\Constants\Constants::DATABASE_DATE_TIME_FORMAT, strtotime($dateEndParts[1]));

                $select->where
                    ->nest()
                    ->greaterThanOrEqualTo($this->getTable() . '.estimated_date_end', $dateEndParts[0])
                    ->and
                    ->lessThanOrEqualTo($this->getTable() . '.estimated_date_end', $dateEndParts[1])
                    ->unnest();
            }

            if (!empty($filters['received_date'])) {
                $dateReceivedParts = explode(' - ', $filters['received_date']);

                $dateReceivedParts[0] = date(\Library\Constants\Constants::DATABASE_DATE_TIME_FORMAT, strtotime($dateReceivedParts[0]));
                $dateReceivedParts[1] = date(\Library\Constants\Constants::DATABASE_DATE_TIME_FORMAT, strtotime($dateReceivedParts[1]));

                $select->where
                    ->nest()
                    ->expression('DATE(' . $this->getTable() . '.received_date) >= "' . $dateReceivedParts[0] . '"', [])
                    ->and
                    ->expression('DATE(' . $this->getTable() . '.received_date) <= "' . $dateReceivedParts[1] . '"', [])
                    ->unnest();
            }

            if (!empty($filters['order_date'])) {
                $dateCreationParts = explode(' - ', $filters['order_date']);

                $dateCreationParts[0] = date(\Library\Constants\Constants::DATABASE_DATE_TIME_FORMAT, strtotime($dateCreationParts[0]));
                $dateCreationParts[1] = date(\Library\Constants\Constants::DATABASE_DATE_TIME_FORMAT, strtotime($dateCreationParts[1]));

                $select->where
                    ->nest()
                    ->expression('DATE(' . $this->getTable() . '.order_date) >= "' . $dateCreationParts[0] . '"', [])
                    ->and
                    ->expression('DATE(' . $this->getTable() . '.order_date) <= "' . $dateCreationParts[1] . '"', [])
                    ->unnest();
            }

            if (!empty($filters['received_quantity'])) {
                $select->where
                    ->equalTo($this->getTable() . '.received_quantity', $filters['received_quantity']);
            }

            if (!empty($filters['order_title'])) {
                $select->where
                    ->like($this->getTable() . '.title', '%' . $filters['order_title'] . '%');
            }

            if (!empty($filters['supplier_tracking_number'])) {
                $select->where
                    ->equalTo($this->getTable() . '.supplier_tracking_number', $filters['supplier_tracking_number']);
            }

            $select
                ->order($sortColumns[$sortCol] . ' ' . $sortDir)
                ->offset((int)$offset)
                ->limit((int)$limit);
            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
        });

        $statement      = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $resultCount    = $statement->execute();
        $currentRow     = $resultCount->current();
        $totalCount     = $currentRow['total'];

        return  [
            'orders_list'   => $result,
            'total_count'   => $totalCount
        ];
    }

    public function getRelatedOrders($categoryId, $locationEntityId, $locationEntityType, $finalStatuses = [])
    {
        $result = $this->fetchAll(function (Select $select) use ($categoryId, $locationEntityId, $locationEntityType, $finalStatuses) {
            $select->join(
                ['suppliers' => DbTables::TBL_SUPPLIERS],
                $this->getTable() . '.supplier_id = suppliers.id',
                [
                    'supplier_name' => 'name',
                ],
                Select::JOIN_LEFT
            );
            $select->where([
                'asset_category_id' => $categoryId,
                'target_id'         => $locationEntityId,
                'target_type'       => $locationEntityType,

            ]);

            if (isset($finalStatuses[0])) {
                $select->where->notIn('status', $finalStatuses);
            }
        });


        return $result;
    }

    public function getMatchingOrdersForAsset($categoryId, $locationEntityType, $locationEntityId, $quantity = 1)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use ($categoryId, $locationEntityId, $locationEntityType, $quantity) {
            $where = new Where();
            $where
                ->equalTo('asset_category_id', $categoryId)
                ->equalTo('target_id', $locationEntityId)
                ->equalTo('target_type', $locationEntityType)
                ->expression('quantity - received_quantity >= ?', $quantity)
                ->notIn('status_shipping', [OrderService::STATUS_RECEIVED, OrderService::STATUS_CANCELED, OrderService::STATUS_REFUNDED]);
            $select
                ->columns(['id', 'status_shipping', 'title', 'remaining_quantity' => new Expression('quantity - received_quantity')])
                ->where($where)
                ->order(new Expression('remaining_quantity ASC'));
        });

        $this->setEntity($prototype);

        return $result;
    }

    public function getMatchingOrdersForConsumableAsset($categoryId, $locationEntityType, $locationEntityId, $quantity, $shipmentStatus, $checkOrderExist = 0)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($categoryId, $locationEntityId, $locationEntityType, $quantity, $shipmentStatus, $checkOrderExist) {
            $where = new Where();
            $where->expression('quantity - received_quantity > 0', []);

            if (!$checkOrderExist) {
                if (!$shipmentStatus) {
                    $where->expression('quantity - received_quantity < ?', $quantity);
                } else {
                    $where->expression('quantity - received_quantity = ?', $quantity);
                }

                $select
                    ->columns(['id', 'status_shipping', 'title', 'quantity', 'quantity_type', 'remaining_quantity' => new Expression('quantity - received_quantity')])
                    ->order(new Expression('remaining_quantity ASC'));
            }

            $where
                ->equalTo('asset_category_id', $categoryId)
                ->equalTo('target_id', $locationEntityId)
                ->equalTo('target_type', $locationEntityType)
                ->notIn('status_shipping', [OrderService::STATUS_RECEIVED, OrderService::STATUS_CANCELED, OrderService::STATUS_REFUNDED]);

            $select->where($where);
        });

        $this->setEntity($prototype);

        return $result;
    }

    /**
     * @param $userId
     * @return mixed
     */
    public function getItemsToBeOrderedCount($userId)
    {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($userId) {
            $select->columns(['count' => new Expression('COUNT(*)')]);
            $select->join(
                ['team_staff' => DbTables::TBL_TEAM_STAFF],
                new Expression('team_staff.user_id = ' . $userId . ' AND ' . $this->getTable() .
                    '.team_id = team_staff.team_id AND (team_staff.type = ' . Team::STAFF_MANAGER . ' OR team_staff.type = ' . Team::STAFF_OFFICER . ')'),
                []
            );
            $select->group('team_staff.type');
            $select->where->equalTo($this->getTable().'.status_shipping', OrderService::STATUS_TO_BE_ORDERED)
                          ->notEqualTo($this->getTable().'.status', OrderService::STATUS_ORDER_REJECTED);
        });
        $this->setEntity($entity);
        return $result['count'];
    }

    /**
     * @param string $dateTimeAfter2days
     * @return int
     */
    public function getItemsToBeDeliveredCount($dateTimeAfter2days, $userId)
    {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($dateTimeAfter2days, $userId) {
            $select->columns(['count' => new Expression('COUNT(*)')]);

            $select->join(
                ['team_staff' => DbTables::TBL_TEAM_STAFF],
                new Expression('team_staff.user_id = ' . $userId . ' AND ' . $this->getTable() .
                    '.team_id = team_staff.team_id AND (team_staff.type = ' . Team::STAFF_MANAGER . ' OR team_staff.type = ' . Team::STAFF_OFFICER . ')'),
                []
            );

            $select->where->in($this->getTable().'.status_shipping',
                [
                OrderService::STATUS_SHIPPED,
                OrderService::STATUS_PARTIALLY_RECEIVED
                ]
            )
            ->isNotNull($this->getTable() . '.estimated_date_start')
            ->lessThanOrEqualTo($this->getTable() . '.estimated_date_start', $dateTimeAfter2days);
        });
        $this->setEntity($entity);
        return $result['count'];
    }

    /**
     * @param $userId
     * @return mixed
     */
    public function getOrdersToBeRefundedCount($userId)
    {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($userId) {
            $select->columns(['count' => new Expression('COUNT(*)')]);
            $select->join(
                ['team_staff' => DbTables::TBL_TEAM_STAFF],
                new Expression('team_staff.user_id = ' . $userId . ' AND ' . $this->getTable() .
                    '.team_id = team_staff.team_id AND (team_staff.type = ' . Team::STAFF_MANAGER . ' OR team_staff.type = ' . Team::STAFF_OFFICER . ')'),
                []
            );
            $select->where->in($this->getTable().'.status_shipping',
                [
                    OrderService::STATUS_RETURNED,
                    OrderService::STATUS_ISSUE
                ]
            );

        });
        $this->setEntity($entity);
        return $result['count'];
    }

    /**
     * @param $userId
     * @return mixed
     */
    public function getOrdersCreatedByMeCount($userId)
    {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($userId) {
            $select->columns(['count' => new Expression('COUNT(*)')]);
            $select->where->equalTo('creator_id', $userId)
                          ->notEqualTo('is_archive', OrderService::STATUS_IS_ARCHIVE);
        });
        $this->setEntity($entity);
        return $result['count'];
    }

    /**
     * @return mixed
     */
    public function getOrdersToBeShippedInLastTwoDaysCount()
    {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $twoDaysAgoDate = date(Constants::DATABASE_DATE_TIME_FORMAT, strtotime('-2 days', strtotime(date(Constants::DATABASE_DATE_TIME_FORMAT))));

        $result = $this->fetchOne(function (Select $select) use ($twoDaysAgoDate) {
            $select->columns(['count' => new Expression('COUNT(*)')]);
            $select->where->equalTo('status_shipping', OrderService::STATUS_ORDERED);
            $select->where->notEqualTo('is_archive', OrderService::STATUS_IS_ARCHIVE);
            $select->where->lessThan('order_date', $twoDaysAgoDate);
        });

        $this->setEntity($entity);

        return $result['count'];
    }

    /**
     * int $userId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getItemsToBeOrdered($userId)
    {
        $result = $this->fetchAll(function (Select $select) use ($userId) {
            $select->columns([
                'id',
                'title',
                'target_type',
                'quantity',
                'quantity_type',
                'order_date',
                'status',
                'location_name' => new Expression(" (CASE " . $this->getTable() . ".target_type " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_APARTMENT . " THEN apartments.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_OFFICE . " THEN offices.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_BUILDING . " THEN apartment_groups.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_STORAGE . " THEN storages.name " .
                    " END)")
            ]);

            $select->join(
                    ['asset_category' => DbTables::TBL_ASSET_CATEGORIES],
                    $this->getTable() . '.asset_category_id = asset_category.id',
                    ['asset_category_name' => 'name'],
                    Select::JOIN_LEFT
                )->join(
                     ['team_staff' => DbTables::TBL_TEAM_STAFF],
                     new Expression('team_staff.user_id = ' . $userId . ' AND ' . $this->getTable() .
                         '.team_id = team_staff.team_id AND (team_staff.type = ' . Team::STAFF_MANAGER . ' OR team_staff.type = ' . Team::STAFF_OFFICER . ')'),
                     []
                )
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    new Expression($this->getTable() . '.target_id = apartments.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_APARTMENT),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['offices' => DbTables::TBL_OFFICES],
                    new Expression($this->getTable() . '.target_id = offices.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_OFFICE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment_groups' => DbTables::TBL_APARTMENT_GROUPS],
                    new Expression($this->getTable() . '.target_id = apartment_groups.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_BUILDING),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['storages' => DbTables::TBL_WM_STORAGE],
                    new Expression($this->getTable() . '.target_id = storages.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_STORAGE),
                    [],
                    Select::JOIN_LEFT
                );
            $select->group($this->getTable() . '.id');

            $select->where->equalTo($this->getTable().'.status_shipping', OrderService::STATUS_TO_BE_ORDERED)
                ->notEqualTo($this->getTable().'.status', OrderService::STATUS_ORDER_REJECTED);
        });
        return $result;
    }

    /**
     * @param $dateTimeAfter2days
     * @param $userId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getItemsToBeDelivered($dateTimeAfter2days, $userId)
    {
        $result = $this->fetchAll(function (Select $select) use ($dateTimeAfter2days, $userId){
            $select->columns([
                'id',
                'status_shipping',
                'title',
                'target_type',
                'quantity',
                'quantity_type',
                'order_date',
                'estimated_date_start',
                'estimated_date_end',
                'tracking_url',
                'status',
                'location_name' => new Expression(" (CASE " . $this->getTable() . ".target_type " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_APARTMENT . " THEN apartments.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_OFFICE . " THEN offices.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_BUILDING . " THEN apartment_groups.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_STORAGE . " THEN storages.name " .
                    " END)")
            ]);

            $select->join(
                    ['asset_category' => DbTables::TBL_ASSET_CATEGORIES],
                    $this->getTable() . '.asset_category_id = asset_category.id',
                    ['asset_category_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['team_staff' => DbTables::TBL_TEAM_STAFF],
                    new Expression('team_staff.user_id = ' . $userId . ' AND ' . $this->getTable() .
                        '.team_id = team_staff.team_id AND (team_staff.type = ' . Team::STAFF_MANAGER . ' OR team_staff.type = ' . Team::STAFF_OFFICER . ')'),
                    []
                )
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    new Expression($this->getTable() . '.target_id = apartments.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_APARTMENT),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['offices' => DbTables::TBL_OFFICES],
                    new Expression($this->getTable() . '.target_id = offices.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_OFFICE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment_groups' => DbTables::TBL_APARTMENT_GROUPS],
                    new Expression($this->getTable() . '.target_id = apartment_groups.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_BUILDING),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['storages' => DbTables::TBL_WM_STORAGE],
                    new Expression($this->getTable() . '.target_id = storages.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_STORAGE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['suppliers' => DbTables::TBL_SUPPLIERS],
                    $this->getTable() . '.supplier_id = suppliers.id',
                    [
                        'supplier_name' => 'name',
                    ],
                    Select::JOIN_LEFT
                );

            $select->where->in($this->getTable().'.status_shipping',
                [
                    OrderService::STATUS_SHIPPED,
                    OrderService::STATUS_PARTIALLY_RECEIVED,
                    OrderService::STATUS_ORDERED,
                ]
            )
                ->isNotNull($this->getTable() . '.estimated_date_start')
                ->lessThanOrEqualTo($this->getTable() . '.estimated_date_start', $dateTimeAfter2days);
        });
        return $result;
    }

    /**
     * @param $userId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getOrdersToBeRefunded($userId)
    {
        $result = $this->fetchAll(function (Select $select) use ($userId) {
            $select->columns([
                'id',
                'status_shipping',
                'target_type',
                'quantity',
                'quantity_type',
                'order_date',
                'supplier_transaction_id',
                'status',
                'location_name' => new Expression(" (CASE " . $this->getTable() . ".target_type " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_APARTMENT . " THEN apartments.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_OFFICE . " THEN offices.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_BUILDING . " THEN apartment_groups.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_STORAGE . " THEN storages.name " .
                    " END)")
            ]);

            $select->join(
                    ['asset_category' => DbTables::TBL_ASSET_CATEGORIES],
                    $this->getTable() . '.asset_category_id = asset_category.id',
                    ['asset_category_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['team_staff' => DbTables::TBL_TEAM_STAFF],
                    new Expression('team_staff.user_id = ' . $userId . ' AND ' . $this->getTable() .
                        '.team_id = team_staff.team_id AND (team_staff.type = ' . Team::STAFF_MANAGER . ' OR team_staff.type = ' . Team::STAFF_OFFICER . ')'),
                    []
                )
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    new Expression($this->getTable() . '.target_id = apartments.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_APARTMENT),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['offices' => DbTables::TBL_OFFICES],
                    new Expression($this->getTable() . '.target_id = offices.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_OFFICE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment_groups' => DbTables::TBL_APARTMENT_GROUPS],
                    new Expression($this->getTable() . '.target_id = apartment_groups.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_BUILDING),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['storages' => DbTables::TBL_WM_STORAGE],
                    new Expression($this->getTable() . '.target_id = storages.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_STORAGE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['suppliers' => DbTables::TBL_SUPPLIERS],
                    $this->getTable() . '.supplier_id = suppliers.id',
                    [
                        'supplier_name' => 'name',
                    ],
                    Select::JOIN_LEFT
                );

            $select->where->in($this->getTable().'.status_shipping',
                [
                    OrderService::STATUS_RETURNED,
                    OrderService::STATUS_ISSUE
                ]
            );

        });
        return $result;
    }

    /**
     * @return mixed
     */
    public function getOrdersToBeShippedInLastTwoDays()
    {
        $twoDaysAgoDate = date(\Library\Constants\Constants::DATABASE_DATE_TIME_FORMAT, strtotime('-2 days', strtotime(date(\Library\Constants\Constants::DATABASE_DATE_TIME_FORMAT))));

        $result = $this->fetchAll(function (Select $select) use ($twoDaysAgoDate) {
            $select->columns([
                'id',
                'title',
                'status_shipping',
                'target_type',
                'quantity',
                'quantity_type',
                'order_date',
                'status',
                'tracking_url',
                'asset_category_id',
                'estimated_date_start',
                'estimated_date_end',
                'location_name' => new Expression(" (CASE " . $this->getTable() . ".target_type " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_APARTMENT . " THEN apartments.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_OFFICE . " THEN offices.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_BUILDING . " THEN apartment_groups.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_STORAGE . " THEN storages.name " .
                    " END)")
            ]);

            $select
                ->join(
                    ['asset_category' => DbTables::TBL_ASSET_CATEGORIES],
                    $this->getTable() . '.asset_category_id = asset_category.id',
                    [
                        'asset_category_name' => 'name',
                        'asset_category_type' => 'type_id'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    new Expression($this->getTable() . '.target_id = apartments.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_APARTMENT),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['offices' => DbTables::TBL_OFFICES],
                    new Expression($this->getTable() . '.target_id = offices.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_OFFICE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment_groups' => DbTables::TBL_APARTMENT_GROUPS],
                    new Expression($this->getTable() . '.target_id = apartment_groups.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_BUILDING),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['storages' => DbTables::TBL_WM_STORAGE],
                    new Expression($this->getTable() . '.target_id = storages.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_STORAGE),
                    [],
                    Select::JOIN_LEFT
                );

            $select->where->equalTo('status_shipping', OrderService::STATUS_ORDERED);
            $select->where->notEqualTo('is_archive', OrderService::STATUS_IS_ARCHIVE);
            $select->where->lessThan('order_date', $twoDaysAgoDate);
        });

        return $result;
    }

    /**
     * @param $userId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getOrdersCreatedByMe($userId)
    {
        $result = $this->fetchAll(function (Select $select) use ($userId) {
            $select->columns([
                'id',
                'title',
                'status_shipping',
                'target_type',
                'quantity',
                'quantity_type',
                'order_date',
                'supplier_transaction_id',
                'status',
                'tracking_url',
                'estimated_date_start',
                'estimated_date_end',
                'location_name' => new Expression(" (CASE " . $this->getTable() . ".target_type " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_APARTMENT . " THEN apartments.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_OFFICE . " THEN offices.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_BUILDING . " THEN apartment_groups.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_STORAGE . " THEN storages.name " .
                    " END)")
            ]);

            $select
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    new Expression($this->getTable() . '.target_id = apartments.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_APARTMENT),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['offices' => DbTables::TBL_OFFICES],
                    new Expression($this->getTable() . '.target_id = offices.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_OFFICE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment_groups' => DbTables::TBL_APARTMENT_GROUPS],
                    new Expression($this->getTable() . '.target_id = apartment_groups.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_BUILDING),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['storages' => DbTables::TBL_WM_STORAGE],
                    new Expression($this->getTable() . '.target_id = storages.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_STORAGE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['suppliers' => DbTables::TBL_SUPPLIERS],
                    $this->getTable() . '.supplier_id = suppliers.id',
                    [
                        'supplier_name' => 'name',
                    ],
                    Select::JOIN_LEFT
                );

            $select->where->equalTo('creator_id', $userId)
                          ->notEqualTo('is_archive', OrderService::STATUS_IS_ARCHIVE);

        });
        return $result;
    }

    /**
     * @param $orderId
     * @param $userId
     * @return array|\ArrayObject|null
     */
    public function isYourTeamOrder($orderId, $userId)
    {
        return $this->fetchOne(function (Select $select) use ($orderId, $userId) {
            $select->columns(['id']);
            $select->join(
                ['team_staff' => DbTables::TBL_TEAM_STAFF],
                new Expression('team_staff.user_id = ' . $userId . ' AND ' . $this->getTable() .
                    '.team_id = team_staff.team_id AND (team_staff.type = ' . Team::STAFF_MANAGER . ' OR team_staff.type = ' . Team::STAFF_OFFICER . ')'),
                []
            );
            $select->where->equalTo($this->getTable().'.id', $orderId);
        });
    }

    /**
     * @param $orderId
     * @return int
     */
    public function forRejectOrder($orderId)
    {
        $this->setEntity(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($orderId) {
            $select->columns(['creator_id']);
            $select->where
                        ->equalTo('id', $orderId)
                        ->notEqualTo('status', OrderService::STATUS_ORDER_APPROVED)
                        ->equalTo('status_shipping', OrderService::STATUS_TO_BE_ORDERED);
            ;
        });
    }

    /**
     * @param $orderId
     * @return int
     */
    public function getDataForPOItem($orderId)
    {
        $this->setEntity(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($orderId) {
            $select->columns([
                'quantity',
                'quantity_type',
                'date_created',
                'title',
                'description',
                'price',
                'currency_id',
                'po_item_id',
                'creator_id',
                'location_name' => new Expression(" (CASE " . $this->getTable() . ".target_type " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_APARTMENT . " THEN apartments.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_OFFICE . " THEN offices.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_BUILDING . " THEN apartment_groups.name " .
                    "WHEN " . OrderService::ORDER_LOCATION_TYPE_STORAGE . " THEN storages.name " .
                    " END)")
            ]);

            $select->join(
                    ['asset_category' => DbTables::TBL_ASSET_CATEGORIES],
                    $this->getTable() . '.asset_category_id = asset_category.id',
                    ['asset_category_name' => 'name'],
                    Select::JOIN_LEFT
                ) ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    new Expression($this->getTable() . '.target_id = apartments.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_APARTMENT),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['offices' => DbTables::TBL_OFFICES],
                    new Expression($this->getTable() . '.target_id = offices.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_OFFICE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment_groups' => DbTables::TBL_APARTMENT_GROUPS],
                    new Expression($this->getTable() . '.target_id = apartment_groups.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_BUILDING),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['storages' => DbTables::TBL_WM_STORAGE],
                    new Expression($this->getTable() . '.target_id = storages.id AND ' . $this->getTable() .'.target_type = ' . OrderService::ORDER_LOCATION_TYPE_STORAGE),
                    [],
                    Select::JOIN_LEFT
                )->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.creator_id = users.id',
                    ['user' => new Expression('CONCAT(users.firstname, " ", users.lastname)')],
                    Select::JOIN_LEFT
                )->join(
                    ['account' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                    new Expression($this->getTable() . '.supplier_id = account.holder_id AND account.type =' . Account::TYPE_SUPPLIER),
                    ['account_id' => 'id'],
                    Select::JOIN_LEFT
                );
            $select->where->equalTo($this->getTable() . '.id', $orderId);
        });
    }

}
