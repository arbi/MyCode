ALTER TABLE `ga_hr_jobs` ADD `cv_required` TINYINT NOT NULL DEFAULT '0' AFTER `status` ;

ALTER TABLE `backoffice`.`ga_bo_users` ADD COLUMN `asana_id` BIGINT NULL AFTER `period_of_evaluation`;

select * from ga_bo_user_evaluations;

ALTER TABLE `backoffice`.`ga_bo_user_evaluations` ADD COLUMN `average` FLOAT NOT NULL AFTER `description`;
update ga_bo_user_evaluations
set average = (select avg(value) from ga_bo_user_evaluation_values where value <> -1 and evaluation_id = ga_bo_user_evaluations.id group by evaluation_id);

UPDATE `backoffice`.`ga_furniture_types` SET `title` = 'King Bed(s)' WHERE `ga_furniture_types`.`id` =1;
UPDATE `backoffice`.`ga_furniture_types` SET `title` = 'Sofa Bed(s)' WHERE `ga_furniture_types`.`id` =2;
UPDATE `backoffice`.`ga_furniture_types` SET `title` = 'Queen Bed(s)' WHERE `ga_furniture_types`.`id` =3;
UPDATE `backoffice`.`ga_furniture_types` SET `title` = 'Rollaway Bed(s)' WHERE `ga_furniture_types`.`id` =4;
UPDATE `backoffice`.`ga_furniture_types` SET `title` = 'Single Bed(s)' WHERE `ga_furniture_types`.`id` =5;
UPDATE `backoffice`.`ga_furniture_types` SET `title` = 'Double Bed(s)' WHERE `ga_furniture_types`.`id` =6;
UPDATE `backoffice`.`ga_furniture_types` SET `title` = 'Futon Bed(s)' WHERE `ga_furniture_types`.`id` =7;

ALTER TABLE `backoffice`.`ga_countries` ADD COLUMN `required_postal_code` TINYINT(1) NOT NULL DEFAULT 2 AFTER `contact_phone`;

INSERT INTO `backoffice`.`ga_groups` (`id`, `name`, `description`) VALUES ('102', 'Apartment Availability Monitoring (Role)', 'Gives access to see apartment availability in notification.');
delete from ga_notifications where user_id = 70 AND ( message like '%closed days%' OR message like '%opened days%');
INSERT INTO `backoffice`.`ga_user_groups` (`id`, `user_id`, `group_id`) VALUES (NULL, '64', '102');

update `ga_notifications` set sender = 'Availability Monitoring' where message like '%closed days%' OR message like '%opened days%';
update `ga_notifications` set sender = 'Applicants' where message like '%You have an Interview%';
update `ga_notifications` set sender = 'Vacation' where message like '%is going to vacation%' or message = '%your vacation%';

UPDATE `backoffice`.`ga_groups` SET `name` = 'Expense Categories Management',
`description` = 'Gives access to ( Add / Edit / Active / Deactive ) expense categories.' WHERE `ga_groups`.`id` =97;
INSERT INTO `backoffice`.`ga_ud_dashboards` (`id`, `name`, `description`, `active`) VALUES ('29', 'New Applicants', 'It shows new applicants', '1');

ALTER TABLE `ga_apartment_groups` ADD `country_id` INT NULL AFTER `timezone` ;

UPDATE `ga_apartment_groups` SET country_id =213 WHERE `timezone` LIKE '%America%';
UPDATE `ga_apartment_groups` SET `country_id` =2 WHERE `timezone` LIKE '%yerevan%';
UPDATE `ga_apartment_groups` SET `country_id` =4 WHERE `timezone` LIKE '%amsterdam%';
UPDATE `ga_apartment_groups` SET `country_id` =188 WHERE timezone LIKE '%madrid%';
UPDATE `ga_apartment_groups` SET `country_id` = 213 where id = 29;
UPDATE `ga_apartment_groups` SET `country_id` = 213 where id = 25;
ALTER TABLE `ga_hr_jobs` ADD `subtitle` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `title` ;

ALTER TABLE `ga_apartment_details` ADD `cleaning_fee` FLOAT( 10, 2 ) NULL DEFAULT '0' AFTER `startup_cost` ;

INSERT INTO `backoffice`.`ga_un_textlines` (`id`, `page_id`, `en`) VALUES ('1497', '61', 'Cleaning Fee');

