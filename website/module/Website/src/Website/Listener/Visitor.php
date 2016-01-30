<?php

namespace Website\Listener;

use DDD\Service\Partners;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventInterface;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container as SessionContainer;
use Zend\Http\Header\SetCookie;
use Library\Constants\WebSite;

use DDD\Service\GeoliteCountry;
use DDD\Service\Partners as PartnerService;

use UAParser\Parser;
use ZF2Graylog2\Traits\Logger;

class Visitor implements ListenerAggregateInterface
{
    /**
     * @var array
     */
    protected $listeners = [];

    /**
     * @var ServiceLocatorInterface
     */
    private $serviceLocator;

    /**
     * @var SessionContainer
     */
    protected $visitorSessionContainer;

    protected $cookie          = false;
    protected $requestHeaders  = false;
    protected $responseHeaders = false;
    protected $cookieDomain    = false;

    /**
     * @param EventManagerInterface $eventManager
     */
    public function attach(EventManagerInterface $eventManager)
    {
        $this->listeners[] = $eventManager->attach('detectVisitor', [$this, 'parseEvents']);
    }

    /**
     * @param EventManagerInterface $eventManager
     */
    public function detach(EventManagerInterface $eventManager)
    {
        foreach ($this->listeners as $index => $listener) {
            if ($eventManager->detach($listener)) {
                unset($this->listeners[$index]);
            }
        }
    }

