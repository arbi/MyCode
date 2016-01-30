<?php

namespace DDD\Service\Apartel;

use DDD\Service\ServiceBase;
use Library\Constants\TextConstants;

class Connection extends ServiceBase
{
    /**
     * @param $apartelId
     * @return array
     */
    public function getCubilisConnectionDetails($apartelId)
    {
        /**
         * @var \DDD\Dao\Apartel\General $generalDao
         */
        $generalDao = $this->getServiceLocator()->get('dao_apartel_general');
        $data = $generalDao->getCubilisConnectionDetails($apartelId);
        return $data ? : [];
    }

    /**
     * @param $apartelId
     * @param $params
     * @return array
     */
    public function saveCubilisConnection($apartelId, $params)
    {

        if (!$apartelId || !$params['cubilis_id'] || !$params['cubilis_username'] || !$params['cubilis_password']) {
            return [
                'status' => 'error',
                'msg' => TextConstants::SERVER_ERROR,
            ];
        }

        /**
         * @var \DDD\Dao\Apartel\General $generalDao
         */
        $generalDao = $this->getServiceLocator()->get('dao_apartel_general');

        $duplicateApartelCubilisInfo = $generalDao->checkDuplicateCubilisInfo(
            $apartelId,
            $params['cubilis_id'],
            $params['cubilis_username'],
            $params['cubilis_password']
        );

        $apartelNames = [];
        if ($duplicateApartelCubilisInfo->count()) {
            foreach ($duplicateApartelCubilisInfo as $row) {
                array_push($apartelNames, $row['slug']);
            }
            $apartelNames = implode(',', $apartelNames);

            $text = sprintf(TextConstants::DUPLICATE_CUBILIS_CONNECTION, $apartelNames);
            return [
                'status' => 'error',
                'msg'    => $text,
            ];
        }

        $generalDao->update([
            'cubilis_id' => $params['cubilis_id'],
            'cubilis_username' => $params['cubilis_username'] ,
            'cubilis_password' => $params['cubilis_password'],
        ], ['id' => $apartelId]);

        if ($params['prepare']) {
            $generalDao->update([
                'sync_cubilis' => 1,
            ], ['id' => $apartelId]);
        }

        if ($params['rollback']) {
            $generalDao->update([
                'sync_cubilis' => 0,
            ], ['id' => $apartelId]);
        }

        return [
            'status' => 'success',
            'msg' => TextConstants::SUCCESS_UPDATE_CUBILIS_DATA,
        ];
    }

    /**
     * @param $apartelId
     * @param $params
     * @return array
     */
    public function connectCubilis($apartelId, $params)
    {

        if (!$apartelId || !$params['cubilis_id'] || !$params['cubilis_username'] || !$params['cubilis_password']) {
            return [
                'status' => 'error',
                'msg' => TextConstants::SERVER_ERROR,
            ];
        }
        /**
         * @var \DDD\Dao\Apartel\General $generalDao
         * @var \DDD\Dao\Apartel\Type $typeDao
         * @var \DDD\Dao\Apartel\Rate $rateDao
         */
        $connect = (int)$params['connect'];
        $generalDao = $this->getServiceLocator()->get('dao_apartel_general');
        $generalDao->update([
            'sync_cubilis' => (int)$params['connect'],
            'cubilis_id' => $params['cubilis_id'],
            'cubilis_username' => $params['cubilis_username'] ,
            'cubilis_password' => $params['cubilis_password'],
        ], ['id' => $apartelId]);

        // disconnect
        if (!$connect) {

            $typeDao = $this->getServiceLocator()->get('dao_apartel_type');
            $rateDao = $this->getServiceLocator()->get('dao_apartel_rate');

            // set null all old cubilis type id
            $typeDao->update(['cubilis_id' => Null], ['apartel_id' => $apartelId]);

            // set null all old cubilis rate id
            $rateDao->update(['cubilis_id' => Null], ['apartel_id' => $apartelId]);
        }

        $successMessage = $connect ? TextConstants::SUCCESS_CONNECTED_TO_CUBILIS :  TextConstants::SUCCESS_DISCONNECTED_FROM_CUBILIS;
        return [
            'status' => 'success',
            'msg' => $successMessage,
        ];
    }
}
