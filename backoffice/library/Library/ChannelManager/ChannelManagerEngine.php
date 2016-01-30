<?php

namespace Library\ChannelManager;

use Zend\ServiceManager\ServiceLocatorInterface;
use ZF2Graylog2\Traits\Logger as ZF2Graylog2Logger;

abstract class ChannelManagerEngine
{
    use ZF2Graylog2Logger;

	const PROVIDERS_ALL = '*';

    const PRODUCT_APARTMENT = 1;
    const PRODUCT_APARTEL = 2;

	const RESERVATION_TYPE_STANDARD = 1;
	const RESERVATION_TYPE_DATE = 2;
	const RESERVATION_TYPE_RESERVATION = 3;

	const APPLICATION_ROOM = 1;
	const APPLICATION_RATE = 2;

	const STATUS_OPEN = 1;
	const STATUS_CLOSE = 2;

	const LOS_MIN = 1;
	const LOS_MAX = 2;

	const PRICE_TYPE_STANDARD = 1;
	const PRICE_TYPE_SINGLE = 2;

	const LOGGING_REQUEST = 'request';
	const LOGGING_RESPONSE = 'response';

	const REQUEST_RESERVATION = 'reservation';
	const REQUEST_RATE = 'rate';

	const SOURCE_LOG = 1;
	const SOURCE_XML = 2;

	private $settings;
	private $providersSignatures;

    private $productType;

    /**
     * @param mixed $productType
     */
    public function setProductType($productType)
    {
        $this->productType = $productType;
    }

    /**
     * @return mixed
     */
    public function getProductType()
    {
        return $this->productType;
    }

	/**
	 * Available providers which is enabled from config.php and not disabled from list
	 * @var ProviderAdapterInterface[] $providers
	 */
	protected $providers;

	/**
	 * @var ServiceLocatorInterface $sm
	 */
	protected $sm;

    /**
     * @param \Zend\ServiceManager\ServiceLocatorInterface $sm
     */
    public function setServiceLocator($sm)
    {
        $this->sm = $sm;
    }

    /**
     * @return \Zend\ServiceManager\ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->sm;
    }

	protected static $highestPrivilegyGiven = false;
	protected static $debugMode = false;

	public function __construct(ServiceLocatorInterface $sm, $settings = null)
    {
		$this->sm = $sm;
		$this->initSettings($settings);
		$this->setupProviders();
	}

	protected function initSettings($settings, $defaultSetting = null)
    {
		if (is_array($settings)) {
			$settings = new Settings($settings);
		} elseif ($settings instanceof Settings) {
			// do nothing
		} elseif (is_null($settings)) {
			if (is_null($defaultSetting)) {
				$settings = $this->defaultSettings();
			} else {
				$settings = $defaultSetting;
			}
		} else {
			throw new \Exception('Unidentified settings format.');
		}

		$this->settings = $settings;

		return $settings;
	}

	protected function addProvider(ProviderAdapterInterface $providerInstance)
    {
		$this->providers[] = $providerInstance;
	}

	private function setupProviders()
    {
        /**
         * @var ProviderAdapterInterface|\ArrayObject $provider
         */
		$this->loadProviders();

		$requiredProviders = $this->settings->get('providers', [self::PROVIDERS_ALL]);

