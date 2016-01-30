<?php

namespace Mailer\Constants;

class Inline
{
    const FONT = 'font-family:\'Open Sans\',sans-serif; ';
    const FONT_16 = 'font-size:16px; ';
    const WIDTH = 'width:510px; ';
    const MAX_WIDTH = 'max-width:510px; ';
    const WIDTH_100 = 'width:100%; ';
    const HEIGHT_120 = 'height:120px; ';

    const ALIGN_LEFT = 'text-align:left; ';
    const ALIGN_CENTER = 'text-align:center; ';
    const ALIGN_MIDDLE = 'vertical-align:middle; ';

    const MARGIN_20_AUTO = 'margin:20px auto; ';
    const PADDING_BOTTOM_20 = 'padding-bottom:20px; ';
    const PADDING_0_20 = 'padding:0 20px; ';
    const PADDING_20 = 'padding:20px; ';
    const PADDING_TOP_5 = 'padding-top:5px; ';
    const PADDING_TOP_20 = 'padding-top:20px; ';
    const PADDING_BOTTOM_5 = 'padding-bottom:5px; ';

    const BORDER_RADIUS = 'border-radius:3px; box-shadow:0 0 5px #BDBDBD; ';

    const COLOR_WHITE = 'color:#fff; ';
    const BACKGROUND_WHITE = 'background-color:#fff; ';

    const SUBHEADING_P = 'margin:0 0 5px 0; padding:0; ';

    public static function getBG($color, $image)
    {
        return "background: {$color} url({$image}) no-repeat; " . self::HEIGHT_120;
    }

    public static function getCover()
    {
        return self::PADDING_0_20 . self::ALIGN_MIDDLE . self::ALIGN_LEFT .self::COLOR_WHITE . self::FONT_16;
    }

    public static function getPrimaryText($color)
    {
        return self::FONT . "font-size:23px; font-weight:bold; color:{$color}; ";
    }

    public static function getPolicy()
    {
        return self::MAX_WIDTH . self::MARGIN_20_AUTO . self::BACKGROUND_WHITE . self::BORDER_RADIUS;;
    }
}
