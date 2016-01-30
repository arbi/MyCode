<?php

namespace Backoffice\View\Helper;

use Library\Utility\Debug;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\View\Helper\AbstractHelper;

class Breadcrumb extends AbstractHelper {
	public function __invoke($items) {
        $out = '';

		if (count($items)) {
			foreach ($items as $i => $item) {
				$active = '';

				if ($i == count($items) - 1) {
					$active = ' class="active"';
				}

				$out .= (
					count($item) === 1
						? '<li' . $active . '>' . $item[0] . '</li>' . PHP_EOL
						: '<li' . $active . '><a href="' . $item[1] . '">' . $item[0] . '</a></li>' . PHP_EOL
				);
			}
		}

		return $out;
    }
}
