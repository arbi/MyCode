<?php

namespace Library\ChannelManager;

use Zend\Config\Config;

class Settings extends Config {
	public function __construct($params = []) {
		parent::__construct($params);
	}
}
