<?php

namespace Library\Constants;

class TextConstants
{
    const SUCCESS_UPDATE                                 = 'Successfully Updated';
    const SUCCESS_ADD                                    = 'Successfully Added';
    const SUCCESS_DELETE                                 = 'Successfully Deleted';
    const SUCCESS_ITEM_DELETE                            = 'Item successfully deleted';
    const SUCCESS_PUBLISH                                = 'Successfully Published';
    const SUCCESS_SEND_MAIL                              = 'Email Sent';
    const SUCCESS_CHARGED                                = 'Successfully Charged';
    const SUCCESS_TRANSACTED                             = 'Successful Transaction';
    const SUCCESS_DEACTIVATE                             = 'Successfully Deactivated';
    const SUCCESS_ACTIVATE                               = 'Successfully Activated';
    const SUCCESS_APPROVED                               = 'Successfully Approved';
    const SUCCESS_REJECTED                               = 'Successfully Rejected';
    const SUCCESS_COMPLETED                              = 'Successfully Completed';
    const SUCCESS_REVIEWED                               = 'Successfully Reviewed';
    const SUCCESS_RESOLVED                               = 'Successfully Resolved';
    const SUCCESS_CANCELLED                              = 'Successfully Cancelled';
    const SUCCESS_TRANSACTION_VERIFIED                   = 'Transaction successfully verified';
    const SUCCESS_TRANSACTION_UNVERIFIED                 = 'Transaction successfully unverified';
    const SUCCESS_TRANSACTION_VOIDED                     = 'Transaction successfully voided';
    const SUCCESS_ITEM_ATTACH                            = 'Item successfully conected to transaction';
    const SUCCESS_ITEM_DETACH                            = 'Item successfully detached from transaction';
    const SUCCESS_ITEM_ATTACHMENT_DELETE                 = 'Item attachment successfully deleted';
    const ERROR_CHARGED                                  = 'Changes not saved, please review and retry carefully';
    const ERROR_NO_CARD                                  = 'No valid Credit Card found, please add new card';
    const ERROR_SEND_MAIL                                = 'Email was not sent, please try again';
    const SERVER_ERROR                                   = 'Server side problem, please try again';
    const ERROR_NO_ITEM                                  = 'Item not found';
    const ERROR                                          = TextConstants::SERVER_ERROR;
    const ERROR_ROW                                      = TextConstants::SERVER_ERROR;
    const ERROR_NO_VIEW_PERMISSION                       = 'You have no permission to access this page';
    const ERROR_ITEM_IS_COMPLETED                        = 'Item is completed';

    const ERROR_BAD_REQUEST                              = 'A bad request was made, notify R&D';
    const ERROR_NO_SKILLS                                = 'Insufficient permissions for this action';
    const ERROR_NO_PERMISSION                            = 'You do not have permission to do this action';

    const AJAX_NO_POST_ERROR                             = 'Type of request method not POST';
    const AJAX_ONLY_POST_ERROR                           = 'The called method must be POST';
    const INVALID_USER_ID                                = 'Invalid user ID';
    const PASSWORD_NOT_TRUE                              = 'Password is not correct';
    const PASSWORDS_NO_MATCH                             = 'Passwords do not match';
    const PASSWORD_MINIMUM_REQUIRED                      = 'Passwords should be minimum 6 characters';
    const WRONG_PARAMETERS                               = 'Specified parameters are wrong';

    const IMAGE_FILE_NOT_FOUND                           = 'Image file not found';
    const FILE_TYPE_NOT_TRUE                             = 'File extension is incorrect';
    const FILE_SIZE_EXCEEDED                             = 'File size limit exceeded';
    const FILE_UPLOAD_ERROR                              = 'Cannot upload file';
    const FILE_NOT_FOUND                                 = 'File not found';

    const PARTNER_SAVE_IMPOSSIBLE                        = 'Partner details not saved';
    const PARTNER_DELETE_IMPOSSIBLE                      = 'Partner not deleted';
    const PARTNER_SEND_EMAIL_IMPOSSIBLE                  = 'Impossible to send email to partner';

