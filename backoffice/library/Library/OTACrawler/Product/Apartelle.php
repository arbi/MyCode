<?php

namespace Library\OTACrawler\Product;

use DDD\Service\Apartel\OTADistribution;
use Library\OTACrawler\Distributor;
use Library\OTACrawler\Interfaces\ParserInterface;
use Library\OTACrawler\Interfaces\Product;
use Library\OTACrawler\Parser;
use Library\OTACrawler\ProductEngine;

class Apartelle extends ProductEngine implements Product
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
        $acceptableOTAList = $this->getAcceptableOTAList();
        $otaList = $service->getOTAListFromArray($this->identityIdList, $acceptableOTAList);

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
