<?php

namespace DDD\Service\Finance\Expense;

use DDD\Service\ServiceBase;
use Library\Constants\Roles;
use Library\Constants\Constants;

class ExpenseCosts extends ServiceBase
{
    const TYPE_APARTMENT = 1;
    const TYPE_OFFICE_SECTION = 2;
    const TYPE_GROUP = 3; // should be converted to apartment or office section

    /**
     * @param int $apartmentId
     * @param int $offset
     * @param int $limit
     * @param int $sortCol
     * @param string $sortDir
     * @param string $like
     * @return array|bool
     */
    public function getDatatableData($apartmentId, $offset, $limit, $sortCol, $sortDir, $like = '')
    {
        try {
            /**
             * @var \DDD\Dao\Finance\Expense\ExpenseCost $expenseDao
             */
            $expenseDao = $this->getServiceLocator()->get('dao_finance_expense_expense_cost');

            $costsResult = $expenseDao->getApartmentCostsForDatatable($apartmentId, $offset, $limit, $sortCol, $sortDir, $like);

            $costsData  = $costsResult['costs_data'];
            $costsTotal = $costsResult['total_count'];

            $data = [];
            if ($costsData->count()) {
                /**
                 * @var \Library\Authentication\BackofficeAuthenticationService $authService
                 */
                $authService = $this->getServiceLocator()->get('library_backoffice_auth');

                foreach ($costsData as $cost) {
                    $viewUrl = '/finance/purchase-order/edit/' . $cost['expense_id'];

                    $view = '<a class="btn btn-xs btn-primary" href="' . $viewUrl . '" target="_blank">View</a>';

                    $rows = [
                        $cost['id'],
                        $cost['category'],
                        date(Constants::GLOBAL_DATE_FORMAT, strtotime($cost['date'])),
                        $cost['amount'],
                        '<p class="crop">' . $cost['purpose'] . '</p>',
                    ];

                    if ($authService->hasRole(Roles::ROLE_EXPENSE_MANAGEMENT)) {
                        array_push($rows, $view);
                    }

                    array_push($data, $rows);
                }
            }

            return [
                'data'  => $data,
                'total' => $costsTotal
            ];

        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot get expenses datatable data', [
                'apartment_id' => $apartmentId,
                'offset'       => $offset,
                'limit'        => $limit,
                'sort_column'  => $sortCol,
                'sort_dir'     => $sortDir,
                'like'         => $like
            ]);
        }

        return false;
    }

}
