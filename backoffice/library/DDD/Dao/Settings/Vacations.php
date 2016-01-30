<?php

namespace DDD\Dao\Settings;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class Vacations extends TableGatewayManager
{
    protected $table = DbTables::TBL_SETTINGS;

    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    public function getCronLastRunDate()
    {
        return $this->fetchOne(function (Select $select) {
            $select->columns([
                'id',
                'vacation_last_run_date' => 'cron_vacation_last_run_date',
            ]);

            $select->order('id DESC');

            $select->limit(1);
        });
    }

    public function updateCronLastRunDate($date)
    {
        if ($this->validateDate($date)) {

            $cronVacationsLastRunDate = $this->getCronLastRunDate();

            $where = new Where();
            $where->equalTo('id', $cronVacationsLastRunDate['id']);

            return $this->save(
                ['cron_vacation_last_run_date' => $date],
                $where
            );
        }

        return FALSE;
    }

    public function validateDate($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') == $date;
    }

    /**
     *
     * @return boolean|string
     */
    public function getDiffDaysBeforeToday()
    {
        $currentLastRun = $this->getCronLastRunDate();

        if (!$currentLastRun) {
            return FALSE;
        } else {
            $currentLastRun = new \DateTime($currentLastRun['vacation_last_run_date']);
        }

        $today = new \DateTime(date('Y-m-d'));

        $dateDiff = $currentLastRun->diff($today);

        return $dateDiff->format('%r%a');
    }
}
