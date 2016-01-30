<?php

namespace Apartel\View\Helper;

use DDD\Domain\Apartment\Rate\Select;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;
use DDD\Service\Apartment\Rate as RateService;


class TypeRateNavigation extends AbstractHelper
{
    use ServiceLocatorAwareTrait;
	/**
	 *
	 * @access private
	 * @var string
	 */
	private $navItemTemplate = '<li class="%3$s"><a href="%1$s"><span class="glyphicon glyphicon-chevron-right"></span> %2$s</a></li>';

    /**
     * @param $typeRates
     * @return string
     */
    public function __invoke($typeRates)
    {
        $routeMatch = $this->getServiceLocator()->get('Application')->getMvcEvent()->getRouteMatch();
        $apartelId = $routeMatch->getParam('apartel_id', 0);
        $selectedTypeID = $routeMatch->getParam('type_id', 0);
        $selectedRateID = $routeMatch->getParam('rate_id', 0);

		$html = '<div class="row">
		            <div class="col-sm-12">
		                <ul class="nav nav-stacked nav-pills">';

		foreach ($typeRates as $type) {
			$linkType = $this->view->url('apartel/type-rate/type', [
                        'apartel_id' => $apartelId,
                        'type_id' => $type['type_id']
                    ]);
            $classType = $selectedTypeID && $selectedTypeID == $type['type_id'] && !$selectedRateID ? 'menu-type active' : 'menu-type';
			$html .= sprintf($this->navItemTemplate, $linkType, $type['type_name'], $classType);

            foreach ($type['rate_list'] as $rate) {
                if ($rate['rate_id']) {
                    $linkRate = $this->view->url('apartel/type-rate/type/rate', [
                        'apartel_id' => $apartelId,
                        'type_id' => $type['type_id'],
                        'rate_id' => $rate['rate_id'],
                    ]);
                    $classRate = $selectedRateID && $selectedRateID == $rate['rate_id'] ? 'menu-rate active' : 'menu-rate';
                    $html .= sprintf($this->navItemTemplate, $linkRate, $rate['rate_name'], $classRate);
                }
            }

            $linkAddNewRate = $this->view->url('apartel/type-rate/type/rate', [
                'apartel_id' => $apartelId,
                'type_id' => $type['type_id'],
            ]);

            $html .= '<div class="rate-add-part"><a href="' . $linkAddNewRate . '" class="btn btn-medium btn-default btn-block"><span class="glyphicon glyphicon-plus"></span> Add New Rate</a></div>';
		}

		$html .= '      </ul>
		            </div>
		        </div>
                <div class="row">
                    <div class="col-sm-12">
                        <a href="/apartel/' . $apartelId . '/type-rate" class="btn btn-medium btn-success btn-block"><span class="glyphicon glyphicon-plus"></span> Add New Room Type</a>
                    </div>
                </div>';

		return $html;
	}
}
