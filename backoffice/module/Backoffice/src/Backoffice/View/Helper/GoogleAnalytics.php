<?php

namespace Backoffice\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceManager;

/**
 * Class GoogleAnalytics
 * @package Backoffice\View\Helper
 */
class GoogleAnalytics extends AbstractHelper
{
    protected $serviceLocator;

    public function __invoke($options = array())
    {
        $userId = 0;

        $auth = $this->serviceLocator->get('library_backoffice_auth');
        if ($auth->hasIdentity()) {
            $userId = $auth->getIdentity()->id;
        }
        if ($userId) {
            $userMainService = $this->serviceLocator->get('service_user_main');
            $userInfo = $userMainService->getUserInfoForGoogleAnalytics($userId);
            return $this->makeGoogleAnalyticsCode($userInfo);
        } else {
            return '';
        }
    }

    /**
     * @param $data
     * @return string
     */
    protected function makeGoogleAnalyticsCode($data)
    {
        $environment = getenv('APPLICATION_ENV') ?: 'production';
        $script = '';

        if ($environment === 'production') {
            $script =
                '<script>
                    dataLayer = [{
                        "uid" : "' . $data['id'] . '",
                        "userCity" : "' . $data['working_city'] . '",
                        "userDept" : "' . $data['department'] . '"
                    }];
                </script>
                <noscript>
                    <iframe src="//www.googletagmanager.com/ns.html?id=GTM-W9XTW2" height="0" width="0" style="display:none;visibility:hidden"></iframe>
                </noscript>'.
                "<script>
                    (
                        function (w,d,s,l,i) {
                            w[l]=w[l]||[];
                            w[l].push({
                                'gtm.start':new Date().getTime(),
                                event:'gtm.js'
                            });
                            var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
                            j.async=true;
                            j.src='//www.googletagmanager.com/gtm.js?id='+i+dl;
                            f.parentNode.insertBefore(j,f);
                        }
                    )
                    (
                        window,
                        document,
                        'script',
                        'dataLayer',
                        'GTM-W9XTW2'
                    );
                </script>";
        }

        return $script;
    }

    public function setServiceLocator(ServiceManager $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
}
