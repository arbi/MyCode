<?php

namespace Backoffice\Form;

use DDD\Domain\User\UserGroup;
use DDD\Service\User\Permissions;
use Library\Utility\Debug;
use Zend\Form\Form;
use Library\Constants\Constants;
use Library\Constants\Objects;

class SearchGinosikForm extends Form
{
    protected $resources;

    public function __construct(
        $name          = 'search_ginosik',
        $resources     = [],
        $isUserManager = false
    ){
        parent::__construct($name);

        $this->resources = $resources;
        $this->setAttribute('method', 'post');

        $this->add(
            [
                'name'       => 'group',
                'type'       => 'Zend\Form\Element\Select',
                'options' => [
                    'label'         => false,
                    'value_options' => $this->getUserGroups(),
                ],
                'attributes' => [
                    'class' => 'form-control'
                ],
            ]
        );

        $this->add(
            [
                'name'       => 'ud',
                'type'       => 'Zend\Form\Element\Select',
                'options' => [
                    'label'         => false,
                    'value_options' => $this->getUDdashboards(),
                ],
                'attributes' => [
                    'class' => 'form-control'
                ],
            ]
        );

        $this->add(
            [
                'name'       => 'city',
                'type'       => 'Zend\Form\Element\Select',
                'options' => [
                    'label'         => false,
                    'value_options' => $this->getCities(),
                ],
                'attributes' => [
                    'class' => 'form-control'
                ],
            ]
        );

        $this->add(
            [
                'name'       => 'team',
                'type'       => 'Zend\Form\Element\Select',
                'options'    => [
                    'label'         => false,
                    'value_options' => $this->getTeams(),
                ],
                'attributes' => [
                    'class' => 'form-control'
                ],
            ]
        );

    }

    /**
     * Get user permission options to populate select elements
     * @access public
     *
     * @return array
     */
    public function getUserGroups()
    {
        /**
         * @var UserGroup[] $permissions
         */
        $permissions = $this->resources['user_groups'];
        $permissionOptions = ['-- All Permissions --'];

        foreach ($permissions as $permission) {
            $permissionOptions[$permission->getId()] = $permission->getName() . ' - ' . Permissions::$permissionTypes[$permission->getType()];
        }

        return $permissionOptions;
    }

    /**
     * @access public
     *
     * @return array
     */
    public function getUDdashboards()
    {
        $groups      = $this->resources['ud_dashboards'];
        $groupsArray = ['-- All UD Widgets --'];

        foreach ($groups as $group) {
            $groupsArray[$group->getId()] = $group->getName();
        }

        return $groupsArray;
    }

    public function getCities()
    {
        $cities      = $this->resources['cities'];
        $citiesArray = ['-- All Cities --'];

        foreach ($cities as $city) {
            $citiesArray[$city['id']] = $city['name'];
        }

        return $citiesArray;
    }

    public function getTeams()
    {
        $teams      = $this->resources['teams'];
        $teamsArray = ['-- All Teams --'];

        foreach ($teams as $team) {
            $teamsArray[$team->getId()] = $team->getName();
        }

        return $teamsArray;
    }
}
