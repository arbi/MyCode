<?php

namespace DDD\Service\Queue;

use DDD\Service\ServiceBase;

class EmailQueue extends ServiceBase
{
    const TYPE_APPLICANT_REJECTION = 1;
    const MAX_SEND_COUNT = 5;

    /**
     * @return \ArrayObject
     */
    public function fetch($type = false)
    {
        /**
         * @var \DDD\Dao\Queue\EmailQueue $queueDao
         */
        $queueDao = $this->getServiceLocator()->get('dao_queue_email_queue');

        return $queueDao->fetch($type);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        /**
         * @var \DDD\Dao\Queue\EmailQueue $queueDao
         */
        $queueDao = $this->getServiceLocator()->get('dao_queue_email_queue');
        $queueDao->delete(['id' => $id]);
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function save($data)
    {
        /**
         * @var \DDD\Dao\Queue\EmailQueue $queueDao
         */
        $queueDao = $this->getServiceLocator()->get('dao_queue_email_queue');
        $queueDao->save($data);

        return true;
    }
}