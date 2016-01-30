<?php

namespace DDD\Service\Venue;

use DDD\Service\ServiceBase;

class Charges extends ServiceBase
{
    const CHARGE_STATUS_NEW         = 1; // New
    const CHARGE_STATUS_CANCELLED   = 2; // Cancelled
    const CHARGE_STATUS_TRANSFERRED = 3; // Transferred


    const ORDER_STATUS_NEW          = 1; // New
    const ORDER_STATUS_PROCESSING   = 2; // Processing
    const ORDER_STATUS_DONE         = 3; // Done
    const ORDER_STATUS_VERIFIED     = 4; // Verified
    const ORDER_STATUS_CANCELLED    = 5; // Cancelled

    static public function getChargeStatuses()
    {
        return [
            self::CHARGE_STATUS_NEW         => 'New',
            self::CHARGE_STATUS_CANCELLED   => 'Cancelled',
            self::CHARGE_STATUS_TRANSFERRED => 'Transferred'
        ];
    }

    static public function getChargeOrderStatuses()
    {
        return [
            self::ORDER_STATUS_NEW          => 'New',
            self::ORDER_STATUS_PROCESSING   => 'Processing',
            self::ORDER_STATUS_DONE         => 'Done',
            self::ORDER_STATUS_VERIFIED     => 'Verified',
            self::ORDER_STATUS_CANCELLED    => 'Cancelled',
        ];
    }

    public function getChargesByVenueId($venueId)
    {

    }

    public function createCharge($data)
    {
        try {
            /**
             * @var \DDD\Dao\Venue\Charges $venueChargeDao
             * @var \Library\Authentication\BackofficeAuthenticationService $auth
             */
            $venueChargeDao = $this->getServiceLocator()->get('dao_venue_charges');
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');

            $data['date_created_server'] = date('Y-m-d H:i:s');

            if (!isset($data['date_created_client']) || empty($data['date_created_client'])) {
                $data['date_created_client'] = $data['date_created_server'];
            }

            if (!isset($data['creator_id'])) {
                $data['creator_id'] = $auth->getIdentity()->id;
            }

            $data['status']= $data['status_id'];
            unset($data['status_id']);

            $data['order_status']= $data['order_status_id'];
            unset($data['order_status_id']);

            $insertResult = $venueChargeDao->insert($data);

            if ($insertResult) {
                return $venueChargeDao->getLastInsertValue();
            }

        } catch (\Exception $e) {
            $this->gr2logException($e);
        }

        return false;
    }

    public function saveCharge($data)
    {
        try {
            /**
             * @var \DDD\Dao\Venue\Charges $venueChargeDao
             */
            $venueChargeDao = $this->getServiceLocator()->get('dao_venue_charges');

            $data['status']= $data['status_id'];
            unset($data['status_id']);

            $data['order_status']= $data['order_status_id'];
            unset($data['order_status_id']);

            $chargeId = $data['id'];
            unset($data['id']);

            return $venueChargeDao->save($data, ['id' => $chargeId]);

        } catch (\Exception $e) {
            $this->gr2logException($e);
        }

        return false;
    }

    /**
     * @param $data
     * @return bool
     */
    public function createChargeForLunchroom($data)
    {
        /**
         * @var \DDD\Dao\Venue\Charges $venueChargeDao
         * @var \Library\Authentication\BackofficeAuthenticationService $auth
         */
        try {
            $venueChargeDao = $this->getServiceLocator()->get('dao_venue_charges');
            $lunchroomOrderArchiveDao = $this->getServiceLocator()->get('dao_venue_lunchroom_order_archive');
            $venueChargeDao->beginTransaction();
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $data['date_created_server'] = $data['date_created_client'] = date('Y-m-d H:i:s');
            $data['creator_id'] = $data['charged_user_id'] = $auth->getIdentity()->id;
            $data['status'] = self::CHARGE_STATUS_NEW;
            $data['order_status'] = self::ORDER_STATUS_NEW;
            $data['description'] = '';
            $detailedOrder = $data['detailed_order'];
            unset($data['detailed_order']);
            $venueChargeDao->save($data);
            $chargeId = $venueChargeDao->getLastInsertValue();
            foreach ($detailedOrder as $detailedOrder) {
                $lunchroomOrderArchiveDao->save(
                    [
                    'venue_charge_id' => $chargeId,
                    'venue_id' => $data['venue_id'],
                    'item_name' => $detailedOrder['title'],
                    'item_price' => $detailedOrder['price'],
                    'item_quantity' => $detailedOrder['quantity'],
                    ]

                );
            }
            $venueChargeDao->commitTransaction();
            return true;
        } catch (\Exception $e) {
            $venueChargeDao->rollbackTransaction();
            return false;
        }

    }
}