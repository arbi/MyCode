<?php

namespace DDD\Dao\Textline;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class Universal extends TableGatewayManager
{
    protected $table = DbTables::TBL_UN_TEXTLINES;

    public function __construct($sm, $domain = 'DDD\Domain\Textline\Universal')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $id
     * @return \DDD\Domain\Textline\Universal|null
     */
    public function getTextlineById($id)
    {
        $result = $this->fetchOne(function(Select $select) use($id){
            $select->columns([
                'en',
                'en_html_clean'
            ]);

            $select->where->equalTo('id', $id);
        });

        return $result;
    }


    public function searchTextlineByEnText($text)
    {
        $result = $this->fetchAll(function(Select $select) use($text){
            //$select->columns();

            $select->where
                    ->equalTo('en', $text);
        });

        return $result;
    }
}

?>
