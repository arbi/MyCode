<?php
namespace DDD\Dao\Recruitment\Applicant;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

/**
 * Class ApplicantComment
 * @package DDD\Dao\Recruitment\Applicant
 */
class ApplicantComment extends TableGatewayManager
{
    protected $table = DbTables::TBL_HR_APPLICANT_COMMENTS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Recruitment\Applicant\ApplicantComment');
    }

    /**
     * @param $applicantId
     * @param null $loggedInUserId
     * @param bool $hrOnlyCommentsIncluded
     * @return \DDD\Domain\Recruitment\Applicant\ApplicantComment[]
     */
    public function getApplicantCommentsById($applicantId, $loggedInUserId = null, $hrOnlyCommentsIncluded = false)
    {
        $return = $this->fetchAll(
            function (Select $select) use($applicantId, $loggedInUserId, $hrOnlyCommentsIncluded) {

                $select->where->equalTo($this->getTable() . '.applicant_id', $applicantId);

                if ($loggedInUserId) {
                    $select->where->equalTo($this->getTable() . '.commenter_id', $loggedInUserId);
                }

                if (!$hrOnlyCommentsIncluded) {
                    $select->where->equalTo($this->getTable() . '.hr_only_comment', 0);
                }

//                $select->columns([
//                    'hr_only_comment' => 'hr_only_comment'
//                ]);

                $select->join(
                    ['user' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.commenter_id = user.id',
                    [
                        'commenter_first_name' => 'firstname',
                        'commenter_last_name' => 'lastname'
                    ]
                );

                $select->order('date asc');
            }
        );

        return $return;
    }
}
