<?php
namespace DDD\Dao\Translation;

use DDD\Service\Translation;
use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;

class Product extends TableGatewayManager
{
    protected $table = DbTables::TBL_PRODUCT_TEXTLINES;

    public function __construct($sm, $domain = ''){
        parent::__construct($sm, $domain);
    }

    public function getTranslationListForSearch(
        $filterParams   = array()
    ) {
    	$columns = array(
            'id'      => 'id',
            'type'    => 'entity_type',
            'content' => 'en',
            'entity_name' => new Expression("
                CASE
                    WHEN (apartel.id IS NOT NULL) THEN apartel_group.name
                    WHEN (building.id IS NOT NULL) THEN building.name
                    WHEN (apartment.id IS NOT NULL) THEN apartment.name
                    WHEN (office.id IS NOT NULL) THEN office.name
                    WHEN (parking.id IS NOT NULL) THEN parking.name
                    WHEN (building_section.id IS NOT NULL) THEN CONCAT(building_for_section.name, ' ', building_section.name)
                END
            ")
    	);

    	$sortColumns = ['id'];

    	$result = $this->fetchAll(function (Select $select) use($columns, $sortColumns, $filterParams) {

    		$select->columns( $columns );

    		$select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                new Expression(
                    $this->getTable() . '.entity_id = apartment.id AND ' .
                    $this->getTable() . '.entity_type IN (' . implode(', ', Translation::$APARTMENT_TEXTLINE_TYPES) . ')'
                ),
                [],
                $select::JOIN_LEFT
            )->join(
                ['apartel' => DbTables::TBL_APARTELS],
                new Expression(
                    $this->getTable() . '.entity_id = apartel.id AND ' .
                    $this->getTable() . '.entity_type IN (' . implode(', ', Translation::$APARTEL_TEXTLINE_TYPES) . ')'
                ),
                [],
                $select::JOIN_LEFT
            )->join(
                ['apartel_group' => DbTables::TBL_APARTMENT_GROUPS],
                'apartel.apartment_group_id = apartel_group.id',
                [],
                $select::JOIN_LEFT
            )->join(
                ['building' => DbTables::TBL_APARTMENT_GROUPS],
                new Expression(
                    $this->getTable() . '.entity_id = building.id AND ' .
                    $this->getTable() . '.entity_type IN (' . implode(', ', Translation::$BUILDING_TEXTLINE_TYPES) . ')'
                ),
                [],
                $select::JOIN_LEFT
            )->join(
                ['building_section' => DbTables::TBL_BUILDING_SECTIONS],
                new Expression(
                    $this->getTable() . '.entity_id = building_section.id AND ' .
                    $this->getTable() . '.entity_type =' . Translation::PRODUCT_TEXTLINE_TYPE_BUILDING_SECTION_APARTMENT_ENTRY
                ),
                [],
                $select::JOIN_LEFT
            )->join(
                ['building_for_section' => DbTables::TBL_APARTMENT_GROUPS],
                'building_section.building_id = building_for_section.id',
                [],
                $select::JOIN_LEFT
            )->join(
                ['office' => DbTables::TBL_OFFICES],
                new Expression(
                    $this->getTable() . '.entity_id = office.id AND ' .
                    $this->getTable() . '.entity_type IN (' . implode(', ', Translation::$OFFICE_TEXTLINE_TYPES) . ')'
                ),
                [],
                $select::JOIN_LEFT
            )->join(
                ['parking' => DbTables::TBL_PARKING_LOTS],
                new Expression(
                    $this->getTable() . '.entity_id = parking.id AND ' .
                    $this->getTable() . '.entity_type IN (' . implode(', ', Translation::$PARKING_TEXTLINE_TYPES) . ')'
                ),
                [],
                $select::JOIN_LEFT
            );

            $where = new Where();
            $textSearch = '%'. strip_tags(trim($filterParams["srch_txt"])).'%';
            if((int)$filterParams["id_translation"] > 0) {
                $where->equalTo($this->getTable().'.entity_id', $filterParams["id_translation"]);
            } else {

                if ( $filterParams["srch_txt"] != '' ) {
                    $where
                        ->NEST
                        ->like('apartment.name', $textSearch)
                        ->or
                        ->NEST
                        ->like('apartel_group.name', $textSearch)
                        ->or
                        ->NEST
                        ->like('building.name', $textSearch)
                        ->or
                        ->NEST
                        ->like('office.name', $textSearch)
                        ->or
                        ->like('parking.name', $textSearch)
                        ->or
                        ->like($this->getTable().'.en_html_clean', $textSearch)
                        ->UNNEST;
                }
            }

            if ((int)$filterParams['product_type']) {
                $where->equalTo($this->getTable().'.type', (int)$filterParams['product_type']);
            }

            $select->where($where);
    		$select->group($this->getTable().'.id');

            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
    	});

        $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $resultCount  = $statement->execute();
        $row = $resultCount->current();
        $total = $row['total'];

        return  [
            'result' => $result,
            'total'  => $total
        ];
    }

