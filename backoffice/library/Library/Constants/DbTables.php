<?php

namespace Library\Constants;

class DbTables {
	// Product
	const TBL_APARTMENTS                    = 'ga_apartments';
    const TBL_APARTMENT_RATES               = 'ga_apartment_rates';

	const TBL_APARTMENTS_DETAILS            = 'ga_apartment_details';
	const TBL_PRODUCT_DESCRIPTIONS          = 'ga_product_description';
	const TBL_APARTMENT_IMAGES              = 'ga_apartment_images';
	const TBL_APARTMENT_LOCATIONS           = 'ga_apartment_locations';
	const TBL_PRODUCT_TYPES                 = 'ga_product_types';
	const TBL_PRODUCT_STATUSES              = 'ga_product_statuses';
	const TBL_PRODUCT_TEXTLINES             = 'ga_product_textlines';
	const TBL_APARTMENT_OTA_DISTRIBUTION    = 'ga_apartment_ota_distribution';
	const TBL_APARTMENT_SPOTS               = 'ga_apartment_spots';

	const TBL_DOCUMENTS 			        = 'ga_documents';
	const TBL_APARTMENT_DOCUMENTS 			= 'ga_apartment_docs';
	const TBL_APARTMENT_GROUP_DOCUMENTS 	= 'ga_apartment_group_docs';
	const TBL_DOCUMENT_TYPES        		= 'ga_document_types';

    const TBL_APARTMENT_AMENITIES           = 'ga_apartment_amenities';
    const TBL_APARTMENT_AMENITY_ITEMS       = 'ga_apartment_amenity_items';

	const TBL_APARTMENT_FURNITURE			= 'ga_apartment_furniture';
	const TBL_APARTMENT_FURNITURE_TYPES		= 'ga_furniture_types';

    const TBL_PRODUCT_REVIEWS               = 'ga_reviews';
    const TBL_APARTMENT_INVENTORY           = 'ga_apartment_inventory';
    const TBL_RESERVATION_NIGHTLY           = 'ga_reservation_nightly';

    const TBL_CURRENCY                      = 'ga_currency';
    const TBL_CURRENCY_VAULT                = 'ga_currency_vault';
    const TBL_CONTINENTS                    = 'ga_continents';
    const TBL_COUNTRIES                     = 'ga_countries';
    const TBL_PROVINCES                     = 'ga_provinces';
    const TBL_CITIES                        = 'ga_cities';
    const TBL_POI                           = 'ga_poi';
    const TBL_POI_TYPE                      = 'ga_poi_types';
    const TBL_UN_TEXTLINES                  = 'ga_un_textlines';
    const TBL_PAGES                         = 'ga_pages';
    const TBL_LOCATION_DETAILS              = 'ga_location_details';
	const TBL_UN_TEXTLINE_PAGE_REL			= 'ga_un_textline_page_rel';
    // Finance Module
    // Expenses
    const TBL_EXPENSES                      = 'ga_expense';
    const TBL_EXPENSE_ITEM                  = 'ga_expense_item';
    const TBL_EXPENSE_COST                  = 'ga_expense_cost';
    const TBL_EXPENSE_ITEM_CATEGORIES       = 'ga_expense_item_category';
    const TBL_EXPENSE_ITEM_SUB_CATEGORIES   = 'ga_expense_item_sub_category';
    const TBL_EXPENSE_ATTACHMENTS           = 'ga_expense_attachments';
    const TBL_EXPENSE_ITEM_ATTACHMENTS      = 'ga_expense_item_attachments';
    const TBL_BUDGETS                       = 'ga_budgets';
    const TBL_BUDGET_CATEGORIES             = 'ga_budget_categories';
    const TBL_BUDGET_STATUSES               = 'ga_budget_statuses';

    // Transactions
    const TBL_TRANSACTIONS                  = 'ga_transactions';
    const TBL_TRANSACTION_ACCOUNTS          = 'ga_transaction_accounts';
    const TBL_TRANSFER_TRANSACTIONS         = 'ga_transfer_transactions';
    const TBL_EXPENSE_TRANSACTIONS          = 'ga_expense_transaction';
    const TBL_REL_PARTNER_COLLECTION_TRANSACTIONS = 'ga_rel_partner_collection_transaction';
    const TBL_PENDING_TRANSFER              = 'ga_pending_transfer';

    // Customers, Wallet, CC
    const TBL_CUSTOMERS                     = 'ga_customers';
    const TBL_CUSTOMER_IDENTITY             = 'ga_customer_identity';
    const TBL_CC_CREATION_QUEUE             = 'ga_cc_creation_queue';
    const TBL_CC_TEST_NUMBERS               = 'ga_cc_test_numbers';

	const TBL_FRAUD_CC                      = 'ga_fraud_cc';
	const TBL_FRAUD_CC_HASHES               = 'ga_fraud_cc_hashes';

    const TBL_TOKEN                         = 'ga_tokens';
    const TBL_SUPPLIERS						= 'ga_suppliers';

    const TBL_LEGAL_ENTITIES                = 'ga_legal_entities';
    const TBL_BANK                          = 'ga_bank';

