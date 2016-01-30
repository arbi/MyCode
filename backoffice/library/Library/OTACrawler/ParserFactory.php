<?php

namespace Library\OTACrawler;

use Library\OTACrawler\Exceptions\CrawlerException;
use Library\OTACrawler\Exceptions\ParserException;
use Library\OTACrawler\Exceptions\PartnerException;
use Library\OTACrawler\Interfaces\ParserInterface;
use Library\OTACrawler\Parser;
use Library\Utility\Debug;

/**
 * Class ParserFactory
 * @package Library\OTACrawler
 *
 * @method int getStatus()
 * @method string getDistributionName()
 * @method float getScore()
 *
 * @see ParserInterface
 */
class ParserFactory
{
    /**
     * @var ParserInterface
     */
    protected $parser;

    /**
     * @param DistributorItem $distributorItem
     * @throws PartnerException
     */
    public function __construct(DistributorItem $distributorItem)
    {
        switch ($distributorItem->getPartnerId()) {
            case OTACrawler::OTA_BOOKING_COM:
                $this->parser = new Parser\Booking($distributorItem);
                break;
            case OTACrawler::OTA_EXPEDIA:
                $this->parser = new Parser\Expedia($distributorItem);
                break;
            case OTACrawler::OTA_VENERE:
                $this->parser = new Parser\Venere($distributorItem);
                break;
            case OTACrawler::OTA_AGODA:
                $this->parser = new Parser\Agoda($distributorItem);
                break;
            case OTACrawler::OTA_EASY_TO_BOOK:
                $this->parser = new Parser\EasyToBook($distributorItem);
                break;
            case OTACrawler::OTA_LATEROOMS:
                $this->parser = new Parser\Laterooms($distributorItem);
                break;
            case OTACrawler::OTA_HOTELS_NL:
                $this->parser = new Parser\HotelsNL($distributorItem);
                break;
            case OTACrawler::OTA_ORBITZ:
                $this->parser = new Parser\Orbitz($distributorItem);
                break;
            default:
                throw new PartnerException(
                    "Respective Parser not defined for partner {$distributorItem->getPartnerName()} (id: " . $distributorItem->getPartnerId() . ').', Parser::PARSER_MISSING
                );
        }
    }

    public function __call($method, $arguments)
    {
        if (is_null($this->parser)) {
            throw new ParserException('Parser not defined.');
        }

        if (method_exists($this->parser, $method)) {
            return $this->parser->{$method}();
        } else {
            throw new ParserException('Bad property name.');
        }
    }
}
