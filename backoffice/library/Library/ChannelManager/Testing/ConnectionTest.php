<?php

namespace Library\ChannelManager\Testing;

use DDD\Service\ChannelManager;
use Library\ChannelManager\ChannelManager as Chm;
use Library\ChannelManager\CivilResponder;
use Library\ChannelManager\Provider\Cubilis\PHPDocInterface\Reservation;
use Zend\ServiceManager\ServiceLocatorInterface;

class ConnectionTest {
	protected $sm;

	/**
	 * @param ServiceLocatorInterface $sm
	 */
	public function __construct($sm) {
		$this->sm = $sm;
	}

    /**
     * @param $apartmentId
     * @param bool $isApartel
     * @return array
     */
	public function testPullReservation($apartmentId, $isApartel = false) {
		/**
		 * @var ChannelManager $chmService
		 * @var \Library\ChannelManager\ChannelManager $chm
		 * @var Reservation $chmReservationData
		 */
		$result = [
			'status' => 'error',
			'msg' => 'Cannot test pullReservation action.',
		];

		try {
			$chmService = $this->sm->get('service_channel_manager');
			$chm = $this->sm->get('ChannelManager');
            $chm->setProductType($isApartel ? Chm::PRODUCT_APARTEL : Chm::PRODUCT_APARTMENT);

			$chmResult = $chm->cronCheckReservation([
				'apartment_id' => $apartmentId,
				'data' => []
			]);
			$chmReservationData = $chmResult->getData();

			if ($chmResult->getStatus() == CivilResponder::STATUS_SUCCESS) {
				$list = $chmService->handleReservations($chmReservationData, $apartmentId, $isApartel);
				$result = [
					'status' => 'success',
					'msg' => 'Pull reservation successful.',
				];
			} else {
				$result['msg'] = $chmResult->getMessage();
			}
		} catch(\Exception $ex) {
			$result['msg'] = $ex->getMessage();
		}

		return $result;
	}

	/**
	 * @param int $apartmentId
	 * @return array
	 */
	public function testUpdateAvailability($apartmentId, $isApartel = false) {
		/**
		 * @var ChannelManager $chmService
		 * @var \Library\ChannelManager\ChannelManager $chm
		 * @var Reservation $chmReservationData
		 */
		$result = [
			'status' => 'error',
			'msg' => 'Cannot test updateAvailability action.',
		];

		try {
			$chm = $this->sm->get('ChannelManager');
            $chm->setProductType($isApartel ? Chm::PRODUCT_APARTEL : Chm::PRODUCT_APARTMENT);

			$chmResult = $chm->updateAvailability([
				'apartment_id' => $apartmentId,
				'data' => [
					[
						'date' => date('Y-m-d', strtotime('-30day')),
						'rate_id' => '1000',
						'room_id' => '1000',
						'avail' => 0
					],
				]
			]);

			if ($chmResult->getStatus() == CivilResponder::STATUS_SUCCESS) {
				$result['msg'] = 'Something crazy\'s going with Cubilis. Notify R&D team about.';
			} else {
				$authErrorList = [401, 507, 508, 509, 606, 607, 609, 610, 706, 707];

				if (!in_array($chmResult->getCode(), $authErrorList) && !preg_match('/username|password|authentication/i', $chmResult->getMessage())) {
					$result = [
						'status' => 'success',
						'msg' => 'Update availability successfull.'
					];
				} else {
					$result['msg'] = $chmResult->getMessage();
				}
			}
		} catch(\Exception $ex) {
			$result['msg'] = $ex->getMessage();
		}

		return $result;
	}

	/**
	 * @param int $apartmentId
	 * @return array
	 */
	public function testFetchList($apartmentId, $isApartel = false) {
		/**
		 * @var \Library\ChannelManager\ChannelManager $chm
		 */
		$result = [
			'status' => 'error',
			'msg' => 'Cannot test pullReservation action.',
		];

		try {
			$chm = $this->sm->get('ChannelManager');
            $chm->setProductType($isApartel ? Chm::PRODUCT_APARTEL : Chm::PRODUCT_APARTMENT);

			$chmResult = $chm->syncWithCubilis([
				'apartment_id' => $apartmentId,
			]);

			if ($chmResult->getStatus() == CivilResponder::STATUS_SUCCESS) {
				$result = [
					'status' => 'success',
					'msg' => 'Rates list successfully fetched from cubilis.',
				];
			} else {
				$result['msg'] = $chmResult->getMessage();
			}
		} catch(\Exception $ex) {
			$result['msg'] = $ex->getMessage();
		}

		return $result;
	}
}
