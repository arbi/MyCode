<?php

namespace DDD\Service;

use DDD\Dao\Finance\Transaction\TransactionAccounts;
use DDD\Domain\Partners\PartnerBooking;
use Library\Authentication\BackofficeAuthenticationService;
use Library\ActionLogger\Logger as ActionLogger;
use Library\Constants\TextConstants;
use Library\Finance\Base\Account;
use Library\Constants\Roles;
use DDD\Service\Contacts\Contact;

class Partners extends ServiceBase
{
    const BUSINESS_MODEL_COMMISSION                     = 1;
    const BUSINESS_MODEL_GINOSI_COLLECT                 = 2;
    const BUSINESS_MODEL_PARTNER_COLLECT_GUEST_INVOICE  = 3;
    const BUSINESS_MODEL_GINOSI_COLLECT_PARTNER         = 4;
    const BUSINESS_MODEL_PARTNER_COLLECT_GUEST_TRANSFER = 5;

    const PARTNER_WEBSITE              = 1;
    const PARTNER_AGODA                = 1118;
    const PARTNER_EXPEDIA              = 1054;
    const PARTNER_EXPEDIA_COLLECT      = 1140;
    const PARTNER_EXPEDIA_VIRTUAL_CARD = 1144;
    const PARTNER_BOOKING              = 1050;
    const PARTNER_UNKNOWN              = 1110;
    const PARTNER_ORBITZ               = 1146;

    const PARTNER_UNKNOWN_COMMISSION   = 0;
    const PARTNER_NAME_UNKNOWN         = 'Unknown Partner';
    const EXPEDIA_COLLECT_TEXT         = 'expedia collect';

    const GINOSI_PARTNER_WEBSITE              = 1;
    const GINOSI_EMPLOYEE                     = 3;
    const GINOSI_CONTACT_CENTER               = 5;
    const PARTNER_DEDUCTED_COMMISSION = 0;

    const GINOSI_EMPLOYEE_NAME        = 'GINOSI Employee';
    const GINOSI_PARTNER_WEBSITE_NAME = 'GINOSI Website';
    const GINOSI_PARTNER_STAFF_NAME   = 'Staff';
    /**
     * @return array
     */
	public static function getBusinessModels()
    {
		return [
            self::BUSINESS_MODEL_GINOSI_COLLECT                 => 'Ginosi Collect from Guest',
            self::BUSINESS_MODEL_PARTNER_COLLECT_GUEST_INVOICE  => 'Partner Collects from Guest (Invoice)',
            self::BUSINESS_MODEL_GINOSI_COLLECT_PARTNER         => 'Ginosi Collects from Partner (Virtual Card)',
            self::BUSINESS_MODEL_PARTNER_COLLECT_GUEST_TRANSFER => 'Partner Collects from Guest ( Bank Transfer )',
		];
	}

    /**
     * @return array
     */
    public static function partnerBusinessModel()
    {
        return [
            self::BUSINESS_MODEL_PARTNER_COLLECT_GUEST_INVOICE,
            self::BUSINESS_MODEL_GINOSI_COLLECT_PARTNER,
            self::BUSINESS_MODEL_PARTNER_COLLECT_GUEST_TRANSFER,
        ];
    }

    public function partnersList($start, $limit, $sortCol, $sortDir, $search, $all)
    {
        $partnersDao = $this->getPartnersDao();

        return $partnersDao->getPartnersList($start, $limit, $sortCol, $sortDir, $search, $all);
    }

    public function getPartnerlist()
    {
        $partnersDao = $this->getPartnersDao();
        $partners = $partnersDao->getPartners();
        $partnerList = [];

        if ($partners->count()) {
            foreach ($partners as $partner) {
                array_push($partnerList, [
                    'id' => $partner->getGid(),
                    'name' => $partner->getPartnerName(),
                ]);
            }
        }

        return $partnerList;
    }

    /**
     * @return array
     */
    public function getActivePartnerList()
    {
        $partnersDao = $this->getPartnersDao();
        $partners = $partnersDao->getPartners();
        $partnerList = [];

        if ($partners->count()) {
            foreach ($partners as $partner) {
                $partnerList[$partner->getGid()] = $partner->getPartnerName();
            }
        }

        return $partnerList;
    }

    /**
     * Return only partners with Partner Collect business model
     * @return array
     */
    public function getActivePartnerFilteredList()
    {
        $partnersDao = $this->getPartnersDao();
        $partners = $partnersDao->getPartnersFiltered();
        $partnerList = [];

        if ($partners->count()) {
            foreach ($partners as $partner) {
                $partnerList[$partner->getGid()] = $partner->getPartnerName();
            }
        }

        return $partnerList;
    }

