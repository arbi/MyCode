<?php

namespace DDD\Service\Booking;

use DDD\Dao\Booking\Partner;
use DDD\Dao\Partners\Partners;
use DDD\Service\Booking;
use DDD\Service\ServiceBase;
use Library\Constants\Roles;
use Library\Utility\Debug;
use Zend\Db\Sql\Where;
use Library\Constants\DbTables;
use Library\Constants\Constants;
use Zend\Db\Sql\Predicate\Predicate;

/**
 *
 * @author Tigran Petrosyan
 */
class BookingManagement extends ServiceBase
{
	public function getReservationsBasicInfo(
		$iDisplayStart 	= null,
		$iDisplayLength = null,
		$filterParams 	= [],
		$sortCol 		= 0,
		$sortDir 		= 'ASC'
	) {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

		foreach ($filterParams as $key => $row) {
			if (!is_array($row)) {
				$filterParams[$key] = trim($row);
			}
		}

		$where = $this->constructWhereFromFilterParams($filterParams);
		$params = [];

		if (isset($filterParams["transaction_amount"]) && $filterParams["transaction_amount"] != '') {
			$params['transaction_amount'] = $filterParams["transaction_amount"];
		}

		if (isset($filterParams["charge_auth_number"]) && $filterParams["charge_auth_number"] != '') {
			$params['charge_auth_number'] = $filterParams["charge_auth_number"];
		}

		if (isset($filterParams["group_id"]) && $filterParams["group_id"] != '') {
			$params['group_id'] = $filterParams["group_id"];
		}

		if (isset($filterParams["comment"]) && $filterParams["comment"]) {
			$params['comment'] = $filterParams["comment"];
		}

		$reservations = $bookingDao->getReservationsBasicInfo(
			$iDisplayStart,
			$iDisplayLength,
			$where,
			$sortCol,
			$sortDir,
			$params
		);

		return $reservations;
	}

	public function constructWhereFromFilterParams($filterParams, $download = false) {
		/* @var $auth \Library\Authentication\BackofficeAuthenticationService */
		$auth = $this->getServiceLocator()->get('library_backoffice_auth');

		$where = new Where();
		$table = DbTables::TBL_BOOKINGS;

		if (isset($filterParams["res_number"]) && $filterParams["res_number"] != '') {
			$where->like(
				$table.'.res_number',
				'%'.$filterParams["res_number"].'%'
			);
		}

		if (isset($filterParams["rooms_count"])) {
            $apartmentTableName = ($download) ? DbTables::TBL_APARTMENTS : 'apartments';
			$roomsCount = $filterParams["rooms_count"] - 1;
			if ($roomsCount >= 0) {
				if ($roomsCount == 2) {
					$where->greaterThanOrEqualTo($apartmentTableName . '.bedroom_count', $roomsCount);
				} else {
					$where->equalTo($apartmentTableName . '.bedroom_count', $roomsCount);
				}
			}
		}

		if (isset($filterParams["status"]) && $filterParams["status"]) {
			if ($filterParams["status"] == Constants::NOT_BOOKED_STATUS) {
				$where->notEqualTo($table.'.status', Booking::BOOKING_STATUS_BOOKED);
            } else {
				$where->equalTo($table.'.status', $filterParams["status"]);
			}
		}

        if (isset($filterParams["arrival_status"]) && $filterParams['arrival_status'] != -1) {
            $where->equalTo($table . '.arrival_status', $filterParams["arrival_status"]);
        }

        if (isset($filterParams["overbooking_status"]) && $filterParams["overbooking_status"] != -1) {
            $where->equalTo($table . '.overbooking_status', $filterParams["overbooking_status"]);
        }

		if (isset($filterParams["apartel_id"]) && $filterParams["apartel_id"] != -2) {
			$where->equalTo(
				$table . '.apartel_id',
				$filterParams["apartel_id"]
			);
		}

		if (isset($filterParams["product_id"])
            && isset($filterParams["product"])
            && ($filterParams["product_id"] != '')
			&& ($filterParams["product"] != '')
		) {
			$where->equalTo(
				$table.'.apartment_id_origin',
				$filterParams["product_id"]
			);
		}

		if (isset($filterParams["assigned_product_id"])
            && isset($filterParams["assigned_product"])
            && ($filterParams["assigned_product_id"] != '')
			&& ($filterParams["assigned_product"] != '')
		) {
			$where->equalTo(
				$table.'.apartment_id_assigned',
				$filterParams["assigned_product_id"]
			);
		}

		if (isset($filterParams["booking_date"]) && $filterParams["booking_date"] != '') {
			$dates 	   = explode(' - ', $filterParams["booking_date"]);
			$startDate = $dates[0];
			$endDate   = $dates[1];

			$where->expression(
				'DATE('.$table.'.timestamp) >= \'' . $startDate . '\'',
				[]
			);

			$where->expression(
				'DATE('.$table.'.timestamp) <= \'' . $endDate . '\'',
				[]
			);
		}

		if (isset($filterParams["arrival_date"]) && $filterParams["arrival_date"] != '') {
			$dates = explode(' - ', $filterParams["arrival_date"]);
			$startDate = $dates[0];
			$endDate = $dates[1];
			$where->lessThanOrEqualTo($table . ".date_from", $endDate);
			$where->greaterThanOrEqualTo($table . ".date_from", $startDate);
		}

		if (isset($filterParams["departure_date"]) && $filterParams["departure_date"] != '') {
			$dates = explode(' - ', $filterParams["departure_date"]);
			$startDate = $dates[0];
			$endDate = $dates[1];
			$where->lessThanOrEqualTo($table . ".date_to", $endDate);
			$where->greaterThanOrEqualTo($table . ".date_to", $startDate);
		}

		if (isset($filterParams["guest_first_name"]) && $filterParams["guest_first_name"] != '') {

            $where->like(
            	$table.'.guest_first_name',
            	'%'.$filterParams["guest_first_name"].'%'
            );
		}

		if (isset($filterParams["guest_last_name"]) && $filterParams["guest_last_name"] != '') {
            $where->like(
            	$table.'.guest_last_name',
            	'%'.$filterParams["guest_last_name"].'%'
            );
		}

		if (isset($filterParams["guest_phone"]) && $filterParams["guest_phone"] != '') {
			$nestedWhere = new Predicate();
			$nestedWhere->like(
				$table.'.guest_phone',
				'%'.$filterParams["guest_phone"].'%'
			);
			$nestedWhere->OR;
			$nestedWhere->like(
				$table.'.guest_travel_phone',
				'%'.$filterParams["guest_phone"].'%'
			);

			$where->addPredicate($nestedWhere);
		}

        if (isset($filterParams["guest_email"]) && $filterParams["guest_email"] != '' ) {
			$where->like($table . '.guest_email', '%' . $filterParams["guest_email"] . '%');
        }

        if (isset($filterParams["guest_secondary_email"]) && $filterParams["guest_secondary_email"] != '' ) {
            $where->like($table.'.secondary_email', '%'.$filterParams["guest_secondary_email"].'%');
        }

		if (isset($filterParams["guest_country_id"])
            && ($filterParams["guest_country_id"] != '')
			&& ($filterParams["guest_country"] != '')
		) {
			$where->equalTo(
				$table.'.guest_country_id',
				$filterParams["guest_country_id"]
			);
		}

		if (isset($filterParams["apt_location_id"])
            && ($filterParams["apt_location_id"] != '')
		) {
            $nestedWhere = new Predicate();
			$nestedWhere->equalTo(
				$table.'.acc_country_id',
				$filterParams["apt_location_id"]
			);

			$nestedWhere->OR;
			$nestedWhere->equalTo(
				$table.'.acc_city_id',
				$filterParams["apt_location_id"]
			);

			$where->addPredicate($nestedWhere);
		}

		if (isset($filterParams["partner_id"]) && $filterParams["partner_id"] != '0') {
			$where->equalTo(
				$table.'.partner_id',
				$filterParams["partner_id"]
			);
		}

		if (isset($filterParams["partner_reference"]) && $filterParams["partner_reference"] != '') {
			$where->like(
				$table.'.partner_ref',
				'%'.$filterParams["partner_reference"].'%'
			);
		}

		if (isset($filterParams["payment_model"]) && $filterParams["payment_model"] != '0') {
			$where->equalTo($table.'.model', $filterParams["payment_model"]);
		}

		if (!$auth->hasRole(Roles::ROLE_DEVELOPMENT_TESTING)) {
			$where->expression(
				$table.'.apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
				[]
            );
		}

		if (isset($filterParams["no_collection"]) && $filterParams["no_collection"] > 1) {
			$where->equalTo(
				$table.'.no_collection',
				$filterParams["no_collection"] == 2 ? 1 : 0
			);
		}

		if (isset($filterParams["channel_res_id"]) && $filterParams["channel_res_id"]) {
			$where->equalTo(
				$table.'.channel_res_id',
                $filterParams["channel_res_id"]
			);
		}

		return $where;
	}

