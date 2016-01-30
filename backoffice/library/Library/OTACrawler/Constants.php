<?php

namespace Library\OTACrawler;

class Constants
{
    const CRAWLER_CHECK                   = 1;
    const CRAWLER_UPDATE                  = 2;
    const OTA_BOOKING_COM                 = 1050;
    const OTA_EASY_TO_BOOK                = 1052;
    const OTA_EXPEDIA                     = 1054;
    const OTA_LATEROOMS                   = 1071;
    const OTA_HOTELS_NL                   = 1072;
    const OTA_VENERE                      = 1053;
    const OTA_YAHOO                       = 1101;
    const OTA_AGODA                       = 1118;
    const OTA_ORBITZ                      = 1146;
    const STATUS_UNKNOWN                  = 0;
    const STATUS_PENDING                  = 1;
    const STATUS_SELLING                  = 2;
    const STATUS_ISSUE                    = 3;
    const CRAWLER_STATUS_UNKNOWN          = 1;
    const CRAWLER_STATUS_OK               = 2;
    const CRAWLER_STATUS_EMPTY_URL        = 3;
    const CRAWLER_STATUS_BAD_URL          = 4;
    const CRAWLER_STATUS_PAGE_NOT_FOUND   = 5;
    const CRAWLER_STATUS_PARSER_NOT_FOUND = 5;
}
