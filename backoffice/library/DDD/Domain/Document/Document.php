<?php

namespace DDD\Domain\Document;

/**
 * Document Domain class
 * @author Tigran Ghabuzyan
 * @final
 *
 * @package core
 * @subpackage core/domain
 */
final class Document
{
    private $id;
    private $typeID;
    private $typeName;
    private $teamName;
    private $description;
    private $username;
    private $password;
    private $url;
    private $attachment;
    private $createdDate;
    private $createdBy;
    private $securityLevel;
    private $accountNumber;
    private $accountHolder;
    private $supplierId;
    private $supplierName;
    private $validFrom;
    private $validTo;
    private $signatoryId;
    private $signatoryFirstName;
    private $signatoryLastName;
    private $legalEntityId;
    private $legalEntityName;
    private $isFrontier;
    private $creatorFirstName;
    private $creatorLastName;
    private $lastEditedBy;
    private $lastEditorFirstname;
    private $lastEditorLastname;
    private $lastEditedDate;
    private $entityId;
    private $entityType;
    private $entityName;
    private $teamManagerId;

    /**
     *
     * @param array $data
     */
    public function exchangeArray($data)
    {
        $this->id                  = (isset($data['id'])) ? $data['id'] : null;
        $this->entityId            = (isset($data['entity_id'])) ? $data['entity_id'] : null;
        $this->entityType          = (isset($data['entity_type'])) ? $data['entity_type'] : null;
        $this->entityName          = (isset($data['entity_name'])) ? $data['entity_name'] : null;
        $this->typeID              = (isset($data['type_id'])) ? $data['type_id'] : null;
        $this->typeName            = (isset($data['type_name'])) ? $data['type_name'] : null;
        $this->teamName            = (isset($data['team_name'])) ? $data['team_name'] : null;
        $this->description         = (isset($data['description'])) ? $data['description'] : null;
        $this->username            = (isset($data['username'])) ? $data['username'] : null;
        $this->password            = (isset($data['password'])) ? $data['password'] : null;
        $this->url                 = (isset($data['url'])) ? $data['url'] : null;
        $this->attachment          = (isset($data['attachment'])) ? $data['attachment'] : null;
        $this->securityLevel       = (isset($data['security_level'])) ? $data['security_level'] : null;
        $this->accountNumber       = (isset($data['account_number'])) ? $data['account_number'] : null;
        $this->accountHolder       = (isset($data['account_holder'])) ? $data['account_holder'] : null;
        $this->supplierId          = (isset($data['supplier_id'])) ? $data['supplier_id'] : null;
        $this->supplierName        = (isset($data['supplier_name'])) ? $data['supplier_name'] : null;
        $this->validFrom           = (isset($data['valid_from'])) ? $data['valid_from'] : null;
        $this->validTo             = (isset($data['valid_to'])) ? $data['valid_to'] : null;
        $this->signatoryId         = (isset($data['signatory_id'])) ? $data['signatory_id'] : null;
        $this->signatoryFirstName  = (isset($data['signatory_first_name'])) ? $data['signatory_first_name'] : null;
        $this->signatoryLastName   = (isset($data['signatory_last_name'])) ? $data['signatory_last_name'] : null;
        $this->legalEntityId       = (isset($data['legal_entity_id'])) ? $data['legal_entity_id'] : null;
        $this->legalEntityName     = (isset($data['legal_entity_name'])) ? $data['legal_entity_name'] : null;
        $this->isFrontier          = (isset($data['is_frontier'])) ? $data['is_frontier'] : null;
        $this->createdBy           = (isset($data['created_by'])) ? $data['created_by'] : null;
        $this->creatorFirstName    = (isset($data['creator_firstname'])) ? $data['creator_firstname'] : null;
        $this->creatorLastName     = (isset($data['creator_lastname'])) ? $data['creator_lastname'] : null;
        $this->createdDate         = (isset($data['created_date'])) ? $data['created_date'] : null;
        $this->lastEditedBy        = (isset($data['last_edited_by'])) ? $data['last_edited_by'] : null;
        $this->lastEditorFirstname = (isset($data['last_editor_firstname'])) ? $data['last_editor_firstname'] : null;
        $this->lastEditorLastname  = (isset($data['last_editor_lastname'])) ? $data['last_editor_lastname'] : null;
        $this->lastEditedDate      = (isset($data['last_edited_date'])) ? $data['last_edited_date'] : null;
        $this->teamManagerId       = (isset($data['team_manager_id'])) ? $data['team_manager_id'] : null;
    }

