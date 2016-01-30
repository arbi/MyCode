<?php
namespace DDD\Dao\MoneyAccount;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;

class Attachment extends TableGatewayManager
{
    protected $table = DbTables::TBL_MONEY_ACCOUNT_ATTACHMENTS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\MoneyAccount\Attachment');
    }

    /**
     * @param $moneyAccountId
     * @return \DDD\Domain\MoneyAccount\Attachment[]
     */
    public function getAttachments($moneyAccountId)
    {
        $result = $this->fetchAll(function (Select $select) use($moneyAccountId) {

            $select->join(
                ['u' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.attacher_id = u.id',
                ['firstname', 'lastname']
            )

            ->where([$this->getTable() .'.money_account_id' => $moneyAccountId]);
        });

        return $result;
    }
}