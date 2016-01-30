<?php

namespace Library\OTACrawler\Parser;

use Library\OTACrawler\Interfaces\ParserInterface;
use Library\OTACrawler\OTACrawler;
use Library\OTACrawler\Parser;

class Venere extends Parser implements ParserInterface
{
    public function getSellingStatus()
    {
        try {
            $htmlData = str_replace("\n", '', $this->getContent());

            preg_match_all(
                '/class="button btn-green".*>(.*)\<\/span>/iU',
                $htmlData,
                $matches,
                PREG_SET_ORDER
            );

            if (count($matches) && isset($matches[0][0])) {
                if (strpos(strtolower(strip_tags($matches[0][0])), 'hotelstrip-book-txt') !== false) {
                    return OTACrawler::STATUS_SELLING;
                }
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
