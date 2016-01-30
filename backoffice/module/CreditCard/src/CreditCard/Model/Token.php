<?php

namespace CreditCard\Model;

use CreditCard\Entity\LocalData;
use CreditCard\Entity\ReservationTicketData;
use CreditCard\Service\Card;
use DDD\Domain\Booking\ChargeAuthorization\ChargeAuthorizationCreditCard;
use DDD\Service\Reservation\ChargeAuthorization;
use Library\Constants\DbTables;
use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Zend\Stdlib\ArrayObject;

/**
 * Class Token
 * @package CreditCard\Model
 */
class Token extends TableGatewayManager
{
    protected $table = DbTables::TBL_TOKEN;

    public function __construct($sm)
    {
        parent::__construct($sm, '\CreditCard\Entity\LocalData');
    }

    /**
     * @param $ccId
     * @return array|LocalData|null
     */
    public function getLocalData($ccId)
    {
        $this->setEntity(new LocalData());
        return $this->fetchOne(function (Select $select) use ($ccId) {

            $select->columns([
                'id',
                'token',
                'first_digits',
                'brand',
                'salt'
            ]);

            $select->where->equalTo('id', $ccId);
        });
    }

    /**
     * @param $ccId
     * @return int
     */
    public function getPartnerBusinessModel($ccId)
    {
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($ccId) {

            $select->columns([
                'id',
                'token',
                'first_digits',
                'brand',
                'salt'
            ]);

            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                $this->getTable() . '.partner_id = partner.gid',
                ['business_model']
            );

            $select->where->equalTo($this->getTable() . '.id', $ccId);
        });

        return $result['business_model'];
    }

    /**
     * @param $ccId
     * @return int
     */
    public function getStatus($ccId)
    {
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($ccId) {

            $select->columns([
                'status'
            ]);

            $select->where->equalTo($this->getTable() . '.id', $ccId);
        });

        return $result['status'];
    }

    /**
     * @param $reservationId
     * @return ChargeAuthorizationCreditCard[]
     */
    public function getCreditCardsForAuthorization($reservationId)
    {
        $this->setEntity(new ChargeAuthorizationCreditCard());

        return $this->fetchAll(function (Select $select) use ($reservationId) {
            $select->columns([
                'id' => 'id',
                'brand' => 'brand',
                'token' => 'token'
            ]);

            $select->join(
                ['reservation' => DbTables::TBL_BOOKINGS],
                $this->getTable() . '.customer_id = reservation.customer_id',
                []
            );

            $select
                ->where
                ->equalTo('reservation.id', $reservationId)
                ->equalTo($this->getTable() . '.partner_id', 1) // not a partner card
                ->in($this->getTable() . '.status', [Card::CC_STATUS_VALID, Card::CC_STATUS_UNKNOWN]);
        });
    }

    /**
     * @param $ccId
     * @return ChargeAuthorizationCreditCard
     */
    public function getCreditCardDataForAuthorization($ccId)
    {
        $this->setEntity(new ChargeAuthorizationCreditCard());

        return $this->fetchOne(function (Select $select) use ($ccId) {
            $select->columns([
                'id' => 'id',
                'brand' => 'brand',
                'token' => 'token'
            ]);

            $select
                ->where
                ->equalTo('id', $ccId);
        });
    }

    /**
     * @param $customerId
     * @return ReservationTicketData[]
     */
    public function getCustomerCreditCards($customerId)
    {
        $this->setEntity(new ReservationTicketData());
        return $this->fetchAll(function (Select $select) use ($customerId) {
            $select->columns([
                'id',
                'date_provided',
                'first_digits',
                'salt',
                'brand',
                'partner_id',
                'source',
                'status',
                'is_default'
            ]);

            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                $this->getTable() . '.partner_id = partner.gid',
                ['partner_name'],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable() . '.customer_id' => $customerId,
            ]);

            $select->group($this->getTable() . '.id');

            $select->order([
                $this->getTable() . '.is_default DESC',
                $this->getTable() . '.date_provided DESC'
            ]);
        });
    }

    /**
     * @param $customerId
     * @return ReservationTicketData[]
     */
    public function getCustomerCreditCardsForFrontier($customerId)
    {
        $this->setEntity(new ReservationTicketData());

        return $this->fetchAll(function (Select $select) use ($customerId) {
            $select->columns([
                'id',
                'date_provided',
                'first_digits',
                'salt',
                'brand',
                'partner_id',
                'source',
                'status',
                'is_default'
            ]);

            $select->where
                ->equalTo($this->getTable() . '.customer_id', $customerId)
                ->in($this->getTable() . '.status', [Card::CC_STATUS_UNKNOWN, Card::CC_STATUS_VALID]);

            $select->group($this->getTable() . '.id');

            $select->order([
                $this->getTable() . '.is_default DESC',
                $this->getTable() . '.status DESC',
                $this->getTable() . '.date_provided DESC'
            ]);
        });
    }

    /**
     * @param $customerId
     * @return ArrayObject
     */
    public function getCustomerCreditCardsForExistenceCheck($customerId)
    {
        $this->setEntity(new ArrayObject());

        return $this->fetchAll(function (Select $select) use ($customerId) {
            $select->columns([
                'id',
                'first_digits',
                'salt',
                'brand',
                'token'
            ]);

            $select->where->equalTo($this->getTable() . '.customer_id', $customerId);
        });
    }
}
