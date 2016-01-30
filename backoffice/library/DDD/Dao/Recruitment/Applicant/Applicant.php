<?php
namespace DDD\Dao\Recruitment\Applicant;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Utility\Debug;
use Library\Constants\Roles;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;

use DDD\Service\Recruitment\Applicant as ApplicantService;

/**
 * Class Applicant
 * @package DDD\Dao\Recruitment\Applicant
 */
class Applicant extends TableGatewayManager
{
    protected $table = DbTables::TBL_HR_APPLICANTS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Recruitment\Applicant\Applicant');
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param int $sortCol
     * @param string $sortDir
     * @param string $like
     * @param string $all
     * @return \DDD\Domain\Recruitment\Applicant\Applicant[]
     */
    public function getApplicantList(
        $offset,
        $limit,
        $sortCol,
        $sortDir,
        $like,
        $all = '1,2,3,4,5,6',
        $hiringTeamId = [],
        $isGlobManager = false,
        $hiringCountryId = false,
        $userId = false,
        $userTeamIds = []
    ) {
        $columns = [
            'status', 'firstname', 'position',
            'job_city', 'date_applied', 'phone', 'email'
        ];

        $status = null;

        if (!empty($all)) {

            if ($all == ApplicantService::APPLICANT_STATUS_ALL) {
                $status = null;
            } else {

                $all = explode(',', $all);

                foreach ($all as $key => $val) {
                    $all[$key] = $this->getTable() . '.status = ' . $val;
                }
                $status = implode(' OR ', $all);
                $status = ' AND (' . $status . ')';
            }
        }

        $likeArray = explode(' ',$like);
        $firstName = trim($likeArray[0]);
        $lastName  = (isset($likeArray[1]) && trim($likeArray[1])) ? trim($likeArray[1]) : $like;

        return $this->fetchAll(
            function (Select $select) use(
                $offset,
                $limit,
                $sortCol,
                $sortDir,
                $like,
                $columns,
                $status,
                $firstName,
                $lastName,
                $hiringTeamId,
                $isGlobManager,
                $hiringCountryId,
                $userId,
                $userTeamIds
            ) {
                $select
                    ->join(
                        ['job' => DbTables::TBL_HR_JOBS],
                        $this->getTable() . '.job_id = job.id',
                        [
                            'position' => 'title',
                            'hiring_team_id'
                        ],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['cities' => DbTables::TBL_CITIES],
                        'job.city_id = cities.id',
                        [],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['details' => DbTables::TBL_LOCATION_DETAILS],
                        'cities.detail_id = details.id',
                        ['job_city' => 'name']
                    );

                $select->where("(" .
                    $this->getTable() . ".firstname like '%" . $firstName . "%'
                    OR " . $this->getTable() . ".lastname like '%" . $lastName . "%'
                    OR " . $this->getTable() . ".lastname like '%" . $firstName . "%'
                    OR " . $this->getTable() . ".firstname like '%" . $lastName . "%'
                    OR " . $this->getTable() . ".email like '%" . $like . "%'
                    OR job.title like '%" . $like . "%'
                    OR details.name like '%" . $like . "%'
                    ) " . $status);

                if (count($hiringTeamId)) {
                    $select->where
                        ->in('job.hiring_team_id', $hiringTeamId);
                }

                if (!$isGlobManager) {
                    $select->join(
                        ['interview' => DbTables::TBL_HR_INTERVIEWS],
                        $this->getTable() . '.id = interview.applicant_id',
                        [],
                        Select::JOIN_LEFT
                    );

                    $select->join(
                        ['interview_participant' => DbTables::TBL_HR_INTERVIEW_PARTICIPANTS],
                        'interview.id = interview_participant.interview_id',
                        [],
                        Select::JOIN_LEFT
                    );

                    if ($hiringCountryId) {
                        $select->where
                            ->nest()
                            ->equalTo('job.country_id', $hiringCountryId)
                            ->unnest();
                    }

                    if (count($userTeamIds)) {
                        $select->where
                            ->nest()
                            ->in('job.hiring_team_id', $userTeamIds)
                            ->unnest();
                    }

                    if ($userId) {
                        $select->where
                            ->nest()
                            ->equalTo('interview_participant.interviewer_id', $userId)
                            ->or
                            ->equalTo('job.hiring_manager_id', $userId)
                            ->unnest();
                    }

                }

                $select->order($columns[$sortCol] . ' ' . $sortDir);

                if ($limit) {
                    $select
                        ->offset((int)$offset)
                        ->limit((int)$limit);
                }

                $select->group($this->getTable() . '.id');
            }
        );
    }

