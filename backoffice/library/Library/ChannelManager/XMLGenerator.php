<?php

/**
 * @todo: Use Stragegy Design Pattern for Generator
 */

namespace Library\ChannelManager;

use Library\Utility\Debug;
use Zend\Feed\Writer\Writer;

abstract class XMLGenerator {
	protected $bufer = [];

	protected function generate($url, array $params = []) {
		$name = strtolower(str_replace('.', '', $url));

		if (!isset($this->bufer[$name])) {
			$source = file_get_contents($url);
			$this->bufer[$name] = $source;
		} else {
			$source = $this->bufer[$name];
		}

		if (count($params)) {
			foreach ($params as $param => $value) {
				if (is_scalar($value)) {
					$source = str_replace('%' . $param . '%', $value, $source);
				}
			}
		}

		return $source;
	}

	protected function beautify($xmlString) {
		/** @todo: validation please? */
		$xml = new \DomDocument('1.0');
		$xml->preserveWhiteSpace = false;
		$xml->formatOutput = true;
		$xml->loadXML($xmlString);

		return $xml->saveXML();
	}

	protected function getURL($template) {
		$calledClassArray = explode('\\', get_called_class());

		if (count($calledClassArray) > 2) {
			$namespaceParts = [
				$calledClassArray[count($calledClassArray) - 3],
				$calledClassArray[count($calledClassArray) - 2],
			];
		} else {
			throw new \Exception('Unnormal class with unnormal namespace detected.');
		}

		return implode(DIRECTORY_SEPARATOR, [__DIR__, $namespaceParts[0], $namespaceParts[1], 'Template', $template]);
	}
}
