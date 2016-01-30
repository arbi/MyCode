<?php

namespace DDD\Domain\MoneyAccount;

use DDD\Service\MoneyAccount AS MoneyAcService;

class MoneyAccount {
    protected $id;
    protected $active;
    protected $balance;
    protected $name;
    protected $currency_id;
    protected $currency_name;
    protected $country_id;
    protected $cname;
    protected $gateway;
    protected $rrn;
    protected $auth;
    protected $error_code;
    protected $terminal;
    protected $type;
    protected $responsiblePersonId;
    protected $bank_id;
    protected $bank_name;
    protected $bank_routing;
    protected $bank_account_number;
    protected $accountHolder;
    protected $address;
    protected $legalEntityId;

    /**
     * @var boolean $isManager
     */
    protected $isManager;
    /**
     * @var boolean $hasTransactionsView
     */
    protected $hasTransactionsView;

    public function exchangeArray($data) {
        $this->id                  = (isset($data['id'])) ? $data['id'] : null;
        $this->name                = (isset($data['name'])) ? $data['name'] : null;
        $this->active              = (isset($data['active'])) ? $data['active'] : null;
        $this->balance             = (isset($data['balance'])) ? $data['balance'] : null;
        $this->currency_id         = (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->currency_name       = (isset($data['currency_name'])) ? $data['currency_name'] : null;
        $this->country_id          = (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->cname               = (isset($data['cname'])) ? $data['cname'] : null;
        $this->gateway             = (isset($data['gateway'])) ? $data['gateway'] : null;
        $this->rrn                 = (isset($data['rrn'])) ? $data['rrn'] : null;
        $this->auth                = (isset($data['auth'])) ? $data['auth'] : null;
        $this->error_code          = (isset($data['error_code'])) ? $data['error_code'] : null;
        $this->terminal            = (isset($data['terminal'])) ? $data['terminal'] : null;
        $this->type                = (isset($data['type'])) ? $data['type'] : null;
        $this->responsiblePersonId = (isset($data['responsible_person_id'])) ? $data['responsible_person_id'] : null;
        $this->bank_id             = (isset($data['bank_id'])) ? $data['bank_id'] : null;
        $this->bank_name           = (isset($data['bank_name'])) ? $data['bank_name'] : null;
        $this->bank_routing        = (isset($data['bank_routing'])) ? $data['bank_routing'] : null;
        $this->bank_account_number = (isset($data['bank_account_number'])) ? $data['bank_account_number'] : null;
        $this->accountHolder       = (isset($data['account_holder'])) ? $data['account_holder'] : null;
        $this->address             = (isset($data['address'])) ? $data['address'] : null;
        $this->isManager           = (isset($data['is_manager'])) ? $data['is_manager'] : false;
        $this->hasTransactionsView = (isset($data['has_transactions_view'])) ? $data['has_transactions_view'] : false;
        $this->legalEntityId       = (isset($data['legal_entity_id'])) ? $data['legal_entity_id'] : null;
    }

    public function getBankId() {
        return $this->bank_id;
    }

	public function getBankName() {
		return $this->bank_name;
	}

	public function getCurrencyName() {
		return $this->currency_name;
	}

    public function getBalance() {
        return $this->balance;
    }

	public function getCountryId() {
		return $this->country_id;
	}

	public function getResponsiblePersonId() {
		return $this->responsiblePersonId;
	}

	public function getAddress() {
		return $this->address;
	}

	public function getBankAccountNumber() {
		return $this->bank_account_number;
	}

	public function getBankRouting() {
		return $this->bank_routing;
	}

	public function getCurrencyId() {
		return $this->currency_id;
	}

	public function getErrorCode() {
		return $this->error_code;
	}

	public function getType() {
		return $this->type;
	}

    public function getTerminal() {
        return $this->terminal;
    }

    public function getError_code() {
        return $this->error_code;
    }

    public function getAuth() {
        return $this->auth;
    }

    public function getRrn() {
        return $this->rrn;
    }

    public function getGateway() {
        return $this->gateway;
    }

    public function getCname() {
        return $this->cname;
    }

    public function setCname($val) {
        $this->cname = $val;

        return $this;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($val) {
        $this->name = $val;

        return $this;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($val) {
        $this->active = $val;

        return $this;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($val) {
        $this->id = $val;

        return $this;
    }

    public function getCurrency_id() {
        return $this->currency_id;
    }

    public function setCurrency_id($val) {
        $this->currency_id = $val;

        return $this;
    }

    public function getAccountHolder() {
        return $this->accountHolder;
    }

    public function setAccountHolder($val) {
        $this->accountHolder = $val;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLegalEntityId() {
        return $this->legalEntityId;
    }

    /**
     * @param $legalEntityId
     * @return $this
     */
    public function setLegalEntityId($legalEntityId) {
        $this->legalEntityId = $legalEntityId;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasTransactionsView()
    {
        return $this->hasTransactionsView;
    }

    /**
     * @return boolean
     */
    public function isManager()
    {
        return $this->isManager;
    }
}
