<?php

namespace Library\ChannelManager\Provider\Cubilis;

use Library\ChannelManager\ChannelManager;
use Library\Utility\Debug;

class NotificationReportGenerator extends CubilisXMLGenerator {
	const TEMPLATE_REQUEST_NOTIFICATION_REPORT = 'RequestNotificationReport.xml';
	const TEMPLATE_HOTEL_RESERVATION_ID = 'HotelReservationId.xml';

	public function generateNR($params) {
		$xmlHeader = $this->generateXMLHeader();
		$xmlPOS = $this->generatePOS($params['credentials']);
		$customParams = $params['params'];
		$reservations = '';

		if (count($customParams['default'])) {
			foreach ($customParams['default'] as $param) {
				$reservation = $this->generateHotelReservationId([
					'res_id' => $param['res_id'],
				]);

				if ($reservation) {
					$reservations .= $reservation;
				}
			}
		}

		$url = $this->getURL(self::TEMPLATE_REQUEST_NOTIFICATION_REPORT);

		if (is_readable($url)) {
			return $this->beautify($xmlHeader . $this->generate($url, [
				'pos' => $xmlPOS,
				'reservations' => $reservations,
			]));
		} else {
            $this->setError("{$url} is not readable");
        }

		return false;
	}

	private function generateHotelReservationId($customParams) {
		return $this->generate($this->getURL(self::TEMPLATE_HOTEL_RESERVATION_ID), $customParams);
	}
}
