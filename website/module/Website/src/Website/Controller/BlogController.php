<?php

namespace Website\Controller;

use Library\Controller\WebsiteBase;
use Library\Facebook\FacebookQueryLanguage;
use Zend\Db\ResultSet\ResultSet;
use Zend\View\Model\ViewModel;
use Library\Constants\DomainConstants;
use Library\Constants\Constants;
use Zend\Feed\Writer\Feed;
use Zend\View\Model\FeedModel;
use Library\Utility\Helper;
use FileManager\Constant\DirectoryStructure;

class BlogController extends WebsiteBase
{
    public function indexAction()
    {
        $blogService = $this->getServiceLocator()->get('service_website_blog');
        $page = (int)$this->params()->fromQuery('page', 1);
        $paginator = $blogService->getBlogList($page);
        $searchService = $this->getServiceLocator()->get('service_website_search');

        return new ViewModel(array(
            'liveServerUrl' => 'https://ginosi.com',
            'paginator' => $paginator,
            'imgPath' => '//'.DomainConstants::IMG_DOMAIN_NAME,
            'realPath' => DirectoryStructure::FS_GINOSI_ROOT . DirectoryStructure::FS_IMAGES_ROOT,
            'cities' => $searchService->getCityListForSearch()
        ));
    }

    public function articleAction()
    {
        $searchService = $this->getServiceLocator()->get('service_website_search');
        $blogService = $this->getServiceLocator()->get('service_website_blog');
        $article = $this->params()->fromRoute('article');
        $blog = $blogService->getBlogByArticel($article);

        if (!$blog) {
            return $this->redirect()->toRoute('blog');
        }

        return new ViewModel([
            'liveServerUrl' => 'https://ginosi.com',
            'blog' => $blog,
            'imgPath' => '//'.DomainConstants::IMG_DOMAIN_NAME,
            'realPath' => DirectoryStructure::FS_GINOSI_ROOT . DirectoryStructure::FS_IMAGES_ROOT,
            'cities' => $searchService->getCityListForSearch(),
        ]);
    }

    public function feedAction()
    {
        $blogDao = $this->getServiceLocator()->get('dao_blog_blog');
        $blogList = $blogDao->getBlogListForFeed();

        $feed = new Feed();
        $feed->setTitle('Ginosi\'s Blog');
        $feed->setLink('//www.ginosi.com');
        $feed->setFeedLink('//www.ginosi.com/blog/feed', 'rss');

        $feed->setDateModified(time());
        $feed->addHub('//pubsubhubbub.appspot.com/');
        $feed->setDescription('Ginosi\'s Blog');

        foreach ($blogList as $row) {
            preg_match('/<p>(.*)<\/p>/', $row->getContent(), $matches);

            if (isset($matches[1]) && !is_null($matches[1])) {
                $desc = $matches[1];
            } else {
                $contents = preg_split('/\n/', $row->getContent());
                $desc = $contents[0];
            }

            $entry = $feed->createEntry();
            $entry->setTitle($row->getTitle());
            $entry->setLink(
                "//" . DomainConstants::WS_DOMAIN_NAME . "/blog/" .
                Helper::urlForSite($row->getTitle())
            );
            $entry->setDateModified(strtotime($row->getDate()));
            $entry->setDateCreated(strtotime($row->getDate()));
            $entry->setDescription($desc);
            $entry->setContent($row->getContent());
            $feed->addEntry($entry);
        }

        /**
         * Render the resulting feed to Atom 1.0 and assign to $out.
         * You can substitute "atom" with "rss" to generate an RSS 2.0 feed.
         */
        $feed->export('rss');
        $feedmodel = new FeedModel();
        $feedmodel->setFeed($feed);

        $this->getServiceLocator()
            ->get('Application')
            ->getEventManager()
            ->attach(
                \Zend\Mvc\MvcEvent::EVENT_RENDER,
                function($event) {
                    $event
                        ->getResponse()
                        ->getHeaders()
                        ->addHeaderLine('Content-Type', 'text/xml; charset=UTF-8');
                }, -10000
            );
        return $feedmodel;
    }
}
