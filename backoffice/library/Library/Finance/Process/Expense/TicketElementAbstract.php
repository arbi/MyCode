<?php

namespace Library\Finance\Process\Expense;

use Library\Utility\Debug;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class TicketElementAbstract implements ITicketElement
{
    use ServiceLocatorAwareTrait;

    const MODE_ADD = 1;
    const MODE_EDIT = 2;
    const MODE_DELETE = 3;

    /**
     * @var int $mode Processing Mode
     */
    protected $mode = self::MODE_ADD;

    /**
     * Ticket element id
     *
     * @var int
     */
    protected $id;

    /**
     * Ticket element specific data. For items also includes cost centers.
     *
     * @var array
     */
    protected $data = [];

    /**
     * @var null|\ArrayObject|array[]
     */
    protected $currencies = [];

    /**
     * @var array
     */
    protected $currenciesOptimized = [];

    /**
     * @return int
     */
    protected function getMode()
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     */
    protected function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return \ArrayObject|array[]
     */
    protected function getCurrencies()
    {
        return $this->currencies;
    }

    /**
     * @param \ArrayObject|array[] $currencies
     */
    protected function setCurrencies($currencies)
    {
        $this->currencies = $currencies;

        if ($currencies->count()) {
            foreach ($currencies as $currency) {
                $this->currenciesOptimized[$currency['id']] = $currency['value'];
            }
        }
    }

    /**
     * @return array
     */
    protected function getCurrenciesOptimized()
    {
        return $this->currenciesOptimized;
    }

    /**
     * @throws \Exception
     */
    protected function detectMode()
    {
        $data = $this->getData();
        $id = $this->getId();

        if (count($data) && is_null($id)) {
            $this->setMode(self::MODE_ADD);
        } elseif (count($data) && $id) {
        $this->setMode(self::MODE_EDIT);
        } elseif (!count($data) && $id) {
            $this->setMode(self::MODE_DELETE);
        } else {
            throw new \Exception('Transaction mode not defined');
        }
    }
}
