<?php
namespace DDD\Service\Venue;

use DDD\Service\ServiceBase;

/**
 * Class Venue
 *
 * @package DDD\Service\Venue
 * @author  Harut Grigoryan
 */
class Venue extends ServiceBase
{
    /**
     * Venue accept order status
     */
    const VENUE_ACCEPT_ORDERS_ON  = 1;
    const VENUE_ACCEPT_ORDERS_OFF = 2;

    /**
     * Venue statuses
     */
    const VENUE_STATUS_ACTIVE   = 1;
    const VENUE_STATUS_INACTIVE = 2;

    const VENUE_TYPE_LUNCHROOM = 1;

    public static function getVenueTypesForSelect()
    {
        return [
            '' => '',
            self::VENUE_TYPE_LUNCHROOM => 'Lunchroom'
        ];
    }

    /**
     * Get all venues
     *
     * @return array
     */
    public function getVenues()
    {
        /**
         * @var \DDD\Dao\Venue\Venue $venueDao
         */
        $venueDao = $this->getServiceLocator()->get('dao_venue_venue');

        return $venueDao->getVenues();
    }

    /**
     * Get venue by id
     *
     * @param $id
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getVenueById($id)
    {
        /**
         * @var \DDD\Dao\Venue\Venue $venueDao
         */
        $venueDao = $this->getServiceLocator()->get('dao_venue_venue');

        return $venueDao->getVenueById($id);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function getVenuesByParams($params = []) {
        /**
         * @var \DDD\Dao\Venue\Venue $venueDao
         */
        $venueDao = $this->getServiceLocator()->get('dao_venue_venue');

        return $venueDao->getVenuesByParams($params);
    }

    /**
     * @return bool
     */
    public function thereIsLunchroomInUserCityThatExceptOrders()
    {
        /**
         * @var \DDD\Dao\Venue\Venue $venueDao
         */
        $auth  = $this->getServiceLocator()->get('library_backoffice_auth');
        $venueDao = $this->getServiceLocator()->get('dao_venue_venue');
        return !!$venueDao->thereIsLunchroomInUserCityThatExceptOrders($auth->getIdentity()->city_id);
    }

    /**
     * @return bool|\Zend\Db\ResultSet\ResultSet
     */
    public function getLunchroomsForLoggedInUser()
    {
        /**
         * @var \DDD\Dao\Venue\Venue $venueDao
         */
        $auth  = $this->getServiceLocator()->get('library_backoffice_auth');
        $venueDao = $this->getServiceLocator()->get('dao_venue_venue');
        $result = $venueDao->getLunchroomsForLoggedInUser($auth->getIdentity()->city_id);
        return ($result->count()) ? $result : false;
    }

    public function getItemsByChargeId($chargeId)
    {
        /**
         * @var \DDD\Dao\Venue\LunchroomOrderArchive $lunchroomOrderArchiveDao
         */
        $lunchroomOrderArchiveDao = $this->getServiceLocator()->get('dao_venue_lunchroom_order_archive');
        $result = $lunchroomOrderArchiveDao->getItemsByChargeId($chargeId);
        return ($result->count()) ? $result : false;
    }
}
