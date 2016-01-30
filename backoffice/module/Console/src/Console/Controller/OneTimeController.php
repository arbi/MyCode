<?php

namespace Console\Controller;

use CreditCard\Service\Fraud;
use DDD\Dao\ActionLogs\ActionLogs;
use DDD\Service\Translation;
use DDD\Service\WHOrder\Order;
use FileManager\Constant\DirectoryStructure;
use Library\Controller\ConsoleBase;
use Library\Finance\Base\Account;
use Zend\Console\Prompt;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Where;
use Zend\Mail\Storage\Message;

class OneTimeController extends ConsoleBase
{

    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());
    }

    public function createMissingThumbsAction()
    {
        $this->initCommonParams($this->getRequest());
        $this->outputMessage('[light_cyan]Creating missing thumbs.');
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');

        $apartments = $dbAdapter->createStatement('
        SELECT ai.*, a.`name`
        FROM `ga_apartment_images` ai LEFT JOIN `ga_apartments` a
          ON ai.`apartment_id` = a.`id`
        ')->execute();

        foreach ($apartments as $apartment) {
            $this->outputMessage('[cyan]Looking into [light_blue]' . $apartment['name'] . '[cyan] images...');
            for ($i = 1; $i <= 32; $i++) {
                if ($apartment['img' . $i]) {
                    $imgPathComponents = explode('/', $apartment['img' . $i]);
                    $originalFileName = array_pop($imgPathComponents);
                    $originalFilePath = DirectoryStructure::FS_GINOSI_ROOT .
                        DirectoryStructure::FS_IMAGES_ROOT .
                        DirectoryStructure::FS_IMAGES_APARTMENT .
                        $apartment['apartment_id'] . '/' . $originalFileName;

                    $thumbFilePath = str_replace('_orig.', '_555.', $originalFilePath);

                    if (!file_exists($originalFilePath)) {
                        $this->outputMessage('[error] Original file for image ' . $i . ' is missing.');
                    } else if (file_exists($thumbFilePath)) {
                        $this->outputMessage('[light_blue] Thumb for image ' . $i . ' exists.');
                    } else {
                        $thumb = new \Imagick($originalFilePath);
                        $thumb->resizeImage(555, 370, \Imagick::FILTER_LANCZOS, 1);
                        $thumb->writeImage($thumbFilePath);
                        $this->outputMessage('[success] Added thumb for image ' . $i);
                    }
                }
            }
        }
        $this->outputMessage('Done!');
    }

    // ***** DO NOT REMOVE THIS, ASK TO ARBI ***** //
    public function addUsersToOauthAction()
    {
        $daOuathUsers   = $this->getServiceLocator()->get('dao_oauth_oauth_users');
        $daoUsermanager = $this->getServiceLocator()->get('dao_user_user_manager');

        $users = $daoUsermanager->fetchAll(['disabled' => 0, 'external' => 0, 'system' => 0]);

        foreach ($users as $user) {
            if (!$daOuathUsers->fetchOne(['username' => $user->getEmail()])) {
                $daOuathUsers->save(['username' => $user->getEmail(), 'password' => $user->getPassword()]);
                var_dump('Add user ' . $user->getEmail() . ' to oauth table.');
            }
        }
    }

    /**
     * Change transactions description standard
     */
    public function changeTransactionsDescriptionAction()
    {
        $this->outputMessage('[light_cyan]Select all Transactions.');
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');

        $transactions = $dbAdapter->createStatement('
        SELECT tr.id, tr.`description`
        FROM `ga_transactions` AS tr
        ')->execute();

        foreach ($transactions as $transaction) {
            $transaction['description'] = str_replace('transaction. Ticket id', 'Transaction', $transaction['description']);
            $transaction['description'] = str_replace('transaction. Res. number', 'Transaction', $transaction['description']);
            $transaction['description'] = str_replace('.', '', $transaction['description']);

            $dbAdapter->createStatement('UPDATE ga_transactions
                                         SET description = "'. $transaction['description'] .'"
                                         WHERE id=' . $transaction['id'])->execute();
            $this->outputMessage('[success] UPDATE ' . $transaction['description']);
        }
    }

    public function applyUserVacationsAction()
    {
        $this->initCommonParams($this->getRequest());
        $this->outputMessage('[light_blue]Applying user vacations on people schedule...');
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');
        $vacations = $dbAdapter->createStatement('
        SELECT `user_id`, `from`, `to`
        FROM `ga_bo_user_vacations`
        WHERE `is_approved` = 1 AND `from` > \'2015-06-01\'
        ')->execute();

        if ($vacations) {
            foreach ($vacations as $vacation) {
                $sql = 'UPDATE `ga_bo_user_schedule_inventory` '
                . 'SET `availability` = 0 '
                . 'WHERE `user_id` = "' . $vacation['user_id'] . '" '
                    . 'AND `date` <= "' . $vacation['to'] . '" '
                    . 'AND `date` >= "' . $vacation['from'] . '"'
                    . 'AND `availability` = "1"';
                $dbAdapter->createStatement($sql)->execute();
            }
        }
        $this->outputMessage('[light_blue]Done!');
    }

    /**
     *  Update customer and apartment balances
     */
    public function updateAllBalanceAction()
    {

        $chargeService       = $this->getServiceLocator()->get('service_booking_charge');
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');

        $sql = "SELECT r.id FROM ga_reservations AS r
  INNER JOIN ga_reservation_charges AS ch ON r.id = ch.reservation_id
WHERE r.date_from >= '2015-09-03'
GROUP BY r.id;";

        $results = $dbAdapter->createStatement($sql)->execute();
        echo 'Start...' . PHP_EOL;
        foreach ($results as $row) {
            // update balance
            $chargeService->updateBalance($row['id']);
        }
        echo 'End' . PHP_EOL;
    }

    public function recoverTextlinesAction()
    {
        echo 'Starting...' . PHP_EOL;
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');
        $sql = "SELECT * FROM ga_un_textlines WHERE en_html_clean IS NULL OR en_html_clean='' AND en <> ''";
        $updateSql = "UPDATE ga_un_textlines SET en_html_clean=? WHERE id=?";
        $result = $dbAdapter->createStatement($sql)->execute();
        foreach ($result as $row){
            $id = $row['id'];
            $en_html_clean = strip_tags($row['en']);
            if ($en_html_clean == '') {
                continue;
            }
            echo $en_html_clean . PHP_EOL;
            $dbAdapter->createStatement($updateSql)->execute([$en_html_clean, $id]);
        }
        echo 'Done' . PHP_EOL;
    }

    public function mergeSuppliersFromToAction()
    {
        $fromSupplierIds = [
            282,
            379,
            14,
            179,
            122,
            67,
            18,
            73,
            191,
            359,
            458,
            274,
            541,
            387,
            496,
            429,
            92 // Amoor
        ];

        $fromSupplierTransactionAccountIds = [
            16428,
            16432,
            16481,
            16487,
            16536,
            16593,
            16605,
            16688,
            16696,
            36148,
            36168,
            36176,
            25229,
            36245,
            36283,
            42906,
            16506 // Amoor
        ];

        $toSupplierIds = [
            388,
            389,
            103,
            190,
            428,
            101,
            130,
            502,
            178,
            408,
            46,
            174,
            5,
            528,
            475,
            10,
            466
        ];

        $toSupplierTransactionAccountIds = [
            16419,
            16424,
            16460,
            16515,
            16517,
            16544,
            16588,
            16592,
            16604,
            36177,
            36178,
            36197,
            25223,
            36262,
            36289,
            39378,
            36253 // Amoor
        ];

        /**
         * @var Adapter $dbAdapter
         */
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');

        $updatePOItemTransactionAccountId = "UPDATE ga_expense_item set account_id = ? WHERE account_id = ?";
        $updateDocumentSupplierId = "UPDATE ga_documents set supplier_id = ? WHERE supplier_id = ?";
        $updateExpenseTransactionAccountToId = "UPDATE ga_expense_transaction set account_to_id = ? WHERE account_to_id = ?";
        $updateTransferTransactionAccountToId = "UPDATE ga_transfer_transactions set account_id_to = ? WHERE account_id_to = ?";
        $updateTransferTransactionAccountFromId = "UPDATE ga_transfer_transactions set account_id_from = ? WHERE account_id_from = ?";
        $updateTransactionAccountId = "UPDATE ga_transactions set account_id = ? WHERE account_id = ?";

        $deleteTransactionAccount = "DELETE FROM ga_transaction_accounts WHERE id=?";
        $deleteFromSuppliers = "DELETE FROM ga_suppliers WHERE id=?";

        foreach ($fromSupplierTransactionAccountIds as $i => $fromSupplierTransactionAccountId) {
            $result1 = $dbAdapter->createStatement($updatePOItemTransactionAccountId)->execute([$toSupplierTransactionAccountIds[$i], $fromSupplierTransactionAccountIds[$i]]);

            echo "Updated transaction account ids in expense item table - " . $result1->getAffectedRows() . PHP_EOL;

            $result2 = $dbAdapter->createStatement($updateDocumentSupplierId)->execute([$toSupplierIds[$i], $fromSupplierIds[$i]]);

            echo "Updated supplier ids in documents table - " . $result2->getAffectedRows() . PHP_EOL;

            $result3 = $dbAdapter->createStatement($updateExpenseTransactionAccountToId)->execute([$toSupplierTransactionAccountIds[$i], $fromSupplierTransactionAccountIds[$i]]);

            echo "Updated transaction account to ids in expense transactions table - " . $result3->getAffectedRows() . PHP_EOL;

            $result4 = $dbAdapter->createStatement($updateTransferTransactionAccountToId)->execute([$toSupplierTransactionAccountIds[$i], $fromSupplierTransactionAccountIds[$i]]);

            echo "Updated transaction account to ids in transfer transactions table - " . $result4->getAffectedRows() . PHP_EOL;

            $result5 = $dbAdapter->createStatement($updateTransferTransactionAccountFromId)->execute([$toSupplierTransactionAccountIds[$i], $fromSupplierTransactionAccountIds[$i]]);

            echo "Updated transaction account from ids in transfer transactions table - " . $result5->getAffectedRows() . PHP_EOL;

            $result6 = $dbAdapter->createStatement($updateTransactionAccountId)->execute([$toSupplierTransactionAccountIds[$i], $fromSupplierTransactionAccountIds[$i]]);

            echo "Updated transaction account ids in main transactions table - " . $result6->getAffectedRows() . PHP_EOL;

            $result7 = $dbAdapter->createStatement($deleteTransactionAccount)->execute([$fromSupplierTransactionAccountIds[$i]]);

            echo "Removed Transaction account - id: " . $fromSupplierTransactionAccountIds[$i] . PHP_EOL;

            $result8 = $dbAdapter->createStatement($deleteFromSuppliers)->execute([$fromSupplierIds[$i]]);

            echo "Removed Supplier account - id: " . $fromSupplierIds[$i] . PHP_EOL;
        }
    }


    public function migrateSuppliersAction()
    {
        $i=0;
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');
        $sqlSelectUsers = "SELECT firstname,lastname,id FROM ga_bo_users";
        $sqlSelectSuppliers = "SELECT name,id FROM ga_suppliers WHERE name = ?";
        $sqlSelectTransactionAccounts = "SELECT * FROM ga_transaction_accounts WHERE holder_id=? AND type=?";
        $sqlSelectExpenseItem   = "SELECT * FROM ga_expense_item WHERE account_id=?";
        $sqlUpdateExpenseItem = "UPDATE ga_expense_item set account_id=? WHERE id=?";
        $deleteFromSuppliers = "DELETE FROM ga_suppliers WHERE id=?";
        $deleteTransactionAccount = "Delete FROM ga_transaction_accounts WHERE id=?";

        $updateTransferTransactionAccountFromId = "UPDATE ga_transfer_transactions set account_id_from = ? WHERE account_id_from = ?";
        $updateTransferTransactionAccountToId = "UPDATE ga_transfer_transactions set account_id_to = ? WHERE account_id_to = ?";

        $updateExpenseTransactionAccountToId = "UPDATE ga_expense_transaction set account_to_id = ? WHERE account_to_id = ?";


        $allUsers = $dbAdapter->createStatement($sqlSelectUsers)->execute();
        echo 'Starting migration process for BO users' . PHP_EOL;
        sleep(1);
        foreach ($allUsers as $user) {
            $supplierResult = $dbAdapter->createStatement($sqlSelectSuppliers)->execute([$user['firstname'] . ' ' . $user['lastname']]);
            if ($supplierResult->count() != 1) {
                continue;
            }
            $supplier = $supplierResult->current();

            $transactionAccountsSupplier = $dbAdapter->createStatement($sqlSelectTransactionAccounts)->execute([$supplier['id'],4]);
            if ($transactionAccountsSupplier->count() != 1) {
                continue;
            }
            $transactionAccountsUSer = $dbAdapter->createStatement($sqlSelectTransactionAccounts)->execute([$user['id'],5]);
            if ($transactionAccountsUSer->count() != 1) {
                continue;
            }
            $transactionAccountSupplier = $transactionAccountsSupplier->current();
            $transactionAccountUSer = $transactionAccountsUSer->current();

            $dbAdapter->createStatement($updateTransferTransactionAccountFromId)->execute([$transactionAccountUSer['id'],$transactionAccountSupplier['id']]);
            $dbAdapter->createStatement($updateTransferTransactionAccountToId)->execute([$transactionAccountUSer['id'],$transactionAccountSupplier['id']]);

            $dbAdapter->createStatement($updateExpenseTransactionAccountToId)->execute([$transactionAccountUSer['id'],$transactionAccountSupplier['id']]);

            $expenseItemsThatHaveTheseSuppliers = $dbAdapter->createStatement($sqlSelectExpenseItem)->execute([$transactionAccountSupplier['id']]);
            foreach ($expenseItemsThatHaveTheseSuppliers as $row) {
                echo $row['id'] . '__' . $row['expense_id'] . PHP_EOL;
                $dbAdapter->createStatement($sqlUpdateExpenseItem)->execute([$transactionAccountUSer['id'], $row['id']]);
            }
            $dbAdapter->createStatement($deleteFromSuppliers)->execute([intval($supplier['id'])]);
            $dbAdapter->createStatement($deleteTransactionAccount)->execute([intval($transactionAccountSupplier['id'])]);
            echo $i++ . $supplier['name'] . '__' . $user['firstname'] . ' ' . $user['lastname'] . PHP_EOL;
            echo $transactionAccountSupplier['id'] . '__' .$transactionAccountSupplier['holder_id'] . PHP_EOL;
            echo $transactionAccountUSer['id'] . '__' .$transactionAccountUSer['holder_id'] . PHP_EOL;
            echo '----------------------' . PHP_EOL;
        }
        echo 'End migration process for BO users' . PHP_EOL;
        echo PHP_EOL . PHP_EOL . PHP_EOL .'Starting migration process for Partners' . PHP_EOL;

    }

    /**
     * Migrate Task [CURRENCY], [EXPENSE_AMOUNT], [BUDGET] parameters to Description and delete fields
     */
    public function migrateTaskOldParamsAction()
    {
        /**
         * @var \DDD\Service\Currency\Currency $currencyService
         */
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');
        $dbAdapter       = $this->getServiceLocator()->get('dbAdapter');
        $query           = 'SELECT * FROM ga_task WHERE expense_task IS NOT NULL OR budged IS NOT NULL OR currency IS NOT NULL';
        $allTasks        = $dbAdapter->createStatement($query)->execute();

        $currencies   = $currencyService->getSimpleCurrencyList();

        if ($allTasks->count() > 0) {
            foreach ($allTasks as $task) {
                $description = $task['description'];
                if (!is_null($task['expense_task']) && strlen($task['expense_task']) > 0) {
                    $description .= ' Expense: ' . $task['expense_task'];
                }
                if (!is_null($task['budged'])) {
                    $description .= ' Budget: ' . $task['budged'];
                }
                if (!is_null($task['currency'])) {
                    $description .= ' Currency: ' . $currencies[$task['currency']];
                }

                $query = 'UPDATE ga_task SET description=? WHERE id=?';
                $dbAdapter->createStatement($query)->execute([$description, $task['id']]);
            }
        }

        $query = 'ALTER TABLE ga_task DROP FOREIGN KEY fk_ga_task_currency, DROP COLUMN expense_task, DROP COLUMN budged, DROP COLUMN currency';
        $dbAdapter->createStatement($query)->execute();

        echo PHP_EOL . PHP_EOL . PHP_EOL .'Expense Task, Budget and Currency Columns are deleted' . PHP_EOL;
    }

    /**
     * Migrate Task Permission to enter field to tag
     */
    public function migrateTaskPermissionToTagAction()
    {
        // get adapter
        $dbAdapter       = $this->getServiceLocator()->get('dbAdapter');
        // get tag
        $permissionTagName = 'Permission to Enter';
        $query = 'SELECT * FROM ga_tag WHERE name = ?';
        $tag   = $dbAdapter->createStatement($query)->execute([$permissionTagName]);

        if ($tag->count() == 0) {
            echo PHP_EOL . PHP_EOL . PHP_EOL .'Permission To Enter Tag not found' . PHP_EOL;
            return false;
        }
        $tag = $tag->current();

        // get all tasks, which have permission_to_enter
        $query           = 'SELECT * FROM ga_task WHERE permission_to_enter > 0';
        $allTasks        = $dbAdapter->createStatement($query)->execute();

        // get task tags
        $query    = 'SELECT * FROM ga_task_tag WHERE tag_id = ?';
        $taskTags = $dbAdapter->createStatement($query)->execute([$tag['id']]);

        $taskTagsByTaskID = [];
        if ($taskTags->count() > 0) {
            foreach ($taskTags as $taskTag) {
                $taskTagsByTaskID[$taskTag['task_id']] = $taskTag;
            }
        }

        $taskTagCounts = 0;
        if ($allTasks->count() > 0) {
            foreach ($allTasks as $task) {
                // check if task haven't tag permission to enter
                if (!isset($taskTagsByTaskID[$task['id']])) {
                    $query = 'INSERT INTO ga_task_tag (tag_id, task_id) VALUES (?, ?)';
                    $dbAdapter->createStatement($query)->execute([$tag['id'], $task['id']]);
                    $taskTagCounts++;
                }

            }
        }

        $query = 'ALTER TABLE ga_task DROP COLUMN permission_to_enter';
        $dbAdapter->createStatement($query)->execute();

        echo PHP_EOL . PHP_EOL . PHP_EOL .'Permission To Enter migrated: Count ' . $taskTagCounts . PHP_EOL;
    }

    public function buildingSectionAction()
    {
        echo 'Start...' . PHP_EOL;
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');
        $buildingSectionDao = $this->getServiceLocator()->get('dao_apartment_group_building_sections');
        $buildingLotDao = $this->getServiceLocator()->get('dao_apartment_group_building_lots');
        $apartmentDao = $this->getServiceLocator()->get('dao_apartment_general');

        $sqlBuilding = "SELECT * FROM ga_building_details";
        $results = $dbAdapter->createStatement($sqlBuilding)->execute();
        foreach ($results as $row) {

            $buildingId = $row['apartment_group_id'];
            $kiId = $row['apartment_entry_textline_id'];
            $lockId = $row['lock_id'];
            echo 'Building - ' . $buildingId . PHP_EOL;

            // create section
            $buildingSectionId = $buildingSectionDao->save([
                'name' => 'Section 1',
                'building_id' => $buildingId,
                'lock_id' => $lockId,
                'apartment_entry_textline_id' => $kiId
            ]);

            // set building section for apartment
            $apartmentDao->save(
                ['building_section_id' => $buildingSectionId],
                ['building_id' => $buildingId]
            );

            // update product tex
            $sqlUpdate = "UPDATE ga_product_textlines set entity_id = {$buildingSectionId}  where entity_id = {$buildingId} AND entity_type = 81";
            $dbAdapter->createStatement($sqlUpdate)->execute();

            // set lot
            if ($buildingSectionId) {
                $sqlLots = "SELECT * FROM ga_parking_lot_buildings where building_id = {$buildingId}";
                $lots = $dbAdapter->createStatement($sqlLots)->execute();
                foreach($lots as $lot) {
                    $buildingLotDao->save([
                        'lot_id' => $lot['lot_id'],
                        'building_section_id' => $buildingSectionId,
                    ]);
                }
            }
        }

        // drop and alter section
        $sqlDrop = "DROP TABLE `backoffice`.`ga_parking_lot_buildings`; ALTER TABLE `backoffice`.`ga_building_details`
DROP COLUMN `lock_id`,
DROP COLUMN `apartment_entry_textline_id`;update ga_reservations set date_from = '2015-10-14' WHERE res_number = '50749MU';";
        $dbAdapter->createStatement($sqlDrop)->execute();
        echo 'End' . PHP_EOL;
    }

    public function removeDuplicateTransactionAction()
    {
        /**
         * @var Adapter $adapter
         */
        $this->initCommonParams($this->getRequest());
        $adapter = $this->getServiceLocator()->get('dbAdapter');

        $adapter->createStatement('delete from ga_transfer_transactions where id = ?;')->execute([340]);
        $adapter->createStatement('delete from ga_transactions where id = ?;')->execute([11653]);
        $this->outputMessage('[purple]Duplicate transaction has been deleted.');
    }

    /**
     * Cleanup not building facility, usage and police textlines
     */
    public function cleanupNotBuildingTextlinesAction()
    {
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');

        // remove all not necessary group facility textlines
        $sql = 'SELECT * FROM ga_apartment_groups WHERE usage_building = ?';
        $groups = $dbAdapter->createStatement($sql)->execute([0]);

        foreach ($groups as $group) {
            $sql = 'DELETE FROM ga_product_textlines
                           WHERE (entity_type=? AND entity_id=?)
                           OR (entity_type=? AND entity_id=?)
                           OR (entity_type=? AND entity_id=?)';
            $dbAdapter->createStatement($sql)->execute([
                Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_FACILITIES,
                $group['id'],
                Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_USAGE,
                $group['id'],
                Translation::PRODUCT_TEXTLINE_TYPE_APARTMENT_BUILDING_POLICIES,
                $group['id']
            ]);
        }

        $this->outputMessage('[light_green]' . sizeof($groups) . ' facilities, usages and policies are removed');
    }

    public function createPartnerMissingTransactionAccountsAction()
    {
        $this->initCommonParams($this->getRequest());
        $this->outputMessage('[light_blue]Adding missing transaction accounts for partners...');
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');
        $sql = '
            SELECT gid FROM ga_booking_partners bp
            LEFT JOIN ga_transaction_accounts ta ON (bp.gid = ta.holder_id && ta.type = "3")
            WHERE ta.id IS NULL AND bp.commission > 0';

        $partners = $dbAdapter->createStatement($sql)->execute([0]);

        $insertData = [];
        foreach($partners as $partner) {
            array_push($insertData, [
                'holder_id' => $partner['gid'],
                'type' => 3
            ]);
        }

        /** @var \DDD\Dao\Finance\Transaction\TransactionAccounts $transactionAccountDao */
        $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
        $transactionAccountDao->multiInsert($insertData, true);

        $this->outputMessage('[light_blue]Done!');
    }

    public function migrateMovedResAction()
    {
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');
        $sql = '
            SELECT id, moved_res, moved_from_res FROM ga_reservations
            WHERE moved_res IS NOT NULL OR moved_from_res IS NOT NULL';

        $reservations = $dbAdapter->createStatement($sql)->execute([0]);

        /**
         * @var ActionLogs $actionLogDao
         */
        $actionLogDao = $this->getServiceLocator()->get('dao_action_logs_action_logs');

        foreach ($reservations as $reservation) {
            if ($reservation['moved_res'] != '') {
                $actionLogDao->insert([
                    'module_id' => 1,
                    'identity_id' => $reservation['id'],
                    'user_id' => 117,
                    'action_id' => 17,
                    'timestamp' => date('Y-m-d'),
                    'value' => "Development Team: Migration: This reservation was Cancelled (Moved) to #" . $reservation['moved_res']
                ]);
            }

            if ($reservation['moved_from_res'] != '') {
                $actionLogDao->insert([
                    'module_id' => 1,
                    'identity_id' => $reservation['id'],
                    'user_id' => 117,
                    'action_id' => 17,
                    'timestamp' => date('Y-m-d'),
                    'value' => "Development Team: Migration: This reservation was Moved from #" . $reservation['moved_from_res']
                ]);
            }
        }
    }

    /**
     * Migrate old supplier data to new
     */
    public function migrateSupplierDataAction()
    {
        $dbAdapter     = $this->getServiceLocator()->get('dbAdapter');
        $oldSupplierID = 103;
        $newSupplierID = 428;

        // update from orders
        $sql = "UPDATE ga_wm_orders SET supplier_id={$newSupplierID} WHERE supplier_id={$oldSupplierID}";
        $dbAdapter->createStatement($sql)->execute();

        // update from documents
        $sql = "UPDATE ga_documents SET supplier_id={$newSupplierID} WHERE supplier_id={$oldSupplierID}";
        $dbAdapter->createStatement($sql)->execute();

        // get old supplier transaction account ID [16517]
        $sql        = 'SELECT * FROM `ga_transaction_accounts` WHERE `type` = ? AND `holder_id` = ?';
        $oldAccount = $dbAdapter->createStatement($sql)->execute([Account::TYPE_SUPPLIER, $oldSupplierID])->current();

        $sql        = 'SELECT * FROM `ga_transaction_accounts` WHERE `type` = ? AND `holder_id` = ?';
        $newAccount = $dbAdapter->createStatement($sql)->execute([Account::TYPE_SUPPLIER, $newSupplierID])->current();

        if ($newAccount && $oldAccount) {
            // update expense item
            $sql = "UPDATE ga_expense_item set account_id = {$newAccount['id']} WHERE account_id = {$oldAccount['id']}";
            $dbAdapter->createStatement($sql)->execute();
            // update expense transactions
            $sql = "UPDATE ga_expense_transaction set account_to_id = {$newAccount['id']} WHERE account_to_id = {$oldAccount['id']}";
            $dbAdapter->createStatement($sql)->execute();
            // update transfer transactions
            $sql = "UPDATE ga_transfer_transactions set account_id_to = {$newAccount['id']} WHERE account_id_to = {$oldAccount['id']}";
            $dbAdapter->createStatement($sql)->execute();
            $sql = "UPDATE ga_transfer_transactions set account_id_from = {$newAccount['id']} WHERE account_id_from = {$oldAccount['id']}";
            $dbAdapter->createStatement($sql)->execute();

            $sql = "UPDATE ga_transactions set account_id = {$newAccount['id']} WHERE account_id = {$oldAccount['id']}";
            $dbAdapter->createStatement($sql)->execute();

            $this->outputMessage('Supplier is migrated');
        } else {
            $this->outputMessage('Suppliers not found');
        }
    }

    /**
     * Change order shipping status to Canceled if status is Rejected
     */
    public function changeRejectedOrdersToCanceledShippingStatusAction()
    {
        $dbAdapter  = $this->getServiceLocator()->get('dbAdapter');
        $sql        = "UPDATE ga_wm_orders SET status_shipping=? WHERE status=?";
        $dbAdapter->createStatement($sql)->execute([Order::STATUS_CANCELED, Order::STATUS_ORDER_REJECTED]);
        $this->outputMessage('Order statuses Successfully changed');
    }


    public function recalculateBalanceForExpensesWhichTransactionCreationDateIsNotTheSameAsTheirItemCreationDateAction()
    {
      $dbAdapter  = $this->getServiceLocator()->get('dbAdapter');
      $currencyVaultService = $this->getServiceLocator()->get('service_currency_currency_vault');

      $sqlSelectAllExpensesWhoseBalanceIsNot0 = "SELECT * FROM ga_expense WHERE transaction_balance<>0";
      $sqlSelectParticularPoById = "SELECT * FROM ga_expense WHERE id=?";
      $sqlSelectItemsOfPoThatHaveTransactionsAttached = "SELECT * FROM ga_expense_item WHERE expense_id=? AND transaction_id IS NOT NULL";
      $sqlSelectTransactionById = "SELECT * FROM ga_expense_transaction WHERE id=?";
      $sqlSelectCurrencyCodeByID = "SELECT * FROM ga_currency WHERE id=?";
      $sqlSelectMoneyTransactionById = "SELECT * FROM ga_money_accounts WHERE id=?";
      $sqlUpdateExpense = "UPDATE ga_expense SET transaction_balance=?,ticket_balance=? WHERE id=?";

      $allExpensesWhoseBalanceIsNot0 = $dbAdapter->createStatement($sqlSelectAllExpensesWhoseBalanceIsNot0)->execute([24270]);
        $i = 0;
        foreach ($allExpensesWhoseBalanceIsNot0 as $expense) {

            $poCurrencyCode = $dbAdapter->createStatement($sqlSelectCurrencyCodeByID)->execute([$expense['currency_id']])->current()['code'];
            $itemsOfPoThatHaveTransactionsAttached = $dbAdapter->createStatement($sqlSelectItemsOfPoThatHaveTransactionsAttached)->execute([$expense['id']]);


             foreach ($itemsOfPoThatHaveTransactionsAttached as $item) {
                 $itemCurrencyCode = $dbAdapter->createStatement($sqlSelectCurrencyCodeByID)->execute([$item['currency_id']])->current()['code'];
                 $itemDateArray = explode(' ', $item['date_created']);
                 $itemDateCreated = $itemDateArray[0];
                 $transaction = $dbAdapter->createStatement($sqlSelectTransactionById)->execute([$item['transaction_id']])->current();
                 $moneyAccountCurrencyId = $dbAdapter->createStatement($sqlSelectMoneyTransactionById)->execute([$transaction['money_account_id']])->current()['currency_id'];
                 $transactionCurrencyCode = $dbAdapter->createStatement($sqlSelectCurrencyCodeByID)->execute([$moneyAccountCurrencyId])->current()['code'];
                 $transactionDateArray = explode(' ', $transaction['creation_date']);
                 $transactionDateCreated = $transactionDateArray[0];
                 if ($transactionCurrencyCode == $poCurrencyCode) {
                    $transactionAmountInPOCurrencyByTransactionCreationDateConversionRate = $transaction['amount'];
                 } else {
                    $transactionAmountInPOCurrencyByTransactionCreationDateConversionRate =
                        $currencyVaultService->convertCurrency($transaction['amount'], $transactionCurrencyCode, $poCurrencyCode, $transactionDateCreated);
                 }
                 $transactionAmountInPOCurrencyByTransactionCreationDateConversionRate =
                     number_format((float)abs($transactionAmountInPOCurrencyByTransactionCreationDateConversionRate), 2, '.', '');
                 $transactionAmountInPOCurrencyByItemCreationDateConversionRate =
                     $currencyVaultService->convertCurrency($transaction['amount'], $transactionCurrencyCode, $poCurrencyCode, $itemDateCreated);
                 $transactionAmountInPOCurrencyByItemCreationDateConversionRate =
                     number_format((float)abs($transactionAmountInPOCurrencyByItemCreationDateConversionRate), 2, '.', '');
                 if ($transactionAmountInPOCurrencyByItemCreationDateConversionRate == $transactionAmountInPOCurrencyByTransactionCreationDateConversionRate) {
                     continue;
                 }
                 $currentExpense = $dbAdapter->createStatement($sqlSelectParticularPoById)->execute([$expense['id']])->current();
                 $newPOTransactionBalance = (float)$currentExpense['transaction_balance'] + $transactionAmountInPOCurrencyByTransactionCreationDateConversionRate - $transactionAmountInPOCurrencyByItemCreationDateConversionRate;
                 $newPOTicketBalance = (float)$currentExpense['ticket_balance'] + $transactionAmountInPOCurrencyByTransactionCreationDateConversionRate - $transactionAmountInPOCurrencyByItemCreationDateConversionRate;
                 $dbAdapter->createStatement($sqlUpdateExpense)->execute([$newPOTransactionBalance, $newPOTicketBalance, $expense['id']]);
                 $finalExpense = $dbAdapter->createStatement($sqlSelectParticularPoById)->execute([$expense['id']])->current();
                 echo ++$i . PHP_EOL .
                     'ItemId = ' . $item['id'] . ' ItemCreationDate = ' .  $itemDateCreated . ' itemCurrency = ' . $itemCurrencyCode . ' itemAmount = ' . $item['amount'] . PHP_EOL .
                      'TransactionId = ' . $transaction['id'] . ' TransactionCreationDate = ' . $transactionDateCreated . ' TransactionCurrency = ' . $transactionCurrencyCode . ' TransactionAmount = ' . $transaction['amount'] . PHP_EOL .
                     'transactionAmountInPOCurrencyByTransactionCreationDateConversionRate = ' . $transactionAmountInPOCurrencyByTransactionCreationDateConversionRate . PHP_EOL .
                     'transactionAmountInPOCurrencyByItemCreationDateConversionRate =        ' . $transactionAmountInPOCurrencyByItemCreationDateConversionRate . PHP_EOL .
                      'poId = ' . $expense['id'] . ' poTicketBalanceBefore = ' . $expense['ticket_balance'] . ' poTicketBalanceAfter = ' . $finalExpense['ticket_balance'] . ' poTransactionBalanceBefore = ' . $expense['transaction_balance'] . ' poTransactionBalanceAfter = ' . $finalExpense['transaction_balance'] . ' PO Currency = ' . $poCurrencyCode . PHP_EOL .
                     '_________________________________________________________________________________________' . PHP_EOL . PHP_EOL;
             }
        }
    }

    /**
     * Set Quantity types "piece" if it is 0
     */
    public function setOrderQuantityDefaultTypeAction()
    {
        $dbAdapter   = $this->getServiceLocator()->get('dbAdapter');

        $pieceTypeID = Order::ORDER_QUANTITY_TYPE_PIECE;
        $sql         = "UPDATE ga_wm_orders SET quantity_type={$pieceTypeID} WHERE quantity_type=0";
        $dbAdapter->createStatement($sql)->execute();
        echo PHP_EOL . 'Quantity Types SuccessFully Migrated' . PHP_EOL;
    }

    public function moveParkingTextlineAction()
    {
        $dbAdapter      = $this->getServiceLocator()->get('dbAdapter');
        $poTextlineDao  = $this->getServiceLocator()->get('dao_textline_group');
        $parkingDao     = $this->getServiceLocator()->get('dao_parking_general');
        $uniTextlineDao = $this->getServiceLocator()->get('dao_textline_universal');

        $sql            = "SELECT `p`.`id` AS `pid`, u.*, `p`.`direction_textline_id` AS `parking_textline` FROM `ga_parking_lots` AS `p` JOIN `ga_un_textlines` AS `u` ON `p`.`direction_textline_id` = `u`.`id`";
        $result         = $dbAdapter->createStatement($sql)->execute();

        foreach ($result as $row) {

            $insertData = [
                'entity_id'     => $row['pid'],
                'type'          => 5,
                'entity_type'   => 200,
                'en'            => $row['en'],
                'en_html_clean' => strip_tags($row['en']),
            ];

            $poTextlineDao->save($insertData);
            $ptId = $poTextlineDao->getLastInsertValue();

            $parkingDao->save(['direction_textline_id' => $ptId], ['id' => $row['pid']]);

            $uniTextlineDao->delete(['id' => $row['id']]);

            var_dump($row['id'], 'Change aprking textline id from: ' . $row['parking_textline'] . ' to: ' . $poTextlineDao->getLastInsertValue());
        }
    }

    public function migrateUnTextlinePageAction()
    {
        $uniTextlineDao = new \DDD\Dao\Textline\Universal($this->getServiceLocator(), 'ArrayObject');
        $uniTextlineRelDao = $this->getServiceLocator()->get('dao_textline_universal_page_rel');

        $textlines = $uniTextlineDao->fetchAll();

        foreach ($textlines as $value) {
            if ((int)$value['page_id']) {
                $uniTextlineRelDao->save(['textline_id' => $value['id'], 'page_id' => $value['page_id']]);
                var_dump('Add page id for textline with id: ' .  $value['id']);
            }
        }
    }

    public function migrateHovhannesPurchaseOrderItemsThatHaveZeroAmountAction()
    {
        /**
         * @var Adapter $dbAdapter
         */
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');

        $select = "
            SELECT
                item.id as item_id,
                item.transaction_id,
                item.amount as item_amount,
                trans.amount as transaction_amount
            FROM
                ga_expense_item as item
            INNER JOIN ga_expense_transaction as trans on item.transaction_id = trans.id
            WHERE
                item.creator_id = 122
                AND
                item.amount=0
        ";

        $result = $dbAdapter->createStatement($select)->execute();

        foreach ($result as $row) {
            $update = "UPDATE ga_expense_item SET amount = " . abs($row['transaction_amount']) . " WHERE id = " . $row['item_id'];
            $updateResult = $dbAdapter->createStatement($update)->execute();
        }
    }

    public function upgradeCreditCardFraudHashesAction()
    {
        /**
         * @var Fraud $fraudCreditCardService
         */
        $fraudCreditCardService = $this->getServiceLocator()->get('service_fraud_cc');

        /**
         * @var Adapter $dbAdapter
         */
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');

        $select = "
            SELECT
              cc_id,
              date_added
            FROM ga_fraud_cc
        ";

        $result = $dbAdapter->createStatement($select)->execute();

        $resultArray = iterator_to_array($result);
        foreach ($resultArray as $row) {
            $fraudCreditCardService->removeCreditCardFromBlackList($row['cc_id']);
        }

        foreach ($resultArray as $row) {
            $fraudCreditCardService->addCreditCardToBlackList($row['cc_id']);
            $updateDateAdded = "UPDATE ga_fraud_cc SET date_added = '" . $row['date_added'] . "' WHERE cc_id = " . $row['cc_id'];
            $updateResult = $dbAdapter->createStatement($updateDateAdded)->execute();
        }
    }

    public function migrateRatesAction()
    {
        $this->initCommonParams($this->getRequest());

        /** @var \DDD\Dao\Apartment\Inventory $apartmentInventoryDao */
        $apartmentInventoryDao = $this->getServiceLocator()->get('dao_apartment_inventory');

        $mapping = [
            812 => [
                'apartment_name' => 'Washington Shade',
                'targets' => [
                    923 => 'Washington Hills',
                    924 => 'Washington Place',
                    922 => 'Washington Promenade',
                    921 => 'Washington Reverence',
                    920 => 'Washington Star',
                    935 => 'Washington Victory'
                ]
            ],
            818 => [
                'apartment_name' => 'Washington Morning',
                'targets' => [
                    927 => 'Washington Blue',
                    928 => 'Washington Glory',
                    929 => 'Washington Heritage',
                    936 => 'Washington Independence',
                    925 => 'Washington Red',
                    926 => 'Washington White',
                ]
            ]
        ];

        foreach ($mapping as $sourceId => $data) {
            $this->outputMessage('[light_blue]Duplicating rates of apartment [yellow]' . $data['apartment_name']);
            $sql                = "SELECT a_i.date, a_i.price, a_r.capacity, a_r.type, a_i.is_lock_price, a_r.name AS rate
            FROM ga_apartment_inventory AS a_i
            INNER JOIN ga_apartment_rates AS a_r ON a_i.rate_id = a_r.id
            WHERE a_i.apartment_id = $sourceId ORDER BY a_i.date;";
            $statement          = $apartmentInventoryDao->getAdapter()->createStatement($sql);
            $apartmentInventory = $statement->execute();

            $this->outputMessage(
                '[blue]Applying onto apartments [yellow]'
                . implode('[blue], [yellow]', array_values($data['targets']))
            );
            $targetApartmentIds = implode(', ', array_keys($data['targets']));
            foreach ($apartmentInventory as $apartment) {
                $this->outputMessage('[blue]' . date('M j, Y', strtotime($apartment['date'])) . ' - [purple]' . $apartment['rate']);
                $sqlForUpdate = "UPDATE ga_apartment_inventory as a_i
                        INNER JOIN ga_apartment_rates as a_r ON a_i.rate_id = a_r.id
                        SET a_i.price = {$apartment['price']},
                        a_i.is_lock_price = {$apartment['is_lock_price']}
                        WHERE a_i.apartment_id IN ($targetApartmentIds)
                        AND a_i.date = '{$apartment['date']}'
                        AND a_r.capacity = {$apartment['capacity']}
                        AND a_r.type = {$apartment['type']}";
                $statement    = $apartmentInventoryDao->adapter->createStatement($sqlForUpdate);
                $statement->execute();
            }
        }
        $this->outputMessage('[success]Done!');
    }

    public function fixAdditionalTaxChargesAction()
    {
        $this->initCommonParams($this->getRequest());

        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');

        $sql = "
SELECT ch.customer_amount, ch.acc_amount, ch.reservation_nightly_date, ch.reservation_id FROM ga_reservation_charges AS ch
INNER JOIN ga_reservations r ON r.id = ch.reservation_id
INNER JOIN ga_booking_partners p ON r.partner_id = p.gid
WHERE
  ch.addons_type = 1
  AND p.additional_tax_commission = 1
  AND ch.`date` >= '2015-11-20'
";
        $results = $dbAdapter->createStatement($sql)->execute();

        $resApartmentPrice = [];
        foreach ($results as $row) {
            $resApartmentPrice[$row['reservation_id']][$row['reservation_nightly_date']] = [
                'acc_amount' => $row['acc_amount'],
                'customer_amount' => $row['customer_amount'],
            ];
        }

        $sql = "
SELECT ch.id, ch.acc_amount, ch.customer_amount, ch.addons_value, ch.reservation_nightly_date, ch.reservation_id, r.res_number
FROM ga_reservation_charges AS ch
INNER JOIN ga_reservations r ON r.id = ch.reservation_id
INNER JOIN ga_booking_partners p ON r.partner_id = p.gid
WHERE
  ch.tax_type = 1
  AND ch.addons_type IN (2, 3, 4, 5)
  AND p.additional_tax_commission = 1
  AND ch.commission > 0
  AND ch.`date` >= '2015-11-20'
";
        $taxCharges = $dbAdapter->createStatement($sql)->execute();

        foreach ($taxCharges as $taxCharge) {
            $apartmentPrice        = $resApartmentPrice[$taxCharge['reservation_id']][$taxCharge['reservation_nightly_date']];
            $correctAccAmount      = round($taxCharge['addons_value'] / 100 * $apartmentPrice['acc_amount'], 2);
            $correctCustomerAmount = round($taxCharge['addons_value'] / 100 * $apartmentPrice['customer_amount'], 2);

            if ($taxCharge['acc_amount'] != $correctAccAmount) {
                $this->outputMessage('[error]Wrong additional tax on: res. N ' . $taxCharge['res_number']);
                $customerAmountUpdate = '';
                if ($taxCharge['customer_amount']) {
                    $customerAmountUpdate = ', customer_amount = ' . $correctCustomerAmount;
                }
                $updateQuery = "UPDATE ga_reservation_charges SET acc_amount = $correctAccAmount $customerAmountUpdate WHERE id = {$taxCharge['id']}";
                $dbAdapter->createStatement($updateQuery)->execute();
                $this->outputMessage('[success]Fixed!');
            }
        }
    }
}
