<?php

namespace FileManager\Constant;


class DirectoryStructure {

    const FS_UPLOAD_MAX_IMAGE_SIZE              = 6291456;  // 6M
    const FS_UPLOAD_MAX_FILE_SIZE               = 52428800; // 50M

    const FS_DOWNLOAD_STREAM_AFTER_SIZE         = 20971520; // 20Mb

    const FS_GINOSI_ROOT                        = '/ginosi/';

    const FS_IMAGES_ROOT                        = 'images/';
    const FS_IMAGES_TEMP_PATH                   = 'tmp/';
    const FS_IMAGES_PROFILE_PATH                = 'profile/';
    const FS_IMAGES_BOOKING_PATH                = 'booking/';
    const FS_IMAGES_MONEY_ACCOUNT_PATH          = 'moneyaccount/';
    const FS_IMAGES_LOCATIONS_PATH              = 'locations/';
    const FS_IMAGES_BLOG_PATH                   = 'blog/';
    const FS_IMAGES_APARTMENT                   = 'acc/';
    const FS_IMAGES_BUILDING                    = 'building/';
    const FS_IMAGES_OFFICE                      = 'office/';
    const FS_IMAGES_PARKING_ATTACHMENTS         = 'bo/parking/attachments/';
    const FS_IMAGES_APARTEL_BG_IMAGE            = 'apartel/';

    const FS_UPLOADS_ROOT                       = 'uploads/';
    const FS_UPLOADS_TMP                        = 'tmp/';
    const FS_UPLOADS_IMAGES_EXPENSE             = 'expenses/';
    const FS_UPLOADS_DOCUMENTS                  = 'documents/';
    const FS_UPLOADS_USER_DOCUMENTS             = 'user/documents/';
    const FS_UPLOADS_HR_APPLICANT_DOCUMENTS     = 'hr/applicants/';
    const FS_UPLOADS_BOOKING_DOCUMENTS          = 'booking/documents/';
    const FS_UPLOADS_MONEY_ACCOUNT_DOCUMENTS    = 'moneyaccount/documents/';
    const FS_UPLOADS_TASK_ATTACHMENTS           = 'task/attachments/';

    const FS_DATABASE_BACKUP                    = 'db/';

    const PATH_MEDIA_ROOT = 'images';
    const PATH_DOCUMENTS_ROOT = 'documents';

}