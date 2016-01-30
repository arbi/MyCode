ALTER TABLE `ga_bo_user_schedule_inventory` ADD COLUMN `note` TEXT AFTER `color_id`;

UPDATE `backoffice`.`ga_groups`
SET `name`      = 'Money Account',
  `description` = 'Gives permission to access Money Accounts module and see accounts based on the permission user has. '
WHERE `ga_groups`.`id` = 76;
UPDATE `backoffice`.`ga_groups`
SET `description` = 'Gives access to create new money accounts.'
WHERE `ga_groups`.`id` = 77;
UPDATE `backoffice`.`ga_groups`
SET
  `description` = 'A very powerful role with access to all accounts and all actions including adding transactions to all accounts. '
WHERE `ga_groups`.`id` = 78;

ALTER TABLE `backoffice`.`ga_asset_categories` ADD COLUMN `is_new` TINYINT NOT NULL DEFAULT 1 AFTER `inactive`;
UPDATE `backoffice`.`ga_asset_categories` SET `is_new`= 0;
ALTER TABLE `backoffice`.`ga_asset_categories` ADD COLUMN `creator_id` INT NOT NULL AFTER `is_new`;
INSERT INTO `backoffice`.`ga_ud_dashboards` (`id`, `name`, `description`, `active`) VALUES ('48', 'New Asset Categories', 'Show new created categories', '1');

UPDATE `ga_action_logs` SET `value`= "Canceled by Customer" WHERE `value`= "Cancelled by Exception" AND `action_id`=16 AND `module_id` = 1;
UPDATE `ga_reservations` SET `status`=7 where `status`=16;

INSERT INTO `oauth_clients` (client_id, client_secret) values('86d3009463c6eccfcb2788ada8989e9d', '$2a$10$btTNOOOrwcHV1AZDUErYx.ZfE8/mhGrOkNOO5sKONZDok5JyEo0vq');
