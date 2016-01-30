<?php

namespace Backoffice\Controller;

use DDD\Service\User;
use Library\Controller\ControllerBase;
use Library\Utility\Debug;
use Zend\Db\Adapter\Adapter;

class TestController extends ControllerBase {
    public function indexAction()
    {
        /**
         * @var Adapter $dbAdapter
         */
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');

        if (ob_get_level()) {
            ob_end_clean();
        }

        header( "Content-Type: text/csv;charset=utf-8" );
        header( "Content-Disposition: attachment;filename=\"large_file.csv\"" );
        header( "Pragma: no-cache" );
        header( "Expires: 0" );

        flush(); // Get the headers out immediately to show the download dialog
        // in Firefox

        $array = $dbAdapter->createStatement("
            SELECT
                `ga_reservations`.`id`                    AS `id`,
                `ga_reservations`.`res_number`            AS `res_number`,
                `ga_reservations`.`partner_id`            AS `partner_id`,
                `ga_reservations`.`partner_name`          AS `partner_name`,
                `ga_reservations`.`acc_city_name`         AS `acc_city_name`,
                `ga_reservations`.`status`                AS `status`,
                `ga_reservations`.`apartel_id`            AS `apartel_id`,
                `ga_reservations`.`timestamp`             AS `timestamp`,
                `ga_reservations`.`first_name`            AS `first_name`,
                `ga_reservations`.`last_name`             AS `last_name`,
                `ga_reservations`.`date_from`             AS `date_from`,
                `ga_reservations`.`date_to`               AS `date_to`,
                `ga_reservations`.`man_count`             AS `pax`,
                `ga_reservations`.`price`                 AS `price`,
                `ga_reservations`.`acc_currency`          AS `currency`,
                `ga_reservations`.`city`                  AS `city`,
                `ga_reservations`.`partner_ref`           AS `partner_ref`,
                `ga_reservations`.`no_collection`         AS `no_collection`,
                `ga_reservations`.`apartment_id_assigned` AS `apartment_id_assigned`,
                `ga_reservations`.`rate_name`             AS `rate_name`,
                `ga_reservations`.`arrival_date`          AS `arrival_date`,
                `ga_reservations`.`departure_date`        AS `departure_date`,
                `ga_apartments`.`name`                    AS `acc_name`,
                `ap_group`.`name`                         AS `apartment_building`,
                `ap_group`.`usage_building`               AS `usage_building`,
                `geo_detail`.`name`                       AS `country_name`,
                `review`.`score`                          AS `review_score`,
                `review`.`liked`                          AS `like`,
                `review`.`dislike`                        AS `dislike`,
                `ag`.`name`                               AS `apartel`,
                `customer_identity`.`ip_address`          AS `ip_address`
            FROM `ga_reservations`
                LEFT JOIN `ga_apartments` ON `ga_reservations`.`apartment_id_assigned` = `ga_apartments`.`id`
                LEFT JOIN `ga_apartment_groups` AS `ap_group` ON `ga_apartments`.`building_id` = `ap_group`.`id`
                LEFT JOIN `ga_countries` AS `country` ON `ga_reservations`.`country_id` = `country`.`id`
                LEFT JOIN `ga_location_details` AS `geo_detail` ON `geo_detail`.`id` = `country`.`detail_id`
                LEFT JOIN `ga_reviews` AS `review` ON `ga_reservations`.`id` = `review`.`res_id`
                LEFT JOIN `ga_apartment_groups` AS `ag` ON `ga_reservations`.`apartel_id` = `ag`.`id`
                LEFT JOIN `ga_customer_identity` AS `customer_identity`
                    ON `ga_reservations`.`id` = `customer_identity`.`reservation_id`
            WHERE `ga_reservations`.`status` = '1'
            ORDER BY `res_number` ASC;
        ")->execute();

        $fp = fopen('php://output', 'w');

        foreach ($array as $i => $fields) {
            fputcsv($fp, $fields, ';', '"');
            if ($i % 100 == 0) {
                flush(); // Attempt to flush output to the browser every 100 lines.
                // You may want to tweak this number based upon the size of
                // your CSV rows.
            }
        }

        fclose($fp);

        exit;
    }
}