    // Geolite country tables (MaxMind)
	const TBL_GEOLITE_COUNTRY               = 'ga_geo_blocks';
	const TBL_GEOLITE_COUNTRY_TEMP          = 'geo_blocks_tmp';

	const TBL_BOOKINGS                      = 'ga_reservations';
	const TBL_RESERVATION_ISSUES            = 'ga_reservation_issues';
	const TBL_RESERVATION_ISSUE_TYPES       = 'ga_reservation_issue_types';
    const TBL_BOOKINGS_QUEUE                = 'ga_reservation_email_queue';
    const TBL_RESERVATION_ATTACHMENTS       = 'ga_reservation_attachments';
    const TBL_RESERVATION_ATTACHMENT_ITEMS  = 'ga_reservation_attachment_items';

    // Partners
    const TBL_BOOKING_PARTNERS              = 'ga_booking_partners';
    const TBL_BOOKING_PARTNER_ACCOUNTS      = 'ga_booking_partner_account';
    const TBL_BOOKING_PARTNER_GCM_VALUES    = 'ga_booking_partner_gcm_values';

	// Users
	const TBL_BACKOFFICE_USERS              = 'ga_bo_users';
	const TBL_BACKOFFICE_USER_GROUPS        = 'ga_user_groups';
	const TBL_DASHBOARDS    				= 'ga_ud_dashboards';
	const TBL_BACKOFFICE_USER_DASHBOARDS    = 'ga_bo_user_dashboards';
	const TBL_CONCIERGE_DASHBOARD_ACCESS    = 'ga_concierge_dashboard_access';
	const TBL_BACKOFFICE_USER_VACATIONS		= 'ga_bo_user_vacations';

	const TBL_WEBSITE_LANGUAGES             = 'ga_languages';
	const TBL_NEWS                          = 'ga_news';
	const TBL_BLOG_POSTS                    = 'ga_blog_posts';

    const TBL_BOOKING_STATUSES              = 'ga_booking_statuses';
    const TBL_BOOKING_ADDONS                = 'ga_addons';
    const TBL_CHARGE                        = 'ga_reservation_charges';
    const TBL_CHARGE_DELETED                = 'ga_reservation_charge_deleted';
    const TBL_CHARGE_TRANSACTION            = 'ga_reservation_transactions';
    const TBL_MONEY_ACCOUNT                 = 'ga_money_accounts';
    const TBL_MONEY_ACCOUNT_USERS           = 'ga_money_account_users';
    const TBL_MONEY_ACCOUNT_ATTACHMENTS     = 'ga_money_account_attachments';
    const TBL_MONEY_ACCOUNT_ATTACHMENT_ITEMS  = 'ga_money_account_attachment_items';

    const TBL_BLACK_LIST                    = 'ga_blacklist';
    const TBL_FRAUD_DETECTION_CC            = 'ga_fraud_detection_cc';
    const TBL_TASK                    		= 'ga_task';
    const TBL_TASK_STAFF               		= 'ga_tasks_staff';
    const TBL_TASK_TYPE               		= 'ga_task_types';
    const TBL_TASK_ATTACHMENTS         		= 'ga_task_attachments';
    const TBL_TASK_SUBTASK            		= 'ga_task_subtasks';
    const TBL_APARTMENT_GROUP_ITEMS         = 'ga_apartment_group_items';
    const TBL_APARTMENT_GROUPS              = 'ga_apartment_groups';

    const TBL_BUILDING_FACILITIES           = 'ga_building_facilities';
    const TBL_BUILDING_FACILITY_ITEMS       = 'ga_building_facility_items';

    const TBL_BUILDINGLINK_CREDENTIALS      = 'ga_buildinglink_credentials';

    const TBL_INVENTORY_SYNCHRONIZATION_QUEUE = 'ga_inventory_synchronization_queue';
    const TBL_EMAIL_QUEUE                   = 'ga_email_queue';
    const TBL_ACTION_LOGS                   = 'ga_action_logs';
    const TBL_LOGS_TEAM                     = 'ga_action_logs_teams';
    const TBL_GROUPS						= 'ga_groups';
    const TBL_PSP						    = 'ga_psp';
	const TBL_APARTEL_OTA_DISTRIBUTION      = 'ga_apartel_ota_distribution';
	const TBL_APARTMENT_REVIEW_CATEGORY     = 'ga_apartment_review_category';
	const TBL_APARTMENT_REVIEW_CATEGORY_REL = 'ga_apartment_review_category_rel';
    const TBL_NOTIFICATIONS                 = 'ga_notifications';
    const TBL_TEAMS                         = 'ga_teams';
    const TBL_TEAM_STAFF                    = 'ga_team_staff';
    const TBL_TEAM_FRONTIER_APARTMENTS      = 'ga_team_frontier_apartments';
    const TBL_TEAM_FRONTIER_BUILDINGS       = 'ga_team_frontier_buildings';

    const TBL_USER_DOCUMENTS                = 'ga_bo_user_documents';
    const TBL_USER_DOCUMENT_TYPES           = 'ga_bo_user_document_types';

