<?php

namespace DDD\Domain\Finance\Expense;

/**
 * Class ExpenseAttachments
 * @package DDD\Domain\Finance\Expense
 */
class ExpenseAttachments
{
    /**
     * @var int 
     */
    protected $id;

    /**
     * @var int 
     */
    protected $expense_id;

    /**
     * @var string 
     */
    protected $filename;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->expense_id = (isset($data['expense_id'])) ? $data['expense_id'] : null;
        $this->filename = (isset($data['filename'])) ? $data['filename'] : null;
    }

    /**
     * @param int $expense_id
     */
    public function setExpenseId($expense_id)
    {
        $this->expense_id = $expense_id;
    }

    /**
     * @return int
     */
    public function getExpenseId()
    {
        return $this->expense_id;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
