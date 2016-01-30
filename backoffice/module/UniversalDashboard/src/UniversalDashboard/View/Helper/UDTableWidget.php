<?php

namespace UniversalDashboard\View\Helper;

use UniversalDashboard\AbstractUDWidget as Widget;
use Zend\View\Helper\AbstractHtmlElement;

/**
 *
 * @author developer
 *
 */
class UDTableWidget extends AbstractHtmlElement {
	/**
	 * @param int $id
	 * @param Widget $widget
     * @param string $type Type of widget
	 * @return string
	 */
    public function __invoke($id, Widget $widget, $type) {
	    if ($count = $widget->getCount()) {
		    $count = $widget->getCount();
	    } else {
		    $count = '';
	    }

	    $widgetId = preg_replace('/[^a-z0-9 ]/i', '', strtolower($widget->getTitle()));
	    $widgetId = preg_replace('/\s+/', '-', $widgetId);
	    $widgetId = 'ud-' . $widgetId;

	    $tableData = $this->getView()->Datatable($id, $widget->getDatatable(), array(
		    'class' => 'table table-striped table-bordered table-condensed'
	    ));

	    $html = sprintf('
<div class="panel panel-%s">
	<div class="panel-heading transition" data-widget-id="%s">
		%s <span class="text-muted">%s</span>
	</div>
	%s
</div>
		    ', $type, $widgetId, $widget->getTitle(), $count, $tableData);

        return $html;
    }
}