    /**
     * Get document ID
     * @access public
     *
     * @return int
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Get document type ID
     * @access public
     *
     * @return string
     */
    public function getTypeID()
    {
        return $this->typeID;
    }

    /**
     * @access public
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @access public
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @access public
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @access public
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @access public
     * @return string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @access public
     * @return int
     */
    public function getSecurityLevel()
    {
        return $this->securityLevel;
    }

    /**
     * @access public
     * @return string
     */
    public function getAccountNumber()
    {
        return $this->accountNumber;
    }

    /**
     * @access public
     * @return string
     */
    public function getAccountHolder()
    {
        return $this->accountHolder;
    }

    /**
     * @access public
     * @return int
     */
    public function getSupplierId()
    {
        return $this->supplierId;
    }

    /**
     * @access public
     * @return int
     */
    public function getValidFrom()
    {
        return $this->validFrom;
    }

    /**
     * @access public
     * @return int
     */
    public function getValidFromJQueqryDatePickerFormat()
    {
        return date("m/d/Y", strtotime($this->validFrom));
    }

    /**
     * @access public
     * @return int
     */
    public function getValidTo()
    {
        return $this->validTo;
    }

    /**
     * @access public
     * @return int
     */
    public function getValidToJQueqryDatePickerFormat()
    {
        return date("m/d/Y", strtotime($this->validTo));
    }

    /**
     * @access public
     * @return int
     */
    public function getSignatoryId()
    {
        return $this->signatoryId;
    }

    /**
     * @access public
     * @return int
     */
    public function getLegalEntityId()
    {
        return $this->legalEntityId;
    }

    /**
     * @access public
     * @return int
     */
    public function getIsFrontier()
    {
        return $this->isFrontier;
    }

    /**
     * @access public
     * @return string
     */
    public function getCreatorFullName()
    {
        return $this->creatorFirstName . ' ' . $this->creatorLastName;
    }

    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function getCreatorFirstName()
    {
        return $this->creatorFirstName;
    }

    public function getCreatorLastName()
    {
        return $this->creatorLastName;
    }

    public function getLastEditorFullName()
    {
        return $this->lastEditorFirstname . ' ' . $this->lastEditorLastname;
    }

    public function getLastEditedBy()
    {
        return $this->lastEditedBy;
    }

    public function getLastEditorFirstname()
    {
        return $this->lastEditorFirstname;
    }

    public function getLastEditorLastname()
    {
        return $this->lastEditorLastname;
    }

    public function getLastEditedDate()
    {
        return $this->lastEditedDate;
    }

    /**
     * @return mixed
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @return mixed
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @return mixed
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return mixed
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * @return mixed
     */
    public function getTeamName()
    {
        return $this->teamName;
    }

    /**
     * @return mixed
     */
    public function getSupplierName()
    {
        return $this->supplierName;
    }

    /**
     * @return mixed
     */
    public function getLegalEntityName()
    {
        return $this->legalEntityName;
    }

    /**
     * @return mixed
     */
    public function getSignatoryFullName()
    {
        return $this->signatoryFirstName . ' ' . $this->signatoryLastName;
    }

    /**
     * @return mixed
     */
    public function getTeamManagerId()
    {
        return $this->teamManagerId;
    }
}