    const HOUSEKEEPER_CHANGE_STATUS                      = 'Reservation status updated';
    const HOUSEKEEPER_SEND_COMMENT                       = 'Comment successfully saved';
    const SUCCESS_DUPLICATE                              = 'Expense ticket successfully duplicated';

    const UNIVERSAL_DASHBOARD_RESERVATION_RESOLVED       = 'Last minute reservation acknowledged';
    const UNIVERSAL_DASHBOARD_TIME_OFF_REQUEST_RESPONSE  = 'Time off request response acknowledged';
    const UNIVERSAL_DASHBOARD_RESOLVE_REQUEST_RESPONSE   = 'Vacation request acknowledged';
    const UNIVERSAL_DASHBOARD_MARK_READ                  = 'Housekeeping comment marked as read';

    const CRON_CURRENCY_NOT_UPDATED                      = 'Review Currency Rates: they have not been updated for a long time';
    const CUBILIS_ERROR_NOT_RATE                         = 'This room has no rate connected with a Cubilis account';
    const CUBILIS_ERROR_ACC_NOT_CONNECTED                = 'This apartment is not connected with a Cubilis account';
    const ERROR_ISSUE                                    = 'There is an open issue with this reservation look for details in the comments';
    const ERROR_OVERBOOKING                              = 'This reservation is overbooking, ensure a quick resolution';
    const FRAUD_ISSUE                                    = 'Fraud Detected: No automatic emails will be sent for this reservation';
    const FRAUD_BLACKLIST                                = 'Fraud detected: This user is in the Blacklist';
    const FRAUD_BLACKLIST_EMAIL                          = 'Email found in Blacklist';
    const FRAUD_BLACKLIST_NSP                            = 'Name, last name and phone number in Blacklist';
    const FRAUD_BLACKLIST_NSA                            = 'Name, last name and address in Blacklist';
    const FRAUD_BLACKLIST_NS                             = 'There is a blacklist reservation from a guest with the same Name and Surname';
    const FRAUD_BLACKLIST_PHONE                          = 'There is a blacklist reservation with the same phone number';
    const FRAUD_BLACKLIST_CC                             = 'Credit card and holder name in Blacklist';
    const FRAUD_COUNTRY_IP                               = 'Country and IP do not match';
    const FRAUD_NAME_HOLDER                              = 'Guest name and credit card holder name do not match';
    const FRAUD_CREDIT_CARD                              = 'Guest have credit card with status "Fraud"';
    const FRAUD_NONE                                     = 'Unknown';
    const FRAUD_ADD_BLACK_LIST                           = 'Are you sure you want to add the identities of this ticket to the Blacklist?';
    const FRAUD_REMOVE_BLACK_LIST                        = 'Are you sure you want to remove this ticket from the Blacklist?';
    const ERROR_NO_COLLECTION                            = 'This ticket is marked as No Collection. Some or all of the debt of this reservation have NOT been paid by the guest';

    const VACATION_DESCRIPTION                           = '<b>%s</b> from <b>%s</b> to <b>%s</b>, <b>%s</b> vacation days deducted';
    const VACATION_DESCRIPTION_SICK_OR_UNPAID            = '<b>%s</b> from <b>%s</b> to <b>%s</b>, <b>%s</b> working days';
    const SCHEDULE_NOT_WORKING                           = 'Not Working';
    const SCHEDULE_PENDING                               = 'Pending Vacation';
    const SCHEDULE_SICK_DAY                              = 'Sick Leave';
    const SCHEDULE_VACATION                              = 'Vacation';
    const SCHEDULE_OFFICE_REQUIRED                       = 'Office is required';
    const VACATION_RESPONSE_UD                           = "<b><a href='/profile/index/%d' target='_blank' >%s</a></b> your vacation <b>%s</b> from <b>%s</b> to <b>%s</b> is";
    const VACATION_REMINDER_UD                           = "<b><a href='/profile/index/%d' target='_blank'>%s</a></b> is going to vacation <b>%s</b> from <b>%s</b> to <b>%s</b>.";
    const INFO_VALID_CARD                                = 'Upon successful validation process OR successful transaction, the validation check-box will be automatically checked.';
    const INFO_REQUSET_PAYMENT                           = 'The validation of this reservation will be reset if you request a new credit card. Once the guest enters the new card details the reservation will be added to the Action Dashboard.';
    const INFO_SETTLING                                  = 'Settling a reservation means we accept fully the financial state of this reservation at the marked time. It should only be done after a full investigation from Finance Department.';
    const INFO_AFFILATE_PAID                             = 'Checking this box means that we have verified and paid the affiliate that contributed to this reservation.';
    const INFO_NO_COLLACTION                             = 'Checking this means we no longer will spend Operations Team resources on trying to collect the amount due. It will become a Finance Team matter.';

