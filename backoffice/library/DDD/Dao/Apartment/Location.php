<?php

namespace DDD\Dao\Apartment;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class Location extends TableGatewayManager
{
    protected $table = DbTables::TBL_APARTMENT_LOCATIONS;
    protected $textlineTable = DbTables::TBL_PRODUCT_TEXTLINES;

    public function __construct($sm, $domain = 'DDD\Domain\Apartment\Location\Location')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * Get apartment location information
     * @access public
     *
     * @param int $apartmentId
     * @return \DDD\Domain\Apartment\Location\Location
     */
    public function getApartmentLocation($apartmentId)
    {
    	return $this->fetchOne(function (Select $select) use($apartmentId) {
    		// fields from product_location table
    		$select->columns([
                'id' => 'id',
                'x_pos' => 'x_pos',
                'y_pos' => 'y_pos',
            ]);

    		// fields from general table
    		$select
                ->join(
                    ['general' => DbTables::TBL_APARTMENTS],
                    $this->table . '.apartment_id = general.id',
                    [
                        'country_id' 	=> 'country_id',
                        'province_id' 	=> 'province_id',
                        'city_id' 		=> 'city_id',
                        'address' 		=> 'address',
                        'postal_code' 	=> 'postal_code',
                        'building_id'	=> 'building_id',
                        'building_section_id' => 'building_section_id',
                        'block'	        => 'block',
                        'floor' => 'floor',
                        'unit_number' => 'unit_number',
                    ]
                )
                ->join(
                    ['groups' => DbTables::TBL_APARTMENT_GROUPS],
                    'general.building_id = groups.id',
                    [
                        'building' => 'name'
                    ],
                    Select::JOIN_LEFT
                )
            ;

    		$select->where([$this->table . '.apartment_id' => $apartmentId]);
    	});
    }
}
