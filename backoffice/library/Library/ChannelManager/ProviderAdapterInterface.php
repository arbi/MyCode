<?php

namespace Library\ChannelManager;

interface ProviderAdapterInterface {
	/**
	 * @param null|array|Settings $params
	 * @return mixed
	 */
	public function updateRate($params);

	/**
	 * @param null|array|Settings $params
	 * @return mixed
	 */
	public function checkRate($params);

	/**
	 * @param null|array|Settings $params
	 * @return mixed
	 */
	public function checkReservation($params);

	/**
	 * @param null|array|Settings $params
	 * @return mixed
	 */
	public function confirm($params);

	/**
	 * @param int $apartmentId
	 * @param string $xml
	 * @return mixed
	 */
	public function sendRaw($apartmentId, $xml);
}
