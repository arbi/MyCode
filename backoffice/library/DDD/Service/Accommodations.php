<?php

namespace DDD\Service;

use DDD\Service\ServiceBase;
use Zend\Db\Sql\Where;
use Library\Constants\Constants;
use Library\Constants\Objects;
use Library\Constants\DbTables;
use Library\Constants\Roles;

class Accommodations extends ServiceBase
{
    const APARTMENT_STATUS_LIVE_AND_SELLING       = 5;
    const APARTMENT_STATUS_DISABLED               = 9;
    const APARTMENT_STATUS_SELLING_NOT_SEARCHABLE = 10;

    protected $accommodationsDao = FALSE;
    protected $serviceLocator;

    /*
     * Use in OmniBox Search
     *
     * @var DDD\Dao\Accommodation $dao
     */
    public function getAccommodationsJSON($query)
    {
        $dao = $this->getAccommodationsDao();
        $result = $dao->getAccommodations($query);

	    return $this->_toArray($result);
    }

    /**
     * Method to get products list with basic information to display in product search page
     *
     * @param array $filterParams
     * @param bool $testApartments
     * @return \DDD\Domain\Apartment\Search\Apartment[]|\ArrayObject
     */
    public function getProductSearchResult($filterParams = [], $testApartments = true) {
    	$dao = $this->getAccommodationsDao('DDD\Domain\Apartment\Search\Apartment');
    	$where = $this->constructWhereFromFilterParams($filterParams, $testApartments);

        return $dao->getAllAccommodations($where, $testApartments);
    }

    /**
     * Construct Where object from query parameters
     *
     * @param array $filterParams
     * @param bool $testApartments
     * @return Where
     */
    public function constructWhereFromFilterParams($filterParams, $testApartments = true)
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $hasDevTestRole = $auth->hasRole(Roles::ROLE_DEVELOPMENT_TESTING);
    	$where = new Where();
    	$table = DbTables::TBL_APARTMENTS;

    	$productStatusGroups = Objects::getProductStatusGroups();

    	if (!$testApartments || !$hasDevTestRole) {
    		$where->expression(
                $table.'.id NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );
    	}

    	if (isset($filterParams["status"]) && $filterParams["status"] != '0') {
    		$statusGroup = $productStatusGroups[$filterParams["status"]];

    		$where->in($table . ".status", $statusGroup);
    	}

    	if (isset($filterParams["building_id"]) && $filterParams["building_id"] != '0') {
    		$where->expression(
                $table . '.id IN (SELECT `apartment_id` FROM ' .
                DbTables::TBL_APARTMENT_GROUP_ITEMS . ' JOIN ' .
                DbTables::TBL_APARTMENT_GROUPS . ' ON `apartment_group_id` = ' .
                DbTables::TBL_APARTMENT_GROUPS . '.id WHERE ' .
                DbTables::TBL_APARTMENT_GROUPS . '.id = ' . $filterParams['building_id'] . ' ) ',
                []
            );
    	}

    	if (isset($filterParams["address"]) && $filterParams["address"] != '' ) {
    		$addressQuery = $filterParams["address"];

            $nestedWhere = new Where();

    		$nestedWhere
				->like($table . '.name', '%' . $addressQuery . '%')
				->or
				->like('det1.name', '%' . $addressQuery . '%')
				->or
				->like('det2.name', '%' . $addressQuery . '%')
				->or
				->like($table . '.address', '%' . $addressQuery . '%')
				->or
				->like($table . '.unit_number', '%' . $addressQuery . '%')
				->or
				->like($table . '.postal_code', '%' . $addressQuery . '%');
            $where->addPredicate($nestedWhere);
    	}

        if (isset($filterParams['createdDate']) && $filterParams['createdDate'] !== '') {
            $createdDate = explode(' - ', $filterParams['createdDate']);

            $where->between($table.'.create_date', $createdDate['0'], $createdDate['1']);
        }

    	return $where;
    }

