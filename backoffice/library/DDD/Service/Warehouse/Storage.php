<?php

namespace DDD\Service\Warehouse;

use DDD\Service\ServiceBase;
use Library\Constants\TextConstants;

class Storage extends ServiceBase
{
    const STORAGE_STATUS_ACTIVE = 0;
    const STORAGE_STATUS_INACTIVE = 1;

    /**
     * @param $offset
     * @param $limit
     * @param $sortCol
     * @param $sortDir
     * @param $like
     * @param int $all
     * @return array
     */
    public function getDatatableData($offset, $limit, $sortCol, $sortDir, $like, $all = 1)
    {
        /**
         * @var \DDD\Dao\Warehouse\Storage $storageDao
         */
        $storageDao = $this->getServiceLocator()->get('dao_warehouse_storage');
        $result = $storageDao->getAllStorage($offset, $limit, $sortCol, $sortDir, $like, $all);
        $data = [];
        $storageList = $result['result'];
        $total = $result['total'];
        if ($storageList->count()) {
            foreach ($storageList as $storage) {
                array_push($data, [
                    ($storage['inactive'] == self::STORAGE_STATUS_ACTIVE)
                        ? '<span class="label label-success">Active</span>'
                        : '<span class="label label-danger">Inactive</span>',
                    $storage['name'],
                    $storage['city_name'],
                    $storage['address'],
                    '<a class="btn btn-xs btn-primary" href="/warehouse/storage/edit/' . $storage['id'] . '" data-html-content="Edit"></a>'
                ]);
            }
        }

        return [
            'data' => $data,
            'total' => $total,
        ];

    }

    /**
     * @param $storageId
     * @return array|\ArrayObject
     */
    public function getStorageData($storageId)
    {
        /**
         * @var \DDD\Dao\Warehouse\Storage $storageDao
         */
        $storageDao = $this->getServiceLocator()->get('dao_warehouse_storage');

        $storageData = $storageDao->getStorageData($storageId);
        return $storageData ? $storageData : [];
    }

    /**
     * @param $storageData
     * @param $storageId
     * @return int
     */
    public function saveStorage($storageData, $storageId)
    {
        /**
         * @var \DDD\Dao\Warehouse\Storage $storageDao
         */
        $storageDao = $this->getServiceLocator()->get('dao_warehouse_storage');
        $storageName = $storageData['name'];
        $params = [
            'name'    => $storageData['name'],
            'city_id' => $storageData['city'],
            'address' => $storageData['address']
        ];

        if ($storageId) {
            $storageDao->save($params, ['id'=>$storageId]);
        } else {
            /**
             * @var $teamService \DDD\Service\Team\Team
             * @var \DDD\Service\Task $taskService
             */

            // add team
            $teamService = $this->getServiceLocator()->get('service_team_team');
            $teamId = $teamService->createTeamFromStorage($storageName);

            // add team id
            $params['team_id'] = $teamId;
            $storageId = $storageDao->save($params);

            // add task
            $taskService = $this->getServiceLocator()->get('service_task');
            $taskService->createStorageCreatedTask($storageName);

        }

		return $storageId;
	}

    /**
     * @param $storageId
     * @param $status
     * @return int
     * @throws \Exception
     */
    public function changeStatus($storageId, $status)
    {
        /**
         * @var \DDD\Dao\Warehouse\Storage $storageDao
         * @var \DDD\Dao\Team\Team $teamDao
         */
        $storageDao = $this->getServiceLocator()->get('dao_warehouse_storage');
        $teamDao = $this->getServiceLocator()->get('dao_team_team');
        $storageData = $storageDao->getTeamId($storageId);

        if (!$storageData) {
            throw new \Exception('No item');
        }

        // change team status
        if ($status) {
            $teamDao->save(['is_disable' => 1], ['id' => $storageData['team_id']]);
        } else {
            $teamDao->save(['is_disable' => 0], ['id' => $storageData['team_id']]);
        }

        return $storageDao->save(['inactive' => $status], ['id'=>$storageId]);
    }

    /**
     * @param $storageId
     * @return array
     */
    public function getAllThresholdForStorage($storageId)
    {
        /**
         * @var \DDD\Dao\Warehouse\Threshold $daoThreshold
         */
        $daoThreshold = $this->getServiceLocator()->get('dao_warehouse_threshold');
        $thresholdList = $daoThreshold->getAllThresholdForStorage($storageId);
        $data = [];

        if ($thresholdList->count()) {
            foreach ($thresholdList as $threshold) {
                array_push($data, [
                    $threshold['name'],
                    $threshold['threshold'],
                    '<a href="javascript:void(0)" data-url="/warehouse/storage/delete-threshold/' . $storageId . '/' . $threshold['id'] . '" data-loading-text="Deleting..." class="btn btn-xs btn-danger deleteThreshold" data-toggle="modal">Delete</a>'
                ]);
            }
        }

        return $data;
    }

    /**
     * @param $categoryId
     * @param $threshold
     * @param $storageId
     * @return int
     */
    public function saveThreshold($categoryId, $threshold, $storageId)
    {
        /**
         * @var \DDD\Dao\Warehouse\Threshold $daoThreshold
         */
        $daoThreshold = $this->getServiceLocator()->get('dao_warehouse_threshold');
        $id = $daoThreshold->save([
            'asset_category_id' => $categoryId,
            'threshold' => $threshold,
            'storage_id' => $storageId
        ]);

        return $id;
    }

    /**
     * @param $thresholdId
     * @return int
     */
    public function deleteThreshold($thresholdId)
    {
        /**
         * @var \DDD\Dao\Warehouse\Threshold $daoThreshold
         */
        $daoThreshold = $this->getServiceLocator()->get('dao_warehouse_threshold');

        $daoThreshold->delete([
            'id' => $thresholdId
        ]);
    }

    public function searchStorageByName($name, $onlyActive = true)
    {
        $storageDao     = $this->getServiceLocator()->get('dao_warehouse_storage');
        $storageList    = $storageDao->searchStorageByName($name, $onlyActive);
        return $storageList;

    }

}
