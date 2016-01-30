<?php

namespace DDD\Dao\User\Document;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Zend\Db\Sql\Select;

class DocumentTypes extends TableGatewayManager
{
    protected $table = DbTables::TBL_USER_DOCUMENT_TYPES;

    public function __construct(
            $sm,
            $domain = 'DDD\Domain\User\Document\DocumentTypes')
    {
        parent::__construct($sm, $domain);
    }
    
    /**
     * 
     * @param int $typeId Document Type Id
     * @return \DDD\Domain\User\Document\DocumentTypes
     */
    public function getDocumentTypes($typeId = FALSE)
    {
        if ($typeId) {
            $result = $this->fetchOne(function (Select $select) use($typeId) {
                $select->columns([
                    'id', 'title', 'description', 'order'
                ]);
                
                $select->where([
                    'id' => $typeId
                ]);
            });
        } else {
            $result = $this->fetchAll(function (Select $select) {
                $select->columns([
                    'id', 'title', 'description', 'order'
                ]);

                $select->order('order ASC');
            });
        }
        
        return $result;
    }
}
