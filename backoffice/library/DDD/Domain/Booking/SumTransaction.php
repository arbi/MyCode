<?php

namespace DDD\Domain\Booking;

class SumTransaction
{
    protected $sum_acc;
    protected $sum_customer;
    public function exchangeArray($data)
    {
        $this->sum_acc = (isset($data['sum_acc'])) ? $data['sum_acc'] : null;
        $this->sum_customer = (isset($data['sum_customer'])) ? $data['sum_customer'] : null;
  }
  
  public function getSum_acc() {
      return $this->sum_acc;
  }   
  
  public function getSum_customer() {
      return $this->sum_customer;
  }   
}