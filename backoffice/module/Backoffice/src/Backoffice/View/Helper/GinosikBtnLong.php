<?php
namespace Backoffice\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

use Library\Constants\DomainConstants;
use Library\Constants\Constants;

class GinosikBtnLong extends AbstractHelper {
	public function __invoke($id, $ginosik, $avatar, $class = '', $disabled = false) {
        if ($disabled) {
            $disabled = ' disabled';
        }
        $avatar = str_replace('_150', '_40', $avatar);
        if (empty($avatar) || !file_exists('/ginosi/images/profile/' . $id . '/' . $avatar)) {
            $avatar = '//' . DomainConstants::BO_DOMAIN_NAME . Constants::VERSION . 'img/no40.gif';
        } else{
            $avatar = '//' . DomainConstants::IMG_DOMAIN_NAME . '/profile/' . $id . '/' . $avatar;
        }

$output = <<<Ginosik
<div class="btn btn-primary btn-sm btn-ginosik btn-ginosik-long {$class}"{$disabled}>
    <img src="{$avatar}" alt="{$ginosik}" title="{$ginosik}">
    <span>{$ginosik}</span>
</div>
Ginosik;
        return $output;
    }

    /**
     *
     * @access private
     * @var ServiceLocatorInterface
     */
    private $serviceLocator;

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
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
