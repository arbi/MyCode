<?php

namespace Library\ChannelManager;

use Library\Utility\Debug;

abstract class XMLParser {
	protected $xml;
	protected $domDocument;

	public function __construct($xml) {
		libxml_use_internal_errors(true);

		$this->xml = $xml;
		$this->domDocument = new \DOMDocument('1.0');

		// Profilactic $ku. Don't delete it. Have fun!
		$ku = $this->domDocument->loadXML($xml);

		if ($errors = libxml_get_errors()) {
			$message = '';

			/** @var $error \LibXMLError */
			foreach ($errors as $error) {
				$message .= $error->message;
			}

			libxml_clear_errors();

			throw new \Exception('XML is broken. "' . $message);
		}
	}

	public function validateXML($schema = null) {
		if (is_null($schema)) {
			return $this->domDocument->validate();
		} else {
			return $this->domDocument->schemaValidateSource($schema);
		}
	}
}
