<?php

    namespace DDD\Dao\User;

    use DDD\Service\User\Evaluations;
    use Library\Utility\Debug;
    use Zend\Db\Sql\Select;
    use Library\DbManager\TableGatewayManager;
    use Library\Constants\DbTables;
    use Zend\Db\Sql\Where;
    use Library\Constants\Roles;
    use Zend\Db\Sql\Expression;
    use Zend\ServiceManager\ServiceLocatorInterface;

    /**
     * Class UserManager
     * @package DDD\Dao\User
     */
    final class UserManager extends TableGatewayManager
    {
        /**
         * @var string
         */
        protected $table = DbTables::TBL_BACKOFFICE_USERS;

        /**
         * Constructor
         * @access public
         *
         * @param ServiceLocatorInterface $sm
         * @param string $domain
         */
        public function __construct($sm, $domain = 'DDD\Domain\User\User')
        {
            parent::__construct($sm, $domain);
        }

        /**
         * @param $id
         * @param bool|false $isManager
         * @param array $columns
         * @param bool|false $withCurrencyData
         * @return array|\ArrayObject|null
         */
        public function getUserById($id, $isManager = false, array $columns = [], $withCurrencyData = false)
        {
            return $this->fetchOne(function (Select $select) use($id, $isManager, $columns, $withCurrencyData) {
                if (!empty($columns)) {
                    $select->columns($columns);
                }

                if ($withCurrencyData) {
                    $select->join(
                        ['city' => DbTables::TBL_CITIES],
                        $this->getTable() . '.city_id = city.id',
                        [],
                        Select::JOIN_LEFT
                    );

                    $select->join(
                        ['province' => DbTables::TBL_PROVINCES],
                        'city.province_id = province.id',
                        [],
                        Select::JOIN_LEFT
                    );

                    $select->join(
                        ['country' => DbTables::TBL_COUNTRIES],
                        'province.country_id = country.id',
                        ['currency_id'],
                        Select::JOIN_LEFT
                    );

                    $select->join(
                        ['currency' => DbTables::TBL_CURRENCY],
                        'country.currency_id = currency.id',
                        ['currency_code' => 'code'],
                        Select::JOIN_LEFT
                    );
                }

                $where = new Where();
                $where->equalTo($this->getTable() . '.id', $id);

                if (!$isManager) {
                    $where->equalTo($this->getTable() . '.disabled', 0);
                }

                $select->where($where);
            });
        }

        /**
         * Get User data for rest
         *
         * @param $id
         * @return array|\ArrayObject|null
         */
        public function getUserDataById($id)
        {
            return $this->fetchOne(function (Select $select) use($id) {
                $where = new Where();
                $where->equalTo($this->getTable() . '.id', $id);
                $where->equalTo($this->getTable() . '.disabled', 0);

                $select
                    ->columns([
                        'id',
                        'firstname',
                        'lastname',
                        'phone' => 'business_phone',
                        'email',
                        'avatar',
                    ])
                    ->join(
                        ['teams' => DbTables::TBL_TEAMS],
                        $this->getTable() . '.department_id = teams.id',
                        ['department' => 'name'],
                        'left'
                    )
                    ->join(
                        ['managers' => DbTables::TBL_BACKOFFICE_USERS],
                        $this->getTable() . '.manager_id = managers.id',
                        [
                            'manager_firstname' => 'firstname',
                            'manager_lastname'  => 'lastname',
                        ],
                        'left'
                    )
                    ->where($where);
            });
        }

        /**
         * Get User data for rest
         *
         * @param $id
         * @param $pin
         * @return mixed
         */
        public function getUserByIdAndPin($id, $pin)
        {
            $result = $this->fetchOne(function (Select $select) use($id, $pin) {
                $where = new Where();
                $where->equalTo($this->getTable() . '.id', $id);
                $where->equalTo($this->getTable() . '.ginocoin_pin', $pin);
                $select->where($where);
            });

            return $result;
        }

        /**
         * @param int $id
         * @return \DDD\Domain\User\User
         */
        public function findUserById($id)
        {
            return $this->fetchOne(function (Select $select) use($id) {
                $select->where(['id' => $id]);
            });
        }

        public function getAvailableManagers($id)
        {
            return $this->fetchAll(function (Select $select) use($id) {
                $select->where('id <> '. (int)$id);
            });
        }

        public function checkEmail($email, $id)
        {
            return $this->fetchOne(function (Select $select) use($email, $id) {
                $select->where(['email' => $email]);

                if ($id > 0) {
                    $select->where->notEqualTo('id', (int)$id);
                }
            });
        }

        public function checkGinosikEmail($id, $email) {
            return $this->fetchOne(function (Select $select) use($id, $email) {
                $select->where->equalTo('email', $email);
                $select->where->equalTo('id', (int)$id);
            });
        }

        public function searchUserByAutocomplate($txt)
        {
            return $this->fetchAll(function (Select $select) use($txt) {
                $select
                    ->columns([
                        'id',
                        'firstname',
                        'lastname'
                    ])
                    ->join(
                        ['apartment_group' => DbTables::TBL_APARTMENT_GROUPS],
                        $this->getTable() . '.id = apartment_group.user_id',
                        ['apartment_group_id' => 'id'],
                        'left'
                    )
                    ->where("(firstname like '" . $txt . "%' OR lastname like '" . $txt ."%' ) AND disabled = 0")
                    ->group($this->getTable().'.id');
            });
        }

        public function findUserByAutocomplate($txt){
            return $this->fetchAll(function (Select $select) use($txt) {
                $select->columns(array('id', 'firstname','lastname'))
                    ->where("(firstname like '" . $txt . "%' OR lastname like '" . $txt ."%' ) AND disabled = 0 AND system = 0")
                    ->group($this->getTable().'.id');
            });
        }

        public function searchManagerByAutocomplate($txt) {
            return $this->fetchAll(function (Select $select) use($txt) {
                $select->columns(array('id', 'firstname','lastname'))
                    ->where("(firstname like '" . $txt . "%' OR lastname like '" . $txt ."%') AND disabled = 0")
                    ->group($this->getTable().'.id');
            });
        }


        /**
         * @param string $query
         * @param bool $all
         * @return \DDD\Domain\User\User[]
         */
        public function getUsers($query = '', $all = false)
        {
            return $this->fetchAll(function (Select $select) use($query, $all) {
                if (!empty($query)) {
                    $select->where("(firstname like '" . $query . "%' OR lastname like '" . $query ."%') AND system = 0");
                }

                if (!$all) {
                    $select->where->equalTo('disabled', 0);
                }

                $select->columns(array('id', 'manager_id', 'firstname', 'lastname', 'avatar'))
                    ->order('firstname')
                    ->limit(10);
            });
        }


        /**
         * @param string $query
         * @param int $limit
         * @return \DDD\Domain\User\User[]
         */
        public function getUsersForOmnibox($query, $limit)
        {
            return $this->fetchAll(function (Select $select) use($query, $limit) {
                $select->where("(firstname like '" . $query . "%' OR lastname like '" . $query ."%') AND system = 0");

                $select->where->equalTo('disabled', 0);

                $select->columns(['id', 'manager_id', 'firstname', 'lastname', 'avatar', 'internal_number'])
                    ->order('firstname')
                    ->limit($limit);
            });
        }

        public function getForSelect()
        {
            return $this->fetchAll(function (Select $select) {
                $select
                    ->columns(['id', 'firstname', 'lastname', 'avatar'])
                    ->where(['disabled' => '0', 'system' => '0', 'external' => 0])
                    ->order('firstname ASC');
            });
        }

        /**
         * @param bool|int $managerId
         * @param bool $active
         * @return \DDD\Domain\User\User[]|array[]|\ArrayObject
         */
        public function getPeopleList($managerId = false, $active = true, $withoutExternalUsers = true, $countryId = false)
        {
            return $this->fetchAll(function (Select $select) use ($managerId, $active, $withoutExternalUsers, $countryId) {
                $select->columns(['id', 'firstname', 'lastname', 'manager_id', 'email']);
                $where = new Where();

                $where->equalTo('system', 0);

                if ($withoutExternalUsers) {
                    $where->equalTo('external', 0);
                }

                if ($countryId) {
                    $where->equalTo('country_id', $countryId);
                }

                if ($active || $managerId) {
                    $nest = $where->nest();
                }

                if ($active) {
                    $nest->equalTo('disabled', 0);
                }

                if ($managerId) {
                    $nest->or->equalTo('id', $managerId);
                }

                $select->where($where);
                $select->order('firstname ASC');
            });
        }

        /**
         * @param bool|int $managerId
         * @return array[]|\ArrayObject
         */
        public function getExtendedPeopleList($managerId = false)
        {
            $this->setEntity(new \ArrayObject());

            return $this->fetchAll(function (Select $select) use ($managerId) {
                $select->columns([
                    'id',
                    'name' => new Expression('concat(firstname, " ", lastname)'),
                    'manager_id',
                ]);

                $select
                    ->where
                    ->equalTo('system', 0)
                    ->equalTo('external', 0)
                    ->equalTo('disabled', 0);

                if ($managerId) {
                    $select->where->or->equalTo('id', $managerId);
                }

                $select->order('name ASC');
            })->buffer();
        }

        public function checkManagerDisabled($id)
        {
            $result = $this->fetchOne(function (Select $select) use($id) {
                $select->columns([])
                    ->join(
                        ['bo' => DbTables::TBL_BACKOFFICE_USERS],
                        $this->getTable() . '.manager_id = bo.id',
                        ['id', 'disabled'], 'LEFT'
                    );
                $select->where([$this->getTable() . '.id' => $id]);
            });

            return $result;
        }

        /**
         * @param int|null $peopleId
         * @return \Zend\Db\ResultSet\ResultSet|\DDD\Domain\User\User[]
         */
        public function getForTicketManager($peopleId = null)
        {
            return $this->fetchAll(function (Select $select) use ($peopleId) {
                $select->columns([
                    'id' => new Expression("distinct {$this->getTable()}.id"),
                    'firstname',
                    'lastname',
                ]);
                $select->join(
                    ['user_group' => DbTables::TBL_BACKOFFICE_USER_GROUPS],
                    $this->getTable() . '.id = user_group.user_id',
                    []
                );

                $select->where([
                    $this->getTable() . '.disabled' => 0,
                    $this->getTable() . '.system' => 0,
                    'user_group.group_id' => Roles::ROLE_FINANCE_BUDGET_HOLDER,
                ]);

                if ($peopleId) {
                    $select->where->or->equalTo($this->getTable() . '.id', $peopleId);
                }

                $select->order('firstname ASC');
            });
        }

        /**
         * @param $id
         * @return \DDD\Domain\User\User|array|\ArrayObject|null
         *
         * @todo method name and context TOTAL mismatch
         */
        public function getManagerId($id)
        {
            $result = $this->fetchOne(function (Select $select) use($id) {
                $select->columns(array('id', 'manager_id', 'city_id', 'budget_holder'))
                    ->where(array(
                        'id' => $id
                    ))
                    ->order('firstname ASC');
            });

            return $result;
        }

        public function getUsersByManagerId($managerId)
        {
            $result = $this->fetchAll(function (Select $select) use($managerId) {
                $select->columns(array('id', 'firstname', 'lastname', 'avatar'))
                    ->where(['manager_id' => $managerId, 'disabled' => 0, 'system' => 0])
                    ->order('firstname ASC');
            });
            return $result;
        }

        public function getUsersBasicInfo(
            $iDisplayStart = null,
            $iDisplayLength = null,
            $sortCol = 0,
            $sortDir = 'ASC',
            $where)
        {
            $sortColumns = [
                'disabled',
                'firstname',
                'city',
                'position',
                'department',
                'next_evaluation',
                'vacation_days',
                'vacation_days_per_year',
                'start_date',
                'end_date'
            ];

            $result = $this->fetchAll(function (Select $select) use($where, $sortColumns, $iDisplayStart, $iDisplayLength, $sortCol, $sortDir) {
                $select->columns([
                    'id',
                    'firstname',
                    'lastname',
                    'position',
                    'manager_id',
                    'start_date',
                    'end_date',
                    'vacation_days',
                    'vacation_days_per_year',
                    'period_of_evaluation',
                    'disabled',
                    'avatar'
                ]);

                $select->join(
                    ['cities' => DbTables::TBL_CITIES],
                    $this->getTable() . '.city_id = cities.id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['evaluations' => DbTables::TBL_USER_EVALUATIONS],
                    new Expression($this->getTable() . '.id = evaluations.user_id AND evaluations.status = 1 AND evaluations.type_id = 3 AND evaluations.date_created > NOW()'),
                    ['next_evaluation' => new Expression('min(evaluations.date_created)')],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['details' => DbTables::TBL_LOCATION_DETAILS],
                    'details.id = cities.detail_id',
                    ['city' => 'name'],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['teams' => DbTables::TBL_TEAMS],
                    $this->getTable() . '.department_id = teams.id',
                    ['department' => 'name'],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['ud' => DbTables::TBL_BACKOFFICE_USER_DASHBOARDS],
                    $this->getTable() . '.id = ud.user_id',
                    [],
                    Select::JOIN_LEFT
                );

                if ($where !== null) {
                    $select->where($where);
                }

                $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));

                if ($iDisplayLength !== null && $iDisplayStart !== null) {
                    $select->limit((int)$iDisplayLength);
                    $select->offset((int)$iDisplayStart);
                }
                $select->group($this->getTable() . '.id');
                $select->order($sortColumns[$sortCol] . ' ' . $sortDir);
            });

            $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
            $result2 = $statement->execute();
            $row = $result2->current();
            $total = $row['total'];
            return ['result' => $result, 'total' => $total];
        }

        public function getUserForContactInfo($id)
        {
            return $this->fetchOne(function (Select $select) use ($id) {
                $select->columns(['id', 'firstname', 'lastname',
                    'email', 'internal_number', 'personal_phone', 'business_phone', 'emergency_phone',
                    'house_phone', 'address_permanent', 'address_residence', 'position', 'alt_email', 'living_city']);
                $select->where->equalTo($this->getTable() . '.id', $id);
                $select->join(
                    ['cities' => DbTables::TBL_CITIES],
                    $this->getTable() . '.city_id = cities.id',
                    [],
                    Select::JOIN_LEFT
                );
                $select->join(
                    ['details' => DbTables::TBL_LOCATION_DETAILS],
                    'details.id = cities.detail_id',
                    ['city' => 'name'],
                    Select::JOIN_LEFT
                );
            });
        }

        /**
         * @param $departmentId int
         * @return \Zend\Db\ResultSet\ResultSet
         */
        public function getUserByDepartment($departmentId)
        {
            $result = $this->fetchAll(function (Select $select) use($departmentId) {
                $select->columns(
                    [
                        'id',
                        'firstname',
                        'lastname',
                        'manager_id'
                    ]
                )->join(
                    ['m' => DbTables::TBL_TEAM_STAFF],
                    $this->getTable() . '.id = m.user_id',
                    ['team_id'],
                    Select::JOIN_LEFT
                )->where(
                    [
                        'm.team_id' => $departmentId,
                        $this->getTable() . '.disabled' => 0,
                        $this->getTable() . '.system' => 0
                    ]
                )->order('firstname ASC');
            });
            return $result;
        }

        public function getUserByEmail($email, $returnArray = false, $customColumn = false)
        {
            if ($returnArray) {
                $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
                $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
            }

            $result = $this->fetchOne(function (Select $select) use($email, $customColumn) {
                if ($customColumn) {
                    $select->columns($customColumn);
                }

                $select->where->equalTo('email', $email);
            });

            if ($returnArray) {
                $this->resultSetPrototype->setArrayObjectPrototype($prototype);
            }

            return $result;
        }

        public function getUserByAlternativeEmail($email)
        {
            $result = $this->fetchOne(function (Select $select) use($email) {
                $select->where(array('alt_email'=>$email));
            });
            return $result;
        }

        /**
         * @param $email
         * @return array|\ArrayObject|null
         */
        public function getUserIdByEmailAddress($email)
        {
            $this->setEntity(new \ArrayObject());

            $result = $this->fetchOne(function (Select $select) use($email) {
                $select->columns([
                    'id',
                    'system',
                    'disabled'
                ]);
                $select
                    ->where
                    ->equalTo('email', $email)
                    ->or
                    ->equalTo('alt_email', $email);
            });

            return $result;
        }

        public function getUsersCountries() {
            return $this->fetchAll(function (Select $select) {
                $select->columns([]);
                $select->join(
                    ['cities' => DbTables::TBL_CITIES],
                    $this->getTable() . '.city_id = cities.id',
                    ['id' => new Expression('DISTINCT(cities.id)')],
                    Select::JOIN_LEFT
                );
                $select->join(
                    ['details' => DbTables::TBL_LOCATION_DETAILS],
                    'details.id = cities.detail_id',
                    [
                        'name' => 'name',
                        'city' => 'name',
                    ],
                    Select::JOIN_LEFT
                );
                $select->where->isNotNull('details.name');

                $select->order(['details.name ASC']);
            });
        }

        public function setOfficeUser($officeId, $userId)
        {
            $data = [
                'reporting_office_id' => $officeId
            ];
            $where = new Where();
            $where->equalTo($this->table . '.id', $userId);

            $this->update(
                $data,
                $where
            );
        }

        public function getUserRoles($userId, $roles = null)
        {
            //TODO: if need to improve for multi roles
            $result = $this->fetchOne(
                function (Select $select) use($userId, $roles) {
                    $select
                        ->join(
                            ['roles' => DbTables::TBL_BACKOFFICE_USER_GROUPS],
                            $this->getTable() . '.id = roles.user_id',
                            ['group_id']
                        )->where(
                            [
                                $this->getTable() . '.id'        => $userId,
                                $this->getTable() . '.disabled'  => 0,
                                'roles.group_id' =>
                                    constant('\Library\Constants\Roles::'. $roles)
                            ]
                        );
                }
            );
            return $result;
        }

        public function getGinosiksReservation($email)
        {
            return $this->fetchOne(
                function (Select $select) use($email) {
                    $select->columns(['email', 'alt_email']);
                    $select->where->expression(
                        $this->getTable() . '.email ="' . $email . '" OR ' .
                        $this->getTable() . '.alt_email = "' . $email . '"',
                        []
                    );
                }
            );
        }

        public function getUserByDocumentId($documentId)
        {
            return $this->fetchOne(function (Select $select) use ($documentId) {
                $select->columns(['id', 'firstname', 'lastname']);
                $select->join(
                    ['user_documents' => DbTables::TBL_USER_DOCUMENTS],
                    $this->getTable() . '.id = user_documents.user_id',
                    [
                        'document_id' => 'id',
                        'type_id',
                        'description',
                        'attachment',
                        'url'
                    ]
                );
                $select->join(
                    ['user_document_types' => DbTables::TBL_USER_DOCUMENT_TYPES],
                    'user_document_types.id = user_documents.type_id',
                    ['type' => 'title']
                );
                $select->where(['user_documents.id' => $documentId]);
            });
        }

        public function getUsersWithPermission($query, $editableUserId = FALSE)
        {
            $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
            return $this->fetchAll(function (Select $select) use($query, $editableUserId) {
                $select->columns(['id', 'manager_id', 'firstname', 'lastname']);

                $select->join(
                    ['user_groups' => DbTables::TBL_BACKOFFICE_USER_GROUPS],
                    $this->getTable() . '.id = user_groups.user_id',
                    []
                );

                $select->join(
                    ['groups' => DbTables::TBL_GROUPS],
                    'user_groups.group_id = groups.id',
                    [
                        'type'      => 'type',
                        'parent_id' => 'parent_id',
                        'group_id'  => 'id',
                    ]
                );

                if (FALSE !== $editableUserId && $editableUserId != 0) {
                    $select->where->notEqualTo($this->getTable() . '.id', $editableUserId);
                }
                $select->where("(firstname like '" . $query . "%' OR lastname like '" . $query ."%') AND disabled = 0 AND system = 0")
                    ->order('firstname');
            });
        }

        /**
         * @param $backofficeUserId int
         * @return \ArrayObject|null
         */
        public function getUserDepartment($backofficeUserId)
        {
            $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
            $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

            $result = $this->fetchOne(
                function (Select $select) use($backofficeUserId) {
                    $where = new Where();
                    $where->equalTo($this->getTable() . '.id', $backofficeUserId);

                    $select
                        ->columns(['department_id' => 'department_id'])
                        ->where($where);
                }
            );

            $this->resultSetPrototype->setArrayObjectPrototype($prototype);

            return $result;
        }

        /**
         * @param bool $withOutExternalUsers
         * @param int $selectedUserId
         * @return array
         */
        public function getAllActiveUsersArray($withOutExternalUsers, $selectedUserId = 0)
        {
            $result = $this->fetchAll(function (Select $select)  use ($withOutExternalUsers, $selectedUserId) {
                $select->columns([
                    'id',
                    'firstname',
                    'lastname',
                ]);

                $nestedWhere = new Where();
                $nestedWhere
                    ->equalTo('disabled', 0)
                    ->equalTo('system', 0);

                if ($withOutExternalUsers) {
                    $nestedWhere->equalTo('external', 0);
                }

                $where = new Where();
                if ($selectedUserId) {
                    $where
                        ->equalTo('id', $selectedUserId)
                        ->orPredicate($nestedWhere);
                } else {
                    $where = $nestedWhere;
                }

                $select->where($where);
            });

            $resultArray = [0 => '-- Please Select --'];

            foreach ($result as $row) {
                $resultArray[$row->getId()] = $row->getFullName();
            }

            return $resultArray;
        }

        /**
         * @param $managerId
         * @return int
         */
        public function getEvaluationLessEmployeesCount($managerId)
        {
            $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

            $result = $this->fetchOne(function (Select $select) use($managerId) {
                $select->columns([
                    'count' => new Expression('COUNT(*)')
                ]);

                $select->join(
                    ['evaluations' => DbTables::TBL_USER_EVALUATIONS],
                    new Expression($this->getTable() . '.id = evaluations.user_id AND evaluations.status = ' . Evaluations::USER_EVALUATION_STATUS_PLANNED),
                    [],
                    Select::JOIN_LEFT
                );

                $select->where
                    ->equalTo('disabled', 0)
                    ->equalTo('system', 0)
                    ->equalTo($this->getTable() . '.manager_id', $managerId)
                    ->notEqualTo($this->getTable() . '.period_of_evaluation', 0)
                    ->isNotNull($this->getTable() . '.period_of_evaluation')
                    ->isNull('evaluations.id');
            });

            return $result['count'];
        }

        /**
         * @param $managerId
         * @return \DDD\Domain\User\User[]
         */
        public function getEvaluationLessEmployees($managerId)
        {
            $result = $this->fetchAll(function (Select $select) use($managerId) {
                $select->columns([
                    'id',
                    'firstname',
                    'lastname',
                    'manager_id'
                ]);

                $select->join(
                    ['evaluations' => DbTables::TBL_USER_EVALUATIONS],
                    new Expression($this->getTable() . '.id = evaluations.user_id AND evaluations.status = ' . Evaluations::USER_EVALUATION_STATUS_PLANNED),
                    [],
                    Select::JOIN_LEFT
                );

                $select->where
                    ->equalTo('disabled', 0)
                    ->equalTo('system', 0)
                    ->equalTo($this->getTable() . '.manager_id', $managerId)
                    ->notEqualTo($this->getTable() . '.period_of_evaluation', 0)
                    ->isNotNull($this->getTable() . '.period_of_evaluation')
                    ->isNull('evaluations.id');
            });

            return $result;
        }

        /**
         * @param $userId
         * @return array|\ArrayObject|null
         */
        public function getUserInfoForGoogleAnalytics($userId)
        {
            $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

            $result = $this->fetchOne(function (Select $select) use($userId) {
                $select->columns([
                    'id'
                ]);

                $select->join(
                    ['cities' => DbTables::TBL_CITIES],
                    $this->getTable() . '.city_id = cities.id',
                    [],
                    Select::JOIN_LEFT
                );
                $select->join(
                    ['details' => DbTables::TBL_LOCATION_DETAILS],
                    'details.id = cities.detail_id',
                    ['working_city' => 'name'],
                    Select::JOIN_LEFT
                );
                $select->join(
                    ['teams' => DbTables::TBL_TEAMS],
                    $this->getTable() . '.department_id = teams.id',
                    ['department' => 'name'],
                    Select::JOIN_LEFT
                );
                $select->where
                    ->equalTo($this->getTable() . '.id', $userId);
            });

            return $result;
        }

        public function getUserFullAddress($userId)
        {
            $prototype = $this->getEntity();
            $this->setEntity(new \ArrayObject());
            $result = $this->fetchOne(function (Select $select) use($userId) {
                $select->columns([
                    'id',
                    'living_city',
                    'address_permanent',
                ]);

                $select->join(
                    ['countries' => DbTables::TBL_COUNTRIES],
                    $this->getTable() . '.country_id = countries.id',
                    [],
                    Select::JOIN_LEFT
                );
                $select->join(
                    ['details_country' => DbTables::TBL_LOCATION_DETAILS],
                    'details_country.id = countries.detail_id',
                    ['country' => 'name'],
                    Select::JOIN_LEFT
                );

                $select->where
                    ->equalTo($this->getTable() . '.id', $userId);
            });

            $this->resultSetPrototype->setArrayObjectPrototype($prototype);
            return $result;
        }

        /**
         * @param int $userId
         * @return \Zend\Db\ResultSet\ResultSet|\ArrayObject[]
         */
        public function getUserTrackingInfo($userId)
        {
            $this->setEntity(new \ArrayObject());

            return $this->fetchOne(function (Select $select) use ($userId) {
                $select->columns(['id']);
                $select->join(
                    ['teams' => DbTables::TBL_TEAMS],
                    $this->getTable() . '.department_id = teams.id',
                    ['department' => 'name'],
                    Select::JOIN_LEFT
                );
                $select->join(
                    ['cities' => DbTables::TBL_CITIES],
                    $this->getTable() . '.city_id = cities.id',
                    [],
                    Select::JOIN_LEFT
                );
                $select->join(
                    ['details' => DbTables::TBL_LOCATION_DETAILS],
                    'cities.detail_id = details.id',
                    ['city' => 'name'],
                    Select::JOIN_LEFT
                );
                $select->where->equalTo($this->getTable() . '.id', $userId);
            });
        }

        /**
         * @param bool $userId
         * @return \Zend\Db\ResultSet\ResultSet
         */
        public function getUserListOrUser($userId = false)
        {
            return $this->fetchAll(function (Select $select) use ($userId) {
                $select
                    ->columns(['id', 'firstname', 'lastname', 'avatar'])
                    ->where(['disabled' => '0', 'system' => '0', 'external' => 0])
                    ->order('firstname ASC');

                if ($userId) {
                    $select->where->equalTo('id', $userId);
                }
            });
        }

        /**
         * @param  string $token
         * @return Object|Array
         */
        public function getUserInfoByToken($token)
        {
            return $this->fetchOne(function (Select $select) use ($token) {
                $select->columns(
                    [
                        'id',
                        'firstname',
                        'lastname',
                        'email',
                        'cityId'    => 'city_id',
                        'countryId' => 'country_id',
                        'avatar'
                    ]
                );

                $select->join(
                    ['token' => DbTables::OAUTH_ACCESS_TOKENS],
                    $this->getTable() . '.email = token.user_id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->where->equalTo('token.access_token', $token);
            });
        }
        /**
         * @param string $searchQuery
         * @return \Zend\Db\ResultSet\ResultSet
         */
        public function searchContacts($searchQuery)
        {
            return $this->fetchAll(function (Select $select) use ($searchQuery) {
                $select->columns(['id', 'firstname', 'lastname', 'position']);
                $select->where->equalTo($this->getTable() . '.disabled', 0)
                    ->where->equalTo($this->getTable() . '.system', 0)
                    ->where->equalTo($this->getTable() . '.external', 0);
                $select->where->
                NEST
                    ->like($this->getTable() . '.firstname', '%' . $searchQuery . '%')
                    ->or
                    ->like($this->getTable() . '.lastname', '%' . $searchQuery . '%')
                    ->UNNEST;
            });
        }

        /**
         * Get users detail for Rest
         *
         * @param bool|false $countryId
         * @param array $columns
         * @return \Zend\Db\ResultSet\ResultSet
         */
        public function getUserByCountry($countryId = false, $columns = [])
        {
            return $this->fetchAll(function (Select $select) use ($countryId, $columns) {
                if (empty($columns)) {
                    $columns = [
                        'id',
                        'firstname',
                        'lastname'
                    ];
                }
                $select->columns($columns);
                $where = new Where();

                $where->equalTo('system', 0);
                $where->equalTo('external', 0);
                $where->equalTo('country_id', $countryId);
                $where->equalTo('disabled', 0);

                $select->where($where);
                $select->order('firstname ASC');
            });
        }
    }
