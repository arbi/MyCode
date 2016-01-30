<?php

namespace DDD\Dao\Finance\Expense;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\ServiceManager\ServiceLocatorInterface;

class ExpenseAttachments extends TableGatewayManager
{
    protected $table = DbTables::TBL_EXPENSE_ATTACHMENTS;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Finance\Expense\ExpenseAttachments') {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $expenseId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getAttachmentsForPreview($expenseId)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function (Select $select) use ($expenseId) {
            $select->columns(['id', 'filename']);
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                $this->getTable() . '.expense_id = expense.id',
                ['date_created'],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable() . '.expense_id' => $expenseId]);
        });
    }

    /**
     * @param int $attachmentId
     * @return array|bool
     */
    public function getAttachmentsToRemove($attachmentId)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($attachmentId) {
            $select->columns(['expense_id', 'filename']);
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
     * @param int $attachmentId
     * @return array|bool
     */
    public function getAttachmentForPreviewById($attachmentId)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchOne(function(Select $select) use ($attachmentId) {
            $select->columns(['expense_id', 'filename']);
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                $this->getTable() . '.expense_id = expense.id',
                ['date_created'],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable() . '.id' => $attachmentId]);
        });
    }
}
