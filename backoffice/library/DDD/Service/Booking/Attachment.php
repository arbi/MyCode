<?php

namespace DDD\Service\Booking;

use DDD\Service\ServiceBase;
use DDD\Service\Booking;
use FileManager\Constant\DirectoryStructure;
use Zend\Validator\EmailAddress;
use Zend\Validator\File\IsImage;

class Attachment extends ServiceBase
{
    public function saveDocData($data, $id = null)
    {
        $bookingDocDao = $this->getBookingDocDao();
        if (is_null($id)) {
            $result = $bookingDocDao->save($data);
        } else {
            $result = $bookingDocDao->save($data, ['id' => $id]);
        }

        return $result;
    }

    public function uploadFile($request, $docId, $bookingId)
    {
        try {

            $files = $request->getFiles();
            $time = time();

            foreach ($files as $key => $file) {

                if (substr($file['name'], -6) == 'tar.gz') {
                    $attachmentExtension = 'tar.gz';
                } else {
                    $attachmentExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
                }

                // file attached
                if ($file['error'] !== 4) {
                    if ($file['error'] !== 0) {
                        throw new \Exception('File upload failed.');
                    }

                    if ($file['size'] > DirectoryStructure::FS_UPLOAD_MAX_FILE_SIZE) {
                        throw new \Exception('File size is too big.');
                    }
                    if (!in_array(strtolower($attachmentExtension), ['pdf', 'jpg', 'jpeg', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'png', 'zip', 'rar', '7z', 'tar', 'tar.gz'])) {
                        throw new \Exception('Invalid file format.');
                    }

                    $year  = date('Y');
                    $month = date('m');

                    $folderPath = DirectoryStructure::FS_GINOSI_ROOT
                        . DirectoryStructure::FS_UPLOADS_ROOT
                        . DirectoryStructure::FS_UPLOADS_BOOKING_DOCUMENTS
                        . $year . '/' . $month . '/' . $bookingId . '/' .$docId;

                    if (!is_dir($folderPath)) {
                        if (!mkdir($folderPath, 0777, true)) {
                            throw new \Exception('Upload failed. Can\'t create directory.');
                        }
                    }

                    $filename = join(
                        '_',
                        [
                            $bookingId,
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
                        $bookingDocFileDao = $this->getBookingDocFileDao();
                        $bookingDocFileDao->save(
                            [
                                'reservation_id' => $bookingId,
                                'doc_id'         => $docId,
                                'attachment'     => $filename
                            ]
                        );
                    }
                }
            }
            return true;
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function getAttachments($bookingId)
    {
        /** @var \DDD\Dao\Booking\AttachmentItem $bookingDocFileDao */
        $bookingDocFileDao = $this->getBookingDocFileDao();

        $responses = $this->getBookingDocDao()->getAttachments($bookingId);
        $resData   = [];

        foreach ($responses  as $response) {
            $files = $bookingDocFileDao->fetchAll(
                [
                    'doc_id'         => $response->getId(),
                    'reservation_id' => $bookingId
                ]
            );

            $date  = $response->getCreatedDate();
            $year  = date('Y', strtotime($date));
            $month = date('m', strtotime($date));

            $filePaths = [];
            foreach ($files as $file) {
                $path = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_UPLOADS_ROOT
                    . DirectoryStructure::FS_UPLOADS_BOOKING_DOCUMENTS
                    . $year . '/' . $month . '/' . $bookingId . '/' .
                    $response->getId() . '/' . $file->getAttachment();
                if (file_exists($path)){
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

    public function deleteAttachment($docId, $bookingId) {

        $bookingDocDao     = $this->getBookingDocDao();
        $bookingDocFileDao = $this->getBookingDocFileDao();

        try {

            $bookingDoc = $bookingDocDao->fetchOne(
                ['id' => $docId]
            );

            if ($bookingDoc) {
                $createdDate = $bookingDoc->getCreatedDate();

                $year  = date('Y', strtotime($createdDate));
                $month = date('m', strtotime($createdDate));
                $docId = $bookingDoc->getId();

                $fileFolder = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_UPLOADS_ROOT
                    . DirectoryStructure::FS_UPLOADS_BOOKING_DOCUMENTS
                    . $year . '/' . $month . '/' . $bookingId . '/' .$docId;

                $imageFolder = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_IMAGES_ROOT
                    . DirectoryStructure::FS_IMAGES_BOOKING_PATH
                    . $year . '/' . $month . '/' . $bookingId . '/' .$docId;

                $filesScan  = @scandir($fileFolder);
                $imagesScan = @scandir($imageFolder);
                //docs
                if (is_array($filesScan) ) {

                    $files  = array_diff($filesScan, ['.', '..']);

                    if (is_dir($fileFolder)) {
                        foreach ($files as $file) {
                            @unlink($fileFolder . '/' .$file);
                        }
                        @rmdir($fileFolder);
                    }

                }

                //images

                if (is_array($imagesScan)) {

                    $images = array_diff($imagesScan, ['.', '..']);

                    if (is_dir($imageFolder)) {
                        foreach ($images as $image) {
                            @unlink($imageFolder . '/' . $image);
                        }
                        @rmdir($imageFolder);
                    }
                }

                $bookingDocDao->deleteWhere(
                    ['id' => $docId]
                );

                $bookingDocFileDao->deleteWhere(
                    [
                        'reservation_id' => $bookingId,
                        'doc_id'         => $docId,
                    ]
                );
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return \DDD\Dao\Booking\Attachment
     */
    public function getBookingDocDao()
    {
        if (!isset($this->dao_booking_document))
            $this->dao_booking_document =
                $this->getServiceLocator()->get('dao_booking_attachment');

        return $this->dao_booking_document;
    }

    public function getBookingDocFileDao()
    {
        if (!isset($this->dao_booking_document_file))
            $this->dao_booking_document_file =
                $this->getServiceLocator()->get('dao_booking_attachment_item');

        return $this->dao_booking_document_file;
    }
}
