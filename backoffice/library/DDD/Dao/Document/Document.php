<?php
namespace DDD\Dao\Document;

use DDD\Service\Accommodations;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use DDD\Service\Document\Document as DocumentService;
use DDD\Service\Team\Team as TeamService;
use Zend\Db\Sql\Where;

/**
 * DAO class for documents
 */
class Document extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_DOCUMENTS;

    /**
     * @access public
     * @param $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Document\Document')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $docId
     * @return \DDD\Domain\Document\Document
     */
    public function getDocumentDataById($docId)
    {
        $result = $this->fetchOne(function (Select $select) use($docId) {

            $select->columns([
                'id',
                'type_id',
                'entity_id',
                'entity_type',
                'description',
                'username',
                'password',
                'url',
                'attachment',
                'security_level',
                'account_number',
                'account_holder',
                'supplier_id',
                'valid_from',
                'valid_to',
                'signatory_id',
                'legal_entity_id',
                'is_frontier',
                'created_by',
                'created_date',
                'last_edited_by',
                'last_edited_date',
                'entity_name' => new Expression('IFNULL(building.name, apartment.name)'),
            ]);

            $select->join(
                ['user_creator' => DbTables::TBL_BACKOFFICE_USERS],
                $this->table . '.created_by = user_creator.id',
                [
                    'creator_firstname' => 'firstname',
                    'creator_lastname'  => 'lastname'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['user_editor' => DbTables::TBL_BACKOFFICE_USERS],
                $this->table . '.last_edited_by = user_editor.id',
                [
                    'last_editor_firstname' => 'firstname',
                    'last_editor_lastname'  => 'lastname'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                new Expression($this->table . '.entity_id = apartment.id AND ' . $this->getTable() . '.entity_type = '. DocumentService::ENTITY_TYPE_APARTMENT),
                ['apartment_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['building' => DbTables::TBL_APARTMENT_GROUPS],
                new Expression($this->table . '.entity_id = building.id AND ' . $this->getTable() . '.entity_type = '. DocumentService::ENTITY_TYPE_APARTMENT_GROUP),
                ['building_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['document_type' => DbTables::TBL_DOCUMENT_TYPES],
                $this->getTable() . '.type_id = document_type.id',
                ['type_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->where(
                [$this->getTable().'.id' => $docId]
            );
        });

        return $result;
    }

    /**
     * @param Where $where
     * @return \DDD\Domain\Document\Document[]
     */
    public function getDocuments($where)
    {
        return $this->fetchAll(function (Select $select) use($where) {

            $columns = [
                'id',
                'type_id',
                'security_level',
                'entity_id',
                'entity_type',
                'supplier_id',
                'description',
                'url',
                'attachment',
                'created_date',
                'account_number',
                'account_holder',
                'valid_from',
                'valid_to',
                'entity_name' => new Expression('IFNULL(building.name, apartment.name)'),
            ];

            $select
                ->columns($columns)
                ->join(
                    ['document_types' => DbTables::TBL_DOCUMENT_TYPES],
                    $this->table . '.type_id = document_types.id',
                    ['type_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['suppliers' => DbTables::TBL_SUPPLIERS],
                    $this->table . '.supplier_id = suppliers.id',
                    ['supplier_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment' => DbTables::TBL_APARTMENTS],
                    new Expression($this->table . '.entity_id = apartment.id AND ' . $this->getTable() . '.entity_type = '. DocumentService::ENTITY_TYPE_APARTMENT),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['building' => DbTables::TBL_APARTMENT_GROUPS],
                    new Expression($this->table . '.entity_id = building.id AND ' . $this->getTable() . '.entity_type = '. DocumentService::ENTITY_TYPE_APARTMENT_GROUP),
                    ['building_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['city' => DbTables::TBL_CITIES],
                    'apartment.city_id = city.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['province' => DbTables::TBL_PROVINCES],
                    'city.province_id = province.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['country' => DbTables::TBL_COUNTRIES],
                    'province.country_id = country.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ["city_details" => DbTables::TBL_LOCATION_DETAILS],
                    'city.detail_id = city_details.id',
                    ['city_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ["country_details" => DbTables::TBL_LOCATION_DETAILS],
                    'country.detail_id = country_details.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['legal_entities' => DbTables::TBL_LEGAL_ENTITIES],
                    $this->getTable() . '.legal_entity_id = legal_entities.id',
                    ['legal_entity_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['signatories' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.signatory_id = signatories.id',
                    [
                        'signatory_first_name' => 'firstname',
                        'signatory_last_name'  => 'lastname',
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['team' => DbTables::TBL_TEAMS],
                    $this->table . '.security_level = team.id',
                    ['team_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->group($this->getTable() . '.id');

            if ($where !== null) {
                $select->where($where);
            }
            $select->where->notEqualTo('apartment.status', Accommodations::APARTMENT_STATUS_DISABLED);

            $select->group('id');

            $select->order('entity_name');
        });
    }

    /**
     * Get apartment documents
     * @access public
     *
     * @param int $apartmentId
     * @param int $securityLevels
     * @param int $hasSecurityAccess
     * @return \DDD\Domain\Document\Document []
     * @author Tigran Petrosyan
     */
    public function getApartmentDocuments($apartmentId, $securityLevels, $hasSecurityAccess)
    {
        if (!isset($securityLevels[0]) && !$hasSecurityAccess) {
            return false;
        }

        $columns = [
            'id'             => 'id',
            'description'    => 'description',
            'username'       => 'username',
            'password'       => 'password',
            'security_level' => 'security_level',
            'url'            => 'url',
            'attachment'     => 'attachment',
            'created_date'   => new Expression('DATE('.$this->getTable().'.created_date)')
        ];

        return $this->fetchAll(function (Select $select) use($columns, $apartmentId, $securityLevels, $hasSecurityAccess) {

            $select->columns($columns);

            $select->join(
                ['document_types' => DbTables::TBL_DOCUMENT_TYPES],
                $this->table . '.type_id = document_types.id',
                ['type_name' => 'name']
            );

            $select->join(
                ['team' => DbTables::TBL_TEAMS],
                $this->table . '.security_level = team.id',
                ['team_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.entity_id', $apartmentId)
                ->equalTo($this->getTable() . '.entity_type', DocumentService::ENTITY_TYPE_APARTMENT);


            if (isset($securityLevels[0]) && !$hasSecurityAccess) {
                $select->where->in($this->table . '.security_level', $securityLevels);
            }

        });
    }

    public function getAfter60DaysExpiringApartmentDocumentsWithInvolvedManagersList()
    {
        /** @var \DDD\Domain\Document\Document[] $result */
        $result =  $this->fetchAll(function (Select $select) {

            $select->columns([
                'id',
                'apartment_id' => 'entity_id',
                'valid_to'
            ]);

            $select->join(
                ['document_types' => DbTables::TBL_DOCUMENT_TYPES],
                $this->table . '.type_id = document_types.id',
                ['type_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['teams' => DbTables::TBL_TEAMS],
                $this->table . '.security_level = teams.id',
                [],
                Select::JOIN_LEFT

            );

            $select->join(
                ['team_staff' => DbTables::TBL_TEAM_STAFF],
                'teams.id = team_staff.team_id',
                ['team_manager_id' => 'user_id'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->table . '.entity_id = apartments.id',
                ['apartment_name' => 'name'],
                Select::JOIN_LEFT
            );


            $select->where(['team_staff.type' => TeamService::STAFF_MANAGER]);

            $select->where
                ->expression(
                    'DATE('.$this->table. '.valid_to) = DATE("' . date('Y-m-d', strtotime('+60 day')) .'")',
                    []
                )
                ->equalTo($this->getTable() . '.entity_type', DocumentService::ENTITY_TYPE_APARTMENT);

        });
        $resultArray = [];
        foreach($result as $row){
            array_push(
                $resultArray,
                [
                    'documentId'        => $row->getId(),
                    'apartmentId'       => $row->getEntityId(),
                    'apartmentName'     => $row->getEntityName(),
                    'managerId'        => $row->getTeamManagerId(),
                    'documentTypeName'  => $row->getTypeName(),
                    'validTo'           => $row->getValidTo(),

                ]
            );
        }
        return $resultArray;
    }

    public function getApartmentDocumentsForFrontier($userId, $apartmentId)
    {
        /** @var \DDD\Domain\Document\Document[] $result */
        $result =  $this->fetchAll(function (Select $select) use($userId, $apartmentId) {

            $select->columns([
                'id',
                'type_id',
                'security_level',
                'apartment_id' => 'entity_id',
                'supplier_id',
                'description',
                'url',
                'attachment',
                'created_date',
                'account_number',
                'account_holder',
                'valid_from',
                'valid_to',
            ]);

            $select->join(
                ['document_types' => DbTables::TBL_DOCUMENT_TYPES],
                $this->table . '.type_id = document_types.id',
                ['type_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['suppliers' => DbTables::TBL_SUPPLIERS],
                $this->table . '.supplier_id = suppliers.id',
                ['supplier_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->table . '.entity_id = apartments.id',
                ['apartment_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['apartment_groups' => DbTables::TBL_APARTMENT_GROUPS],
                'apartments.building_id = apartment_groups.id',
                ['building_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['cities' => DbTables::TBL_CITIES],
                'apartments.city_id = cities.id',
                [],
                Select::JOIN_LEFT
            );



            $select->join(
                ['legal_entities' => DbTables::TBL_LEGAL_ENTITIES],
                $this->getTable() . '.legal_entity_id = legal_entities.id',
                ['legal_entity_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['signatories' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.signatory_id = signatories.id',
                [
                    'signatory_first_name' => 'firstname',
                    'signatory_last_name'  => 'lastname',
                ],
                Select::JOIN_LEFT
            );



            $select->join(
                ['team_apartments' => DbTables::TBL_TEAM_FRONTIER_APARTMENTS],
                $this->getTable() . '.entity_id = team_apartments.apartment_id',
                [

                ],
                Select::JOIN_INNER
            );

            $select->join(
                ['teams' => DbTables::TBL_TEAMS],
                'teams.id = team_apartments.team_id',
                [],
                Select::JOIN_INNER
            );

            $select->join(
                ['team_staff' => DbTables::TBL_TEAM_STAFF],
                'teams.id = team_staff.team_id',
                [],
                Select::JOIN_INNER
            );

            $select
                ->where
                    ->equalTo($this->table . '.entity_type', DocumentService::ENTITY_TYPE_APARTMENT)
                    ->equalTo($this->table . '.is_frontier', 1)
                    ->equalTo($this->table . '.entity_id', $apartmentId)
                    ->equalTo('team_staff.user_id', $userId)
                    ->equalTo('team_staff.type', TeamService::STAFF_MANAGER);

            $select->group('id');
            $select->order('apartments.name');
        });

        $resultArray = [];
        foreach($result as $row){
            array_push($resultArray,
                [
                    'typeName'        => $row->getTypeName(),
                    'validTo'         => $row->getValidTo(),
                    'description'     => $row->getDescription()
                ]) ;
        }

        return $resultArray;
    }

    /**
     * @param int $apartmentGroupId
     * @param array $securityLevels
     * @param int $hasSecurityAccess
     * @return bool|\Zend\Db\ResultSet\ResultSet|\DDD\Domain\Document\Document
     */
    public function getApartmentGroupDocuments($apartmentGroupId, $securityLevels, $hasSecurityAccess)
    {
        if (!isset($securityLevels[0]) && !$hasSecurityAccess) {
            return false;
        }

        $columns = [
            'id'             => 'id',
            'description'    => 'description',
            'username'       => 'username',
            'password'       => 'password',
            'security_level' => 'security_level',
            'url'            => 'url',
            'attachment'     => 'attachment',
            'created_date'   => new Expression('DATE('.$this->getTable().'.created_date)')
        ];

        return $this->fetchAll(function (Select $select) use($columns, $apartmentGroupId, $securityLevels, $hasSecurityAccess) {

            $select->columns($columns);

            $select->join(
                ['document_types' => DbTables::TBL_DOCUMENT_TYPES],
                $this->table . '.type_id = document_types.id',
                ['type_name' => 'name']
            );

            $select->join(
                ['team' => DbTables::TBL_TEAMS],
                $this->table . '.security_level = team.id',
                ['team_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo('entity_id', $apartmentGroupId)
                ->equalTo('entity_type', DocumentService::ENTITY_TYPE_APARTMENT_GROUP);

            if (isset($securityLevels[0]) && !$hasSecurityAccess) {
                $select->where->in($this->table . '.security_level', $securityLevels);
            }
        });
    }
}