		if (count($requiredProviders)) {
			$availableProviders = [];

			foreach ($this->providersSignatures as $provider) {
				$availableProviders[] = $provider->name;
			}

			if ($requiredProviders[0] != self::PROVIDERS_ALL && count(array_intersect($availableProviders, $requiredProviders)) != count($requiredProviders)) {
				throw new \Exception('The provider name is not enrolled in the list of module names.');
			}

			foreach ($this->providersSignatures as $provider) {
				$is = false;

				if ($requiredProviders[0] == self::PROVIDERS_ALL) {
					$is = true;
				} else {
					if (in_array($provider->name, $requiredProviders)) {
						$is = true;
					} else {
                        $this->gr2err("Provider wasn't registered in our system", [
                            'cron' 			   => 'ChannelManager',
                            'channel_provider' => $provider->name
                        ]);
					}
				}

				!$is ?: $this->addProvider(
					new $provider->module->adapter(
						new $provider->module->class($this)
					)
				);
			}
		} else {
			throw new \Exception('No providers where found.');
		}
	}

	private function loadProviders()
    {
        /**
         * @var \ArrayObject $moduleConfig
         */
		$modules = glob(__DIR__ . '/Provider/*', GLOB_ONLYDIR);

		if (count($modules)) {
			foreach ($modules as $module) {
				$moduleEntrails = glob($module . '/*.*');

				if (count($moduleEntrails)) {
					foreach ($moduleEntrails as $entrail) {
						if (basename($entrail) == 'config.php') {
							if (is_readable($entrail)) {
								$moduleConfig = new Settings(include $entrail);

								if (!is_null($moduleConfig->name) &&
									!is_null($moduleConfig->version) &&
									!is_null($moduleConfig->status) &&
									!is_null($moduleConfig->module) &&
									!is_null($moduleConfig->module->adapter) &&
									!is_null($moduleConfig->module->class)) {
									$this->providersSignatures[$moduleConfig->name] = $moduleConfig;
								} else {
									throw new \Exception('Incorrect structure found in the config.php file for the module ' . basename($module) . '.');
								}

								break;
							} else {
								throw new \Exception('config.php is not readable for module ' . basename($module) . '.');
							}
						}
					}
				} else {
					throw new \Exception('Not found any entrails for the module ' . basename($module) . '.');
				}
			}
		} else {
			throw new \Exception('No modules where found.');
		}
	}

	protected function getClassBaseName($className)
    {
		$parts = explode('\\', $className);

		if (count($parts) < 2) {
			throw new \Exception('Unnormal class with unnormal namespace detected.');
		}

		return $parts[count($parts) - 2];
	}

	protected function defaultSettings()
    {
		return new Settings([
			'providers' => [self::PROVIDERS_ALL]
		]);
	}

	protected function checkReservationSettings()
    {
		return new Settings([
			'type' => self::RESERVATION_TYPE_STANDARD,
		]);
	}

	public function giveMeHighestPrivilege($is = true)
    {
        $this->gr2info('HIGHEST PRIVILEGE ' . ($is ? 'GRANTED' : 'REJECTED'));

		self::$highestPrivilegyGiven = $is;

		return $this;
	}

	public static function isDebugMode()
    {
		return self::$debugMode;
	}

	public function giveMeDebugMode($is = true)
    {
        $this->gr2info('DEBUG MODE ' . ($is ? 'ENABLED' : 'DISABLED'));

		self::giveMeHighestPrivilege();
		self::$debugMode = $is;

		return $this;
	}

	public static function isHighestPrivilegyGiven()
    {
		return self::$highestPrivilegyGiven;
	}

	protected function civilResponseForCivilPeople($object)
    {
		return new CivilResponder($object);
	}

	/**
	 * Disable logging for some cases.
	 *
	 * @param string $xml
	 * @return bool
	 */
	public static function dontLogThatYouDontWantYouToLog($xml)
    {
		// Always log xml which status is not success
		if (preg_match('|Errors|i', $xml)) {
			return false;
		}

		// Request reservations
		if (preg_match('|<HotelReservation\/>|i', $xml)) {
			return true;
		}

		// Response of request reservations
		if (preg_match('|<HotelReservations \/>|i', $xml)) {
			return true;
		}

		// Update rates
		if (preg_match('|<Success \/>|i', $xml)) {
			if (strlen($xml) < 170) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Replace Credit Card number and other sensitive information.
	 *
	 * @param string $xml
	 * @return string
	 */
	public static function replaceSensitiveData($xml)
    {
		$xml = preg_replace('/(CardNumber=")\d+(")/Ui', '$1\114111111111111111$2', $xml);
		$xml = preg_replace('/(SeriesCode=")\d+(")/Ui', '$1\11123$2', $xml);
		$xml = preg_replace('/(ExpireDate=")\d+(")/Ui', '$1\111234$2', $xml);
		$xml = preg_replace('/(Type="1" ID=")\S+(")/Ui', '$1\11login.cubilis@replaced.log$2', $xml);
		$xml = preg_replace('/(MessagePassword=")\S+(")/Ui', '$1\11password-replaced$2', $xml);
		$xml = preg_replace('/(<Email><!\[CDATA\[)\S+(\])/Ui', '$1\11guest.email@replaced.log$2', $xml);

		return $xml;
	}
}
