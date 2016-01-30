<?php

namespace Library\Traits;

use Library\Utility\Debug;
use Zend\Log\Logger;

trait CubilisLog {
	/**
	 * It is a shortcut for logger under DDD\Service\ServiceBase class.
	 *
	 * @param $message string
	 * @param $level int
	 */
	protected function log($message, $level = Logger::INFO) {
		// do nothing here
	}
}
