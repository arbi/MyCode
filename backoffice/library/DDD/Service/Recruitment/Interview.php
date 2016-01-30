<?php

namespace DDD\Service\Recruitment;

use DDD\Service\ServiceBase;

final class Interview extends ServiceBase
{
    private $daoInterview = false;

    public function isInterviewer($interviewerId, $applicantId)
    {
        return $this->getInterviewDao()->isInterviewer($interviewerId, $applicantId);
    }

    public function getInterviewsForApplicant($id)
    {
        return $this->getInterviewDao()->getInterviewsForApplicant($id);
    }

    public function saveInterview($data)
    {
        $this->getInterviewDao();

        if ($data['id']) {
            $this->daoInterview->save($data, ['id' => $data['id']]);
            return $data['id'];
        } else {
            unset($data['id']);
            return $this->daoInterview->save($data);
        }
    }

    public function saveParticipants($participants, $interviewId) {
        $interviewParticipantDao = $this->daoInterview = $this->getServiceLocator()->get('dao_recruitment_interview_interview_participant');
        $interviewParticipantDao->deleteWhere(['interview_id' => $interviewId]);

        if (count($participants)) {
            foreach($participants as $participant) {
                $interviewParticipantDao->save(['interview_id' => $interviewId, 'interviewer_id' => $participant]);
            }
        }

        return true;
    }

    public function getInterviewParticipantsArray($interviewId)
    {
        $interviewParticipantDao = $this->getServiceLocator()->get('dao_recruitment_interview_interview_participant');
        $res = $interviewParticipantDao->fetchAll(['interview_id' => $interviewId],['interviewer_id']);

        $participants = [];

        foreach ($res as $row) {
            array_push($participants, $row->getInterviewerId());
        }

        return $participants;
    }

    public function getInterviewDao()
    {
        if (!($this->daoInterview)) {
            $this->daoInterview = $this->getServiceLocator()->get('dao_recruitment_interview_interview');
        }

        return $this->daoInterview;
    }
}
