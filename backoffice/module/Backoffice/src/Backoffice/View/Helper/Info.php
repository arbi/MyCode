<?php
namespace Backoffice\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * Class Info
 * @package Backoffice\View\Helper
 */
class Info extends AbstractHelper
{
    /**
     * @param $text
     * @param string $infoText
     * @param string $infoTitle
     * @param string $infoPlacement
     * @return string
     */
    public function __invoke($text, $infoText = '', $infoTitle = '', $infoPlacement = 'top')
    {
        if (trim($text) !== '') {
            return '<span
                    data-content="' . htmlspecialchars($infoText) . '"
                    data-container="body"
                    data-toggle="popover"
                    ' . (($infoTitle) ? 'title="' . $infoTitle . '"' : '') . '
                    data-placement="' . $infoPlacement . '"
                    class="commented-text"
                    data-animation="true"
                >' . $text . '</span>';
        }

        return '';
    }
}
