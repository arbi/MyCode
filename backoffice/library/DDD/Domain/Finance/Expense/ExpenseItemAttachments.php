<?php

namespace DDD\Domain\Finance\Expense;

class ExpenseItemAttachments
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $expenseId;

    /**
     * @var int
     */
    protected $itemId;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @param $dateCreated
     */
    protected $dateCreated;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->expenseId = (isset($data['expense_id'])) ? $data['expense_id'] : null;
        $this->itemId = (isset($data['item_id'])) ? $data['item_id'] : null;
        $this->filename = (isset($data['filename'])) ? $data['filename'] : null;
        $this->dateCreated = (isset($data['date_created'])) ? $data['date_created'] : null;
    }


    /**
     * @return int
     */
    public function getExpenseId()
    {
        return $this->expenseId;
    }

    /**
     * @return int
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    public function getDateCreatedNeededFormat()
    {
        list($date,) = explode(' ', $this->dateCreated);

        return str_replace('-', '/', $date);
    }
}
