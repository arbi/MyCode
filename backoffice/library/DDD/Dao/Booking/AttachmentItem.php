<?php
namespace DDD\Dao\Booking;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;

class AttachmentItem extends TableGatewayManager
{
    protected $table = DbTables::TBL_RESERVATION_ATTACHMENT_ITEMS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Booking\AttachmentItem');
    }

    public function getDocFiles($bookingId, $docId)
    {
        $result = $this->fetchAll(function (Select $select) use($bookingId, $docId) {

            $select->join(
                ['attachment' => DbTables::TBL_RESERVATION_ATTACHMENTS],
                $this->getTable() . '.doc_id = attachment.id',
                ['created_date']
            )

            ->where(
                [
                    $this->getTable() .'.reservation_id' => $bookingId,
                    $this->getTable() .'.doc_id'         => $docId
                ]
            );
        });
        return $result;
    }
}
