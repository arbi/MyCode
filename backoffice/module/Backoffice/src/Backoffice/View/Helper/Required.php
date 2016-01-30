<?php
namespace Backoffice\View\Helper;

use Zend\View\Helper\AbstractHelper;

class Required extends AbstractHelper {
	public function __invoke() {
        return '<span class="text-danger required" data-toggle="tooltip" title="Required" data-original-title="Required">*</span>';
    }
}
