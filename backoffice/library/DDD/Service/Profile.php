<?php

namespace DDD\Service;

use Library\Constants\TextConstants;

class Profile extends ServiceBase {
	// Days to show
	const SCHEDULE_LENGTH = 21;

	// Shift of days from current day to show. To place current day to the any position.
	const SCHEDULE_DAYS_SHIFT = -15;

	// Calculation Start Day (local)
	const SCHEDULE_CALCULATION_START_DAY = '1 Jul 2013';

	const SHIFT_EVEN = 1;
	const SHIFT_ODD = 0;

	protected $_userDao;
	protected $_vacationDao;

    /**
     * @todo Duplicate method (User Service > getUserManagees())
     *
     * @param int $userId
     * @return \Traversable
     */
    public function getUserSubordinates($userId) {
		/**
		 * @var $userDao \DDD\Dao\User\Usermanager
		 * @var $userData \DDD\Domain\User\User
		 */
		$userDao = $this->getUserDao();
		$subordinates = $userDao->getUsersByManagerId($userId);

		return $subordinates;
	}

	public function getUserDao() {
		if ($this->_userDao === null) {
			$this->_userDao = $this->getServiceLocator()->get('dao_user_user_manager');
		}

		return $this->_userDao;
	}

	public function getVacationDao() {
		if ($this->_vacationDao === null) {
			$this->_vacationDao = $this->getServiceLocator()->get('dao_user_vacation_days');
		}

		return $this->_vacationDao;
	}
}
