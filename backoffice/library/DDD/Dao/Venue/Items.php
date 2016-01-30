<?php

namespace DDD\Dao\Venue;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Library\Constants\DbTables;

class Items extends TableGatewayManager
{
    protected $table   = DbTables::TBL_VENUE_ITEMS;

    public function __construct($sm, $domain = 'DDD\Domain\Venue\Items')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $venueId
     * @param bool|false $onlyAvailable
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getItemsByVenueId($venueId, $onlyAvailable = false)
    {
        $result = $this->fetchAll(function (Select $select) use ($venueId, $onlyAvailable) {
            $select->columns([
                'id',
                'venue_id',
                'title',
                'description',
                'price',
                'is_available'
            ]);

            $select->where
                ->equalTo('venue_id', $venueId);

            if ($onlyAvailable) {
                $select->where
                    ->equalTo('is_available', 1);
            }
        });

        return $result;
    }

}