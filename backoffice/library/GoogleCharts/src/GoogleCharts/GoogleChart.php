<?php

namespace GoogleCharts;

use Zend\View\Helper\AbstractHtmlElement;

class GoogleChart extends AbstractHtmlElement
{
    /**
     * @var string
     */
    protected static $jsFile  = 'https://www.google.com/jsapi';

    /**
     * @var bool
     */
    protected static $autoRegisterJsFile = true;

    public function __invoke($id, array $attributes = array(), $title, $data) {
        if (self::getAutoRegisterJsFile()) {
            $this->getView()->inlineScript()->appendFile(self::getJsFile());
            self::setAutoRegisterJsFile(false);
        }
        
        return '';
    }

    /**
     * @param string $id
     * @param array $attributes
     * @return string
     */
    protected function renderHtml($id, $attributes) {
    	$html = '<div id="' . $id . '" ';
    	foreach ($attributes as $key => $value) {
    		$html .= $key . '="' . $value . '" ';
    	}
    	return $html;
    }
    
    /**
     * @param boolean $autoRegisterJsFile
     */
    public static function setAutoRegisterJsFile($autoRegisterJsFile)
    {
        self::$autoRegisterJsFile = $autoRegisterJsFile;
    }

    /**
     * @return boolean
     */
    public static function getAutoRegisterJsFile()
    {
        return self::$autoRegisterJsFile;
    }

    /**
     * @param string $jsFile
     */
    public static function setJsFile($jsFile)
    {
        self::$jsFile = $jsFile;
    }

    /**
     * @return string
     */
    public static function getJsFile()
    {
        return self::$jsFile;
    }
}