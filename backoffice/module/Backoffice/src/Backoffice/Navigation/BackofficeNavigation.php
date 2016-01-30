<?php

namespace Backoffice\Navigation;

use DDD\Service\User;
use Library\Constants\Constants;
use Library\Constants\DomainConstants;
use Library\Utility\Debug;
use Library\Utility\Helper;
use Zend\Authentication\AuthenticationService;
use Zend\Navigation\Service\DefaultNavigationFactory;
use Backoffice\View\Helper\Navigation as NavigationHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

class BackofficeNavigation extends DefaultNavigationFactory
{
    protected $serviceLocator = null;
    protected $availableGroups = null;
    protected $userData = null;
    protected $name;

    protected function getPages(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;

        if (is_null($this->pages)) {
            $configuration = $this->drawConfig($serviceLocator->get('Config'));

            if (!isset($configuration['navigation'])) {
                throw new \InvalidArgumentException('Could not find navigation configuration key');
            }

            if (!isset($configuration['navigation'][$this->getName()])) {
                throw new \InvalidArgumentException(sprintf(
                    'Failed to find a navigation container by the name "%s"',
                    $this->getName()
                ));
            }

            $pages = $this->getPagesFromConfig($configuration['navigation'][$this->getName()]);
            $this->pages = $this->preparePages($serviceLocator, $pages);
        }

        return $this->pages;
    }

    private function getPermissions() {
        /**
         * @var User $service
         */
        if (is_null($this->availableGroups)) {
            $service = $this->serviceLocator->get('service_user');
            $userData = $this->getUserData();
            $availableGroupList = $service->getUsersGroup($userData['id']);
            $availableGroups = [];

            foreach ($availableGroupList as $row){
                $availableGroups[] = $row['group_id'];
            }

            $this->availableGroups = $availableGroups;
        }

        return $this->availableGroups;
    }

    private function getUserData() {
        /**
         * @var AuthenticationService $auth
         */
        if (is_null($this->userData)) {
            $auth = $this->serviceLocator->get('library_backoffice_auth');

            if ($auth->hasIdentity()) {
                $userId = $auth->getIdentity()->id;
                $userName = $auth->getIdentity()->firstname . ' ' . $auth->getIdentity()->lastname;
                $userAvatar = $auth->getIdentity()->avatar;
            } else {
                throw new \Exception('User not defined.');
            }

            $boDomain = '//' . DomainConstants::BO_DOMAIN_NAME;
            $imgDomain = '//' . DomainConstants::IMG_DOMAIN_NAME;
            $version = Constants::VERSION;
            $avatarUrl = (isset($userAvatar) && !empty($userAvatar))
                ? "{$imgDomain}/profile/{$userId}/{$userAvatar}"
                : "{$boDomain}{$version}img/no.gif";

            $avatarUrl = file_exists(
                str_replace($imgDomain, '/ginosi/images', $avatarUrl)
            )
                ? $avatarUrl
                : "{$boDomain}{$version}img/no40.gif";

            $this->userData = [
                'id' => $userId,
                'name' => $userName,
                'avatar' => $avatarUrl,
            ];
        }

        return $this->userData;
    }

    private function drawConfig($config) {
        $output = [];

        $rootLevel = isset($config['navigation'])
            ? $config['navigation'][$this->getName()]
            : $config;

        if (is_array($rootLevel)) {
            $userData = $this->getUserData();

            foreach ($rootLevel as $menuItem) {
                $hasPermission = isset($menuItem['permission'])
                    ? is_array($menuItem['permission'])
                        ? array_intersect($this->getPermissions(), $menuItem['permission'])
                        : in_array($menuItem['permission'], $this->getPermissions())
                    : true;

                if ($hasPermission) {
                    if ($menuItem['label'] == NavigationHelper::USERNAME) {
                        $menuItem['label'] = $userData['name'];
                        $menuItem['icon'] = $userData['avatar'];
                    }

                    $output[] = [
                        'label' => $menuItem['label'],
                        'route' => isset($menuItem['route']) ? $menuItem['route'] : 'backoffice/default',
                        'controller' => isset($menuItem['controller']) ? $menuItem['controller'] : null,
                        'action' => isset($menuItem['action']) ? $menuItem['action'] : null,
                        'icon' => isset($menuItem['icon']) ? $menuItem['icon'] : '',
                    ];

                    if (isset($menuItem['pages'])) {
                        $children = $this->drawConfig($menuItem['pages']);

                        if (count($children)) {
                            $output[count($output) - 1]['pages'] = $this->drawConfig($menuItem['pages']);
                        } else {
                            array_pop($output);
                        }
                    }
                }
            }
        }

        return (isset($config['navigation'])
            ? [
                'navigation' => [
                    $this->getName() => $output
                ]
            ]
            : $output);
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }
}
