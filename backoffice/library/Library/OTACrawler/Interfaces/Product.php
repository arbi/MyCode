<?php

namespace Library\OTACrawler\Interfaces;

use Library\OTACrawler\Parser;

interface Product
{
    public function getDistributionList();
    public function changeOTAStatus($parser);
    public function changeCrawlerStatus($distributionItemId, $crawlerStatus);
}