INSERT INTO `backoffice`.`ga_un_textlines` (`id`, `page_id`, `en`) VALUES ('1498', '61', 'The cost of cleaning for the apartment');

CREATE TABLE IF NOT EXISTS `ga_reservation_doc_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `doc_id` int(11) NOT NULL,
  `attachment` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `ga_reservation_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_id` tinyint(4) NOT NULL DEFAULT '1',
  `booking_id` int(11) NOT NULL,
  `attacher_id` int(11) NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE  `backoffice`.`ga_cities` SET  `ordering` =  '0' WHERE  `ga_cities`.`id` =48;
UPDATE  `backoffice`.`ga_cities` SET  `ordering` =  '1' WHERE  `ga_cities`.`id` =49;
UPDATE  `backoffice`.`ga_cities` SET  `ordering` =  '2' WHERE  `ga_cities`.`id` =52;
UPDATE  `backoffice`.`ga_cities` SET  `ordering` =  '3' WHERE  `ga_cities`.`id` =55;
UPDATE  `backoffice`.`ga_cities` SET  `ordering` =  '4' WHERE  `ga_cities`.`id` =51;
UPDATE  `backoffice`.`ga_cities` SET  `ordering` =  '5' WHERE  `ga_cities`.`id` =37;
UPDATE  `backoffice`.`ga_cities` SET  `ordering` =  '6' WHERE  `ga_cities`.`id` =6;

insert into ga_groups set
  id = 94,
  name = 'Finance Expense Creator',
  description = 'Gives extended permission to add new expenses',
  type = 3,
  parent_id = 0;

CREATE TABLE `backoffice`.`ga_bank` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(50) NULL,
	`address` VARCHAR(80) NULL,
	`bic` VARCHAR(25) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NULL,
	PRIMARY KEY (`id`));

ALTER TABLE `backoffice`.`ga_money_accounts`
DROP COLUMN `account_holder`,
DROP COLUMN `bank_routing`,
ADD COLUMN `bank_id` INT(11) UNSIGNED NOT NULL AFTER `country_id`,
ADD COLUMN `legal_entity_id` INT(11) UNSIGNED NOT NULL AFTER `bank_id`,
ADD COLUMN `card_holder_id` INT(11) UNSIGNED NOT NULL AFTER `legal_entity_id`;

insert into ga_bank set name = 'Converse Bank', address = 'Republic Square, 26/1 Vazgen Sargsyan, Yerevan, Armenia', bic = 'COVBAM22';
insert into ga_bank set name = 'ProCredit Bank', address = '21 Al. Kazbegi Ave., Tbilisi 0160, Georgia', bic = 'MIBGGE22';
insert into ga_bank set name = 'Rabobank', address = '"Postbus 94374, 1090GJ, Amsterdam, the Netherlands"', bic = 'RABONL2U';
insert into ga_bank set name = 'Citibank', address = 'Farragut West Branch, 1775 Pennsylvania Ave NW, Washington, D.C 20006', bic = '254070116';
insert into ga_bank set name = 'Wells Fargo Bank', address = '1600 Vine Street l Hollywood, CA 90028', bic = '122000247';
insert into ga_bank set name = 'Santander Bank', address = 'Oficona 2360 Barcelona, Diagona, Spain', bic = 'BSCHESMM';

UPDATE ga_un_textlines SET `en` = "Your trip to {{CITY_NAME}} is coming up. Follow the link below to access the entry details for {{PRODUCT}}." WHERE `id` = 1113;
INSERT INTO ga_un_textlines (`id`, `page_id`, `en`) VALUES  (1658, 61, "<p>If&nbsp;you have any questions, do not hesitate to <a style=\"color: #ffffff;\" href=\"https://www.ginosi.com/contact-us\">contact us</a>.</p><p>Please note that you will not be able to enter the apartment without viewing these instructions.</p>");
UPDATE ga_un_textlines SET `en` = "<p>If&nbsp;you have any questions, do not hesitate to <a href=\"https://www.ginosi.com/contact-us\">contact us</a>.</p><p>Please note that you will not be able to enter the apartment without viewing these instructions.</p>" WHERE `id` = 1658;
INSERT INTO ga_un_textlines (`id`, `page_id`, `en`) VALUES  (1659, 61, "31 K. Ulnetsi . Yerevan 0001 Armenia");
UPDATE ga_un_textlines SET `en` = "<p>Please take a few moments to write a review. We value your inpu and it will help us provide a better experience for all of our guests.</p>" WHERE `id` = 987;
UPDATE ga_un_textlines SET `en` = "<p>Thank you for taking the time to share your thoughts with us.</p>" WHERE `id` = 989;
UPDATE ga_un_textlines SET `en` = "<h2>Welcome</h2><p>Thank you for choosing Ginosi Apartments for your stay in {{CITY_NAME}}.</p>" WHERE `id` = 1656;
UPDATE ga_un_textlines SET `en` = "<p>Important: Entry Instructions for Reservation #{{RES_NUMBER}} from {{ARRIVAL_DATE}} in {{CITY_NAME}}</p>" WHERE `id` = 1090;
UPDATE ga_un_textlines SET `en` = "<p>Please Review Your Stay from {{ARRIVAL_DATE}} in {{CITY}} with Ginosi Apartments</p>" WHERE `id` = 1650;
UPDATE ga_un_textlines SET `en_html_clean` = "Please Review Your Stay from {{ARRIVAL_DATE}} in {{CITY}} with Ginosi Apartments" WHERE `id` = 1650;
UPDATE ga_un_textlines SET `en_html_clean` = "Important: Entry Instructions for Reservation #{{RES_NUMBER}} from {{ARRIVAL_DATE}} in {{CITY_NAME}}" WHERE `id` = 1090;

INSERT INTO ga_un_textlines (id, page_id, en, en_html_clean) VALUES (1669, 61, "Total Amount to Pay Was", "Total Amount to Pay Was");
INSERT INTO ga_un_textlines (id, page_id, en, en_html_clean) VALUES (1670, 61, "Penalty", "Penalty");
INSERT INTO ga_un_textlines (id, page_id, en, en_html_clean) VALUES (1671, 61, "<p>Thank you for booking a Ginosi Apartment for your stay.</p><p>The terms and conditions that apply to your purchase can be found at</p> <a href=\"https://www.ginosi.com/about-us/terms-and-conditions\" target=\"blank\">https://www.ginosi.com/about-us/terms-and-conditions</a>", "Thank you for booking a Ginosi Apartment for your stay. The terms and conditions that apply to your purchase can be found at <a href=\"https://www.ginosi.com/about-us/terms-and-conditions\" target=\"blank\">https://www.ginosi.com/about-us/terms-and-conditions</a>");
INSERT INTO ga_un_textlines (id, page_id, en, en_html_clean) VALUES (1672, 61, "<p style=\"margin:0\">Ginosi Apartments</p><p style=\"margin:0\">Karapet Ulnetsi 31, Yerevan, Armenia,</p><p style=\"margin:0\">Phone (International): +1 (818) 641 15 64</p>", "Ginosi Apartments Karapet Ulnetsi 31, Yerevan, Armenia, Phone (International): +1 (818) 641 15 64");
INSERT INTO ga_un_textlines (id, page_id, en, en_html_clean) VALUES (1673, 61, "<p>Paid To</p>", "Paid To");
INSERT INTO ga_un_textlines (id, page_id, en, en_html_clean) VALUES (1674, 61, "<p>Balance</p>", "Balance");
INSERT INTO ga_un_textlines (id, page_id, en, en_html_clean) VALUES (1675, 61, "<p>Total Amount Paid</p>", "Total Amount Paid");
INSERT INTO ga_un_textlines (id, page_id, en, en_html_clean) VALUES (1676, 61, "<p>Apartment Address</p>", "Apartment Address");
INSERT INTO ga_un_textlines (id, page_id, en, en_html_clean) VALUES (1677, 61, "<p>Customer Address</p>", "Customer Address");
INSERT INTO ga_un_textlines (id, page_id, en, en_html_clean) VALUES (1678, 61, "<p>Customer Name</p>", "Customer Name");
INSERT INTO ga_un_textlines (id, page_id, en, en_html_clean) VALUES (1679, 61, "<p>Receipt Issue Date</p>", "Receipt Issue Date");
INSERT INTO ga_un_textlines (id, page_id, en, en_html_clean) VALUES (1680, 61, "<p>ReceiptID</p>", "ReceiptID");

ALTER TABLE ga_reservations DROP COLUMN rooms_count;