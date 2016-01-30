<?php

namespace DDD\Dao\News;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class News extends TableGatewayManager
{
    protected $table = DbTables::TBL_NEWS;

    public function __construct($sm, $domain = 'DDD\Domain\News\News')
    {
        parent::__construct($sm, $domain);
    }

    public function getNewsList()
    {
        $select = new Select();
        $select->columns([
            'content' => 'en',
            'title' => 'en_title',
            'url' =>'en_title',
            'date',
        ]);

        $select->from($this->table);
        $select->order('date DESC');

        return new \Zend\Paginator\Paginator(
            new \Zend\Paginator\Adapter\DbSelect(
                $select,
                $this->adapter,
                $this->resultSetPrototype
            )
        );
    }

    public function getNewsByArticle($title)
    {
        return $this->fetchOne(function (Select $select) use ($title) {
            $select->columns([
                'content' => 'en',
                'title' => 'en_title',
                'date',
            ]);

            $select->where->equalTo('slug', $title);
        });
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet|\DDD\Domain\News\News[]
     */
    public function getNewsListForFeed()
    {
        return $this->fetchAll(function (Select $select) {
            $select->order('date DESC');
        });
    }
}
