<?php

namespace Library\OTACrawler\Interfaces;

use Library\OTACrawler\Parser;

interface ParserInterface
{
    public function getSellingStatus();
    public function getDistributionName();
    public function getScore();
}
