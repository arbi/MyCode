<?php

namespace Library\ChannelManager\Provider\Cubilis;

use Library\ChannelManager\ChannelManager;
use Library\Utility\Debug;

class RateInformationGenerator extends CubilisXMLGenerator {
	const TEMPLATE_REQUEST_ROOM_INFORMATION = 'RequestRateInformation.xml';

	public function generateRI($params) {
		$xmlHeader = $this->generateXMLHeader();
		$xmlPOS = $this->generatePOS($params['credentials']);

		if ($xmlHeader && $xmlPOS) {
			$url = $this->getURL(self::TEMPLATE_REQUEST_ROOM_INFORMATION);

			if (is_readable($url)) {
				return $this->beautify($xmlHeader . $this->generate($url, [
					'pos' => $xmlPOS,
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
}
