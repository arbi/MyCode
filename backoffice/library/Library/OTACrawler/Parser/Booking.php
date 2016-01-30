<?php

namespace Library\OTACrawler\Parser;

use Library\OTACrawler\Interfaces\ParserInterface;
use Library\OTACrawler\OTACrawler;
use Library\OTACrawler\Parser;

class Booking extends Parser implements ParserInterface
{
    public function getSellingStatus()
    {
        try {
            $htmlData = str_replace("\n", '', $this->getContent());

            preg_match_all(
                '/<table.*id="maxotel_rooms".*>.*(show\s*prices).*\<\/span>/iU',
                $htmlData,
                $matches,
                PREG_SET_ORDER
            );

            if (count($matches) && count($matches[0]) > 1) {
                return OTACrawler::STATUS_SELLING;
            }

            return OTACrawler::STATUS_ISSUE;
        } catch (\Exception $e) {
            if ($e->getCode() === Parser::PARSER_PAGE_NOT_FOUND) {
                return OTACrawler::CRAWLER_STATUS_BAD_URL;
            } else {
                return OTACrawler::STATUS_ISSUE;
            }
        }
    }

    public function getDistributionName()
    {
        // do nothing for now
    }

    public function getScore()
    {
        // do nothing for now
    }
}
