<?php

namespace DDD\Dao\Booking;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;

class PinnedReservation extends TableGatewayManager {
    protected $table = DbTables::TBL_PINNED_RESERVATIONS;

    public function __construct($sm, $domain = '\DDD\Domain\UniversalDashboard\Widget\PinnedReservation') {
    	parent::__construct($sm, $domain);
    }

    /**
     * Get user's pinned reservations count
     * @return int
     */
    public function getAllPinnedReservationsCount($userId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(
            ['user_id' => $userId],
            ['count' => new Expression('COUNT(*)')]
        );

        return $result['count'];
    }
}
