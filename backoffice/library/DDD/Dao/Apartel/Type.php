<?php

namespace DDD\Dao\Apartel;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;

class Type extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTEL_TYPE;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = '\DDD\Domain\Apartel\Type\Type')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $apartelId
     * @param $allStatus
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getApartelTypesWithRates($apartelId, $allStatus)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll( function (Select $select) use($apartelId, $allStatus) {
                $select->columns([
                    'type_id' => 'id',
                    'type_name' => 'name',
                    'type_cubilis_id' => 'cubilis_id',
                ]);

                $select->join(
                    ['rates' => DbTables::TBL_APARTEL_RATES],
                    $this->getTable() . '.id = rates.apartel_type_id',
                    [
                        'rate_id' => 'id',
                        'rate_name' => 'name',
                        'rate_cubilis_id' => 'cubilis_id',
                        'type' => 'type',
                    ],
                    $select::JOIN_LEFT
                );

                $select->where->equalTo($this->getTable() . '.apartel_id', $apartelId);
                if (!$allStatus) {
                    $select->where
                        ->equalTo('rates.active', 1)
                    ;
                }
            }
        );
    }

    /**
     * @param $typeId
     * @return array|\ArrayObject|null
     */
    public function getApartelTypeSyncWithCubilis($typeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Apartel\Type\Type());
        return $this->fetchOne( function (Select $select) use($typeId) {
                $select->columns([
                    'id' => 'id'
                ]);

                $select->where->equalTo('id', $typeId)
                              ->isNotNull('cubilis_id')
                ;
            }
        );
    }

    /**
     * @param $cubilisRoomId
     * @return array|\ArrayObject|null
     */
    public function getRoomTypeByCubilisRoomId($cubilisRoomId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Apartel\Type\Type());
        return $this->fetchOne(function(Select $select) use($cubilisRoomId) {
            $select->columns(['id']);

            $select->join(
                ['apartel' => DbTables::TBL_APARTELS],
                $this->getTable() . '.apartel_id = apartel.id',
                []
            );

            // apartel is connected to cubilis
            $select->where->equalTo($this->getTable() . '.cubilis_id', $cubilisRoomId)
                          ->equalTo('apartel.sync_cubilis', 1);
        });
    }

    /**
     * @param $apartelId
     * @param $roomListCount
     * @return \Zend\Db\ResultSet\ResultSetInterface
     */
    public function getBuildingsListByApartelRoomListCount($apartelId, $roomListCount)
    {
        $sql = "SELECT
                main.building_id
                FROM
                (SELECT
                    a.building_id, COUNT(a.building_id) AS count_apartment
                FROM
                    ga_apartel_type AS a_t
                INNER JOIN ga_rel_apartel_type_apartment AS r_a_a ON a_t.id = r_a_a.apartel_type_id
                INNER JOIN ga_apartments AS a ON r_a_a.apartment_id = a.id
                WHERE
                    a_t.apartel_id = {$apartelId}
                GROUP BY a.building_id) AS main
                WHERE
                main.count_apartment >= {$roomListCount}";
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute();
        $this->setEntity(new \ArrayObject());
        $resultSet = clone $this->resultSetPrototype;
        $resultSet->initialize($result);
        return $resultSet;
    }

    /**
     * @param $apartelId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllSyncRoomTypes($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll( function (Select $select) use($apartelId) {
            $select->columns([
                'id' => 'id'
            ]);

            $select->where->equalTo('apartel_id', $apartelId)
                ->isNotNull('cubilis_id')
            ;
        }
        );
    }

    /**
     * @param $typeId
     * @return array|\ArrayObject|null
     */
    public function getApartel($typeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne( function (Select $select) use($typeId) {
                $select->columns([
                    'apartel_id',
                ]);

                $select->where->equalTo('id', $typeId);
            }
        );
    }

    /**
     * @param $apartelId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllApartmentForApartel($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll( function (Select $select) use($apartelId) {
            $select->columns([]);
            $select->join(
                ['rel_type_apartment' => DbTables::TBL_APARTEL_REL_TYPE_APARTMENT],
                $this->getTable() . '.id = rel_type_apartment.apartel_type_id',
                [],
                $select::JOIN_INNER
            )->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                'rel_type_apartment.apartment_id = apartment.id',
                [
                    'id',
                    'name'
                ],
                $select::JOIN_INNER
            );

            $select->where->equalTo($this->getTable() . '.apartel_id', $apartelId);
            $select->group('apartment.id');
        });
    }

    /**
     * @param $typeId
     * @return array|\ArrayObject|null
     */
    public function getRoomTypeData($typeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne( function (Select $select) use($typeId) {
            $select->columns([
                'name',
            ]);

            $select->where->equalTo('id', $typeId);
        }
        );
    }

    /**
     * @param $apartelId
     * @return array|\ArrayObject|null
     */
    public function getFirstRoomType($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
            return $this->fetchOne( function (Select $select) use($apartelId) {
                $select->columns([
                    'id',
                ]);

                $select->where->equalTo('apartel_id', $apartelId);
            }
        );
    }

    /**
     * @param $apartelId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllTypes($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll( function (Select $select) use($apartelId) {
            $select->columns([
                'id',
                'name',
            ]);
            $select->where->equalTo('apartel_id', $apartelId);
            }
        );
    }

    /**
     * @param $groupId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllRoomTypesByGroupId($groupId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll( function (Select $select) use($groupId) {
            $select->columns([
                'id',
                'name',
            ]);
            $select->join(
                ['apartel' => DbTables::TBL_APARTELS],
                $this->getTable() . '.apartel_id = apartel.id',
                [],
                $select::JOIN_INNER
            );
            $select->where->equalTo('apartment_group_id', $groupId);
            $select->order('name ASC');
        });
    }

    /**
     * @param $groupId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getRoomTypeForWebsite($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll( function (Select $select) use($apartelId) {
            $select->columns([
                'id',
                'name',
            ]);
            $select->join(
                ['rate' => DbTables::TBL_APARTEL_RATES],
                new Expression( $this->getTable() . '.id = rate.apartel_type_id AND rate.active = 1'),
                [
                    'price' => new Expression('MIN(week_price)'),
                ],
                $select::JOIN_INNER
            )->join(
                ['rel' => DbTables::TBL_APARTEL_REL_TYPE_APARTMENT],
                $this->getTable() . '.id = rel.apartel_type_id',
                [],
                $select::JOIN_INNER
            )->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                'rel.apartment_id = apartment.id',
                [
                    'square_meters',
                    'bedroom_count',
                    'bathroom_count',
                ],
                $select::JOIN_INNER
            )->join(
                ['currency' => DbTables::TBL_CURRENCY],
                'apartment.currency_id = currency.id',
                [
                    'code',
                    'symbol'
                ]
            );
            $select->where->equalTo($this->getTable() . '.apartel_id', $apartelId);
            $select->group($this->getTable() . '.id');
        });
    }


}
