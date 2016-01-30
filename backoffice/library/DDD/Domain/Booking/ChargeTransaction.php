<?php

namespace DDD\Domain\Booking;

class ChargeTransaction
{
    protected $id;
    protected $res_number;
    protected $money_account_id;
    protected $money_transaction_id;
    protected $date;
    protected $bank_amount;
    protected $money_account_currency;
    protected $bank_rate;
    protected $rrn;
    protected $auth_code;
    protected $error_code;
    protected $status;
    protected $user_id;
    protected $comment;
    protected $bank;
    protected $user;
    protected $type;
    protected $acc_amount;
    protected $cacheuser;
    protected $terminal;
    protected $psp_name;
    protected $symbol;

    /**
     * @var int
     */
    protected $apartmentId;
    protected $ccId;
    protected $reservation_id;
    protected $exact_expense_id;

    /**
     * Can be 2 (Ginosi Collect) or 3 (Partner Collect)
     * @var int
     */
    protected $moneyDirection;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->money_account_id = (isset($data['money_account_id'])) ? $data['money_account_id'] : null;
        $this->money_transaction_id = (isset($data['money_transaction_id'])) ? $data['money_transaction_id'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->bank_amount = (isset($data['bank_amount'])) ? $data['bank_amount'] : null;
        $this->money_account_currency = (isset($data['money_account_currency'])) ? $data['money_account_currency'] : null;
        $this->bank_rate = (isset($data['bank_rate'])) ? $data['bank_rate'] : null;
        $this->rrn = (isset($data['rrn'])) ? $data['rrn'] : null;
        $this->auth_code = (isset($data['auth_code'])) ? $data['auth_code'] : null;
        $this->error_code = (isset($data['error_code'])) ? $data['error_code'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->user_id = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->comment = (isset($data['comment'])) ? $data['comment'] : null;
        $this->bank = (isset($data['bank'])) ? $data['bank'] : null;
        $this->user = (isset($data['user'])) ? $data['user'] : null;
        $this->type = (isset($data['type'])) ? $data['type'] : null;
        $this->acc_amount = (isset($data['acc_amount'])) ? $data['acc_amount'] : null;
        $this->cacheuser = (isset($data['cacheuser'])) ? $data['cacheuser'] : null;
        $this->terminal = (isset($data['terminal'])) ? $data['terminal'] : null;
        $this->moneyDirection = (isset($data['money_direction'])) ? $data['money_direction'] : null;
        $this->psp_name = (isset($data['psp_name'])) ? $data['psp_name'] : null;
        $this->symbol = (isset($data['symbol'])) ? $data['symbol'] : null;
        $this->apartmentId = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
        $this->ccId = (isset($data['cc_id'])) ? $data['cc_id'] : null;
        $this->reservation_id = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
        $this->exact_expense_id = (isset($data['exact_expense_id'])) ? $data['exact_expense_id'] : null;
    }

    public function getExactExpenseId()
    {
        return $this->exact_expense_id;
    }

    public function getReservationId()
    {
        return $this->reservation_id;
    }

    public function getPspName()
    {
        return $this->psp_name;
    }

    public function getTerminal()
    {
        return $this->terminal;
    }

    public function getCacheuser()
    {
        return $this->cacheuser;
    }

    public function getAcc_amount()
    {
        return $this->acc_amount;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getBank()
    {
        return $this->bank;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getReservationNumber()
    {
        return $this->res_number;
    }

    public function getMoneyAccountId()
    {
        return $this->money_account_id;
    }

    public function getMoneyTransactionId()
    {
        return $this->money_transaction_id;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getBank_amount()
    {
        return $this->bank_amount;
    }

    public function getMoneyAccountCurrency()
    {
        return $this->money_account_currency;
    }

    public function getBank_rate()
    {
        return $this->bank_rate;
    }

    public function getRrn()
    {
        return $this->rrn;
    }

    public function getAuth_code()
    {
        return $this->auth_code;
    }

    public function getError_code()
    {
        return $this->error_code;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getIsVirtual()
    {
        return is_null($this->money_transaction_id);
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Returns money direction number, can be 2 (Ginosi Collect) or 3 (Partner Collect)
     * @return number
     */
    public function getMoneyDirection()
    {
        return $this->moneyDirection;
    }

    /**
     * @return mixed
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * @return int
     */
    public function getApartmentId()
    {
        return $this->apartmentId;
    }

    public function getCCId()
    {
        return $this->ccId;
    }
}
