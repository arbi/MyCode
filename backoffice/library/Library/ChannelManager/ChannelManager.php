<?php

namespace Library\ChannelManager;

use DDD\Dao\Apartment\Inventory;
use Library\Utility\Debug;

/**
 * No words, just ChannelManager
 *
 * Class ChannelManager
 * @package Library\ChannelManager
 * @todo: Parsers and Generators must have own directories
 * @todo: Extend basic \Exception() class
 * @todo: Add validation for beautifier
 */
class ChannelManager extends ChannelManagerBase
{
    const SYNC_WITH_PRODUCT = 'product';
    const SYNC_WITH_TYPE = 'type';
    const SYNC_WITH_RATE = 'rate';

	public function availabilityToggleFromCalendar($apartmentId, $date, $availability)
    {
		try {
			$inventoryDao = new Inventory($this->getServiceLocator(), 'DDD\Domain\Apartment\Inventory\RateAvailabilityComplete');
			$inventoryDomain = $inventoryDao->getRateAvailabilityByApartmentId($apartmentId, $date, $date);

			if ($inventoryDomain) {
				$reqRates = [];

				foreach ($inventoryDomain as $rate) {
					$reqRates[] = [
						'rate_id' => $rate->getCubilisRateId(),
						'room_id' => $rate->getCubilisRoomId(),
						'avail' => $availability,
						'date' => $rate->getDate(),
					];
				}

				return $this->syncCollection($apartmentId, $reqRates);
			} else {
				throw new \Exception('Cannot get rates by apartment_id.');
			}
		} catch (\Exception $ex) {
            $this->gr2logException($ex, 'Channel Manager: Availability Toggle From Calendar Failed', [
				'apartment_id' => $apartmentId,
				'date'		   => $date,
				'availability' => $availability
			]);

			$response = new \stdClass();
			$response->status = CivilResponder::STATUS_ERROR;
			$response->code = $ex->getCode();
			$response->message = $ex->getMessage();

			$response = ['ChannelManager' => $response];
		}

		return $this->civilResponseForCivilPeople($response);
	}

	/**
	 * @param array $params
	 *
	 * @return CivilResponder
	 * @throws \Exception
	 */
	public function cronCheckReservation(array $params)
    {
		try {
			$newParams = [];

			if (count($params)) {
				if (isset($params['apartment_id'])) {
                    $apartmentId = $params['apartment_id'];
				} else {
					throw new \Exception('Parameter [Apartment ID] is not defined.');
				}

				$params = $params['data'];

				if (count($params)) {
					if (isset($params['res_id'])) {
						$newParams['reservation'] = $params['res_id'];
					} elseif (isset($params['date'])) {
						$newParams['date'] = $params['date'];
					} else {
						throw new \Exception('Abnormal data detected.');
					}
				} else {
					$newParams['type'] = ChannelManager::RESERVATION_TYPE_STANDARD;
				}
			} else {
				throw new \Exception('Empty array provided.');
			}

			$response = $this->checkReservation([
				'default' => $newParams,
				'apartment_id' => $apartmentId,
			]);
		} catch (\Exception $ex) {
            $this->gr2logException($ex, 'Channel Manager: Check reservation Failed', $params);

			$response = new \stdClass();

			$response->status = CivilResponder::STATUS_ERROR;
			$response->code = $ex->getCode();
			$response->message = $ex->getMessage();

			$response = ['ChannelManager' => $response];
		}

		return $this->civilResponseForCivilPeople($response);
	}

	/**
	 * @param array $params
	 *
	 * @return CivilResponder
	 * @throws \Exception
	 */
	public function updateAvailability(array $params)
    {
		try {
			if (count($params)) {
				if (isset($params['apartment_id'])) {
                    $apartmentId = $params['apartment_id'];
				} else {
					throw new \Exception('Parameter [Apartment ID] is not defined.');
				}

				$newParams = [];
				$params = $params['data'];

				if (count($params)) {
					foreach ($params as $param) {
						$newParamsRow = [];

						if (isset($param['date'])) {
							$newParamsRow['date_start'] = $param['date'];
							$newParamsRow['date_end'] = date('Y-m-d', strtotime('+1 day', strtotime($param['date'])));
						} else {
							throw new \Exception('Parameter [Date] is not defined.');
						}

						if (isset($param['rate_id'])) {
							$newParamsRow['rate_id'] = $param['rate_id'];
						} else {
							throw new \Exception('Parameter [Rate] is not defined.');
						}

						if (isset($param['room_id'])) {
							$newParamsRow['room_id'] = $param['room_id'];
						} else {
							throw new \Exception('Parameter [Room] is not defined.');
						}

						if (isset($param['avail'])) {
							$newParamsRow['availability'] = $param['avail'];
						} else {
							throw new \Exception('Parameter [Availability] is not defined.');
						}

						$newParamsRow['status'] = ChannelManager::STATUS_OPEN;
						$newParams[] = $newParamsRow;
					}

					$response = $this->updateRate([
						'default' => $newParams,
						'apartment_id' => $apartmentId,
					]);
				} else {
					throw new \Exception('Empty array provided.');
				}
			} else {
				throw new \Exception('Empty array provided.');
			}
		} catch (\Exception $ex) {
            $this->gr2logException($ex, 'Channel Manager: Update Availability Failed', $params);

			$response = new \stdClass();

			$response->status = CivilResponder::STATUS_ERROR;
			$response->code = $ex->getCode();
			$response->message = $ex->getMessage();

			$response = ['ChannelManager' => $response];
		}

		return $this->civilResponseForCivilPeople($response);
	}

