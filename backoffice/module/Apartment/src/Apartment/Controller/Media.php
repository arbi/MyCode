<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Library\Constants\TextConstants;
use Library\Utility\Debug;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Library\Constants\Constants;
use Library\Constants\DomainConstants;
use FileManager\Constant\DirectoryStructure;

use Apartment\Form\Media as MediaForm;
use Library\Utility\Helper;

use Library\Utility\PathZipper;

class Media extends ApartmentBaseController
{
	public function indexAction()
    {
        /**
         * @var JsonModel $images
         * @var \DDD\Service\Apartment\Media $apartmentMediaService
         */
        $apartmentMediaService = $this->getServiceLocator()->get('service_apartment_media');

        $formData     = $apartmentMediaService->getAccVideoLinks($this->apartmentId);
        $images       = $this->getImages();

        $form = new MediaForm();
		$form->prepare();
		$form->populateValues($formData);

        // passing form and map to the view
		$viewModelForm = new ViewModel();
		$viewModelForm->setVariables([
            'form'      => $form,
            'formData'  => $formData,
            'hasImages' => (bool)$images['count'],
		]);
		$viewModelForm->setTemplate('form-templates/media');

		$viewModel = new ViewModel();
		$viewModel->setVariables([
            'apartmentId'     => $this->apartmentId,
            'apartmentStatus' => $this->apartmentStatus,
		]);

		// child view to render form
		$viewModel->addChild($viewModelForm, 'formOutput');
		$viewModel->setTemplate('apartment/media/index');

		return $viewModel;
	}

    public function saveAction()
    {
		$request = $this->getRequest();
        $result = array(
            "status" => "error",
            "msg"    => "Something went wrong. Cannot save video links."
        );

		if ($request->isXmlHttpRequest() OR $request->isPost()) {
			$postData = $request->getPost();

			if (count($postData)) {
				$form = new MediaForm('apartment_media');
				$form->setData($postData);
				$form->prepare();

				if ($form->isValid()) {
					$data = $form->getData();
                    unset($data['save_button']);

                    /**
                     * @var \DDD\Service\Apartment\Media $apartmentMediaService
                     */
                    $apartmentMediaService = $this->getServiceLocator()->get('service_apartment_media');

                    $apartmentMediaService->saveVideos($this->apartmentId, $data);

                    $result = [
                        "status" => "success",
                        "msg" => "Video links successfully updated",
                    ];
				} else {
                    $result = [
                        "status" => "error",
                        "msg" => $form->getMessages(),
                    ];
				}
			}
        }

		Helper::setFlashMessage([$result['status'] => $result['msg']]);

        return new JsonModel($result);
	}

    public function ajaxGetImagesAction()
    {
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' => TextConstants::SERVER_ERROR,
            'images' => [],
        ];

        if ($request->isPost() && $request->isXmlHttpRequest()) {
            try {
                $images = $this->getImages();
                $result = [
                    'status' => 'success',
                    'msg' => TextConstants::SUCCESS_FOUND,
                    'images' => $images['images'],
                ];
            } catch (\Exception $e) {
                $result['msg'] = $e->getMessage();
            }
        } else {
            $result['msg'] = TextConstants::ERROR_BAD_REQUEST;
        }

