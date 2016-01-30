<?php

namespace DDD\Service\Apartel;

use DDD\Service\ServiceBase;
use Zend\Form\Annotation\Object;

/**
 * Class DistributionView
 * @package DDD\Service\Apartel
 */
class DistributionView extends ServiceBase
{
    /**
     * @param bool $hasDevTestRole
     * @return array
     */
    public function getOptions($hasDevTestRole = false)
    {
        $apartmentGroupDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $apartelList = $apartmentGroupDao->getApartelList($hasDevTestRole);
        return ['apartelList' => $apartelList, 'partnerList' => $this->getPartnerList()];
    }

    /**
     * @param $apartelId
     * @return array
     */
    public function getDataByApartelId($apartelId)
    {
        $data = [];
        if (!$apartelId) {
            return $data;
        }

        //apartel
        $apartelDistributionDao = $this->getServiceLocator()->get('dao_apartel_ota_distribution');
        $apartelDistributionList = $apartelDistributionDao->getApartelOTAList($apartelId);
        if ($apartelDistributionList->count() > 0) {
            foreach ($apartelDistributionList as $apartel) {
                $apartelDistributionFilterList[] = [
                    'partnerId' => $apartel['partner_id'],
                    'url' => $apartel['url'],
                    'status' => $apartel['status']
                ];
            }

            $editApartelUrl = '<a href="/concierge/edit/' . $apartelId . '" target="_blank">Apartel</a>';

            array_push(
                $data,
                array_merge(
                    [
                        $editApartelUrl,
                        "DT_RowClass" => 'warning'
                    ],
                    $this->getPartnerListWithOTA($apartelDistributionFilterList)
                )
            );
        }

        //apartment
        $apartmentGroupItemServiec = $this->getServiceLocator()->get('dao_apartment_group_apartment_group_items');
        $apartelApartmentDistributionListList = $apartmentGroupItemServiec->apartelApartmentDistributionListList($apartelId);
        $apartmentWithPartner = [];
        foreach ($apartelApartmentDistributionListList as $apartment) {
            $partnerData = [
                'partnerId' => $apartment['partner_id'],
                'url' => $apartment['url'],
                'status' => $apartment['ota_status']
            ];
            $apartmentWithPartner[$apartment['apartmentId']]['partnerList'][] = $partnerData;
            $apartmentWithPartner[$apartment['apartmentId']]['apartmentName'] = '<a href="/apartment/' . $apartment['apartmentId'] . '/channel-connection"  target="_blank">' . $apartment['apartment_name'] . '</a>';
        }

        foreach ($apartmentWithPartner as $row) {
            $list = [$row['apartmentName']];
            array_push($data, array_merge($list, $this->getPartnerListWithOTA($row['partnerList'])));
        }

		return $data;
    }

    /**
     * @param $otaList
     * @return array
     */
    private function getPartnerListWithOTA($otaList) 
    {
        $otaPartnerList = [];
        $partnerList = $this->getPartnerList();
        foreach ($partnerList as $partner) {
            $hasPartner = false; $class = '';
            
            foreach ($otaList as $ota) {
                if($ota['partnerId'] == $partner['gid']) {
                    if($ota['status'] == OTADistribution::STATUS_SELLING) {
                        $linkText = 'Selling';
                        $class = 'btn-info';
                    } else {
                        $class = 'btn-danger';
                        $linkText = 'Pending';
                    }
                    
                    $url = ($ota['url']) ? '<a href="'.$ota['url'].'" class="'.$class.' btn btn-xs" target="_blank">' . $linkText . '</a>' :  
                                           '<span class="'.$class.' btn btn-xs" disabled="disabled">' . $linkText . '</span>';
                    
                    $otaPartnerList[] = $url;
                    $hasPartner = true; 
                    break;
                }
            }
            
            if(!$hasPartner) {
                $otaPartnerList[] = '';
            }
        }

        return $otaPartnerList;
    }

    /**
     * @return array
     */
    private function getPartnerList()
    {
        $partnerDao = $this->getServiceLocator()->get('dao_partners_partners');
        $partnerList = $partnerDao->getActiveOutsidePartners();
        return iterator_to_array($partnerList);
    }        
}
