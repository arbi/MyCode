<?php

namespace Library\OTACrawler;

use Library\OTACrawler\Exceptions;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * Class OTACrawler
 * Life is nothing without a little chaos to make it interesting.
 *
 * @package Library\OTACrawler
 */
class OTACrawler extends CrawlerEngine implements ServiceLocatorAwareInterface
{
    /**
     * @throws Exceptions\CrawlerException
     * @throws \Exception
     */
    public function update()
    {
        try {
            $this->prepareLastTemptation();

            foreach ($this->product->getDistributionList() as $dist) {
                $productName = $this->product->getType() == ProductEngine::PRODUCT_APARTMENT ? 'Apartment' : 'Apartelle';
                $crawlerStatus = self::CRAWLER_STATUS_UNKNOWN;

                try {
                    $parser = $this->parse($dist);

                    if ($this->getCrawlerStatus() == self::CRAWLER_UPDATE) {
                        if ($dist->getStatus() != $parser->getSellingStatus()) {
                            $this->product->changeOTAStatus($parser);
                        }
                    }

                    if ($parser->getSellingStatus() == OTACrawler::STATUS_SELLING) {
                        $this->setDebug("{$productName}: {$dist->getIdentityId()}, {$dist->getPartnerName()} - Selling");
                        $crawlerStatus = self::CRAWLER_STATUS_OK;
                    } elseif ($parser->getSellingStatus() == OTACrawler::STATUS_ISSUE) {
                        $this->setDebug("{$productName}: {$dist->getIdentityId()}, {$dist->getPartnerName()} - Not Selling");
                        $crawlerStatus = self::CRAWLER_STATUS_OK;
                    } else {
                        $this->setDebug("{$productName}: {$dist->getIdentityId()}, {$dist->getPartnerName()} - Unknown");
                        $lastException = $parser->getException();

                        if (!is_null($lastException) && $lastException instanceof \Exception) {
                            $this->setDebug('Error message from Parser: ' . $lastException->getMessage());
                        }
                    }
                } catch (\Exception $ex) {
                    if ($ex instanceof Exceptions\ParserException) {
                        if ($ex->getCode() == Parser::PARSER_MISSING) {
                            $this->setDebug("{$productName}: {$dist->getIdentityId()}, {$dist->getPartnerName()} - Parser Missing");
                            $crawlerStatus = self::CRAWLER_STATUS_PARSER_NOT_FOUND;
                        }
                    }

                    if ($ex instanceof Exceptions\ReaderException) {
                        $this->setDebug("{$productName}: {$dist->getIdentityId()}, {$dist->getPartnerName()} - Invalid Url");

                        switch ($ex->getCode()) {
                            case Parser::PARSER_EMPTY_URL:
                                $crawlerStatus = self::CRAWLER_STATUS_EMPTY_URL;
                                break;
                            case Parser::PARSER_BAD_URL:
                                $crawlerStatus = self::CRAWLER_STATUS_BAD_URL;
                                break;
                            case Parser::PARSER_PAGE_NOT_FOUND:
                                $crawlerStatus = self::CRAWLER_STATUS_PAGE_NOT_FOUND;
                                break;
                        };
                    }
                }

                if ($dist->getOtaStatus() != $crawlerStatus) {
                    $this->product->changeCrawlerStatus($dist->getId(), $crawlerStatus);
                }
            }
        } catch (Exceptions\CrawlerException $crawlerEx) {
            throw new Exceptions\CrawlerException('Crawler Error!', 0, $crawlerEx);
        } catch (Exceptions\ParserException $crawlerEx) {
            throw new Exceptions\ParserException('Parser Error!', 0, $crawlerEx);
        } catch (\Exception $ex) {
            throw new \Exception('Some weird error!', 0, $ex);
        }
    }

    public function check()
    {
        $this->setCrawlerStatus(self::CRAWLER_CHECK);
        $this->update();
    }
}
