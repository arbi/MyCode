<?php

namespace DDD\Service\Reservation;

use DDD\Service\Partners as PartnerService;
use DDD\Service\ServiceBase;
use DDD\Service\Taxes;
use DDD\Service\ApartmentGroup as ApartmentGroupService;

/**
 * Class PartnerSpecific
 * @package DDD\Service\Reservation
 */
class PartnerSpecific extends ServiceBase
{

    /**
     * @param $cubilisPrice
     * @param $partner
     * @param $apartmentId
     * @param $capacity
     * @param $nightCount
     * @return bool|float
     */
    public function getBasePriceByFuzzyLogic($cubilisPrice, $partner, $apartmentId, $capacity, $nightCount)
    {
        // check partner from fuzzy logic list
        $partnerId = $partner['id'];

        /**
         * @var \DDD\Dao\Apartment\Details $apartmentDetailsDao
         * @var \DDD\Service\Partners $partnerService
         */
        $basePriceIntegerPart = 1;

        if ($partnerId == PartnerService::PARTNER_AGODA) {
            // calculate partner commission
            $partnerService = $this->getServiceLocator()->get('service_partners');
            $partnerData    = $partnerService->getPartnerDataForReservation($partnerId, $apartmentId, true);

            if ($partnerData) {
                $basePriceIntegerPart -= $partnerData->getCommission() * 0.01;
            }

            // calculate tax
            $taxData = $this->getTaxValueAndPercent ($apartmentId, $capacity, $basePriceIntegerPart);
            // tax is percent
            if ($taxData['taxPercent']) {
                $basePriceIntegerPart += $taxData['taxPercent'];
            }

            // tax is value
            if ($taxData['taxValue']) {
                $cubilisPrice -= $taxData['taxValue'];
            }

            // calculate cleaning fee
            $apartmentDetailsDao = $this->getServiceLocator()->get('dao_apartment_details');
            $cleaningFee = $apartmentDetailsDao->getCleaningFee($apartmentId);

            if ($cleaningFee) {
                $cubilisPrice -= $cleaningFee/$nightCount;
            }

            $ourPrice = round($cubilisPrice / $basePriceIntegerPart, 2);
            return $ourPrice;
        } elseif ($partnerId == PartnerService::PARTNER_ORBITZ) {
            // calculate partner commission
            $partnerService = $this->getServiceLocator()->get('service_partners');
            $partnerData = $partnerService->getPartnerDataForReservation($partnerId, $apartmentId, true);

            if ($partnerData) {
                $increasedPercent = 1 - $partnerData->getCommission() * 0.01;
                $ourPrice = round($cubilisPrice / $increasedPercent, 2);
                return $ourPrice;
            } else {
                return $cubilisPrice;
            }

        } else {
            return $cubilisPrice;
        }
    }

