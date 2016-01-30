<?php

namespace Library\ChannelManager\Provider\Cubilis;

use DDD\Dao\Apartment\Details as DaoDetails;
use Library\ChannelManager\ChannelManager;
use Library\ChannelManager\CivilResponder;
use Library\Constants\DomainConstants;
use Library\Utility\Debug;
use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Log\Logger;
use ZF2Graylog2\Traits\Logger as ZF2Graylog2Logger;
use Zend\ServiceManager\ServiceManager;

class Cubilis
{
    use ZF2Graylog2Logger;

    private $url = [
		'prod' => [
            'get_reservation'  => 'https://cubilis.eu/plugins/pms_ota/reservations.aspx',
            'get_availability' => 'https://cubilis.eu/plugins/pms_ota/accommodations.aspx',
            'set_availability' => 'https://cubilis.eu/plugins/pms_ota/set_availability.aspx',
            'confirmation'     => 'https://www.cubilis.eu/plugins/pms_ota/confirmReservations.aspx',
        ],
        'dev' => [
            'get_reservation'  => 'http://pastebin.com/raw.php?i=bKtHGhUm',
            'get_availability' => 'http://pastebin.com/raw.php?i=myHfcBjv',
            'set_availability' => 'http://pastebin.com/raw.php?i=UK0cvb7P',
            'error'            => 'http://pastebin.com/raw.php?i=9F0HHJNJ',
            'success'          => 'http://pastebin.com/raw.php?i=UK0cvb7P',
            'confirmation'     => 'http://pastebin.com/raw.php?i=UK0cvb7P',
        ],
    ];

	private $chm;
	private $sm;
	private $client;

    const NO_CONNECTION_CODE = 99999;

	public function __construct(ChannelManager $chm)
    {
		$this->chm = $chm;
		$this->sm = $chm->getServiceLocator();
		$this->client = new Client();
		$this->client
			->setMethod(Request::METHOD_POST)
			->setEncType('text/xml')
			->setAdapter(new Client\Adapter\Curl())
			->setOptions([
				'curloptions' => [
					CURLOPT_SSL_VERIFYHOST => 2,
					CURLOPT_SSL_VERIFYPEER => true,
                    CURLOPT_SSLVERSION => 1, // tls
                ]
            ]);
    }

    private function getUrl($route)
    {
        if (ChannelManager::isDebugMode()) {
            if (isset($this->url['dev'][$route])) {
                return $this->url['dev'][$route];
            } else {
                throw new \Exception('DEBUG MODE: Route not match.');
            }
        } else {
            if (isset($this->url['prod'][$route])) {
                return $this->url['prod'][$route];
            } else {
                throw new \Exception('Route not match.');
            }
        }
    }

    public function updateRate($params)
    {
        $output = new \stdClass();
        $apartmentId = $this->getApartmentId($params);
        $generator = new UpdateRateGenerator();
        $xml = $generator->generateRI([
            'credentials' => $this->getCredentials($apartmentId),
            'params' => $params,
        ]);

        try {
            if ($xml === false) {
                $errorMessage = 'Cannot generate xml: ' . $generator->getErrors();
                $this->gr2crit('Cannot generate XML', [
                    'full_message' => $generator->getErrors()
                ]);

                throw new \Exception($errorMessage);
            }

            $this->client->setUri($this->getUrl('set_availability'));
            $this->client->setRawBody($xml);

            $response = $this->client->send();

            if ($response->getStatusCode() == 200) {
                $parser = new StandardCubilisXMLParser($response->getBody());

                if ($parser->isSuccess()) {
                    $output->status = CivilResponder::STATUS_SUCCESS;
                    $output->message = 'Cubilis has been successfully updated.';
                } else {
                    $output->status = CivilResponder::STATUS_ERROR;
                    $output->code = $parser->getError()->code;
                    $output->message = $parser->getError()->message;
                    $output->data = $xml;
                }
            } else {
                throw new \Exception('Cubilis is down. Page status code: ' . $response->getStatusCode(), self::NO_CONNECTION_CODE);
            }
        } catch (\Exception $ex) {
            $output->status = CivilResponder::STATUS_ERROR;
            $output->message = $ex->getMessage();
            $output->code = $ex->getCode();
            $output->data = $xml;
        }

        return $output;
    }