    public function getForTranslation($param)
    {
        $aptKI          = Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_DIRECT_ENTRY_KEY_INSTRUCTION;
        $aptDescription = Translation::PRODUCT_SHORT_TEASER_GENERAL_AND_DESCROPTION;

        $result = $this->fetchOne(function (Select $select) use($param, $aptKI, $aptDescription) {
            $select->columns([
                'id',
                'content' => 'en',
                'other' => 'entity_type',
                'type' => new Expression("
                    CASE
                        WHEN (apartel.id IS NOT NULL) THEN apartel_group.name
                        WHEN (building.id IS NOT NULL) THEN building.name
                        WHEN (apartment.id IS NOT NULL) THEN apartment.name
                        WHEN (office.id IS NOT NULL) THEN office.name
                        WHEN (parking.id IS NOT NULL) THEN parking.name
                        WHEN (building_section.id IS NOT NULL) THEN CONCAT(building_for_section.name, ' ', building_section.name)
                    END
                ")
            ]);
            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                new Expression(
                    $this->getTable() . '.entity_id = apartment.id AND ' .
                    $this->getTable() . '.entity_type IN (' . implode(', ', Translation::$APARTMENT_TEXTLINE_TYPES) . ')'
                ),
                [],
                $select::JOIN_LEFT
            )->join(
                ['apartel' => DbTables::TBL_APARTELS],
                new Expression(
                    $this->getTable() . '.entity_id = apartel.id AND ' .
                    $this->getTable() . '.entity_type IN (' . implode(', ', Translation::$APARTEL_TEXTLINE_TYPES) . ')'
                ),
                [],
                $select::JOIN_LEFT
            )->join(
                ['apartel_group' => DbTables::TBL_APARTMENT_GROUPS],
                'apartel.apartment_group_id = apartel_group.id',
                [],
                $select::JOIN_LEFT
            )->join(
                ['building' => DbTables::TBL_APARTMENT_GROUPS],
                new Expression(
                    $this->getTable() . '.entity_id = building.id AND ' .
                    $this->getTable() . '.entity_type IN (' . implode(', ', Translation::$BUILDING_TEXTLINE_TYPES) . ')'
                ),
                [],
                $select::JOIN_LEFT
            )->join(
                ['building_section' => DbTables::TBL_BUILDING_SECTIONS],
                new Expression(
                    $this->getTable() . '.entity_id = building_section.id AND ' .
                    $this->getTable() . '.entity_type =' . Translation::PRODUCT_TEXTLINE_TYPE_BUILDING_SECTION_APARTMENT_ENTRY
                ),
                [],
                $select::JOIN_LEFT
            )->join(
                ['building_for_section' => DbTables::TBL_APARTMENT_GROUPS],
                'building_section.building_id = building_for_section.id',
                [],
                $select::JOIN_LEFT
            )->join(
                ['office' => DbTables::TBL_OFFICES],
                new Expression(
                    $this->getTable() . '.entity_id = office.id AND ' .
                    $this->getTable() . '.entity_type IN (' . implode(', ', Translation::$OFFICE_TEXTLINE_TYPES) . ')'
                ),
                [],
                $select::JOIN_LEFT
            )->join(
                ['parking' => DbTables::TBL_PARKING_LOTS],
                new Expression(
                    $this->getTable() . '.entity_id = parking.id AND ' .
                    $this->getTable() . '.entity_type IN (' . implode(', ', Translation::$PARKING_TEXTLINE_TYPES) . ')'
                ),
                [],
                $select::JOIN_LEFT
            );

            $select->where
                   ->equalTo($this->getTable().'.id', $param['id']);
        });
        return $result;
    }
}
