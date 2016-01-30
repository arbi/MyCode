<?php

namespace DDD\Dao\User\Document;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Library\Utility\Debug;
use Zend\Db\Sql\Select;

class Documents extends TableGatewayManager
{
    protected $table = DbTables::TBL_USER_DOCUMENTS;

    public function __construct(
            $sm,
            $domain = 'DDD\Domain\User\Document\Documents')
    {
        parent::__construct($sm, $domain);
    }

    /**
     *
     * @param int $userId
     * @return \DDD\Domain\User\Document\Documents
     */
    public function getDocumentsByUserId($userId)
    {
        $result = $this->fetchAll(function (Select $select) use($userId) {
            $select->columns([
                'id', 'user_id', 'creator_id', 'type_id', 'date_created',
                'description', 'attachment', 'url'
            ]);

            $select->join(
                ['types' => DbTables::TBL_USER_DOCUMENT_TYPES],
                $this->getTable() . '.type_id = types.id',
                ['type' => 'title'],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable() . '.user_id' => $userId
            ]);

            $select->order($this->getTable() . '.date_created DESC');
        });

        return $result;
    }

    /**
     *
     * @param int $documentId
     * @return \DDD\Domain\User\Document\Documents
     */
    public function getDocumentsById($documentId)
    {
        $result = $this->fetchOne(function (Select $select) use($documentId) {
            $select->columns([
                'id', 'user_id', 'creator_id', 'type_id', 'date_created',
                'description', 'attachment', 'url'
            ]);

            $select->join(
                ['types' => DbTables::TBL_USER_DOCUMENT_TYPES],
                $this->getTable() . '.type_id = types.id',
                ['type' => 'title'],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable() . '.id' => $documentId,
            ]);
        });

        return $result;
    }
}
