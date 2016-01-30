<?php
namespace DDD\Dao\Geolocation;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Expression;

class Details extends TableGatewayManager
{
    protected $table = DbTables::TBL_LOCATION_DETAILS;

    public function __construct($sm, $domain = 'DDD\Domain\Geolocation\Details')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $txt
     * @param null $is_searchable
     * @return \DDD\Domain\Geolocation\Details[]
     */
    public function getCountriesByText($txt, $is_searchable = NULL)
    {
        $result = $this->fetchAll(function (Select $select) use($txt, $is_searchable) {
            $select->join(
                ['countries' => DbTables::TBL_COUNTRIES],
                $this->getTable().'.id = countries.detail_id',
                ['location_id'=>'id']
            );

            $select->join(
                ['continents' => DbTables::TBL_CONTINENTS],
                'continents.id = countries.continent_id',
                ['parent_id' => 'id']
            );

            $select->where
                    ->like($this->getTable() . '.name', $txt . '%');

            if (isset($is_searchable)) {
                $select->where(array($this->getTable().'.is_searchable', $is_searchable));
            }

            $select
                ->columns([
                    'id',
                    'name',
                    'category' => new Expression('"Countries"')
                ])
                ->order('name');
        });

        return $result;
    }

    public function getProvincesByText($txt, $is_searchable = NULL)
    {
        $result = $this->fetchAll(function (Select $select) use($txt, $is_searchable) {
            $select->join(
                array('provinces' => DbTables::TBL_PROVINCES),
                $this->getTable().'.id = provinces.detail_id',
                array('location_id'=>'id'));

            $select->join(
                array('countries' => DbTables::TBL_COUNTRIES),
                'countries.id = provinces.country_id',
                array('parent_id'=>'id'));

            $select->where
                    ->like($this->getTable().'.name', '%' . $txt . '%');

            if(isset($is_searchable)) {
                $select->where(array($this->getTable().'.is_searchable', $is_searchable));
            }

            $select->columns(array('id', 'name'))
                    ->order('name');
        });

        return $result;
    }

    public function getCitiesByText($txt, $is_searchable = NULL)
    {
        $result = $this->fetchAll(function (Select $select) use($txt, $is_searchable) {
            $select->join(
                array('cities' => DbTables::TBL_CITIES),
                $this->getTable().'.id = cities.detail_id',
                array('location_id'=>'id'));

            $select->join(
                array('provinces' => DbTables::TBL_PROVINCES),
                'provinces.id = cities.province_id',
                array('parent_id'=>'id'));

            $select->where
                    ->like($this->getTable().'.name', '%' . $txt . '%');

            if(isset($is_searchable)) {
                $select->where(array($this->getTable().'.is_searchable', $is_searchable));
            }

            $select->columns(array('id', 'name', 'category' => new Expression('"Cities"')))
                    ->order('name');
        });

        return $result;
    }

    public function getPoisByText($txt, $is_searchable = NULL)
    {
        $result = $this->fetchAll(function (Select $select) use($txt, $is_searchable) {
            $select->join(
                array('poi' => DbTables::TBL_POI),
                $this->getTable().'.id = poi.detail_id',
                array('location_id'=>'id'));

            $select->where
                    ->like($this->getTable().'.name', '%' . $txt . '%');

            if(isset($is_searchable)) {
                $select->where(array($this->getTable().'.is_searchable', $is_searchable));
            }

            $select->columns(array('id', 'name'))
                    ->order('name');
        });

        return $result;
    }

    public function getParentDetail($id, $where, $tbl, $tbl_parent)
    {
        $result = $this->fetchOne(function (Select $select) use($id, $where, $tbl, $tbl_parent) {
            $select->columns(
                [
                    'name',
                    'slug'
                ]
            );

            $select->join(
                ['parent' => $tbl_parent],
                $this->getTable().'.id = parent.detail_id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['thistbl' => $tbl],
                'thistbl.'.$where.' = parent.id',
                [],
                Select::JOIN_LEFT
            );

            $select->where
                   ->equalTo('thistbl.id', $id);
        });

        return $result;
    }

    public function getTranslationListForSearch(
            $filterParams = array()
            )
    {
        $where = new Where();

        if((int)$filterParams["id_translation"] > 0){
                $where->equalTo($this->getTable().'.id', $filterParams["id_translation"]);
        } else {

            if ( $filterParams["srch_txt"] != '' ) {
                $pred = new Predicate();
                $pred->like($this->getTable().'.name', '%'.  strip_tags(trim($filterParams["srch_txt"])).'%')
                    ->or
                    ->like($this->getTable().'.information_text_html_clean', '%'.  strip_tags(trim($filterParams["srch_txt"])).'%');
                $where->addPredicate($pred);
            }
        }

    	$columns = [
            'id'      => 'id',
            'tx_2'    => 'information_text',
            'name'    => 'name',
            'name_en' => 'name'
    	];

    	$sortColumns = ['id', 'information_text', 'name_en'];

    	$result = $this->fetchAll(function (Select $select) use($columns, $sortColumns, $where) {

    		$select->columns( $columns );

    		$select->join(
                    DbTables::TBL_COUNTRIES,
                    $this->getTable() . '.id = ' . DbTables::TBL_COUNTRIES . '.detail_id',
                    array('country' => 'id'), 'left');

    		$select->join(
                    DbTables::TBL_PROVINCES,
                    $this->getTable() . '.id = ' . DbTables::TBL_PROVINCES . '.detail_id',
                    array('provinces' => 'id'), 'left');

    		$select->join(
                    DbTables::TBL_CITIES,
                    $this->getTable() . '.id = ' . DbTables::TBL_CITIES . '.detail_id',
                    array('city' => 'id'), 'left');

    		$select->join(
                    DbTables::TBL_POI,
                    $this->getTable() . '.id = ' . DbTables::TBL_POI . '.detail_id',
                    array('poi' => 'id'), 'left');

    		if ($where !== null) {
    			$select->where($where);
    		}

            $select->group($this->getTable() . '.id');
    	});

      $count = $this->getCount($where);

      return ['result'=>$result, 'count'=>$count];
    }

    public function getforTranslation($param)
    {
        $name = ($param['locationOptions'] == 'name')
            ? 'name'
            : 'information_text';

        $result = $this->fetchOne(function (Select $select) use($param, $name) {
            $select->columns([
                'id',
                'content' => $name,
                'type'    => 'name'
            ]);

            $select->where->equalTo($this->getTable().'.id', $param['id']);
        });

        return $result;
    }

    /**
     *
     * @param int $id
     * @return \DDD\Domain\Geolocation\Details
     */
    public function getDetailsById($id)
    {
        $result = $this->fetchOne(function (Select $select) use($id) {
            $select->columns([
                'name',
                'latitude',
                'longitude',
                'cover_image',
                'description' => 'information_text',
                'information_text',
                'is_searchable'
            ]);

            $select->where->equalTo('id', $id);
        });

        return $result;
    }

    public function getTaxDataByApartmentId($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns([
                'tot',
                'tot_type',
                'tot_included',
                'vat',
                'vat_type',
                'vat_included',
                'sales_tax',
                'sales_tax_type',
                'sales_tax_included',
                'city_tax',
                'city_tax_type',
                'city_tax_included',
            ]);

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                $this->getTable().'.id = city.detail_id',
                [],
                $select::JOIN_INNER
            );

            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                'city.id = apartment.city_id',
                [],
                $select::JOIN_INNER
            );

            $select->where->equalTo('apartment.id', $apartmentId);
        });
    }
}