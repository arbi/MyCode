<?php

namespace Console\Controller;

use Library\Controller\ConsoleBase;

/**
 * Class DatabaseController
 * @package Console\Controller
 */
class DatabaseController extends ConsoleBase
{
    private $originalDatabaseName   = FALSE;
    private $safeDatabaseName       = FALSE;

    private $dbUsername = FALSE;
    private $dbPassword = FALSE;

    private $backupPath = FALSE;

    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', 'show');

        switch ($action) {
            case 'safe-backup': $this->safeBackupAction();
                break;
            default :
                $this->helpAction();
        }
    }

    public function safeBackupAction()
    {
        try {
            // Prepare database config
            $this->prepareDatabaseConfig();

            $dbConnector = $this->getDbConnector();

            // Create database connector
            if(($dbSafeConnector = $this->getDbConnector($this->safeDatabaseName))) {
                $this->outputMessage("    Remove old safe database: `$this->safeDatabaseName`...");
                $dbConnector->exec("DROP DATABASE `$this->safeDatabaseName`");
            }

            // Clean safe database
            $this->outputMessage("    Creating new database: `$this->safeDatabaseName`...");
            $dbConnector->exec("CREATE DATABASE `$this->safeDatabaseName` CHARACTER SET utf8 COLLATE utf8_general_ci");

            $dbSafeConnector = $this->getDbConnector($this->safeDatabaseName);

            // Backing up original database
            $this->outputMessage("    Backing up original database: `$this->originalDatabaseName`...");
            $backupFilename = $this->backupPath.date("Y.m.d-H.i.s", time()).'.sql';
            $dbDumpCommand = "mysqldump -u $this->dbUsername -p$this->dbPassword $this->originalDatabaseName > $backupFilename";
            shell_exec($dbDumpCommand);

            // Fill to safe database
            $this->outputMessage("    Filling backup to safe database: `$this->safeDatabaseName`...");
            $dbFillCommand = "mysql -u $this->dbUsername -p$this->dbPassword $this->safeDatabaseName < $backupFilename";
            shell_exec($dbFillCommand);

            // Remove original backup file
            $this->outputMessage("    Remove original backup file: $backupFilename");
            unlink($backupFilename);

            // "Safing" Database
            $dbSafeConnector->exec($this->getSafeSqlString());

            // Backing up safe database
            $this->outputMessage("    Backing up safe database: `$this->safeDatabaseName`...");
            $safeBackupFilename = $this->backupPath.date("Y.m.d-H.i.s", time()).'-safe';
            $safeBackupFilenameFull = $safeBackupFilename.'.sql';
            $safeDbDumpCommand = "mysqldump -u $this->dbUsername -p$this->dbPassword $this->safeDatabaseName > $safeBackupFilenameFull";
            shell_exec($safeDbDumpCommand);

            // Compress dump
            $this->outputMessage("    Compress safe backup to archive: `$safeBackupFilename.tar.gz`...");
            $compressDumpedFileCommand = 'tar -czf ' . $safeBackupFilename . '.tar.gz ' . $safeBackupFilenameFull;
            shell_exec($compressDumpedFileCommand);

            $this->outputMessage("    Remove safe backup file: `$safeBackupFilenameFull`...");
            unlink($safeBackupFilenameFull);

            echo PHP_EOL.PHP_EOL.'    New safe database backup successfully created:'.PHP_EOL
                .'    '.$safeBackupFilename . '.tar.gz '
                .PHP_EOL.PHP_EOL;

            // Remove new safe database
            $this->outputMessage("    Remove new safe database: `$this->safeDatabaseName`...");
            $dbConnector->exec("DROP DATABASE `$this->safeDatabaseName`");

            $this->outputMessage("    DONE");

        } catch (\Exception $e) {
            echo "ERROR PMF!".PHP_EOL.PHP_EOL;
            var_dump($e);
        }
    }



    private static function getSafeSqlString()
    {
        return <<<'SQL'
/* data cleansing script version 2 */

/* disconnect all apartments from cubilis */
/* clear cubilis connection credentials */
update `ga_apartment_details`
    set
      `ga_apartment_details`.`cubilis_id` = 0,
      `ga_apartment_details`.`cubilis_us` = '',
      `ga_apartment_details`.`cubilis_pass` = '',
      `ga_apartment_details`.`sync_cubilis` = 0
      where `ga_apartment_details`.`apartment_id` <> 42;

update `ga_apartels`
    set
      `ga_apartels`.`cubilis_id` = 0,
      `ga_apartels`.`cubilis_username` = '',
      `ga_apartels`.`cubilis_password` = '',
      `ga_apartels`.`sync_cubilis` = 0
      where `ga_apartels`.`id` <> 1;

/* clear guest email addresses, cc last 4 digits, cc cvc codes*/
update `ga_reservations`
    set
      `ga_reservations`.`guest_phone` = '',
      `ga_reservations`.`guest_zip_code` = '',
      `ga_reservations`.`guest_travel_phone` = '',
      `ga_reservations`.`guest_email` = 'test@ginosi.com',
      `ga_reservations`.`pin` = 1111,
      `ga_reservations`.`secondary_email` = NULL;

/* clear customer email */
update `ga_customers`
    set
        `ga_customers`.`email` = 'test@ginosi.com';

/* clear customer cc data: part of card number, cvc */
update `ga_tokens`
    set
        `ga_tokens`.`token` = 'sample_token',
        `ga_tokens`.`salt` = 'sample_sal',
        `ga_tokens`.`first_digits` = 'KFCuVtrAm1yGzyp9Eye9kc5Aq5fz9c3KsgvJsnr+x+E=';

/* clear customer cc data from queue: card number, holder name, cvc */
truncate `ga_cc_creation_queue`;

/* clear ga_external_accounts */
delete from ga_external_accounts;

/* clear ga_bo_salary_schemes */
delete from ga_bo_salary_schemes;

/* clear permissions */
delete from ga_user_groups
  where user_id in (111,117,159,161,163,171,222,234,312,14,195,387,405);

/* Khachik B. */
insert into ga_user_groups (user_id, group_id)
    select '111', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '111', id from ga_ud_dashboards;

/* Tigran P. */
insert into ga_user_groups (user_id, group_id)
    select '117', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '117', id from ga_ud_dashboards;

/* Tigran T. */
insert into ga_user_groups (user_id, group_id)
    select '159', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '159', id from ga_ud_dashboards;

/* Eduard A. */
insert into ga_user_groups (user_id, group_id)
    select '161', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '161', id from ga_ud_dashboards;

/* Aram B. */
insert into ga_user_groups (user_id, group_id)
    select '163', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '163', id from ga_ud_dashboards;

/* Christina T. */
insert into ga_user_groups (user_id, group_id)
    select '171', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '171', id from ga_ud_dashboards;

/* Tigran G. */
insert into ga_user_groups (user_id, group_id)
    select '222', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '222', id from ga_ud_dashboards;

/* Arbi B. */
insert into ga_user_groups (user_id, group_id)
    select '234', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '234', id from ga_ud_dashboards;

/* Hrayr P. */
insert into ga_user_groups (user_id, group_id)
    select '312', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '312', id from ga_ud_dashboards;

/* Astghik H. */
insert into ga_user_groups (user_id, group_id)
    select '14', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '14', id from ga_ud_dashboards;

/* Kristian M. */
insert into ga_user_groups (user_id, group_id)
    select '195', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '195', id from ga_ud_dashboards;

/* Harut G. */
insert into ga_user_groups (user_id, group_id)
    select '387', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '387', id from ga_ud_dashboards;

/* Vardan G.. */
insert into ga_user_groups (user_id, group_id)
    select '405', id from ga_groups;
insert into ga_bo_user_dashboards (user_id, dashboard_id)
    select '405', id from ga_ud_dashboards;

/* clear salaries and personnel expenses */
update `ga_expense_item`
  set
      `ga_expense_item`.`amount` = 42,
      `ga_expense_item`.`currency_id` = 50
  where
      `ga_expense_item`.`sub_category_id` in (47, 48, 83);

/* clear expense item comments */
update `ga_expense_item`
  set
      `ga_expense_item`.`comment` = '';

/* clear Lock values */
update `ga_lock_settings`
  set
      `ga_lock_settings`.`value` = 1111;

/* set password '123456' for all backoffice users */
/* clear personal phones, addresses, evaluation periods */
update `ga_bo_users`
  set
      `ga_bo_users`.`password` = '$2y$10$cdIVAXRTiNZT5PT/gwyXO.aSUWujy0CADH.lbO2Ghf/8mdmHAAifW',
      `ga_bo_users`.`personal_phone` = '',
      `ga_bo_users`.`address_permanent` = '',
      `ga_bo_users`.`address_residence` = '',
      `ga_bo_users`.`period_of_evaluation` = '0',
      `ga_bo_users`.`vacation_days` = '20';

/* clear evaluation data  */
truncate ga_bo_user_evaluation_values;
delete from `ga_bo_user_evaluations`;

/* clear vacation data */
delete from `ga_bo_user_vacations`;

/* clear employee documents data*/
truncate ga_bo_user_documents;

/* truncate notifications data*/
truncate `ga_notifications`;

/* truncate applicant comments*/
TRUNCATE `ga_hr_applicant_comments`;

/* remove contacts */
TRUNCATE TABLE `ga_contacts`;

/* clear all venues data */
TRUNCATE `ga_venue_charges`;
DELETE FROM `ga_venues`;

/* Clear Inventory Synchronization Queue */
delete from `ga_inventory_synchronization_queue`;

/* Clear Applicants and Interviews data */
delete from `ga_hr_interview_participants`;
delete from `ga_hr_interviews`;
delete from `ga_hr_applicants`;

/* update ga_money_accounts */
UPDATE `ga_money_accounts`
    SET
        `account_ending` = '1111',
        `bank_account_number` = '11111111',
        `description` = 'TEST DESCRIPTION';

/* Zerofill ga_money_accounts balance */
UPDATE `ga_money_accounts` SET `balance` = '0';

/* update ga_bank */
UPDATE `ga_bank`
    SET
        `bic` = 'TEST BIC';

/* Update ga_documents */
UPDATE `ga_documents`
    SET
        `signatory_id` = null,
        `legal_entity_id` = null,
        `description` = '',
        `username` = '',
        `password` = '',
        `account_number` = '',
        `account_holder` = '',
        `url` = '',
        `attachment` = '',
        `valid_from` = null,
        `valid_to` = null;


UPDATE
    `ga_expense_transaction`
    INNER JOIN
      `ga_transaction_accounts` ON `ga_transaction_accounts`.`holder_id` = `ga_expense_transaction`.`account_to_id`
    SET
      `ga_expense_transaction`.`amount` = 42
    WHERE
      `ga_transaction_accounts`.`type` = 1;

UPDATE `ga_expense`
    SET `purpose`='REPLACED PURPOSE'
    WHERE 1;

    /* CREATING UNIT TESTER USER*/
    INSERT INTO ga_bo_users
(`id`,`manager_id`,`password`, `firstname`, `lastname`,
 `disabled`,`email`,`city_id`,`reporting_office_id`,`birthday`,
 `avatar`,`last_login`,`start_date`,`end_date`, `vacation_days`,
 `personal_phone`,`business_phone`,`emergency_phone`,`house_phone`, `address_permanent`,`address_residence`,
 `timezone`,`schedule_type`,`schedule_start`,`position`, `department_id`,
 `vacation_days_per_year`,`system`,`external`,`alt_email`,`period_of_evaluation`, `asana_id`,
 `badge_next`,`employment`,`country_id`,`living_city`, `sick_days`
)
    VALUES
(13,117,'$2y$10$cdIVAXRTiNZT5PT/gwyXO.aSUWujy0CADH.lbO2Ghf/8mdmHAAifW','Unit','Tester',
 0,'test@ginosi.com',6,0,NULL ,
 '1427204890_0_150.png',NOW() ,'2015-08-20',NULL ,20,
 '','','','','','',
 'Asia/Yerevan',1,'2015-05-25','Tester',14,
 0,1,0,'',0,0,
 0,100,2,'Yerevan',-1
);


/* CREATING MOBILE USER */
/** INSERT INTO `ga_bo_users`
 (`id`,`manager_id`,`password`, `firstname`, `lastname`,
 `disabled`,`email`,`city_id`,`reporting_office_id`,`birthday`,
 `avatar`,`last_login`,`start_date`,`end_date`, `vacation_days`,
 `personal_phone`,`business_phone`,`emergency_phone`,`house_phone`, `address_permanent`,`address_residence`,
 `timezone`,`schedule_type`,`schedule_start`,`position`, `department_id`,
 `vacation_days_per_year`,`system`,`external`,`alt_email`,`period_of_evaluation`, `asana_id`,
 `badge_next`,`employment`,`country_id`,`living_city`, `sick_days`
 )
 VALUES
 (12,117,'$2y$10$cdIVAXRTiNZT5PT/gwyXO.aSUWujy0CADH.lbO2Ghf/8mdmHAAifW','App','User',
 0,'app.user@ginosi.com',6,0,NULL ,
 '',NOW() ,'2015-08-20',NULL ,20,
 '','','','','','',
 'Asia/Yerevan',1,'2015-05-25','Tester',14,
 0,0,0,'',0,0,
 0,100,2,'Yerevan',-1
 );

 INSERT INTO ga_user_groups (`user_id`, `group_id`)
   SELECT '12', `id` FROM `ga_groups` AS `gg` WHERE `gg`.`id` IN (136, 137, 138); */

INSERT INTO ga_user_groups (`user_id`, `group_id`)
  SELECT '13', `id` FROM ga_groups;

INSERT INTO ga_bo_user_dashboards (`user_id`, `dashboard_id`)
  SELECT '13', id FROM ga_ud_dashboards
  WHERE active=1;

INSERT INTO ga_concierge_dashboard_access (`user_id`, `apartment_group_id`)
  SELECT '13', id FROM ga_apartment_groups
  WHERE active=1 AND usage_concierge_dashboard=1;

INSERT INTO ga_team_staff (`team_id`,`user_id`,`type`)
    VALUES
      (14,13,2);
TRUNCATE `backoffice`.`ga_espm`;
    /* END CREATING UNIT TESTER USER*/
SQL;
    }

    /**
     * @param null $dbName
     * @return bool|\PDO
     */
    private function getDbConnector($dbName = NULL)
    {
        try {
            if (is_null($dbName)) {
                $dbName = '';
            } else {
                $dbName = ';dbname='.$dbName;
            }

            $dbConnect = new \PDO(
                'mysql:host=localhost'.$dbName.';charset=utf8',
                $this->dbUsername,
                $this->dbPassword
            );

            if (is_object($dbConnect)) {
                return $dbConnect;
            }

            return false;

        } catch (\Exception $e) {
            $this->outputMessage("    Error with Database! " . $e->getMessage());
            return false;
        }
    }

    private function prepareDatabaseConfig()
    {
        $config = $this->getServiceLocator()->get('config');

        $this->dbUsername = $config['database_params']['username'];
        $this->dbPassword = $config['database_params']['password'];
        $this->backupPath = $config['database_params']['backup_path'];

        $this->originalDatabaseName = 'backoffice';
        $this->safeDatabaseName     = 'backoffice_safe';
    }

    /**
     * echo help
     */
    public function helpAction()
    {
        echo <<<USAGE

 \e[0;37m----------------------------------------------------------\e[0m
 \e[2;37m          ✡  Ginosi Backoffice Console (GBC)  ✡          \e[0m
 \e[0;37m----------------------------------------------------------\e[0m

 \e[0;37mDatabase parameters:\e[0m

    \e[1;33mdb help\e[0m                \e[2;33m- show this help\e[0m

    \e[1;33mdb safe-backup\e[0m         \e[2;33m- create safe database backup\e[0m


USAGE;
    }
}