    /**
     * @param EventInterface $event
     */
    public function parseEvents(EventInterface $event)
    {
        // initialize session container
        $this->startSession($event->getParam('sessionManager'));

        $this->serviceLocator   = $event->getParam('serviceLocator');
        $this->responseHeaders  = $event->getParam('responseHeaders');
        $this->requestHeaders   = $event->getParam('requestHeaders');
        $this->cookie           = $this->requestHeaders->get('Cookie');

        $appConfig = $event->getParam('serviceLocator')->get('config');
        $this->cookieDomain = $appConfig['session']['cookie_domain'];

        // IP
        if (strstr('127.0.0.1', $event->getParam('clientIp'))
            || strstr('192.168.', $event->getParam('clientIp'))
        ) {
            // change for test
            $this->setIp('37.157.217.124'); // AM - our uCom ip
//            $this->setIp('195.250.68.1'); // AM
//            $this->setIp('87.242.75.108'); // RU
//            $this->setIp('173.252.74.22'); // US
//            $this->setIp('146.185.42.33'); // FR
            $this->setIp('77.73.57.78'); // IT
//            $this->setIp('80.241.247.222'); // GE
        } else {
            $this->setIp($event->getParam('clientIp'));
        }

        // LANGUAGE
        // if we have query param to set lang (example: /?lang=en)
        if ($event->getParam('setLang')['lang']) {

            if (in_array($event->getParam('setLang')['lang'], $this->supportedLanguages())) {
                $this->setLanguage($event->getParam('setLang')['lang']);
            }

            $cleanedParams = (strpos($event->getParam('setLang')['url']->getQuery(), '&'))
                ? '?'.str_replace(['&lang=', 'lang=', $event->getParam('setLang')['lang']],
                    '',
                    $event->getParam('setLang')['url']->getQuery())
                : '';
            $cleanedParams = str_replace('?&', '?', $cleanedParams);

            $responseUrl = $event->getParam('setLang')['url']->getScheme().'://'
                .$event->getParam('setLang')['url']->getHost()
                .$event->getParam('setLang')['url']->getPath()
                .$cleanedParams;

            $response = $event->getParam('response');
            $response->setHeaders($response->getHeaders()->addHeaderLine('Location', $responseUrl));
            $response->setStatusCode(302);
            $response->sendHeaders();
            exit();
        }

        if (!$this->visitorSessionContainer->language
            OR !in_array($this->visitorSessionContainer->language, $this->supportedLanguages())
            OR !isset($this->cookie['language'])
            OR !in_array($this->cookie['language'], $this->supportedLanguages())
        ) {
            $this->detectLanguage(
                $event->getParam('browserLang'),
                $event->getParam('clientIp')
            );
        }
        // TODO: simple SET LANG when requested /?ln=new_lang

        // CURRENCY
        // if we have query param to set currency (example: /?cur=USD)
        if ($event->getParam('setCurrency')['cur']) {

            $selectedCurrency = strtoupper($event->getParam('setCurrency')['cur']);

            if (in_array($selectedCurrency, $this->supportedCurrencies())) {
                $this->setCurrency($selectedCurrency);
            }

            $cleanedParams = (strpos($event->getParam('setCurrency')['url']->getQuery(), '&'))
                ? '?'.str_replace(['&cur=', 'cur=', $event->getParam('setCurrency')['cur']],
                    '',
                    $event->getParam('setCurrency')['url']->getQuery())
                : '';
            $cleanedParams = str_replace('?&', '?', $cleanedParams);

            $responseUrl = $event->getParam('setCurrency')['url']->getScheme().'://'
                .$event->getParam('setCurrency')['url']->getHost()
                .$event->getParam('setCurrency')['url']->getPath()
                .$cleanedParams;

            $response = $event->getParam('response');
            $response->setHeaders($response->getHeaders()->addHeaderLine('Location', $responseUrl));
            $response->setStatusCode(302);
            $response->sendHeaders();
            exit();
        }

        if (   !$this->visitorSessionContainer->currency
            || !in_array($this->visitorSessionContainer->currency, $this->supportedCurrencies())
            || !isset($this->cookie['currency'])
            || !in_array($this->cookie['currency'], $this->supportedCurrencies())
        ) {
            $this->detectCurrency(
                $event->getParam('browserLang'),
                $event->getParam('clientIp')
            );
        }

        // REFERER
        $this->setReferer($event->getParam('referer'));

        // LANDING PAGE
        $this->setLandingPage($event->getParam('request')->getUri());

        // TODO: simple SET CURRENCY when requested /?cur=new_cur
        // GID -PARTNER ID

        if (!is_null($this->visitorSessionContainer->partnerName)) {
            /**
             * @var \DDD\Dao\Partners\Partners $partnerDao
             */
            $partnerDao  = $event->getParam('serviceLocator')->get('dao_partners_partners');
            $partnerData = $partnerDao->fetchOne(
                ['partner_name' => $this->visitorSessionContainer->partnerName],
                ['show_partner']
            );

            if ($partnerData) {
                $this->visitorSessionContainer->showPartner = $partnerData->getShowPartner();
            }
        }

        if (   isset($this->cookie['backoffice_user'])
            && $this->cookie['backoffice_user']
            && !isset($this->visitorSessionContainer->partnerId)
        ) {
            $this->setPartner(PartnerService::GINOSI_CONTACT_CENTER, $event);
        }

        if (   !isset($this->cookie['backoffice_user'])
            && !$event->getParam('setPartnerId')['gid']
            && ($this->visitorSessionContainer->partnerId == PartnerService::GINOSI_CONTACT_CENTER)
        ) {
            unset($this->visitorSessionContainer->partnerId);
            unset($this->visitorSessionContainer->partnerName);
        }

        if ($event->getParam('setPartnerId')['gid']) {
            // TODO: check whether there is a partner with this id
            if (true) {
                $this->setPartner($event->getParam('setPartnerId')['gid'], $event);
            }

            $cleanedParams = (strpos($event->getParam('setPartnerId')['url']->getQuery(), '&'))
                ? '?' . preg_replace('/(\&gid\=|gid\=)[0-9]+/', '', $event->getParam('setPartnerId')['url']->getQuery())
                : '';

            $cleanedParams = str_replace('?&', '?', $cleanedParams);

            $responseUrl = $event->getParam('setPartnerId')['url']->getScheme().'://'
                .$event->getParam('setPartnerId')['url']->getHost()
                .$event->getParam('setPartnerId')['url']->getPath()
                .$cleanedParams;
            $this->visitorSessionContainer->referer_host = NULL;
            $response = $event->getParam('response');
            $response->setHeaders($response->getHeaders()->addHeaderLine('Location', $responseUrl));

            $response->setStatusCode(302);
            $response->sendHeaders();
            exit();
        } elseif (!empty($this->visitorSessionContainer->referer_host)) {
            $searchEngines = WebSite::getSearchEngineList();

            foreach ($searchEngines as $engineName => $gid) {
                if(strstr($this->visitorSessionContainer->referer_host, $engineName)) {
                    $this->setPartner($gid, $event);
                }
            }
        }

        // COUNTRY
        if ($this->getIp()) {
            $this->detectCountryByIpAddressAndStoreInSession();
        }

        // BROWSER / OS / DEVICE
        $this->parseUserAgentAndStoreInSession($event->getParam('userAgent'));
    }

    /**
     * @param $sessionManager
     */
    private function startSession($sessionManager)
    {
        SessionContainer::setDefaultManager($sessionManager);
        $this->visitorSessionContainer = new SessionContainer('visitor');
    }

    /**
     * Returns Ginosi supported languages. Currently only English
     * @return array
     */
    private function supportedLanguages()
    {
        return [
            'default' => 'en', // Ginosi Default Language
        ];
    }

