<?php

namespace Library\OTACrawler;

use Library\OTACrawler\Exceptions;
use Library\OTACrawler\Interfaces\ParserInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

class CrawlerEngine extends Constants
{
    use ServiceLocatorAwareTrait;

    /**
     * @var ProductEngine $product
     */
    protected $product;
    protected $settings;
    protected $debug         = [];
    protected $echoFlag      = false;
    protected $crawlerStatus = self::CRAWLER_UPDATE;

    /**
     * @param ProductEngine $product
     * @param array $settings Now only for ota's
     */
    public function __construct(ProductEngine $product, array $settings = [])
    {
        $this->product  = $product;
        $this->settings = $settings;
    }

    public function prepareLastTemptation()
    {
        if (count(array_diff($this->settings, [
            self::OTA_BOOKING_COM,
            self::OTA_EXPEDIA,
            self::OTA_VENERE,
            self::OTA_AGODA,
            self::OTA_EASY_TO_BOOK,
            self::OTA_LATEROOMS,
            self::OTA_HOTELS_NL,
            self::OTA_YAHOO,
            self::OTA_ORBITZ
            ]))) {
            throw new Exceptions\CrawlerException('Unknown OTA selected.');
        }

        $this->product->setAcceptableOTAList($this->settings);
        $this->product->setServiceLocator(
            $this->getServiceLocator()
        );
    }

    /**
     * @return ServiceLocatorInterface
     * @throws Exceptions\CrawlerException
     */
    public function getServiceLocator()
    {
        if ($this->serviceLocator instanceof ServiceLocatorInterface) {
            return $this->serviceLocator;
        } else {
            throw new Exceptions\CrawlerException('Service Locator not defined.');
        }
    }

    /**
     * @param $infoString
     * @throws Exceptions\CrawlerException
     */
    public function setDebug($infoString)
    {
        if (is_scalar($infoString)) {
            if ($this->getEchoFlag()) {
                echo $infoString . PHP_EOL;
            } else {
                $this->debug[] = $infoString;
            }
        } else {
            throw new Exceptions\CrawlerException('Method accepts only scalar data.');
        }
    }

    /**
     * @return array
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * @return array
     */
    public function getEchoFlag()
    {
        return $this->echoFlag;
    }

    /**
     * @param bool $flag
     */
    public function setEchoFlag($flag)
    {
        $this->echoFlag = $flag;
    }

    /**
     * @param int $status
     * @throws Exceptions\CrawlerException
     */
    protected function setCrawlerStatus($status)
    {
        if (in_array($status, [self::CRAWLER_UPDATE, self::CRAWLER_CHECK])) {
            $this->crawlerStatus = $status;
        } else {
            throw new Exceptions\CrawlerException('Crawler status wrong.');
        }
    }

    protected function getCrawlerStatus()
    {
        return $this->crawlerStatus;
    }

    /**
     * @param DistributorItem $dist
     * @return ParserInterface|Parser
     */
    public function parse($dist)
    {
        return new ParserFactory($dist);
    }

    /**
     * @return ProductEngine
     */
    protected function getProduct()
    {
        return $this->product;
    }
}
