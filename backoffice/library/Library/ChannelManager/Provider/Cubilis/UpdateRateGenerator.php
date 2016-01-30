<?php

namespace Library\ChannelManager\Provider\Cubilis;

use Library\ChannelManager\ChannelManager;
use Library\Utility\Debug;

class UpdateRateGenerator extends CubilisXMLGenerator {
	const TEMPLATE_REQUEST_AVAILABLE_NOTIFICATION = 'AvailableNotification.xml';
	const TEMPLATE_REQUEST_AVAILABLE_STATUS_MESSAGE = 'AvailableStatusMessage.xml';
	const TEMPLATE_REQUEST_LENGTHS_OF_STAY = 'LengthsOfStay.xml';
	const TEMPLATE_REQUEST_LENGTH_OF_STAY = 'LengthOfStay.xml';
	const TEMPLATE_REQUEST_BEST_AVAILABLE_RATES = 'BestAvailableRates.xml';
	const TEMPLATE_REQUEST_BEST_AVAILABLE_RATE = 'BestAvailableRate.xml';

	public function generateRI($params) {
		$xmlHeader = $this->generateXMLHeader();
		$xmlPOS = $this->generatePOS($params['credentials']);
		$params = $params['params'];

		if ($xmlHeader && $xmlPOS) {
			$url = $this->getURL(self::TEMPLATE_REQUEST_AVAILABLE_NOTIFICATION);

			if (is_array($params['default']) && count($params['default'])) {
				$messages = '';

				foreach ($params['default'] as $message) {
					$messages .= $this->generateMessage($message);
				}
			} else {
				throw new \Exception('No data provided to sync.');
			}

			if (is_readable($url)) {
				/** @todo: more validation please? */
				$brandCode = isset($params['provider_specific']['cubilis']['brand_code']) ? 'BrandCode="' . $params['provider_specific']['cubilis']['brand_code'] . '"' : '';

				return $this->beautify($xmlHeader . $this->generate($url, [
					'pos' => $xmlPOS,
					'brand_code' => $brandCode,
					'messages' => $messages,
				]));
			} else {
				throw new \Exception('File ' . self::TEMPLATE_REQUEST_AVAILABLE_NOTIFICATION . ' is not readable.');
			}
		}

		return false;
	}

	private function generateMessage($params)
    {
		$url = $this->getURL(self::TEMPLATE_REQUEST_AVAILABLE_STATUS_MESSAGE);

		// Check for not connected rates
		if (isset($params['rate_id'])) {
			if (is_null($params['rate_id']) || trim($params['rate_id']) == '') {
				return '';
			}
		} else {
			return '';
		}

		if (is_readable($url)) {
			// Min Max Stays, optional
			if (isset($params['stay_length'])) {
				$collection = $params['stay_length'];

				if (is_array($collection) && count($collection)) {
					$stays = $this->generateStays($collection);
				}
			}

			// Price, optional
			if (isset($params['price'])) {
				$collection = $params['price'];

				if (is_array($collection) && count($collection)) {
					$prices = $this->generatePrices($collection);
				}
			}

			// Rate ID, optional
			if (isset($params['rate_id'])) {
				$rateId = 'RatePlanID="' . $this->getParam($params['rate_id'], 'int', false) . '"';
			}

			// Status
			if (isset($params['status']) && in_array($params['status'], [ChannelManager::STATUS_OPEN, ChannelManager::STATUS_CLOSE])) {
				$status = (
					$params['status'] == ChannelManager::STATUS_OPEN ?
					'Open' : 'Closed'
				);
			} else {
				throw new \Exception('Parameter Status is not defined.');
			}

			return $this->generate($url, [
				'availability' => $this->getParam($params['availability']),
				'room_id' => $this->getParam($params['room_id']),
				'start_date' => $this->getParam(date('Y-m-d', strtotime($params['date_start'])), 'date'),
				'end_date' => $this->getParam(date('Y-m-d', strtotime($params['date_end'])), 'date'),
				'rate_id' => isset($rateId) ? $rateId : '',
				'stays' => isset($stays) ? $stays : '',
				'prices' => isset($prices) ? $prices : '',
				'status' => $status,
			]);
		} else {
			throw new \Exception('File ' . self::TEMPLATE_REQUEST_AVAILABLE_NOTIFICATION . ' is not readable.');
		}
	}