    /**
     * @return array
     */
    private function supportedCurrencies()
    {
        return [
            'en-US'   => 'USD', // English (United States)
            'en-GB'   => 'GBP', // English (United Kingdom)
            'en-AU'   => 'USD', // English (Australia)
            'en-ZA'   => 'USD', // English (South Africa)
            'en-CA'   => 'USD', // English (Canada)
            'en-NZ'   => 'USD', // English (New Zealand)
            'en'      => 'USD', // English
            'nl'      => 'EUR', // Dutch

            'fr-CA'   => 'EUR', // French (Canada)
            'fr-CH'   => 'EUR', // French (Switzerland)
            'fr-FR'   => 'EUR', // French (France)
            'fr'      => 'EUR', // French

            'it-IT'   => 'EUR', // Italian (Italy)
            'it-CH'   => 'EUR', // Italian (Switzerland)
            'it'      => 'EUR', // Italian

            'de-DE'   => 'EUR', // German (Germany)
            'de-CH'   => 'EUR', // German (Switzerland)
            'de-AT'   => 'EUR', // German (Austria)
            'de'      => 'EUR', // German

            'ru'      => 'RUB', // Russian
            'be'      => 'USD', // Belarusian
            'uk'      => 'USD', // Ukrainian

            'hy'      => 'AMD', // Armenian

            'ka'      => 'GEL', // Georgian

            'default' => 'USD', // Ginosi Default Language
        ];
    }

    /**
     * @return bool|mixed
     */
    private function getIp()
    {
        if (isset($this->visitorSessionContainer->ip)) {
            return $this->visitorSessionContainer->ip;
        }

        return false;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip = '127.0.0.1')
    {
        $this->visitorSessionContainer->ip = $ip;
    }

    /**
     * @param $browserLang
     * @return bool
     */
    private function detectCurrency($browserLang)
    {
        $supported = $this->supportedCurrencies();

        // check isset true language in cookie
        if (isset($this->cookie['currency'])
            && in_array($this->cookie['currency'], $supported))
        {
            return $this->setCurrency($this->cookie['currency']);
        }

        // detect language from user agent header (Accept-Language)
        if ($browserLang) {
            foreach ($browserLang as $locale) {
                if (array_key_exists($locale->typeString, $supported)) {
                    // The locale is one of our supported list
                    return $this->setCurrency($supported[$locale->typeString]);
                    break;
                }
            }
        }

        // set to default language
        return $this->setCurrency($supported['default']);
    }