        return new JsonModel($result);
    }

    private function getImages()
    {
        /**
         * @var \DDD\Service\Apartment\Media $apartmentMediaService
         */
        $apartmentMediaService = $this->getServiceLocator()->get('service_apartment_media');

        $request = $this->getRequest();
        $result = [
            'images' => [],
            'count' => 0,
        ];

        if ($request->getPost('size', false) AND in_array($request->getPost('size'), ['40', '70', '110', '445', '500', 'orig'])) {
            $size = $request->getPost('size');
        } else {
            $size = 'orig';
        }

        $imagesOriginal = $apartmentMediaService->getAccImages($this->apartmentId);

        foreach ($imagesOriginal as $key => $image) {
            if ($image !== ''
                AND $image !== NULL
                AND file_exists(DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_IMAGES_ROOT
                    . substr($image, 1))
                AND file_exists(
                    DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_IMAGES_ROOT
                    . substr(
                        str_replace(
                            ['original', 'orig', '.png', '.gif'],
                            [$size, $size, '.jpg', '.jpg'], $image
                        ), 1
                    )
                )
            ) {
                $result['images'][$key] = str_replace(
                    ['original', 'orig', '.png', '.gif'],
                    [$size, $size, '.jpg', '.jpg'],
                    '//' . DomainConstants::IMG_DOMAIN_NAME . $image
                );

                $result['count']++;
            } elseif ($image !== '' AND $image !== NULL) {
                $result['images'][$key] = '//' . DomainConstants::BO_DOMAIN_NAME . Constants::VERSION . 'img/image_not_found.png';
            } else {
                $result['images'][$key] = '';
            }
        }

        return $result;
    }

    public function ajaxSetSortAction()
    {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception('Need AJAX request');
            }

            /**
             * @var \DDD\Service\Apartment\Media $apartmentMediaService
             */
            $apartmentMediaService = $this->getServiceLocator()->get('service_apartment_media');

            $imagesOriginal = $apartmentMediaService->getAccImages($this->apartmentId);
            $params = $this->params();
            $reSerted = [];

            foreach ($params->fromPost('values') as $key => $item) {
                $reSerted['img' . ($key + 1)] = $imagesOriginal['img' . $item];
            }

            $apartmentMediaService->saveImagesSort($this->apartmentId, $reSerted);

            return new JsonModel([
                'msg' => 'Images are re-sorted successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'status'=>'error',
                'msg' => $e->getMessage(),
            ]);
        }
    }

    public function ajaxDeleteImagesAction()
    {
        try {
            if (!$this->getRequest()->isXmlHttpRequest()) {
                throw new \Exception('Need AJAX request');
            }

            $params = $this->params();

            /**
             * @var \DDD\Service\Apartment\Media $apartmentMediaService
             */
            $apartmentMediaService = $this->getServiceLocator()->get('service_apartment_media');

            $imageNumbers = explode(',', $params->fromPost('imageNumbers'));

            foreach($imageNumbers as $i => $imageNumber) {
                //After i items have been deleted the new number for next image will be decreased by i
                $imageNumber -= $i;

                if (is_numeric($imageNumber) && (int)$imageNumber >= 1 && (int)$imageNumber <= 32) {
                    $apartmentMediaService->deleteImage($this->apartmentId, $imageNumber);
                } else {
                    throw new \Exception('Invalid number of image');
                }
            }

            return new JsonModel([
                'msg'=>'Images removed successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonModel([
                'status'=>'error',
                'msg' => $e->getMessage(),
            ]);
        }
    }

    public function ajaxUploadImagesAction()
    {
        try {
            $request = $this->getRequest();

            if (!$request->isPost()) {
                throw new \Exception('Need POST request');
            }

            /**
             * @var \DDD\Service\Upload $uploadService
             */
            $uploadService = $this->getServiceLocator()->get('service_upload');

            $uploadImages = $request->getFiles();
            $files = $uploadImages['files'];

            $count = count($files);

            if ($count > 5) {
                $result = [
                    'msg'       => 'You can not upload more than five images at once',
                    'status'    => 'error'
                ];
                return new JsonModel($result);
            }

            if ($count > 1) {
                $result = [
                    'msg'       => 'Images uploaded successfully',
                    'status'    => 'success'
                ];
            } elseif ($count > 0) {
               $result = [
                    'msg'       => 'Image uploaded successfully',
                    'status'    => 'success'
                ];
            } else {
                $result = [
                    'msg'       => 'Files not uploaded!',
                    'status'    => 'error'
                ];
            }

            if(!empty($files)) {
                $uploadResult = $uploadService->uploadImages($this->apartmentId, $files);

				if (   is_array($uploadResult)
					&& isset($uploadResult['error'])
					&& $uploadResult['error']
				) {
					throw new \Exception($uploadResult['msg']);
				}

                /**
                 * @var \DDD\Service\Apartment\Media $apartmentMediaService
                 */
                $apartmentMediaService = $this->getServiceLocator()->get('service_apartment_media');

                $currentSavedImages = $apartmentMediaService->getAccImages($this->apartmentId);

                $emptyKeys = [];
                foreach ($currentSavedImages as $key => $image) {
                    if (empty($image)) {
                        $emptyKeys[] = $key;
                    }
                }

                $saveToDb = [];
                $path = str_replace(DirectoryStructure::FS_GINOSI_ROOT . DirectoryStructure::FS_IMAGES_ROOT
                    , '/'
                    , DirectoryStructure::FS_GINOSI_ROOT
                        . DirectoryStructure::FS_IMAGES_ROOT
                        . DirectoryStructure::FS_IMAGES_APARTMENT);
                $cnt = 0;

                foreach ($uploadResult as $savedFiles) {
                    if (is_string($savedFiles)) {
                        $result['msg'] = $savedFiles;
                        $result['status'] = 'error';

                    } elseif ($cnt < count($emptyKeys)) {
                        $saveToDb[$emptyKeys[$cnt]] = $path.$this->apartmentId . '/' . $savedFiles['original'];
                    } else {
                        $result = [
                            'msg'       => 'Not all images sufficed space in the database! Max count: 32',
                            'status'    => 'info'
                        ];
                    }

                    $cnt++;
                }

                if (!empty($saveToDb)) {
                    $apartmentMediaService->saveNewImages($this->apartmentId, $saveToDb);
                }
            }

            return new JsonModel($result);

        } catch (\Exception $e) {
            return new JsonModel([
                'status' =>'error',
                'msg'    => $e->getMessage()
            ]);
        }
    }

    public function downloadAllImagesAction()
    {
        $theFolder = DirectoryStructure::FS_GINOSI_ROOT
            . DirectoryStructure::FS_IMAGES_ROOT
            . DirectoryStructure::FS_IMAGES_APARTMENT
            . $this->apartmentId;

        $zipFileName = DirectoryStructure::FS_GINOSI_ROOT
            . DirectoryStructure::FS_IMAGES_ROOT
            . DirectoryStructure::FS_IMAGES_TEMP_PATH
            . 'Apartment_' . $this->apartmentId . '_Images_' . date('Y.m.d_H:m:s') . '.zip';

        $za = new PathZipper();
        $res = $za->open($zipFileName, \ZipArchive::CREATE);

        if ($res === TRUE) {
            $za->addDir($theFolder, basename($theFolder), 'orig');
            $za->close();
        } else  {
            echo 'Could not create a zip archive';
        }

        ob_get_clean();

        $fhandle = finfo_open(FILEINFO_MIME);
        $mime_type = finfo_file($fhandle, $zipFileName);
        header('Content-Type: ' . $mime_type);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Length: ".filesize($zipFileName));
        header("Content-Disposition: attachment; filename='" . basename($zipFileName) . "';");
        header("Content-Transfer-Encoding: binary");
        header("Accept-Ranges: bytes");
        readfile($zipFileName);

        unlink($zipFileName);
        exit;
    }
}
