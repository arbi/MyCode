<?php

namespace DDD\Domain\Office;

use DDD\Dao\User\UserManager;
use DDD\Service\ServiceBase;
use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Objects;
use Library\Utility\Debug;
use Library\Utility\Helper;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;

class OfficeSection
{
    protected $id;
    protected $name;
    protected $officeId;
    protected $disable;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ?
            $data['id'] : null;
        $this->name = (isset($data['name'])) ?
            $data['name'] : null;
        $this->officeId = (isset($data['office_id'])) ?
            $data['office_id'] : null;
        $this->disable = (isset($data['disable'])) ?
            $data['disable'] : null;

    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }
    public function getOfficeId() {
        return $this->officeId;
    }

    public function setOfficeId($officeId) {
        $this->officeId = $officeId;
        return $this;
    }

    public function getDisable() {
        return $this->disable;
    }

    public function setDisable($disable) {
        $this->disable = $disable;
        return $this;
    }
}
