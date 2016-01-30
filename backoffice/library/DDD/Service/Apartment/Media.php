<?php

namespace DDD\Service\Apartment;

use DDD\Service\ServiceBase;
use FileManager\Constant\DirectoryStructure;

class Media extends ServiceBase
{
    public function getAccImages($apartmentId)
    {
        $mediaDao = $this->getMediaDao();
        $imagesData = $mediaDao->getImages($apartmentId);
        
        return [
            'img1' => $imagesData->getImg1(),
            'img2' => $imagesData->getImg2(),
            'img3' => $imagesData->getImg3(),
            'img4' => $imagesData->getImg4(),
            'img5' => $imagesData->getImg5(),
            'img6' => $imagesData->getImg6(),
            'img7' => $imagesData->getImg7(),
            'img8' => $imagesData->getImg8(),
            'img9' => $imagesData->getImg9(),
            'img10' => $imagesData->getImg10(),
            'img11' => $imagesData->getImg11(),
            'img12' => $imagesData->getImg12(),
            'img13' => $imagesData->getImg13(),
            'img14' => $imagesData->getImg14(),
            'img15' => $imagesData->getImg15(),
            'img16' => $imagesData->getImg16(),
            'img17' => $imagesData->getImg17(),
            'img18' => $imagesData->getImg18(),
            'img19' => $imagesData->getImg19(),
            'img20' => $imagesData->getImg20(),
            'img21' => $imagesData->getImg21(),
            'img22' => $imagesData->getImg22(),
            'img23' => $imagesData->getImg23(),
            'img24' => $imagesData->getImg24(),
            'img25' => $imagesData->getImg25(),
            'img26' => $imagesData->getImg26(),
            'img27' => $imagesData->getImg27(),
            'img28' => $imagesData->getImg28(),
            'img29' => $imagesData->getImg29(),
            'img30' => $imagesData->getImg30(),
            'img31' => $imagesData->getImg31(),
            'img32' => $imagesData->getImg32()
        ];
    }
    
    public function saveImagesSort($apartmentId, $imagesSort)
    {
        $mediaDao = $this->getMediaDao();
        $mediaDao->save($imagesSort, ['apartment_id' => $apartmentId]);
    }

    public function deleteImage($apartmentId, $imageNumber)
    {
        $currentImages = $this->getAccImages($apartmentId);
        
        /**
         * delete image url from array
         * and resort keys
         */
        $newSortInDb = [];
        $i = 1;
        
        foreach ($currentImages as $imgKey => $imageUrl) {
            $key = (int)substr($imgKey, 3);
            
            if ($i !== $key OR $key === (int)$imageNumber) {
                if ($i === $key) {
                    $i++;
                }
                $newSortInDb[$imgKey] = isset($currentImages['img'.$i]) ? $currentImages['img'.$i] : '';
            } else {
                $newSortInDb[$imgKey] = $imageUrl;
            }
            $i++;
        }
        
        /**
         * delete file
         */
        $imageToDelete = $currentImages['img'.$imageNumber];
        
        $filesMask = substr(explode('orig', $imageToDelete)[0],1);
        $files = glob(DirectoryStructure::FS_GINOSI_ROOT
            . DirectoryStructure::FS_IMAGES_ROOT
            . $filesMask.'*');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        /**
         * save to db
         */
        $mediaDao = $this->getMediaDao();
        $mediaDao->save($newSortInDb, ['apartment_id' => $apartmentId]);
    }
    
    public function saveNewImages($apartmentId, $data)
    {
        $mediaDao = $this->getMediaDao();
        $mediaDao->save($data, ['apartment_id' => $apartmentId]);
    }
    
    public function getAccVideoLinks($apartmentId)
    {
        $mediaDao = $this->getMediaDao('DDD\Domain\Apartment\Media\Videos');
        $videoData = $mediaDao->getVideos($apartmentId);
        
        return [
            'video'             => $videoData->getVideo(),
            'key_entry_video'   => $videoData->getKeyEntryVideo()
        ];
    }
    
    public function saveVideos($apartmentId, $data)
    {
        $mediaDao = $this->getMediaDao('DDD\Domain\Apartment\Media\Videos');
        $mediaDao->save($data, ['apartment_id' => $apartmentId]);
    }
    
    /**
	 * @access public
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Media
	 */
    private function getMediaDao($domain = 'DDD\Domain\Apartment\Media\Images')
    {
        return new \DDD\Dao\Apartment\Media($this->getServiceLocator(), $domain);
    }
}
