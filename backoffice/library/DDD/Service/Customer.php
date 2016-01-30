<?php

namespace DDD\Service;

use DDD\Dao\Finance\Transaction\TransactionAccounts as TransactionAccountsDAO;
use Library\Finance\Base\Account;
use Library\IpInfo\IpInfo;
use Library\Validator\ClassicValidator;
use Zend\Session\Container;

/**
 * Class Customer
 * @package DDD\Service
 */
class Customer extends ServiceBase
{
    const UNKNOWN_EMAIL = 'unknown@ginosi.com';

    /**
     * @param $emailAddress
     * @return \DDD\Domain\Finance\Customer|null
     */
    public function createCustomer($emailAddress)
    {
        /**
         * @var \DDD\Dao\Finance\Customer $customerDao
         */
        $customerDao = $this->getServiceLocator()->get('dao_finance_customer');

        // Set unknown email address if email address is empty or invalid
        if (is_null($emailAddress) || !ClassicValidator::validateEmailAddress($emailAddress)) {
            $emailAddress = self::UNKNOWN_EMAIL;
        }

        $affectedRows = $customerDao->save(
            ['email' => $emailAddress]
        );

        if ($affectedRows) {
            $lastInsertedId = $customerDao->getLastInsertValue();
            $customer = $customerDao->getCustomer($lastInsertedId);

            $this->createCustomerTransactionAccount($lastInsertedId);

            return $customer;
        } else {
            return null;
        }
    }

    /**
     * @param $customerId
     * @return bool
     */
    public function createCustomerTransactionAccount($customerId)
    {
        /**
         * @var TransactionAccountsDAO $transactionAccountDao
         */
        $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');

        $transactionAccountDao->save([
            'type' => Account::TYPE_CUSTOMER,
            'holder_id' => $customerId,
        ]);

        return true;
    }

    /**
     * @param $customerId
     * @param $email
     * @return bool
     */
    public function updateCustomerEmail($customerId, $email)
    {
        /**
         * @var \DDD\Dao\Finance\Customer $customerDao
         */
        $customerDao = $this->getServiceLocator()->get('dao_finance_customer');

        $customerDao->update(
            ['email' => $email],
            ['id' => $customerId]
        );

        return true;
    }









