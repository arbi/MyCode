<?php

namespace Library\ChannelManager;

use Library\Utility\Debug;
use Zend\ServiceManager\ServiceManager;

abstract class ChannelManagerBase extends ChannelManagerEngine
{
	private $result = [];

	protected function updateRate($params) {
		foreach ($this->providers as $provider) {
			if ($provider instanceof ProviderAdapterInterface) {
				$providerClassName = get_class($provider);
				$this->result['update_rate'][$this->getClassBaseName($providerClassName)] = $provider->updateRate(
					$params,
					new Settings()
				);
			}
		}

		return $this->result['update_rate'];
	}

	protected function checkReservation($params = null)
    {
		foreach ($this->providers as $provider) {
			if ($provider instanceof ProviderAdapterInterface) {
				$providerClassName = get_class($provider);
				$this->result['check_reservation'][$this->getClassBaseName($providerClassName)] = $provider->checkReservation(
					$this->initSettings(
						$params,
						$this->checkReservationSettings()
					)
				);
			}
		}

		return $this->result['check_reservation'];
	}

	protected function checkRate($params = null)
    {
		foreach ($this->providers as $provider) {
			if ($provider instanceof ProviderAdapterInterface) {
				$providerClassName = get_class($provider);
				$this->result['check_rate'][$this->getClassBaseName($providerClassName)] = $provider->checkRate(
					$this->initSettings(
						$params,
						new Settings()
					)
				);
			}
		}

		return $this->result['check_rate'];
	}

	protected function confirm($params = null)
    {
		foreach ($this->providers as $provider) {
			if ($provider instanceof ProviderAdapterInterface) {
				$providerClassName = get_class($provider);
				$this->result['confirm'][$this->getClassBaseName($providerClassName)] = $provider->confirm(
					$this->initSettings(
						$params,
						new Settings()
					)
				);
			}
		}

		return $this->result['confirm'];
	}

	protected function sendRaw($apartmentId, $xml)
    {
		foreach ($this->providers as $provider) {
			if ($provider instanceof ProviderAdapterInterface) {
				$providerClassName = get_class($provider);
				$this->result['send_raw'][$this->getClassBaseName($providerClassName)] = $provider->sendRaw(
                    $apartmentId,
					$xml
				);
			}
		}

		return $this->result['send_raw'];
	}
}
