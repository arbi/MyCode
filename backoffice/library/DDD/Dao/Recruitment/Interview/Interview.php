<?php
namespace DDD\Dao\Recruitment\Interview;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

/**
 * Class Interview
 * @package DDD\Dao\Recruitment\Interview
 *
 * @author Tigran Petrosyan
 */
class Interview extends TableGatewayManager
{
    protected $table = DbTables::TBL_HR_INTERVIEWS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Recruitment\Interview\Interview');
    }

    public function getInterviewsForApplicant($applicantId)
    {
        return $this->fetchAll(function (Select $select) use($applicantId) {
            $select
                ->join(
                    ['participants' => DbTables::TBL_HR_INTERVIEW_PARTICIPANTS],
                    $this->getTable() . '.id = participants.interview_id',
                    ['interviewer_id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    'participants.interviewer_id = users.id',
                    ['interviewer_first_name' => 'firstname', 'interviewer_last_name' => 'lastname'],
                    Select::JOIN_LEFT
                )
                ->where([$this->getTable() . '.applicant_id' => $applicantId]);
        });
    }

    public function isInterviewer($interviewerId, $applicantId)
    {
        $result = $this->fetchOne(function (Select $select) use($applicantId, $interviewerId) {
            $select
                ->join(
                    ['participants' => DbTables::TBL_HR_INTERVIEW_PARTICIPANTS],
                    $this->getTable() . '.id = participants.interview_id',
                    []
                )
                ->where([$this->getTable() . '.applicant_id' => $applicantId])
                ->where(['participants.interviewer_id' => $interviewerId]);
        });
        if($result) {
            return true;
        } else {
            return false;
        }
    }
}
