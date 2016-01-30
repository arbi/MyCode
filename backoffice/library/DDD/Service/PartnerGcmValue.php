<?php

namespace DDD\Service;

use DDD\Service\Website\Textline;

class PartnerGcmValue extends ServiceBase
{
    /**
     * @param  $partnerId
     * @return array
     */
	public function getByPartnerId($partnerId)
    {
        $partnerGcmValuesDao = $this->getServiceLocator()->get('dao_partners_partner_gcm_value');

		return $partnerGcmValuesDao->getByPartnerId($partnerId);
	}

    /**
     * @param $params
     * @param $partnerId
     * @return bool|int
     */
    public function saveValues($params, $partnerId)
    {
        /* @var \DDD\Dao\Partners\PartnerGcmValue $partnerGcmValuesDao */
        $partnerGcmValuesDao = $this->getServiceLocator()->get('dao_partners_partner_gcm_value');

        try {
            $partnerGcmValuesDao->beginTransaction();

            $saveData = [];

            if (isset($params['keys']) && !empty($params['keys'])) {
                foreach ($params['keys'] as $pKey => $key) {
                    $value = trim($params['values'][$pKey]);
                    $key   = trim($key);

                    if (empty($value) || empty($key)) {
                        continue;
                    }

                    array_push($saveData, [
                        'key'           => $key,
                        'value'         => $params['values'][$pKey],
                        'partner_id'    => $partnerId,
                    ]);
                }
            }

            $partnerGcmValuesDao->delete(['partner_id' => $partnerId]);

            $result = true;
            if (!empty($saveData)) {
                $result = $partnerGcmValuesDao->multiInsert($saveData);
            }
            $partnerGcmValuesDao->commitTransaction();

            return $result;
        } catch (\Exception $e) {
            $this->gr2logException($e);
            $partnerGcmValuesDao->rollbackTransaction();
        }

        return false;
    }
}
