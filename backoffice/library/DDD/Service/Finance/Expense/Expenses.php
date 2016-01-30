<?php

namespace DDD\Service\Finance\Expense;

use DDD\Service\ServiceBase;

class Expenses extends ServiceBase
{
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
}
