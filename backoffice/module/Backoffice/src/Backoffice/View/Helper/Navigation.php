<?php

namespace Backoffice\View\Helper;

use Zend\Navigation\AbstractContainer;
use Zend\Navigation\Page\AbstractPage;
use Zend\View\Helper\AbstractHelper;
use Zend\View\Helper\Navigation as NavigationHelper;

class Navigation extends AbstractHelper {
    const SEPARATOR = '%-----%';
    const USERNAME = '%username%';

    private $level = 0;
    private $userName = NULL;

    /**
     * @param AbstractPage[] $container
     * @param NavigationHelper $navigation
     * @param string $name
     *
     * @return string
     * @throws \Exception
     */
    public function __invoke($container, $navigation, $name)
    {
        if (!($container instanceof AbstractContainer)) {
            return false;
        }

        if (!in_array($name, ['main', 'profile', 'notifications'])) {
            throw new \Exception('Parameter name is undefined.');
        }

        $name = ucfirst($name);
        $method = "drawBootstrapNavigation{$name}";

        return $this->$method($container, $navigation);
    }

    public function __construct($serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * @param AbstractPage[] $container
     * @param NavigationHelper $navigation
     *
     * @return string
     */
    protected function drawBootstrapNavigationMain($container, $navigation)
    {
        switch ($this->level) {
            case 0: $navigationClass = 'nav navbar-nav'; break;
            default: $navigationClass = 'dropdown-menu';
        }

        $template = "<ul class='{$navigationClass}'>";

        foreach ($container as $page) {
            if (!$page->isVisible() || !$navigation->accept($page)) {
                continue;
            }

            $request = $this->serviceManager->getServiceLocator()->get('Request');
            $serverParam = $request->getServer();
            if ($page->getHref() == $serverParam->get('REQUEST_URI')) {
                $page->setActive();
            }

            $hasChildren = $page->hasPages();
            $active = $page->isActive() ? ' class="active"' : '';

            // Add Navigation Headings
            if ($this->checkForHeading($page->getLabel(), $template)) {
                continue;
            }

            // Add Navigation Separators
            if ($this->checkForSeparator($page->getLabel(), $template)) {
                continue;
            }

            if (!$hasChildren) {
                $template .= "<li{$active}><a tabindex='-1' href='{$page->getHref()}'>{$page->getLabel()}</a></li>";
            } else {
                $submenuClass = $this->level ? '-submenu' : '';
                $template .= "<li class='dropdown{$submenuClass}'><a tabindex='-1' href='#' class='dropdown-toggle' data-toggle='dropdown'>{$page->getLabel()} <b class='caret visible-xs-inline-block'></a></b>";

                $this->level++;
                $template .= $this->drawBootstrapNavigationMain($page->getPages(), $navigation);
                $this->level--;

                $template .= '</li>';
            }
        }

        $template .= '</ul>';

        return $template;
    }

    /**
     * @param AbstractPage[] $container
     * @param NavigationHelper $navigation
     *
     * @return string
     */
    protected function drawBootstrapNavigationProfile($container, $navigation)
    {
        switch ($this->level) {
            case 0:
                $navigationClass = 'nav navbar-nav navbar-right navbar-profile';
                $template = "<ul class='{$navigationClass}'>";
                break;
            default:
                $navigationClass = 'dropdown-menu';
                $template = "<ul class='{$navigationClass}'>";

                $template .= "<li class='navigation-li-username visible-sm-block visible-md-block visible-lg-block'>{$this->userName}</li>";
                $template .= "<li class='divider'></li>";
        }

        foreach ($container as $page) {
            if (!$page->isVisible() || !$navigation->accept($page)) {
                continue;
            }

            $hasChildren = $page->hasPages();

            // Add Navigation Headings
            if ($this->checkForHeading($page->getLabel(), $template)) {
                continue;
            }

            // Add Navigation Separators
            if ($this->checkForSeparator($page->getLabel(), $template)) {
                continue;
            }

            $this->userName = $page->getLabel();

            $liParams = $this->level
                ? ''
                : " id='fat-menu' class='dropdown profile-menu'";

            $aParams = $this->level
                ? ''
                : " id='drop-profile' class='dropdown-toggle profile-menu-cover' data-toggle='dropdown'";

            $additional = $this->level
                ? ''
                : "<img class='navigation-avatar' src='{$page->get('icon')}'> <span class='visible-xs-inline-block navigation-xs-username'>{$this->userName}</span>";

            if (!$hasChildren) {
                if ($page->getLabel() == 'lampushka') {
                    $template .= "<li{$liParams}><a tabindex='-1' href='#'{$aParams} id='submit-idea'>Give Feedback <span class='submit-idea-icon'></span></a></li>";
                } elseif ($page->getLabel() == 'Lunchroom') {
                    $venueService = $this->serviceManager->getServiceLocator()->get('service_venue_venue');
                    if ($venueService->thereIsLunchroomInUserCityThatExceptOrders()){
                        $template .= "<li{$liParams}><a tabindex='-1' href='{$page->getHref()}'{$aParams}>{$page->getLabel()}</a></li>";
                    }
                }
                else {
                    $template .= "<li{$liParams}><a tabindex='-1' href='{$page->getHref()}'{$aParams}>{$page->getLabel()}</a></li>";
                }
            } else {
                $template .= "<li{$liParams}><a tabindex='-1' href='#'{$aParams}>{$additional}</a>";

                $this->level++;
                $template .= $this->drawBootstrapNavigationProfile($page->getPages(), $navigation);
                $this->level--;

                $template .= '</li>';
            }
        }

        $template .= '</ul>';

        return $template;
    }

    /**
     * @return string
     */
    protected function drawBootstrapNavigationNotifications()
    {
        return '<ul class="nav navbar-nav navbar-right navbar-notifications">
            <li class="dropdown">
                <a href="#" class="dropdown-toggle notifications-icon" data-toggle="dropdown">
                    <i class="glyphicon glyphicon-envelope"><span class="notifications-count" style="display:none"></span></i>
                </a>
                <ul class="dropdown-menu ul-notifications" role="menu" style="min-width:330px;">
                    <li class="see-all-notifcations">
                        <a href="/notification" class="text-center">
                            See all notifications <i class="fa fa-arrow-right"></i>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>';
    }

    /**
     * @param string $label
     * @param string $template
     *
     * @return bool
     */
    protected function checkForHeading($label, &$template)
    {
        if (strpos($label, '[') == 0 && strpos($label, ']') == strlen($label) - 1) {
            $label = substr($label, 1, strlen($label) - 2);
            $template .= "<li class='nav-separator dropdown-header nav-menu-header'>{$label}</li>";

            return true;
        }

        return false;
    }

    /**
     * @param string $label
     * @param string $template
     *
     * @return bool
     */
    protected function checkForSeparator($label, &$template)
    {
        if ($label == self::SEPARATOR) {
            $template .= '<li class="divider"></li>';

            return true;
        }

        return false;
    }
}
