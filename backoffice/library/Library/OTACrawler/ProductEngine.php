<?php

namespace Library\OTACrawler;

use DDD\Service\Apartel\OTADistribution as ApartelOTADistribution;
use DDD\Service\Apartment\OTADistribution as ApartmentOTADistribution;
use Library\OTACrawler\Exceptions\ParserException;
use Library\OTACrawler\Interfaces\ParserInterface;
use Library\OTACrawler\Product\Apartelle;
use Library\OTACrawler\Product\Apartment;
use Library\OTACrawler\Exceptions\CrawlerException;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Library\OTACrawler\Interfaces\Product;

class ProductEngine implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    const PRODUCT_APARTMENT = 1;
    const PRODUCT_APARTELLE = 2;

    protected $identityIdList = [];
    protected $type = null;
    protected $otaList = [];

    /**
     * @var ApartmentOTADistribution|ApartelOTADistribution|null
     */
    protected $service = null;

    /**
     * @param int|int[]|array $identity Apartment or Apartelle (Group) Id or list of one of this types
     * @throws Exceptions\CrawlerException
     */
    public function __construct($identity = null)
    {
        if (is_null($identity)) {
            // Do nothing. Let identityIdList empty
        } else {
            if (!is_array($identity)) {
                $identity = [$identity];
            }

            foreach ($identity as $identityId) {
                if ((int)$identityId > 0) {
                    array_push($this->identityIdList, (int)$identityId);
                } else {
                    throw new CrawlerException(get_parent_class($this) . ' Id cannot be equal or less than zero.');
                }
            }
        }
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
            throw new CrawlerException('Service Locator not defined. Please call Crawler::prepare() method before.');
        }
    }

    /**
     * @return array|ApartelOTADistribution|ApartmentOTADistribution|null|object
     * @throws Exceptions\CrawlerException
     */
    protected function getService()
    {
        if (is_null($this->service)) {
            if ($this instanceof Apartment) {
                $this->type = self::PRODUCT_APARTMENT;
                $this->service = $this->getServiceLocator()->get('service_apartment_ota_distribution');
            } elseif ($this instanceof Apartelle) {
                $this->type = self::PRODUCT_APARTELLE;
                $this->service = $this->getServiceLocator()->get('service_apartel_ota_distribution');
            } else {
                throw new CrawlerException('Undefined distributor detected.');
            }
        }

        return $this->service;
    }

    /**
     * @param array $otaList
     */
    public function setAcceptableOTAList($otaList)
    {
        $this->otaList = $otaList;
    }

    /**
     * @return array|bool
     */
    public function getAcceptableOTAList()
    {
        return $this->otaList;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Distributor|DistributorItem[]
     * @throws ParserException
     */
    public function getDistributionList()
    {
        throw new ParserException(__METHOD__ . ' not declared.');
    }

    /**
     * @param ParserInterface $parser
     * @return int
     * @throws Exceptions\ParserException
     *
     * @see \Library\OTACrawler\Product\Apartment::changeOTAStatus()
     * @see \Library\OTACrawler\Product\Apartelle::changeOTAStatus()
     */
    public function changeOTAStatus($parser)
    {
        throw new ParserException(__METHOD__ . ' not declared.');
    }

    /**
     * @param int $distributionId
     * @param int $status
     * @return int
     * @throws Exceptions\ParserException
     *
     * @see \Library\OTACrawler\Product\Apartment::changeCrawlerStatus()
     * @see \Library\OTACrawler\Product\Apartelle::changeCrawlerStatus()
     */
    public function changeCrawlerStatus($distributionId, $status)
    {
        throw new ParserException(__METHOD__ . ' not declared.');
    }
}
