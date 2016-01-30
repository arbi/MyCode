<?php

namespace DDD\Domain\Booking;

/**
 * Class SaleStatisticsRow
 * @package DDD\Domain\Booking
 *
 * @author Tigran Petrosyan
 */
class SaleStatisticsRow
{

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $totalAmount;

    /**
     * @var int
     */
    protected $count;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->label        = (isset($data['label'])) ? $data['label'] : null;
        $this->totalAmount  = (isset($data['total_amount'])) ? $data['total_amount'] : null;
        $this->count        = (isset($data['count'])) ? $data['count'] : null;
    }

    /**
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $totalAmount
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
    }

    /**
     * @return string
     */
    public function getTotalAmount()
    {
        return number_format($this->totalAmount, 2);
    }
}
