<?php

namespace DDD\Service\Finance;

use DDD\Service\ServiceBase;
use Library\Finance\Finance;
use Library\Utility\Debug;
use Zend\Db\Adapter\Adapter;

/**
 * Service class providing methods to work with suppliers
 * @author Tigran Ghabuzyan
 */
final class Suppliers extends ServiceBase
{
    /**
     * @param int $start
     * @param int $limit
     * @param string $sortCol
     * @param string $sortDir
     * @param string $search
     * @param string $all
     * @return \DDD\Domain\Finance\Supplier
     */
    public function getSuppliersList($start, $limit, $sortCol, $sortDir, $search, $all)
    {
        /**
         * @var \DDD\Dao\Finance\Supplier $supplierDao
         */
        $supplierDao = $this->getServiceLocator()->get('dao_finance_supplier');

        return $supplierDao->getSuppliersList($start, $limit, $sortCol, $sortDir, $search, $all);
    }

    /**
     * @param string $keyword
     * @return array|array[]
     */
    public function getCostCentersAutocomplete($keyword)
    {
        /**
         * @var Adapter $dbAdapter
         */
        $dbAdapter = $this->getServiceLocator()->get('dbadapter');
        $statement = $dbAdapter->createStatement("
            (
                select
                    ga_apartments.id as id,
                    'Apartment' as label,
                    1 as type,
                    ga_apartments.name as name,
		            ga_apartments.currency_id as currency_id
                from ga_apartments
                where status <> 9 and name like '%{$keyword}%'
            )
            union
            (
                select
                    ga_office_sections.id as id,
                    ga_offices.name as label,
                    2 as type,
                    ga_office_sections.name as name,
		            ga_countries.currency_id as currency_id
                from ga_offices
                    right join ga_office_sections on ga_office_sections.office_id = ga_offices.id
		            left join ga_countries on ga_countries.id = ga_offices.country_id
                where ga_office_sections.disable = 0
                    and (ga_offices.name like '%{$keyword}%' or ga_office_sections.name like '%{$keyword}%')
            )
            union
            (
                select
                    ga_apartment_groups.id as id,
                    'Group' as label,
                    3 as type,
                    ga_apartment_groups.name as name,
                    ga_countries.currency_id as currency_id
                from ga_apartment_groups
                    left join ga_countries on ga_countries.id = ga_apartment_groups.country_id
                where ga_apartment_groups.name like '%{$keyword}%' and usage_cost_center = 1
            );
        ");

        $result = $statement->execute();
        $resultList = [];

        if ($result->count()) {
            foreach ($result as $costCenter) {
                array_push($resultList, array_merge_recursive($costCenter, [
                    'unique_id' => $costCenter['type'] . '_' . $costCenter['id'],
                ]));
            }
        }

        return $resultList;
    }

    /**
     * @param string $search
     * @param bool $all
     * @return \DDD\Domain\Finance\Supplier
     */
    public function getSuppliersCount($search, $all)
    {
        /**
         * @var \DDD\Dao\Finance\Supplier $supplierDao
         */
        $supplierDao = $this->getServiceLocator()->get('dao_finance_supplier');

        return $supplierDao->getSuppliersCount($search, $all);
    }

    /**
     * @param int $id
     * @return \DDD\Domain\Finance\Supplier
     */
    public function getSupplierById($id)
    {
        $supplierDao = $this->getServiceLocator()->get('dao_finance_supplier');

        return $supplierDao->getSupplierById($id);
    }

    /**
     * @param int $supplierId
     * @param array $data
     * @return \DDD\Domain\Finance\Supplier
     */
    public function saveSupplier($data, $supplierId)
    {
        $data['name'] = trim($data['name']);

        $finance      = new Finance($this->getServiceLocator());
        $supplier     = $finance->getSupplier($supplierId ? : null);
        $supplierData = [
            'name'        => $data['name'],
            'description' => $data['description'],
        ];

        if ($supplierId) {
            $supplier->save($supplierData);
        } else {
            $supplier->create($supplierData);
        }
    }

    /**
     * @param string $name
     * @param int $status
     * @param int $id
     * @return \DDD\Domain\Finance\Supplier
     */
    public function hasActiveOrInactiveDuplicate($name, $id, $status)
    {
        $name = trim($name);
        $supplierDao = $this->getServiceLocator()->get('dao_finance_supplier');
        $activeOrInactiveDuplicateSupplierExistsOrNot = $supplierDao->activeOrInactiveDuplicateSupplierExistsOrNot($name,$id,$status);
        return $activeOrInactiveDuplicateSupplierExistsOrNot;
    }

    /**
     * @param int $status
     * @param int $supplierId
     * @return boolean
     */
    public function changeStatus($supplierId, $status)
    {
        $finance  = new Finance($this->getServiceLocator());
        $supplier = $finance->getSupplier($supplierId);

        $supplier->save(['active' => $status]);

        return true;
    }

    public function getSupplierList()
    {
        /**
         * @var \DDD\Dao\Finance\Supplier $supplierDao
         */
        $supplierDao = $this->getServiceLocator()->get('dao_finance_supplier');
        $suppliers = $supplierDao->getForSelect();
        $supplierList = [];

        if ($suppliers->count()) {
            foreach ($suppliers as $supplier) {
                $supplierList[$supplier->getId()] = $supplier->getName();
            }
        }

        return $supplierList;
    }
}
