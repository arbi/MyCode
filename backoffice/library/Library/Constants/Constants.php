<?php

namespace Library\Constants;

class Constants
{

    //Cach keys constants

    const CACH_CURRENCIES   = 'currency';
    const CACH_LANGUAGES    = 'language';
    const CACH_UN_TEXTLINES = 'un_textlines';
    const BASE_PATH_VIEW    = '';
    const VERSION           = '/__ver__/';
    const APP_VERSION       = '__ver__';

	// User Shift Types
    const SHIFT_12_5_HOURS = 0;
    const SHIFT_9_HOURS    = 1;
    const SHIFT_DYNAMIC    = 2;

	// User Vacation Statuses
	const VACATION_CANCELED = 0;
	const VACATION_APPROVED = 1;
	const VACATION_REJECTED = 3;

    // Review mails settings
    const REVIEW_SEND_EMAIL_AFTER_DAYS  = 3;
    const REVIEW_SEND_AN_EMAIL_TO_DAYS  = 10;

    const DEFAULT_USER      = 199;
    const ADDONS_PARKING    = 6;
//     const PROFILE_VIEWER = 22;

    const NOT_BOOKED_STATUS = 111;
    const AGODA_PARTNER_ID  = 1118;

    const EXPIDIA_EXPIDIA_COLLECT_PARTNER_ID           = 1140;
    const EXPIDIA_EXPIDIA_OR_GINOSI_COLLECT_PARTNER_ID = 1144;
    const EXPIDIA_GINOSI_COLLECT_PARTNER_ID            = 1054;

    const TEST_APARTMENT_1     = 42;
    const TEST_APARTMENT_2     = 43;
    const TEST_APARTMENT_GROUP = 1;

    const JOB_STATUS_DRAFT    = 1;
    const JOB_STATUS_LIVE     = 2;
    const JOB_STATUS_INACTIVE = 3;

    public static $jobStatus = [
        self::JOB_STATUS_LIVE     => 'Live',
        self::JOB_STATUS_DRAFT    => 'Draft',
        self::JOB_STATUS_INACTIVE => 'Inactive'
    ];

    const BOOKING_OTHERS        = 1;

    const GLOBAL_DATE_FORMAT       = 'M j, Y';
    const GLOBAL_DATE_TIME_FORMAT  = 'M j, Y H:i:s';
    const GLOBAL_DATE_TIME_WO_SEC_FORMAT = 'M j, Y H:i';
    const GLOBAL_DATE_WO_YEAR = 'M j';
    const DATABASE_DATE_FORMAT     = 'Y-m-j';
    const DATABASE_DATE_TIME_FORMAT = 'Y-m-j H:i';

    // This constant is deprecated and should be changed with GLOBAL_DATE_FORMAT in near future
    const GLOBAL_DATE_FORMAT_DATEPICKER      = 'd M Y';
    const MAX_ROW_COUNT = 3000;

    const GINOSI_CALL_CENTER_URL = 'https://ginosi.cc/call.php?exten=99%d&phone=%s';

}
