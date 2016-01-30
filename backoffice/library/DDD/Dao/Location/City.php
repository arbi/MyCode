<?php
namespace DDD\Dao\Location;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Constants\Objects;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use DDD\Service\Location;

class City extends TableGatewayManager
{
    protected $table = DbTables::TBL_CITIES;

    public function __construct($sm, $domain = 'DDD\Domain\Location\City')
    {
        parent::__construct($sm, $domain);
    }

    public function getCityThumbById($id)
    {
        $result = $this->fetchOne(function(Select $select) use($id){
            $select->columns(array(
                'id',
            ));

            $select->join(
                array('details' => DbTables::TBL_LOCATION_DETAILS),
                $this->getTable().'.detail_id = details.id',
                array(
                    'thumb' => 'thumbnail'
                ),
                'left'
            );

            $select->where
                    ->equalTo($this->getTable().'.id', $id);
        });

        return $result;
    }

    /**
     * @param bool $txt
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getCityForSearch($txt = false)
    {
        return $this->fetchAll(function (Select $select) use ($txt) {
            $select->columns([
                'id',
                'timezone',
                'max_capacity' => new Expression('(
                    select max(max_capacity)
                    from ga_apartments
                    where ga_apartments.city_id = ga_cities.id and ga_apartments.status in (' . Objects::PRODUCT_STATUS_LIVEANDSELLIG .', ' . Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE . ')
                )'),
            ]);

            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                $this->table . '.detail_id = details.id',
                [
                    'city_url' => 'slug',
                ]
            );

            $select->join(
                ['provinces' => DbTables::TBL_PROVINCES],
                $this->table . '.province_id = provinces.id',
                [
                    'country_id',
                    'short_name'
                ]
            );

            if ($txt) {
                $select->where->like('details.name', '%' . $txt . '%');
            }

            $select->where->equalTo('details.is_searchable', 1);

            $select->group($this->table . '.id');

            $select->order([$this->table . '.ordering' => 'ASC']);
        });
    }

    public function getCityForSearchBySlug($slug = false)
    {
        $result = $this->fetchAll(function (Select $select) use ($slug) {
            $select->columns(
                [
                    'id',
                    'timezone'
                ]
            );

            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                $this->table . '.detail_id = details.id',
                [
                    'city_url' => 'name',
                    'city_slug' => 'slug'
                ]
            );

            $select->join(
                ['provinces' => DbTables::TBL_PROVINCES],
                $this->table . '.province_id = provinces.id',
                ['country_id']
            );

            if ($slug) {
                $select->where->equalTo('details.slug', $slug);
            }

            $select->where
                ->equalTo('details.is_searchable', 1);

            $select->group($this->table . '.id');

            $select->order([$this->table . '.ordering' => 'ASC']);
        });
        return $result;
    }

    public function getCityByName($name)
    {
        $result = $this->fetchOne(function (Select $select) use($name) {
            $select->columns(
                [
                    'id', 'timezone'
                ]
            );

            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                $this->table . '.detail_id = details.id',
                []
            );

            $select->join(
                ['general' => DbTables::TBL_APARTMENTS],
                $this->table . '.id = general.city_id',
                []
            );

            $select->where
                ->equalTo('general.status', Objects::PRODUCT_STATUS_LIVEANDSELLIG)
                ->equalTo('details.name', $name);
        });

        return $result;
    }

    public function getCityBySlug($slug)
    {
        $result = $this->fetchOne(function (Select $select) use($slug) {
            $select->columns(
                [
                    'id', 'timezone'
                ]
            );

            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                $this->table . '.detail_id = details.id',
                []
            );

            $select->join(
                ['general' => DbTables::TBL_APARTMENTS],
                $this->table . '.id = general.city_id',
                []
            );

            $select->where
                ->equalTo('general.status', Objects::PRODUCT_STATUS_LIVEANDSELLIG)
                ->equalTo('details.slug', $slug);
        });

        return $result;
    }

    public function getCityListForIndex()
    {
        $sql = "SELECT sub3.*, SUM(b_g.man_count) as capacity FROM (
                    SELECT sub2.*, SUM(DATEDIFF(`b_w`.`date_to`, `b_w`.`date_from`)) AS `sold_future_nights` FROM (
                        SELECT sub1.*, SUM(DATEDIFF(`b_p`.`date_to`, `b_p`.`date_from`)) AS `spent_nights` FROM (
                            SELECT city.`id` AS `city_id`, `det1`.`name` AS `city_url`, `det2`.`name` AS `province_url`,
                            `provinces`.`country_id` AS `country_id`, city.`ordering`, `det1`.`cover_image`, `det1`.`id` as detail_id, `det1`.`slug` as city_slug, `det2`.`slug` as province_slug
                            FROM " . DbTables::TBL_CITIES . " as city
                            INNER JOIN " . DbTables::TBL_LOCATION_DETAILS. " AS `det1` ON city.`detail_id` = `det1`.`id`
                            INNER JOIN " . DbTables::TBL_PROVINCES. " AS `provinces` ON city.`province_id` = `provinces`.`id`
                            INNER JOIN " . DbTables::TBL_LOCATION_DETAILS. " AS `det2` ON `provinces`.`detail_id` = `det2`.`id`
                            WHERE `det1`.`is_searchable` = 1 AND `city`.`id` IN (" . Location::getCityListForWebsiteTopDestinationsWidget() . ") GROUP BY city.`id`
                        ) AS sub1
                        INNER JOIN " . DbTables::TBL_BOOKINGS. " AS `b_p` ON `b_p`.`acc_city_id` = `sub1`.`city_id`  AND `b_p`.`date_to` <= NOW() AND `b_p`.`status` = 1
                        GROUP BY `sub1`.`city_id`
                    ) AS sub2
                    INNER JOIN " . DbTables::TBL_BOOKINGS. " AS `b_w` ON `b_w`.`acc_city_id` = `sub2`.`city_id`  AND `b_w`.`date_to` > NOW() AND `b_w`.`status` = 1
                    GROUP BY `sub2`.`city_id` ORDER BY `sub2`.`ordering` ASC
                ) AS sub3
                INNER JOIN " . DbTables::TBL_BOOKINGS. " AS `b_g` ON `b_g`.`acc_city_id` = `sub3`.`city_id`  AND `b_g`.`date_from` <= NOW() AND `b_g`.`date_to` >= NOW() AND `b_g`.`status` = 1
                GROUP BY `sub3`.`city_id`
                ORDER BY `sub3`.`ordering` ASC";
        $statement = $this->adapter->createStatement($sql, array());
        $result = $statement->execute();
    	return $result;
    }

    public function getParentCountryCurrency($cityId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());


        $result = $this->fetchOne(function (Select $select) use ($cityId)
        {
            $select->where([
                $this->getTable().'.id' => $cityId
            ]);

            $select->join(
                DbTables::TBL_PROVINCES,
                $this->getTable().'.province_id = '.DbTables::TBL_PROVINCES.'.id',
                []
            );

            $select->join(
                DbTables::TBL_COUNTRIES,
                DbTables::TBL_PROVINCES.'.country_id = '.DbTables::TBL_COUNTRIES.'.id',
                []
            );

            $select->join(
                DbTables::TBL_CURRENCY,
                DbTables::TBL_COUNTRIES.'.currency_id = '.DbTables::TBL_CURRENCY.'.id',
                ['code']
            );
		});

        return $result;
    }
}
