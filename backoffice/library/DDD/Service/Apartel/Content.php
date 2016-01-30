<?php

namespace DDD\Service\Apartel;

use DDD\Service\ServiceBase;
use DDD\Service\Translation;
use Library\Upload\Images;
use FileManager\Constant\DirectoryStructure;

class Content extends ServiceBase
{

    /**
     * @param \Zend\Stdlib\Parameters $data
     * @param array $file
     * @return bool
     */
    public function saveApartel($data, $file = [])
    {
        try {
            /** @var \DDD\Dao\Apartel\General $apartelGeneralDao */
            $apartelGeneralDao  = $this->getServiceLocator()->get('dao_apartel_general');
            /** @var \DDD\Dao\Apartel\Details $apartelDetailsDao */
            $apartelDetailsDao  = $this->getServiceLocator()->get('dao_apartel_details');
            /** @var \DDD\Dao\Textline\Apartment $productTextlineDao */
            $productTextlineDao = $this->getServiceLocator()->get('dao_textline_apartment');
            /** @var \DDD\Service\Website\Cache $cache */
            $cache              = $this->getServiceLocator()->get('service_website_cache');

            $apartelGeneralData = $apartelGeneralDao->getApartelById($data['id'], false);

            if ($apartelGeneralData === false) {
                throw new \Exception('Cannot find Apartel by Id');
            }

            $apartelDetailsData = $apartelDetailsDao->getApartelDetailsById($data['id']);

            $productTextlineDao->update(
                [
                    'en'            => $data['content_textline'],
                    'en_html_clean' => Translation::cleanTextline($data['content_textline'])
                ],
                ['id' => $apartelDetailsData->getContentTextlineId()]
            );
            $cache->set('prod-'.$apartelDetailsData->getContentTextlineId().'-en', $data['content_textline']);

            $productTextlineDao->update(
                [
                    'en'            => $data['meta_description_textline'],
                    'en_html_clean' => Translation::cleanTextline($data['meta_description_textline'])
                ],
                ['id' => $apartelDetailsData->getMetaDescriptionTextlineId()]
            );
            $cache->set('prod-'.$apartelDetailsData->getMetaDescriptionTextlineId().'-en', $data['meta_description_textline']);

            $productTextlineDao->update(
                [
                    'en'            => $data['moto_textline'],
                    'en_html_clean' => Translation::cleanTextline($data['moto_textline'])
                ],
                ['id' => $apartelDetailsData->getMotoTextlineId()]
            );
            $cache->set('prod-'.$apartelDetailsData->getMotoTextlineId().'-en', $data['moto_textline']);

            if (!empty($file)) {
                try {
                    $image = new Images($file);

                    if ($image && $image->errors) {
                        return false;
                    }

                    $image->resizeToWidth([1920]);
                } catch (\Exception $e) {
                    throw new \Exception($e->getMessage());
                }

                $apartelImagesPath = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_IMAGES_ROOT
                    . DirectoryStructure::FS_IMAGES_APARTEL_BG_IMAGE
                    . $data['id'] . '/';

                array_map('unlink', glob($apartelImagesPath . '*'));

                $originalImagePath = $image->saveImage($apartelImagesPath, 80);

                $originalImage = explode('/', $originalImagePath);
                $originalImageName = $originalImage[count($originalImage) - 1];
                $originalImageNameParsed = explode('_', $originalImageName);

                $newImageName = $originalImageNameParsed[0] . '_0_1920.png';

                $apartelDetailsDao->update(
                    ['bg_image' => $newImageName],
                    ['apartel_id' => $data['id']]
                );
            }

            if (isset($newImageName) && !empty($newImageName)) {
                return $newImageName;
            } else {
                return true;
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot save Apartel general data', $data);
        }

        return false;
    }
}
