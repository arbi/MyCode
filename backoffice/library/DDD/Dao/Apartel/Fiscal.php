<?php
namespace DDD\Dao\Apartel;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;

class Fiscal extends TableGatewayManager
{
    protected $table = DbTables::TBL_APARTEL_FISCAL;
    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $apartelId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getApartelFiscals($apartelId) {
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($apartelId) {
            $select->columns([
                'id',
                'name',
                'partner_id',
                'channel_partner_id',
                'apartel_id',
            ]);
            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                $this->getTable() . '.partner_id = partner.gid',
                [
                    'partner_name'
                ],
                Select::JOIN_LEFT
            );
            $select->where->equalTo($this->getTable().'.apartel_id', $apartelId);
        });

        return $result;
    }

    /**
     * @param $query
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getFiscalListByQ($query)
    {
        return $this->fetchAll(
            function (Select $select) use ($query) {
                $select->columns(['id', 'name']);
                $select->where->like('name', "%{$query}%");
                $select->order(['name ASC']);
            }
        );
    }

}