    /**
     * Set this method as source for product search autocomplete elements
     *
     * @param string $query
     * @param bool $building
     * @param int $mode
     *
     * @return array
     */
    public function getProductsForAutocomplete($query, $building = false, $mode = 0)
    {
    	/*
    	 * @var DDD\Dao\Accommodation\Accommodations
    	 */
    	$dao = $this->getAccommodationsDao('DDD\Domain\Accommodation\ProductAutocomplete');
    	$result = $dao->getForAutocomplete($query, $building, $mode);

    	$autocompleteArray = ['rc' => '00', 'result' => []];
    	$data = [];

    	foreach ($result as $key => $item) {
    		$data[$key]['id'] = $item->getId();
    		$data[$key]['name'] = $item->getName() . ' - ' . $item->getCityName() . ' - ' . $item->getUnitNumber();

            if ($building) {
                $data[$key]['apartmentGroup'] = $item->getApartmentGroup();
                $data[$key]['buildingId'] = $item->getBuildingId();
            }
    	}

    	$autocompleteArray['result'] = $data;

    	return $autocompleteArray;
    }

    /**
     * Set this method as source for product "search by full address" autocomplete elements
     *
     * @param string $query
     * @param int $mode
     * @return array
     */
    public function getProductsByFullAddress($query, $mode = 0)
    {
    	/*
    	 * @var DDD\Dao\Accommodation\Accommodations
    	*/
    	$dao = $this->getAccommodationsDao('DDD\Domain\Accommodation\ProductFullAddress');
    	$result = $dao->findByFullAddress($query, $mode);

    	$autocompleteArray = ['rc' => '00', 'result' => []];
    	$data = [];

    	foreach ($result as $key => $item) {
    		$data[$key]['id'] = $item->getId();
    		$data[$key]['name'] = $item->getFullAddress();
    	}

    	$autocompleteArray['result'] = $data;

    	return $autocompleteArray;
    }

    /**
     * Prepare resources needed for product search form
     * @access public
     *
     * @return array
     */
    public function prepareApartmentSearchFormResources()
    {
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        $hasDevTestRole = $auth->hasRole(Roles::ROLE_DEVELOPMENT_TESTING);
    	$conciergeDao   = $this->getConciergeDao();
        $concierges     = $conciergeDao->prepareFormResources($hasDevTestRole);

        return [
    		"concierges" => $concierges
    	];
    }

    public function getAppartmentFullAddressByID($id) {
    	$appartmentDao = $this->getAccommodationsDao('DDD\Domain\Accommodation\ProductFullAddress');

    	return $appartmentDao->getAppartmentFullAddressByID($id);
    }

    /**
     * Calculate and update product quality score based on reviews
     *
     * @param int $apartmentId
     * @return bool
     */
    public function updateProductReviewScore($apartmentId)
    {
        /**
         * @var \DDD\Dao\Accommodation\Review $accommodationReviewDao
         */
        $accommodationReviewDao = $this->getServiceLocator()->get('dao_accommodation_review');
        $accommodationReviewDao->setEntity(new \ArrayObject());

    	$productDao = $this->getAccommodationsDao();

        $reviews = $accommodationReviewDao->getProductReviews($apartmentId);

        if ($reviews) {
             $productDao->save(['score' => round($reviews['avgScore'], 1)], ['id' => $apartmentId]);

             return true;
        }

        return false;
    }

    /**
     * Calculate product completness percent based on many factors
     *
     * @param int $productID
     * @return int
     */
    public function calculateProductCompletnessPercent($productID) {
    	$completnessPercent = 0;

    	/**
    	 * @todo
    	 * check images count
    	 * check description texts, textlines
    	 * check locations
    	 * check key instruction video link
    	 * check product types, rates
    	 */

    	return $completnessPercent;
    }

    /**
     * Get concierge DAO object initialized via service locator
     * @access private
     *
     * @return \DDD\Dao\ApartmentGroup\ApartmentGroup
     */
    private function getConciergeDao() {
    	return $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
    }

	private function _toArray($data) {
		$out = [];

		if (count($data)) {
			foreach ($data as $item) {
				array_push($out, [
					'id' => $item->getId(),
					'text' => $item->getName(),
				]);
			}
		}

		return $out;
	}

