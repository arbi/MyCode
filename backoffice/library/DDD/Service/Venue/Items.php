<?php

namespace DDD\Service\Venue;

use DDD\Service\ServiceBase;

class Items extends ServiceBase
{
    const STATUS_AVAILABLE      = 1;
    const STATUS_NOT_AVAILABLE  = 2;

    static public function getStatuses()
    {
        return [
            self::STATUS_AVAILABLE      => 'Available',
            self::STATUS_NOT_AVAILABLE  => 'Not Available'
        ];
    }

    public function saveItems($venueId, $itemsData)
    {
        /**
         * @var \DDD\Dao\Venue\Items $itemsDao
         */
        $itemsDao = $this->getServiceLocator()->get('dao_venue_items');

        try {
            $itemsDao->beginTransaction();

            $saveData = [];

            if (!empty($itemsData['titles'])) {
                foreach ($itemsData['titles'] as $key => $itemTitle) {
                    $title = trim($itemTitle);

                    if (empty($title)) {
                        continue;
                    }

                    array_push($saveData, [
                        'title'         => $title,
                        'description'   => $itemsData['descriptions'][$key],
                        'price'         => $itemsData['prices'][$key],
                        'is_available'  => $itemsData['availabilities'][$key],
                        'venue_id'      => $venueId
                    ]);
                }
            }

            $itemsDao->delete(['venue_id' => $venueId]);

            $result = true;
            if (!empty($saveData)) {
                $result = $itemsDao->multiInsert($saveData);
            }

            $itemsDao->commitTransaction();

            return $result;

        } catch (\Exception $e) {
            $this->gr2logException($e);
            $itemsDao->rollbackTransaction();
        }

        return false;
    }

    /**
     * @param $venueId
     * @return array
     */
    public function getItemsByVenueId($venueId)
    {
        /**
         * @var \DDD\Dao\Venue\Items $itemsDao
         */
        $itemsDao = $this->getServiceLocator()->get('dao_venue_items');
        $itemsData = $itemsDao->getItemsByVenueId($venueId, true);
        $itemArray = [];
        foreach ($itemsData as $item) {
            array_push($itemArray,[
               'title' =>  $item->getTitle(),
               'id' =>  $item->getId(),
               'description' =>  $item->getDescription(),
               'price' =>  $item->getPrice()
            ]);
        }
        return $itemArray;
    }

}