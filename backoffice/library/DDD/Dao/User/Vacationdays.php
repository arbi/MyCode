<?php

namespace DDD\Dao\User;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression;

use DDD\Service\User\Vacation as VacationService;

class Vacationdays extends TableGatewayManager
{
    protected $table = DbTables::TBL_BACKOFFICE_USER_VACATIONS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\User\Vacationdays');
    }

	public function getUserVacationDays($userId) {
		$result = $this->fetchAll(function (Select $select) use($userId) {
			$select->columns(['from', 'to', 'total_number', 'comment', 'type', 'is_approved'])
				->where([$this->getTable() . '.user_id' => $userId, $this->getTable() . '.is_approved IN (1,2)'])//, $this->getTable() . '.is_approved = ' . Constants::VACATION_APPROVED])
				->order([$this->getTable() . '.to ASC']);
		});

		return $result;
	}

	public function getUserVacationRequestOld($userId) {
		$result = $this->fetchAll(function (Select $select) use($userId) {
			$select->where
                ->equalTo('user_id',$userId)
                ->expression('`from` < CURDATE()', [])
                ->notEqualTo('is_approved', Constants::VACATION_CANCELED);
            $select->order('from DESC');
		});
		return $result;
	}

	public function getUserVacationRequestNew($userId) {
		$result = $this->fetchAll(function (Select $select) use($userId) {
			$select->where
                ->equalTo('user_id',$userId)
                ->expression('`from` >= CURDATE()', [])
                ->notIn('is_approved', [Constants::VACATION_CANCELED, Constants::VACATION_REJECTED]);
            $select->order('from ASC');
		});
		return $result;
	}

    public function getUserVacationRequestPerThisYesr($userId, $onlyApproved = false)
    {
        $result = $this->fetchAll(function (Select $select) use($userId, $onlyApproved) {
			$select->where
                ->equalTo('user_id',$userId)
                ->expression("`from` >= CONCAT(YEAR(CURDATE()), '-01-01')", [])
                ->notEqualTo('type', VacationService::VACATION_TYPE_SICK);

            if ($onlyApproved) {
                $select->where
                    ->equalTo('is_approved', Constants::VACATION_APPROVED);
            }

            $select->order('from ASC');
		});
		return $result;
    }

    public function getSickDays($userId, $onlyApproved = true)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use($userId, $onlyApproved) {
            $select->columns(['total_number']);
            $select->where
                ->equalTo('user_id', $userId)
                ->equalTo('type', VacationService::VACATION_TYPE_SICK)
                ->expression("`from` >= CONCAT(YEAR(CURDATE()), '-01-01')", []);

            if ($onlyApproved) {
                $select->where->equalTo('is_approved', VacationService::VACATION_REQUEST_STATUS_APPROVED);
            }
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }
}
