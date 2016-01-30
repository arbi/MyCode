<?php

namespace Library\ChannelManager\Provider\Cubilis;

use Library\ChannelManager\Anonymous;
use Library\ChannelManager\Provider\Cubilis\PHPDocInterface\Reservation;

/**
 * Class ReservationCubilisXMLParser
 * @package Library\ChannelManager\Provider\Cubilis
 * @problems
 *      fixme: for RoomStay element, attribute IndexNumber (index) always returns null
 *      fixme: add BasicPropertyInfo element after RoomStay
 *      fixme: add new elements and attributes from doc v2.02
 */
class ReservationCubilisXMLParser extends CubilisXMLParser {
	/**
	 * Get Reservations List
	 * @return Reservation|bool
	 */
	public function getReservationList() {
		return $this->getCollectionElement($this->domDocument->getElementsByTagName('HotelReservation'), 'getReservationListItem');
	}

	/**
	 * @param $reservationListItem \DomElement
	 * @return Anonymous
	 */
	protected function getReservationListItem($reservationListItem) {
		$anonym = new Anonymous([
			'creationDatetime' => $reservationListItem->hasAttribute('CreateDateTime') ? $reservationListItem->getAttribute('CreateDateTime') : null,
			'creatorId' => $reservationListItem->hasAttribute('CreatorID') ? $reservationListItem->getAttribute('CreatorID') : null,
			'status' => $reservationListItem->hasAttribute('ResStatus') ? $reservationListItem->getAttribute('ResStatus') : null,
			'customerIP' => null,
			'getUniqueId' => function() use ($reservationListItem) {
				return $this->getCollectionElement($reservationListItem->getElementsByTagName('UniqueID'), 'getUniqueIdItem');
			},
			'getRoomStay' => function() use ($reservationListItem) {
				return $this->getCollectionElement($reservationListItem->getElementsByTagName('RoomStay'), 'getRoomStayItem');
			},
			'getInfo' => function() use ($reservationListItem) {
				return $this->getInfo($reservationListItem->getElementsByTagName('ResGlobalInfo'));
			},
		]);

		/**
		 * Source
		 * @var $sourceItem \DomElement
		 */
		$source = $reservationListItem->getElementsByTagName('Source');

		if ($source->length) {
			$sourceItem = $source->item(0);

			$anonym->customerIP = $sourceItem->hasAttribute('TerminalID') ? $sourceItem->getAttribute('TerminalID') : null;
		}

		return $anonym;
	}

	/**
	 * @param $uniqueIdItem \DomElement
	 * @return Anonymous
	 */
	protected function getUniqueIdItem($uniqueIdItem) {
		$anonym = new Anonymous([
			'id' => $uniqueIdItem->hasAttribute('ID') ? $uniqueIdItem->getAttribute('ID') : null,
			'type' => $uniqueIdItem->hasAttribute('Type') ? $uniqueIdItem->getAttribute('Type') : null,
			'companyName' => null,
		]);

		if ($anonym->type == 'PAR') {
			$uniqueIdItemCompanyName = $uniqueIdItem->getElementsByTagName('CompanyName');

			if ($uniqueIdItemCompanyName->length) {
				$anonym->companyName = $uniqueIdItemCompanyName->item(0)->nodeValue;
			}
		}

		return $anonym;
	}

	/**
	 * @param $roomStayItem \DomElement
	 * @return Anonymous
	 */
	protected function getRoomStayItem($roomStayItem) {
		$anonym = new Anonymous([
			'index' => $roomStayItem->hasAttribute('IndexNumber') ? $roomStayItem->getAttribute('IndexNumber') : null,
			'status' => $roomStayItem->hasAttribute('RoomStayStatus') ? $roomStayItem->getAttribute('RoomStayStatus') : null,
			'totalAmountAfterTax' => null,
			'currency' => null,
			'getRoomType' => function() use ($roomStayItem) {
				return $this->getCollectionElement($roomStayItem->getElementsByTagName('RoomType'), 'getRoomTypeItem');
			},
			'getRatePlan' => function() use ($roomStayItem) {
				return $this->getCollectionElement($roomStayItem->getElementsByTagName('RatePlan'), 'getRatePlanItem');
			},
			'getGuestCount' => function() use ($roomStayItem) {
				return $this->getCollectionElement($roomStayItem->getElementsByTagName('GuestCount'), 'getGuestCountItem');
			},
			'getComment' => function() use ($roomStayItem) {
				return $this->getCollectionElement($roomStayItem->getElementsByTagName('Comment'), 'getCommentItem');
			},
		]);

		/**
		 * Total
		 * @var $totalItem \DomElement
		 */
		$total = $roomStayItem->getElementsByTagName('Total');

		if ($total->length) {
			$totalItem = $total->item(0);

			$anonym->totalAmountAfterTax = $totalItem->hasAttribute('AmountAfterTax') ? $totalItem->getAttribute('AmountAfterTax') : null;
			$anonym->currency = $totalItem->hasAttribute('CurrencyCode') ? $totalItem->getAttribute('CurrencyCode') : null;
		}

		return $anonym;
	}

