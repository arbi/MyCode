<?php
namespace DDD\Dao\Booking;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class Partner extends TableGatewayManager
{
    protected $table = DbTables::TBL_BOOKING_PARTNERS;

    public function __construct($sm, $domain = 'DDD\Domain\Booking\Partner')
    {
        parent::__construct($sm, $domain);
    }

	/**
	 * @param $id int
	 * @return \DDD\Domain\Booking\Partner
	 */
	public function getCommissionById($id)
    {
        return $this->fetchOne(function (Select $select) use ($id) {
            $select->columns([
                'id' => 'gid',
                'commission',
                'business_model'
            ]);

            $select->join(
                ['partner_account' => DbTables::TBL_BOOKING_PARTNER_ACCOUNTS],
                $this->getTable() . '.gid = partner_account.partner_id',
                ['cubilis_partner_id' => 'cubilis_id']
            );

            $select->where(['partner_account.cubilis_id' => $id]);
        });
    }

    /**
     * @param $partnerID
     * @return array|\ArrayObject|null
     */
    public function getCommissionWebSiteById($partnerID)
    {
        return $this->fetchOne(function (Select $select) use ($partnerID) {
            $select->columns([
                'commission',
                'business_model',
                'partner_name'
            ]);

            $select->where([
                'gid'    => $partnerID,
                'active' => 1
            ]);
        });
    }

    public function getPartnerListForWeb()
    {
        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id' => 'gid',
                'partner_name'
            ]);

            $select->where(['active' => 1]);

            $select->order('partner_name ASC');
        });
    }

    /**
     * @param $partnerID
     * @return array|\ArrayObject|null
     */
    public function getPartnerName($partnerID)
    {
        return $this->fetchOne(function (Select $select) use ($partnerID) {
            $select->columns([
                'partner_name'
            ]);

            $select->where(['gid' => $partnerID]);
        });
    }

}
