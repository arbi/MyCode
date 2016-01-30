<?php

namespace Library\ChannelManager\Provider\Cubilis;

use Library\ChannelManager\Anonymous;
use Library\ChannelManager\XMLParser;
use Zend\Http\Response;

abstract class CubilisXMLParser extends XMLParser {
	public function isSuccess() {
		return (bool)$this->domDocument->getElementsByTagName('Success')->length;
	}

	/**
	 * @todo: Extend to show all errors.
	 * @return \Library\ChannelManager\Provider\Cubilis\PHPDocInterface\Error
	 * @throws \Exception
	 */
	public function getError() {
		/** @var $error \DomElement */
		$errorsLength = $this->domDocument->getElementsByTagName('Error')->length;

		if ($errorsLength) {
			$error = $this->domDocument->getElementsByTagName('Error')->item(0);
			$output = new \stdClass();

			$output->code = $error->getAttribute('Code');
			$output->message = $error->getAttribute('ShortText');
			$output->type = $error->getAttribute('Type');

			return $output;
		} else {
			throw new \Exception('Error element not found.');
		}
	}

	public function getDocumentVersion() {
		return $this->domDocument->documentElement->getAttribute('Version') ? $this->domDocument->documentElement->getAttribute('Version') : null;
	}

	/**
	 * @param $domNodeList \DomNodeList
	 * @param $itemMetodName string Method name of handler
	 * @return Anonymous
	 * @throws \BadMethodCallException
	 */
	protected function getCollectionElement($domNodeList, $itemMetodName) {
		$that = $this;
		$anonym = new Anonymous([
			'getLength' => function() use ($domNodeList) {
				return $domNodeList->length;
			},
			'getItems' => function() use ($that, $domNodeList, $itemMetodName) {
				if (!$domNodeList->length) {
					throw new \BadMethodCallException('Node list is empty.');
				}

				$output = [];

				foreach ($domNodeList as $domNodeListItem) {
					if (method_exists($that, $itemMetodName)) {
						$output[] = $that->$itemMetodName($domNodeListItem);
					} else {
						throw new \BadMethodCallException('Method' . $itemMetodName . ' not exists.');
					}
				}

				return $output;
			},
		]);

		return $anonym;
	}
}
