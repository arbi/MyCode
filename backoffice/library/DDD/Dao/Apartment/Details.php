<?php
namespace DDD\Dao\Apartment;

use DDD\Domain\Apartment\Details\Sync;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression as SqlExpression;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;

class Details extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTMENTS_DETAILS;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Apartment\Details\Sync'){
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $apartmentId
     * @return int
     */
    public function isCubilisConnected($apartmentId) {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

    	return $this->fetchOne(function (Select $select) use ($apartmentId) {
    		$select->columns(['sync_cubilis']);
    		$select->where(['apartment_id' => $apartmentId]);
    	});
    }

	/**
	 * @return Sync[]|\ArrayObject|ResultSet|null
	 */
	public function getReadyToSyncAccs() {
        /**
         * @var ResultSet $result
         */
        $result = $this->fetchAll(function (Select $select) {
			$select->columns(['id', 'apartment_id', 'cubilis_id', 'cubilis_us', 'cubilis_pass', 'sync_cubilis']);
			$select->where(['sync_cubilis' => '1']);
		});

        return $result->buffer();
	}

	/**
	 * @param int $apartmentId
	 * @return Sync|null
	 */
	public function getCubilisDetails($apartmentId)
    {
		return $this->fetchOne(function (Select $select) use($apartmentId) {
			$select->columns(['id', 'apartment_id', 'cubilis_id', 'cubilis_us', 'cubilis_pass', 'sync_cubilis']);
			$select->where(['apartment_id' => $apartmentId]);
		});
	}

    /**
     * @param $apartmentId
     * @param $cubilisId
     * @param $cubilisUser
     * @param $cubilisPassword
     * @return int
     */
    public function updateCubilisDetails($apartmentId, $cubilisId, $cubilisUser, $cubilisPassword)
    {
		return $this->update([
			'cubilis_id' => $cubilisId,
			'cubilis_us' => $cubilisUser,
			'cubilis_pass' => $cubilisPassword,
		], ['apartment_id' => $apartmentId]);
	}

    /**
     * @param $apartmentId
     * @param $syncCubilis
     * @return int
     */
    public function connectToCubilis($apartmentId, $syncCubilis)
    {
		return $this->update([
			'sync_cubilis' => $syncCubilis,
		], ['apartment_id' => $apartmentId]);
	}

    /**
     * @param $apartmentId
     * @return float
     */
    public function getCleaningFee($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use($apartmentId) {
            $select->columns(['cleaning_fee']);
            $select->where(['apartment_id' => $apartmentId]);
        });

        return $result['cleaning_fee'];
    }

    public function checkDuplicateCubilisInfo($apartmentId, $cubilisId, $username, $password)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use($apartmentId, $cubilisId, $username, $password) {
            $select->columns([]);
            $select->join(
                ['a' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id = a.id',
                ['name']
            );

            $select->where->notEqualTo($this->getTable() . '.apartment_id', $apartmentId);
            $select->where->equalTo('sync_cubilis', 1);
            $select->where->equalTo('cubilis_id', $cubilisId);
            $select->where->equalTo('cubilis_us', $username);
            $select->where->equalTo('cubilis_pass', $password);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }
}
