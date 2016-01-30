<?php

namespace DDD\Service\User;

use DDD\Dao\Finance\Expense\ExpenseItem;
use DDD\Dao\MoneyAccount\MoneyAccountUsers;
use DDD\Dao\User\Dashboards;
use DDD\Dao\User\UserGroups;
use DDD\Service\ServiceBase;
use DDD\Service\User;

/**
 * Class Permissions
 * @package DDD\Service\User
 *
 * @author Tigran Petrosyan
 */
class Permissions extends ServiceBase
{
    const PERMISSION_TYPE_FUNCTION = 0;
    const PERMISSION_TYPE_MODULE   = 1;
    const PERMISSION_TYPE_ROLE     = 2;

    /**
     * @todo: that for type=3 ???
     *
     * Temporary named as "Personal". But displaying in front as "Function", as is view in User Management Permissions list.
     */
    const PERMISSION_TYPE_PERSONAL = 3;

    public static $permissionTypes = [
        self::PERMISSION_TYPE_FUNCTION => 'Function',
        self::PERMISSION_TYPE_MODULE   => 'Module',
        self::PERMISSION_TYPE_ROLE     => 'Role',
        self::PERMISSION_TYPE_PERSONAL => 'Function',
    ];

    /**
     * @param $backofficeUserId
     * @return bool
     */
    public function takeAllRoles($backofficeUserId)
    {
        /**
         * @var UserGroups $userRolesDao
         */
        $userRolesDao = $this->getServiceLocator()->get('dao_user_user_groups');

        $userRolesDao->delete(
            ['user_id' => $backofficeUserId]
        );

        return true;
    }

    /**
     * @param $backofficeUserId
     * @return bool
     */
    public function takeAllDashboardsAccess($backofficeUserId)
    {
        /**
         * @var Dashboards $userDashboardsDao
         */
        $userDashboardsDao = $this->getServiceLocator()->get('dao_user_user_dashboards');

        $userDashboardsDao->delete(
            ['user_id' => $backofficeUserId]
        );

        return true;
    }

    /**
     * @param $backofficeUserId
     * @return bool
     */
    public function takeAllMoneyAccountsAccess($backofficeUserId)
    {
        /**
         * @var MoneyAccountUsers $moneyAccountUsersDao
         */
        $moneyAccountUsersDao = $this->getServiceLocator()->get('dao_money_account_money_account_users');

        $moneyAccountUsersDao->delete(
            ['user_id' => $backofficeUserId]
        );

        return true;
    }

    /**
     * @param $backofficeUserId
     * @return bool
     */
    public function takeAllConciergeDashboardsAccess($backofficeUserId)
    {
        /**
         * @var \DDD\Dao\ApartmentGroup\ConciergeDashboardAccess $conciergeDashboardAccessDao
         */
        $conciergeDashboardAccessDao = $this->getServiceLocator()->get('dao_apartment_group_concierge_dashboard_access');

        $conciergeDashboardAccessDao->delete(
            ['user_id' => $backofficeUserId]
        );

        return true;
    }

    /**
     * @param int $backofficeUserId
     * @return bool
     */
    public function changePOItemManager($backofficeUserId)
    {
        /**
         * @var User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');
        $poItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');

        $poItemDao->save(['manager_id' => $userService->getBudgetHolderUserManagerId($backofficeUserId)], ['manager_id' => $backofficeUserId]);
    }
}
