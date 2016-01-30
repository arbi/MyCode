<?php
namespace DDD\Dao\Translation;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Predicate\Predicate;

class Universal extends TableGatewayManager
{
    protected $table = DbTables::TBL_UN_TEXTLINES;

    public function __construct($sm, $domain = 'DDD\Domain\Translation\GetLang'){
        parent::__construct($sm, $domain);
    }

    public function getTranslationListForSearch( $filterParams = []) {
        $where = new Where();
        $hasPageQuery = false;
        if ((int)$filterParams["id_translation"] > 0) {
            $where->equalTo( $this->getTable() . '.id', $filterParams["id_translation"]);
        } else {
            if ($filterParams["srch_txt"] != '') {
                $where->like( $this->getTable() . '.en_html_clean', '%'. strip_tags(trim($filterParams["srch_txt"])).'%');
            }

            if ($filterParams['category'] == 1 && isset($filterParams['un_type'][0])) {
                $pages = explode(',', $filterParams['un_type'][0]);
                $where->in('pr.page_id', $pages);
            }

            if ($filterParams["description"] != '') {
                $where->like( $this->getTable() . '.description', '%'. strip_tags(trim($filterParams["description"])).'%');
            }
        }

    	$columns = array(
            'id'          => 'id',
            'content'     => 'en',
            'description' => 'description',
            'page_name'   => new Expression("GROUP_CONCAT(p.name SEPARATOR ', ')")
    	);

    	$sortColumns = ['id', 'en'];
    	$result = $this->fetchAll(function (Select $select) use(
            $columns, $sortColumns, $where
        ) {

    		$select->columns( $columns );

            $select->join(
                ['pr' => DbTables::TBL_UN_TEXTLINE_PAGE_REL],
                $this->getTable() . '.id = pr.textline_id',
                []
            );
            $select->join(
                ['p' => DbTables::TBL_PAGES],
                'p.id = pr.page_id',
                []
            );

    		if ($where !== null) {
    			$select->where($where);
    		}
            $select->group($this->getTable() . '.id');
            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));

    	});

        $statement = $this->adapter->query('SELECT FOUND_ROWS() as count');
        $result2   = $statement->execute();
        $row       = $result2->current();

        return ['result' => $result, 'count' => $row['count']];
    }

    function getforTranslation($param){
        $result = $this->fetchOne(function (Select $select) use($param) {

            $select->columns(array(
                'id',
                'content' => 'en',
                'description'
            ));

            $select->where->equalTo($this->getTable().'.id', $param['id']);
          });

        return $result;
    }
}
