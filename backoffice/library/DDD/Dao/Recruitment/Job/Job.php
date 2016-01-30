<?php
namespace DDD\Dao\Recruitment\Job;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use DDD\Service\Recruitment\Job as JobService;

use Zend\Db\Sql\Select;


/**
 * Class Job
 * @package DDD\Dao\Recruitment\Job
 */
class Job extends TableGatewayManager
{
    protected $table = DbTables::TBL_HR_JOBS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Recruitment\Job\Job');
    }

    public function getJobList(
        $offset,
        $limit,
        $sortCol,
        $sortDir,
        $like,
        $all = 2
    ) {
        $columns = [
            'status', 'title', 'department_id', 'city', 'start_date',
            'description', 'requirements', 'hiring_manager_id','published',
            'id', 'cv_required', 'notify_manager'
        ];

        $status = null;

        if ($all == '1') {
            $status = ' AND status = 1';
        } elseif ($all == '2') {
            $status = ' AND status = 2';
        } elseif ($all == '3') {
            $status = ' AND status = 3';
        }

        return $this->fetchAll(
            function (Select $select) use(
                $offset,
                $limit,
                $sortCol,
                $sortDir,
                $like,
                $columns,
                $status
            ){
                $select
                    ->join(
                        ['t' => DbTables::TBL_TEAMS],
                        $this->getTable() . '.department_id = t.id',
                        ['department' => 'name'],
                        Select::JOIN_LEFT
                    )->join(
                        ['c' => DbTables::TBL_CITIES],
                        $this->getTable() . '.city_id = c.id',
                        [],
                        Select::JOIN_LEFT
                    )->join(
                        ['d' => DbTables::TBL_LOCATION_DETAILS],
                        'c.detail_id = d.id',
                        ['city' => 'name'],
                        Select::JOIN_LEFT
                );
                $select->where("(" . $this->getTable() . ".title like '%".$like."%'
                    OR " . $this->getTable() . ".description like '%".$like."%')
                    $status
                ");
                $sorColumn = ($sortCol == 2) ? 't.name' : $columns[$sortCol];
                $select
                    ->order($sorColumn.' '.$sortDir)
                    ->offset((int)$offset)
                    ->limit((int)$limit);
            }
        );
    }

    public function getJobCount($like, $all = 1) {

        $column = ['id'];

        $status = null;

        if ($all == '1') {
            $status = ' AND status = 1';
        } elseif ($all == '2') {
            $status = ' AND status = 2';
        } elseif ($all == '3') {
            $status = ' AND status = 3';
        }

        $results =  $this->fetchAll(
            function (Select $select) use(
                $like,
                $column,
                $status
            ){

                $select->where("(title like '%" . $like.
                    "%' OR description like '%" . $like . "%')
                    $status"
                );
                $select->columns($column);
            }
        );

        $jobCount = [];
        foreach ($results as $result){
            $jobCount[] = $result->getId();
        }
        $count = count($jobCount);
        return $count;
    }

    public function getJobsForWebsite()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(
            function (Select $select) {
                $select->join(
                    ['cities' => DbTables::TBL_CITIES],
                    'cities.id = ' . $this->getTable() . '.city_id',
                    ['detail_id']
                );
                $select->join(
                    ['provinces' => DbTables::TBL_PROVINCES],
                    'cities.province_id = provinces.id',
                    ['country_id']
                );
                $select->join(
                    ['details' => DbTables::TBL_LOCATION_DETAILS],
                    'cities.detail_id = details.id',
                    ['city_img' => 'thumbnail']
                );
                $select
                    ->columns(['id', 'slug', 'title', 'subtitle', 'city_id', 'start_date'])
                    ->where($this->getTable() . '.status = ' . JobService::LIVE_STATUS)
                    ->order($this->getTable() . '.city_id ASC AND ' . $this->getTable() . '.id DESC');
            }
        );
    }
}