    const INFO_RESERVATION_TICKET_STATUS                 = 'Reservation status, identifying whether reservation booked, cancelled, etc.';
    const INFO_RESERVATION_TICKET_STATE                  = 'Reservation state, identifying whether reservation is normal, overbooked or overbooked then resolved.';
    const INFO_RESERVATION_TICKET_APARTEL                = 'Identifies whether reservation is non-apartel, on unknown apartel or on exact apartel.';
    const INFO_RESERVATION_TICKET_PAX                    = 'Reservation PAX, identifying guest count.';
    const INFO_RESERVATION_TICKET_CHANNEL                = 'Source of reservations, direct or partner';

    const PROFILE_GINOSI_RESERVATION                     = 'Reservation <a href="%s">%s</a> to <b>%s</b> from <b>%s</b> to <b>%s</b> for <b>%s</b> day(s) by <b>%s</b> email';
    const APARTMENT_IS_LIVE_SELLING                      = 'You can\'t set status Live and Selling or Selling not searchable when there is no';
    const APARTMENT_IS_LIVE_SELLING_GENRAL               = ' General information;';
    const APARTMENT_IS_LIVE_SELLING_RATE                 = ' Active Rate;';
    const APARTMENT_IS_LIVE_SELLING_LOCATION             = ' Location;';
    const APARTMENT_IS_LIVE_SELLING_IMG                  = ' Main Image;';
    const APARTMENT_NAME_ALREADY_EXIST                   = 'This apartment name already exist';

    const WEBSITE_REVIEW_HASH_INCORRECT                  = 'Review hash code is incorrect';
    const WEBSITE_REVIEW_ALREADY_EXISTS                  = 'Review for this booking ticket already exists';

    const NOTIFICATION_INVALID_ID                        = 'Invalid Notification ID';
    const NOTIFICATION_SOLVED_OK                         = 'Notification solved successfully';
    const NOTIFICATION_ARCHIVED_OK                       = 'Notification archived successfully';

    const ERROR_IS_GINOSI                                = '<br>ginosi.com email addresses can\'t be in black list';

    const USER_EVALUATIONS_LIST_ERROR                    = 'Can not find evaluations for this user';
    const USER_EVALUATION_CREATED                        = 'New evaluation created successfully and next evaluation was planned';
    const USER_EVALUATION_COMMENT_CREATED                = 'New evaluation comment created successfully';
    const USER_EVALUATION_WARNING_CREATED                = 'New evaluation warning created successfully';
    const USER_EVALUATION_CANNOT_BE_CREATE               = 'You can not create new evaluation';
    const USER_EVALUATION_CANNOT_BE_DELETE               = 'You can not delete this evaluation';
    const USER_EVALUATION_NOT_FOUND                      = 'Evaluation not found';
    const USER_EVALUATION_CANNOT_CANCELLED               = 'Problem during evaluation cancellation';
    const USER_EVALUATION_EMPTY_DESCRIPTION              = 'Evaluation description cannot be empty';

    const USER_DOCUMENTS_LIST_ERROR                      = 'Can not find documents for this user';
    const USER_DOCUMENT_CANNOT_BE_CREATE                 = 'You do not have permission to create new document';
    const USER_DOCUMENT_CANNOT_BE_DELETE                 = 'You do not have permission to delete this document';
    const USER_DOCUMENT_CANNOT_BE_UPLOAD                 = 'You do not have permission to upload document';
    const USER_DOCUMENT_CANNOT_BE_DOWNLOAD               = 'You can not download this document';
    const USER_DOCUMENT_NOT_FOUND                        = 'Document does not found';
    const USER_DOCUMENT_ATTACHMENT_NOT_FOUND             = 'Document attachment not found';
    const USER_DOCUMENT_EMPTY_DESCRIPTION                = 'Document description cannot be empty';
    const ERROR_HAS_DEBT_BALANCE                         = 'This guest has a previous balance. Please check the previous reservation made this email address and ensure proper actions.';
    const USER_SALARY_CANNOT_BE_VIEW                     = 'You do not have permission to view users salary';
    const USER_SALARY_CANNOT_BE_MANAGE                   = 'You do not have permission to modify users salary';

