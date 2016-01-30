<?php

namespace DDD\Service\User;

use DDD\Service\User as UserBase;

use Library\Constants\Roles;
use FileManager\Constant\DirectoryStructure;

class Documents extends UserBase
{
    /**
     *
     * @var \DDD\Dao\User\Document\Documents $_userDocumentsDao
     */
    protected $_userDocumentsDao = NULL;

    /**
     *
     * @var \DDD\Dao\User\Document\DocumentTypes $_userDocumentTypesDao
     */
    protected $_userDocumentTypesDao = NULL;

    /**
     * @param int|bool $typeId Document Type Id
     * @return \DDD\Domain\User\Document\DocumentTypes
     */
    public function getDocumentTypes($typeId = false)
    {
        $this->getUserDocumentTypesDao();
        return $this->_userDocumentTypesDao->getDocumentTypes($typeId);
    }

    /**
     *
     * @param int $userId
     * @return \DDD\Domain\User\Document\Documents
     */
    public function getUserDocumentsList($userId)
    {
        $this->getUserDocumentsDao();
        return $this->_userDocumentsDao->getDocumentsByUserId($userId);
    }

    /**
     *
     * @param int $documentId
     * @return \DDD\Domain\User\Document\Documents
     */
    public function getDocumentsData($documentId)
    {
        $this->getUserDocumentsDao();
        return $this->_userDocumentsDao->getDocumentsById($documentId);
    }

    /**
     *
     * @param array $data
     * @return boolean
     */
    public function addDocument(Array $data, $id = null)
    {
        try {
            $attachment = '';

            if (!empty($data['file']) && !empty($data['file']['fileInfo'])) {
                $file = $data['file']['fileInfo'];

                if (!is_null($id)) {
                    $document = $this->getDocumentsData($id);
                    $data['userId'] = $document->getUserId();
                }

                $attachmentPath = DirectoryStructure::FS_UPLOADS_USER_DOCUMENTS . $data['userId'];
                $fullAttachmentPath = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_UPLOADS_ROOT
                    . $attachmentPath;

                if (!is_dir($fullAttachmentPath)) {
                    mkdir($fullAttachmentPath, 0775, true);
                }

                $documentTypeTitle = $this->getDocumentTypes($data['typeId']);

                $documentType = preg_replace("/[^a-zA-Z0-9]/", "_", $documentTypeTitle->getTitle());

                $fileExtention = pathinfo($file['name'], PATHINFO_EXTENSION);

                if (!in_array(strtolower($fileExtention), ['jpg', 'jpeg', 'png', 'pdf', 'xls', 'xlsx', 'doc', 'docx', 'gif'])) {
                    return false;
                }

                $currentDate = time();

                $fileNewName = $currentDate . '_' . $documentType . '.' . $fileExtention;

                /**
                 * @var \League\Flysystem\Filesystem $filesystem
                 */
                $filesystem = $this->getServiceLocator()->get('BsbFlysystemManager')->get('uploads');

                $stream = fopen($file['tmp_name'], 'r+', true);
                $result = $filesystem->writeStream($attachmentPath.'/'.$fileNewName, $stream);
                fclose($stream);

                if ($result) {
                    $attachment = $fileNewName;

                    if (!is_null($id)) {
                        //delete old
                        if (isset($document) && !empty($document->getAttachment())) {
                            $filepath = $fullAttachmentPath . '/' .$document->getAttachment();

                            if (is_writable($filepath)) {
                                unlink($filepath);
                            } else {
                                $this->gr2err("Cannot delete file", [
                                    'file' => $filepath
                                ]);
                            }
                        }
                    }

                } else {
                    return false;
                }
            }

            $this->getUserDocumentsDao();

            if (!is_null($id)) {
                $saveArray =  [
                    'creator_id'   => $data['creatorId'],
                    'type_id'      => $data['typeId'],
                    'description'  => $data['description'],
                    'url'          => $data['url'],
                ];
                if ($attachment) {
                    $saveArray['attachment'] = $attachment;
                }
                unset($data['file']);
                return $this->_userDocumentsDao->save(
                    $saveArray,
                    ['id' => $id]
                );

            } else {
                return $this->_userDocumentsDao->save(
                    [
                        'user_id'      => $data['userId'],
                        'creator_id'   => $data['creatorId'],
                        'type_id'      => $data['typeId'],
                        'date_created' => date('Y-m-d H:i:s'),
                        'description'  => $data['description'],
                        'attachment'   => $attachment,
                        'url'          => $data['url'],
                    ]
                );
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     *
     * @param int $documentId
     * @return boolean
     */
    public function deleteDocument($documentId)
    {
        try {
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');

            if (!$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT) && !$auth->hasRole(Roles::ROLE_PEOPLE_MANAGEMENT_HR) ) {
                return false;
            }

            $this->getUserDocumentsDao();

            $documentData = $this->getDocumentsData($documentId);

            if ($documentData === false) {
                $this->gr2err('Cannot find document data for '.$documentId, [
                    'service' => 'User/Documents'
                ]);
                return false;
            }

            if (!empty($documentData->getAttachment())) {
                $filepath = DirectoryStructure::FS_GINOSI_ROOT
                    . DirectoryStructure::FS_UPLOADS_ROOT
                    . DirectoryStructure::FS_UPLOADS_USER_DOCUMENTS
                    . $documentData->getUserId()
                    . '/' . $documentData->getAttachment();

                if (is_writable($filepath)) {
                    unlink($filepath);
                } else {
                    $this->gr2err("Cannot delete file", [
                        'file' => $filepath
                    ]);
                }
            }

            return $this->_userDocumentsDao->delete([
                'id' => $documentId
            ]);

        } catch (\Exception $e) {
            $this->gr2err($e->getMessage(), [
                'service' => 'User/Documents'
            ]);
            return false;
        }
    }

    /**
     * @return \DDD\Dao\User\Document\Documents
     */
    public function getUserDocumentsDao()
    {
    	if ($this->_userDocumentsDao === null) {
    		$this->_userDocumentsDao = $this->getServiceLocator()->get('dao_user_document_documents');
        }

    	return $this->_userDocumentsDao;
    }

    /**
     * @return \DDD\Dao\User\Document\DocumentTypes
     */
    public function getUserDocumentTypesDao()
    {
    	if ($this->_userDocumentTypesDao === null) {
    		$this->_userDocumentTypesDao = $this->getServiceLocator()->get('dao_user_document_document_types');
        }

    	return $this->_userDocumentTypesDao;
    }
}