	/**
	 * @param $roomTypeItem \DomElement
	 * @return Anonymous
	 */
	protected function getRoomTypeItem($roomTypeItem) {
		$anonym = new Anonymous([
			'isRoom' => $roomTypeItem->hasAttribute('IsRoom') ? ($roomTypeItem->getAttribute('IsRoom') == 'true' ? true : false) : null,
			'roomId' => $roomTypeItem->hasAttribute('RoomID') ? $roomTypeItem->getAttribute('RoomID') : null,
			'configuration' => $roomTypeItem->hasAttribute('Configuration') ? $roomTypeItem->getAttribute('Configuration') : null,
			'promotionCode' => $roomTypeItem->hasAttribute('PromotionCode') ? $roomTypeItem->getAttribute('PromotionCode') : null,
			'name' => null,
			'getDetail' => function() use ($roomTypeItem) {
				return $this->getCollectionElement($roomTypeItem->getElementsByTagName('AdditionalDetail'), 'getRoomTypeDetailItem');
			},
		]);

		/**
		 * Name
		 * @var $nameItem \DomElement
		 */
		$name = $roomTypeItem->getElementsByTagName('RoomDescription');

		if ($name->length) {
			$nameItem = $name->item(0);

			$anonym->name = $nameItem->hasAttribute('Name') ? $nameItem->getAttribute('Name') : null;
		}

		return $anonym;
	}

	/**
	 * @param $detailItem \DomElement
	 * @return Anonymous
	 */
	protected function getRoomTypeDetailItem($detailItem) {
		$anonym = new Anonymous([
			'text' => null,
		]);

		// Text
		$text = $detailItem->getElementsByTagName('Text');

		if ($text->length) {
			$textItem = $text->item(0);

			$anonym->text = $textItem->nodeValue;
		}

		return $anonym;
	}

	/**
	 * @param $ratePlanItem \DomElement
	 * @return Anonymous
	 */
	protected function getRatePlanItem($ratePlanItem) {
		$anonym = new Anonymous([
			'effectiveDate' => $ratePlanItem->hasAttribute('EffectiveDate') ? $ratePlanItem->getAttribute('EffectiveDate') : null,
			'id' => $ratePlanItem->hasAttribute('RatePlanID') ? $ratePlanItem->getAttribute('RatePlanID') : null,
			'name' => $ratePlanItem->hasAttribute('RatePlanName') ? $ratePlanItem->getAttribute('RatePlanName') : null,
			'amount' => null,
			'currency' => null,
			'isTaxInclusive' => null,
			'getDetail' => function() use ($ratePlanItem) {
				return $this->getCollectionElement($ratePlanItem->getElementsByTagName('AdditionalDetail'), 'getRateDetailItem');
			},
		]);

		/**
		 * isTaxInclusive
		 * @var $nameItem \DomElement
		 */
		$isTaxInclusive = $ratePlanItem->getElementsByTagName('RatePlanInclusions');
		if ($isTaxInclusive->length) {
			$anonym->isTaxInclusive = $isTaxInclusive->item(0)->hasAttribute('TaxInclusive') ? ($isTaxInclusive->item(0)->getAttribute('TaxInclusive') == 'true' ? true : false) : null;
		}

        /**
         * amount
         */
        $amount = $ratePlanItem->getElementsByTagName('AdditionalDetail');

        if ($amount->length) {
            $anonym->amount = $amount->item(0)->hasAttribute('Amount') ? $amount->item(0)->getAttribute('Amount') : null;
            $anonym->currency = $amount->item(0)->hasAttribute('CurrencyCode') ? $amount->item(0)->getAttribute('CurrencyCode') : null;
        }

		return $anonym;
	}

	/**
	 * @param $ratePlanItem \DomElement
	 * @return Anonymous
	 */
	protected function getRateDetailItem($ratePlanItem) {
		return new Anonymous([
			'amount' => $ratePlanItem->hasAttribute('Amount') ? $ratePlanItem->getAttribute('Amount') : null,
			'currency' => $ratePlanItem->hasAttribute('CurrencyCode') ? $ratePlanItem->getAttribute('CurrencyCode') : null,
		]);
	}

