<?php
namespace Backoffice\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ConfirmationDialog extends AbstractHelper {
	public function __invoke($title, $id, $confirmButtonId, $confirmBtnText = 'Delete', $style = 'danger') {
        return
            '<div id="' . $id . '" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-' . $style . ' text-' . $style . '">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title">' . $title . '</h4>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure?</p>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Cancel</button>
                            <a class="btn btn-' . $style . '" href="#" id="' . $confirmButtonId . '">' . $confirmBtnText . '</a>
                        </div>
                    </div>
                </div>
            </div>';
    }
}
