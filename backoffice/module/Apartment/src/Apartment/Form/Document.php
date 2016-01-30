<?php

namespace Apartment\Form;

use Library\Form\FormBase;
use Library\Constants\Constants;

use Zend\Db\ResultSet\AbstractResultSet;
use Zend\Stdlib\ArrayObject;


class Document extends FormBase
{

    public function __construct($data, $options)
    {
        parent::__construct('document-form');
        $this->setAttributes([
            'action'  => '',
            'method'  => 'post',
            'class'   => 'form-horizontal',
            'id'      => 'document-form',
            'enctype' => 'multipart/form-data'
        ]);
        $this->setName('document-form');

        $category = [];
        foreach ($options['list'] as $row) {
            $category[$row->getId()] = $row->getName();
        }
        $this->add([
            'name'       => 'category',
            'options'    => [
                'label'         => 'Type',
                'value_options' => $category
            ],
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'category',
                'class' => 'form-control custom-selectize',
            ],
        ]);
        $securityLevelOptions = [0 => '-- Please Select --'] + $options['security_level'];
        $legalEntityArray = $options['legalEntityArray'];
        $signatoriesArray = $options['signatoriesArray'];

        $this->add([
            'name'       => 'security_level',
            'options'    => [
                'label'         => 'Security',
                'value_options' => $securityLevelOptions
            ],
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'security_level',
                'class' => 'form-control custom-selectize',
            ],
        ]);

        $this->add([
            'name'       => 'legal_entity_id',
            'options'    => [
                'label'         => 'Legal Entity',
                'value_options' => $legalEntityArray
            ],
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'legal_entity_id',
                'class' => 'form-control custom-selectize',
            ],
        ]);

        $this->add([
            'name'       => 'signatory_id',
            'options'    => [
                'label'         => 'Signatory',
                'value_options' => $signatoriesArray
            ],
            'type'       => 'Zend\Form\Element\Select',
            'attributes' => [
                'id'    => 'signatory_id',
                'class' => 'form-control custom-selectize',
            ],
        ]);

        $this->add([
            'name'       => 'username',
            'options'    => [
                'label' => 'Username',
            ],
            'attributes' => [
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'username',
                'maxlength' => 50,
            ],
        ]);

        $this->add([
            'name'       => 'gaghtnabarr',
            'options'    => [
                'label' => 'Password',
            ],
            'attributes' => [
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'gaghtnabarr',
                'maxlength' => 50,
            ],
        ]);

        $this->add([
            'name'       => 'url',
            'options'    => [
                'label' => 'URL',
            ],
            'attributes' => [
                'type'        => 'text',
                'class'       => 'form-control',
                'id'          => 'url',
                'maxlength'   => 500,
                'placeholder' => 'http://example.com'
            ],
        ]);

        $this->add([
            'name'       => 'description',
            'options'    => [
                'label' => 'Description',
            ],
            'attributes' => [
                'type'  => 'textarea',
                'class' => 'form-control tinymce',
                'rows'  => '12',
                'id'    => 'description',
            ]
        ]);

        $this->add([
            'name'       => 'attachment_doc',
            'options'    => [
                'label' => 'Attachment',
            ],
            'attributes' => [
                'type'          => 'file',
                'id'            => 'attachment_doc',
                'class'         => 'hidden-file-input',
                'data-max-size' => '52428800'
            ],
        ]);

        $this->add([
            'name'       => 'account_number',
            'options'    => [
                'label' => 'Account Number',
            ],
            'attributes' => [
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'account_number',
                'maxlength' => 255,
            ],
        ]);

        $this->add([
            'name'       => 'account_holder',
            'options'    => [
                'label' => 'Account Holder',
            ],
            'attributes' => [
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'account_holder',
                'maxlength' => 255,
            ],
        ]);

        $this->add([
            'name'       => 'valid_from',
            'options'    => [
                'label' => 'Valid From',
            ],
            'attributes' => [
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'valid_from',
                'maxlength' => 25,
            ],
        ]);

        $this->add([
            'name'       => 'valid_to',
            'options'    => [
                'label' => 'Valid To',
            ],
            'attributes' => [
                'type'      => 'text',
                'class'     => 'form-control',
                'id'        => 'valid_to',
                'maxlength' => 25,
            ],
        ]);

        $this->add( [
            'name' => 'is_frontier',
            'type' => 'Zend\Form\Element\Checkbox',
            'attributes' => [
                'id' => 'is_frontier',
            ],
            'options' => [
                'label' => 'Frontier',
            ],
        ]);

        $this->add([
            'name'       => 'supplier_id',
            'type'       => 'Zend\Form\Element\Select',
            'options'    => [
                'label'         => 'Supplier',
                'value_options' => $this->getSuppliers($options['suppliers']),
            ],
            'attributes' => [
                'class' => 'form-control custom-selectize',
                'id'    => 'supplier_id'
            ],
        ]);

        $this->add([
            'name'       => 'edit_id',
            'attributes' => [
                'type' => 'hidden',
                'id'   => 'edit_id',
            ],
        ]);

        $buttonname = 'Add Document';
        if (is_object($data)) {
            $buttonname = 'Save Changes';
        }

        $this->add([
            'name'       => 'save_button',
            'type'       => 'Zend\Form\Element\Button',
            'options'    => [
                'label' => $buttonname,
            ],
            'attributes' => [
                'class'             => 'btn btn-primary pull-right margin-left-10 col-sm-2 col-xs-12 state',
                'data-loading-text' => 'Saving...',
                'id'                => 'save_button',
            ],
        ]);

        $this->add([
            'name'       => 'delete_button',
            'options'    => [
                'label' => 'Delete Document',
            ],
            'attributes' => [
                'type'              => 'button',
                'data-toggle'       => 'modal',
                'data-target'       => '#delete-modal',
                'class'             => 'btn btn-danger pull-right margin-left-10 col-sm-2 col-xs-12 state',
                'data-loading-text' => 'Saving...',
                'id'                => 'delete_button',
            ],
        ]);

        if (is_object($data)) {
            $objectData                       = new \ArrayObject();
            $objectData['edit_id']            = $data->getID();
            $objectData['category']           = $data->getTypeID();
            $objectData['username']           = $data->getUsername();
            $objectData['gaghtnabarr']        = $data->getPassword();
            $objectData['url']                = $data->getUrl();
            $objectData['description']        = $data->getDescription();
            $objectData['account_number']     = $data->getAccountNumber();
            $objectData['account_holder']     = $data->getAccountHolder();
            $objectData['security_level']     = $data->getSecurityLevel();
            $objectData['supplier_id']        = $data->getSupplierId();
            $objectData['valid_from']         = ($data->getValidFrom() === null) ? '' : date(Constants::GLOBAL_DATE_FORMAT, strtotime($data->getValidFromJQueqryDatePickerFormat()));
            $objectData['valid_to']           = ($data->getValidTo() === null)   ? '' : date(Constants::GLOBAL_DATE_FORMAT, strtotime($data->getValidToJQueqryDatePickerFormat()));
            $objectData['signatory_id']       = $data->getSignatoryId();
            $objectData['legal_entity_id']    = $data->getLegalEntityId();
            $objectData['is_frontier']        = $data->getIsFrontier();
            $this->bind($objectData);
        }
    }

    private function getSuppliers($suppliers)
    {
        return $this->convertResultSetToArray($suppliers);
    }

    private function convertResultSetToArray($data, array $options = [])
    {
        if (!($data instanceof AbstractResultSet || $data instanceof ArrayObject)) {
            throw new \Exception('Parameter must be an instance of AbstractResultSet or ArrayObject');
        }

        $outArray = [];

        if (isset($options['first'])) {
            $outArray[$options['first']['key']] = $options['first']['value'];
        }

        foreach ($data as $dataItem) {
            $outArray[$dataItem->getId()] = $dataItem->getName();
        }

        return $outArray;
    }

}
