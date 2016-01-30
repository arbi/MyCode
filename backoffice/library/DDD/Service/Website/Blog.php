<?php
namespace DDD\Service\Website;

use DDD\Service\ServiceBase;
use Library\Constants\Constants;
use DDD\Dao\Blog\Blog as BlogDao;
use Zend\Paginator\Paginator;
use Library\Constants\WebSite;
use Library\Validator\ClassicValidator;

class Blog extends ServiceBase
{
    /**
     * 
     * @param int $page
     * @return \Zend\Paginator\Paginator
     */
    public function getBlogList($page) {
    	$dao = $this->getBlogDao();
        $paginator = $dao->getBlogList();
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(WebSite::BLOG_PAGE_COUNT);
    	return $paginator;
    }
    /**
     * 
     * @param string $article
     * @return type
     */
    public function getBlogByArticel($article) {
        if(!ClassicValidator::checkCityName($article))
            return false;
    	$dao = $this->getBlogDao();
    	$blog = $dao->getBlogByArticel($article);
    	return $blog;
    }
    /**
     * 
     * @param type $domain
     * @return \DDD\Dao\Blog\Blog
     */
    private function getBlogDao($domain = 'ArrayObject')
    {
        return new BlogDao($this->getServiceLocator(), $domain);
    }
}