    const ERROR_USER_DEPARTMENT                          = ' %s already in other %s.';

    const SUCCESS_ISSUE_SOLVED_SECCESSFULLY              = 'Issue solved successfully';
    const ERROR_ISSUE_NOT_SOLVED                         = 'Issue is not solved yet';
    const ISSUE_DETECTED_FOLLOWING                       = 'Following issues were found';
    const CLOSE_APARTMENT_CALENDAR                       = "<b><a href='/profile/index/%d' target='_blank'>%s</a></b> closed days for <b><a href='/apartment/%d/calendar/%s' target='_blank'>%s</a></b> on <b>%s</b>.";
    const OPEN_APARTMENT_CALENDAR                        = "<b><a href='/profile/index/%d' target='_blank'>%s</a></b> opened days for <b><a href='/apartment/%d/calendar/%s' target='_blank'>%s</a></b> on <b>%s</b>.";
    const CLOSE_APARTMENT_INVENTORY                      = "<b><a href='/profile/index/%d' target='_blank'>%s</a></b> closed days for <b><a href='/apartment/%d/calendar/%s' target='_blank'>%s</a></b> from <b>%s</b> to <b>%s</b> %s.";
    const OPEN_APARTMENT_INVENTORY                       = "<b><a href='/profile/index/%d' target='_blank'>%s</a></b> opened days for <b><a href='/apartment/%d/calendar/%s' target='_blank'>%s</a></b> from <b>%s</b> to <b>%s</b> %s.";
    const DISPLAY_DASHBOARD                              = "Display Dashboard";
    const SYSTEM_USER                                    = "System User";
    const MANAGER_DISABLED                               = "<b>%s</b>'s manager, <b>%s</b> has been disabled.";
    const USER_ACTIVATE_ALERT                            = "Permissions of <b>%s</b> has been reset for security reasons and also <b>%s</b>'s vacation information has been reset. ";

    const SYSTEM_DATABASE_BACKUP_REMOVED                 = 'Database backup removed successfully';
    const SYSTEM_DATABASE_BACKUP_NOT_REMOVED             = 'Database backup cannot be removed!';
    const INSERT_PROBLEM                                 = "Problems in inserting data to database!";

    const SUCCESS_PINNED                                 = "Successfully pinned reservation.";
    const SUCCESS_UNPINNED                               = "Successfully unpinned reservation.";
    const SUCCESS_LOCKED                                 = "Successfully locked reservation.";
    const SUCCESS_UNLOCKED                               = "Successfully unlocked reservation.";
    const SUCCESS_FOUND                                  = "Successfully found.";
    const SEND_EVALUATE_INFORM_UD                        = "Evaluation message successfully sent to UD.";
    const NOT_SEND_EVALUATE_INFORM_UD                    = "Evaluation message not sent to UD.";
    const RECEIPT_THANK_YOU                              = 'Thank You for booking Ginosi Apartment for your stay.<br>The terms and conditions which applay to your purchase can be found at <a href="//www.ginosi.com/about-us/terms-and-conditions" target="_blank">www.ginosi.com/about-us/terms-and-conditions</a>';
    const TOTAL_PRICE_FOR_NIGHT                          = 'Night (%s)';
    const SUCCESS_CUBILIS_UPDATE                         = 'Cubilis details successfully updated.';
    const ERROR_SYNC_CUBILIS                             = 'Cannot sync with Cubilis.';
    const USER_EVALUATION_ADD                            = '<b><a href="%s" target="_blank">%s</a></b> has been evaluated by <b>%s</b>. "%s"';

