<?php

namespace DDD\Domain\User\Evaluation;

class EvaluationValues
{
    private $id;
    private $evaluationId;
    private $itemId;
    private $item;
    private $value;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->evaluationId = (isset($data['evaluation_id'])) ? $data['evaluation_id'] : null;
        $this->itemId = (isset($data['item_id'])) ? $data['item_id'] : null;
        $this->item = (isset($data['item'])) ? $data['item'] : null;
        $this->value = (isset($data['value'])) ? $data['value'] : null;
    }

    public function getId() {
        return $this->id;
    }

    public function getEvaluationId() {
        return $this->evaluationId;
    }

    public function getItemId() {
        return $this->itemId;
    }

    public function getItem() {
        return $this->item;
    }

    public function getValue() {
        return $this->value;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setEvaluationId($evaluationId) {
        $this->evaluationId = $evaluationId;
    }

    public function setItemId($itemId) {
        $this->itemId = $itemId;
    }

    public function setValue($value) {
        $this->value = $value;
    }
}
