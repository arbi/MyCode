<?php

namespace Library\OTACrawler;

use Library\OTACrawler\Exceptions\ReaderException;

class Parser
{
    const PARSER_MISSING        = 10;
    const PARSER_EMPTY_URL      = 11;
    const PARSER_BAD_URL        = 12;
    const PARSER_PAGE_NOT_FOUND = 13;

    protected $distributorItem;
    protected $transport;
    protected $exception = null;

    /**
     * @param DistributorItem $distributorItem
     * @throws ReaderException
     */
    public function __construct(DistributorItem $distributorItem)
    {
        $this->distributorItem = $distributorItem;
        $this->transport = new Reader($distributorItem->getUrl());

        if (empty($this->transport->getUrl())) {
            throw new ReaderException('Url Cannot be empty.', self::PARSER_EMPTY_URL);
        }
    }

    /**
     * @return DistributorItem
     */
    public function getDistributionItem()
    {
        return $this->distributorItem;
    }

    /**
     * @param \Exception $exception
     */
    public function setException($exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param string $queryString
     * @return string
     */
    protected function getContent($queryString = '')
    {
        return $this->transport->getHTML($queryString);
    }
}