    const ERROR_DESCRIPTION_EMPTY                        = 'Description field is empty.';
    const CARD_PENDING_TEXT                              = "Warning: There is a credit card for this reservation, that hasn\'t been processed yet. It can take a few minutes.";
    const SUCCESS_CHANGE_DATE                            = 'Dates successfully changed';
    const COMMENT_REVERSE_CHARGE_ON_MODIFY               = 'Reverse has been done on modification process';
    const COMMENT_PENALTY_CHARGE_ON_MODIFY               = 'Penalty has been applied on modification process';
    const ERROR_NO_AVAILABILITY                          = 'No availability for this date range';
    const CREDIT_CARD_DATA_NOT_VALID                     = 'Provided Credit Card data is not valid';
    const SUCCESS_ADD_TO_BLACKLIST                       = 'Successfully Added to Blacklist';
    const COMMENT_TRANSACTION_ONLY_STATUS                = "Transaction status has been changed from <b>%s</b> To <b>%s</b> on %s by %s";
    const COMMENT_TRANSACTION_STATUS_TYPE                = "Transaction has been changed from <b>%s</b> with status <b>%s</b> to <b>%s</b> with status <b>%s</b> on %s by %s";

    const SUCCESS_PARTNER_ID_CHANGED                     = 'Partner Successfully changed';
    const ERROR_PARTNER_ID_CHANGED                       = 'Partner has not been changed';
    const RECEIVED_BY_MONEY_DIRECTION                    = 'Transaction money direction';

    const ERROR_CHARGE_DISCOUNT_AMOUNT_MORE_THAN_ALLOWED = 'Error: The total amount of discount cannot exceed more than 20% of product value.';
    const INFO_CCCA_VERIFIED                             = 'Checking this means that the CCCA Verification has been checked.';
    const INFO_RESERVATION_LOCKED                        = 'Checking this means that the reservation can not be moved';
    const MODIFICATION_DATE_NOT_PAST                     = 'Date From should not be in the past. <br>';
    const MODIFICATION_STATUS_BOOKED                     = 'Reservation status should be Booked. <br>';
    const MODIFICATION_NO_INDICATED                      = 'No date changes indicated. <br>';
    const MODIFICATION_DATE_SHOULD_FUTURE                = 'Date To should be in the future. <br>';
    const MODIFICATION_BAD_DATA_FOR_CHANGE               = 'Bad Data for change Date';
    const MODIFICATION_RATE_CHANGE_IF_ORIGINAL_NOT_EXIST = 'Reservation has been extended with <b>%s</b> rate on <b>%s</b> date as the original rate is not longer available.';
    const ARRIVAL_STATUS_CHECK_IN                        = 'Arrival status changed to "Checked-in"';
    const ARRIVAL_STATUS_CHECK_OUT                       = 'Arrival status changed to "Checked-out"';
    const ARRIVAL_STATUS_NO_SHOW                         = 'Arrival status changed to "No show"';
    const SUCCESS_CREATED                                = 'Successfully created';
    const SUCCESS_CREATED_SKU                            = 'SKU successfully created';
    const TASK_TITLE_FOR_FOB                             = 'Hand over a fob';
    const TASK_TITLE_FOR_KEY                             = 'Hand over keys';
    const TASK_FOB_KEY_DESCRIPTION                       = 'Extra %s have been given to customer. Please take back on check-out.';
    const TASK_ERROR_NO_PERMISSION_CHANGE_STATUS         = 'You have no permission to change the status of this task';
    const TASK_STATUS_CHANGE_MARKED_DONE                 = 'Task successfully marked as done';
    const TASK_STATUS_CHANGED                            = 'Task status successfully changed';
    const SUCCESS_ADD_EMAIL                              = 'Successfully added email';
    const ERROR_ALREADY_EXIST_PARTNER_CITY               = 'Error: Already exist this city partner combination';

    const CANNOT_DEACTIVATE_PERMANENT_TEAM               = 'It\s not possible to deactivate <b>permanent team</b>.';
    const ERROR_EMAIL_NOT_FOUND                          = 'Email not found.';
    const ERROR_INVALID_DATA                             = 'Invalid data provided';
    const SUCCESS_SEND_QUEUE                             = 'Successfully sent to queue';
    const INFO_FOR_PRIMARY_EMAIL                         = 'Email address that is being provided upon making the booking. This is the email that is being used to send all kind of automatic emails to the guest (e.g. confirmation email, KI, etc.)';
    const INFO_FOR_SECONDARY_EMAIL                       = 'Additional Email that is being provided by the guest and upon guests requests. This email can be used to manually send the information to the guest. (e.g. confirmation email, KI, etc.)';



