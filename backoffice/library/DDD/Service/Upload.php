<?php

namespace DDD\Service;

use DDD\Service\ServiceBase;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Library\Upload\Images;
use Library\Upload\Files;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Roles;

use FileManager\Constant\DirectoryStructure;

class Upload extends ServiceBase
{
    protected $userLocator = false;

    /**
     * @var BackofficeAuthenticationService $backofficeAuthenticationService
     * @param int $userId
     * @param array $file
     * @return string|boolean
     */
    public function updateAvatar($userId, Array $file)
    {
        /**
         * @var \DDD\Dao\User\UserManager $usersDao
         */
        $usersDao = $this->getServiceLocator()->get('dao_user_user_manager');

        // get user true id from session
        $backofficeAuthenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $authStorage = $backofficeAuthenticationService->getStorage()->read();
        $trueId      = $authStorage->id;

        // check requested user id with session
        if ($trueId != $userId && !$backofficeAuthenticationService->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR)) {
            $result['status'] = 'error';
            $result['msg']    = TextConstants::INVALID_USER_ID;
            return $result;
        }

        $image = new Images($file);

        // image type not true
        if ($image->errors) {
            $result['status'] = 'error';
            $result['msg'] = $image->errors;
            return $result;
        }

        // resize image(s)
        $profileImagesPath = DirectoryStructure::FS_GINOSI_ROOT
            . DirectoryStructure::FS_IMAGES_ROOT
            . DirectoryStructure::FS_IMAGES_PROFILE_PATH;

        if ($image->resizeToSquare([150, 40, 18]) && $image->saveImage($profileImagesPath . $userId . '/')) {
            //delete old avatar from file system
            $userProfile = $usersDao->fetchOne('`id` = '.$userId);
            $oldAvatar = $userProfile->getAvatar();

            $oldAvatarFile = explode('_', $oldAvatar);

            if ($oldAvatarFile[0] !== '') {
                $mask = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_IMAGES_ROOT
                    . DirectoryStructure::FS_IMAGES_PROFILE_PATH
                    . $userId . '/' . $oldAvatarFile[0] . '*';
                @array_map("unlink", glob($mask));
            }

            $usersDao->save([
                'avatar' => $image->filenames[0]['150'],
            ], ['id' => $userId]);
        }

        return $image->filenames[0]['150'];
    }

    public function saveToTemp(Array $file)
    {
        $image = new Images($file);

        // image type not true
        if ($image->errors) {
            $result['status'] = 'error';
            $result['msg'] = $image->errors;
            return $result;
        } else {
            return $image->saveImage(DirectoryStructure::FS_GINOSI_ROOT
                . DirectoryStructure::FS_IMAGES_ROOT
                . DirectoryStructure::FS_IMAGES_TEMP_PATH);
        }
    }

    /**
     *
     * @param array $file File information from $request->getFiles()
     * @return string|boolean Return moved file full path or false
     */
    public function moveToTemp($file)
    {
        $uploadTo = substr(DirectoryStructure::FS_GINOSI_ROOT
            . DirectoryStructure::FS_UPLOADS_ROOT
            . DirectoryStructure::FS_UPLOADS_TMP, 0, -1);

        if (!is_dir($uploadTo)) {
            mkdir($uploadTo, 0775, true);
        }

        $tempPath = $uploadTo . '/' . time() . '_' . $file['name'];

        if (move_uploaded_file($file['tmp_name'], $tempPath)) {
            return $tempPath;
        }

        return false;

    }

    public function uploadImages($ownerId, array $files, $ownerFileName = null, $imagesOwner = null)
    {
        $myTime = time();

        $cropImages = new Images($files);

        if ($cropImages->errors) {
            return ['error' => true, "msg" => $cropImages->errors];
        }

        $cropImages->cropImages([
            ['h' => 300, 'w' => 445],
            ['h' => 520, 'w' => 780],
            ['h' => 64,  'w' => 96],
            ['h' => 370, 'w' => 555]
        ]);

        $imagePath = DirectoryStructure::FS_GINOSI_ROOT
            . DirectoryStructure::FS_IMAGES_ROOT
            . DirectoryStructure::FS_IMAGES_APARTMENT
            . $ownerId . '/';

        $cropImages->saveImage(
            $imagePath,
            75,
            false,
            true,
            'orig',
            'jpg',
            $myTime,
            true,
            $ownerFileName,
            $imagesOwner
        );

        return $cropImages->filenames;
    }
}
