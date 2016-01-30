<?php

namespace Backoffice\View\Helper;

use DDD\Dao\ApartmentGroup\ApartmentGroup;
use Library\Constants\Roles;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;
use Zend\Navigation\Navigation as GroupNavigation;

/**
 * Holds the group of apartments navigation system
 *
 * @package ApartmentGroup
 * @subpackage Navigation
 */
class ApartmentGroupNavigation extends AbstractHelper
{
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
     *
     * @var int
     * @access protected
     */
    protected $apartmentGroupId;

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

    protected function getNavigationContainer($menuList)
    {
        $container = new GroupNavigation($menuList);

        return $container;
    }

    /**
     * @param $apartmentGroupId
     * @param $selectedItem
     * @return string
     */
    public function __invoke($apartmentGroupId, $selectedItem)
    {
        $this->apartmentGroupId    = $apartmentGroupId;
        $this->selectedItem        = $selectedItem;
        $this->navigationContainer = $this->getNavigationContainer($this->_menuListArray());

        return $this->render();
    }


    private function _menuListArray()
    {
        /**
         * @var ApartmentGroup $accGroupsManagementDao
         */
        $params = ['id' => $this->apartmentGroupId];

        $accGroupsManagementDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $apartmentGroupData     = $accGroupsManagementDao->getRowById($this->apartmentGroupId);

        if ($apartmentGroupData) {
            $menuList =  [
                'general' => [
                    'class'  => 'general',
                    'label'  => 'General',
                    'route'  => 'apartment-group',
                    'target' => '_self',
                    'icon'   => '',
                    'params' => $params,
                ],
                'building' => [
                    'class'  => 'building',
                    'label'  => 'Building',
                    'route'  => 'apartment-group/building',
                    'target' => '_self',
                    'icon'   => '',
                    'params' => [
                        'id'         => $this->apartmentGroupId,
                        'permission' => (int)$apartmentGroupData->isBuilding(),
                    ],
                ],
                'building-documents' => [
                    'class'  => 'documents',
                    'label'  => 'Documents',
                    'route'  => 'apartment-group/document',
                    'target' => '_self',
                    'icon'   => '',
                    'params' => [
                        'id'         => $this->apartmentGroupId,
                        'permission' => (int)$apartmentGroupData->isBuilding(),
                    ],
                ],
                'contacts' => [
                    'class'  => 'contacts',
                    'label'  => 'Contacts',
                    'route'  => 'apartment-group/contacts',
                    'target' => '_self',
                    'icon'   => '',
                    'params' => [
                        'id'         => $this->apartmentGroupId,
                        'permission' => (int)$apartmentGroupData->isBuilding(),
                    ],
                ],
                'concierge_dashboard' => [
                    'class'  => 'concierge-dashboard',
                    'label'  => 'Concierge Dashboard',
                    'route'  => 'apartment-group/concierge',
                    'target' => '_self',
                    'icon'   => '',
                    'params' => [
                        'id'         => $this->apartmentGroupId,
                        'permission' => (int)$apartmentGroupData->getIsArrivalsDashboard(),
                    ],
                ],
                'history' => [
                    'class'  => 'history',
                    'label'  => 'History',
                    'route'  => 'apartment-group/history',
                    'target' => '_self',
                    'icon'   => 'glyphicon glyphicon-list-alt',
                    'class'  => 'pull-right',
                    'params' => $params,
                ],
            ];
        }

        return $menuList;
    }

    public function render($container = null)
    {
        /**
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         * @var \DDD\Dao\Apartel\General $apartelDao
         * */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $apartelDao = $this->getServiceLocator()->get('dao_apartel_general');
        $params = ['id' => $this->apartmentGroupId];
        $menu = [];

        foreach ($this->navigationContainer->toArray() as $tab) {
            if (isset($tab['params']['permission']) && !$tab['params']['permission']) {
                continue;
            } else {
                if ($tab['route'] == 'apartment-group/document' && !$auth->hasRole(Roles::ROLE_DOCUMENTS_MANAGEMENT)) {
                    continue;
                }
            }

            $url = $this->getView()->url($tab ['route'], $params);

            if (($this->selectedItem == $tab['route']) || $this->selectedItem == $tab['route'] .'/general') {
                $li = sprintf($this->_linkSelectedTemplate, $url, $tab['label'], $tab['target'], $tab['icon'], $tab['class']);
            } else {
                $li = sprintf ($this->_linkTemplate, $url, $tab['label'], $tab['target'], $tab['icon'], $tab['class']);
            }

            array_push($menu, $li);
        }

        // for apartel
        $getApartel = $apartelDao->getApartelByApartmentGroup($this->apartmentGroupId);

        if ($getApartel && ($auth->hasRole(Roles::ROLE_APARTMENT_INVENTORY_MANAGER) || $auth->hasRole(Roles::ROLE_APARTMENT_CONNECTION))) {
            array_push($menu, '<li class="%5$s"><a href="/apartel/' . $getApartel['id'] . '" title="apartel" target="_blank"><span class="apartel"></span> Apartel</a></li>');
        }

        $html = '<ul class="nav nav-tabs nav-apartment-group" role="tablist">' . PHP_EOL . implode (PHP_EOL, $menu) . PHP_EOL . '</ul>';

        return $html;
    }
}
