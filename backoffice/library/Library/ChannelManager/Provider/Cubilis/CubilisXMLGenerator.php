<?php

namespace Library\ChannelManager\Provider\Cubilis;

use Library\ChannelManager\XMLGenerator;

class CubilisXMLGenerator extends XMLGenerator {
	const TEMPLATE_XML_HEADER = 'XMLHeader.xml';
	const TEMPLATE_POS = 'POS.xml';

    protected $errors = [];

	protected function generateXMLHeader(array $params = []) {
		$url = $this->getURL(self::TEMPLATE_XML_HEADER);

		if (is_readable($url)) {
			return $this->generate($url, $params);
		}

		return false;
	}

	protected function generatePOS(array $params = []) {
		$url = $this->getURL(self::TEMPLATE_POS);

		if (is_readable($url)) {
			return $this->generate($url, $params);
		}

		return false;
	}

    protected function setError($errorMessage) {
        $this->errors[] = $errorMessage;
    }

    public function getErrors() {
        return implode(PHP_EOL, $this->errors);
    }
}
