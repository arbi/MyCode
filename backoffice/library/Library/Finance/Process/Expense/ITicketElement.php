<?php

namespace Library\Finance\Process\Expense;

interface ITicketElement
{
    /**
     * Based on data and id combination detected processing mode.
     *
     * @param array $data
     * @param int|null $id
     */
    public function __construct(array $data, $id);

    /**
     * Save an element based on expense ticket's data. For now it can be expense item
     * or expense transaction, but tomorrow can be used for booking charges and transactions.
     *
     * @param Ticket $expenseTicket
     * @return void
     */
    public function save(Ticket $expenseTicket);

    /**
     * Return element specific data in array.
     *
     * @return array
     */
    public function getData();
}
