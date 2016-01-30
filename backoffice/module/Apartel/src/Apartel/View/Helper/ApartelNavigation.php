<?php

namespace Apartel\View\Helper;

use Library\Constants\Roles;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;
use Zend\Navigation\Navigation as Navigation;
use Zend\ServiceManager\ServiceLocatorInterface;
use Library\Constants\Objects;

class ApartelNavigation extends AbstractHelper {
    use ServiceLocatorAwareTrait;
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
	 * Holds the apartel ID to construct navigation links
	 *
	 * @var int
	 * @access protected
	 */
	protected $apartelId;

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
     * @param $menuList
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
	 * @access protected
	 * @return array
	 */
	protected function _filter($data)
    {
        /** @var \DDD\Service\Apartel\General $generalService */
//		$generalService = $this->getServiceLocator()->get('service_website_apartel');
//		$notPermissions = $generalService->permissionChecker($this->apartelId);
//
//		foreach ($data as $key=>$row) {
//			if (in_array(str_replace('apartment/', '', $row['route']), $notPermissions)) {
//				unset($data[$key]);
//			}
//		}

		return $data;
	}

    /**
     * @param $apartelId
     * @return string
     */
    public function __invoke($apartelId)
    {
        $controller = $this->getServiceLocator()->get('Application')->getMvcEvent()->getRouteMatch()->getParam('controller');
        $this->apartelId = $apartelId;
		$this->selectedItem = $controller;
        $menuList = $this->_filter($this->_menuListArray());
        $this->navigationContainer = $this->getNavigationContainer($menuList);

		return $this->render();
	}

    /**
     * @return array
     */
    private function _menuListArray()
    {
        $apartelId = $this->apartelId;
        $params = array (
			'apartel_id' => $apartelId,
		);

        $menuList =  array (
            'general' => [
				'label' => 'General',
				'route' => 'apartel/general',
                'controller' => 'controller_apartel_general',
				'target' => '_self',
				'icon' => 'glyphicon glyphicon-cog',
				'params' => $params
			],
            'content' => [
                'label' => 'Content',
                'route' => 'apartel/content',
                'controller' => 'controller_apartel_content',
                'target' => '_self',
                'icon' => 'glyphicon glyphicon-cog',
                'params' => $params
            ],
            'type-rate' => [
                'label' => 'Room Type / Rate',
                'route' => 'apartel/type-rate/home',
                'controller' => 'controller_apartel_type_rate',
                'target' => '_self',
                'icon' => 'glyphicon glyphicon-tasks',
                'params' => [
                    'apartel_id' => $apartelId,
                    'permission' => Roles::ROLE_APARTMENT_INVENTORY_MANAGER
                ]
            ],
            'calendar' => [
                'label' => 'Calendar',
                'route' => 'apartel/calendar',
                'controller' => 'controller_apartel_calendar',
                'target' => '_self',
                'icon' => 'glyphicon glyphicon-calendar',
                'params' => [
                    'apartel_id' => $apartelId,
                    'permission' => Roles::ROLE_APARTMENT_INVENTORY_MANAGER
                ]
            ],
            'inventory' => [
                'label' => 'Inventory',
                'route' => 'apartel/inventory',
                'controller' => 'controller_apartel_inventory',
                'target' => '_self',
                'icon' => 'glyphicon glyphicon-asterisk',
                'params' => [
                    'apartel_id' => $apartelId,
                    'permission' => Roles::ROLE_APARTMENT_INVENTORY_MANAGER
                ]
            ],
            'connection' => [
                'label' => 'Connection',
                'route' => 'apartel/connection',
                'controller' => 'controller_apartel_connection',
                'target' => '_self',
                'icon' => 'glyphicon glyphicon-random',
                'params' => [
                    'apartel_id' => $apartelId,
                    'permission' => Roles::ROLE_APARTMENT_CONNECTION
                ]
            ],
            'history' => [
                'label' => 'History',
                'route' => 'apartel/history',
                'controller' => 'controller_apartel_history',
                'target' => '_self',
                'icon' => 'glyphicon glyphicon-list-alt',
                'class' => 'pull-right',
                'params' => $params
            ],
		);

        return $menuList;
    }

	public function render()
    {
		/**
         * @var $auth \Library\Authentication\BackofficeAuthenticationService
         */
		$auth = $this->getServiceLocator()->get('library_backoffice_auth');
		$params = [
            'apartel_id' => $this->apartelId
        ];

		$menu = [];

		foreach ($this->navigationContainer->toArray() as $tab) {
			if (isset($tab['params']['permission'])) {
				if (!$auth->hasRole($tab['params']['permission'])) {
					continue;
				}
			}

			$url = $this->getView ()->url($tab ['route'], $params);

			if ($this->selectedItem == $tab['controller']) {
				$li = sprintf($this->_linkSelectedTemplate, $url, $tab['label'], $tab['target'], $tab['icon'], $tab['class']);
			} else {
				$li = sprintf ($this->_linkTemplate, $url, $tab['label'], $tab['target'], $tab['icon'], $tab['class']);
			}

			$menu[] = $li;
		}

		$html = '<ul class="nav nav-tabs nav-apartel" role="tablist">' . PHP_EOL . implode (PHP_EOL, $menu) . PHP_EOL . '</ul>';

		return $html;
	}
}
