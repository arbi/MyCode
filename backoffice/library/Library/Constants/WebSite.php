<?php

namespace Library\Constants;

class WebSite
{
    //pagination
    const PAGINTAION_ITEM_COUNT = 6;
    const IMAGES_PATH           = '/ginosi/images';
    const SITE_MAP_PATH         = '/ginosi/website/public/sitemap.xml';
    const DEFAULT_CURRENCY      = 'EUR';
    const WEB_SITE_PARTNER      = 1;
    const BUSINESS_MODEL        = 2;
    const BLOG_PAGE_COUNT       = 10;
    const NEWS_PAGE_COUNT       = 6;
    const REVIEW_PAGE_COUNT     = 5;
    
    public static function getSearchEngineList() {
		return array(
                    'yahoo'  => 1101,
                    'google' => 1099,
                    'bing'   => 1100
		);
	}
    //images size
    const IMG_WIDTH_SEARCH          = 555;
    const IMG_WIDTH_AMARTMENT_SMALL = 96;
    const IMG_WIDTH_AMARTMENT_BIG   = 780;
    const IMG_WIDTH_LOCATION_BIG    = 1920;
    const IMG_WIDTH_LOCATION_MEDIUM = 500;
    const IMG_WIDTH_LOCATION_SMALL  = 360;
}