    public function saveCustomerIdentityForReservation($reservationId, $forceData = [])
    {
        try {
            if (is_numeric($reservationId)) {
                $data['reservation_id'] = $reservationId;
            } else {
                throw new \Exception('Wrong reservation number');
            }

            /* @var $bookingDao \DDD\Dao\Booking\Booking */
            $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

            $reservationCustomerId = $bookingDao->getCustomerIdByReservationId($reservationId);

            $data['customer_id'] = $reservationCustomerId;

            /* @var $customerIdentityDao \DDD\Dao\Customer\CustomerIdentity */
            $customerIdentityDao = $this->getServiceLocator()->get('dao_customer_customer_identity');

            $existingIdentity = $customerIdentityDao->getCustomerIdentityByReservationId($reservationId);

            // get data about visitor from session
            $visitorData = new Container('visitor');

            if (isset($_COOKIE['backoffice_user']) && is_numeric($_COOKIE['backoffice_user'])) {
                /* @var $userDao \DDD\Dao\User\UserManager */
                $userDao = $this->getServiceLocator()->get('dao_user_user_manager');

                $userData = $userDao->getUserById($_COOKIE['backoffice_user'], true);

                if ($userData) {
                    $data['user_id'] = $userData->getId();
                }
            } elseif ($this->isBot($visitorData)) {
                return FALSE;
            } else {

                $listOfBlankIpFromCubilis = ['127.0.0.1'];

                if ((
                    !($existingIdentity)
                    || empty($existingIdentity->getIpAddress())
                    || in_array(long2ip($existingIdentity->getIpAddress()), $listOfBlankIpFromCubilis)
                    ) && isset($visitorData->ip)
                ) {
                    $data['ip_address'] = ip2long($visitorData->ip);
                }

                $ipInfoService = new IpInfo();
                $ipInfo = $ipInfoService->getIpInfo($visitorData->ip);

                $ipInfoHostName = $ipInfo->getHostName();
                $ipInfoProvider = $ipInfo->getProvider();
                $ipInfoCity = $ipInfo->getCity();
                $ipInfoCountry = $ipInfo->getCountry();
                $ipInfoLocation = $ipInfo->getLocation();
                $ipInfoRegion = $ipInfo->getRegion();

                if ((!($existingIdentity) || empty($existingIdentity->getIpAddress())) && isset($ipInfoHostName)) {
                    $data['ip_hostname'] = $ipInfoHostName;
                }

                if ((!($existingIdentity) || empty($existingIdentity->getIpAddress())) && isset($ipInfoProvider)) {
                    $data['ip_provider'] = $ipInfoProvider;
                }

                if (isset($visitorData->ua_family)) {
                    $data['ua_family'] = $visitorData->ua_family;
                }

                if (isset($visitorData->ua_major)) {
                    $data['ua_major'] = $visitorData->ua_major;
                }

                if (isset($visitorData->ua_minor)) {
                    $data['ua_minor'] = $visitorData->ua_minor;
                }

                if (isset($visitorData->ua_patch)) {
                    $data['ua_patch'] = $visitorData->ua_patch;
                }

                if (isset($visitorData->ua_languages)) {
                    $data['ua_language'] = $visitorData->ua_languages;
                }

                if (isset($visitorData->os_family)) {
                    $data['os_family'] = $visitorData->os_family;
                }

                if (isset($visitorData->os_major)) {
                    $data['os_major'] = $visitorData->os_major;
                }

                if (isset($visitorData->os_minor)) {
                    $data['os_minor'] = $visitorData->os_minor;
                }

                if (isset($visitorData->os_patch)) {
                    $data['os_patch'] = $visitorData->os_patch;
                }

                if (isset($visitorData->os_patchMinor)) {
                    $data['os_patchMinor'] = $visitorData->os_patchMinor;
                }

                if (isset($visitorData->device_family)) {
                    $data['device_family'] = $visitorData->device_family;
                }

                if (isset($visitorData->device_brand)) {
                    $data['device_brand'] = $visitorData->device_brand;
                }

                if (isset($visitorData->device_model)) {
                    $data['device_model'] = $visitorData->device_model;
                }

                if ((!($existingIdentity) || empty($existingIdentity->getGeoCountry())) && isset($ipInfoCity)) {
                    $data['geo_city'] = $ipInfoCity;
                }

                if ((!($existingIdentity) || empty($existingIdentity->getGeoCountry())) && isset($ipInfoRegion)) {
                    $data['geo_region'] = $ipInfoRegion;
                }

                if ((!($existingIdentity) || empty($existingIdentity->getGeoCountry())) && isset($ipInfoCountry)) {
                    $data['geo_country'] = $ipInfoCountry;
                }

                if ((!($existingIdentity) || empty($existingIdentity->getGeoCountry())) && isset($ipInfoLocation)) {
                    $data['geo_location'] = $ipInfoLocation;
                }

                if (isset($visitorData->landing_page)) {
                    $data['landing_page'] = $visitorData->landing_page;
                }

                if (isset($visitorData->referer)) {
                    $data['referer_page'] = $visitorData->referer;
                }

                if (isset($visitorData->referer_host)) {
                    $data['referer_host'] = $visitorData->referer_host;
                }
            }

            // rigidly replace data
            if (count($forceData)) {
                foreach ($forceData as $dataKey => $dataValue) {
                    if ($dataKey == 'ip_address') {
                        $data[$dataKey] = ip2long($dataValue);
                    } else {
                        $data[$dataKey] = $dataValue;
                    }
                }
            }

            if ($existingIdentity) {
                return $customerIdentityDao->update($data,
                    [
                        'reservation_id' => $reservationId
                    ]);
            } else {
                return $customerIdentityDao->save($data);
            }

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     *
     * @param \Zend\Session\Container $visitorData
     * @return bool
     */
    public function isBot($visitorData)
    {
        $bot = 'bot';
        $spider = 'spider';

        if (    (isset($visitorData->ua_family) && (strstr(strtolower($visitorData->ua_family), $bot) || strstr(strtolower($visitorData->ua_family), $spider)))
            ||  (isset($visitorData->os_family) && (strstr(strtolower($visitorData->os_family), $bot) || strstr(strtolower($visitorData->os_family), $spider)))
            ||  (isset($visitorData->device_family) && (strstr(strtolower($visitorData->device_family), $bot) || strstr(strtolower($visitorData->device_family), $spider)))
        ) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     *
     * @param int $reservationId
     * @return \DDD\Domain\Customer\CustomerIdentity
     */
    public function getCustomerIdentityByReservationId($reservationId)
    {
        /* @var $customerIdentityDao \DDD\Dao\Customer\CustomerIdentity */
        $customerIdentityDao = $this->getServiceLocator()->get('dao_customer_customer_identity');

        return $customerIdentityDao->getCustomerIdentityByReservationId($reservationId);
    }
}
