<?php
namespace Application\Entity;

/**
 * @api {get} Errors
 * @apiVersion 1.0.0
 * @apiName ErrorList
 * @apiGroup Error
 *
 * @apiSuccessExample {json} Error Types:
 *         400: 'Bad Request'
 *         401: 'Unauthorized'
 *         409: 'Duplicate Request'
 *         500: 'Server side problem'
 *         600: 'Invalid or expired token'
 *         601: 'Image file not found'
 *         602: 'File extension is incorrect'
 *         603: 'File size limit exceeded'
 *         604: 'Cannot upload file'
 *         605: 'File not found'
 *         606: 'User not found'
 *         607: 'Some parameters are not set'
 *         608: 'Status not found'
 *         609: 'Invalid valuable asset status'
 *         610: 'Asset not found'
 *         611: 'Location not found'
 *         612: 'Module not found'
 *         613: 'Duplicate category'
 *         614: 'Duplicate user hash'
 *
 * @apiErrorExample Sample Response:
 *     HTTP/1.1 404 Not Found
 *     {
 *         "type": "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html",
 *         "title": "Not Found",
 *         "status": 404,
 *         "detail": {
 *             "code": 605,
 *             "message": "File not found"
 *         }
 *     }
 *
 */
class Error
{
    const BAD_REQUEST_CODE             = 400;
    const AUTHENTICATION_FAILED_CODE   = 401;
    const NOT_FOUND_CODE               = 404;
    const DUPLICATE_REQUEST_CODE       = 409;
    const SERVER_SIDE_PROBLEM_CODE     = 500;
    const INVALID_TOKEN_CODE           = 600;
    const IMAGE_FILE_NOT_FOUND_CODE    = 601;
    const FILE_TYPE_NOT_TRUE_CODE      = 602;
    const FILE_SIZE_EXCEEDED_CODE      = 603;
    const FILE_UPLOAD_ERROR_CODE       = 604;
    const FILE_NOT_FOUND_CODE          = 605;
    const USER_NOT_FOUND_CODE          = 606;
    const INCOMPLETE_PARAMETERS_CODE   = 607;
    const STATUS_NOT_FOUND_CODE        = 608;
    const INVALID_VALUABLE_STATUS_CODE = 609;
    const ASSET_NOT_FOUND_CODE         = 610;
    const LOCATION_NOT_FOUND_CODE      = 611;
    const MODULE_NOT_FOUND_CODE        = 612;
    const DUPLICATE_CATEGORY_CODE      = 613;
    const DUPLICATE_USER_HASH          = 614;

    public static $errorTitles = [
        self::BAD_REQUEST_CODE             => ['httpCode' => self::BAD_REQUEST_CODE, 'message'           => 'Bad Request'],
        self::AUTHENTICATION_FAILED_CODE   => ['httpCode' => self::AUTHENTICATION_FAILED_CODE, 'message' => 'Unauthorized'],
        self::NOT_FOUND_CODE               => ['httpCode' => self::NOT_FOUND_CODE, 'message'             => 'Not Found'],
        self::SERVER_SIDE_PROBLEM_CODE     => ['httpCode' => self::SERVER_SIDE_PROBLEM_CODE, 'message'   => 'Server side problem'],
        self::DUPLICATE_REQUEST_CODE       => ['httpCode' => self::DUPLICATE_REQUEST_CODE, 'message'     => 'Duplicate Request'],
        self::INVALID_TOKEN_CODE           => ['httpCode' => self::AUTHENTICATION_FAILED_CODE, 'message' => 'Invalid or expired token'],
        self::IMAGE_FILE_NOT_FOUND_CODE    => ['httpCode' => self::AUTHENTICATION_FAILED_CODE, 'message' => 'Image file not found'],
        self::FILE_TYPE_NOT_TRUE_CODE      => ['httpCode' => self::AUTHENTICATION_FAILED_CODE, 'message' => 'File extension is incorrect'],
        self::FILE_SIZE_EXCEEDED_CODE      => ['httpCode' => self::AUTHENTICATION_FAILED_CODE, 'message' => 'File size limit exceeded'],
        self::FILE_UPLOAD_ERROR_CODE       => ['httpCode' => self::AUTHENTICATION_FAILED_CODE, 'message' => 'Cannot upload file'],
        self::FILE_NOT_FOUND_CODE          => ['httpCode' => self::AUTHENTICATION_FAILED_CODE, 'message' => 'File not found'],
        self::USER_NOT_FOUND_CODE          => ['httpCode' => self::AUTHENTICATION_FAILED_CODE, 'message' => 'User not found'],
        self::INCOMPLETE_PARAMETERS_CODE   => ['httpCode' => self::AUTHENTICATION_FAILED_CODE, 'message' => 'Incomplete Parameters'],
        self::STATUS_NOT_FOUND_CODE        => ['httpCode' => self::AUTHENTICATION_FAILED_CODE, 'message' => 'Status not found'],
        self::INVALID_VALUABLE_STATUS_CODE => ['httpCode' => self::BAD_REQUEST_CODE, 'message'           => 'Invalid valuable asset status'],
        self::ASSET_NOT_FOUND_CODE         => ['httpCode' => self::NOT_FOUND_CODE, 'message'             => 'Asset not found'],
        self::LOCATION_NOT_FOUND_CODE      => ['httpCode' => self::NOT_FOUND_CODE, 'message'             => 'Location not found'],
        self::MODULE_NOT_FOUND_CODE        => ['httpCode' => self::NOT_FOUND_CODE, 'message'             => 'Module not found'],
        self::DUPLICATE_CATEGORY_CODE      => ['httpCode' => self::DUPLICATE_REQUEST_CODE, 'message'     => 'Duplicate category'],
        self::DUPLICATE_USER_HASH          => ['httpCode' => self::DUPLICATE_REQUEST_CODE, 'message'     => 'Duplicate user cache'],
    ];
}
