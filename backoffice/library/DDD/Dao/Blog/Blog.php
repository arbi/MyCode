<?php

namespace DDD\Dao\Blog;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class Blog extends TableGatewayManager
{
    protected $table = DbTables::TBL_BLOG_POSTS;

    public function __construct($sm, $domain = 'DDD\Domain\Blog\Blog')
    {
        parent::__construct($sm, $domain);
    }

    public function getBlogList()
    {
        $select = new Select();
        $select->from($this->table);
        $select->order('date DESC');

        return new \Zend\Paginator\Paginator(
            new \Zend\Paginator\Adapter\DbSelect($select, $this->adapter, $this->resultSetPrototype)
        );
    }

    public function getBlogByArticel($article)
    {
        return $this->fetchOne(function (Select $select) use ($article) {
            $select->where(['slug' => $article]);
        });
    }

    public function getBlogForWebIndex()
    {
        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'date',
                'slug',
                'title',
                'content'
            ]);

            $select->order('date DESC')->limit(2);
        });
    }

    public function getBlogListForFeed()
    {
        return $this->fetchAll(function (Select $select) {
            $select->order('date DESC');
        });
    }
}