	private function generateStays($params) {
		$url = $this->getURL(self::TEMPLATE_REQUEST_LENGTHS_OF_STAY);

		if (is_readable($url)) {
			$stays = '';

			if (count($params)) {
				foreach ($params as $stay) {
					if (isset($stay['type']) && isset($stay['time']) && in_array($stay['type'], [ChannelManager::LOS_MIN, ChannelManager::LOS_MAX]) && ctype_digit($stay['time'])) {
						$LOSType = (
							$stay['type'] == ChannelManager::LOS_MIN ?
							'SetMinLOS' : 'SetMaxLOS'
						);
					} else {
						throw new \Exception('Unnormal data format for LengthsOfStay element.');
					}

					$stays .= $this->generateStay([
						'min_max_type' => $LOSType,
						'min_max_time' => $stay['time'],
					]);
				}
			}

			return $this->generate($url, [
				'stay_min_max' => $stays,
			]);
		} else {
			throw new \Exception('File ' . self::TEMPLATE_REQUEST_LENGTHS_OF_STAY . ' is not readable.');
		}
	}

	private function generateStay($params) {
		$url = $this->getURL(self::TEMPLATE_REQUEST_LENGTH_OF_STAY);

		if (is_readable($url)) {
			return $this->generate($url, $params);
		} else {
			throw new \Exception('File ' . self::TEMPLATE_REQUEST_LENGTH_OF_STAY . ' is not readable.');
		}
	}

	private function generatePrices($params) {
		$url = $this->getURL(self::TEMPLATE_REQUEST_BEST_AVAILABLE_RATES);

		if (is_readable($url)) {
			$prices = '';

			if (is_array($params) && count($params) > 0) {
				if (isset($params['standard'])) {
					$prices .= $this->generatePrice([
						'amount' => $this->getParam($params['standard'], 'float'),
						'amount_type' => '',
					]);
				} else {
					throw new \Exception('Unnormal data format for BestAvailableRates element.');
				}

				if (isset($params['single'])) {
					$prices .= $this->generatePrice([
						'amount' => $this->getParam($params['single'], 'float'),
						'amount_type' => 'RatePlanCode="Single"',
					]);
				}
			}

			return $this->generate($url, [
				'available_prices' => $prices,
			]);
		} else {
			throw new \Exception('File ' . self::TEMPLATE_REQUEST_BEST_AVAILABLE_RATES . ' is not readable.');
		}
	}

	private function generatePrice($params) {
		$url = $this->getURL(self::TEMPLATE_REQUEST_BEST_AVAILABLE_RATE);

		if (is_readable($url)) {
			return $this->generate($url, [
				'amount' => $params['amount'],
				'amount_type' => $params['amount_type'],
			]);
		} else {
			throw new \Exception('File ' . self::TEMPLATE_REQUEST_BEST_AVAILABLE_RATE . ' is not readable.');
		}
	}

	private function getParam($param, $type = 'int', $required = true) {
		$paramWithType = false;

		if (isset($param)) {
			switch ($type) {
				case 'int':
					$paramWithType = !ctype_digit((string)$param) ?: (int)$param;

					break;
				case 'float':
					$paramWithType = !is_numeric($param) ?: (float)$param;

					break;
				case 'date':
					$paramWithType = !(bool)strtotime($param) ?: $param;

					break;
			}
		}

		if ($paramWithType === false) {
			if ($required) {
				throw new \Exception('Parameter [' . $this->getVariableName($param) . '] is not defined.');
			} else {
				return '';
			}
		} else {
			return $paramWithType;
		}
	}

	private function getVariableName($var) {
		$tempArray[$var] = '';

		foreach ($tempArray as $key => $val) {
			return $key;
		}

		// The right code is the code that works right
		return false;
	}
}