    public function getOccupancyStatistics($queryParams)
    {
        $dao            = $this->getAccommodationsDao('DDD\Domain\Accommodation\Statistics');
        $auth           = $this->getServiceLocator()->get('library_backoffice_auth');
        $hasDevTestRole = $auth->hasRole(Roles::ROLE_DEVELOPMENT_TESTING);

        $searchDate  = $queryParams['starting_form'];
        $searchDate  = explode('_', $searchDate);
        list($y, $m) = $searchDate;

        $startDate = date('Y-m-01', strtotime($y . '-' . $m . '-01'));

        if ($m >= 11) {
            $endDate = date("Y-m-d", strtotime('last day of ' . ($y + 1) . '-' . ($m - 10) . '-01'));
        } else {
            $endDate = date("Y-m-d", strtotime('last day of ' . $y . '-' . ($m + 2) . '-01'));
        }

        $result = $dao->getOccupancyStatistics(
            $queryParams,
            $startDate,
            $endDate,
            $hasDevTestRole
        );
        $statistics  = $monthList = $prodAv = [];
	    $totalUnsold = 0;

        foreach ($result as $row){
            $av = 0;
            if (!in_array($row->getMonth_name(), $monthList)) {
               $monthList[] = $row->getMonth_name();
            }
            if (array_key_exists ( $row->getId(), $statistics)) {
                if (isset($monthList[2])) {
                    if ($row->getAvailability() == 0) {
                        $prodAv[$row->getId()]['m3']++;
                    }

                    $av = $prodAv[$row->getId()]['m3'];
                } elseif (isset($monthList[1])) {
                    if ($row->getAvailability() == 0) {
                        $prodAv[$row->getId()]['m2']++;
                    }

                    $av = $prodAv[$row->getId()]['m2'];
                } else {
                    if ($row->getAvailability() == 0) {
                        $prodAv[$row->getId()]['m1']++;
                    }

                    $av = $prodAv[$row->getId()]['m1'];
                }

                $statistics[$row->getId()][$row->getMonth_name()] = round($av / $row->getDay_count() * 100);
            } else {
                $statistics[$row->getId()]['id']          = $row->getId();
                $statistics[$row->getId()]['name']        = $row->getName();
                $statistics[$row->getId()]['city_name']   = $row->getCity_name();
                $statistics[$row->getId()]['building']    = $row->getBuilding();
                $statistics[$row->getId()]['bedrooms']    = $row->getBedrooms();
                $statistics[$row->getId()]['pax']         = $row->getPax();
                $statistics[$row->getId()]['unsold_days'] = $this->unsoldDays($row->getId());

                if ($row->getAvailability() == 0) {
                   $prodAv[$row->getId()] = ['m1' => 1, 'm2' => 0, 'm3' => 0];
                } else {
                   $prodAv[$row->getId()] = ['m1' => 0, 'm2' => 0, 'm3' => 0];
                }

                $statistics[$row->getId()][$row->getMonth_name()] = round($prodAv[$row->getId()]['m1'] / $row->getDay_count() * 100);
	            $totalUnsold += $this->unsoldDays($row->getId());
            }
        }

        return [
            'statistics'  => $statistics,
            'monthList'   => $monthList,
            'totalUnsold' => $totalUnsold,
        ];
    }

    private function unsoldDays($id)
    {
        $dao = new \DDD\Dao\Accommodation\RateAv($this->getServiceLocator(), 'DDD\Domain\Count');
        $result = $dao->unsoldDays($id);

        if ($result) {
            return $result->getCount();
        }

        return 0;
    }

    private function getAccommodationsDao($domain = 'DDD\Domain\Accommodation\Accommodations')
    {
        if (!$this->accommodationsDao || get_class($this->accommodationsDao) !== $domain) {
            $this->accommodationsDao = new \DDD\Dao\Accommodation\Accommodations($this->getServiceLocator(), $domain);
        }

        return $this->accommodationsDao;
    }
}
