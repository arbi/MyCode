<?php

namespace Library\ChannelManager\Provider\Cubilis;

use Library\ChannelManager\ChannelManager;
use Library\Utility\Debug;

class RoomInformationGenerator extends CubilisXMLGenerator {
	const TEMPLATE_REQUEST_ROOM_INFORMATION = 'RequestRoomInformation.xml';
	const TEMPLATE_REQUEST_UNIQUE_ID = 'UniqueId.xml';

	public function generateRI($params) {
		$xmlHeader = $this->generateXMLHeader();
		$xmlPOS = $this->generatePOS($params['credentials']);

		// default values
		$uniqueId = '';
		$purgeDate = '';
		$customParams = $params['params'];

		switch ($customParams['type']) {
			case ChannelManager::RESERVATION_TYPE_STANDARD:
				// do nothing

				break;
			case ChannelManager::RESERVATION_TYPE_DATE:
				$purgeDate = sprintf('PurgeDate="%s"', $customParams['date']);

				break;
			case ChannelManager::RESERVATION_TYPE_RESERVATION:
				$uniqueId = $this->generateUniqueId([
					'type' => 'RES',
					'id' => $customParams['reservation'],
				]);

				break;
		}

		if ($xmlHeader && $xmlPOS) {
			$url = $this->getURL(self::TEMPLATE_REQUEST_ROOM_INFORMATION);

			if (is_readable($url)) {
				return $this->beautify($xmlHeader . $this->generate($url, [
					'pos' => $xmlPOS,
					'unique_id' => $uniqueId,
					'purge_date' => $purgeDate,
				]));
			} else {
                $this->setError("{$url} is not readable");
            }
		} else {
            $this->setError("xmlHeader: {$xmlHeader}");
            $this->setError("xmlPOS: {$xmlPOS}");
        }

		return false;
	}

	private function generateUniqueId($customParams) {
		return $this->generate($this->getURL(self::TEMPLATE_REQUEST_UNIQUE_ID), $customParams);
	}
}
