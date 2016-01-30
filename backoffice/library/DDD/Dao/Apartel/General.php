<?php

namespace DDD\Dao\Apartel;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use DDD\Service\Apartel\General as ApartelGeneralService;

use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Db\Sql\Expression;

class General extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTELS;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = '\DDD\Domain\Apartel\General\General')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $apartelId
     * @param bool|true $onlyActive
     * @return false|\DDD\Domain\Apartel\General\General
     */
    public function getApartelById($apartelId, $onlyActive = true)
    {
        $result = $this->fetchOne(function (Select $select) use ($apartelId, $onlyActive) {
            $select->columns([
                'id',
                'apartment_group_id',
                'slug',
                'status'
            ]);

            if ($onlyActive) {
                $select->where
                    ->equalTo('status', ApartelGeneralService::APARTEL_STATUS_ACTIVE);
            }

            $select->where
                ->equalTo('id', $apartelId);
        });

        return $result;
    }

    /**
     * @param $apartelId
     * @return array|\ArrayObject|null
     */
    public function getCubilisConnectionDetails($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use($apartelId) {
            $select->columns([
                'cubilis_id',
                'cubilis_username',
                'cubilis_password',
                'sync_cubilis',
            ]);
            $select->where->equalTo($this->getTable() . '.id', $apartelId);
        });

        return $result;
    }

    /**
     * @param $apartelId
     * @return array|\ArrayObject|null
     */
    public function isApartel($apartelId)
    {
        return $this->fetchOne(function (Select $select) use($apartelId) {
            $select->columns(['id']);
            $select->join(
                ['apartment_group' => DbTables::TBL_APARTMENT_GROUPS],
                $this->getTable() . '.apartment_group_id = apartment_group.id',
                []
            );

            $select->where->equalTo($this->getTable() . '.id', $apartelId)
                          ->equalTo('apartment_group.usage_apartel', 1);
        });
    }

    /**
     * @param $apartelId
     * @return array|\ArrayObject|null
     */
    public function getApartelDataForHeader($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne( function (Select $select) use($apartelId) {
                $select->columns([
                    'id',
                    'apartment_group_id',
                    'sync_cubilis',
                    'slug',
                    'status'
                ]);
                $select->join(
                    ['apartment_group' => DbTables::TBL_APARTMENT_GROUPS],
                    $this->getTable() . '.apartment_group_id = apartment_group.id',
                    ['name']
                )->join(
                    ['country' => DbTables::TBL_COUNTRIES],
                    'apartment_group.country_id = country.id',
                    []
                )->join(
                    ['location_details' => DbTables::TBL_LOCATION_DETAILS],
                    'country.detail_id = location_details.id',
                    ['country_name' => 'name']
                )->join(
                    ['apartel_type' => DbTables::TBL_APARTEL_TYPE],
                    $this->getTable() . '.id = apartel_type.apartel_id',
                    [],
                    $select::JOIN_LEFT
                )->join(
                    ['rel_apartel_type' => DbTables::TBL_APARTEL_REL_TYPE_APARTMENT],
                    'apartel_type.id = rel_apartel_type.apartel_type_id',
                    [],
                    $select::JOIN_LEFT
                )->join(
                    ['apartment' => DbTables::TBL_APARTMENTS],
                    'rel_apartel_type.apartment_id = apartment.id',
                    [],
                    $select::JOIN_LEFT
                )->join(
                    ['city' => DbTables::TBL_CITIES],
                    'apartment.city_id = city.id',
                    [],
                    $select::JOIN_LEFT
                )->join(
                    ['location_details1' => DbTables::TBL_LOCATION_DETAILS],
                    'city.detail_id = location_details1.id',
                    ['city_slug' => 'slug'],
                    $select::JOIN_LEFT
                )
                ;

                $select->where->equalTo($this->getTable() . '.id', $apartelId)
                    ->equalTo('apartment_group.usage_apartel', 1);
            }
        );
    }

    /**
     * @param $apartelId
     * @return array|\ArrayObject|null
     */
    public function getGeneralConnectionData($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Apartel\General\General());
        $result = $this->fetchOne(function (Select $select) use($apartelId) {
            $select->columns([
                'cubilis_id',
                'cubilis_username',
                'cubilis_password',
                'sync_cubilis',
            ]);
            $select->where->equalTo($this->getTable() . '.id', $apartelId);
        });

        return $result;
    }

    /**
     * @return array|\ArrayObject|null
     */
    public function getReadyToSyncCubilis()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Apartel\General\General());
        $result = $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'cubilis_password',
                'cubilis_username',
                'cubilis_id',
            ]);
            $select->where->equalTo('sync_cubilis', 1);
        });

        return $result->buffer();
    }

    public function getApartelCurrency($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function(Select $select) use($apartelId) {
            $select->columns([]);

            $select->join(
                ['apartel_type' => DbTables::TBL_APARTEL_TYPE],
                $this->getTable() . '.id = apartel_type.apartel_id',
                []
            );

            $select->join(
                ['apartel_rel' => DbTables::TBL_APARTEL_REL_TYPE_APARTMENT],
                'apartel_type.id = apartel_rel.apartel_type_id',
                []
            );

            $select->join(
                ['apartment_general' => DbTables::TBL_APARTMENTS],
                'apartel_rel.apartment_id = apartment_general.id',
                []
            );

            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                'apartment_general.currency_id = currency.id',
                ['code']
            );

            $select->where
                ->equalTo($this->getTable() . '.id', $apartelId);
            $select->limit(1);
        });
    }

    /**
     * @param $apartelId
     * @return array|\ArrayObject|null
     */
    public function getApartmentGroup($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use($apartelId) {
            $select->columns([
                'apartment_group_id',
            ]);
            $select->where->equalTo($this->getTable() . '.id', $apartelId);
        });

        return $result;
    }

    /**
     * @param $apartmentGroupId
     * @return array|\ArrayObject|null
     */
    public function getApartelByApartmentGroup($apartmentGroupId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use($apartmentGroupId) {
            $select->columns([
                'id',
            ]);
            $select->where->equalTo('apartment_group_id', $apartmentGroupId);
        });

        return $result;
    }

    /**
     * @param $apartelId
     * @param $limit
     * @return \ArrayObject
     */
    public function getPopularReviews($apartelId, $limit = 5)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchAll(function (Select $select) use($apartelId, $limit) {
            $select->columns([
                'count' => new Expression('count(*)'),
                ]);

            $select->join(
                ['apartment_group_items' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                $this->getTable() . '.apartment_group_id = apartment_group_items.apartment_group_id',
                []
            );
            $select->join(
                ['reviews' => DbTables::TBL_PRODUCT_REVIEWS],
                'reviews.apartment_id = apartment_group_items.apartment_id',
                []
            );
            $select->join(
                ['apartment_review_category_rel' =>  DbTables::TBL_APARTMENT_REVIEW_CATEGORY_REL],
                'apartment_review_category_rel.review_id = reviews.id',
                []
            );
            $select->join(
                ['apartment_review_category' =>  DbTables::TBL_APARTMENT_REVIEW_CATEGORY],
                'apartment_review_category.id = apartment_review_category_rel.apartment_review_category_id',
                ['name', 'type']
            );
            $select->group('apartment_review_category.id');
            $select->order([
                'count'        => 'DESC',
                'reviews.date' => 'DESC'
            ]);
            $select->limit($limit);
            $select->where->equalTo($this->getTable() . '.id', $apartelId);
        });
    }

    /**
     * @param $apartelSlug
     * @return array|\ArrayObject|null
     */
    public function getApartelDataForWebsite($apartelSlug)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use($apartelSlug) {
            $select->columns(
                [
                    'id',
                ]
            );

            $select->join(
                ['apartment_group' => DbTables::TBL_APARTMENT_GROUPS],
                $this->getTable() . '.apartment_group_id = apartment_group.id',
                [
                    'name',
                    'timezone'
                ]
            )->join(
                ['details' => DbTables::TBL_APARTELS_DETAILS],
                $this->getTable() . '.id = details.apartel_id',
                [
                    'content_textline_id',
                    'moto_textline_id',
                    'meta_description_textline_id',
                    'bg_image'
                ]
            )->join(
                ['apartel_type' => DbTables::TBL_APARTEL_TYPE],
                $this->getTable() . '.id = apartel_type.apartel_id',
                []
            )->join(
                ['rel_apartel_type' => DbTables::TBL_APARTEL_REL_TYPE_APARTMENT],
                'apartel_type.id = rel_apartel_type.apartel_type_id',
                []
            )->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                'rel_apartel_type.apartment_id = apartment.id',
                [
                    'city_id',
                    'address',
                    'postal_code'
                ]
            )->join(
                ['city' => DbTables::TBL_CITIES],
                'apartment.city_id = city.id',
                []
            )->join(
                ['city_details' => DbTables::TBL_LOCATION_DETAILS],
                'city.detail_id = city_details.id',
                [
                    'city_name' => 'name'
                ]
            )->join(
                ['province' => DbTables::TBL_PROVINCES],
                'city.province_id = province.id',
                [
                    'short_name'
                ]
            )->join(
                ['country' => DbTables::TBL_COUNTRIES],
                'province.country_id = country.id',
                []
            )->join(
                ['country_details' => DbTables::TBL_LOCATION_DETAILS],
                'country.detail_id = country_details.id',
                [
                    'country_name' => 'name'
                ]
            );

            $select->where
                ->equalTo($this->getTable() . '.slug', $apartelSlug)
                ->equalTo($this->getTable() . '.status', ApartelGeneralService::APARTEL_STATUS_ACTIVE);
        });

        $this->setEntity($prototype);
        return $result;
    }

    /**
     * @param $apartelSlug
     * @return array|\ArrayObject|null
     */
    public function getApartelDataBySlug($apartelSlug)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use($apartelSlug) {
            $select->columns([]);

            $select->join(
                    ['apartment_group' => DbTables::TBL_APARTMENT_GROUPS],
                    $this->getTable() . '.apartment_group_id = apartment_group.id',
                    [
                        'timezone',
                        'apartel_name' => 'name'
                    ]
                )->join(
                    ['apartel_type' => DbTables::TBL_APARTEL_TYPE],
                    $this->getTable() . '.id = apartel_type.apartel_id',
                    []
                )->join(
                    ['rel_apartel_type' => DbTables::TBL_APARTEL_REL_TYPE_APARTMENT],
                    'apartel_type.id = rel_apartel_type.apartel_type_id',
                    []
                )->join(
                    ['apartment' => DbTables::TBL_APARTMENTS],
                    'rel_apartel_type.apartment_id = apartment.id',
                    [
                        'city_id'
                    ]
                )
            ;

            $select->where
                        ->equalTo($this->getTable() . '.slug', $apartelSlug)
                        ->equalTo($this->getTable() . '.status', ApartelGeneralService::APARTEL_STATUS_ACTIVE);
            $select->limit(1);
        });

        return $result;
    }

    /**
     * @param $apartelId
     * @return array|\ArrayObject|null
     */
    public function checkDuplicateCubilisInfo($apartelId, $cubilisId, $username, $password)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use($apartelId, $cubilisId, $username, $password) {
            $select->columns(['slug']);
            $select->where->notEqualTo($this->getTable() . '.id', $apartelId);
            $select->where->equalTo('sync_cubilis', 1);
            $select->where->equalTo('cubilis_id', $cubilisId);
            $select->where->equalTo('cubilis_username', $username);
            $select->where->equalTo('cubilis_password', $password);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

    /**
     * @param $apartmentGroupId
     * @param $apartmentId
     * @return array|\ArrayObject|null
     */
    public function checkApartmentFromThisApartel($apartmentGroupId, $apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use($apartmentGroupId, $apartmentId) {
            $select->columns([
                'id',
            ]);

            $select->join(
                ['apartel_type' => DbTables::TBL_APARTEL_TYPE],
                $this->getTable() . '.id = apartel_type.apartel_id',
                []
            )->join(
                ['rel_apartel_type' => DbTables::TBL_APARTEL_REL_TYPE_APARTMENT],
                'apartel_type.id = rel_apartel_type.apartel_type_id',
                []
            );
            $select->where->equalTo($this->getTable() . '.apartment_group_id', $apartmentGroupId)
                          ->equalTo('rel_apartel_type.apartment_id', $apartmentId);

        });

        return $result;
    }

    /**
     * @param bool|true $onlyActive
     * @return \ArrayObject[]
     */
    public function getApartelsForSitemap($onlyActive = true)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($onlyActive) {
            $select->columns([
                'id',
                'slug',
            ]);

            $select->join(
                ['apartel_type' => DbTables::TBL_APARTEL_TYPE],
                $this->getTable() . '.id = apartel_type.apartel_id',
                [],
                $select::JOIN_LEFT
            );

            $select->join(
                ['rel_apartel_type' => DbTables::TBL_APARTEL_REL_TYPE_APARTMENT],
                'apartel_type.id = rel_apartel_type.apartel_type_id',
                [],
                $select::JOIN_LEFT
            );

            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                'rel_apartel_type.apartment_id = apartment.id',
                [],
                $select::JOIN_LEFT
            );

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                'apartment.city_id = city.id',
                [],
                $select::JOIN_LEFT
            );

            $select->join(
                ['location_details1' => DbTables::TBL_LOCATION_DETAILS],
                'city.detail_id = location_details1.id',
                ['city_slug' => 'slug'],
                $select::JOIN_LEFT
            );

            if ($onlyActive) {
                $select->where
                    ->equalTo($this->getTable() . '.status', ApartelGeneralService::APARTEL_STATUS_ACTIVE);
            }

            $select->group($this->getTable() . '.id');
        });

        return $result;
    }
}
