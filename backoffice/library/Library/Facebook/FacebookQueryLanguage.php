<?php

namespace Library\Facebook;

class FacebookQueryLanguage
{
    /**
     * @var string
     */
    private $queryUrl = 'https://api.facebook.com/method/fql.query?query=';

    /**
     * @var bool
     */
    private $isTotalIncluded = false;

    /**
     * @var bool
     */
    private $isLikeIncluded = false;

    /**
     * @var bool
     */
    private $isCommentIncluded = false;

    /**
     * @var bool
     */
    private $isShareIncluded = false;

    /**
     * @var bool
     */
    private $isClickIncluded = false;

    /**
     * @var array
     */
    private $urls = [];

    /**
     * @param $urls
     * @return $this
     */
    public function setUrls($urls)
    {
        $this->urls = $urls;

        return $this;
    }

    /**
     * @return $this
     */
    public function joinTotal()
    {
        $this->isTotalIncluded = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function joinLike()
    {
        $this->isLikeIncluded = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function joinComment()
    {
        $this->isCommentIncluded = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function joinShare()
    {
        $this->isShareIncluded = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function joinClick()
    {
        $this->isClickIncluded = true;

        return $this;
    }

    /**
     * Return url likes by sorting
     *
     * @return mixed
     * @throws \Exception
     */
    public function execute(){
        if(empty($this->urls))
            throw new \Exception('Page Url`s is not defined for Facebook Api Query');

        $query     = 'select ';
        $separator = '';

        if ($this->isTotalIncluded) {
            $query    .= 'total_count';
            $separator = ',';
        }

        if ($this->isLikeIncluded) {
            $query    .= $separator . 'like_count';
            $separator = ',';
        }

        if ($this->isCommentIncluded) {
            $query    .= $separator . 'comment_count';
            $separator = ',';
        }

        if ($this->isShareIncluded) {
            $query    .= $separator . 'share_count';
            $separator = ',';
        }

        if ($this->isClickIncluded) {
            $query .= $separator . 'click_count';
        }

        $urls = implode(', ', array_map( function($url) {
            return '"' . urlencode($url) . '"';
        }, $this->urls));

        $query .= ' from link_stat where url IN ('. $urls .')';
        $output = file_get_contents($this->queryUrl . urlencode($query) . '&format=json');

        return json_decode($output);
    }
}