    /**
     * @param $apartmentId
     * @param $capacity
     * @param $integerPart
     * @return array
     */
    private function getTaxValueAndPercent ($apartmentId, $capacity, $integerPart)
    {
        /**
         * @var \DDD\Dao\Geolocation\Details $detailsDao
         * @var \DDD\Dao\Booking\Addons $addonDao
         * @var \DDD\Service\Partners $partnerService
         */
        $detailsDao = $this->getServiceLocator()->get('dao_geolocation_details');
        $addonDao   = $this->getServiceLocator()->get('dao_booking_addons');
        $taxPercent = $taxValue = 0;

        // get tax list
        $taxData   = $detailsDao->getTaxDataByApartmentId($apartmentId);
        $taxesList = $addonDao->getTaxesList();
        $taxesList = iterator_to_array($taxesList);

        if ($taxData) {
            foreach ($taxesList as $tax) {
                $taxName   = $tax['location_join'];
                $taxType   = isset($taxData[$taxName . '_type']) ? $taxData[$taxName . '_type'] : 0;
                $taxAmount = isset($taxData[$taxName]) ? $taxData[$taxName] : 0;
                $excluded  = isset($taxData[$taxName . '_included']) && !$taxData[$taxName . '_included'] ? true : false;

                // if tax not exclusive
                if ($taxType && $taxAmount && $excluded) {

                    if ($taxType == Taxes::TAXES_TYPE_PERCENT) {
                        $taxPercent += $integerPart * $taxAmount * 0.01;
                    } elseif ($taxType == Taxes::TAXES_TYPE_PER_NIGHT) {
                        $taxValue += $taxAmount;
                    } elseif ($taxType == Taxes::TAXES_TYPE_PER_PERSON) {
                        $taxValue += $taxAmount * $capacity;
                    }
                }
            }
        }

        return [
            'taxPercent' => $taxPercent,
            'taxValue'   => $taxValue,
        ];
    }
    /**
     * @param $partnerId
     * @param $partnerCommission
     * @return int
     */
    public function getPartnerDeductedCommission($partnerId, $partnerCommission)
    {
        /**
         * @var \DDD\Dao\Partners\Partners $partnerDao
         */
        $partnerDao = $this->getServiceLocator()->get('dao_partners_partners');

        // check commission deducted or not
        if ($partnerDao->checkDeductedCommission($partnerId)) {
            return  PartnerService::PARTNER_DEDUCTED_COMMISSION;
        }

        return $partnerCommission;
    }

    /**
     * @param $partnerId
     * @param $price
     * @param $apartmentId
     * @return float
     */
    public function getPartnerDeductedPrice($partnerId, $price, $apartmentId)
    {
        /**
         * @var \DDD\Service\Partners $partnerService
         *  @var \DDD\Dao\Partners\Partners $partnerDao
         *
         */
        $partnerDao = $this->getServiceLocator()->get('dao_partners_partners');

        // check commission deducted or not
        if ($partnerDao->checkDeductedCommission($partnerId)) {
            $partnerService = $this->getServiceLocator()->get('service_partners');
            $partnerData = $partnerService->getPartnerDataForReservation($partnerId, $apartmentId, true);
            if ($partnerData) {
                $price -= $price * $partnerData->getCommission() / 100;
                $price = round($price, 2);
            }
        }

        return $price;
    }

    /**
     * @param $partnerId
     * @return bool
     */
    public function checkCleaningFeeSpecificationForCharge($partnerId)
    {
        /**
         * @var \DDD\Dao\Partners\Partners $partnerDao
         */
        $partnerDao = $this->getServiceLocator()->get('dao_partners_partners');

        // check commission deducted or not
        if ($partnerDao->checkDeductedCommission($partnerId) && $partnerId == PartnerService::PARTNER_EXPEDIA_VIRTUAL_CARD) {
            return  true;
        }

        return false;
    }

    /**
     * For now just change partner of which is reserved for castelldefels & is from expedia
     */
    public function changePartnerForSomeCases($partnerId, $apartmentId)
    {
        $apartmentGroupItemsDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group_items');
        $apartmentGroup         = $apartmentGroupItemsDao->fetchOne(['apartment_id' => $apartmentId], ['apartment_group_id']);
        $partnerDao             = $this->getServiceLocator()->get('dao_partners_partners');

        $partnerInfo = $partnerDao->getPartnerDataForReservation($partnerId, false);

        if (   $apartmentGroup->getApartmentGroupId() == ApartmentGroupService::APARTMENT_GROUP_CASTELLDEFELS
            && $partnerInfo
            && $partnerInfo->getGid() == PartnerService::PARTNER_EXPEDIA
        ) {
            $partnerInfo = $partnerDao->fetchOne(['gid' => PartnerService::PARTNER_EXPEDIA_VIRTUAL_CARD]);
            if ($partnerInfo) {
                $partnerId = PartnerService::PARTNER_EXPEDIA_VIRTUAL_CARD;
                return $partnerId;
            }
        }
        return false;
    }


}