    public function partnersCount($search, $all)
    {
        $partnersDao = $this->getPartnersDao();

        return $partnersDao->getPartnersCount($search, $all);
    }

    /**
     * @param int $id
     * @return \DDD\Domain\Partners\Partners
     */
    public function partnerById($id)
    {
        return $this->getPartnersDao()->getPartnerById($id);
    }

    public function addPartner($params)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var TransactionAccounts $transactionAccountDao
         */
        $partnersDao = $this->getPartnersDao();
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');

        if (!$auth->hasRole(Roles::ROLE_PARTNER_DISCOUNTS)) {
            $params['discount_num'] = 0;
        }

        $dataUpdate = [
            'account_holder_name'       => $params['account_holder_name'],
            'bank_account_num'          => $params['bank_account_num'],
            'partner_name'              => $params['partner_name'],
            'contact_name'              => $params['contact_name'],
            'commission'                => $params['commission'],
            'additional_tax_commission' => $params['additional_tax_commission'],
            'bank_bsr'                  => $params['bank_bsr'],
            'mobile'                    => $params['mobile'],
            'is_ota'                    => $params['is_ota'],
            'email'                     => $params['email'],
            'phone'                     => $params['phone'],
            'notes'                     => $params['notes'],
            'discount'                  => $params['discount_num'],
            'create_date'               => date('Y-m-d'),
            'active'                    => 1,
            'show_partner'              => $params['show_partner'],
            'apply_fuzzy_logic'         => $params['apply_fuzzy_logic'],
            'is_deducted_commission'    => $params['is_deducted_commission'],
        ];

        try {
            $partnersDao->beginTransaction();

            $partnerId = $partnersDao->save($dataUpdate);
            $this->savePartnerAccounts($partnerId, $params['cubilis_id']);

            if (!empty($params['commission'])) {
                $transactionAccountDao->save([
                    'type' => Account::TYPE_PARTNER,
                    'holder_id' => $partnerId,
                ]);
            }

            $partnersDao->commitTransaction();
        } catch (\Exception $ex) {
            $partnersDao->rollbackTransaction();

            return false;
        }

