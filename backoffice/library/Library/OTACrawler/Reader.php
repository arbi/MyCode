<?php

namespace Library\OTACrawler;

use Library\OTACrawler\Exceptions;

class Reader
{
    const METHOD_GET  = 0;
    const METHOD_POST = 1;

    protected $url;
    protected $content  = null;
    protected $method   = self::METHOD_GET;
    protected $postData = [];
    protected $headers  = [];

    /**
     * @param string $url
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * @todo Use Curl and also check http status
     *
     * @param string $queryString
     *
     * @return string
     * @throws Exceptions\CrawlerException
     * @throws Exceptions\ReaderException
     */
    public function getHTML($queryString = '')
    {
        $this->url = $this->url . $queryString;

        if (empty($this->url)) {
            throw new Exceptions\ReaderException('Url cannot be empty.', Parser::PARSER_EMPTY_URL);
        }

        if (filter_var($this->url, FILTER_VALIDATE_URL) === false) {
            throw new Exceptions\ReaderException('Url invalid.', Parser::PARSER_BAD_URL);
        }

        if (is_null($this->content)) {
            $headers = '';

            if (count($this->headers)) {
                foreach ($this->headers as $headerType => $headerValue) {
                    if (!empty($headers)) {
                        $headers .= '\r\n';
                    }

                    $headers .= $headerType . ': ' . $headerValue;
                }
            }

            $opts = ['http' => [
                'method' => ($this->method) ? 'POST' : 'GET',
                'header' => $headers,
            ]];

            if ($this->method === self::METHOD_POST) {
                if (count($this->postData)) {
                    $opts['http']['content'] = http_build_query($this->postData);
                }
            }

            $context = stream_context_create($opts);

            $this->content = @file_get_contents($this->url, false, $context);
        }

        if (empty($this->content)) {
            throw new Exceptions\ReaderException('Canot read content. Maybe url is broken.', Parser::PARSER_PAGE_NOT_FOUND);
        }

        return $this->content;
    }

    /**
     * @param int $method
     * @return int
     */
    public function setRequestMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @param array $data
     * @return array
     */
    public function setPostData(array $data)
    {
        $this->postData = $data;
    }

    /**
     * @param array $headers
     * @return array
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param string $url
     * @return string
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
