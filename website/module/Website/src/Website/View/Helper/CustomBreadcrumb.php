<?php

namespace Website\View\Helper;

use Website\View\Helper\BaseHelper;
use Zend\ServiceManager\ServiceManager;
use Library\Utility\Helper;
use DDD\Dao\Geolocation\Cities;
use DDD\Dao\Apartment\General;

class CustomBreadcrumb extends BaseHelper
{
    /**
     * @var
     */
     protected $_textLineSite = null;
    /**
	 *
	 * @var ServiceLocatorInterface
	 */
	protected $serviceLocator;
    /**
     *
     * @return string
     */
    public function __invoke()
    {
        $request    = $this->getServiceLocator()->get('request');
        $router     = $this->getServiceLocator()->get('router');
        $routeMatch = $router->match($request);
        if(!$routeMatch)
            return false;
        $controller = $routeMatch->getParam('controller');
        $action     = $routeMatch->getParam('action');
        $routeParam = $routeMatch->getParams();
        $getQuery   = $request->getQuery();
        $drowBreadcrumb = $this->drowBreadcrumb($controller, $action, $routeParam, $getQuery);
        return $drowBreadcrumb;
    }
    /**
     *
     * @param string $controller
     * @param string $action
     * @param string $routeParam
     * @param ArrayObject $getQuery
     * @return string
     */
    public function drowBreadcrumb($controller, $action, $routeParam, $getQuery){
        $getParams = $this->getBreadcrumbParams($controller, $action, $routeParam, $getQuery);
        $url = '<div role="navigation" aria-label="Breadcrumbs" class="hidden-xs"><ol class="breadcrumbs"><li class="luggage"><a href="' . '//' . \Library\Constants\DomainConstants::WS_DOMAIN_NAME . '">&amp;</a></li>';
        foreach ($getParams as $row){
            if($row['url']){
                $url .= '<li itemtype="http://data-vocabulary.org/Breadcrumb" itemscope="itemscope"><a href="'.$row['url'].'" itemprop="url"><span itemprop="title">'.$row['name'].'</span></a></li>';
            } else {
                $url .= '<li><span class="breadcrumbs-title">'.$row['name'].'</span></li>';
            }
        }
        $url .= '</ol></div>';
        return $url;
    }
    /**
     *
     * @param string $controller
     * @param string $action
     * @param string $routeParam
     * @param ArrayObject $getQuery
     * @return boolean
     */
    public function getBreadcrumbParams($controller = false, $action = false, $routeParam = false, $getQuery = false){
        $noSecureDomain = '//' . \Library\Constants\DomainConstants::WS_DOMAIN_NAME;

        if(!$controller || !$action) {
            return false;
        }
        $controller = strtolower($controller);
        $action = strtolower($action);
        $controller = str_replace('website\controller\\', '', $controller);
        $params[] = [
                        'name' => $this->getTextline(1449),
                        'url'  => '/'
                    ];
        switch ($controller){
            case 'search':
                $params[] = [
                               'name' => $this->getTextline(1322),
                               'url'  => '/location'
                            ];
                if($cityName = $this->getNameFromQuery($getQuery, 'city')){
                    $params[] = [
                                    'name' => $cityName,
                                    'url'  => ''
                                ];
                }
                break;
            case 'apartment':
                $apartmentTitle = (isset($routeParam['apartmentTitle']) ? $routeParam['apartmentTitle'] : '');
                $cityProvince = $provinceUrl = $apartment = $cityName = '';
                if($apartmentTitle){
                    $cityParams = explode('--', $apartmentTitle);
                    $cityUrl        = $cityParams[1];
                    $apartmentName  = $cityParams[0];
                    $generalDao     = new General($this->getServiceLocator(), 'ArrayObject');
                    $generalResult  = $generalDao->getBreadcrupDataByCityApartment($apartmentName, Helper::urlForSearch($cityUrl));
                    if($generalResult){
                        $provinceUrl = $generalResult['prov_name'];
                        $cityName    = $generalResult['city_name'];
                        $apartment   = $generalResult['name'];
                    }
                    $cityProvince = $cityUrl . '--' . Helper::urlForSite($provinceUrl);
                }
                $params[] = [
                               'name' => $this->getTextline(865),
                               'url'  => '/location'
                            ];
                if($cityName) {
                    $params[] = [
                                   'name' => $cityName,
                                   'url'  => '/location/'. $cityProvince
                                ];
                }
                if($apartment) {
                    $params[] = [
                                   'name' => $apartment . ' ' . $this->getTextline(1418),
                                   'url'  => ''
                                ];
                }
                break;
            case 'aboutus':
                if($action == 'index'){
                    $params[] = [
                                    'name' => $this->getTextline(1440),
                                    'url'  => ''
                                ];
                } elseif($action == 'privacy-policy'){
                    $params[] = [
                                    'name' => $this->getTextline(1371),
                                    'url'  => ''
                                ];
                } elseif($action == 'terms-and-conditions'){
                    $params[] = [
                                    'name' => $this->getTextline(1373),
                                    'url'  => ''
                                ];
                }
                break;
            case 'contactus':
                $params[] = [
                               'name' => $this->getTextline(1353),
                               'url'  => ''
                           ];
                break;
            case 'faq':
                $params[] = [
                               'name' => $this->getTextline(1379),
                               'url'  => ''
                           ];
                break;
            case 'location':
                if($action == 'index'){
                    $params[] = [
                                    'name' => $this->getTextline(1320),
                                    'url'  => ''
                                ];
                } else {
                    $params[] = [
                                    'name' => $this->getTextline(1320),
                                    'url'  => '/location'
                                ];
                    $cityProvince = (isset($routeParam['cityProvince']) ? $routeParam['cityProvince'] : '');
                    $locationService = $this->getServiceLocator()->get('service_website_location');
                    $cityResponse = $locationService->getCityByProvincCity($cityProvince);
                    $city = (isset($cityResponse['city_data']['city_name'])) ? $cityResponse['city_data']['city_name'] : '';
                    if(isset($routeParam['poi'])) {
                        $params[] = [
                                'name' => $city,
                                'url'  => '/location/'.$cityProvince
                            ];
                        $params[] = [
                                'name' => $this->getNameFromRoute($routeParam['poi'], 0),
                                'url'  => ''
                            ];
                    } else {
                        $params[] = [
                                'name' => $city,
                                'url'  => ''
                            ];
                    }
                }
                break;
                case 'blog':
                    if($action == 'index'){
                        $params[] = [
                                        'name' => $this->getTextline(1331),
                                        'url'  => ''
                                    ];
                    } else {
                        $blogTitel = (isset($routeParam['article']) ? $routeParam['article'] : '');
                        $params[] = [
                                        'name' => $this->getTextline(1331),
                                        'url'  => '/blog'
                                    ];
                        $params[] = [
                                        'name' => $this->getNameFromRoute($blogTitel, 0),
                                        'url'  => ''
                                    ];
                    }
                break;
                case 'news':
                    if($action == 'index'){
                        $params[] = [
                                        'name' => $this->getTextline(1417),
                                        'url'  => ''
                                    ];
                    } else {
                        $blogTitel = (isset($routeParam['article']) ? $routeParam['article'] : '');
                        $params[] = [
                                        'name' => $this->getTextline(1417),
                                        'url'  => '/news'
                                    ];
                        $params[] = [
                                        'name' => $this->getNameFromRoute($blogTitel, 0),
                                        'url'  => ''
                                    ];
                    }
                break;
            case 'jobs':
                if($action == 'index'){
                    $params[] = [
                        'name' => $this->getTextline(1488),
                        'url'  => ''
                    ];
                } else {
                    $announcementTitle = (isset($routeParam['slug']) ? $this->getNameFromRoute($routeParam['slug'], 0) : '');
                    $params[] = [
                        'name' => $this->getTextline(1488),
                        'url'  => '/jobs'
                    ];
                    $params[] = [
                        'name' => $this->getNameFromRoute($announcementTitle, 0),
                        'url'  => ''
                    ];
                }
        }
        foreach ($params as &$param) {
            //if secure change to www
            if (isset($param['url']) && $param['url'] != '') {
                $param['url'] = $noSecureDomain . $param['url'];
            }
        }

        return $params;
    }
    /**
     *
     * @param string $url
     * @param int $val
     * @param string $delimiter
     * @return string
     */
    private function getNameFromRoute($url, $val, $delimiter = '--'){
        $params = explode($delimiter, $url);
        $data = (isset($params[$val]) ? $params[$val] : $params[0]);
        return $this->explodeName($data);
    }
    /**
     *
     * @param ArrayObject $url
     * @param string $val
     * @return string|boolean
     */
    private function getNameFromQuery($url, $val)
    {
        if(!$url || !$val) {
            return false;
        }

        $newUrl = '';

        foreach ($url as $key=>$row){
            if ($key == $val) {
                $newUrl = $row;
            }
        }

        if (!$newUrl) {
            return false;
        }

        return $this->explodeName($newUrl);
    }
    /**
     *
     * @param string $data
     * @return string
     */
    private function explodeName($data){
        $name = '';
        $params = explode('-', $data);
        foreach ($params as $key=>$param){
            $name .= ($key ? ' ' : ''). ucfirst($param);
        }
        return $name;
    }
    /**
     *
     * @param int $id
     * @return string
     */
    private function getTextline($id){
         if ($this->_textLineSite === null) {
            $helperTextLine = new \Website\View\Helper\Textline();
            $helperTextLine->setServiceLocator($this->getServiceLocator());
            $this->_textLineSite = $helperTextLine;
        }
        return $this->_textLineSite->getFromCache($id);
    }
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
	public function setServiceLocator(ServiceManager $serviceLocator) {
		$this->serviceLocator = $serviceLocator;
	}
}