    const TBL_USER_EVALUATIONS              = 'ga_bo_user_evaluations';
    const TBL_USER_EVALUATION_TYPES         = 'ga_bo_user_evaluation_types';
    const TBL_USER_EVALUATION_ITEMS         = 'ga_bo_user_evaluation_items';
    const TBL_USER_EVALUATION_VALUES        = 'ga_bo_user_evaluation_values';
    const TBL_USER_SCHEDULE                 = 'ga_bo_user_schedules';
    const TBL_USER_SCHEDULE_INVENTORY       = 'ga_bo_user_schedule_inventory';
    const TBL_EXTERNAL_ACCOUNT              = 'ga_external_accounts';
    const TBL_SALARY_SCHEMES                = 'ga_bo_salary_schemes';
    const TBL_OFFICES                       = 'ga_offices';
    const TBL_OFFICE_SECTIONS               = 'ga_office_sections';
    const TBL_PINNED_RESERVATIONS           = 'ga_pinned_reservations';


    // Recruitment
    const TBL_HR_JOBS                       = 'ga_hr_jobs';
    const TBL_HR_APPLICANTS                 = 'ga_hr_applicants';
    const TBL_HR_APPLICANT_COMMENTS         = 'ga_hr_applicant_comments';
    const TBL_HR_INTERVIEWS                 = 'ga_hr_interviews';
    const TBL_HR_INTERVIEW_PARTICIPANTS     = 'ga_hr_interview_participants';

    const TBL_SETTINGS                      = 'ga_settings';

    const TBL_LOCKS                         = 'ga_locks';
    const TBL_LOCK_TYPES                    = 'ga_lock_types';
    const TBL_LOCK_TYPE_SETTING_ITEMS       = 'ga_lock_type_setting_items';
    const TBL_LOCK_SETTINGS                 = 'ga_lock_settings';
    const TBL_LOCK_TYPE_SETTINGS            = 'ga_lock_type_settings';
    const TBL_PARTNER_CITY_COMMISSION       = 'ga_partner_city_commission';
    const TBL_BUILDING_DETAILS              = 'ga_building_details';
    const TBL_BUILDING_SECTIONS             = 'ga_building_sections';
    const TBL_BUILDING_LOTS                 = 'ga_building_lots';

    //Parking
    const TBL_PARKING_LOTS                  = 'ga_parking_lots';
    const TBL_PARKING_SPOTS                 = 'ga_parking_spots';
    const TBL_PARKING_INVENTORY             = 'ga_parking_inventory';

    //Ccca
    const TBL_CCCA                          = 'ga_ccca';

    // Apartel
    const TBL_APARTELS                      = 'ga_apartels';
    const TBL_APARTELS_DETAILS              = 'ga_apartel_details';
    const TBL_APARTEL_TYPE                  = 'ga_apartel_type';
    const TBL_APARTEL_REL_TYPE_APARTMENT    = 'ga_rel_apartel_type_apartment';
    const TBL_APARTEL_RATES                 = 'ga_apartel_rates';
    const TBL_APARTEL_INVENTORY             = 'ga_apartel_inventory';
    const TBL_RESERVATIONS_IDENTIFICATOR    = 'ga_reservations_identificator';
    const TBL_APARTEL_FISCAL                = 'ga_apartel_fiscal';


    const TBL_CONTACTS                      = 'ga_contacts';

    const TBL_TAG                           = 'ga_tag';
    const TBL_TASK_TAG                      = 'ga_task_tag';

    // Warehouse
    const TBL_ASSET_CATEGORIES              = 'ga_asset_categories';
    const TBL_WM_STORAGE                    = 'ga_wm_storages';
    const TBL_WM_THRESHOLD                  = 'ga_wm_threshold';
    const TBL_SKU                           = 'ga_sku';

    const TBL_WM_ORDERS                     = 'ga_wm_orders';

    const TBL_ASSETS_CHANGES                = 'ga_assets_changes';
    const TBL_ASSETS_VALUABLE               = 'ga_assets_valuable';
    const TBL_ASSETS_CONSUMABLE             = 'ga_assets_consumable';
    const TBL_ASSETS_CONSUMABLE_SKUS_RELATION  = 'ga_assets_consumable_skus_relation';
    const TBL_ASSETS_VALUABLE_STATUSES      = 'ga_assets_valuable_statuses';
    const TBL_ASSET_CATEGORY_ALIASES        = 'ga_asset_category_aliases';

    // Venue tables
    const TBL_VENUES                        = 'ga_venues';
    const TBL_VENUE_CHARGES                 = 'ga_venue_charges';
    const TBL_VENUE_ITEMS                   = 'ga_venue_items';
    const TBL_LUNCHROOM_ORDER_ARCHIVE       = 'ga_lunchroom_order_archive';

	// OAuth Tables
	const OAUTH_ACCESS_TOKENS 				= 'oauth_access_tokens';
	const TBL_API_REQUESTS 				    = 'ga_api_requests';
	const TBL_OAUTH_USERS 				    = 'oauth_users';
	const TBL_ESPM       				    = 'ga_espm';

	const TBL_USER_DEVICES                  = 'ga_bo_user_devices';

}