    public function sendRaw($apartmentId, $xml)
    {
        $output = new \stdClass();

        try {

            $this->client->setUri($this->getUrl('set_availability'));
            $this->client->setRawBody($xml);

            $response = $this->client->send();

            if ($response->getStatusCode() == 200) {

                $parser = new StandardCubilisXMLParser($response->getBody());

                if ($parser->isSuccess()) {
                    $output->status = CivilResponder::STATUS_SUCCESS;
                    $output->message = 'Cubilis has been successfully updated.';
                } else {
                    $output->status = CivilResponder::STATUS_ERROR;
                    $output->code = $parser->getError()->code;
                    $output->message = $parser->getError()->message;
                    $output->data = $xml;
                }
            } else {
                throw new \Exception('Cubilis is down. Page status code: ' . $response->getStatusCode());
            }
        } catch (\Exception $ex) {
            $output->status = CivilResponder::STATUS_ERROR;
            $output->message = $ex->getMessage();
            $output->code = $ex->getCode();
            $output->data = $xml;
        }

        return $output;
    }

    public function checkRate($params)
    {
        /**
         * @var Response $response
         */
        $output = new \stdClass();

        try {
            $apartmentId = $this->getApartmentId($params);
            $generator = new RateInformationGenerator();
            $xml = $generator->generateRI([
                'credentials' => $this->getCredentials($apartmentId),
            ]);

            if ($xml === false) {
                $errorMessage = 'Cannot generate xml: ' . $generator->getErrors();
                $this->gr2crit('Cannot generate XML', [
                    'full_message' => $generator->getErrors()
                ]);

                throw new \Exception($errorMessage);
            }

            $this->client->setUri($this->getUrl('get_availability'));
            $this->client->setRawBody($xml);

            $response = $this->client->send();

            if ($response->getStatusCode() == 200) {
                $parser = new RatesCubilisXMLParser($response->getBody());

                if ($parser->isSuccess()) {
                    $output->status = CivilResponder::STATUS_SUCCESS;
                    $output->message = 'Availability check successful.';
                    $output->data = $parser;
                } else {
                    $output->status = CivilResponder::STATUS_ERROR;
                    $output->code = $parser->getError()->code;
                    $output->message = $parser->getError()->message;
                }
            } else {
                throw new \Exception('Cubilis is down. Page status code: ' . $response->getStatusCode());
            }
        } catch (\Exception $ex) {
            $output->status = CivilResponder::STATUS_ERROR;
            $output->message = $ex->getMessage();
            $output->code = $ex->getCode();
        }

        return $output;
    }

    public function notificationReport($params)
    {
        /**
         * @var Response $response
         */
        $output = new \stdClass();

        try {
            $apartmentId = $this->getApartmentId($params);
            $generator = new NotificationReportGenerator();
            $xml = $generator->generateNR([
                'credentials' => $this->getCredentials($apartmentId),
                'params' => $params,
            ]);

            if ($xml === false) {
                $errorMessage = 'Cannot generate xml: ' . $generator->getErrors();
                $this->gr2crit('Cannot generate XML', [
                    'full_message' => $generator->getErrors()
                ]);

                throw new \Exception($errorMessage);
            }

            $this->client->setUri($this->getUrl('confirmation'));
            $this->client->setRawBody($xml);

            $response = $this->client->send();

            if ($response->getStatusCode() == 200) {
                $parser = new StandardCubilisXMLParser($response->getBody());

                if ($parser->isSuccess()) {
                    $output->status = CivilResponder::STATUS_SUCCESS;
                    $output->message = 'Confirmation successfull.';
                } else {
                    $output->status = CivilResponder::STATUS_ERROR;
                    $output->message = $parser->getError()->message;
                    $output->code = $parser->getError()->code;
                }
            } else {
                throw new \Exception('Cubilis is down. Page status code: ' . $response->getStatusCode());
            }
        } catch (\Exception $ex) {
            $output->status = CivilResponder::STATUS_ERROR;
            $output->message = "Cannot send notification request: {$ex->getMessage()}";
            $output->code = $ex->getCode();
        }

        return $output;
    }

