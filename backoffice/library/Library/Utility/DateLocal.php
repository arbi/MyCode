<?php

namespace Library\Utility;

use Library\Constants\Objects;
use Library\Validator\ClassicValidator;
use Zend\Db\ResultSet\ResultSet;
use Zend\Session\Container;

class DateLocal {
	public static function convertDate($date) {
		if (empty($date)) {
			return '';
		}

		if (ClassicValidator::checkDateRegex($date)) {
			list($day, $month, $year) = explode(' ', $date);

			return $year . '-' . self::convertMonth($month) . '-' . $day;
		} else {
			throw new \Exception('Invalid date format.');
		}
	}

	public static function convertDateFromYMD($date, $secondaryDate = null) {
		if (empty($date)) {
			return '';
		}

		if (ClassicValidator::checkDateRegexYMD($date)) {
			list($year, $month, $day) = explode('-', $date);

			try {
				$rightDate = self::convertMonth($month) . ' ' . $day  .', ' . $year;
			} catch (\Exception $ex) {
				$rightDate = $secondaryDate;
			}

			return $rightDate;
		} else {
			throw new \Exception('Invalid date format.');
		}
	}

	public static function convertMonth($month) {
		$months = Objects::getMonths();

		if (in_array($month, $months)) {
			array_unshift($months, '');
			$monthsReversed = array_flip($months);

			return str_pad($monthsReversed[$month], 2, '0', STR_PAD_LEFT);
		} elseif (in_array($month, range(1, 12))) {
			array_unshift($months, '');

			return $months[(int)$month];
		} else {
			throw new \Exception('Wrong month name.');
		}
	}
}

