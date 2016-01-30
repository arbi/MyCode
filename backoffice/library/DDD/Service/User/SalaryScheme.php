<?php

namespace DDD\Service\User;

use DDD\Service\User as UserBase;
use Zend\Db\Sql\Where;

/**
 * Class    SalaryScheme
 * @package DDD\Service\User\SalaryScheme
 * @author  Harut Grigoryan
 */
class SalaryScheme extends UserBase
{
    /**
     * Statuses
     */
    const SALARY_SCHEME_STATUS_ACTIVE   = 1;
    const SALARY_SCHEME_STATUS_INACTIVE = 2;
    const SALARY_SCHEME_STATUS_ARCHIVED = 3;
    /**
     * Scheme types
     */
    const SALARY_SCHEME_TYPE_LOAN         = 1;
    const SALARY_SCHEME_TYPE_SALARY       = 2;
    const SALARY_SCHEME_TYPE_COMPENSATION = 3;
    /**
     * Pay types
     */
    const SALARY_SCHEME_PAY_FREQUENCY_TYPE_WEEKLY    = 1;
    const SALARY_SCHEME_PAY_FREQUENCY_TYPE_BI_WEEKLY = 2;
    const SALARY_SCHEME_PAY_FREQUENCY_TYPE_MONTHLY   = 3;

    /**
     * @param  array $params
     * @return mixed
     */
    public function getSalarySchemesByParams($params = []) {
        /**
         * @var \DDD\Dao\User\SalaryScheme $salarySchemeDao
         */
        $salarySchemeDao = $this->getServiceLocator()->get('dao_user_salary_scheme');

        return $salarySchemeDao->getSalarySchemesByParams($params);
    }
}