	/**
	 * @param $countItem \DomElement
	 * @return Anonymous
	 */
	protected function getGuestCountItem($countItem) {
		return new Anonymous([
			'ageQualifyingCode' => $countItem->hasAttribute('AgeQualifyingCode') ? $countItem->getAttribute('AgeQualifyingCode') : null,
			'count' => $countItem->hasAttribute('Count') ? $countItem->getAttribute('Count') : null,
		]);
	}

	/**
	 * @param $commentItem \DomElement
	 * @return Anonymous
	 */
	protected function getCommentItem($commentItem) {
		$anonym = new Anonymous([
			'guestViewable' => $commentItem->hasAttribute('GuestViewable') ? $commentItem->getAttribute('GuestViewable') : null,
			'text' => null,
		]);

		// Text
		$text = $commentItem->getElementsByTagName('Text');

		if ($text->length) {
			$textItem = $text->item(0);

			$anonym->text = $textItem->nodeValue;
		}

		return $anonym;
	}

	/**
	 * @param $domNodeList \DomNodeList
	 * @return Anonymous
	 * @throws \Exception
	 */
	protected function getInfo($domNodeList) {
		if ($domNodeList->length) {
			return $this->getInfoItem($domNodeList->item(0));
		}

		throw new \Exception('Profile info is missing.');
	}

	/**
	 * @param $infoItem \DomElement
	 * @return Anonymous
	 */
	protected function getInfoItem($infoItem) {
		$anonym = new Anonymous([
			'timeSpanStart' => null,
			'timeSpanEnd' => null,
			'totalAmountAfterTax' => null,
			'currency' => null,
			'getProfile' => function() use ($infoItem) {
				return $this->getCollectionElement($infoItem->getElementsByTagName('Profile'), 'getProfileItem');
			},
			'getPaymentCard' => function() use ($infoItem) {
				return $this->getCollectionElement($infoItem->getElementsByTagName('PaymentCard'), 'getPaymentCardItem');
			},
			'getReservationId' => function() use ($infoItem) {
				return $this->getCollectionElement($infoItem->getElementsByTagName('HotelReservationID'), 'getReservationIdItem');
			},
			'getComment' => function() use ($infoItem) {
				return $this->getCollectionElement($infoItem->getElementsByTagName('Comment'), 'getInfoCommentItem');
			},
		]);

		/**
		 * Timespan
		 * @var $timespanItem \DomElement
		 */
		$timespan = $infoItem->getElementsByTagName('TimeSpan');

		if ($timespan->length) {
			$timespanItem = $timespan->item(0);

			$anonym->timeSpanStart = $timespanItem->hasAttribute('Start') ? $timespanItem->getAttribute('Start') : null;
			$anonym->timeSpanEnd = $timespanItem->hasAttribute('End') ? $timespanItem->getAttribute('End') : null;
		}

		/**
		 * Total
		 * @var $totalItem \DomElement
		 */
		$total = $infoItem->getElementsByTagName('Total');

		if ($total->length) {
			$totalItem = $total->item(0);

			$anonym->totalAmountAfterTax = $totalItem->hasAttribute('AmountAfterTax') ? $totalItem->getAttribute('AmountAfterTax') : null;
			$anonym->currency = $totalItem->hasAttribute('Currency') ? $totalItem->getAttribute('Currency') : null;
		}

		return $anonym;
	}

	/**
	 * @param $profileItem \DomElement
	 * @return Anonymous
	 */
	protected function getProfileItem($profileItem) {
		return new Anonymous([
			'getCustomer' => function() use ($profileItem) {
				return $this->getCollectionElement($profileItem->getElementsByTagName('Customer'), 'getCustomerItem');
			},
		]);
	}

