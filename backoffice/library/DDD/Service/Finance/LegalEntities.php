<?php

namespace DDD\Service\Finance;

use DDD\Service\ServiceBase;

/**
 * Service class providing methods to work with legal entities
 * @author Hrayr Papikyan
 */
final class LegalEntities extends ServiceBase
{
    /**
     * @param int $start
     * @param int $limit
     * @param string $sortCol
     * @param string $sortDir
     * @param string $search
     * @param string $all
     * @return \DDD\Domain\Finance\LegalEntities
     */
    public function getLegalEntitiesList($start, $limit, $sortCol, $sortDir, $search, $all)
    {
        /**
         * @var \DDD\Dao\Finance\LegalEntities $legalEntitiesDao
         */
        $legalEntitiesDao = $this->getServiceLocator()->get('dao_finance_legal_entities');

        return $legalEntitiesDao->getLegalEntitiesList($start, $limit, $sortCol, $sortDir, $search, $all);
    }

    /**
     * @param string $search
     * @param bool $all
     * @return \DDD\Domain\Finance\LegalEntities
     */
    public function getLegalEntitiesCount($search, $all)
    {
        /**
         * @var \DDD\Dao\Finance\LegalEntities $legalEntitiesDao
         */
        $legalEntitiesDao = $this->getServiceLocator()->get('dao_finance_legal_entities');

        return $legalEntitiesDao->getLegalEntitiesCount($search, $all);
    }

    /**
     * @param int $id
     * @return \DDD\Domain\Finance\LegalEntities
     */
    public function getLegalEntityById($id)
    {
        /**
         * @var \DDD\Dao\Finance\LegalEntities $legalEntitiesDao
         */
        $legalEntitiesDao = $this->getServiceLocator()->get('dao_finance_legal_entities');

        return $legalEntitiesDao->getLegalEntityById($id);
    }

    /**
     * @param int $legalEntityId
     * @param array $data
     * @return boolean
     */
    public function saveLegalEntity($data, $legalEntityId)
    {

        $legalEntitiesDao = $this->getServiceLocator()->get('dao_finance_legal_entities');
        $legalEntityData = [
            'name'        => $data['name'],
            'description' => $data['description'],
            'country_id'  => $data['country_id'],
        ];

        if ($legalEntityId) {
            $legalEntitiesDao->save($legalEntityData,['id' => $legalEntityId]);
        } else {
            $legalEntitiesDao->save($legalEntityData);
        }
        return true;
    }

    /**
     * @param int $status
     * @param int $legalEntityId
     * @return boolean
     */
    public function changeStatus($legalEntityId, $status)
    {
        $legalEntitiesDao = $this->getServiceLocator()->get('dao_finance_legal_entities');

        $legalEntitiesDao->save(['active' => $status], ['id' => $legalEntityId]);

        return true;
    }

    public function getLegalEntitiesForSelect(){
        $legalEntitiesDao = $this->getServiceLocator()->get('dao_finance_legal_entities');
        return $legalEntitiesDao->getForSelect();
    }

    public function checkName($id, $name, $countryId)
    {
        /**
         * @var \DDD\Dao\Finance\LegalEntities $legalEntitiesDao
         */

        $legalEntitiesDao = $this->getServiceLocator()->get('dao_finance_legal_entities');
        if ((int)$countryId) {
            return $legalEntitiesDao->checkName($id, $name, $countryId);
        }
    }

    public function getCountryNameByLegalEntityId($legalEntityId)
    {
        $legalEntitiesDao = $this->getServiceLocator()->get('dao_finance_legal_entities');
        return $legalEntitiesDao->getCountryNameByLegalEntityId($legalEntityId);
    }

}
