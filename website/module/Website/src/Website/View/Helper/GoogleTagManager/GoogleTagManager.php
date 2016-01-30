<?php

namespace Website\View\Helper\GoogleTagManager;

use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;

/**
 * Class GoogleTagManager
 * @package Website\View\Helper\GoogleTagManager
 *
 * @author Tigran Petrosyan
 */
class GoogleTagManager extends AbstractHelper
{
    use ServiceLocatorAwareTrait;

    public function __invoke()
    {
        $config = $this->getServiceLocator()->get('config');

        $googleTagManagerId = $config['google-tag-manager']['id'];

        $environment = ucfirst(getenv('APPLICATION_ENV') ?: 'production');

        $scriptContainerName = 'Website\\View\Helper\\GoogleTagManager\\ScriptContainer\\' . ucfirst($environment) . 'Container';
        $scriptContainer = new $scriptContainerName();

        $scriptTemplate = $scriptContainer->getScriptTemplate();

        $script = sprintf($scriptTemplate, $googleTagManagerId, $googleTagManagerId);

        return $script;
    }
}
