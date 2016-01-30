<?php
namespace DDD\Dao\Recruitment\Interview;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

/**
 * Class InterviewParticipant
 * @package DDD\Dao\Recruitment\Interview
 *
 * @author Tigran Petrosyan
 */
class InterviewParticipant extends TableGatewayManager
{
    protected $table = DbTables::TBL_HR_INTERVIEW_PARTICIPANTS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Recruitment\Interview\InterviewParticipant');
    }
}