	/**
	 * @param array $params
	 *
	 * @return CivilResponder
	 * @throws \Exception
	 */
	public function sendConfirmation(array $params)
    {
		try {
			if (count($params)) {
				if (isset($params['apartment_id'])) {
                    $apartmentId = $params['apartment_id'];
				} else {
					throw new \Exception('Parameter [Apartment ID] is not defined.');
				}

				$params = $params['data'];

				foreach ($params as $param) {
					if (!isset($param['res_id'])) {
						throw new \Exception('Parameter [Reservation Id] is not defined.');
					}
				}

				$response = $this->confirm([
					'default' => $params,
					'apartment_id' => $apartmentId,
				]);
			} else {
				throw new \Exception('Empty array provided.');
			}
		} catch (\Exception $ex) {
            $this->gr2logException($ex, 'Channel Manager: Send Confirmation Failed', $params);

			$response = new \stdClass();

			$response->status = CivilResponder::STATUS_ERROR;
			$response->code = $ex->getCode();
			$response->message = $ex->getMessage();

			$response = ['ChannelManager' => $response];
		}

		return $this->civilResponseForCivilPeople($response);
	}

	public function sendXMLDirectly($apartmentId, $xml)
    {
		try {
			if (!is_numeric($apartmentId) || $apartmentId <= 0) {
				throw new \Exception('Bad apartment id provided.');
			}

			if (strlen(trim($xml)) < 10) {
				throw new \Exception('Bad xml provided.');
			}

			$response = $this->sendRaw($apartmentId, $xml);
		} catch (\Exception $ex) {
            $this->gr2logException($ex, 'Channel Manager: Send XML Directly Failed', [
				'apartment_id' => $apartmentId,
				'xml'		   => $xml
			]);

			$response = new \stdClass();

			$response->status = CivilResponder::STATUS_ERROR;
			$response->code = $ex->getCode();
			$response->message = $ex->getMessage();

			$response = ['ChannelManager' => $response];
		}

		return $this->civilResponseForCivilPeople($response);
	}

	/**
	 * @param $params
	 *
	 * @return CivilResponder
	 * @throws \Exception
	 */
	public function syncWithCubilis(array $params)
    {
		$response = new \stdClass();

		try {
			$response = $this->checkRate($params);
		} catch (\Exception $ex) {
            $this->gr2logException($ex, 'Channel Manager: Synchronization with Cubilis Failed', $params);

			$response->status = CivilResponder::STATUS_ERROR;
			$response->code = $ex->getCode();
			$response->message = $ex->getMessage();

			$response = ['ChannelManager' => $response];
		}

		return $this->civilResponseForCivilPeople($response);
	}


