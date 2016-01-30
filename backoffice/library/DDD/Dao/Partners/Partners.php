<?php

namespace DDD\Dao\Partners;

use DDD\Domain\Partners\PartnerBooking;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\Stdlib\ArrayObject;

class Partners extends TableGatewayManager
{
    protected $table = DbTables::TBL_BOOKING_PARTNERS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Partners\Partners');
    }

    public function getPartnersList($offset, $limit, $sortCol, $sortDir, $like, $all = '1')
    {
	    if ($all === '1') {
		    $whereAll = 'AND active = 1';
	    } elseif ($all === '2') {
		    $whereAll = 'AND active = 0';
	    } else {
		    $whereAll = ' ';
	    }

        $columns = ['active', 'gid', 'partner_name', 'contact_name', 'email', 'mobile', 'phone'];

        return $this->fetchAll(function (Select $select) use ($offset, $limit, $sortCol, $sortDir, $like, $whereAll, $columns) {
            $select->where("
                (partner_name like '%{$like}%'
                    OR contact_name like '%{$like}%'
                    OR email like '%{$like}%'
                    OR gid like '%{$like}%'
                    OR mobile like '%{$like}%'
                    OR phone like '%{$like}%')
                    $whereAll
            ");

            $select
                ->columns($columns)
                ->order($columns[$sortCol] . ' ' . $sortDir)
                ->offset((int)$offset)
                ->limit((int)$limit);
		});
    }

    public function getPartnersForSelect()
    {
        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'gid',
                'partner_name'
            ]);

            $select->order('partner_name ASC');
        });
    }

    public function getPartnersCount($like, $all = '1')
    {
        /**
         * @var \ArrayObject $result
         */
        switch ($all) {
            case '1':
                $whereAll = 'AND active = 1';
                break;
            case '2':
                $whereAll = 'AND active = 0';
                break;
            default:
                $whereAll = ' ';
        }

        $result = $this->fetchAll(function (Select $select) use ($like, $whereAll) {
            $select->where("
                (partner_name like '{$like}%'
                    OR contact_name like '{$like}%'
                    OR email like '%{$like}%')
                    $whereAll
            ");

            $select->columns(['gid']);
		});

		return $result->count();
    }

    /**
     * @param $id
     * @return \DDD\Domain\Partners\Partners|null
     */
    public function getPartnerById($id)
    {
        $this->setEntity(new \DDD\Domain\Partners\Partners());

        return $this->fetchOne(function (Select $select) use ($id) {
            $select->where->equalTo('gid', $id);
            $select->columns([
                'gid',
                'partner_name',
                'business_model',
                'contact_name',
                'email',
                'mobile',
                'phone',
                'commission',
                'additional_tax_commission',
                'account_holder_name',
                'bank_bsr',
                'bank_account_num',
                'notes',
                'is_ota',
                'customer_email',
                'create_date',
                'active',
                'discount',
                'show_partner',
                'apply_fuzzy_logic',
                'is_deducted_commission',
            ]);
        });
    }

    /**
     * @param int $affiliateId
     * @return \DDD\Domain\Partners\Partners[]|\ArrayObject
     */
    public function getPartners($affiliateId = 0)
    {
        return $this->fetchAll(function (Select $select) use ($affiliateId) {
            $select->columns([
                'gid',
                'partner_name'
            ]);

            $select
                ->where
                ->equalTo('active', 1)
                ->OR
                ->equalTo('gid', $affiliateId);

            $select->order('partner_name ASC');
        });
    }

    /**
     * Return only partners with Partner Collect business model
     * @return \DDD\Domain\Partners\Partners[]|\ArrayObject
     */
    public function getPartnersFiltered()
    {
        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'gid',
                'partner_name'
            ]);

            $select
                ->where
                ->equalTo('active', 1)
                ->and
                ->in('business_model', [\DDD\Service\Partners::BUSINESS_MODEL_PARTNER_COLLECT_GUEST_INVOICE, \DDD\Service\Partners::BUSINESS_MODEL_PARTNER_COLLECT_GUEST_TRANSFER]);

            $select->order('partner_name ASC');
        });
    }

    public function getActiveOutsidePartners()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'gid',
                'partner_name'
            ]);

            $select->where
                ->equalTo('active', 1)
                ->equalTo('is_ota', 1);

            $select->order('partner_name ASC');
        });
    }


    /**
     * @param int $id
     * @param bool $isOurPartnerId
     * @return array|\ArrayObject|null
     */
    public function getPartnerDataForReservation($id, $isOurPartnerId) {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Partners\PartnerBooking());
        return $this->fetchOne(function (Select $select) use ($id, $isOurPartnerId) {
            $select->columns(['gid' => 'gid', 'commission', 'business_model', 'partner_name']);

            if ($isOurPartnerId) {
                $select->where([$this->getTable() . '.gid' => $id]);
            } else {
                $select->join(
                    ['partner_account' => DbTables::TBL_BOOKING_PARTNER_ACCOUNTS],
                    $this->getTable() . '.gid = partner_account.partner_id',
                    ['cubilis_partner_id' => 'cubilis_id']
                );
                $select->where(['partner_account.cubilis_id' => $id]);
            }
        });
    }

    /**
     * @param $partnerId
     * @return PartnerBooking
     */
    public function getPartnerModel($partnerId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Partners\PartnerBooking());
        return $this->fetchOne(function (Select $select) use ($partnerId) {
            $select->columns([
                'gid',
                'commission',
                'business_model',
                'partner_name'
            ]);
            $select->where(['gid' => $partnerId]);
        });
    }

    /**
     * @param $partnerId
     * @return bool
     */
    public function checkFuzzyLogic($partnerId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result =  $this->fetchOne(function (Select $select) use ($partnerId) {
            $select->columns([
                'apply_fuzzy_logic'
            ]);
            $select->where(['gid' => $partnerId]);
        });

        return $result && $result['apply_fuzzy_logic'] ? true : false;
    }

    /**
     * @param $partnerId
     * @return bool
     */
    public function checkDeductedCommission($partnerId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result =  $this->fetchOne(function (Select $select) use ($partnerId) {
            $select->columns([
                'is_deducted_commission'
            ]);
            $select->where(['gid' => $partnerId]);
        });

        return $result && $result['is_deducted_commission'] ? true : false;
    }

    /**
     * @param $partnerId
     * @return array|\ArrayObject|null
     */
    public function getPartnerCommission($partnerId)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($partnerId) {
            $select->columns([
                'commission'
            ]);
            $select->where(['gid' => $partnerId]);
        });
        $this->setEntity($prototype);

        return $result;
    }

    /**
     * @param $id
     * @return array|\ArrayObject|null
     */
    public function getPartnerNameAndDiscountById($id)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result =  $this->fetchOne(function (Select $select) use ($id) {
            $select->where->equalTo('gid', $id);
            $select->columns([
                'partner_name',
                'discount',
            ]);
        });

        $this->setEntity($prototype);

        return $result;
    }

    public function searchContacts($searchQuery)
    {
        return $this->fetchAll(function (Select $select) use ($searchQuery) {
               $select->columns(['gid','partner_name','contact_name']);
               $select->where
                   ->like($this->getTable() . '.partner_name' , '%' . $searchQuery . '%')
                   ->or
                   ->like($this->getTable() . '.contact_name' , '%' . $searchQuery . '%');
        });
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getActivePartners()
    {
        $this->setEntity(new \ArrayObject());
        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id' => 'gid',
                'partner_name'
            ]);

            $select->where->equalTo('active', 1);
            $select->order('partner_name ASC');
        });
    }
}
