<?php

namespace Apartment\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Library\Constants\DomainConstants;

/**
 * Render rate navigation
 *
 * @package apartment
 * @subpackage apartment_view_helpers
 */
class ApartmentReservationsLink extends AbstractHelper {

	/**
	 *
	 * @access public     	
	 * @param int $apartmentId
	 * @return string
	 */
	public function __invoke($apartmentId) {
		
		$url = '//' . DomainConstants::BO_DOMAIN_NAME . '/booking/' . $apartmentId;
		
		$html = ' <a href="' . $url . '" class="action-item label label-info margin-left-5 pull-right" target="blank">Reservations&nbsp;<span class="glyphicon glyphicon-share"></span></a> ';
		
		return $html;
	}
}