    const SUCCESS_UPDATE_CUBILIS_DATA = 'Cubilis details successfully updated.';
    const SUCCESS_CONNECTED_TO_CUBILIS = 'Successfully connected to Cubilis.';
    const SUCCESS_DISCONNECTED_FROM_CUBILIS = 'Successfully disconnected from Cubilis.';
    const ERROR_DUPLICATE_APARTEL_TYPE = 'Error: Has duplicate type.';
    const ERROR_DUPLICATE_APARTEL_RATE = 'Error: Has duplicate rate.';
    const ERROR_DUPLICATE_APARTMENT_GROUP_NAME = 'Has duplicate apartment group name';
    const CUBILIS_SUCCESS_UPDATE = 'Cubilis details successfully updated.';
    const BAD_REQUEST = 'Error: Bad request.';
    const HAS_CUBILIS_LINK = 'Error: Cannot delete, because has link to Cubilis.';
    const ERROR_NOT_UPDATE_AVAILABILITY_APARTEL = 'Error: Cannot update availability monthly for Apartel.';
    const INFO_RELATED_RESERVATION = 'Reservations that arrived alongside current reservation.';
    const CUBILIS_NOT_CONNECTED = 'Not Connected To Cubilis';
    const CUBILIS_CONNECTED = 'Cubilis Connected';
    const PRICE_EXCEED_LIMIT = 'The new set price is too different from the original one. Are you sure to change the rate price?';
    const OVERBOOKING_STATUS_CHANGE_ERROR = 'Error: Cannot change overbooking status';
    const OVERBOOKING_STATUS_CHANGE_NOT_OPEN_DAY = 'The state of the overbooking has been changed to Overbooking, but the availability was not updated.';

    const NO_PERMISSION = 'You have no permission to do this action';
    const CONTACTS_NOT_FOUND = 'Contact not found';
    const CONTACTS_DUPLICATE_BY_NAME_WITHIN_TEAM = 'Found a duplicate by Name in the selected Team';
    const DOWNLOAD_ERROR_CSV = 'Your query result contains more than 1000 rows. Please make a smaller query to be able download.';
    const AVAILABILITY_CLOSE_MSG = 'Please fill message.';
    const SUCCESS_FROZEN = 'Successfully Frozen';
    const SUCCESS_UNFROZEN = 'Successfully Unfrozen';
    const SUCCESS_ARCHIVE = 'Successfully Archived';
    const SUCCESS_UNARCHIVE = 'Successfully Unarchived';

    const EXPENSE_TICKET_NOT_FOUND  = 'Expense ticket not found';
    const STORAGE_TEAM_NAME = '%s Storage';
    const STORAGE_TEAM_DESCRIPTION = 'This %s team is used for access control of assets from this storage.';

    const STORAGE_CREATED_TASK_TITLE = 'New team has been created for %s storage';
    const STORAGE_CREATED_TASK_DESCRIPTION = 'New team has been created for %s storage. Please set up team director and staff members.';
    const UNIQUE_SKU  = 'SKU should be unique';
    const WAREHOUSE_ORDER_CATEGORY_DOES_NOT_CORRESPOND_WITH_LOCATION = 'Consumable category does not correspond with not storage type locations';

    const APARTEL_DETAILS_SAVED_SUCCESSFULLY = 'Apartel details saved successfully';
    const APARTEL_DETAILS_NOT_SAVED = 'Apartel details not saved';
    const DUPLICATE_CUBILIS_CONNECTION = 'Another connection with the same details was detected. Effected Properties: <b>%s</b>';

    const SUPPLIER_ACCOUNT_CANNOT_BE_MANAGE = 'You do not have permission to modify supplier account';
    const PARTNER_ACCOUNT_CANNOT_BE_MANAGE = 'You do not have permission to modify partner account';

    const CHANGE_APARTMENT_OCCUPANCY = "Tha Occupancy of reservation is more than apartment\'s Max Capacity";
    const CATEGORY_MERGED = "Category successfully Merged";
}
