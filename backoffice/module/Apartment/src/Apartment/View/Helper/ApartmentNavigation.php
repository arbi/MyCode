<?php

namespace Apartment\View\Helper;

use Library\Constants\Roles;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;
use Zend\Navigation\Navigation as Navigation;
use Zend\ServiceManager\ServiceLocatorInterface;
use Library\Constants\Objects;

/**
 * Holds the apartment's navigation system
 *
 *
 * @package Apartment
 * @subpackage Navigation
 */
class ApartmentNavigation extends AbstractHelper
{
    use ServiceLocatorAwareTrait;

	/**
	 * Template for the links
	 *
	 * @var string
	 */
	protected $_linkTemplate = '<li class="%5$s"><a href="%1$s" title="%2$s" target="%3$s"><span class="%4$s"></span> %2$s</a></li>';

	/**
	 * Template for the selected link
	 *
	 * @var string
	 */
	protected $_linkSelectedTemplate = '<li class="active %5$s"><a href="%1$s" title="%2$s" target="%3$s"><span class="%4$s"></span> %2$s</a></li>';

	/**
	 * Holds the apartment ID to construct navigation links
	 *
	 * @var int
	 */
	protected $apartmentId;

	/**
	 * @var string
	 */
	protected $selectedItem;

	/**
	 * Holds the navigation array
	 *
	 * @var array
	 */
	protected $navigationContainer;

    /**
     * @param array $menuList
     * @return Navigation
     */
    protected function getNavigationContainer($menuList) {
		$container = new Navigation($menuList);

		return $container;
	}

	/**
	 * Returns an array with all the pages that will be available for
	 * the current user
	 *
	 * @param array $data
	 * @return array
	 */
	protected function _filter($data)
    {
		$generalService = $this->getServiceLocator()->get('service_apartment_general');
		$notPermissions = $generalService->permissionChecker($this->apartmentId);

		foreach ($data as $key=>$row) {
			if (in_array(str_replace('apartment/', '', $row['route']), $notPermissions)) {
				unset($data[$key]);
			}
		}

		return $data;
	}

    /**
     * @param int $apartmentId
     * @param int $apartmentStatus
     * @param $selectedItem
     * @return string
     */
    public function __invoke($apartmentId, $apartmentStatus, $selectedItem)
    {
        $this->apartmentId = $apartmentId;
		$this->selectedItem = $selectedItem;
        $menuList = $this->_filter($this->_menuListArray($apartmentStatus));
        $this->navigationContainer = $this->getNavigationContainer($menuList);

		return $this->render();
	}


    /**
     * @param int $apartmentStatus
     * @return array
     */
    private function _menuListArray($apartmentStatus)
    {
        $apartmentId = $this->apartmentId;
        $params = [
			'apartment_id' => $apartmentId,
		];

        $menuList =  [
            'general' =>
			array (
				'label' => 'General',
				'route' => 'apartment/general',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-cog',
				'params' => $params
			),
            'details' =>
			array (
				'label' => 'Details',
				'route' => 'apartment/details',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-list',
				'params' => $params
			),
            'location' =>
			array (
				'label' => 'Location',
				'route' => 'apartment/location',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-map-marker',
				'params' => $params
			),
            'media' =>
			array (
				'label' => 'Media',
				'route' => 'apartment/media',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-film',
				'params' => $params
			),
            'documents' =>
			array (
				'label' => 'Docs',
				'route' => 'apartment/document',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-file',
				'params' => $params
			),
            'rates' =>
			array (
				'label' => 'Rates',
				'route' => 'apartment/rate',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-tasks',
				'params' => [
                    'apartment_id' => $apartmentId,
                    'permission' => Roles::ROLE_APARTMENT_INVENTORY_MANAGER
                ]
			),
            'calendar' =>
			array (
				'label' => 'Calendar',
				'route' => 'apartment/calendar',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-calendar',
				'params' => $params
			),
            'inventory' =>
			array (
				'label' => 'Inventory',
				'route' => 'apartment/inventory-range',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-asterisk',
				'params' => $params
			),
            'connection' =>
			array (
				'label' => 'Connection',
				'route' => 'apartment/channel-connection',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-random',
				'params' => [
					'apartment_id' => $apartmentId,
					'permission' => Roles::ROLE_APARTMENT_CONNECTION,
				]
			),
            'costs' =>
			array (
				'label' => 'Costs',
				'route' => 'apartment/cost',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-briefcase',
                'params' => [
                    'apartment_id' => $apartmentId,
					'permission' => Roles::ROLE_APARTMENT_COSTS_READER,
                ]
			),
            'statistics' =>
			array (
				'label' => 'Stats',
				'route' => 'apartment/statistics',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-signal',
				'params' => $params
			),
            'reviews' =>
			array (
				'label' => 'Reviews',
				'route' => 'apartment/review',
				'target' => '_self',
                'icon' => 'glyphicon glyphicon-star-empty',
				'params' => $params
			),
            'history' =>
			array (
				'label' => 'History',
				'route' => 'apartment/history',
				'target' => '_self',
                'icon' => 'glyphicon glyphicon-list-alt',
                'class' => 'pull-right',
				'params' => $params
			),
		];

        if ($apartmentStatus == Objects::PRODUCT_STATUS_DISABLED) {
            unset($menuList['inventory'], $menuList['connection'], $menuList['rates'], $menuList['calendar']);
        }

        return $menuList;
    }

	public function render($container = null)
    {
		/* @var $auth \Library\Authentication\BackofficeAuthenticationService */
		$auth = $this->getServiceLocator()->get('library_backoffice_auth');
		$params = [
			'apartment_id' => $this->apartmentId,
		];

		$menu = [];

		foreach ($this->navigationContainer->toArray() as $tab) {
			if (isset($tab['params']['permission'])) {
				if (!$auth->hasRole($tab['params']['permission'])) {
					continue;
				}
			}

			$url = $this->getView()->url($tab['route'], $params);

			if ($this->selectedItem == $tab['route']) {
				$li = sprintf($this->_linkSelectedTemplate, $url, $tab['label'], $tab['target'], $tab['icon'], $tab['class']);
			} else {
				$li = sprintf($this->_linkTemplate, $url, $tab['label'], $tab['target'], $tab['icon'], $tab['class']);
			}

			array_push($menu, $li);
		}

        return '<ul class="nav nav-tabs nav-apartment" role="tablist">' . PHP_EOL . implode (PHP_EOL, $menu) . PHP_EOL . '</ul>';
    }
}
