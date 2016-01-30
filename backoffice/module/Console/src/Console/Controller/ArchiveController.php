<?php

namespace Console\Controller;

use FileManager\Constant\DirectoryStructure;
use Library\Controller\ConsoleBase;
use Zend\Console\Prompt;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Where;
use Zend\Mail\Storage\Message;

class ArchiveController extends ConsoleBase
{
    public function fixTransactionAccountsAction()
    {
        /**
         * @var Adapter $adapter
         */
        $this->initCommonParams($this->getRequest());
        $adapter = $this->getServiceLocator()->get('dbAdapter');
        $updateTransactionStmt = $adapter->createStatement('update ga_transactions set account_id = ? where id = ?;');
        $data = $adapter->createStatement("
            select
                ga_transactions.id as money_transaction_id,
                ga_transactions.account_id,
                ga_transactions.amount,
                ga_expense_transaction.money_account_id,
                ifnull(
                        ifnull(ga_booking_partners.partner_name, ga_suppliers.name),
                        concat(ga_bo_users.firstname, ' ', ga_bo_users.lastname)
                ) as account_name,
                ta.id as account_replacement_id,
                ma.name as replacement_name,
                ga_money_accounts.name
            from ga_transactions
                left join ga_transaction_accounts on ga_transaction_accounts.id = ga_transactions.account_id
                left join ga_bo_users on ga_bo_users.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = 5
                left join ga_suppliers on ga_suppliers.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = 4
                left join ga_booking_partners on ga_booking_partners.gid = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = 3

                left join ga_expense_transaction on ga_expense_transaction.money_transaction_id = ga_transactions.id
                left join ga_money_accounts on ga_money_accounts.id = ga_expense_transaction.money_account_id

                left join ga_transaction_accounts as ta on ta.holder_id = ga_expense_transaction.money_account_id and ta.type = 2
                left join ga_money_accounts as ma on ma.id = ta.holder_id and ta.type = 2
            where ga_transaction_accounts.type <> 2;
        ")->execute();

        if ($data->count()) {
            $this->outputMessage("[warning]WARNING! {$data->count()} broken transactions found.");

            foreach ($data as $item) {
                $updateTransactionStmt->execute([$item['account_replacement_id'], $item['money_transaction_id']]);
                $this->outputMessage("[purple]#[light_purple]{$item['money_transaction_id']} [purple]Supplier [light_purple]{$item['account_name']} [purple]has been replaced with [light_purple]{$item['replacement_name']}");
            }
        } else {
            $this->outputMessage('[success]SUCCESS! No broken data found.');
        }
    }

    public function fixExpenseBalanceAction()
    {
        $expenseId = $this->getRequest()->getParam('expense-id', null);

        /**
         * @var Adapter $dbAdapter
         */
        $this->initCommonParams($this->getRequest());
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');

        // Get Items Statement
        $itemsStmt = $dbAdapter->createStatement('
            select
              ga_expense_item.id,
              ga_expense_item.amount,
              ga_expense_item.currency_id,
              date(ga_expense_item.date_created) as date_created,
              ga_expense_item.is_refund
            from ga_expense_item
            where expense_id = ?;
        ');

        // Get Transaction Statement
        $transactionStmt = $dbAdapter->createStatement('
            select
                ga_expense_transaction.id,
                ga_expense_transaction.amount,
                ga_money_accounts.currency_id,
                date(ga_expense_transaction.creation_date) as date_created,
                ga_expense_transaction.is_refund,
                ga_expense_transaction.status
            from ga_expense_transaction
                left join ga_money_accounts on ga_money_accounts.id = ga_expense_transaction.money_account_id
            where expense_id = ?;
        ');

        // Set Item Balance
        $expenseItemBalanceStmt = $dbAdapter->createStatement('
            update ga_expense set item_balance = ? where id = ?;
        ');
        $expenseTransactionBalanceStmt = $dbAdapter->createStatement('
            update ga_expense set transaction_balance = ? where id = ?;
        ');
        $expenseTicketBalanceStmt = $dbAdapter->createStatement('
            update ga_expense set ticket_balance = ? where id = ?;
        ');

        // Get Currencies
        $currencies = $dbAdapter->createStatement('
            select * from ga_currency_vault;
        ')->execute();
        $currencyList = [];

        // Prepare Currencies
        foreach ($currencies as $currency) {
            if (!isset($currencyList[$currency['date']])) {
                $currencyList[$currency['date']] = [];
            }

            if (!isset($currencyList[$currency['date']][$currency['currency_id']])) {
                $currencyList[$currency['date']][$currency['currency_id']] = $currency['value'];
            }
        }

        if (!is_null($expenseId)) {
            $expenseWhere = ' where id=' . $expenseId . ' ';
        } else {
            $expenseWhere = ' ';
        }
        // Get Expenses
        $expenses = $dbAdapter->createStatement('
            select id, currency_id, item_balance, transaction_balance, ticket_balance from ga_expense ' . $expenseWhere . 'order by id asc;
        ')->execute();

        foreach ($expenses as $expense) {
            $items = $itemsStmt->execute([$expense['id']]);
            $transactions = $transactionStmt->execute([$expense['id']]);
            $itemBalance = 0;
            $transactionBalance = 0;

            if ($items->count()) {
                foreach ($items as $item) {
                    if (empty($currencyList[$item['date_created']])) {
                        $this->outputMessage('[red]TERMINATED: [light_red]Currency vault is broken. Data for ' . $item['date_created'] . ' is missing.');
                        exit;
                    }

                    if (empty($currencyList[$item['date_created']][$expense['currency_id']])) {
                        $this->outputMessage('[red]TERMINATED: [light_red]Currency vault is broken. Data for ' . $item['date_created'] . ' and currency ' . $expense['currency_id'] . ' is missing.');
                        exit;
                    }

                    if (empty($currencyList[$item['date_created']][$item['currency_id']])) {
                        $this->outputMessage('[red]TERMINATED: [light_red]Currency vault is broken. Data for ' . $item['date_created'] . ' and currency ' . $item['currency_id'] . ' is missing.');
                        exit;
                    }

                    if ($item['currency_id'] != $expense['currency_id']) {
                        $amount = $item['amount']
                            * $currencyList[$item['date_created']][$expense['currency_id']]
                            / $currencyList[$item['date_created']][$item['currency_id']];
                    } else {
                        $amount = $item['amount'];
                    }

                    if ((int)$item['is_refund']) {
                        $itemBalance -= $amount;
                    } else {
                        $itemBalance += $amount;
                    }
                }
            }

            if ($expense['item_balance'] != number_format($itemBalance, 2, '.', '')) {
                $expenseItemBalanceStmt->execute([$itemBalance, $expense['id']]);
            }

            if ($transactions->count()) {
                foreach ($transactions as $transaction) {
                    if ((int)$transaction['status'] === 0) {
                        continue;
                    }

                    if (empty($currencyList[$transaction['date_created']])) {
                        $this->outputMessage('[red]TERMINATED: [light_red]Currency vault is broken. Data for ' . $transaction['date_created'] . ' is missing.');
                    }

                    if (empty($currencyList[$transaction['date_created']][$expense['currency_id']])) {
                        $this->outputMessage('[red]TERMINATED: [light_red]Currency vault is broken. Data for ' . $transaction['date_created'] . ' and currency ' . $expense['currency_id'] . ' is missing.');
                    }

                    if (empty($currencyList[$transaction['date_created']][$transaction['currency_id']])) {
                        $this->outputMessage('[red]TERMINATED: [light_red]Currency vault is broken. Data for ' . $transaction['date_created'] . ' and currency ' . $transaction['currency_id'] . ' is missing.');
                    }

                    if ($transaction['currency_id'] != $expense['currency_id']) {
                        $amount = $transaction['amount']
                            * $currencyList[$transaction['date_created']][$expense['currency_id']]
                            / $currencyList[$transaction['date_created']][$transaction['currency_id']];
                    } else {
                        $amount = $transaction['amount'];
                    }

                    $transactionBalance += $amount;
                }
            }

            if ($expense['transaction_balance'] != number_format($transactionBalance, 2, '.', '')) {
                $expenseTransactionBalanceStmt->execute([$transactionBalance, $expense['id']]);
            }

            if ($expense['ticket_balance'] != number_format($itemBalance + $transactionBalance, 2, '.', '')) {
                $expenseTicketBalanceStmt->execute([$itemBalance + $transactionBalance, $expense['id']]);
                $ticketBalance = number_format($itemBalance + $transactionBalance, 2, '.', '');

                $this->outputMessage("[purple]Expense [light_purple]{$expense['id']} [purple]Balance from/to [light_purple]{$expense['ticket_balance']} [purple]-- [light_purple]{$ticketBalance}");
            } else {
                $this->outputMessage("[purple]Expense [light_purple]{$expense['id']} [purple]is CLEAR");
            }
        }

        $this->outputMessage('[success]FINISHED'); exit;
    }

    public function fixMoneyAccountBalancesAction()
    {
        /**
         * @var Adapter $adapter
         */
        $this->initCommonParams($this->getRequest());
        $adapter = $this->getServiceLocator()->get('dbAdapter');
        $moneyAccounts = $adapter->createStatement('select id, name, balance from ga_money_accounts;')->execute();
        $moneyAccountsStmt = $adapter->createStatement('update ga_money_accounts set balance = ? where id = ?;');
        $transactionsStmt = $adapter->createStatement("
            select
                ga_money_accounts.id as money_account_id,
                ga_money_accounts.name as name,
                ga_transaction_accounts.id as account_id,
                ga_transactions.amount as amount
            from ga_transactions
            left join ga_transaction_accounts on ga_transaction_accounts.id = ga_transactions.account_id
            left join ga_money_accounts on ga_money_accounts.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = 2
            where ga_money_accounts.id = ? && ga_transactions.is_voided = 0;
        ");

        if ($moneyAccounts->count()) {
            foreach ($moneyAccounts as $moneyAccount) {
                $this->outputMessage("[purple]Executing [light_purple]{$moneyAccount['name']} [purple]with balance [light_purple]{$moneyAccount['balance']}");

                $transactions = $transactionsStmt->execute([$moneyAccount['id']]);
                $this->outputMessage("  [light_purple]{$transactions->count()} [purple]transaction(s) found");

                if ($transactions->count()) {
                    $balance = 0;

                    foreach ($transactions as $transaction) {
                        $balance += $transaction['amount'];
                    }

                    $moneyAccountsStmt->execute([$balance, $moneyAccount['id']]);
                    $this->outputMessage("  [purple]New balance is [light_purple]{$balance}");
                }

                echo PHP_EOL;
            }
        }
    }

    public function fixTransactionsDirectionAction()
    {
        /**
         * @var Adapter $adapter
         */
        $this->initCommonParams($this->getRequest());
        $adapter = $this->getServiceLocator()->get('dbAdapter');
        $transactionUpdateStmt = $adapter->createStatement('update ga_transactions set amount = ? where id = ?;');
        $isRefundStmt = $adapter->createStatement("
            select
              ga_transactions.id,
              ga_transactions.amount
            from ga_transactions
                left join ga_expense_transaction on ga_expense_transaction.money_transaction_id = ga_transactions.id
                left join ga_reservation_transactions on ga_reservation_transactions.money_transaction_id = ga_transactions.id
            where ga_expense_transaction.id is not null and ga_reservation_transactions.id is null
                and ga_expense_transaction.is_refund = 1 and ga_transactions.amount < 0
        ");
        $isNotRefundStmt = $adapter->createStatement("
            select
              ga_transactions.id,
              ga_transactions.amount
            from ga_transactions
                left join ga_expense_transaction on ga_expense_transaction.money_transaction_id = ga_transactions.id
                left join ga_reservation_transactions on ga_reservation_transactions.money_transaction_id = ga_transactions.id
            where ga_expense_transaction.id is not null and ga_reservation_transactions.id is null
                and ga_expense_transaction.is_refund = 0 and ga_transactions.amount > 0
        ");

        $isRefundExpenseTransactions = $isRefundStmt->execute();

        if ($isRefundExpenseTransactions->count()) {
            $this->outputMessage("[light_purple]{$isRefundExpenseTransactions->count()} [purple]refund expense transactions found which should be in a positive amount");

            foreach ($isRefundExpenseTransactions as $transaction) {
                $transactionUpdateStmt->execute([abs($transaction['amount']), $transaction['id']]);
            }

            $this->outputMessage('[light_purple]Fixed');
            echo PHP_EOL;
        }

        $isNotRefundExpenseTransactions = $isNotRefundStmt->execute();

        if ($isNotRefundExpenseTransactions->count()) {
            $this->outputMessage("[light_purple]{$isNotRefundExpenseTransactions->count()} [purple]non-refund expense transactions found which should be in a negative amount");

            foreach ($isNotRefundExpenseTransactions as $transaction) {
                $transactionUpdateStmt->execute([-1 * $transaction['amount'], $transaction['id']]);
            }

            $this->outputMessage('[light_purple]Fixed');
        }
    }
}
