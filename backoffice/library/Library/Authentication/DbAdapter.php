<?php

namespace Library\Authentication;

use Zend\Authentication\Adapter\DbTable;

final class DbAdapter extends DbTable {
	function __construct($dbAdapter, $dbTable = null, $identityColumn = null, $credentialColumn = null) {
        parent::__construct($dbAdapter, $dbTable, $identityColumn, $credentialColumn, '? AND disabled = 0');
	}

	/**
	 * @see \Zend\Authentication\Adapter\DbTable\AbstractAdapter::getResultRowObject()
	 */
	public function getResultRowObject($returnColumns = null, $omitColumns = null) {
		return parent::getResultRowObject(null, ['password']);
	}
}
