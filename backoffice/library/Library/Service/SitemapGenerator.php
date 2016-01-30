<?php

namespace Library\Service;

use Zend\ServiceManager\ServiceLocatorInterface;
use Library\Constants\Objects;
use Library\Constants\DomainConstants;
use Library\Constants\WebSite;
use DDD\Service\Location;
use Library\Utility\Helper;
use DDD\Dao\Geolocation\Cities;
use DDD\Dao\Geolocation\Poi;

/**
 * Sitemap generator class
 *
 * @author Tigran Petrosyan
 */
class SitemapGenerator {

	/**
	 * @var ServiceLocatorInterface
	 */
	protected $serviceLocator;

	/**
	 *
	 * @param ServiceLocatorInterface $serviceLocator
	 */
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
		$this->serviceLocator = $serviceLocator;
	}

	/**
	 *
	 * @return ServiceLocatorInterface
	 */
	public function getServiceLocator() {
		return $this->serviceLocator;
	}

	/**
	 * @access private
	 * @var string
	 */
	private $urlBlockTemplate = '<url><loc>%1$s</loc><changefreq>daily</changefreq><priority>%2$s</priority></url>';

	/**
	 *
	 * @var string
	 */
	private $staticPages = array(
		'', // home page
		'blog',
		'about-us',
		'contact-us',
		'about-us/privacy-policy',
		'about-us/terms-and-conditions',
		'faq',
		'location',
		'jobs',
		'news',
	);

	/**
	 * @param bool|true $staticPages
	 * @param bool|true $apartmentPages
	 * @param bool|true $locationPages
	 * @param bool|true $blogPages
	 * @param bool|true $newsPages
	 * @return array
	 */
	public function generate($staticPages = true, $apartmentPages = true, $locationPages = true, $blogPages = true, $newsPages = true, $apartelPages = true) {

		$filepath = WebSite::SITE_MAP_PATH;

		// Let's make sure the file exists and is writable first.
//		if (is_writable($filepath)) {
//        } else {
//		    return array(
//		    	'status' => 'error',
//		    	'msg' => 'Sitemap file is not writable or doesn\'t exist.'
//		    );
//		}
            //$handle = (file_exists($filepath))? fopen($filepath, 'a') : fopen($filepath, 'w');
			if (!$handle = fopen($filepath, 'w')) {
				return array(
					'status' => 'error',
					'msg' => 'Sitemap file is not writable or doesn\'t exist.'
				);
			}

			// get languages
			$languagesService = $this->getServiceLocator()->get('service_language');
			$languages = $languagesService->getEnabledLanguagesIsoCodes();
			$languagesArray = array();
			foreach ($languages as $language) {
				$languagesArray[] = $language;
			}

			$xml = '<?xml version="1.0" encoding="UTF-8"?>';
			$xml .= '<urlset  xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

			if ($staticPages) {
				$xml .= $this->generateStaticPagesPart($languagesArray);
			}

			if ($apartmentPages) {
				$xml .= $this->generateApartmentPagesPart($languagesArray);
			}

            if ($apartelPages) {
                $xml .= $this->generateApartelPagesPart();
            }

			if ($locationPages) {
				$xml .= $this->generateLocationPagesPart($languagesArray);
			}

			if ($blogPages) {
				$xml .= $this->generateBlogPostsPart();
			}

            if($newsPages) {
                $xml .= $this->generateNewsPart();
            }

			$xml .= '</urlset>';

			if (fwrite($handle, $xml) === false) {
				return array(
					'status' => 'error',
					'msg' => 'Cannot write to file.'
				);
			}

			fclose($handle);

			return array(
				'status' => 'success',
				'msg' => 'Sitemap generation succesfully completed!'
			);

	}

	/**
	 * @access private
	 * @param array $languages
	 */
	private function generateStaticPagesPart($languages) {
		$partialXML = '';
		$priority = '0.9';
		foreach ($this->staticPages as $page) {
            $url = 'https://' . DomainConstants::WS_DOMAIN_NAME;
            if ($page != '') {
                $url .= '/' . $page;
                $priority = '1.0';
            } else {
                $priority = '0.9';
            }
            $partialXML .= sprintf($this->urlBlockTemplate, $url, $priority);
		}

		return $partialXML;
	}

	/**
	 *
	 * @access private
	 * @return string
	 */
	private function generateApartmentPagesPart($languages)
    {
        $partialXML = '';

        /* @var $oldAccommodationsService \DDD\Service\Accommodations */
        $oldAccommodationsService = $this->getServiceLocator()->get('service_accommodations');

        // get products data
        $apartments = $oldAccommodationsService->getProductSearchResult(array ("status" => Objects::PRODUCT_STATUS_LIVEANDSELLIG), false);

        /* @var $apartmentService \DDD\Service\Apartment\General */
        $apartmentService = $this->getServiceLocator()->get('service_apartment_general');

        foreach ($apartments as $apartment) {
            $url = 'https:' . $apartmentService->getWebsiteLink($apartment->getId()) . '?show=reviews';
            $partialXML .= sprintf($this->urlBlockTemplate, $url, '0.9');
        }

        return $partialXML;
    }

    /**
     * @return string
     */
	private function generateApartelPagesPart()
    {
        $partialXML = '';

        /**
         * @var \DDD\Dao\Apartel\General $apartmentDao
         */
        $apartmentDao = $this->getServiceLocator()->get('dao_apartel_general');

        $apartels = $apartmentDao->getApartelsForSitemap();

		foreach ($apartels as $apartel) {
            $url = 'https://' . DomainConstants::WS_DOMAIN_NAME . '/apartel/' . $apartel['slug'] . '--' . $apartel['city_slug'];
			$partialXML .= sprintf($this->urlBlockTemplate, $url, '0.9');
        }

		return $partialXML;
    }

    /**
	 * @access private
	 * @param array $languages
	 * @return string
	 */
	private function generateLocationPagesPart($languages)
    {
        $partialXML = '';

        $cityDao = new Cities($this->getServiceLocator(), 'ArrayObject');
        $poiDao  = new Poi($this->getServiceLocator(), 'ArrayObject');
        $cities  = $cityDao->getCityForLocation();

        foreach ($cities as $city) {
            $cityUrl = $city['city_url'] . '--' . $city['province_url'];
            $url     = 'https://' . DomainConstants::WS_DOMAIN_NAME . '/location/' . $cityUrl;
            $partialXML .= sprintf($this->urlBlockTemplate, $url, '0.9');

            //poi list
            $poiList = $poiDao->getAllPoisByCityID($city['id'], true);

            foreach ($poiList as $poi) {
                $url = 'https://' . DomainConstants::WS_DOMAIN_NAME . '/location/' . $cityUrl . '/' . $poi['slug'];
                $partialXML .= sprintf($this->urlBlockTemplate, $url, '0.9');
            }
        }

        return $partialXML;
    }

    /**
	 * @access private
	 * @return string
	 */
	private function generateBlogPostsPart() {
		$partialXML = '';

		// get blog posts
		/**
		 * @var $blogService \DDD\Service\Blog
		 */
		$blogService = $this->getServiceLocator()->get('service_blog');
		$blogPosts = $blogService->getBlogResult();

		foreach ($blogPosts as $blogPost) {
			$url = 'https://' . DomainConstants::WS_DOMAIN_NAME . '/blog/' . $blogPost->getSlug();
			$partialXML .= sprintf($this->urlBlockTemplate, $url, '0.9');
		}

		return $partialXML;
	}
	/**
	 * @return string
	 */
	private function generateNewsPart() {
		$partialXML = '';

		// get blog posts
		/**
		 * @var $newsService \DDD\Service\News
		 */
		$newsService = $this->getServiceLocator()->get('service_news');
		$newsPosts = $newsService->getNewsResult();

		foreach ($newsPosts as $news) {
			$url = 'https://' . DomainConstants::WS_DOMAIN_NAME . '/news/' . Helper::urlForSite($news->getEn_title());
			$partialXML .= sprintf($this->urlBlockTemplate, $url, '0.6');
		}

		return $partialXML;
	}
}