<?php
namespace DDD\Dao\Tag;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

/**
 * Class Tag
 * @package DDD\Dao\Tag
 */
class Tag extends TableGatewayManager
{
    protected $table = DbTables::TBL_TAG;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Tag\Tag');
    }

    /**
     * @param $offset
     * @param $limit
     * @param $sortCol
     * @param $sortDir
     * @param $like
     * @return \DDD\Domain\Tag\Tag[]
     */
    public function getTagsList($offset, $limit, $sortCol, $sortDir, $like)
    {
        return $this->fetchAll(function (Select $select) use ($offset, $limit, $sortCol, $sortDir, $like) {
            $columns = [
                'id',
                'name',
                'style',
                'used_count' => new Expression('(SELECT COUNT("id") FROM ' . DbTables::TBL_TASK_TAG .
                    ' WHERE ' . DbTables::TBL_TASK_TAG . '.tag_id = ' . $this->getTable() . '.id)')
            ];
            $orderColumns = ['name', 'used_count'];
            $select->where->
                    like($this->getTable() . '.name', '%' . $like . '%');

            $select
                ->columns($columns)
                ->order($orderColumns[$sortCol] . ' ' . $sortDir)
                ->offset((int)$offset)
                ->limit((int)$limit);
        });
    }

    public function getTagsCount($like)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $columns = ['count' => new Expression('count(*)')];
        $res =  $this->fetchOne(function (Select $select) use ($like,  $columns) {
            $select->where->
            like($this->getTable() . '.name', '%' . $like . '%');
            $select
                ->columns($columns);
        });
        $count = $res['count'];
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $count;
    }

    public function alreadyExists($tagName,$id)
    {
        return  $this->fetchOne(function (Select $select) use ($tagName,  $id) {
            $select->where->equalTo($this->getTable() . '.name',$tagName);
            if ($id) {
                $select->where->notEqualTo($this->getTable() . '.id',$id);
            }
            $select
                ->columns(['id']);
        });
    }

}
