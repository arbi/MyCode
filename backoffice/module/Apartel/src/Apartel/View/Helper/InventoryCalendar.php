<?php

namespace Apartel\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * Class InventoryCalendar
 * @package Apartment\View\Helper
 */
class InventoryCalendar extends AbstractHelper {
	private $navItemTemplate         = '<li><a href="%1$s"><span class="glyphicon glyphicon-chevron-right"></span> %2$s %3$s</a></li>';
	private $navSelectedItemTemplate = '<li class="active"><a href="%1$s"><span class="glyphicon glyphicon-chevron-right"></span> %2$s %3$s</a></li>';

    /**
     * @param $apartelId
     * @param $roomTypeId
     * @param $year
     * @param $month
     * @return string
     */
	public function __invoke($apartelId, $roomTypeId, $year, $month) {
		// get current month
		$currentYear        = date ( 'y' );
		$currentYear4Digits = date ( 'Y' );
		$nextYear4Digits    = $currentYear4Digits + 1;
		$currentMonth       = date ( 'n' );

		$monthNavigation = array ();
		// this year links
		for($counter = $currentMonth; $counter <= 12; $counter ++) {
			$monthNavigation [] = [
				'monthName'   => date ( "F", mktime ( 0, 0, 0, $counter, 10 ) ),
				'month'       => $counter,
				'year'        => $currentYear4Digits,
				'year4Digits' => $currentYear4Digits
			];
		}

		// next year links
		for($counter = 1; $counter <= $currentMonth; $counter ++) {
			$monthNavigation [] = [
				'monthName'   => date ( "F", mktime ( 0, 0, 0, $counter, 10 ) ),
				'month'       => $counter,
				'year'        => $nextYear4Digits,
				'year4Digits' => $nextYear4Digits
			];
		}

		$html = '<ul class="nav nav-pills nav-stacked">';
		foreach ( $monthNavigation as $item ) {
			$link = $this->view->url (
				'apartel/calendar',
				[
					'apartel_id' => $apartelId,
					'type_id' => $roomTypeId,
					'year' => $item ['year4Digits'],
					'month' => $item ['month']
				]
			);

			if ($year == $item ['year'] && $month == $item ['month'])
				$html .= sprintf ( $this->navSelectedItemTemplate, $link, $item ['monthName'], $item ['year'] );
			else
				$html .= sprintf ( $this->navItemTemplate, $link, $item ['monthName'], $item ['year'] );
		}
		$html .= '</ul>';

		return $html;
	}
}
