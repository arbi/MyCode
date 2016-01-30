<?php

namespace DDD\Dao\Office;

use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;
use Library\Constants\Roles;

class OfficeSection extends TableGatewayManager
{
    protected $table = DbTables::TBL_OFFICE_SECTIONS;
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Office\OfficeSection');
    }

    public function getAllSecByOfficeId($id)
    {
        $result = $this->fetchAll(function (Select $select) use($id) {
            $select
                ->where('office_id ='. $id)
                ->order('disable asc');
		});

		return $result;
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getAllSections()
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function(Select $select) {
            $select->columns(['id', 'section_name' => 'name']);
            $select->join(
                ['office' => DbTables::TBL_OFFICES],
                $this->getTable() . '.office_id = office.id',
                ['office_id' => 'id', 'office_name' => 'name'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                'office.country_id = country.id',
                ['currency_id'],
                Select::JOIN_LEFT
            );
            $select->where([
                $this->getTable() . '.disable' => 0,
                'office.disable' => 0,
            ]);
            $select->order(['office_id ASC']);
        });
    }

    public function getSectionById($id)
    {
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use($id) {
            $select
                ->join(
                    ['office' => DbTables::TBL_OFFICES],
                    $this->getTable() . '.office_id = office.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['country' => DbTables::TBL_COUNTRIES],
                    'office.country_id = country.id',
                    ['currency_id'],
                    Select::JOIN_LEFT
                );
            $select->where->equalTo($this->getTable() . '.id', $id);
        });

        return $result;
    }
}
