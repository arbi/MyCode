<?php

namespace DDD\Service\Contacts;

use DDD\Service\ServiceBase;
use Library\Constants\Roles;

class Contact extends ServiceBase
{
    const SCOPE_TEAM     = 1;
    const SCOPE_PERSONAL = 2;
    const SCOPE_GLOBAL   = 3;

    const TYPE_GENERAL   = 'general';
    const TYPE_PARTNER   = 'partners';
    const TYPE_OFFICE    = 'offices';
    const TYPE_EMPLOYEE  = 'employees';

    const LABEL_NAME_PARTNER   = 'Partner';
    const LABEL_NAME_OFFICE    = 'Office';
    const LABEL_NAME_EMPLOYEE  = 'Employee';

    const LABEL_CLASS_SCOPE_TEAM     = 'primary';
    const LABEL_CLASS_SCOPE_GLOBAL   = 'success';
    const LABEL_CLASS_SCOPE_PERSONAL = 'warning';
    const LABEL_CLASS_PARTNER        = 'info';
    const LABEL_CLASS_OFFICE         = 'danger';
    const LABEL_CLASS_EMPLOYEE       = 'default';


    protected $contactDao = false;

    /**
     * @param int $id
     * @param bool $joinExtraData
     *
     * @return \DDD\Domain\Contacts\Contact
     */
    public function getContactById($id, $joinExtraData = false)
    {
        try {
            $contactDao = $this->getContactDao();

            if ($joinExtraData) {
                $contactData = $contactDao->getContactByIdWithExtraData($id);
            } else {
                $contactData = $contactDao->getContactById($id);
            }

            if ($contactData) {
                $canEdit = false;

                /**
                 * @var \Library\Authentication\BackofficeAuthenticationService $authService
                 */
                $authService = $this->getServiceLocator()->get('library_backoffice_auth');
                $authService->getIdentity()->id;

                if ($contactData->getCreatorId() == $authService->getIdentity()->id) {
                    $canEdit = true;
                }

                if (!$canEdit && $authService->hasRole(Roles::ROLE_CONTACTS_GLOBAL_MANAGER)) {
                    $canEdit = true;
                }

                if ($contactData->getScope() == Contact::SCOPE_GLOBAL) {
                    $canEdit = true;
                }

                if (!$canEdit) {
                    /**
                     * @var \DDD\Dao\Team\TeamStaff $teamStaffDao
                     */
                    $teamStaffDao = $this->getServiceLocator()->get('dao_team_team_staff');

                    $isTeamStaff = $teamStaffDao->isTeamStaff(
                        $authService->getIdentity()->id,
                        $contactData->getTeamId());

                    if ($isTeamStaff) {
                        $canEdit = true;
                    }
                }

                if ($canEdit) {
                    return $contactData;
                }
            }
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot get contact data by id', [
                'id' => $id
            ]);
        }

        return false;
    }

    /**
     * @param string $queryString
     * @return bool|\DDD\Domain\Contacts\Card[]|bool
     */
    public function searchContacts($queryString = '')
    {
        try {
            if (!is_string($queryString)) {
                throw new \Exception('Invalid parameter type: Query string should be string.');
            }

            /**
             * @var \Library\Authentication\BackofficeAuthenticationService $authService
             */
            $authService = $this->getServiceLocator()->get('library_backoffice_auth');
            $userId = $authService->getIdentity()->id;

            $global = false;
            if ($authService->hasRole(Roles::ROLE_CONTACTS_GLOBAL_MANAGER)) {
                $global = true;
            }

            $contactDao = $this->getContactDao();

            $contacts = $contactDao->findContactsForOmniSearch($queryString, $userId, $global);

            $result = [];
            if ($contacts->count()) {
                foreach ($contacts as $contactData) {
                    $companyTitle = (!empty($contactData->getCompany())) ? ', ' . $contactData->getCompany() : '';
                    $positionTitle = (!empty($contactData->getPosition())) ? ', ' . $contactData->getPosition() : '';
                    $apartmentTitle = (!empty($contactData->getApartmentName())) ? ', ' . $contactData->getApartmentName() : '';
                    $buildingTitle = (!empty($contactData->getBuildingName())) ? ', ' . $contactData->getBuildingName() : '';
                    $partnerTitle = (!empty($contactData->getPartnerName())) ? ', ' . $contactData->getPartnerName() : '';

                    $label = $labelClass = '';
                    switch ($contactData->getScope()) {
                        case Contact::SCOPE_TEAM:
                            $label = $contactData->getTeamName();
                            $labelClass = self::LABEL_CLASS_SCOPE_TEAM;
                            break;
                        case Contact::SCOPE_GLOBAL:
                            $label = 'Global';
                            $labelClass = self::LABEL_CLASS_SCOPE_GLOBAL;
                            break;
                        case Contact::SCOPE_PERSONAL:
                            $label = 'Personal';
                            $labelClass = self::LABEL_CLASS_SCOPE_PERSONAL;
                            break;
                    }

                    $result[] = [
                        'id'    => $contactData->getId() . '_' . self::TYPE_GENERAL,
                        'type'  => self::TYPE_GENERAL,
                        'label'  => $label,
                        'labelClass'  => $labelClass,
                        'text'  =>
                            $contactData->getName()
                            . $companyTitle
                            . $positionTitle,
                        'info'  => $apartmentTitle
                            . $buildingTitle
                            . $partnerTitle,
                    ];
                }
            }

            return $result;
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot get list of contacts', [
                'query' => $queryString
            ]);
        }

        return false;
    }

    /**
     * @param array|\Contacts\Form\ContactForm $data
     *
     * @return bool|int
     */
    public function addContact($data)
    {
        try {
            $contactDao = $this->getContactDao();

            if(is_object($data) && $data instanceof \ArrayObject) {
                $data = $data->getArrayCopy();
            }

            return $contactDao->save(array_filter($data));
        } catch (\Exception $e) {
            $this->gr2logException($e, "New contact wasn't created", $data);
        }

        return false;
    }

    /**
     * @param int $id
     * @param array|\Contacts\Form\ContactForm $data
     *
     * @return bool|int
     */
    public function updateContact($id, $data)
    {
        try {
            $contactDao = $this->getContactDao();

            if(is_object($data) && $data instanceof \ArrayObject) {
                $data = $data->getArrayCopy();
            }

            return $contactDao->save(array_filter($data), ['id' => $id]);
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot update contact', [
                'id' => $id
            ]);
        }

        return false;
    }

    /**
     * @param int $id
     *
     * @return bool|void
     */
    public function deleteContact($id)
    {
        try {
            $contactDao = $this->getContactDao();

            return $contactDao->deleteWhere(['id' => $id]);
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot delete contact', [
                'id' => $id
            ]);
        }

        return false;
    }

    /**
     * @param int $creatorId
     * @param int $scope
     *
     * @return bool|void
     */
    public function deleteContactByCreatorIdAndScope($creatorId, $scope)
    {
        try {
            $contactDao = $this->getContactDao();

            return $contactDao->deleteWhere([
                'creator_id'    => $creatorId,
                'scope'         => $scope
            ]);
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot delete contacts', [
                'user_id'       => $creatorId,
                'contact_scope' => $scope
            ]);
        }

        return false;
    }

    /**
     * @param string $contactName
     * @param int $teamId
     *
     * @return bool|\DDD\Domain\Contacts\Contact[]
     */
    public function checkDuplicateByNameWithinTeamId($contactName, $teamId)
    {
        try {
            $contactDao = $this->getContactDao();

            return $contactDao->getContactByNameWithinTeamId($contactName, $teamId);
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot find contact', [
                'contact_name' => $contactName,
                'team_id'      => $teamId,
            ]);
        }

        return false;
    }

    /**
     * @return \DDD\Dao\Contacts\Contact
     */
    private function getContactDao()
    {
        if ($this->contactDao) {
            return $this->contactDao;
        }

        return $this->getServiceLocator()->get('dao_contacts_contact');
    }
}