	public function validateDownloadCsv($filterParams)
	{
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingExportRow());

		$where  = $this->constructWhereFromFilterParams($filterParams, true);
        $params = null;

		if ( $filterParams['group_id'] != '' ) {
			$params['group'] = $filterParams['group_id'];
		}

        if ( $filterParams["comment"] ) {
            $params['comment'] = $filterParams["comment"];
        }

		$response = $bookingDao->validateDownloadCsv($where, $params);

		if ((int)$response['count'] > Constants::MAX_ROW_COUNT) {
			return false;
		}

		return true;
	}
	/**
	 * Method to get reservations to export in CSV
	 *
	 * @param int|null $iDisplayStart
	 * @param int|null $iDisplayLength
	 * @param array $filterParams
	 * @param int $sortCol
	 * @param string $sortDir
	 *
	 * @return \DDD\Domain\Booking\BookingExportRow[]
	 *
	 * @author Tigran Petrosyan
	 */
	public function getReservationsToExport(
        $iDisplayStart  = null,
        $iDisplayLength = null,
        $filterParams   = [],
        $sortCol        = 0,
        $sortDir        = 'ASC'
    ) {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingExportRow());

		$where  = $this->constructWhereFromFilterParams($filterParams, true);
        $params = null;

		if ( $filterParams['group_id'] != '' ) {
			$params['group'] = $filterParams['group_id'];
		}

        if ( $filterParams["comment"] ) {
            $params['comment'] = $filterParams["comment"];
        }

		$reservations = $bookingDao->getReservationsToExport(
            $iDisplayStart,
            $iDisplayLength,
            $where,
            $sortCol,
            $sortDir,
            $params
        );

		return $reservations;
	}

    /**
	 * Prepare resources needed for booking search form
	 *
	 * @return array
	 */
	public function prepareSearchFormResources() {
        /**
         * @var Partners $partnerDao
         */
        $apartmentGroupsDao = new \DDD\Dao\ApartmentGroup\ApartmentGroup($this->getServiceLocator(), 'DDD\Domain\ApartmentGroup\ForSelect');
        $partnerDao = $this->getServiceLocator()->get('dao_partners_partners');

        $partners = $partnerDao->getPartnersForSelect();
        $groups = $apartmentGroupsDao->getAllGroups();
        $apartels = $apartmentGroupsDao->getApartelList();

		return array(
			'partners' => $partners,
            'groups' => $groups,
            'apartels' => $apartels
		);
	}
}
