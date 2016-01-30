<?php
namespace DDD\Dao\MoneyAccount;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;

class AttachmentItem extends TableGatewayManager
{
    protected $table = DbTables::TBL_MONEY_ACCOUNT_ATTACHMENT_ITEMS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\MoneyAccount\AttachmentItem');
    }

    /**
     * @param $moneyAccountId
     * @param $docId
     * @return \DDD\Domain\MoneyAccount\AttachmentItem[]
     */
    public function getDocFiles($moneyAccountId, $docId)
    {
        $result = $this->fetchAll(function (Select $select) use($moneyAccountId, $docId) {

            $select->join(
                ['attachment' => DbTables::TBL_MONEY_ACCOUNT_ATTACHMENTS],
                $this->getTable() . '.doc_id = attachment.id',
                ['created_date']
            )

            ->where(
                [
                    $this->getTable() .'.money_account_id' => $moneyAccountId,
                    $this->getTable() .'.doc_id'         => $docId
                ]
            );
        });
        return $result;
    }
}
