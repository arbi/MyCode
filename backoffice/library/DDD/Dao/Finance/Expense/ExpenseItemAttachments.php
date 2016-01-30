<?php

namespace DDD\Dao\Finance\Expense;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\ServiceManager\ServiceLocatorInterface;

class ExpenseItemAttachments extends TableGatewayManager
{
    protected $table = DbTables::TBL_EXPENSE_ITEM_ATTACHMENTS;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Finance\Expense\ExpenseItemAttachments') {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $attachmentId
     * @return array|bool
     */
    public function getAttachmentForPreviewById($attachmentId)
    {

        return $this->fetchOne(function(Select $select) use ($attachmentId) {
            $select->columns(['expense_id', 'item_id', 'filename']);
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                $this->getTable() . '.expense_id = expense.id',
                ['date_created'],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable() . '.id' => $attachmentId]);
        });

    }

    /**
     * @param array $itemIdList
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getAttachmentsForPreview($itemIdList)
    {
        return $this->fetchAll(function (Select $select) use ($itemIdList) {
            $select->columns(['id', 'expense_id', 'item_id', 'filename']);
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                $this->getTable() . '.expense_id = expense.id',
                ['date_created'],
                Select::JOIN_LEFT
            );
            $select->where->in($this->getTable() . '.item_id', $itemIdList);
        });
    }

    /**
     * @param int $itemId
     * @return array|bool
     */
    public function getAttachmentsToRemove($itemId)
    {
        return $this->fetchOne(function (Select $select) use ($itemId) {
            $select->columns(['filename', 'expense_id']);
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                $this->getTable() . '.expense_id = expense.id',
                ['date_created'],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable() . '.item_id' => $itemId]);
        });
    }

    /**
     * @param $itemId
     * @return array|\ArrayObject|null
     */
    public function getAttachmentBasicInfoByItemId($itemId)
    {
        return $this->fetchOne(function(Select $select) use ($itemId) {
            $select->columns(['filename', 'id']);
            $select->where([$this->getTable() . '.item_id' => $itemId]);
        });

    }

}
