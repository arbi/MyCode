<?php

namespace DDD\Dao\Contacts;

use \DDD\Service\Contacts\Contact as ContactService;
use DDD\Service\Team\Team;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class Contact extends TableGatewayManager
{
    protected $table = DbTables::TBL_CONTACTS;

    public function __construct($sm, $domain = 'DDD\Domain\Contacts\Contact') {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $id
     *
     * @return bool|\DDD\Domain\Contacts\Contact
     */
    public function getContactById($id)
    {
        $result = $this->fetchOne(function (Select $select) use ($id) {
            $select->columns([
                'id',
                'creator_id',
                'date_created',
                'date_modified',
                'scope',
                'team_id',
                'apartment_id',
                'building_id',
                'partner_id',
                'name',
                'company',
                'position',
                'city',
                'address',
                'email',
                'skype',
                'url',
                'phone_mobile_country_id',
                'phone_mobile',
                'phone_company_country_id',
                'phone_company',
                'phone_other_country_id',
                'phone_other',
                'phone_fax_country_id',
                'phone_fax',
                'notes'
            ]);

            $select->where
                ->equalTo('id', $id);
        });

        return $result;
    }

    /**
     * @param int $id
     *
     * @return bool|\DDD\Domain\Contacts\Card
     */
    public function getContactByIdWithExtraData($id)
    {
        $this->resultSetPrototype
            ->setArrayObjectPrototype(new \DDD\Domain\Contacts\Card());

        $result = $this->fetchOne(function (Select $select) use ($id) {
            $select->columns([
                'id',
                'creator_id',
                'date_created',
                'date_modified',
                'scope',
                'team_id',
                'apartment_id',
                'building_id',
                'partner_id',
                'name',
                'company',
                'position',
                'city',
                'address',
                'email',
                'skype',
                'url',
                'phone_mobile',
                'phone_company',
                'phone_other',
                'phone_fax',
                'notes'
            ]);

            $select->join(
                ['team' => DbTables::TBL_TEAMS],
                $this->getTable() . '.team_id = team.id',
                ['team_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id = apartment.id',
                ['apartment_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['building' => DbTables::TBL_APARTMENT_GROUPS],
                $this->getTable() . '.building_id = building.id',
                ['building_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                $this->getTable() . '.partner_id = partner.gid',
                ['partner_name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['phone_mobile_country' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.phone_mobile_country_id = phone_mobile_country.id',
                ['phone_mobile_country_code' => 'phone_code'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['phone_company_country' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.phone_company_country_id = phone_company_country.id',
                ['phone_company_country_code' => 'phone_code'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['phone_other_country' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.phone_other_country_id = phone_other_country.id',
                ['phone_other_country_code' => 'phone_code'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['phone_fax_country' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.phone_fax_country_id = phone_fax_country.id',
                ['phone_fax_country_code' => 'phone_code'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.id', $id);
        });

        return $result;
    }

    /**
     * @param string $contactName
     * @param int $teamId
     *
     * @return bool|\DDD\Domain\Contacts\Contact[]
     */
    public function getContactByNameWithinTeamId($contactName, $teamId)
    {
        $result = $this->fetchAll(function (Select $select) use ($contactName, $teamId) {
            $select->columns([
                'id',
                'creator_id',
                'date_created',
                'date_modified',
                'team_id',
                'apartment_id',
                'building_id',
                'partner_id',
                'name',
                'company',
                'position',
                'city',
                'address',
                'email',
                'skype',
                'url',
                'phone_mobile',
                'phone_company',
                'phone_other',
                'phone_fax',
                'notes'
            ]);

            $select->where
                ->literal('LOWER(`name`) = LOWER("'.$contactName.'")')
                ->and
                ->equalTo('team_id', $teamId);
        });

        return $result;
    }

    /**
     * @param string $query
     * @param bool|int $userId
     * @param bool $global
     *
     * @return bool|\DDD\Domain\Contacts\Card[]
     */
    public function findContactsForOmniSearch($query, $userId = false, $global = false)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Contacts\Card());

        $result = $this->fetchAll(function (Select $select) use ($query, $userId, $global) {
            $select->columns([
                'id',
                'creator_id',
                'date_created',
                'date_modified',
                'scope',
                'team_id',
                'apartment_id',
                'building_id',
                'partner_id',
                'name',
                'company',
                'position',
                'city',
                'address',
                'email',
                'skype',
                'url',
                'phone_mobile',
                'phone_company',
                'phone_other',
                'phone_fax',
                'notes'
            ]);

            $select
                ->join(
                    ['team' => DbTables::TBL_TEAMS],
                    $this->getTable() . '.team_id = team.id',
                    ['team_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['team_staff' => DbTables::TBL_TEAM_STAFF],
                    new Expression($this->getTable() . '.team_id = team_staff.team_id
                        AND team_staff.type IN(' . Team::STAFF_MANAGER . ', ' . Team::STAFF_OFFICER . ', ' . Team::STAFF_MEMBER . ')'
                    ),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id = apartment.id',
                    ['apartment_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['building' => DbTables::TBL_APARTMENT_GROUPS],
                    $this->getTable() . '.building_id = building.id',
                    ['building_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                    $this->getTable() . '.partner_id = partner.gid',
                    ['partner_name'],
                    Select::JOIN_LEFT
                );

            $select->where
                ->NEST
                ->like($this->getTable() . '.name', '%' . $query . '%')
                ->or
                ->like($this->getTable() . '.company', '%' . $query . '%')
                ->or
                ->like($this->getTable() . '.position', '%' . $query . '%')
                ->or
                ->like('apartment.name', '%' . $query . '%')
                ->or
                ->like('building.name', '%' . $query . '%')
                ->or
                ->like('partner.partner_name', '%' . $query . '%')
                ->UNNEST;
            $nestedWhere = new Where();

            $nestedWhere
                ->equalTo($this->getTable() . '.creator_id', $userId)
                ->or
                ->equalTo('scope', ContactService::SCOPE_GLOBAL);

            if ($global) {
                $nestedWhere
                    ->or
                    ->equalTo('scope', ContactService::SCOPE_TEAM);
            } else {
                $nestedWhere
                    ->or
                    ->NEST
                    ->equalTo('team_staff.user_id', $userId)
                    ->and
                    ->equalTo('scope', ContactService::SCOPE_TEAM);
            }
            $select->where->addPredicate($nestedWhere);
            $select->group($this->getTable() . '.id');
        });

        return $result;
    }

    /**
     * @param $apartmentId
     * @return \Zend\Db\ResultSet\ResultSet | \DDD\Domain\Contacts\Card[]
     */
    public function  getContactByApartmentId($apartmentId)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Contacts\Card());

        $result = $this->fetchAll(function (Select $select) use ($apartmentId) {
            $select->join(
                ['phone_mobile_country' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.phone_mobile_country_id = phone_mobile_country.id',
                ['phone_mobile_country_code' => 'phone_code'],
                Select::JOIN_LEFT
            );
            $select->where->equalTo('apartment_id', $apartmentId);
        });
        $this->setEntity($prototype);

        return $result;
    }

    public function  getContactByBuildingId($buildingId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Contacts\Card());

        $result = $this->fetchAll(function (Select $select) use ($buildingId) {
            $select->join(
                ['phone_mobile_country' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.phone_mobile_country_id = phone_mobile_country.id',
                ['phone_mobile_country_code' => 'phone_code'],
                Select::JOIN_LEFT
            );
            $select->where->equalTo('building_id', $buildingId);
        });

        return $result;
    }
}