    /**
     * @param string $currency
     * @return bool
     * @todo catch exception and return false?
     */
    public function setCurrency($currency = 'USD')
    {
        try {
            $this->visitorSessionContainer->currency = $currency;

            $newCurrencyCookie = new SetCookie('currency', $currency, time() + 365 * 60 * 60 * 24, '/', $this->cookieDomain); // now + 1 year
            $this->responseHeaders->addHeader($newCurrencyCookie);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $partnerId
     * @return bool
     * @todo catch exception and return false?
     */
    public function setPartner($partnerId)
    {
        try {
            /**
             * @var \DDD\Dao\Partners\Partners $partnerDao
             */
            $partnerDao = $this->serviceLocator->get('dao_partners_partners');
            $partnerData = $partnerDao->getPartnerById($partnerId);
            if ($partnerData) {
                $partnerName = $partnerData->getPartnerName();
                $this->visitorSessionContainer->partnerId   = $partnerId;
                $this->visitorSessionContainer->partnerName = $partnerName;
                $this->visitorSessionContainer->showPartner = $partnerData->getShowPartner();

                return true;
            } else {
                $partnerData = $partnerDao->getPartnerById(PartnerService::GINOSI_PARTNER_WEBSITE);

                if ($partnerData) {
                    $partnerName = $partnerData->getPartnerName();

                    $this->visitorSessionContainer->partnerId   = PartnerService::GINOSI_PARTNER_WEBSITE;
                    $this->visitorSessionContainer->partnerName = $partnerName;
                    return true;
                } else {
                    return false;
                }
            }
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * @return bool|mixed
     */
    public function getReferer()
    {
        if (isset($this->visitorSessionContainer->referer)) {
            return $this->visitorSessionContainer->referer;
        }

        return false;
    }

    /**
     * @param $referer
     */
    private function setReferer($referer)
    {
        if (empty($this->getReferer()) && $referer) {
            if (   !preg_match('/(.*)ginosi.com/', $referer->uri()->getHost())
                || preg_match('/(.*)backoffice.ginosi.com/', $referer->uri()->getHost())
            ) {
                $this->visitorSessionContainer->referer      = $referer->getUri();
                $this->visitorSessionContainer->referer_host = $referer->uri()->getHost();
            } else {
                $this->visitorSessionContainer->referer      = 'No Referrer';
                $this->visitorSessionContainer->referer_host = 'Direct';
            }
        } elseif (!isset($this->visitorSessionContainer->referer_host)) {
            $this->visitorSessionContainer->referer_host = null;
        }
    }

    /**
     * @return bool|mixed
     */
    private function getLandingPage()
    {
        if (isset($this->visitorSessionContainer->landing_page)) {
            return $this->visitorSessionContainer->landing_page;
        }

        return false;
    }

    /**
     * @param $requestUri
     */
    public function setLandingPage($requestUri)
    {
        if (preg_match('/(.*)\.ginosi.com/', $requestUri->getHost())) {
            if (!$this->getLandingPage()) {
                $landingPage = $requestUri->getScheme() . '://' . $requestUri->getHost() . $requestUri->getPath();

                if (!empty($requestUri->getQuery())) {
                    $landingPage .= '?' . $requestUri->getQuery();
                }

                $this->visitorSessionContainer->landing_page = $landingPage;
            }
        }
    }

    /**
     * @param $userAgent
     */
    private function parseUserAgentAndStoreInSession($userAgent)
    {
        $parser = Parser::create();
        $result = $parser->parse($userAgent);

        if (!isset($this->visitorSessionContainer->ua->family)) {
            $this->visitorSessionContainer->ua_major  = $result->ua->major;
            $this->visitorSessionContainer->ua_minor  = $result->ua->minor;
            $this->visitorSessionContainer->ua_patch  = $result->ua->patch;
            $this->visitorSessionContainer->ua_family = $result->ua->family;

            $this->visitorSessionContainer->os_major      = $result->os->major;
            $this->visitorSessionContainer->os_minor      = $result->os->minor;
            $this->visitorSessionContainer->os_patch      = $result->os->patch;
            $this->visitorSessionContainer->os_patchMinor = $result->os->patchMinor;
            $this->visitorSessionContainer->os_family     = $result->os->family;

            $this->visitorSessionContainer->device_brand  = $result->device->brand;
            $this->visitorSessionContainer->device_model  = $result->device->model;
            $this->visitorSessionContainer->device_family = $result->device->family;
        }
    }

    /**
     * @param string $languageIsoCode
     * @return bool
     */
    private function setLanguage($languageIsoCode = 'en')
    {
        $this->visitorSessionContainer->language = $languageIsoCode;

        $newLangCookie = new SetCookie('language', $languageIsoCode, time() + 365 * 60 * 60 * 24, '/', $this->cookieDomain); // now + 1 year
        $this->responseHeaders->addHeader($newLangCookie);

        return true;
    }

    private function detectLanguage($browserLang)
    {
        $supported = $this->supportedLanguages();

        // check isset true language in cookie
        if (   isset($this->cookie['language'])
            && in_array($this->cookie['language'], $supported)
        ) {
            return $this->setLanguage($this->cookie['language']);
        }

        $detectedLanguages = [];
        $sustainableDetectedLanguages = [];

        // detect language from user agent header (Accept-Language)
        if ($browserLang) {
            foreach ($browserLang as $locale) {
                $detectedLanguages[] = $locale->typeString;
                if (array_key_exists($locale->typeString, $supported)) {
                    $sustainableDetectedLanguages[] = $locale->typeString;
                    // The locale is one of our supported list
                }
            }

            if (count($detectedLanguages)) {
                $this->visitorSessionContainer->ua_languages = implode(', ', $detectedLanguages);
            }

            if (count($sustainableDetectedLanguages)) {
                return $this->setLanguage($sustainableDetectedLanguages[0]); // set to first sustainable detected lang
            }
        }

        // set to default language
        return $this->setLanguage($supported['default']);
    }

    /**
     * Detect visitor country details by IP address using local MaxMind's Geolite Country database
     * And store it in session
     * @return bool
     */
    private function detectCountryByIpAddressAndStoreInSession()
    {
        /**
         * @var GeoliteCountry $geoLiteCountryService
         */
        $geoLiteCountryService = $this->serviceLocator->get('service_geolite_country');

        $countryData = $geoLiteCountryService->getCountryDataByIp($this->getIp());

        if (!$countryData) {
            return false;
        }

        $this->visitorSessionContainer->country_id     = $countryData['country_id'];
        $this->visitorSessionContainer->country_iso    = $countryData['country_iso'];
        $this->visitorSessionContainer->country_name   = $countryData['country_name'];

        return true;
    }
}
