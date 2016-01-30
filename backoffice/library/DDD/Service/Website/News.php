<?php
namespace DDD\Service\Website;

use DDD\Service\ServiceBase;
use Library\Constants\Constants;
use DDD\Dao\News\News as NewsDao;
use Zend\Paginator\Paginator;
use Library\Constants\WebSite;
use Library\Utility\Helper;
use Library\Validator\ClassicValidator;

class News extends ServiceBase
{
    /**
     * 
     * @param int $page
     * @return Zend\Paginator\Paginator
     */
    public function getNewsList($page) {
    	$dao = $this->getNewsDao();
        $paginator = $dao->getNewsList();
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(WebSite::NEWS_PAGE_COUNT);
    	return $paginator;
    }

    /**
     * @param $title
     * @return array|\ArrayObject|bool|null
     */
    public function getNewsByArticle($title)
    {
        if (!ClassicValidator::checkNewsTitle($title))
            return false;
    	$dao = $this->getNewsDao();
    	$news = $dao->getNewsByArticle($title);
    	return $news;
    }

    /**
     * @param string $domain
     * @return NewsDao
     */
    private function getNewsDao($domain = 'ArrayObject')
    {
        return new NewsDao($this->getServiceLocator(), $domain);
    }
}
