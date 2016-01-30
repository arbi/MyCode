<?php

namespace Website\Controller;

use DDD\Dao\News\News as NewsDao;
use DDD\Service\Website\News;
use Library\Controller\WebsiteBase;
use Library\Utility\Helper;
use Zend\View\Model\ViewModel;
use Zend\Feed\Writer\Feed;
use Zend\View\Model\FeedModel;
use Library\Constants\DomainConstants;

class NewsController extends WebsiteBase
{
    public function indexAction()
    {
        /**
         * @var News $newsService
         */
        $searchService = $this->getServiceLocator()->get('service_website_search');
        $newsService = $this->getServiceLocator()->get('service_website_news');
        $page = (int)$this->params()->fromQuery('page', 1);
        $paginator = $newsService->getNewsList($page);

        return new ViewModel([
            'paginator' => $paginator,
            'cities' => $searchService->getCityListForSearch(),
        ]);
    }

    public function articleAction()
    {
        /**
         * @var News $newsService
         */
        $newsService = $this->getServiceLocator()->get('service_website_news');
        $searchService = $this->getServiceLocator()->get('service_website_search');
        $article = $this->params()->fromRoute('article');
        $news = $newsService->getNewsByArticle($article);

        if (!$news) {
            return $this->redirect()->toRoute('news');
        }

        return new ViewModel([
            'news' => $news,
            'cities' => $searchService->getCityListForSearch()
        ]);
    }

    public function feedAction()
    {
        /**
         * @var NewsDao $newsDao
         */
        $newsDao = $this->getServiceLocator()->get('dao_news_news');
        $newsList = $newsDao->getNewsListForFeed();

        $feed = new Feed();
        $feed->setTitle('Ginosi\'s News');
        $feed->setLink('//www.ginosi.com');
        $feed->setFeedLink('//www.ginosi.com/news/feed', 'rss');

        $feed->setDateModified(time());
        $feed->addHub('//pubsubhubbub.appspot.com/');
        $feed->setDescription('Ginosi\'s News');

        foreach ($newsList as $row) {
            preg_match('/<p>(.*)<\/p>/', $row->getEn(), $matches);

            if (isset($matches[1]) && !is_null($matches[1])) {
                $desc = $matches[1];
            } else {
                $contents = preg_split('/\n/', $row->getEn());
                $desc = $contents[0];
            }

            $entry = $feed->createEntry();
            $entry->setTitle($row->getEn_title());
            $entry->setLink(
                "//" . DomainConstants::WS_DOMAIN_NAME . "/news/" .
                Helper::urlForSite($row->getEn_title())
            );
            $entry->setDateModified(strtotime($row->getDate()));
            $entry->setDateCreated(strtotime($row->getDate()));
            $entry->setDescription($desc);
            $entry->setContent($row->getEn());
            $feed->addEntry($entry);
        }

        /**
         * Render the resulting feed to Atom 1.0 and assign to $out.
         * You can substitute "atom" with "rss" to generate an RSS 2.0 feed.
         */
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

        $feed->export('rss');

        return (
            new FeedModel()
        )->setFeed($feed);
    }
}
