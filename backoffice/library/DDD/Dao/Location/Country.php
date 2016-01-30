<?php
namespace DDD\Dao\Location;

use DDD\Dao\Location\LocationDaoAbstract;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;

/**
 * Country DAO class
 * @package core
 * @subpackage core_dao
 *
 * @author Tigran Petrosyan
 */
class Country extends LocationDaoAbstract
{
	/**
	 * 
	 * @param unknown $sm
	 * @param string $domain
	 */
    public function __construct($sm, $domain = 'DDD\Domain\Location\Country')
    {
        parent::__construct($sm, $domain, DbTables::TBL_COUNTRIES, DbTables::TBL_CONTINENTS, 'continent_id', DbTables::TBL_PROVINCES);
    }

	/**
	 * @param string $isoCode
	 * @return \DDD\Domain\Location\Country
	 */
	public function getByISOCode($isoCode) {
		return $this->fetchOne(function (Select $select) use ($isoCode) {
			$select->columns(['id']);

			$select->join(
				['geo' => DbTables::TBL_LOCATION_DETAILS],
				$this->getTable() . '.detail_id = geo.id',
				['name']
			);

			$select->where(['geo.iso' => $isoCode]);
		});
	}

	/**
	 * @param string $isoCode
	 * @return \DDD\Domain\Location\Country
	 */
	public function getByISOAlpha3Code($isoCode) {
		return $this->fetchOne(function (Select $select) use ($isoCode) {
			$select->columns(['id']);

			$select->join(
				['geo' => DbTables::TBL_LOCATION_DETAILS],
				$this->getTable() . '.detail_id = geo.id',
				['name']
			);

			$select->where(['geo.iso_alpha_3' => $isoCode]);
		});
	}

	/**
	 * @param string $isoCode
	 * @return \DDD\Domain\Location\Country
	 */
	public function getDetailsByCountryId($countryId) {
		return $this->fetchOne(function (Select $select) use ($countryId) {
			
            $select->columns([
                'id',
                'details_id' => 'detail_id'
                ]);
            
            $select->join(
				['geo' => DbTables::TBL_LOCATION_DETAILS],
				$this->getTable() . '.detail_id = geo.id',
				['iso']
			);
            
			$select->where
                    ->equalTo($this->getTable().'.id', $countryId);
		});
	}

    /**
     * @return \DDD\Domain\Location\Country[]
     */
    public function getCountriesWithChildrenCount()
    {
        $previousEntity = $this->getResultSetPrototype()->getArrayObjectPrototype();
        $this->getResultSetPrototype()->setArrayObjectPrototype(new \DDD\Domain\Location\Country());

    	$result = $this->fetchAll(
            function (Select $select) {
                $select
                    ->columns($this->mainColumns)
                    ->join(
                        ['details' => $this->detailsTable],
                        $this->table . '.detail_id = details.id',
                        $this->detailsColumns
                    )
                    ->join(
                        ['children_table' => $this->childTable],
                        $this->table.'.id = children_table.country_id',
                        [
                            'children_count' => new Expression('COUNT(*)'),
                            'child_id' => 'id'
                        ],
                        Select::JOIN_LEFT
                    )
                    ->group($this->table . '.id')
                    ->order(['name ASC']);
            }
    	);

        $this->getResultSetPrototype()->setArrayObjectPrototype($previousEntity);

    	return $result;
    }
    
    /**
     * 
     * @return ArrayObject
     */
    public function getCountriesWithCurrecny() 
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
		return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id']);
            $select->join(
				['currency' => DbTables::TBL_CURRENCY],
				$this->getTable() . '.currency_id = currency.id',
				['code'],
                $select::JOIN_LEFT    
			);
		});
    }

	public function getCountryWithPhoneCodes()
	{
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'phone_code'
            ]);

            $select->join(
                ['location_details' => DbTables::TBL_LOCATION_DETAILS],
                $this->getTable() . '.detail_id = location_details.id',
                ['name'],
                Select::JOIN_LEFT
            );
        });

        return $result;
	}
}
