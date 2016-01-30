<?php

namespace Library\OTACrawler\Product;

use DDD\Service\Apartment\OTADistribution;
use Library\Constants\Objects;
use Library\OTACrawler\Distributor;
use Library\OTACrawler\Interfaces\ParserInterface;
use Library\OTACrawler\Interfaces\Product;
use Library\OTACrawler\Parser;
use Library\OTACrawler\ProductEngine;

class Apartment extends ProductEngine implements Product
{
    /**
     * @return \ArrayObject|Distributor|\Library\OTACrawler\DistributorItem[]
     */
    public function getDistributionList()
    {
        /**
         * @var OTADistribution $service
         */
        $service = $this->getService();
        $acceptableStatusList = [Objects::PRODUCT_STATUS_LIVEANDSELLIG, Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE];
        $acceptableOTAList = $this->getAcceptableOTAList();

        $otaList = $service->getOTAListFromArray($this->identityIdList, $acceptableStatusList, $acceptableOTAList);

        return (
            new Distributor($otaList)
        )->getAll();
    }

    /**
     * @param ParserInterface|Parser $parser
     * @return bool
     */
    public function changeOTAStatus($parser)
    {
        /**
         * @var OTADistribution $service
         */
        $service = $this->getService();
        $status = $parser->getSellingStatus();
        $distributionItem = $parser->getDistributionItem();

        return $service->changeOTASellingStatus($distributionItem->getId(), $status);
    }

    /**
     * @param int $distributionItemId
     * @param int $crawlerStatus
     * @return bool
     */
    public function changeCrawlerStatus($distributionItemId, $crawlerStatus)
    {
        /**
         * @var OTADistribution $service
         */
        $service = $this->getService();

        return $service->changeOTACrawlerStatus($distributionItemId, $crawlerStatus);
    }
}
