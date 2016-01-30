<?php

namespace DDD\Service;

use DDD\Service\ServiceBase;
use FileManager\Constant\DirectoryStructure;
use Zend\Validator\EmailAddress;
use Zend\Validator\File\IsImage;

class MoneyAccountAttachment extends ServiceBase
{
    public function saveDocData($data, $id = null)
    {
        $moneyAccountDocDao = $this->getServiceLocator()->get('dao_money_account_document');
        if (is_null($id)) {
            $result = $moneyAccountDocDao->save($data);
        } else {
            $result = $moneyAccountDocDao->save($data, ['id' => $id]);
        }

        return $result;
    }

    public function uploadFile($request, $docId, $moneyAccountId)
    {
        try {
            $files = $request->getFiles();
            $time = time();

            foreach ($files as $key => $file) {
                $attachmentExtension = pathinfo($file['name'], PATHINFO_EXTENSION);

                // file attached
                if ($file['error'] !== 4) {
                    if ($file['error'] !== 0) {
                        throw new \Exception('File upload failed.');
                    }

                    if ($file['size'] > DirectoryStructure::FS_UPLOAD_MAX_FILE_SIZE) {
                        throw new \Exception('File size is too big.');
                    }

                    if (in_array($attachmentExtension, ['php', 'phtml', 'html', 'js'])) {
                        throw new \Exception('Invalid file format.');
                    }

                    $year  = date('Y');
                    $month = date('m');

                    $folderPath = DirectoryStructure::FS_GINOSI_ROOT
                        . DirectoryStructure::FS_UPLOADS_ROOT
                        . DirectoryStructure::FS_UPLOADS_MONEY_ACCOUNT_DOCUMENTS
                        . $year . '/' . $month . '/' . $moneyAccountId . '/' .$docId;

                    if (!is_dir($folderPath)) {
                        if (!mkdir($folderPath, 0777, true)) {
                            throw new \Exception('Upload failed. Can\'t create directory.');
                        }
                    }

                    $filename = join(
                        '_',
                        [
                            $moneyAccountId,
                            $docId,
                            $time,
                            ++$key
                        ]
                    );

                    $filename      = str_replace(' ', '_', $filename);
                    $filename      = implode('.', [$filename, $attachmentExtension]);
                    $fullPath      = implode('/', [$folderPath, $filename]);

                    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                        throw new \Exception('Cannot copy file.');
                    } else {
                        $moneyAccountAttachmentFileDao = $this->getServiceLocator()->get('dao_money_account_attachment_item');
                        $moneyAccountAttachmentFileDao->save(
                            [
                                'money_account_id' => $moneyAccountId,
                                'doc_id'         => $docId,
                                'attachment'     => $filename
                            ]
                        );
                    }
                }
            }
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function getAttachments($moneyAccountId)
    {
        $moneyAccountDocFileDao = $this->getServiceLocator()->get('dao_money_account_attachment_item');

        $responses = $this->getServiceLocator()->get('dao_money_account_document')->getAttachments($moneyAccountId);
        $resData   = [];

        foreach ($responses  as $response) {
            $files = $moneyAccountDocFileDao->fetchAll(
                [
                    'doc_id'         => $response->getId(),
                    'money_account_id' => $moneyAccountId
                ]
            );

            $date  = $response->getCreatedDate();
            $year  = date('Y', strtotime($date));
            $month = date('m', strtotime($date));

            $filePaths = [];
            foreach ($files as $file) {
                $path = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_UPLOADS_ROOT
                    . DirectoryStructure::FS_UPLOADS_MONEY_ACCOUNT_DOCUMENTS
                    . $year . '/' . $month . '/' . $moneyAccountId . '/'
                    . $response->getId() . '/' . $file->getAttachment();

                if (file_exists($path)) {
                    $filePaths[] = $path;
                }
            }
            $resData[] = [
                'id'           => $response->getId(),
                'createdDate'  => $response->getCreatedDate(),
                'attacher'     => $response->getFirstname() . ' ' . $response->getLastname(),
                'description'  => $response->getDescription(),
                'filePaths'    => $filePaths
            ];
        }
        return $resData;
    }

    public function deleteAttachment($docId, $moneyAccountId)
    {
        $moneyAccountDocDao = $this->getServiceLocator()->get('dao_money_account_document');
        $moneyAccountDocFileDao = $this->getServiceLocator()->get('dao_money_account_attachment_item');

        try {

            $moneyAccountDoc = $moneyAccountDocDao->fetchOne(
                ['id' => $docId]
            );

            if ($moneyAccountDoc) {
                $createdDate = $moneyAccountDoc->getCreatedDate();
                $year  = date('Y', strtotime($createdDate));
                $month = date('m', strtotime($createdDate));
                $docId = $moneyAccountDoc->getId();

                $fileFolder = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_UPLOADS_ROOT
                    . DirectoryStructure::FS_UPLOADS_MONEY_ACCOUNT_DOCUMENTS
                    . $year . '/' . $month . '/' . $moneyAccountId . '/' . $docId;

                $imageFolder = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_IMAGES_ROOT
                    . DirectoryStructure::FS_IMAGES_MONEY_ACCOUNT_PATH
                    . $year . '/' . $month . '/' . $moneyAccountId . '/' . $docId;

                $filesScan  = @scandir($fileFolder);
                $imagesScan = @scandir($imageFolder);
                if (is_array($filesScan) && is_array($imagesScan)) {
                    $files  = array_diff($filesScan, ['.', '..']);
                    $images = array_diff($imagesScan, ['.', '..']);
                    if (is_dir($fileFolder)) {
                        foreach ($files as $file) {
                            @unlink($fileFolder . '/' .$file);
                        }
                        @rmdir($fileFolder);
                    }
                    if (is_dir($imageFolder)) {
                        foreach ($images as $image) {
                            @unlink($imageFolder . '/' . $image);
                        }
                        @rmdir($imageFolder);
                    }
                }
                $moneyAccountDocDao->deleteWhere(
                    ['id' => $docId]
                );
                $moneyAccountDocFileDao->deleteWhere(
                    [
                        'money_account_id' => $moneyAccountId,
                        'doc_id'         => $docId,
                    ]
                );
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}