	/**
	 * @param $customerItem \DomElement
	 * @return Anonymous
	 */
	protected function getCustomerItem($customerItem) {
		$anonym = new Anonymous([
			'language' => $customerItem->hasAttribute('Language') ? $customerItem->getAttribute('Language') : null,
			'name' => null,
			'surname' => null,
			'phone' => null,
			'email' => null,
			'address' => null,
			'city' => null,
			'postalCode' => null,
			'country' => null,
		]);

		/**
		 * Name
		 * @var $nameItem \DomElement
		 */
		$name = $customerItem->getElementsByTagName('GivenName');

		if ($name->length) {
			$nameItem = $name->item(0);

			$anonym->name = $nameItem->nodeValue;
		}

		/**
		 * Surname
		 * @var $surnameItem \DomElement
		 */
		$surname = $customerItem->getElementsByTagName('Surname');

		if ($surname->length) {
			$surnameItem = $surname->item(0);

			$anonym->surname = $surnameItem->nodeValue;
		}

		/**
		 * Telephone
		 * @var $phoneItem \DomElement
		 */
		$phone = $customerItem->getElementsByTagName('Telephone');

		if ($phone->length) {
			$phoneItem = $phone->item(0);

			$anonym->phone = $phoneItem->hasAttribute('PhoneNumber') ? $phoneItem->getAttribute('PhoneNumber') : null;
		}

		/**
		 * Email
		 * @var $emailItem \DomElement
		 */
		$email = $customerItem->getElementsByTagName('Email');

		if ($email->length) {
			$emailItem = $email->item(0);

			$anonym->email = $emailItem->nodeValue;
		}

		/**
		 * Address
		 * @var $addressItem \DomElement
		 */
		$address = $customerItem->getElementsByTagName('AddressLine');

		if ($address->length) {
			$addressItem = $address->item(0);

			$anonym->address = $addressItem->nodeValue;
		}

		/**
		 * City
		 * @var $cityItem \DomElement
		 */
		$city = $customerItem->getElementsByTagName('CityName');

		if ($city->length) {
			$cityItem = $city->item(0);

			$anonym->city = $cityItem->nodeValue;
		}

		/**
		 * PostalCode
		 * @var $postalCodeItem \DomElement
		 */
		$postalCode = $customerItem->getElementsByTagName('PostalCode');

		if ($postalCode->length) {
			$postalCodeItem = $postalCode->item(0);

			$anonym->postalCode = $postalCodeItem->nodeValue;
		}

		/**
		 * Country
		 * @var $countryItem \DomElement
		 */
		$country = $customerItem->getElementsByTagName('CountryName');

		if ($country->length) {
			$countryItem = $country->item(0);

			$anonym->country = $countryItem->nodeValue;
		}

		return $anonym;
	}

	/**
	 * @param $cardItem \DomElement
	 * @return Anonymous
	 */
	protected function getPaymentCardItem($cardItem) {
		$anonym = new Anonymous([
			'cardCode' => $cardItem->hasAttribute('CardCode') ? $cardItem->getAttribute('CardCode') : null,
			'cardNumber' => $cardItem->hasAttribute('CardNumber') ? $cardItem->getAttribute('CardNumber') : null,
			'seriesCode' => $cardItem->hasAttribute('SeriesCode') ? $cardItem->getAttribute('SeriesCode') : null,
			'expireDate' => $cardItem->hasAttribute('ExpireDate') ? $cardItem->getAttribute('ExpireDate') : null,
			'cardHolderName' => null,
		]);

		/**
		 * Card Holder Name
		 * @var $nameItem \DomElement
		 */
		$name = $cardItem->getElementsByTagName('CardHolderName');

		if ($name->length) {
			$nameItem = $name->item(0);

			$anonym->cardHolderName = $nameItem->nodeValue;
		}

		return $anonym;
	}

	/**
	 * @param $reservationItem \DomElement
	 * @return Anonymous
	 */
	protected function getReservationIdItem($reservationItem) {
		return new Anonymous([
			'value' => $reservationItem->hasAttribute('ResID_Value') ? $reservationItem->getAttribute('ResID_Value') : null,
			'source' => $reservationItem->hasAttribute('ResID_Source') ? $reservationItem->getAttribute('ResID_Source') : null,
		]);
	}

	/**
	 * @param $commentItem \DomElement
	 * @return Anonymous
	 */
	protected function getInfoCommentItem($commentItem) {
		$anonym = new Anonymous([
			'guestViewable' => $commentItem->hasAttribute('GuestViewable') ? $commentItem->getAttribute('GuestViewable') : null,
			'name' => $commentItem->hasAttribute('Name') ? $commentItem->getAttribute('Name') : null,
			'creatorId' => $commentItem->hasAttribute('CreatorID') ? $commentItem->getAttribute('CreatorID') : null,
			'text' => null,
		]);

		/**
		 * Text
		 * @var $textItem \DomElement
		 */
		$text = $commentItem->getElementsByTagName('Text');

		if ($text->length) {
			$textItem = $text->item(0);

			$anonym->text = $textItem->nodeValue;
		}

		return $anonym;
	}
}