    public function getApplicantCount(
        $like,
        $all             = '1,2,3,4,5',
        $isGlobManager   = false,
        $hiringCountryId = false,
        $userId          = false,
        $userTeamIds     = []
    ) {

        $column = ['id'];

        $status = null;

        if (!empty($all)) {
            if ($all == 9) {
                $all = '1,2,3,4,5,6,7,8';
            }
            $all = explode(',', $all);

            foreach ($all as $key => $val) {
                $all[$key] = $this->getTable() . '.status = ' . $val;
            }
            $status = implode(' OR ', $all);
            $status = ' AND (' . $status . ')';
        }

        $likeArray = explode(' ',$like);
        $firstName = trim($likeArray[0]);
        $lastName  = (isset($likeArray[1]) && trim($likeArray[1])) ? trim($likeArray[1]) : $like;

        $result =  $this->fetchAll(
            function (Select $select) use(
                $like,
                $column,
                $status,
                $firstName,
                $lastName,
                $isGlobManager,
                $hiringCountryId,
                $userId,
                $userTeamIds
            ){
                $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));

                $select
                    ->join(
                        ['j' => DbTables::TBL_HR_JOBS],
                        $this->getTable() . '.job_id = j.id',
                        ['position' => 'title'],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['cities' => DbTables::TBL_CITIES],
                        'j.city_id = cities.id',
                        [],
                        Select::JOIN_LEFT
                    )
                    ->join(
                        ['details' => DbTables::TBL_LOCATION_DETAILS],
                        'cities.detail_id = details.id',
                        ['job_city' => 'name']
                    );

                $select->where("(" .
                    $this->getTable() . ".firstname like '%".$firstName."%'
                    OR " . $this->getTable() . ".lastname like '%".$lastName."%'
                    OR " . $this->getTable() . ".lastname like '%".$firstName."%'
                    OR " . $this->getTable() . ".firstname like '%".$lastName."%'
                    OR " . $this->getTable() . ".email like '%".$like."%'
                    OR j.title like '%".$like."%'
                    OR details.name like '%".$like."%'
                    ) " . $status
                );

                if (!$isGlobManager) {
                    $select->join(
                        ['interview' => DbTables::TBL_HR_INTERVIEWS],
                        $this->getTable() . '.id = interview.applicant_id',
                        [],
                        Select::JOIN_LEFT
                    );

                    $select->join(
                        ['interview_participant' => DbTables::TBL_HR_INTERVIEW_PARTICIPANTS],
                        'interview.id = interview_participant.interview_id',
                        [],
                        Select::JOIN_LEFT
                    );

                    if ($hiringCountryId) {
                        $select->where
                            ->nest()
                            ->equalTo('j.country_id', $hiringCountryId)
                            ->unnest();
                    }

                    if (count($userTeamIds)) {
                        $select->where
                            ->nest()
                            ->in('j.hiring_team_id', $userTeamIds)
                            ->unnest();
                    }
                    if ($userId) {
                        $select->where
                            ->nest()
                            ->equalTo('interview_participant.interviewer_id', $userId)
                            ->or
                            ->equalTo('j.hiring_manager_id', $userId)
                            ->unnest();
                    }

                }

                $select->columns($column);

                $select->group($this->getTable() . '.id');
            }
        );

        $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $result2   = $statement->execute();
        $row       = $result2->current();
        $total     = $row['total'];

        return $row['total'];
    }

    /**
     * @param int $id
     * @return \DDD\Domain\Recruitment\Applicant\Applicant
     */
    public function getApplicantById($id)
    {
        return $this->fetchOne(function (Select $select) use($id) {
            $select
                ->join(
                    ['job' => DbTables::TBL_HR_JOBS],
                    $this->getTable() . '.job_id = job.id',
                    [
                        'position' => 'title',
                        'hiring_team_id'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['c' => DbTables::TBL_CITIES],
                    'job.city_id = c.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['ld' => DbTables::TBL_LOCATION_DETAILS],
                    'c.detail_id = ld.id',
                    ['job_city' => 'name'],
                    Select::JOIN_LEFT
                )
                ->where([$this->getTable() . '.id' => $id]);
        });
    }

    public function getApplicantByEmail($email, $id)
    {
        return $this->fetchAll(function (Select $select) use($email, $id) {
            $select->columns(['id', 'date_applied']);
            $select
                ->join(
                    ['j' => DbTables::TBL_HR_JOBS],
                    $this->getTable() . '.job_id = j.id',
                    ['position' => 'title'],
                    Select::JOIN_LEFT
                );
            $select->where->notEqualTo($this->getTable() . '.id', $id)
                       ->equalTo($this->getTable() . '.email', $email)
            ;
            $select->order($this->getTable() . '.date_applied DESC');
        });
    }
}
