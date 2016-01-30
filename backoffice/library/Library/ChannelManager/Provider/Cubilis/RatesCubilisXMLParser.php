<?php

namespace Library\ChannelManager\Provider\Cubilis;

use Library\ChannelManager\Anonymous;

/**
 * Class RatesCubilisXMLParser
 * @package Library\ChannelManager\Provider\Cubilis
 */
class RatesCubilisXMLParser extends CubilisXMLParser {
	/**
	 * Get Hotel Room List
	 * @return \Library\ChannelManager\Provider\Cubilis\PHPDocInterface\HotelRoomList|bool
	 */
	public function getHotelRoomList() {
		return $this->getCollectionElement($this->domDocument->getElementsByTagName('HotelRoomList'), 'getHotelRoomListItem');
	}

	/**
	 * @param $hotelRoom \DomElement
	 * @return Anonymous
	 */
	protected function getHotelRoomListItem($hotelRoom) {
		return new Anonymous([
			'hotelId' => $hotelRoom->hasAttribute('HotelCode') ? $hotelRoom->getAttribute('HotelCode') : null,
			'getRoomStay' => function() use ($hotelRoom) {
				return $this->getCollectionElement($hotelRoom->getElementsByTagName('RoomStay'), 'getRoomStayItem');
			},
		]);
	}

	/**
	 * @param $roomStayItem \DomElement
	 * @return Anonymous
	 */
	protected function getRoomStayItem($roomStayItem) {
		return new Anonymous([
			'getRoomType' => function() use ($roomStayItem) {
				return $this->getCollectionElement($roomStayItem->getElementsByTagName('RoomType'), 'getRoomTypeItem');
			},
			'getRatePlan' => function() use ($roomStayItem) {
				return $this->getCollectionElement($roomStayItem->getElementsByTagName('RatePlan'), 'getRatePlanItem');
			},
		]);
	}

	/**
	 * @param $roomTypeItem \DomElement
	 * @return Anonymous
	 */
	protected function getRoomTypeItem($roomTypeItem) {
		$anonym = new Anonymous([
			'isRoom' => $roomTypeItem->hasAttribute('IsRoom') ? $roomTypeItem->getAttribute('IsRoom') : null,
			'roomId' => $roomTypeItem->hasAttribute('RoomID') ? $roomTypeItem->getAttribute('RoomID') : null,
			'name' => null,
		]);

		// Name
		$roomDescription = $roomTypeItem->getElementsByTagName('RoomDescription');

		if ($roomDescription->length) {
			/** @var $roomDescriptionItem \DomElement */
			$roomDescriptionItem = $roomDescription->item(0);

			$anonym->name = $roomDescriptionItem->hasAttribute('Name') ? $roomDescriptionItem->getAttribute('Name') : null;
		}

		return $anonym;
	}

	/**
	 * @param $ratePlanItem \DomElement
	 * @return Anonymous
	 */
	protected function getRatePlanItem($ratePlanItem) {
		return new Anonymous([
			'id' => $ratePlanItem->hasAttribute('RatePlanID') ? $ratePlanItem->getAttribute('RatePlanID') : null,
			'name' => $ratePlanItem->hasAttribute('RatePlanName') ? $ratePlanItem->getAttribute('RatePlanName') : null,
		]);
	}
}
