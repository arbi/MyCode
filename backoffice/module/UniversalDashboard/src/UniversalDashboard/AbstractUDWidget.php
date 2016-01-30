<?php

namespace UniversalDashboard;

use Library\Constants\DomainConstants;

/**
 *
 * @author Tigran Petrosyan
 */
class AbstractUDWidget
{
	const WIDGET_PRIMARY = 'primary';
	const WIDGET_INFO = 'info';
	const WIDGET_DANGER = 'danger';
	const WIDGET_WARNING = 'warning';
	const WIDGET_SUCCESS = 'success';
    const WIDGET_DEFAULT = 'default';
    /**
	 * @access private
	 * @var string
	 */
	private $title;

	/**
	 * @access private
	 * @var int
	 */
	private $count = 0;

	/**
	 * @access private
	 * @var string
	 */
	private $width = "6";

    protected $columns;

    protected $ajaxSourceUrl;

    protected $sorting = [0, 'ASC'];

    protected $afterDrawCallbackJsFunctionAddition = '';

    public function getColumns()
    {
        return $this->columns;
    }

    public function getAjaxSourceUrl()
    {
        return $this->ajaxSourceUrl;
    }

    public function getSortingOrder()
    {
        return $this->sorting;
    }

    /**
	 * @access public
	 * @param string $title
	 */
	public function setTitle($title)
    {
		$this->title = $title;
	}

	/**
	 * @access public
	 * @return string
	 */
	public function getTitle()
    {
		return $this->title;
	}

	/**
	 * @access public
	 * @param int $count
	 */
	public function setCount($count)
    {
		$this->count = $count;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getCount()
    {
		return $this->count;
	}

	/**
	 * @access public
	 * @param string $width
	 */
	public function setWidth($width)
    {
		$this->width = $width;
	}

	/**
	 *
	 * @return string
	 */
	public function getWidth()
    {
		return $this->width;
	}

    public function getAfterDrawCallbackJsFunctionAddition()
    {
        return $this->afterDrawCallbackJsFunctionAddition;
    }

    /**
     *
     * @param type $data
     * @return string
     */
    protected function prepareActionButtons($data)
    {
        $actions = $data->getActions();

        if (strlen($actions) == 0 || $actions === NULL) {
            return '';
        }

        $actions = unserialize($actions);

        $actionsColumn = '<div style="white-space: nowrap;">';

        foreach ($actions as $button) {
            if (isset($button['action']['url'])) {

                if ($button['class'] === 'success') {
                    $actionsColumn .=
                        '<a data-target="//'.DomainConstants::BO_DOMAIN_NAME.
                        $button['action']['url'].$data->getId().'"'.
                        'class="'.$this->getButtonClass($button['class']).'" '.
                        'target="_blank">'.
                        $button['title'].
                        '</a> ';
                } else {
                    $actionsColumn .=
                        '<a href="//'.DomainConstants::BO_DOMAIN_NAME.
                        $button['action']['url'].'"'.
                        'class="'.$this->getButtonClass($button['class']).'" '.
                        'target="_blank">'.
                        $button['title'].
                        '</a> ';
                }
            }
        }

        $actionsColumn .= '</div>';

        return $actionsColumn;
    }

    private function getButtonClass($classType)
    {
        switch ($classType) {
            case 'primary':
                return 'btn btn-xs btn-primary';
            case 'success':
                return 'btn btn-xs btn-success success-notification';
            case 'danger':
                return 'btn btn-xs btn-danger';
            case 'info':
                return 'btn btn-xs btn-info';
            case 'warning':
                return 'btn btn-xs btn-warning';
            case 'link':
                return 'btn btn-xs btn-link';
            default :
                return 'btn btn-xs btn-default';
        }
    }
}
