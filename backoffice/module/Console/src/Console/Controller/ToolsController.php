<?php

namespace Console\Controller;

use Library\Controller\ConsoleBase;
use Zend\Db\Adapter\Adapter;
use Zend\Text\Table\Table;
use Zend\Console\Prompt;

use Library\Constants\Constants;
use Library\Constants\DbTables;
use FileManager\Constant\DirectoryStructure;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;

/**
 * Class ToolsController
 * @package Console\Controller
 */
class ToolsController extends ConsoleBase
{
    private $onlyLocationImages        = false;
    private $onlyProfileImages         = false;
    private $onlyApartmentImages       = false;
    private $onlyBlogImages            = false;
    private $onlyDocuments             = false;
    private $onlyBuildingMaps          = false;
    private $onlyOfficeMaps            = false;
    private $onlyPurchaseOrder         = false;
    private $onlyPurchaseOrderItems    = false;
    private $onlyUsersDocuments        = false;
    private $onlyJobsDocuments         = false;
    private $onlyBookingDocuments      = false;
    private $onlyMoneyAccountDocuments = false;
    private $onlyTaskAttachments       = false;
    private $onlyParkingAttachments    = false;
    private $onlyApartelImages         = false;

    private $withOutParams  = true;
    private $removeFromDisk = false;

    private $counter = 1;

    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', 'help');