        return true;
    }

    public function savePartner($params, $userId = null)
    {
        /**
         * @var \Library\ActionLogger\Logger $actionLogger
         */
        $partnersDao        = $this->getPartnersDao();
        $partnerAccountDao  = $this->getPartnerAccountDao();
        $actionLogger       = $this->getServiceLocator()->get('ActionLogger');
        $partnerCurrentData = $this->partnerById($params['gid']);
        $auth               = $this->getServiceLocator()->get('library_backoffice_auth');

        if (!$partnerCurrentData) {
            return false;
        }

        try {
            $partnersDao->beginTransaction();

            // check for change Partner's name
            if ($params['partner_name'] != $partnerCurrentData->getPartnerName()) {
                $msg = "Partner's name change from '{$partnerCurrentData->getPartnerName()}' to '{$params['partner_name']}";
                $actionLogger->save(ActionLogger::MODULE_PARTNERS, $partnerCurrentData->getGid(), ActionLogger::ACTION_PARTNER_NAME, $msg);
            }


            if (!$auth->hasRole(Roles::ROLE_PARTNER_DISCOUNTS)) {
                $params['discount_num'] = $partnerCurrentData->getDiscount();
            }

            if ($params['gid'] == self::GINOSI_EMPLOYEE) {
                $params['discount_num'] = 25;
            }

            if ($partnerCurrentData->getDiscount() != $params['discount_num']) {
                $usermanagerDao = $this->getServiceLocator()->get('dao_user_user_manager');
                $userInfo       = $usermanagerDao->fetchOne(['id' => $userId]);

                $message = $userInfo->getFirstname() . ' ' . $userInfo->getLastname() .
                    ' has changed the <b>discount</b> from <b>' .
                    $partnerCurrentData->getDiscount() . '</b> to <b>' . $params['discount_num'] . '</b>.';

                $actionLogger->save(ActionLogger::MODULE_PARTNERS, $partnerCurrentData->getGid(), ActionLogger::ACTION_PARTNER_DISCOUNT, $message);
            }

            $dataUpdate = [
                'partner_name'              => $params['partner_name'],
                'business_model'            => $params['business_model'],
                'contact_name'              => $params['contact_name'],
                'email'                     => $params['email'],
                'mobile'                    => $params['mobile'],
                'phone'                     => $params['phone'],
                'commission'                => $params['commission'],
                'additional_tax_commission' => $params['additional_tax_commission'],
                'bank_bsr'                  => $params['bank_bsr'],
                'bank_account_num'          => $params['bank_account_num'],
                'notes'                     => $params['notes'],
                'is_ota'                    => $params['is_ota'],
                'account_holder_name'       => $params['account_holder_name'],
                'discount'                  => $params['discount_num'],
                'show_partner'              => $params['show_partner'],
                'apply_fuzzy_logic'         => $params['apply_fuzzy_logic'],
                'is_deducted_commission'    => ($params['business_model'] == self::BUSINESS_MODEL_GINOSI_COLLECT) ? 0 : $params['is_deducted_commission'],
            ];

            $partnersDao->save($dataUpdate, ['gid' => $params['gid']]);
            $partnerAccountDao->delete(['partner_id' => $params['gid']]);
            $this->savePartnerAccounts($params['gid'], $params['cubilis_id']);

            if (!empty($params['commission']) && !$partnerCurrentData->getCommission()) {
                /** @var \DDD\Dao\Finance\Transaction\TransactionAccounts $transactionAccountDao */
                $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
                $transactionAccountData = [[
                    'type' => Account::TYPE_PARTNER,
                    'holder_id' => $params['gid'],
                ]];

                // Using multiinsert method for it's IGNORE feature
                // Standard insert method does not support that feature
                $transactionAccountDao->multiInsert($transactionAccountData, true);
            }

            $partnersDao->commitTransaction();
        } catch (\Exception $ex) {
            $partnersDao->rollbackTransaction();

            return false;
        }

        return true;
    }

    public function changeStatus($partnerId, $status)
    {
        /**
         * @var \Library\ActionLogger\Logger $actionLogger
         */
        $actionLogger = $this->getServiceLocator()->get('ActionLogger');
        $partnersDao = $this->getPartnersDao();

        try {
            $partnerCurrentData = $this->partnerById($partnerId);
            $statusChange = 'Activated';

            if ($status == '0') {
                $statusChange = 'Deactivated';
            }

            $actionLogger->save(ActionLogger::MODULE_PARTNERS, $partnerCurrentData->getGid(), ActionLogger::ACTION_PARTNER_STATUS, "Partner's status is set as {$statusChange}");
            $partnersDao->save(['active' => (int)$status], ['gid' => $partnerId]);
        } catch (\Exception $ex) {
            return false;
        }

		return true;
    }

    /**
     * @param int $partnerID
     * @var \DDD\Dao\Partners\PartnerAccount
     * @return string
     */
    public function getPartnerAccounts($partnerID)
    {
    	$partnerAccountDao = $this->getPartnerAccountDao();
    	$accounts = $partnerAccountDao->getPartnerAccounts($partnerID);

    	$commaSeparatedString = '';

    	foreach ($accounts as $account) {
    		$commaSeparatedString .= $account['cubilis_id'] . ',';
    	}

        return trim($commaSeparatedString, ',');
    }

    /**
     * @param int $partnerId
     * @return \DDD\Dao\Partners\PartnerAccount
     */
    public function removePartnerAccounts($partnerId)
    {
    	$partnerAccountDao = $this->getPartnerAccountDao();
    	$result = $partnerAccountDao->delete(['partner_id' => $partnerId]);

    	return $result;
    }

    /**
     * @param int $partnerId
     * @var \DDD\Dao\Partners\PartnerAccount
     */
    public function savePartnerAccounts($partnerId, $commaSeparatedValues)
    {
    	$partnerAccountDao = $this->getPartnerAccountDao();
    	$commaSeparatedValues = str_replace(' ', '', $commaSeparatedValues);
    	$partnerAccounts = explode(',', $commaSeparatedValues);

    	foreach ($partnerAccounts as $partnerAccountId) {
    		if ($partnerAccountId != '' && is_numeric($partnerAccountId)) {
    			$account = array(
                    'partner_id' => $partnerId,
                    'cubilis_id' => $partnerAccountId,
                    'title' => '',
    			);

    			$partnerAccountDao->save($account);
    		}
    	}
    }

    /**
     * Get partner dao instance via service locator
     * @return \DDD\Dao\Partners\Partners
     */
    private function getPartnersDao()
    {
    	return $this->getServiceLocator()->get('dao_partners_partners');
    }

    /**
     * Get partner account dao instance via service locator
     * @return \DDD\Dao\Partners\PartnerAccount
     */
    private function getPartnerAccountDao()
    {
    	return $this->getServiceLocator()->get('dao_partners_partner_account');
    }

    /**
     *
     * @param int $partnerId
     * @return \ArrayObject|\ArrayObject[]
     */
    public function getPartnerLogs($partnerId)
    {
        /**
         * @var \DDD\Dao\ActionLogs\ActionLogs $actionLogsDao
         */
        $actionLogsDao = $this->getServiceLocator()->get('dao_action_logs_action_logs');

        return $actionLogsDao->getByPartnerId($partnerId);
    }

    /**
     * @param $partnerId
     * @param $apartmentId
     * @param bool $isOurPartnerId
     * @param string $commentForPars
     * @return array|\ArrayObject|PartnerBooking|null
     */
    public function getPartnerDataForReservation($partnerId, $apartmentId, $isOurPartnerId = false, $commentForPars = '')
    {
        /**
         * @var \DDD\Dao\Partners\PartnerCityCommission $partnerCityCommissionDao
         */
        $partnerDao = $this->getPartnersDao();
        $partnerCityCommissionDao = $this->getServiceLocator()->get('dao_partners_partner_city_commission');
        $partnerData = $partnerDao->getPartnerDataForReservation($partnerId, $isOurPartnerId);

        if (!$partnerData) {
            $this->gr2emerg("Partner not defined, therefore Unknown Partner with commission was selected respectively", [
                'partner_id' => $partnerId,
            ]);

            $domain = new PartnerBooking();
            $domain->setBusinessModel(self::BUSINESS_MODEL_GINOSI_COLLECT);
            $domain->setPartnerName(self::PARTNER_NAME_UNKNOWN);
            $domain->setCommission(self::PARTNER_UNKNOWN_COMMISSION);
            $domain->setGid(self::PARTNER_UNKNOWN);

            return $domain;
        }

        // change partner model if Expedia and Expedia collect
        if ($partnerData->getGid() == self::PARTNER_EXPEDIA && $commentForPars && strpos(strtolower($commentForPars), self::EXPEDIA_COLLECT_TEXT) !== false) {
            $partnerData = $partnerDao->getPartnerModel(self::PARTNER_EXPEDIA_COLLECT);

            if (!$partnerData) {
                $this->gr2emerg("Partner not defined, therefore Unknown Partner with commission was selected respectively", [
                    'partner_id' => $partnerId,
                ]);

                $domain = new PartnerBooking();
                $domain->setBusinessModel(self::BUSINESS_MODEL_GINOSI_COLLECT);
                $domain->setPartnerName(self::PARTNER_NAME_UNKNOWN);
                $domain->setCommission(self::PARTNER_UNKNOWN_COMMISSION);
                $domain->setGid(self::PARTNER_UNKNOWN);

                return $domain;
            }
        }

        // check partner is special partner city commission
        $specialPartnerCityCommission = $partnerCityCommissionDao->getPartnerCityCommissionByPartnerIdApartmentId($partnerData->getGid(), $apartmentId);

        if ($specialPartnerCityCommission) {
            $partnerData->setCommission($specialPartnerCityCommission);
        }

        return $partnerData;
    }

    /**
     * @param int $partnerId
     * @param int $cityId
     * @param float $commission
     * @return array
     */
    public function savePartnerCityCommission($partnerId, $cityId, $commission)
    {
        /**
         * @var \DDD\Dao\Partners\PartnerCityCommission $partnerCityCommissionDao
         */
        $partnerCityCommissionDao = $this->getServiceLocator()->get('dao_partners_partner_city_commission');

        if ($partnerCityCommissionDao->fetchOne(['partner_id' => $partnerId, 'city_id' => $cityId])) {
            return [
                'status' => 'error',
                'msg' => TextConstants::ERROR_ALREADY_EXIST_PARTNER_CITY,
            ];
        }

        $partnerCityCommissionDao->save([
            'partner_id' => $partnerId,
            'city_id' => $cityId,
            'commission' => $commission
        ]);

        return ['status' => 'success'];
    }

    public function searchContacts($searchQuery)
    {
        /**
         * @var \DDD\Dao\Partners\Partners $partnersDao
         */
        $partnersDao = $this->getServiceLocator()->get('dao_partners_partners');
        $result = $partnersDao->searchContacts($searchQuery);
        $resultArray = [];
        foreach ($result as $row) {
            $contactName = ($row->getContactName()) ? ', ' . $row->getContactName() : '';
            array_push($resultArray,
                [
                    'id'    => $row->getGid() . '_' . Contact::TYPE_PARTNER,
                    'type'  => Contact::TYPE_PARTNER,
                    'label'  => Contact::LABEL_NAME_PARTNER,
                    'labelClass'  => Contact::LABEL_CLASS_PARTNER,
                    'text'  => $row->getPartnerName() . $contactName,
                    'info'  =>''
                ]);
        }

        return $resultArray;

    }
}
