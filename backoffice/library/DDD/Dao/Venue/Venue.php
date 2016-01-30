<?php

namespace DDD\Dao\Venue;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Library\Finance\Base\Account;
use DDD\Service\Venue\Venue as VenueService;

class Venue extends TableGatewayManager
{
    /**
     * @var string
     */
    protected $table   = DbTables::TBL_VENUES;

    /**
     * @param \Zend\ServiceManager\ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Venue\Venue')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getVenues()
    {
        $result = $this->fetchAll(function (Select $select) {

        });

        return $result;
    }

    /**
     * @param $id
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getVenueById($id)
    {
        $result = $this->fetchOne(function (Select $select) use ($id) {
            $select->columns([
                'id',
                'name',
                'currency_id',
                'city_id',
                'threshold_price',
                'discount_price',
                'perday_max_price',
                'perday_min_price',
                'accept_orders',
                'status',
                'manager_id',
                'cashier_id',
                'creation_date',
                'type',
                'account_name' => new Expression('
                    ifnull(
                        ifnull(partner.partner_name, supplier.name),
                        concat(people.firstname, " ", people.lastname)
                    )
                '),
                'account_id' => new Expression('
                    ifnull(
                        ifnull(partner.gid, supplier.id), people.id
                    )
                '),
            ]);

            $select->join(
                ['accounts' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                $this->getTable() . '.account_id = accounts.id',
                [
                    'unique_id' => 'id',
                    'account_type' => 'type',
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['people' => DbTables::TBL_BACKOFFICE_USERS],
                new Expression('people.id = accounts.holder_id and accounts.type = ' . Account::TYPE_PEOPLE),
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['supplier' => DbTables::TBL_SUPPLIERS],
                new Expression('supplier.id = accounts.holder_id and accounts.type = ' . Account::TYPE_SUPPLIER),
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                new Expression('partner.gid = accounts.holder_id and accounts.type = ' . Account::TYPE_PARTNER),
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['currency_code' => 'code'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.id', $id);
        });

        return $result;
    }

    /**
     * @param array $params
     * @return array|\ArrayObject|null
     */
    public function getVenuesByParams($params = [])
    {
        $result = $this->fetchAll(function (Select $select) use ($params) {
            $where = new Where();

            if (isset($params['acceptOrders']) && $params['acceptOrders'] > 0) {
                $where->equalTo('accept_orders', $params['acceptOrders']);
            }
            if (isset($params['cityId']) && $params['cityId'] > 0) {
                $where->equalTo('city_id', $params['cityId']);
            }
            if (isset($params['managerId']) && $params['managerId'] > 0) {
                $where->equalTo('manager_id', $params['managerId']);
            }
            if (isset($params['cashierId']) && $params['cashierId'] > 0) {
                $where->equalTo('cashier_id', $params['cashierId']);
            }

            $where->equalTo('status', \DDD\Service\Venue\Venue::VENUE_STATUS_ACTIVE);

            $select->where($where)
                   ->order('id DESC');
        });

        return $result;
    }

    /**
     * @param $cityId
     * @return array|\ArrayObject|null
     */
    public function thereIsLunchroomInUserCityThatExceptOrders($cityId)
    {
        return $this->fetchOne(function (Select $select) use ($cityId){
            $select->columns(['id']);
            $select->where->equalTo('city_id', $cityId)
                ->where->equalTo('accept_orders', VenueService::VENUE_ACCEPT_ORDERS_ON)
                ->where->equalTo('status', VenueService::VENUE_STATUS_ACTIVE)
                ->where->equalTo('type', VenueService::VENUE_TYPE_LUNCHROOM);
        });
    }

    /**
     * @param $cityId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getLunchroomsForLoggedInUser($cityId)
    {
        return $this->fetchAll(function (Select $select) use ($cityId){
            $select->columns(['id', 'name', 'threshold_price', 'discount_price']);
            $select->where->equalTo('city_id', $cityId)
                ->where->equalTo('accept_orders', VenueService::VENUE_ACCEPT_ORDERS_ON)
                ->where->equalTo('status', VenueService::VENUE_STATUS_ACTIVE)
                ->where->equalTo('type', VenueService::VENUE_TYPE_LUNCHROOM);
        });
    }
}
