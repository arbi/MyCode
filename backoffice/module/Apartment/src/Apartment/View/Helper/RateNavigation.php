<?php

namespace Apartment\View\Helper;

use DDD\Domain\Apartment\Rate\Select;
use Library\Utility\Debug;
use Zend\View\Helper\AbstractHelper;
use DDD\Service\Apartment\Rate as RateService;

/**
 * Render rate navigation
 *
 * @package apartment
 * @subpackage apartment_view_helpers
 */
class RateNavigation extends AbstractHelper
{
	/**
	 *
	 * @access private
	 * @var string
	 */
	private $navItemTemplate = '<li><a href="%1$s"><span class="glyphicon glyphicon-chevron-right"></span> %2$s</a></li>';

	/**
	 *
	 * @access private
	 * @var string
	 */
	private $navSelectedItemTemplate = '<li class="active"><a href="%1$s"><span class="glyphicon glyphicon-chevron-right"></span> %2$s</a></li>';

	/**
	 * @access public
	 * @param array $rates
     * @param int $apartmentId
     * @param int $selectedRateID
	 * @param string $action
	 * @return string
	 */
	public function __invoke($rates, $apartmentId = null, $selectedRateID = null, $action = 'rate')
    {
		$html = '<ul class="nav nav-stacked nav-pills">';

		foreach ($rates as $rate) {
			/** @var $rate Select */
			$link = (
				(!is_null($apartmentId))
					? $this->view->url("apartment/{$action}/index", [
						'apartment_id' => $apartmentId,
						'rate_id' => $rate->getID()
					])
					: "#rateId={$rate->getID()}"
			);

			$parentSymbol = '';
			if ($rate->getType() == RateService::TYPE1) {
				$parentSymbol = '<span class="glyphicon glyphicon-heart-empty"></span>';
			}

			if (!$rate->isActive()) {
				$parentSymbol = '<small class="label label-default pull-right label-inactive-rate">inactive</small>';
			}

			$html .= (
				(!is_null($selectedRateID) && $selectedRateID == $rate->getID())
					? sprintf($this->navSelectedItemTemplate, $link, $rate->getName() . ' ' . $parentSymbol)
					: sprintf($this->navItemTemplate, $link, $rate->getName() . ' ' . $parentSymbol)
			);
		}

		$html .= '</ul>';

		return $html;
	}
}