    /**
     * @param $itemId
     * @param null $dateFrom
     * @param null $dateTo
     * @param string $type
     * @return CivilResponder
     */
    public function syncAll($itemId, $dateFrom = null, $dateTo = null, $type = self::SYNC_WITH_PRODUCT)
    {
		$response = new \stdClass();

		try {
            /**
             * @var \DDD\Dao\Apartel\Inventory $apartelInventoryDao
             */

            if ($this->getProductType() == self::PRODUCT_APARTEL) {
                $apartelInventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');
                $inventoryDomain = $apartelInventoryDao->getRateAvailabilityByApartelId($itemId, $dateFrom, $dateTo, $type);
            } else {
                $inventoryDao = new Inventory($this->getServiceLocator(), 'DDD\Domain\Apartment\Inventory\RateAvailabilityComplete');
                $inventoryDomain = $inventoryDao->getRateAvailabilityByApartmentId($itemId, $dateFrom, $dateTo, $type);
            }

			if ($inventoryDomain) {
				$reqRates = $chunkedReqRates = $resultStatuses = [];
                $apartmentId = 0;
				foreach ($inventoryDomain as $rate) {
                    if (!$apartmentId) {
                        $apartmentId = $rate->getProductId();
                    }
					$reqRates[] = [
						'rate_id' => $rate->getCubilisRateId(),
						'room_id' => $rate->getCubilisRoomId(),
						'avail' => $rate->getAvailability(),
						'capacity' => $rate->getCapacity(),
						'price' => $rate->getPrice(),
						'min_stay' => $rate->getMinStay(),
						'max_stay' => $rate->getMaxStay(),
						'date' => $rate->getDate(),
					];
				}

				if (count($reqRates) > 50) {
					$chunkedReqRates = array_chunk($reqRates, 50);
				} else {
					$chunkedReqRates[] = $reqRates;
				}

				foreach ($chunkedReqRates as $ratesCollection) {

					$result = $this->syncCollection($apartmentId, $ratesCollection);
					if ($result->getStatus() == CivilResponder::STATUS_ERROR) {
						break;
					}

					$resultStatuses[] = $result;
				}

				if (count($chunkedReqRates) == count($resultStatuses)) {
					$response->code = 0;
					$response->status = CivilResponder::STATUS_SUCCESS;
					$response->message = 'Successfully synced.';
				} else {
					$response->code = 1;
					$response->status = CivilResponder::STATUS_ERROR;
					$response->message = (count($resultStatuses) . ' out of ' . count($chunkedReqRates) . ' fail. Last error message: ' . $result->getMessage() . '.');
					$response->data = $resultStatuses;
				}

				$response = ['Cubilis' => $response];
			} else {
				throw new \Exception('Nothing found to sync.');
			}
		} catch (\Exception $ex) {
            $this->gr2logException($ex, 'Channel Manager: Synchronization All Failed', [
				'item_id' 	=> $itemId,
				'date_from' => $dateFrom,
				'date_to'	=> $dateTo,
				'type'		=> $type
			]);

			$response->status = CivilResponder::STATUS_ERROR;
			$response->code = $ex->getCode();
			$response->message = $ex->getMessage();

			$response = ['ChannelManager' => $response];
		}
		return $this->civilResponseForCivilPeople($response);
	}

    /**
     * @param $apartmentId
     * @param $ratesCollection
     * @return CivilResponder
     */
    public function syncCollection($apartmentId, $ratesCollection)
    {
		try {
            if (count($ratesCollection)) {
				$newParams = [];

				foreach ($ratesCollection as $collectionItem) {
					$newParamsRow = [];

					if (isset($collectionItem['date'])) {
						$newParamsRow['date_start'] = $collectionItem['date'];
						$newParamsRow['date_end'] = date('Y-m-d', strtotime('+1 day', strtotime($collectionItem['date'])));
					}

					if (isset($collectionItem['price'])) {
						$newParamsRow['price'] = [];
						$newParamsRow['price']['standard'] = $collectionItem['price'];
					}

					if (isset($collectionItem['rate_id'])) {
						$newParamsRow['rate_id'] = $collectionItem['rate_id'];
					}

					if (isset($collectionItem['room_id'])) {
						$newParamsRow['room_id'] = $collectionItem['room_id'];
					}

					if (isset($collectionItem['avail'])) {
						$newParamsRow['availability'] = $collectionItem['avail'];
					}

					if (isset($collectionItem['capacity'])) {
						$newParamsRow['capacity'] = $collectionItem['capacity'];
					}

					if (isset($collectionItem['min_stay'])) {
						if (!isset($newParamsRow['stay_length'])) {
							$newParamsRow['stay_length'] = [];
						}

						$newParamsRow['stay_length'][] = [
							'type' => self::LOS_MIN,
							'time' => $collectionItem['min_stay'],
						];
					}

					if (isset($collectionItem['max_stay'])) {
						if (!isset($newParamsRow['stay_length'])) {
							$newParamsRow['stay_length'] = [];
						}

						$newParamsRow['stay_length'][] = [
							'type' => self::LOS_MAX,
							'time' => $collectionItem['max_stay'],
						];
					}

					$newParamsRow['status'] = self::STATUS_OPEN;
					$newParams[] = $newParamsRow;
				}

				$response = $this->updateRate([
					'default' => $newParams,
					'apartment_id' => $apartmentId,
				]);
			} else {
				throw new \Exception('Empty array of data provided. It may happen when there is not any rate connection.');
			}
		} catch (\Exception $ex) {
            $this->gr2logException($ex, 'Channel Manager: Synchronization Collection Failed', [
				'apartment_id' => $apartmentId
			]);

			$response = new \stdClass();

			$response->status = CivilResponder::STATUS_ERROR;
			$response->code = $ex->getCode();
			$response->message = $ex->getMessage();

			$response = ['ChannelManager' => $response];
		}

		return $this->civilResponseForCivilPeople($response);
	}
}
