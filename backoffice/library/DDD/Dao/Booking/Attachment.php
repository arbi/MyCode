<?php
namespace DDD\Dao\Booking;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;

class Attachment extends TableGatewayManager
{
    protected $table = DbTables::TBL_RESERVATION_ATTACHMENTS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Booking\Attachment');
    }

    /**
     * @param $reservationId
     * @return \Zend\Db\ResultSet\ResultSet | \DDD\Domain\Booking\Attachment[]
     */
    public function getAttachments($reservationId)
    {
        $result = $this->fetchAll(function (Select $select) use($reservationId) {

            $select->join(
                ['u' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.attacher_id = u.id',
                ['firstname', 'lastname']
            )

            ->where([$this->getTable() .'.reservation_id' => $reservationId]);
        });

        return $result;
    }
}