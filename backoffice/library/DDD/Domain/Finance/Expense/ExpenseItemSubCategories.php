<?php

namespace DDD\Domain\Finance\Expense;

class ExpenseItemSubCategories
{
    protected $id;
    protected $category_id;
    protected $name;
    protected $description;
    protected $is_active;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->category_id = (isset($data['category_id'])) ? $data['category_id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->description = (isset($data['description'])) ? $data['description'] : null;
        $this->is_active = (isset($data['is_active'])) ? $data['is_active'] : null;
    }

	public function getId()
    {
		return $this->id;
	}

    public function getCategoryId()
    {
        return $this->category_id;
    }

	public function getName()
    {
		return $this->name;
	}

	public function getDescription()
    {
		return $this->description;
	}

    public function getIsActive()
    {
        return $this->is_active;
    }
}
