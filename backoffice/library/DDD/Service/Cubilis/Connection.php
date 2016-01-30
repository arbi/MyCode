<?php

namespace DDD\Service\Cubilis;

use DDD\Service\ServiceBase;
use Library\ChannelManager\CivilResponder;
use Library\ChannelManager\Provider\Cubilis\RatesCubilisXMLParser;
use Library\ChannelManager\ChannelManager as Chm;

class Connection extends ServiceBase
{

    /**
     * @param $apartmentId
     * @param $rates
     * @param bool $isApartel
     * @return array
     */
    public function getCubilisTypes($apartmentId, $rates, $isApartel = false)
    {
		$output = [];

		/** @var $chm \Library\ChannelManager\ChannelManager */
		$chm = $this->getServiceLocator()->get('ChannelManager');
        $chm->setProductType($isApartel ? Chm::PRODUCT_APARTEL : Chm::PRODUCT_APARTMENT);
		$result = $chm->syncWithCubilis([
			'apartment_id' => $apartmentId,
		]);
		if ($result->getStatus() == CivilResponder::STATUS_SUCCESS) {
			/** @var $xmlData RatesCubilisXMLParser */
			$xmlData = $result->getData();
			$hotels = $xmlData->getHotelRoomList();

			if ($hotels->getLength()) {
				foreach ($hotels->getItems() as $hotel) {
					$rooms = $hotel->getRoomStay();

					if ($rooms->getLength()) {
                        foreach ($rooms->getItems() as $roomStayItem) {
                            $ratePlans = $roomStayItem->getRatePlan();
                            $roomType = $roomStayItem->getRoomType();

                            if ($roomType->getLength()) {
                                $roomId = $roomType->getItems()[0]->roomId;
                                $roomName = $roomType->getItems()[0]->name;

                                if ($ratePlans->getLength()) {
                                    foreach ($ratePlans->getItems() as $ratePlan) {
                                        if ($isApartel) {
                                            $output[$roomId] = [
                                                'type_id' => $roomId,
                                                'type_name' => $roomName,
                                                'rate_list' => (isset($output[$roomId]['rate_list']) ? $output[$roomId]['rate_list'] : []),
                                            ];

                                            $output[$roomId]['rate_list'][$ratePlan->id] = [
                                                'rate_id' => $ratePlan->id,
                                                'rate_name' => $ratePlan->name,
                                            ];
                                        } else {
                                            $output[$ratePlan->id] = [
                                                'name' => $ratePlan->name,
                                                'room_id' => $roomId,
                                                'status' => (isset($rates['cubilis_rate'][$ratePlan->id]) ? 'connected' : 'not-connected'),
                                            ];
                                        }
                                    }
                                }
                            }

                            if (!$isApartel) {
                                break;
                            }
                        }
					}
				}
			}
		}

		return $output;
	}

}