        switch ($action) {
            case 'report-unused-files':
                $this->reportUnusedFilesAction();
                break;
            case 'optimize-tables':
                $this->optimizeTablesAction();
                break;
            default:
                $this->helpAction();
        }
    }

    public function helpAction()
    {
echo <<<USAGE

 \e[0;37m----------------------------------------------------------\e[0m
 \e[2;37m          ✡  Ginosi Backoffice Console (GBC)  ✡          \e[0m
 \e[0;37m----------------------------------------------------------\e[0m

 \e[0;37mTools parameters:\e[0m

    \e[1;33mtools help\e[0m                     \e[2;33m- show this help\e[0m

    \e[1;33mtools optimize-tables\e[0m          \e[2;33m- Run optimize tables statement for all database tables \e[0m

    \e[1;33mtools report-unused-files\e[0m      \e[2;33m- report all unused files on hard disk & lost files in database\e[0m
        \e[2;33m--only-locations-images\e[0m
        \e[2;33m--only-profiles-images\e[0m
        \e[2;33m--only-apartments-images\e[0m
        \e[2;33m--only-documents\e[0m
        \e[2;33m--only-building-maps\e[0m
        \e[2;33m--only-office-maps\e[0m
        \e[2;33m--only-blog-images\e[0m
        \e[2;33m--only-purchase-order-attachments\e[0m
        \e[2;33m--only-purchase-order-item-attachments\e[0m
        \e[2;33m--only-users-documents\e[0m
        \e[2;33m--only-jobs-documents\e[0m
        \e[2;33m--only-booking-documents\e[0m
        \e[2;33m--only-money-account-documents\e[0m
        \e[2;33m--only-task-attachments\e[0m
        \e[2;33m--only-parking-attachments\e[0m
        \e[2;33m--only-apartel-images\e[0m

        \e[2;33m--remove-from-disk\e[0m         \e[2;33m- remove reported unused files from hard disk\e[0m

    \e[3;33mfor additional questions, please call Tigo :)\e[0m


USAGE;
    }

    /**
     * Generate report about unsed files and lost data in database
     */
    public function reportUnusedFilesAction()
    {
        // get params
        if ($this->getRequest()->getParam('only-locations-images')) {
            $this->onlyLocationImages = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-building-maps')) {
            $this->onlyBuildingMaps = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-office-maps')) {
            $this->onlyOfficeMaps = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-profiles-images')) {
            $this->onlyProfileImages = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-apartments-images')) {
            $this->onlyApartmentImages = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-blog-images')) {
            $this->onlyBlogImages = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-documents')) {
            $this->onlyDocuments = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-purchase-order-attachments')) {
            $this->onlyPurchaseOrder = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-purchase-order-item-attachments')) {
            $this->onlyPurchaseOrderItems = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-users-documents')) {
            $this->onlyUsersDocuments = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-jobs-documents')) {
            $this->onlyJobsDocuments = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-booking-documents')) {
            $this->onlyBookingDocuments = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-money-account-documents')) {
            $this->onlyMoneyAccountDocuments = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-task-attachments')) {
            $this->onlyTaskAttachments = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-parking-attachments')) {
            $this->onlyParkingAttachments = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('only-apartel-images')) {
            $this->onlyApartelImages = true;
            $this->withOutParams = false;
        }

        if ($this->getRequest()->getParam('remove-from-disk')) {
            $this->removeFromDisk = true;
        }

        $table = new Table([
            'columnWidths' => [4, 71, 71]
        ]);

        $table->appendRow([
            '#',
            'on Disk',
            'on Database'
        ]);

        // array of unused files in disk - for delete it!
        $unusedFiles = [];

        /**
         * Locations
         */
        if ($this->onlyLocationImages OR $this->withOutParams) {
            $locationImagesPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_LOCATIONS_PATH;

            if (!is_readable($locationImagesPath)) {
                echo "Folder does not exist: ".$locationImagesPath.PHP_EOL.PHP_EOL;
            } else {
                $locationsDiskData = array_diff(scandir($locationImagesPath), ['.','..']);
                $locationsDiskArray = [];

                foreach ($locationsDiskData as $locationDisk) {
                    $thisFilesList = array_diff(scandir($locationImagesPath.$locationDisk), ['.','..']);

                    foreach ($thisFilesList as $file) {
                        $locationsDiskArray[$locationDisk][] = $file;
                    }
                }

                $locationsDbData = $this->getLocationsData();

                foreach ($locationsDbData as $locationDb) {

                    $cover_image = $locationImagesPath . $locationDb->getId() . '/' . $locationDb->getCover_image();
                    $thumbnail = $locationImagesPath . $locationDb->getId() . '/' . $locationDb->getThumbnail();

                    if (!empty($locationDb->getCover_image()) && $locationDb->getCover_image() !== null && !is_file($cover_image)) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            DbTables::TBL_LOCATION_DETAILS . '.id: '.$locationDb->getId().PHP_EOL
                                .'name: '.$locationDb->getName().PHP_EOL
                                .'cover_image: '.PHP_EOL
                                .$cover_image
                            ]);
                    } elseif (!empty($locationDb->getCover_image()) && $locationDb->getCover_image() !== null) {
                        $filename = explode('_', $locationDb->getCover_image())[0];

                        foreach ($locationsDiskArray[$locationDb->getId()] as $locationFileKey => $locationFile) {
                            if (strstr($locationFile, $filename)) {
                                unset($locationsDiskArray[$locationDb->getId()][$locationFileKey]);
                            }
                        }
                    }

                    if (!empty($locationDb->getThumbnail()) && $locationDb->getThumbnail() !== null && !is_file($thumbnail)) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            DbTables::TBL_LOCATION_DETAILS . '.id: '.$locationDb->getId().PHP_EOL
                                .'name: '.$locationDb->getName().PHP_EOL
                                .'thumbnail: '.PHP_EOL
                                .$thumbnail
                            ]);
                    } elseif (!empty($locationDb->getThumbnail()) && $locationDb->getThumbnail() !== null) {
                        $filename = explode('_', $locationDb->getThumbnail())[0];

                        foreach ($locationsDiskArray[$locationDb->getId()] as $locationFileKey => $locationFile) {
                            if (strstr($locationFile, $filename)) {
                                unset($locationsDiskArray[$locationDb->getId()][$locationFileKey]);
                            }
                        }
                    }
                }
                foreach ($locationsDiskArray as $folder => $locationDisk) {
                    foreach ($locationDisk as $locationDiskFiles) {
                        $unusedLocationFile = $locationImagesPath.$folder.'/'.$locationDiskFiles;
                        $unusedFiles[] = $unusedLocationFile;

                        if (is_dir($unusedLocationFile)) {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Location'.PHP_EOL
                                    .'-=!=- UNKNOWN FOLDER -=!=- '.PHP_EOL
                                    .$unusedLocationFile,
                                'x'
                            ]);
                        } else {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Location'.PHP_EOL
                                    .'filename: '.PHP_EOL
                                    .$unusedLocationFile,
                                'x'
                            ]);
                        }
                    }
                }
            }
        }

        /**
         * Profiles
         */
        if ($this->onlyProfileImages OR $this->withOutParams) {
            $profileImagesPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_PROFILE_PATH;

            if (!is_readable($profileImagesPath)) {
                echo "Folder does not exist: ".$profileImagesPath.PHP_EOL.PHP_EOL;
            } else {
                $profilesDiskData = array_diff(scandir($profileImagesPath), ['.','..']);
                $profilesDiskArray = [];

                foreach ($profilesDiskData as $profileDisk) {
                    $thisFilesList = array_diff(scandir($profileImagesPath.$profileDisk.'/'), ['.','..']);

                    foreach ($thisFilesList as $file) {
                        $profilesDiskArray[$profileDisk][] = $file;
                    }
                }

                $profilesDbData = $this->getProfilesData();

                foreach ($profilesDbData as $profileDb) {
                    $avatar = $profileImagesPath.$profileDb->getId().'/'.$profileDb->getAvatar();

                    if (!empty($profileDb->getAvatar()) AND $profileDb->getAvatar() !== null AND !is_file($avatar)) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            DbTables::TBL_BACKOFFICE_USERS . '.id: '.$profileDb->getId().PHP_EOL
                                .'user name: '.$profileDb->getFirstName().' '.$profileDb->getLastName().PHP_EOL
                                .'avatar: '.PHP_EOL
                                .$avatar
                            ]);
                    } elseif (!empty($profileDb->getAvatar()) AND $profileDb->getAvatar() !== null) {
                        $filename = explode('_', $profileDb->getAvatar())[0];

                        foreach ($profilesDiskArray[$profileDb->getId()] as $profileFileKey => $profileFile) {
                            if (strstr($profileFile, $filename)) {
                                unset($profilesDiskArray[$profileDb->getId()][$profileFileKey]);
                            }
                        }
                    }
                }
                foreach ($profilesDiskArray as $folder => $profileDisk) {
                    foreach ($profileDisk as $profileDiskFiles) {
                        $unusedProfileFile = $profileImagesPath.$folder.'/'.$profileDiskFiles;
                        $unusedFiles[] = $unusedProfileFile;

                        if (is_dir($unusedProfileFile)) {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Profile'.PHP_EOL
                                    .'-=!=- UNKNOWN FOLDER -=!=- '.PHP_EOL
                                    .$unusedProfileFile,
                                'x'
                            ]);
                        } else {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Profile'.PHP_EOL
                                    .'filename: '.PHP_EOL
                                    .$unusedProfileFile,
                                'x'
                            ]);
                        }
                    }
                }
            }
        }

        /**
         * Apartment Images
         */
        if ($this->onlyApartmentImages OR $this->withOutParams) {
            $apartmentImagesPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_APARTMENT;

            if (!is_readable($apartmentImagesPath)) {
                echo "Folder does not exist: ".$apartmentImagesPath.PHP_EOL.PHP_EOL;
            } else {
                $apartmentsDiskData = array_diff(scandir($apartmentImagesPath), ['.','..']);
                $apartmentsDiskArray = [];

                foreach ($apartmentsDiskData as $apartmentDisk) {
                    $thisFilesList = array_diff(scandir($apartmentImagesPath.$apartmentDisk.'/'), ['.','..']);

                    foreach ($thisFilesList as $file) {
                        $apartmentsDiskArray[$apartmentDisk][] = $file;
                    }
                }

                $apartmentsDbData = $this->getApartmentsData();

                foreach ($apartmentsDbData as $apartmentDb) {
                    $dbImageResult = [];

                    $apartmentThumbsSizes = [
                        '445',
                        '555',
                        '780',
                        '96'
                    ];

                    if (isset($apartmentsDiskArray[$apartmentDb->getApartmentId()])
                        && count($apartmentsDiskArray[$apartmentDb->getApartmentId()]))
                    {
                        for ($i=1; $i <= 32; $i++) {
                            $imgId = 'img'.$i;
                            $getImgActionId = 'getImg'.$i;

                            if (!empty($apartmentDb->$getImgActionId()) AND $apartmentDb->$getImgActionId() !== null) {

                                $dbImageResult[$i]['has_original']  = false;
                                $dbImageResult[$i]['has_thumbs']     = [];

                                $filenameFromDb = explode('/', $apartmentDb->$getImgActionId())[3];
                                $fileOriginNameFromDb = explode('orig', $filenameFromDb)[0];

                                foreach ($apartmentsDiskArray[$apartmentDb->getApartmentId()]
                                    as $apartmentFileKey => $apartmentFile)
                                {
                                    foreach ($apartmentThumbsSizes as $size) {
                                        if (strstr($apartmentFile, $fileOriginNameFromDb.$size)) {
                                            unset($apartmentsDiskArray[$apartmentDb->getApartmentId()][$apartmentFileKey]);
                                            $dbImageResult[$i]['has_thumbs'][] = $size;
                                        }
                                    }

                                    if ($apartmentFile == $filenameFromDb) {
                                        unset($apartmentsDiskArray[$apartmentDb->getApartmentId()][$apartmentFileKey]);
                                        $dbImageResult[$i]['has_original'] = $apartmentDb->$getImgActionId();
                                    }
                                }
                            }
                        }

                        foreach ($dbImageResult as $imgId => $imgValues) {
                            $doesNotHaveThis = '';
                            $getImgActionId = 'getImg'.$imgId;

                            if (!$imgValues['has_original']) {
                                $doesNotHaveThis .= PHP_EOL.'original: '
                                    .substr($apartmentImagesPath,0,-1)
                                    .$apartmentDb->$getImgActionId();
                            }

                            $hasNoThumb = array_diff($apartmentThumbsSizes, $imgValues['has_thumbs']);

                            if (count($hasNoThumb)) {
                                $filenameFromDb = explode('/', $apartmentDb->$getImgActionId())[3];
                                $fileOriginNameFromDb = explode('orig', $filenameFromDb)[0];

                                foreach ($hasNoThumb as $thumbSize) {
                                    $thumsImagePath = $apartmentImagesPath
                                        .$apartmentDb->getApartmentId().'/'
                                        .$fileOriginNameFromDb
                                        .$thumbSize.'.jpg';
                                    $doesNotHaveThis .= PHP_EOL.'thumb: '.$thumsImagePath;
                                }
                            }

                            if (!empty($doesNotHaveThis)) {
                                $table->appendRow([
                                    $this->runCounter(),
                                    'x',
                                    DbTables::TBL_APARTMENT_IMAGES . '.apartment_id: '.$apartmentDb->getApartmentId().PHP_EOL
                                        .$imgId.': '
                                        .$doesNotHaveThis
                                ]);
                            }
                        }
                    } elseif (!empty($apartmentDb->getImg1())) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            DbTables::TBL_APARTMENT_IMAGES . '.apartment_id: '.$apartmentDb->getApartmentId().PHP_EOL
                                .'!!!'.PHP_EOL
                                .'Apartment images folder empty or not exist'.PHP_EOL
                                .'But apartment has attached images in database'
                        ]);
                    }
                }

                foreach ($apartmentsDiskArray as $folder => $apartmentDisk) {
                    foreach ($apartmentDisk as $apartmentDiskFiles) {
                        if ($apartmentDiskFiles === 'map') {
                            continue;
                        }

                        $unusedApartmentImagesFile = $apartmentImagesPath.$folder.'/'.$apartmentDiskFiles;
                        $unusedFiles[] = $unusedApartmentImagesFile;

                        if (is_dir($unusedApartmentImagesFile)) {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Apartment'.PHP_EOL
                                    .'-=!=- UNKNOWN FOLDER -=!=- '.PHP_EOL
                                    .$unusedApartmentImagesFile,
                                'x'
                            ]);
                        } else {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Apartment'.PHP_EOL
                                    .'filename: '.PHP_EOL
                                    .$unusedApartmentImagesFile,
                                'x'
                            ]);
                        }
                    }
                }
            }
        }

        /**
         * Documents
         */
        if ($this->onlyDocuments OR $this->withOutParams) {
            $documentUploadsPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_UPLOADS_ROOT
                . DirectoryStructure::FS_UPLOADS_DOCUMENTS;

            if (!is_readable($documentUploadsPath)) {
                echo "Folder does not exist: ".$documentUploadsPath . PHP_EOL . PHP_EOL;
            } else {
                $docsDiskData = array_diff(scandir($documentUploadsPath), ['.', '..']);
                $docsDiskArray = [];

                foreach ($docsDiskData as $docDiskYear) {
                    $yearFilesList = array_diff(scandir($documentUploadsPath . $docDiskYear), ['.','..']);

                    foreach ($yearFilesList as $docDiskMonth) {
                        $monthFilesList = array_diff(scandir($documentUploadsPath . $docDiskYear . '/' . $docDiskMonth), ['.', '..']);

                        foreach ($monthFilesList as $docDiskDay) {
                            $dayFilesList = array_diff(scandir($documentUploadsPath . $docDiskYear . '/' . $docDiskMonth . '/' . $docDiskDay), ['.', '..']);

                            foreach ($dayFilesList as $file) {
                                $docsDiskArray[$docDiskYear . '/' . $docDiskMonth . '/' . $docDiskDay][] = $file;
                            }
                        }
                    }
                }

                $docsDbData = $this->getDocumentsData();

                /** @var \DDD\Domain\Document\Document $docDb */
                foreach ($docsDbData as $docDb) {
                    $dateFolder = date('Y/m/j', strtotime($docDb->getCreatedDate()));
                    $attachment = $documentUploadsPath . $dateFolder . '/' . $docDb->getAttachment();

                    if (!empty($docDb->getAttachment()) AND $docDb->getAttachment() !== null) {
                        if (!is_file($attachment)) {
                            $table->appendRow([
                                $this->runCounter(),
                                'x',
                                DbTables::TBL_DOCUMENTS . '.apartment_id: ' . $docDb->getID() . PHP_EOL
                                . 'attachment: ' . PHP_EOL
                                . $attachment
                            ]);
                        } else {
                            $filename = $docDb->getAttachment();

                            foreach ($docsDiskArray[$dateFolder] as $docFileKey => $docFile) {
                                if ($docFile === $filename) {
                                    unset($docsDiskArray[$dateFolder][$docFileKey]);
                                }
                            }
                        }
                    }
                }
                foreach ($docsDiskArray as $dateFolder => $docFiles) {
                    foreach ($docFiles as $key => $docFile) {
                        $unusedDocumentFile = $documentUploadsPath . $dateFolder . '/' . $docFile;
                        $unusedFiles[] = $unusedDocumentFile;

                        if (is_dir($unusedDocumentFile)) {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Apartment Documents' . PHP_EOL
                                . '-=!=- UNKNOWN FOLDER -=!=- ' . PHP_EOL
                                . $unusedDocumentFile,
                                'x'
                            ]);
                        } else {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Apartment Documents' . PHP_EOL
                                . 'filename: ' . PHP_EOL
                                . $unusedDocumentFile,
                                'x'
                            ]);
                        }
                    }
                }
            }
        }

        /**
         * Apartment Maps
         */
        if ($this->onlyBuildingMaps OR $this->withOutParams) {
            $buildingImagesPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_BUILDING;

            if (!is_readable($buildingImagesPath)) {
                echo "Folder does not exist: " . $buildingImagesPath . PHP_EOL.PHP_EOL;
            } else {
                $buildingDiskData = array_diff(scandir($buildingImagesPath), ['.', '..']);
                $mapsDiskArray    = [];

                foreach ($buildingDiskData as $buildingId) {
                    if (is_dir($buildingImagesPath . $buildingId . '/map')) {
                        $thisFilesList = array_diff(scandir($buildingImagesPath . $buildingId . '/map'), ['.', '..']);

                        if (count($thisFilesList)) {
                            foreach ($thisFilesList as $file) {
                                $mapsDiskArray[$buildingId][] = $file;
                            }
                        }
                    }
                }

                $mapsDbData = $this->getBuildingsMapsData();

                foreach ($mapsDbData as $mapsDb) {

                    $file = substr($buildingImagesPath,0,-1) . '/' . $mapsDb['building_id'] . '/map/' . $mapsDb['map_attachment'];
                    if (!empty($mapsDb['map_attachment']) && $mapsDb['map_attachment'] !== null AND !is_file($file)) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            DbTables::TBL_BUILDING_DETAILS . '.id: '.$mapsDb['id'].PHP_EOL
                                . DbTables::TBL_BUILDING_DETAILS . '.apartment_group_id: '.$mapsDb['building_id'].PHP_EOL
                                .'img: '.PHP_EOL
                                .$file
                            ]);
                    } elseif (!empty($mapsDb['map_attachment']) AND $mapsDb['map_attachment'] !== null) {

                        foreach ($mapsDiskArray[$mapsDb['building_id']] as $mapsFileKey => $mapsFile) {
                            if ($mapsFile == $mapsDb['map_attachment']) {
                                unset($mapsDiskArray[$mapsDb['building_id']][$mapsFileKey]);
                            }
                        }
                    }
                }
                foreach ($mapsDiskArray as $folder => $mapsDisk) {
                    foreach ($mapsDisk as $mapsDiskFile) {
                        $unusedMapsFile = $buildingImagesPath . $folder . '/map/' . $mapsDiskFile;
                        $unusedFiles[] = $unusedMapsFile;

                        if (is_dir($unusedMapsFile)) {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Building Maps'.PHP_EOL
                                    .'-=!=- UNKNOWN FOLDER -=!=- '.PHP_EOL
                                    .$unusedMapsFile,
                                'x'
                            ]);
                        } else {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Building Maps'.PHP_EOL
                                    .'filename: '.PHP_EOL
                                    .$unusedMapsFile,
                                'x'
                            ]);
                        }
                    }
                }
            }
        }

        /**
         * Apartment Maps
         */
        if ($this->onlyOfficeMaps OR $this->withOutParams) {
            $officeImagesPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_OFFICE;

            if (!is_readable($officeImagesPath)) {
                echo "Folder does not exist: " . $officeImagesPath . PHP_EOL.PHP_EOL;
            } else {
                $officeDiskData = array_diff(scandir($officeImagesPath), ['.', '..']);
                $mapsDiskArray    = [];

                foreach ($officeDiskData as $officeId) {
                    if (is_dir($officeImagesPath . $officeId)) {
                        $thisFilesList = array_diff(scandir($officeImagesPath . $officeId), ['.', '..']);

                        if (count($thisFilesList)) {
                            foreach ($thisFilesList as $file) {
                                $mapsDiskArray[$officeId][] = $file;
                            }
                        }
                    }
                }

                $mapsDbData = $this->getOfficeMapsData();

                foreach ($mapsDbData as $mapsDb) {

                    $file = substr($officeImagesPath,0,-1) . '/' . $mapsDb->getId() . '/map/' . $mapsDb->getMapAttachment();
                    if (!empty($mapsDb->getMapAttachment()) && $mapsDb->getMapAttachment() !== null AND !is_file($file)) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            DbTables::TBL_OFFICES . '.id: ' . $mapsDb->getId() . PHP_EOL
                            . 'img: ' . PHP_EOL
                            . $file
                        ]);
                    } elseif (!empty($mapsDb->getMapAttachment()) AND $mapsDb->getMapAttachment() !== null) {

                        foreach ($mapsDiskArray[$mapsDb->getId()] as $mapsFileKey => $mapsFile) {
                            if ($mapsFile == $mapsDb->getMapAttachment()) {
                                unset($mapsDiskArray[$mapsDb->getId()][$mapsFileKey]);
                            }
                        }
                    }
                }
                foreach ($mapsDiskArray as $folder => $mapsDisk) {
                    foreach ($mapsDisk as $mapsDiskFile) {
                        $unusedMapsFile = $officeImagesPath . $folder . '/map/' . $mapsDiskFile;
                        $unusedFiles[] = $unusedMapsFile;

                        if (is_dir($unusedMapsFile)) {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Office Maps'.PHP_EOL
                                    .'-=!=- UNKNOWN FOLDER -=!=- '.PHP_EOL
                                    .$unusedMapsFile,
                                'x'
                            ]);
                        } else {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Office Maps'.PHP_EOL
                                    .'filename: '.PHP_EOL
                                    .$unusedMapsFile,
                                'x'
                            ]);
                        }
                    }
                }
            }
        }

        /**
         * Blog
         */
        if ($this->onlyBlogImages OR $this->withOutParams) {
            $blogImagesPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_BLOG_PATH;

            if (!is_readable($blogImagesPath)) {
                echo "Folder does not exist: ".$blogImagesPath.PHP_EOL.PHP_EOL;
            } else {
                $blogsDiskData = array_diff(scandir($blogImagesPath), ['.','..']);
                $blogsDiskArray = [];

                foreach ($blogsDiskData as $blogDisk) {
                    $thisFilesList = array_diff(scandir($blogImagesPath.$blogDisk), ['.','..']);

                    foreach ($thisFilesList as $file) {
                        $blogsDiskArray[$blogDisk][] = $file;
                    }
                }

                $blogsDbData = $this->getBlogsData();

                foreach ($blogsDbData as $blogDb) {
                    $img = substr(DirectoryStructure::FS_GINOSI_ROOT . DirectoryStructure::FS_IMAGES_ROOT,0,-1).$blogDb->getImg();

                    if (!empty($blogDb->getImg()) AND $blogDb->getImg() !== null AND !is_file($img)) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            DbTables::TBL_BLOG_POSTS . '.id: '.$blogDb->getId().PHP_EOL
                                .'img: '.PHP_EOL
                                .$img
                            ]);
                    } elseif (!empty($blogDb->getImg()) AND $blogDb->getImg() !== null) {
                        $filename = explode('_', explode('/',$blogDb->getImg())[3])[0];

                        foreach ($blogsDiskArray[$blogDb->getId()] as $blogFileKey => $blogFile) {
                            if (strstr($blogFile, $filename)) {
                                unset($blogsDiskArray[$blogDb->getId()][$blogFileKey]);
                            }
                        }
                    }
                }
                foreach ($blogsDiskArray as $folder => $blogDisk) {
                    foreach ($blogDisk as $blogDiskFiles) {
                        $unusedBlogFile = $blogImagesPath.$folder.'/'.$blogDiskFiles;
                        $unusedFiles[] = $unusedBlogFile;

                        if (is_dir($unusedBlogFile)) {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Blogs'.PHP_EOL
                                    .'-=!=- UNKNOWN FOLDER -=!=- '.PHP_EOL
                                    .$unusedBlogFile,
                                'x'
                            ]);
                        } else {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Blogs'.PHP_EOL
                                    .'filename: '.PHP_EOL
                                    .$unusedBlogFile,
                                'x'
                            ]);
                        }
                    }
                }
            }
        }

        /**
         * Users Documents
         */
        if ($this->onlyUsersDocuments OR $this->withOutParams) {
            $userUploadsPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_UPLOADS_ROOT
                . DirectoryStructure::FS_UPLOADS_USER_DOCUMENTS;

            if (!is_readable($userUploadsPath)) {
                echo "Folder does not exist: ".$userUploadsPath.PHP_EOL.PHP_EOL;
            } else {
                $usersDocsDiskData = array_diff(scandir($userUploadsPath), ['.','..']);
                $usersDocsDiskArray = [];

                foreach ($usersDocsDiskData as $userDocDisk) {
                    $thisFilesList = array_diff(scandir($userUploadsPath.$userDocDisk), ['.','..']);

                    foreach ($thisFilesList as $file) {
                        $usersDocsDiskArray[$userDocDisk][] = $file;
                    }
                }

                $usersDocsDbData = $this->getUsersDocumentsData();

                foreach ($usersDocsDbData as $userDocDb) {
                    $attachment = $userUploadsPath.$userDocDb->getUserId().'/'.$userDocDb->getAttachment();

                    if (!empty($userDocDb->getAttachment()) AND $userDocDb->getAttachment() !== null AND !is_file($attachment)) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            'ga_bo_user_documents.id: '.$userDocDb->getId().PHP_EOL
                                .'attachment: '.PHP_EOL
                                .$attachment
                            ]);
                    } elseif (!empty($userDocDb->getAttachment()) AND $userDocDb->getAttachment() !== null) {
                        $filename = $userDocDb->getAttachment();

                        foreach ($usersDocsDiskArray[$userDocDb->getUserId()]
                            as $userDocFileKey => $userDocFile)
                        {
                            if ($userDocFile === $filename) {
                                unset($usersDocsDiskArray[$userDocDb->getUserId()][$userDocFileKey]);
                            }
                        }
                    }
                }
                foreach ($usersDocsDiskArray as $folder => $userDocDisk) {
                    foreach ($userDocDisk as $userDocDiskFiles) {
                        $unusedUserDocumentsFile = $userUploadsPath.$folder.'/'.$userDocDiskFiles;
                        $unusedFiles[] = $unusedUserDocumentsFile;

                        if (is_dir($unusedUserDocumentsFile)) {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: User Documents'.PHP_EOL
                                    .'-=!=- UNKNOWN FOLDER -=!=- '.PHP_EOL
                                    .$unusedUserDocumentsFile,
                                'x'
                            ]);
                        } else {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: User Documents'.PHP_EOL
                                    .'filename: '.PHP_EOL
                                    .$unusedUserDocumentsFile,
                                'x'
                            ]);
                        }
                    }
                }
            }
        }

        /**
         * Jobs Documents
         */
        if ($this->onlyJobsDocuments OR $this->withOutParams) {
            $applicantUploadsPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_UPLOADS_ROOT
                . DirectoryStructure::FS_UPLOADS_HR_APPLICANT_DOCUMENTS;

            if (!is_readable($applicantUploadsPath)) {
                echo "Folder does not exist: ".$applicantUploadsPath.PHP_EOL.PHP_EOL;
            } else {
                $jobsDocsDiskData = array_diff(scandir($applicantUploadsPath), ['.','..']);
                $jobsDocsDiskArray = [];

                foreach ($jobsDocsDiskData as $jobDocDiskYear) {
                    $yearFilesList = array_diff(scandir($applicantUploadsPath.$jobDocDiskYear), ['.','..']);

                    foreach ($yearFilesList as $jobDocDiskMonth) {
                        $monthFilesList = array_diff(scandir($applicantUploadsPath.$jobDocDiskYear.'/'.$jobDocDiskMonth), ['.','..']);

                        foreach ($monthFilesList as $file) {
                            $jobsDocsDiskArray[$jobDocDiskYear][$jobDocDiskMonth][] = $file;
                        }
                    }
                }

                $jobsDocsDbData = $this->getJobsDocumentsData();

                foreach ($jobsDocsDbData as $jobDocDb) {
                    $attachmentDate = explode('-', $jobDocDb->getDateApplied());

                    $attachment = $applicantUploadsPath
                        .$attachmentDate[0]
                        .'/'.$attachmentDate[1]
                        .'/'.$jobDocDb->getCvFileName();

                    if (!empty($jobDocDb->getCvFileName()) AND $jobDocDb->getCvFileName() !== null AND !is_file($attachment)) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            'ga_hr_applicants.id: '.$jobDocDb->getId().PHP_EOL
                                .'attachment: '.PHP_EOL
                                .$attachment
                            ]);
                    } elseif (!empty($jobDocDb->getCvFileName()) AND $jobDocDb->getCvFileName() !== null) {
                        $filename = $jobDocDb->getCvFileName();

                        foreach ($jobsDocsDiskArray[$attachmentDate[0]][$attachmentDate[1]]
                            as $jobDocFileKey => $jobDocFile)
                        {
                            if ($jobDocFile === $filename) {
                                unset($jobsDocsDiskArray[$attachmentDate[0]][$attachmentDate[1]][$jobDocFileKey]);
                            }
                        }
                    }
                }
                foreach ($jobsDocsDiskArray as $year => $monthFiles) {
                    foreach ($monthFiles as $month => $jobDocDiskFiles) {
                        foreach ($jobDocDiskFiles as $fileKey => $jobDocDiskFile) {
                            $unusedJobDocumentFile = $applicantUploadsPath
                                .$year.'/'
                                .$month.'/'
                                .$jobDocDiskFile;
                            $unusedFiles[] = $unusedJobDocumentFile;

                            if (is_dir($unusedJobDocumentFile)) {
                                $table->appendRow([
                                    $this->runCounter(),
                                    'type: Applicant Documents'.PHP_EOL
                                        .'-=!=- UNKNOWN FOLDER -=!=- '.PHP_EOL
                                        .$unusedJobDocumentFile,
                                    'x'
                                ]);
                            } else {
                                $table->appendRow([
                                    $this->runCounter(),
                                    'type: Applicant Documents'.PHP_EOL
                                        .'filename: '.PHP_EOL
                                        .$unusedJobDocumentFile,
                                    'x'
                                ]);
                            }
                        }
                    }
                }
            }
        }

        /**
         * Booking attached documents
         */
        if ($this->onlyBookingDocuments || $this->withOutParams) {


            $bookingDocFolder = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_UPLOADS_ROOT
                . DirectoryStructure::FS_UPLOADS_BOOKING_DOCUMENTS;

            if (!is_readable($bookingDocFolder)) {
                echo "Folder does not exist: " . $bookingDocFolder.PHP_EOL.PHP_EOL;
            } else {
                $bookingDocsDiskData = array_diff(scandir($bookingDocFolder), ['.','..']);
                $bookingDocsDiskArray = [];

                foreach ($bookingDocsDiskData as $bookingDocDiskYear) {
                    $yearFilesList = array_diff(scandir($bookingDocFolder.$bookingDocDiskYear), ['.','..']);

                    foreach ($yearFilesList as $bookingDocDiskMonth) {
                        $monthFilesList = array_diff(scandir($bookingDocFolder.$bookingDocDiskYear.'/'.$bookingDocDiskMonth), ['.','..']);

                        foreach ($monthFilesList as $BookingsList) {
                            $bookingsList = array_diff(scandir($bookingDocFolder.$bookingDocDiskYear.'/'.$bookingDocDiskMonth .'/' . $BookingsList), ['.','..']);

                            foreach ($bookingsList as $bookingDocsList) {
                                $docList = array_diff(scandir($bookingDocFolder.$bookingDocDiskYear.'/'.$bookingDocDiskMonth .'/' . $BookingsList . '/' . $bookingDocsList), ['.','..']);

                                foreach ($docList as $file) {
                                    $bookingDocsDiskArray[$bookingDocDiskYear][$bookingDocDiskMonth][$BookingsList][$bookingDocsList][] = $file;
                                }
                            }
                        }
                    }
                }


                $bookingsDocsDbData = $this->getReservationAttachmentsData();

                foreach ($bookingsDocsDbData as $bookingDocDb) {


                    $createdDate = $bookingDocDb->getCreatedDate();

                    $year  = date('Y', strtotime($createdDate));
                    $month = date('m', strtotime($createdDate));

                    $attachment = $bookingDocFolder .
                        $year . '/' . $month . '/' .
                        $bookingDocDb->getReservationId() . '/' .
                        $bookingDocDb->getDocId() . '/' .
                        $bookingDocDb->getAttachment();

                    if (   !empty($bookingDocDb->getAttachment())
                        && ($bookingDocDb->getAttachment() !== null)
                        && !is_file($attachment)
                    ) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            'ga_reservation_attachment_items.id: '.$bookingDocDb->getId().PHP_EOL
                                .'attachment: '.PHP_EOL
                                .$attachment
                            ]);
                    } elseif (   !empty($bookingDocDb->getAttachment())
                              && ($bookingDocDb->getAttachment() !== null)
                    ) {
                        $filename = $bookingDocDb->getAttachment();
                        foreach ($bookingDocsDiskArray[$year][$month][$bookingDocDb->getReservationId()]
                            as $bookingDocFileKey => $bookingDocFile)
                        {

                            foreach ($bookingDocFile as $docFiles => $bookDocFile) {

                                if ($bookDocFile === $filename) {
                                    unset(
                                        $bookingDocsDiskArray
                                            [$year]
                                            [$month]
                                            [$bookingDocDb->getReservationId()]
                                            [$bookingDocFileKey]
                                            [$docFiles]
                                    );
                                }
                            }
                        }
                    }
                }

                foreach ($bookingDocsDiskArray as $year => $monthFiles) {
                    foreach ($monthFiles as $month => $booksId) {
                        foreach ($booksId as $bookId => $docsId) {
                            foreach ($docsId as $bookingDocId => $bookingFileList) {
                                foreach ($bookingFileList as $bookingFileKey => $bookingDiskFile) {
                                    $unusedBookingDocFile = $bookingDocFolder
                                        . $year . '/'
                                        . $month . '/'
                                        . $bookId . '/'
                                        . $bookingDocId . '/'
                                        . $bookingDiskFile;

                                    $unusedFiles[] = $unusedBookingDocFile;

                                    if (is_dir($unusedBookingDocFile)) {
                                        $table->appendRow([
                                            $this->runCounter(),
                                            'type: Booking Documents'.PHP_EOL
                                                .'-=!=- UNKNOWN FOLDER -=!=- '.PHP_EOL
                                                .$unusedBookingDocFile,
                                            'x'
                                        ]);
                                    } else {
                                        $table->appendRow([
                                            $this->runCounter(),
                                            'type: Booking Documents'.PHP_EOL
                                                .'filename: '.PHP_EOL
                                                .$unusedBookingDocFile,
                                            'x'
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }


        /**
         * Money Account attached documents
         */
        if ($this->onlyMoneyAccountDocuments || $this->withOutParams) {
            $moneyAccountDocFolder = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_UPLOADS_ROOT
                . DirectoryStructure::FS_UPLOADS_MONEY_ACCOUNT_DOCUMENTS;

            if (!is_readable($moneyAccountDocFolder)) {
                echo "Folder does not exist: " . $moneyAccountDocFolder.PHP_EOL.PHP_EOL;
            } else {
                $moneyAccountDocsDiskData = array_diff(scandir($moneyAccountDocFolder), ['.','..']);
                $moneyAccountDocsDiskArray = [];

                foreach ($moneyAccountDocsDiskData as $moneyAccountDocDiskYear) {
                    $yearFilesList = array_diff(scandir($moneyAccountDocFolder.$moneyAccountDocDiskYear), ['.','..']);

                    foreach ($yearFilesList as $moneyAccountDocDiskMonth) {
                        $monthFilesList = array_diff(scandir($moneyAccountDocFolder.$moneyAccountDocDiskYear.'/'.$moneyAccountDocDiskMonth), ['.','..']);

                        foreach ($monthFilesList as $MoneyAccountsList) {
                            $moneyAccountsList = array_diff(scandir($moneyAccountDocFolder.$moneyAccountDocDiskYear.'/'.$moneyAccountDocDiskMonth .'/' . $MoneyAccountsList), ['.','..']);

                            foreach ($moneyAccountsList as $moneyAccountsDocsList) {
                                $docList = array_diff(scandir($moneyAccountDocFolder.$moneyAccountDocDiskYear.'/'.$moneyAccountDocDiskMonth .'/' . $MoneyAccountsList . '/' . $moneyAccountsDocsList), ['.','..']);

                                foreach ($docList as $file) {
                                    $moneyAccountDocsDiskArray[$moneyAccountDocDiskYear][$moneyAccountDocDiskMonth][$MoneyAccountsList][$moneyAccountsDocsList][] = $file;
                                }
                            }
                        }
                    }
                }


                $moneyAccountsDocsDbData = $this->getMoneyAccountAttachmentsData();

                foreach ($moneyAccountsDocsDbData as $moneyAccountDocDb) {


                    $createdDate = $moneyAccountDocDb->getCreatedDate();

                    $year  = date('Y', strtotime($createdDate));
                    $month = date('m', strtotime($createdDate));

                    $attachment = $moneyAccountDocFolder .
                        $year . '/' . $month . '/' .
                        $moneyAccountDocDb->getMoneyAccountId() . '/' .
                        $moneyAccountDocDb->getDocId() . '/' .
                        $moneyAccountDocDb->getAttachment();

                    if (   !empty($moneyAccountDocDb->getAttachment())
                        && ($moneyAccountDocDb->getAttachment() !== null)
                        && !is_file($attachment)
                    ) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            DbTables::TBL_MONEY_ACCOUNT_ATTACHMENT_ITEMS . '.id: '.$moneyAccountDocDb->getId().PHP_EOL
                            .'attachment: '.PHP_EOL
                            .$attachment
                        ]);
                    } elseif (   !empty($moneyAccountDocDb->getAttachment())
                        && ($moneyAccountDocDb->getAttachment() !== null)
                    ) {
                        $filename = $moneyAccountDocDb->getAttachment();
                        foreach ($moneyAccountDocsDiskArray[$year][$month][$moneyAccountDocDb->getMoneyAccountId()]
                                 as $moneyAccountDocFileKey => $moneyAccountDocFile)
                        {

                            foreach ($moneyAccountDocFile as $docFiles => $monAccDocFile) {

                                if ($monAccDocFile === $filename) {
                                    unset(
                                        $moneyAccountDocsDiskArray
                                        [$year]
                                        [$month]
                                        [$moneyAccountDocDb->getMoneyAccountId()]
                                        [$moneyAccountDocFileKey]
                                        [$docFiles]
                                    );
                                }
                            }
                        }
                    }
                }

                foreach ($moneyAccountDocsDiskArray as $year => $monthFiles) {
                    foreach ($monthFiles as $month => $monacsId) {
                        foreach ($monacsId as $monacId => $docsId) {
                            foreach ($docsId as $moneyAccountDocId => $moneyAccountFileList) {
                                foreach ($moneyAccountFileList as $moneyAccountFileKey => $moneyAccountDiskFile) {
                                    $unusedMoneyAccountDocFile = $moneyAccountDocFolder
                                        . $year . '/'
                                        . $month . '/'
                                        . $monacId . '/'
                                        . $moneyAccountDocId . '/'
                                        . $moneyAccountDiskFile;

                                    $unusedFiles[] = $unusedMoneyAccountDocFile;

                                    if (is_dir($unusedMoneyAccountDocFile)) {
                                        $table->appendRow([
                                            $this->runCounter(),
                                            'type: Money Account Documents'.PHP_EOL
                                            .'-=!=- UNKNOWN FOLDER -=!=- '.PHP_EOL
                                            .$unusedMoneyAccountDocFile,
                                            'x'
                                        ]);
                                    } else {
                                        $table->appendRow([
                                            $this->runCounter(),
                                            'type: Money Account Documents'.PHP_EOL
                                            .'filename: '.PHP_EOL
                                            .$unusedMoneyAccountDocFile,
                                            'x'
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * Task attachments
         */
        if ($this->onlyTaskAttachments || $this->withOutParams) {

            $taskAttachmentsFolder = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_UPLOADS_ROOT
                . DirectoryStructure::FS_UPLOADS_TASK_ATTACHMENTS;

            if (!is_readable($taskAttachmentsFolder)) {
                echo "Folder does not exist: " . $taskAttachmentsFolder . PHP_EOL . PHP_EOL;
            } else {
                $taskAttachmentsDiskData = array_diff(scandir($taskAttachmentsFolder), ['.','..']);
                $taskAttachmentsDiskArray = [];
                foreach ($taskAttachmentsDiskData as $year) {
                    $monthList = array_diff(scandir($taskAttachmentsFolder . $year), ['.','..']);

                    foreach ($monthList as $month) {
                        $dayList = array_diff(scandir($taskAttachmentsFolder . $year . '/' . $month), ['.','..']);

                        foreach ($dayList as $day) {
                            $idList = array_diff(scandir($taskAttachmentsFolder . $year . '/' . $month . '/' . $day), ['.','..']);
                            foreach ($idList as $id) {
                                $taskAttachmentsList = array_diff(scandir($taskAttachmentsFolder . $year . '/' . $month . '/' . $day . '/' . $id), ['.','..']);

                                foreach ($taskAttachmentsList as $file) {
                                    $taskAttachmentsDiskArray[$year][$month][$day][$id][] = $file;
                                }
                            }
                        }
                    }
                }

                $taskAttachmentsDbData = $this->getTaskAttachmentsData();

                foreach ($taskAttachmentsDbData as $taskAttachmentDb) {
                    $attachmentDate = explode('/', $taskAttachmentDb->getPath());

                    $attachment = $taskAttachmentsFolder
                        . $taskAttachmentDb->getPath() . '/' . $taskAttachmentDb->getTaskId()
                        . '/' . $taskAttachmentDb->getFile();

                    if (!empty($taskAttachmentDb->getFile()) AND !is_file($attachment)) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            'ga_task_attachments.id: ' . $taskAttachmentDb->getId() . PHP_EOL
                            . 'attachment: ' . PHP_EOL
                            . $attachment
                        ]);
                    } elseif (!empty($taskAttachmentDb->getFile())) {
                        $filename = $taskAttachmentDb->getFile();
                        $key = array_search($filename, $taskAttachmentsDiskArray[$attachmentDate[0]][$attachmentDate[1]][$attachmentDate[2]][$taskAttachmentDb->getTaskId()]);
                        if ($key !== false) {
                            unset($taskAttachmentsDiskArray[$attachmentDate[0]][$attachmentDate[1]][$attachmentDate[2]][$taskAttachmentDb->getTaskId()][$key]);
                        }
                    }
                }
                if (count($taskAttachmentsDiskArray)) {
                    foreach ($taskAttachmentsDiskArray as $year => $monthFiles) {
                        foreach ($monthFiles as $month => $dayFiles) {
                            foreach ($dayFiles as $day => $idFiles) {
                                foreach ($idFiles as $taskId => $files) {

                                    foreach ($files as $file) {
                                        $unusedTaskAttachmentFile = $taskAttachmentsFolder
                                            . $year . '/'
                                            . $month . '/'
                                            . $day . '/'
                                            . $taskId . '/'
                                            . $file;
                                        $unusedFiles[] = $unusedTaskAttachmentFile;

                                        if (is_dir($unusedTaskAttachmentFile)) {
                                            $table->appendRow([
                                                $this->runCounter(),
                                                'type: Task Attachments' . PHP_EOL
                                                . '-=!=- UNKNOWN FOLDER -=!=- ' . PHP_EOL
                                                . $unusedTaskAttachmentFile,
                                                'x'
                                            ]);
                                        } else {
                                            $table->appendRow([
                                                $this->runCounter(),
                                                'type: Task Attachments' . PHP_EOL
                                                . 'filename: ' . PHP_EOL
                                                . $unusedTaskAttachmentFile,
                                                'x'
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * Parking attachments
         */
        if ($this->onlyParkingAttachments || $this->withOutParams) {
            $parkingAttachmentsFolder = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_PARKING_ATTACHMENTS;

            if (!is_readable($parkingAttachmentsFolder)) {
                echo "Folder does not exist: " . $parkingAttachmentsFolder . PHP_EOL . PHP_EOL;
            } else {
                $parkingAttachmentsDiskData = array_diff(scandir($parkingAttachmentsFolder), ['.','..']);
                $parkingAttachmentsDiskArray = [];
                foreach ($parkingAttachmentsDiskData as $id) {
                    $parkingAttachmentsList = array_diff(scandir($parkingAttachmentsFolder . $id), ['.','..']);

                    foreach ($parkingAttachmentsList as $file) {
                        $parkingAttachmentsDiskArray[$id][] = $file;
                    }
                }

                $parkingAttachmentsDbData = $this->getParkingAttachmentsData();

                foreach ($parkingAttachmentsDbData as $parkingAttachmentDb) {

                    $attachment = $parkingAttachmentsFolder . '/' . $parkingAttachmentDb->getId()
                        . '/' . $parkingAttachmentDb->getParkingPermit();

                    if (!empty($parkingAttachmentDb->getParkingPermit()) AND !is_file($attachment)) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            'ga_parking_attachments.id: ' . $parkingAttachmentDb->getId() . PHP_EOL
                            . 'attachment: ' . PHP_EOL
                            . $attachment
                        ]);
                    } elseif (!empty($parkingAttachmentDb->getParkingPermit())) {
                        $filename = $parkingAttachmentDb->getParkingPermit();
                        $key = array_search($filename, $parkingAttachmentsDiskArray[$parkingAttachmentDb->getId()]);
                        if ($key !== false) {
                            unset($parkingAttachmentsDiskArray[$parkingAttachmentDb->getId()][$key]);
                        }
                    }
                }
                if (count($parkingAttachmentsDiskArray)) {
                    foreach ($parkingAttachmentsDiskArray as $parkingId => $files) {
                        foreach ($files as $file) {
                            $unusedParkingAttachmentFile = $parkingAttachmentsFolder
                                . $parkingId . '/'
                                . $file;
                            $unusedFiles[] = $unusedParkingAttachmentFile;

                            if (is_dir($unusedParkingAttachmentFile)) {
                                $table->appendRow([
                                    $this->runCounter(),
                                    'type: Parking Attachments' . PHP_EOL
                                    . '-=!=- UNKNOWN FOLDER -=!=- ' . PHP_EOL
                                    . $unusedParkingAttachmentFile,
                                    'x'
                                ]);
                            } else {
                                $table->appendRow([
                                    $this->runCounter(),
                                    'type: Parking Attachments' . PHP_EOL
                                    . 'filename: ' . PHP_EOL
                                    . $unusedParkingAttachmentFile,
                                    'x'
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // Apartel Images
        if ($this->onlyApartelImages || $this->withOutParams) {
            $apartelImagesPath = DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_APARTEL_BG_IMAGE;

            if (!is_readable($apartelImagesPath)) {
                echo "Folder does not exist: " . $apartelImagesPath . PHP_EOL.PHP_EOL;
            } else {
                $apartelImagesDiskData = array_diff(scandir($apartelImagesPath), ['.','..']);
                $apartelImagesDiskArray = [];

                foreach ($apartelImagesDiskData as $apartelImageDisk) {
                    $thisFilesList = array_diff(scandir($apartelImagesPath.$apartelImageDisk), ['.','..']);

                    foreach ($thisFilesList as $file) {
                        $apartelImagesDiskArray[$apartelImageDisk][] = $file;
                    }
                }

                $apartelImagesDbData = $this->getApartelImagesData();

                foreach ($apartelImagesDbData as $apartelImageDb) {
                    /**
                     * @var \DDD\Domain\Apartel\Details\Details $apartelImageDb
                     */
                    $bgImage = $apartelImagesPath . $apartelImageDb->getApartelId() . '/' . $apartelImageDb->getBgImage();

                    if (!empty($apartelImageDb->getBgImage()) && $apartelImageDb->getBgImage() !== null && !is_file($bgImage)) {
                        $table->appendRow([
                            $this->runCounter(),
                            'x',
                            DbTables::TBL_APARTELS_DETAILS . '.id: ' . $apartelImageDb->getId() . PHP_EOL
                                . 'apartel id: ' . $apartelImageDb->getApartelId() . PHP_EOL
                                . 'cover_image: ' . PHP_EOL
                                . $bgImage
                        ]);
                    } elseif (!empty($apartelImageDb->getBgImage()) && $apartelImageDb->getBgImage() !== null) {
                        $filename = explode('_', $apartelImageDb->getBgImage())[0];

                        foreach ($apartelImagesDiskArray[$apartelImageDb->getApartelId()] as $apartelFileKey => $apartelFile) {
                            if (strstr($apartelFile, $filename)) {
                                unset($apartelImagesDiskArray[$apartelImageDb->getApartelId()][$apartelFileKey]);
                            }
                        }
                    }
                }

                foreach ($apartelImagesDiskArray as $folder => $apartelDisk) {
                    foreach ($apartelDisk as $apartelDiskFiles) {
                        $unusedApartelFile = $apartelImagesPath.$folder . '/' . $apartelDiskFiles;
                        $unusedFiles[] = $unusedApartelFile;

                        if (is_dir($unusedApartelFile)) {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Apartel Image'.PHP_EOL
                                .'-=!=- UNKNOWN FOLDER -=!=- '.PHP_EOL
                                .$unusedApartelFile,
                                'x'
                            ]);
                        } else {
                            $table->appendRow([
                                $this->runCounter(),
                                'type: Apartel Image'.PHP_EOL
                                .'filename: '.PHP_EOL
                                .$unusedApartelFile,
                                'x'
                            ]);
                        }
                    }
                }
            }
        }

        // Purchase Order
        if ($this->onlyPurchaseOrder || $this->withOutParams) {
            $this->matchAttachments($table, [
                'id' => 'id',
                'date' => 'date',
                'filename' => 'filename',
                'dir' => '/ginosi/uploads/expense',
                'type' => 'Purchase Order Attachment ',
                'depth' => 4,
                'sql' => '
                    select
                        ga_expense.id as id,
                        ga_expense.date_created as date,
                        ga_expense_attachments.filename
                    from ga_expense_attachments
                        left join ga_expense on ga_expense.id = ga_expense_attachments.expense_id
                ',
            ]);
        }


        // Purchase Order Items
        if ($this->onlyPurchaseOrderItems || $this->withOutParams) {
            $this->matchAttachments($table, [
                'id' => 'id',
                'filename' => 'filename',
                'dir' => '/ginosi/uploads/expense/items',
                'type' => 'Purchase Order Item Attachment ',
                'depth' => 5,
                'sql' => '
                    select
                        filename
                    from ga_expense_item_attachments
                    where expense_id IS NOT NULL
                ',
            ]);

            $this->matchAttachments($table, [
                'id' => 'id',
                'filename' => 'filename',
                'dir' => '/ginosi/uploads/expense/items_tmp',
                'type' => 'Purchase Order Temporary Items Attachment ',
                'depth' => 1,
                'sql' => '
                    select
                        filename
                    from ga_expense_item_attachments
                    where expense_id IS NULL
                ',
            ]);
        }

        echo PHP_EOL.'Rendering table...'.PHP_EOL;
        sleep(1);

        echo $table;

        /**
         * REMOVE UNUSED FILES FROM DISK
         */
        if (count($unusedFiles) > 0 AND $this->removeFromDisk) {
            echo PHP_EOL;
            echo "\e[1;31m!!! WARNING !!!\e[0m";

            if ( Prompt\Confirm::prompt(PHP_EOL.'Are you absolutely sure you want to permanently delete unused files from hard disk? You have already created backup copy of these files? [y/n]'.PHP_EOL, 'y', 'n') )
            {
                echo PHP_EOL;
                $secretWord = Prompt\Line::prompt(
                    'Please say the magic word and press [ENTER]: ',
                    false,
                    30
                );

                echo PHP_EOL.PHP_EOL;

                if ($secretWord === 'please') {
                    echo "go go go..." . PHP_EOL;

                    foreach ($unusedFiles as $fileToRemove) {
                        if (is_writable($fileToRemove)) {
                            if (is_dir($fileToRemove)) {
                                $this->delTree($fileToRemove);
                            } else {
                                unlink($fileToRemove);
                            }
                            echo "Removed: " . $fileToRemove . PHP_EOL;
                        } else {
                            echo "Cannot remove file (permissions denied): " . $fileToRemove . PHP_EOL;
                        }
                    }
                } else {
                    echo "Wrong! Try again :P" . PHP_EOL;
                }
            } else {
                echo "You chose NO :(" . PHP_EOL;
            }
        }

        echo PHP_EOL . 'Reporting done!' . PHP_EOL . PHP_EOL;
    }

    /**
     * Remove folder recursive
     *
     * @param string $dir
     * @return boolean
     */
    public static function delTree($dir)
    {
        try {
            if (!is_dir($dir)) {
                return false;
            }

            $removedCount = 0;

            $files = array_diff(scandir($dir), ['.','..']);

            foreach ($files as $file) {

                if (is_dir("$dir/$file")) {
                    self::delTree("$dir/$file");
                } else {
                    if(unlink("$dir/$file")) {
                        $removedCount++;
                    }
                }
            }

            if (rmdir($dir)) {
                $removedCount++;
                return $removedCount;
            } else {
                return false;
            }

        } catch (\Exception $e) {
            echo PHP_EOL."[ \e[0;31mWARN\e[0m ] Cannot delete folder: ".print_r($e->getMessage()).PHP_EOL.PHP_EOL;
            return false;
        }
    }

    private function getLocationsData()
    {
        $locationsDao = $this->getServiceLocator()->get('dao_geolocation_details');

        $result = $locationsDao->fetchAll(function(Select $select) {
            $select->columns(array(
                'id',
                'name',
                'cover_image',
                'thumbnail'
            ));
        });

        return $result;
    }

    private function getProfilesData()
    {
        $locationsDao = $this->getServiceLocator()->get('dao_user_user_manager');

        $result = $locationsDao->fetchAll(function(Select $select) {
            $select->columns(array(
                'id',
                'firstname',
                'lastname',
                'avatar'
            ));
        });

        return $result;
    }

    private function getApartmentsData()
    {
        $locationsDao = $this->getServiceLocator()->get('dao_apartment_media');

        $result = $locationsDao->fetchAll(function(Select $select) {
            $select->columns(array(
                'id', 'apartment_id',
                'img1', 'img2', 'img3', 'img4', 'img5', 'img6', 'img7', 'img8',
                'img9', 'img10', 'img11', 'img12', 'img13', 'img14', 'img15',
                'img16', 'img17', 'img18', 'img19', 'img20', 'img21', 'img22',
                'img23', 'img24', 'img25', 'img26', 'img27', 'img28', 'img29',
                'img30', 'img31', 'img32'
            ));
        });

        return $result;
    }

    private function getDocumentsData()
    {
        /* @var $documentDao \DDD\Dao\Document\Document */
        $documentDao = $this->getServiceLocator()->get('dao_document_document');

        $result = $documentDao->fetchAll(function(Select $select) {
            $select->columns(array(
                'id',
                'created_date',
                'attachment'
            ));
        });

        return $result;
    }

    private function getBuildingsMapsData()
    {
        /** @var \DDD\Dao\ApartmentGroup\BuildingDetails $buildingDetailsDao */
        $buildingDetailsDao     = $this->getServiceLocator()->get('dao_apartment_group_building_details');

        $result = $buildingDetailsDao->fetchAll(function(Select $select) {
            $select->columns(array(
                'id',
                'building_id' => 'apartment_group_id',
                'map_attachment'
            ));
        });

        return $result;
    }

    private function getOfficeMapsData()
    {
        /** @var \DDD\Dao\Office\OfficeManager $officeDao */
        $officeDao = $this->getServiceLocator()->get('dao_office_office_manager');

        $result = $officeDao->fetchAll(function(Select $select) {
            $select->columns([
                'id',
                'map_attachment'
            ]);
        });

        return $result;
    }

    private function getBlogsData()
    {
        $locationsDao = $this->getServiceLocator()->get('dao_blog_blog');

        $result = $locationsDao->fetchAll(function(Select $select) {
            $select->columns(array(
                'id',
                'img'
            ));
        });

        return $result;
    }

    private function getExpensesData()
    {
        $locationsDao = $this->getServiceLocator()->get('dao_expense_expense');

        $result = $locationsDao->fetchAll(function(Select $select) {
            $select->columns(array(
                'id',
                'attachment'
            ));
        });

        return $result;
    }

    private function getUsersDocumentsData()
    {
        $locationsDao = $this->getServiceLocator()->get('dao_user_document_documents');

        $result = $locationsDao->fetchAll(function(Select $select) {
            $select->columns(array(
                'id',
                'user_id',
                'attachment'
            ));
        });

        return $result;
    }

    private function getJobsDocumentsData()
    {
        $applicantsDao = $this->getServiceLocator()->get('dao_recruitment_applicant_applicant');

        $result = $applicantsDao->fetchAll(function(Select $select) {
            $select->columns(array(
                'id',
                'cv',
                'date_applied'
            ));
        });

        return $result;
    }

    private function getReservationAttachmentsData()
    {
        $BookingDocFileDao = $this
            ->getServiceLocator()
            ->get('dao_booking_attachment_item');

        $result = $BookingDocFileDao->fetchAll(
            function(Select $select) {
                $select->join(
                    ['d' => DbTables::TBL_RESERVATION_ATTACHMENTS],
                    'ga_reservation_attachment_items.doc_id = d.id',
                    ['created_date'],
                    Select::JOIN_LEFT
                );
                $select->columns(
                    [
                        'id',
                        'reservation_id',
                        'doc_id',
                        'attachment'
                    ]
                );
            }
        );

        return $result;
    }

    private function getMoneyAccountAttachmentsData()
    {
        $moneyAccountDocFileDao = $this
            ->getServiceLocator()
            ->get('dao_money_account_attachment_item');

        $result = $moneyAccountDocFileDao->fetchAll(
            function(Select $select) {
                $select->join(
                    ['d' => DbTables::TBL_MONEY_ACCOUNT_ATTACHMENTS],
                    'ga_money_account_attachment_items.doc_id = d.id',
                    ['created_date'],
                    Select::JOIN_LEFT
                );
                $select->columns(
                    [
                        'id',
                        'money_account_id',
                        'doc_id',
                        'attachment'
                    ]
                );
            }
        );

        return $result;
    }

    private function getTaskAttachmentsData()
    {
        $taskAttachmentsDao = $this
            ->getServiceLocator()
            ->get('dao_task_attachments');

        $result = $taskAttachmentsDao->fetchAll(
            function(Select $select) {
                $select->join(
                    ['tasks' => DbTables::TBL_TASK],
                    DbTables::TBL_TASK_ATTACHMENTS . '.task_id = tasks.id',
                    ['path'  => new Expression('REPLACE(DATE(creation_date), "-", "/")')],
                    Select::JOIN_LEFT
                );
                $select->columns(
                    [
                        'id',
                        'task_id',
                        'file'
                    ]
                );
            }
        );

        return $result;
    }

    private function getParkingAttachmentsData()
    {
        $parkingAttachmentsDao = $this->getServiceLocator()->get('dao_parking_general');

        $result = $parkingAttachmentsDao->fetchAll(
            function(Select $select) {
                $select->columns(
                    [
                        'id',
                        'parking_permit'
                    ]
                );
            }
        );

        return $result;
    }

    /**
     * @return \DDD\Dao\Apartel\Details[]
     */
    private function getApartelImagesData()
    {
        $locationsDao = $this->getServiceLocator()->get('dao_apartel_details');

        $result = $locationsDao->fetchAll(function(Select $select) {
            $select->columns(array(
                'id',
                'apartel_id',
                'bg_image'
            ));
        });

        return $result;
    }

    private function runCounter()
    {
        return (string)$this->counter++;
    }

    /**
     * Generic function to find matches
     * Tested for expense attachments
     *
     * @param Table $table
     * @param array $options
     * [
     *      id => 'id',
     *      filename => 'filename',
     *      dir      => '/ginosi/uploads/expense',
     *     'type'    => 'Purchase Order Ticket'
     *     'depth'    => 4
     *      sql      => 'select filename, from ga_expense_attachments'
     * ]
     */
    private function matchAttachments(Table $table, array $options)
    {
        /**
         * @var Adapter $dbAdapter
         */
        $dbAdapter = $this->getServiceLocator()->get('dbadapter');
        $rows = $dbAdapter->createStatement($options['sql'])->execute();
        $dbFileNames = [];
        foreach ($rows as $row) {
            $dbFileNames[] = $row['filename'];
        }
        $dirStr = '';
        for ($i = 1; $i <= $options['depth']; $i++) {
            $dirStr .= '/*';
        }
        $files = glob($options['dir'] . $dirStr . '/*.*');
        $diskFileNames = [];
        foreach ($files as $file) {
            $diskFileNames[] = basename($file);
        }

        $missingOnDisk = array_diff($dbFileNames, $diskFileNames);
        $missingOnDb = array_diff($diskFileNames, $dbFileNames);


        foreach ($missingOnDb as $missingFileOnDb) {
            // 'Missing on db - ' . $match . PHP_EOL;
            $table->appendRow([
                $this->runCounter(),
                'type: ' . $options['type'] . PHP_EOL . 'filename: ' . $missingFileOnDb,
                'x',
            ]);
        }

        foreach ($missingOnDisk as $missingFileOnDisk) {
            $table->appendRow([
                $this->runCounter(),
                'x',
                $options['type']  . PHP_EOL . $missingFileOnDisk,
            ]);
        }
    }

    public function optimizeTablesAction()
    {
        $start = microtime(true);
        $oDbTables = new \ReflectionClass('Library\Constants\DbTables');
        $tables = $oDbTables->getConstants();
        $dbAdapter = $this->getServiceLocator()->get('dbAdapter');
        $dbSizeSql = 'SELECT table_schema as db,
  sum( data_length + index_length ) / 1024 / 1024 as size
FROM information_schema.TABLES
WHERE table_schema = "backoffice";';
        $dbSizeBefore = $dbAdapter->createStatement($dbSizeSql)->execute()->current();
        $dbSizeBefore = round($dbSizeBefore['size'], 2);
        foreach ($tables as $table) {
            $iterationStart = microtime(true);
            $sql = "OPTIMIZE TABLE $table";
            $dbAdapter->createStatement($sql)->execute();
            $iterationEnd = microtime(true);
            echo "\033[0;36m" . 'Table ' . "\033[1;36m" . $table . "\033[0;36m" . ' optimized in ' . "\033[1;36m" . round($iterationEnd - $iterationStart, 2) . "\033[0;36m" . ' seconds!' . "\033[0m" . PHP_EOL;
        }
        $dbSizeAfter = $dbAdapter->createStatement($dbSizeSql)->execute()->current();
        $dbSizeAfter = round($dbSizeAfter['size'], 2);
        $end = microtime(true);
        echo PHP_EOL . "\033[0;32m" . 'Process finished in ' . "\033[1;32m" .
            round($end - $start, 2) . "\033[0;32m" . ' seconds' . PHP_EOL .
            "\033[0;32m" . 'Database size before optimization: ' . "\033[1;32m" . $dbSizeBefore . ' Mb' . PHP_EOL .
            "\033[0;32m" . 'Database size after optimization:  ' . "\033[1;32m" . $dbSizeAfter . ' Mb' . PHP_EOL .
            "\033[0;32m" . 'Total space relieved:  ' . "\033[1;32m" . ($dbSizeBefore - $dbSizeAfter) . ' Mb' . "\033[0m" . PHP_EOL; exit();
    }
}
