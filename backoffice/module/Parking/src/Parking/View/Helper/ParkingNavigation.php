<?php

namespace Parking\View\Helper;

use Library\Constants\Roles;
use Zend\View\Helper\AbstractHelper;
use Zend\Navigation\Navigation as Navigation;
use Zend\ServiceManager\ServiceLocatorInterface;
/**
 * Holds the parking's navigation system
 *
 * @package Parking
 * @subpackage Navigation
 */
class ParkingNavigation extends AbstractHelper {
	/**
	 * Template for the links
	 *
	 * @var string
	 * @access protected
	 */
	protected $_linkTemplate = '<li class="%5$s"><a href="%1$s" title="%2$s" target="%3$s"><span class="%4$s"></span> %2$s</a></li>';

	/**
	 * Template for the selected link
	 *
	 * @var string
	 * @access protected
	 */
	protected $_linkSelectedTemplate = '<li class="active %5$s"><a href="%1$s" title="%2$s" target="%3$s"><span class="%4$s"></span> %2$s</a></li>';

	/**
	 * Holds the parking lot id to construct navigation links
	 *
	 * @var int
	 * @access protected
	 */
	protected $parkingLotId;


	/**
	 * Holds the parking spot Unit to construct navigation links
	 * If we are not in spot tab the value of the variable will be false
	 * else if we are adding a new spot, the value will be null
	 *
	 * @var int
	 * @access protected
	 */
	protected $parkingSpotUnit;

	/**
	 *
	 * @var string
	 * @access protected
	 */
	protected $selectedItem;

	/**
	 * Holds the navigation array
	 *
	 * @var array
	 * @access protected
	 */
	protected $navigationContainer;

	/**
	 * Constructs the Navigation object.
	 * Must not be called directly
	 *
	 * @access public
	 * @return void
	 */

    /**
	 *
	 * @access private
	 * @var ServiceLocatorInterface
	 */
	private $serviceLocator;

    protected function getNavigationContainer($menuList) {
		$container = new Navigation($menuList);

		return $container;
	}

    /**
     * @param $parkingId
     * @param $selectedItem
	 * @param $parkingSpotId
     * @return string
     */
    public function __invoke($parkingId, $selectedItem, $parkingSpotUnit = false)
    {
        $this->parkingLotId = $parkingId;
		$this->selectedItem = $selectedItem;
		$this->parkingSpotUnit = $parkingSpotUnit;
        $this->navigationContainer = $this->getNavigationContainer($this->_menuListArray());

		return $this->render();
	}


    private function _menuListArray()
    {
        $parkingLotId = $this->parkingLotId;
        $params = [
			'parking_lot_id' => $parkingLotId,
		];

        $menuList =  [
            'general' =>
			[
				'label' => 'General',
				'route' => 'parking/general',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-cog',
				'params' => $params
			],
            'spots' =>
			[
				'label' => 'Spots',
				'route' => 'parking/spots',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-tasks',
				'params' => [
                    'parking_lot_id' => $parkingLotId,
                    'permission' => Roles::ROLE_PARKING_MANAGEMENT
                ]
			],
            'calendar' =>
			[
				'label' => 'Calendar',
				'route' => 'parking/calendar',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-calendar',
				'params' => $params
			],
		];

		if (false !== $this->parkingSpotUnit) {
			if (null === $this->parkingSpotUnit) {
				$spotLabelName = 'Add New';
				$glyphiconClass = 'glyphicon-plus';
			} else {
				$glyphiconClass = 'glyphicon-pencil';
				$spotLabelName = $this->parkingSpotUnit;
			}

			$menuList['spot'] =
				[
					'label' => $spotLabelName,
					'route' => 'parking/spots/edit',
					'target' => '_self',
					'icon' => 'glyphicon ' . $glyphiconClass,
					'params' => [
						'parking_lot_id' => $parkingLotId,
						'permission' => Roles::ROLE_PARKING_MANAGEMENT
					]
				];

		}

        return $menuList;
    }

	public function render($container = null) {
		/* @var $auth \Library\Authentication\BackofficeAuthenticationService */
		$auth = $this->getServiceLocator()->get('library_backoffice_auth');
		$params = array (
			'parking_lot_id' => $this->parkingLotId
		);

		$menu = [];

		foreach ($this->navigationContainer->toArray() as $tab) {
			if (isset($tab['params']['permission'])) {
				if (!$auth->hasRole($tab['params']['permission'])) {
					continue;
				}
			}

			$url = $this->getView ()->url($tab ['route'], $params);

			if ($this->selectedItem == $tab['route']) {
				$li = sprintf($this->_linkSelectedTemplate, $url, $tab['label'], $tab['target'], $tab['icon'], $tab['class']);
			} else {
				$li = sprintf ($this->_linkTemplate, $url, $tab['label'], $tab['target'], $tab['icon'], $tab['class']);
			}

			array_push($menu, $li);
		}

		$html = '<ul class="nav nav-tabs nav-parking" role="tablist">' . PHP_EOL . implode (PHP_EOL, $menu) . PHP_EOL . '</ul>';

		return $html;
	}

    public function getServiceLocator() {
		return $this->serviceLocator;
	}

	/**
	 * Set service locator
	 *
	 * @param ServiceLocatorInterface $serviceLocator
	 */
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
		$this->serviceLocator = $serviceLocator;
	}
}