    public function checkReservation($params)
    {
        /**
         * @var Response $response
         */
        $output = new \stdClass();

        try {
            $apartmentId = $this->getApartmentId($params);
            $generator = new RoomInformationGenerator();
            $xml = $generator->generateRI([
                'credentials' => $this->getCredentials($apartmentId),
                'params' => $params,
            ]);

            if ($xml === false) {
                $errorMessage = 'Cannot generate xml: ' . $generator->getErrors();
                $this->gr2crit('Cannot generate XML', [
                    'full_message' => $generator->getErrors()
                ]);

                throw new \Exception($errorMessage);
            }

            try {
                if (!ChannelManager::dontLogThatYouDontWantYouToLog($xml)) {
                    $this->gr2debug('Cubilis XML: ' . ChannelManager::LOGGING_REQUEST, [
                        'xml'          => ChannelManager::replaceSensitiveData($xml),
                        'apartment_id' => $apartmentId,
                        'request_type' => ChannelManager::REQUEST_RESERVATION
                    ]);
                }
            } catch (\Exception $ex) {
                $this->gr2logException($ex, 'Cubilis: XML Request logging failed', $params);
            }

            $this->client->setUri($this->getUrl('get_reservation'));
            $this->client->setRawBody($xml);

            $response = $this->client->send();

            if ($response->getStatusCode() == 200) {
                try {
                    if (!ChannelManager::dontLogThatYouDontWantYouToLog($response->getBody())) {
                        $this->gr2debug('Cubilis XML: '.ChannelManager::LOGGING_RESPONSE, [
                            'xml' => ChannelManager::replaceSensitiveData($response->getBody()),
                            'apartment_id' => $apartmentId,
                            'request_type' => ChannelManager::REQUEST_RESERVATION
                        ]);
                    }
                } catch (\Exception $ex) {
                    $this->gr2logException($ex, 'Cubilis: XML Request logging failed', $params);
                }

                $parser = new ReservationCubilisXMLParser($response->getBody());

                if ($parser->isSuccess()) {
                    $output->status = CivilResponder::STATUS_SUCCESS;
                    $output->message = 'Check successfull.';
                    $output->data = $parser->getReservationList();
                } else {
                    $output->status = CivilResponder::STATUS_ERROR;
                    $output->message = $parser->getError()->message;
                    $output->code = $parser->getError()->code;
                }
            } else {
                $this->gr2alert('Cubilis is down.', [
                    'response_code' => $response->getStatusCode()
                ]);

                throw new \Exception('Cubilis is down. Page status code: ' . $response->getStatusCode());
            }
        } catch (\Exception $ex) {
            $output->status = CivilResponder::STATUS_ERROR;
            $output->message = "Reservation check failed: {$ex->getMessage()}";
            $output->code = $ex->getCode();
        }

        return $output;
    }

    private function getCredentials($productId)
    {
        if (ChannelManager::isDebugMode()) {
            return [
                'id' => '11111',
                'password' => '11111',
                'cubilis_id' => '11111',
            ];
        }

		/**
		 * @var \DDD\Dao\Apartment\Details $accDetailsDao
		 * @var \DDD\Domain\Apartment\Details\Sync $accDetails
         * @var \DDD\Dao\Apartel\General $apartelGeneralDao
		 */
        if ($this->chm->getProductType() == ChannelManager::PRODUCT_APARTEL) {
            $apartelGeneralDao = $this->getServiceLocator()->get('dao_apartel_general');
            $apartelGeneral = $apartelGeneralDao->getGeneralConnectionData($productId);

            $syncCubilis = $apartelGeneral->getSyncCubilis();
            $username = $apartelGeneral->getCubilisUsername();
            $password = $apartelGeneral->getCubilisPassword();
            $cubilisId = $apartelGeneral->getCubilisId();
        } else {
            $accDetailsDao = $this->getAccDetailsDao();
            $accDetails = $accDetailsDao->fetchOne([
                'apartment_id' => $productId,
            ],[
                'sync_cubilis',
                'cubilis_id',
                'cubilis_us',
                'cubilis_pass',
            ]);

            $syncCubilis = $accDetails->getSync_cubilis();
            $username = $accDetails->getCubilisUs();
            $password = $accDetails->getCubilisPass();
            $cubilisId = $accDetails->getCubilisId();
        }

		if (DomainConstants::BO_DOMAIN_NAME == "backoffice.ginosi.com" || ChannelManager::isHighestPrivilegyGiven()) {
			if ($syncCubilis) {
				if ($cubilisId && $username && $password) {
					return [
						'id' => $username,
						'password' => $password,
						'cubilis_id' => $cubilisId,
					];
				} else {
					throw new \Exception('Cubilis apartment credentials are empty or are in wrong format.');
				}
			} else {
				throw new \Exception('This product doesnot need to be synced with cubilis.');
			}
		} else {
			if ($syncCubilis) {
				throw new \Exception('TEST! This product cannot be synchronized with cubilis.');
			} else {
				throw new \Exception('TEST! This product does not need to be synced with cubilis.');
			}
		}
	}

    private function getServiceLocator()
    {
        return $this->sm;
    }

    private function getAccDetailsDao()
    {
        return new DaoDetails($this->getServiceLocator(), 'DDD\Domain\Apartment\Details\Sync');
    }

    private function getApartmentId($params)
    {
        if (isset($params['apartment_id'])) {
            return $params['apartment_id'];
        } else {
            throw new \Exception('Apartment ID is missing');
        }
    }
}
