<?php

namespace DDD\Dao\Finance;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use DDD\Service\Reservation\ChargeAuthorization;
class Ccca extends TableGatewayManager
{
    protected $table = DbTables::TBL_CCCA;

    public function __construct($sm, $domain = 'DDD\Domain\Finance\Ccca')
    {
        parent::__construct($sm, $domain);
    }

    public function getAllsentCcca($reservationId)
    {
        return $this->fetchAll(function (Select $select) use ($reservationId) {
            $select->where
                ->equalTo('reservation_id', $reservationId)
                ->in('status', [
                        ChargeAuthorization::CHARGE_AUTHORIZATION_PAGE_STATUS_GENERATED,
                        ChargeAuthorization::CHARGE_AUTHORIZATION_PAGE_STATUS_VIEWED
                    ]
                );
        });
    }

    public function getInfoForCCCAPage($pageToken)
    {
        return $this->fetchOne(function (Select $select) use ($pageToken) {
            $select->columns(['amount']);
            $select->where->equalTo('page_token', $pageToken);
        });
    }
}
