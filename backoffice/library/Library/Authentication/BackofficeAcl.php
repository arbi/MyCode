<?php

namespace Library\Authentication;

use DDD\Service\Booking\BookingTicket;
use DDD\Service\Apartment\ReviewCategory;

use Library\Constants\Roles;

class BackofficeAcl
{
    public static function getResourceRole()
    {
        return [
            Roles::ROLE_NULL => [
                ['controller' => 'home', 'action' => []],
                ['controller' => 'upload', 'action' => []],
                ['controller' => 'omnisearch', 'action' => ['index']],
                ['controller' => 'user', 'action' => []],
                ['controller' => 'test', 'action' => []],
                ['controller' => 'task', 'action' => []],
                ['controller' => 'feedback', 'action' => []],
                ['controller' => 'notification', 'action' => []],
                ['controller' => 'cc-provide', 'action' => []],
                ['controller' => 'charge-authorization', 'action' => []],
                ['controller' => 'language', 'action' => []],
                ['controller' => 'applicants', 'action' => []],
                ['controller' => 'common', 'action' => []],
                ['controller' => 'controller_apartment_document', 'action' => []],
                ['controller' => 'controller_apartment_welcome_note', 'action' => []],
                ['controller' => 'controller_bo_user_evaluation', 'action' => []],
                ['controller' => 'universal-dashboard', 'action' => []],
                ['controller' => 'universal-dashboard-data', 'action' => []],
                ['controller' => 'controller_venue_lunchroom', 'action' => []],
            ],
            Roles::ROLE_DEVELOPMENT_TESTING => [
                ['controller' => 'test-results', 'action' => []],
            ],
            Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL => [
                ['controller' => 'purchase-order', 'action' => []],
                ['controller' => 'transfer', 'action' => []],
            ],
            Roles::ROLE_EXPENSE_CREATOR => [
                ['controller' => 'purchase-order', 'action' => []],
            ],
            Roles::ROLE_PO_ITEM_CREATOR => [
                ['controller' => 'purchase-order', 'action' => []],
            ],
            Roles::ROLE_PO_ITEM_MANAGEMENT => [
                ['controller' => 'purchase-order-item', 'action' => []],
                ['controller' => 'purchase-order', 'action' => ['get-sub-categories', 'get-accounts', 'get-cost-centers']],
            ],
            Roles::ROLE_PEOPLE_DIRECTORY => [
                ['controller' => 'company-directory', 'action' => []],
                ['controller' => 'controller_bo_user_schedule', 'action' => ['ajax-save-schedule']],
            ],
            Roles::ROLE_PEOPLE_SCHEDULE_VIEWER => [
                ['controller' => 'controller_bo_user_schedule', 'action' => []],
            ],
            Roles::ROLE_PEOPLE_SCHEDULE_EDITOR => [
                ['controller' => 'controller_bo_user_schedule', 'action' => []],
            ],
            Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER => [
                ['controller' => 'concierge', 'action' => ['ajaxgetuser', 'ajaxgetmeneger', 'ajaxgethousekeeper']],
                ['controller' => 'apartment-group-apartel', 'action' => ['index', 'ajax-save-ota', 'remove-ota', 'ajax-check-ota']],
                ['controller' => 'concierge-dashboard', 'action' => ['index']],
                ['controller' => 'apartment-group-document', 'action' => []],

            ],
            Roles::ROLE_CONCIERGE_DASHBOARD => [
                ['controller' => 'concierge', 'action' => ['item']],
                ['controller' => 'concierge-dashboard', 'action' => ['index']],
                ['controller' => 'frontier', 'action' => ['ajax-change-arrival-status', 'ajax-send-comment']],
            ],
            Roles::ROLE_APARTMENT_GROUP_MANAGEMENT => [
                ['controller' => 'concierge', 'action' => ['ajaxcheckname', 'item']],
                ['controller' => 'apartment-group-general', 'action' => ['index', 'edit', 'ajaxsave', 'ajaxcheckname', 'ajaxgetapartmentsforcountry']],
                ['controller' => 'apartment-group-concierge', 'action' => ['index', 'ajaxsave']],
                ['controller' => 'apartment-group-apartel', 'action' => ['index', 'ajax-save-ota', 'remove-ota', 'ajax-check-ota']],
                ['controller' => 'apartment-group-history', 'action' => ['index']],
                ['controller' => 'apartment-group-building', 'action' => ['index', 'ajaxsave', 'ajax-save-section', 'delete']],
                ['controller' => 'apartment-group-document', 'action' => []],
                ['controller' => 'apartment-group', 'action' => []],
                ['controller' => 'translation', 'action' => ['view']],
                ['controller' => 'apartment-group-contacts', 'action' => []],

            ],
            Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER => [
                ['controller' => 'apartment-group-general', 'action' => ['ajax-create-apartel', 'ajax-deactivate-apartel']],
            ],
            Roles::ROLE_FRONTIER_CHARGE => [
                ['controller' => 'frontier', 'action' => ['charge', 'ajax-frontier-charge', 'ajax-cc-new-data']],
            ],
            Roles::ROLE_PROFILE => [
                ['controller' => 'profile', 'action' => []],
            ],
            Roles::ROLE_CURRENCY_MANAGEMENT => [
                ['controller' => 'currency', 'action' => []],
            ],
            Roles::ROLE_HOUSEKEEPING => [
                ['controller' => 'housekeeping-tasks', 'action' => []],
            ],
            Roles::ROLE_GLOBAL_HOUSEKEEPING_MANAGER => [
                ['controller' => 'housekeeping-tasks', 'action' => []],
            ],
            Roles::ROLE_PARTNER_MANAGEMENT => [
                ['controller' => 'partners', 'action' => []],
            ],
            Roles::ROLE_LOCATION_MANAGEMENT => [
                ['controller' => 'location', 'action' => []],
            ],
            Roles::ROLE_BLOG_MANAGEMENT => [
                ['controller' => 'blog', 'action' => []],
            ],
            Roles::ROLE_NEWS_MANAGEMENT => [
                ['controller' => 'news', 'action' => []],
            ],
            Roles::ROLE_BOOKING_MANAGEMENT => [
                ['controller' => 'booking', 'action' => []],
                ['controller' => 'controller_apartment_apartment', 'action' => ['search-country', 'search-country-city', 'search-by-address-components', 'get-buildings']],
            ],
            Roles::ROLE_CONFIG_MANAGEMENT => [
                ['controller' => 'system', 'action' => []],
            ],
            Roles::ROLE_CONTENT_EDITOR_PRODUCT => [
                ['controller' => 'translation', 'action' => []],
            ],
            Roles::ROLE_CONTENT_EDITOR_TEXTLINE => [
                ['controller' => 'translation', 'action' => []],
            ],
            Roles::ROLE_CONTENT_EDITOR_LOCATION => [
                ['controller' => 'translation', 'action' => []],
            ],
            Roles::ROLE_GLOBAL_TASK_MANAGER => [
                ['controller' => 'task', 'action' => []],
            ],
            Roles::ROLE_TASK_MANAGEMENT => [
                ['controller' => 'task', 'action' => []],
            ],
            Roles::ROLE_APARTEL_INVENTORY => [
                ['controller' => 'group-inventory', 'action' => []],
            ],
            Roles::ROLE_MONEY_ACCOUNT => [
                ['controller' => 'money-account', 'action' => []],
            ],
            Roles::ROLE_APARTMENT_MANAGEMENT => [
                ['controller' => 'controller_apartment_main', 'action' => []],
                ['controller' => 'controller_apartment_general', 'action' => []],
                ['controller' => 'controller_apartment_details', 'action' => []],
                ['controller' => 'controller_apartment_furniture', 'action' => []],
                ['controller' => 'controller_apartment_media', 'action' => []],
                ['controller' => 'controller_apartment_location', 'action' => []],
                ['controller' => 'controller_apartment_review', 'action' => []],
                ['controller' => 'controller_apartment_statistics', 'action' => []],
                ['controller' => 'controller_apartment_history', 'action' => []],
                ['controller' => 'controller_apartment_channel_connection', 'action' => ['index', 'ajax-save-ota', 'remove-ota', 'ajax-check-ota-connection']],
                [
                    'controller' => 'controller_apartment_apartment',
                    'action' => [
                        'search',
                        'get-autocomplete-results',
                        'search-by-address-components',
                        'search-country',
                        'get-buildings',
                        'get-autocomplete-building',
                        'get-apartment-search-json',
                    ]
                ],
                ['controller' => 'translation', 'action' => ['view', 'editkidirectentry']],
            ],
            Roles::ROLE_DOCUMENTS_MANAGEMENT => [
                [
                    'controller' => 'controller_document',
                    'action' => [],
                ],
            ],
            Roles::ROLE_APARTMENT_COSTS_READER => [
                ['controller' => 'controller_apartment_costs', 'action' => []],
            ],
            Roles::ROLE_APARTMENT_INVENTORY_READER => [
                ['controller' => 'controller_apartment_inventory_calendar', 'action' => ['index']],
            ],
            Roles::ROLE_APARTMENT_INVENTORY_MANAGER => [
                ['controller' => 'controller_apartment_inventory_range', 'action' => []],
                ['controller' => 'controller_apartment_inventory_calendar', 'action' => []],
                ['controller' => 'controller_apartment_rate', 'action' => []],
                ['controller' => 'controller_apartel_general', 'action' => []],
                ['controller' => 'controller_apartel_type_rate', 'action' => []],
                ['controller' => 'controller_apartel_calendar', 'action' => []],
                ['controller' => 'controller_apartel_inventory', 'action' => []],
            ],
            Roles::ROLE_APARTMENT_CONNECTION => [
                ['controller' => 'controller_apartment_channel_connection', 'action' => ['save', 'connect', 'test-pull-reservations', 'test-update-availability', 'test-fetch-list', 'link']],
                ['controller' => 'controller_apartel_general', 'action' => []],
                ['controller' => 'controller_apartel_connection', 'action' => []],
                ['controller' => 'controller_apartel_content', 'action' => []],
                ['controller' => 'controller_apartel_history', 'action' => []],
            ],

            Roles::ROLE_APARTMENT_OCCUPANCY_STATISTICS => [
                ['controller' => 'controller_apartment_occupancy_statistics', 'action' => []],
                ['controller' => 'controller_apartment_apartment', 'action' => ['search', 'get-json', 'get-autocomplete-results', 'search-by-address-components', 'search-country', 'get-buildings', 'get-autocomplete-building']],
            ],
            Roles::ROLE_APARTMENT_SALES_STATISTICS => [
                ['controller' => 'controller_apartment_sales_statistics', 'action' => []],
            ],
            Roles::ROLE_PSP => [
                ['controller' => 'psp', 'action' => []],
            ],
            Roles::ROLE_DISTRIBUTION_VIEW => [
                ['controller' => 'distribution-view', 'action' => []],
            ],
            Roles::ROLE_APARTMENT_REVIEW_CATEGORY => [
                ['controller' => 'controller_apartment_review_category', 'action' => []],
            ],
            Roles::ROLE_TEAM => [
                ['controller' => 'team', 'action' => []],
            ],
            Roles::ROLE_SUPPLIERS_MANAGEMENT => [
                ['controller' => 'suppliers', 'action' => []],
            ],
            Roles::ROLE_LEGAL_ENTITIES_MANAGEMENT => [
                ['controller' => 'legal-entities', 'action' => []],
            ],
            Roles::ROLE_OFFICE => [
                ['controller' => 'office', 'action' => [
                    'index',
                    'get-json',
                    'edit',
                    'ajaxsave',
                    'ajaxdeleteoffice',
                    'ajaxdeactiveoffice',
                    'ajaxactiveoffice',
                    'ajaxcheckname',
                    'getprovinceoptions',
                    'getcityoptions',
                    'change-section-status',
                ]],
            ],
            Roles::ROLE_OFFICE_COST_VIEWER => [
                ['controller' => 'office', 'action' => [
                    'ajax-get-office-costs',
                    'ajax-download-office-costs-csv'
                ]],
            ],
            Roles::ROLE_EXPENSE_CATEGORY_MANAGEMENT => [
                ['controller' => 'expense-item-categories', 'action' => []],
            ],
            Roles::ROLE_JOB_MANAGEMENT => [
                ['controller' => 'jobs', 'action' => []],
            ],
            Roles::ROLE_APPLICANT_MANAGEMENT => [
                ['controller' => 'applicants', 'action' => []],
            ],
            Roles::ROLE_FINANCE_ACCOUNT_RECEIVABLE => [
                ['controller' => 'chart', 'action' => []],
            ],
            Roles::ROLE_FRONTIER_MANAGEMENT => [
                    ['controller' => 'frontier', 'action' => []],
                    ['controller' => 'booking', 'action' => ['ajax-get-parking-spots']],
            ],
//            Roles::ROLE_EXPENSE_MANAGEMENT => [
//                ['controller' => 'purchase-order', 'action' => []],
//            ],
            Roles::ROLE_LOCK_MANAGEMENT => [
                ['controller' => 'controller_lock_general', 'action' => []],
            ],
            Roles::ROLE_VENUE_MANAGEMENT => [
                ['controller' => 'controller_venue_general', 'action' => []],
            ],
            Roles::ROLE_VENUE_MANAGER => [
                ['controller' => 'controller_venue_general', 'action' => []],
                ['controller' => 'controller_venue_charges', 'action' => []],
                ['controller' => 'controller_venue_items', 'action' => []],
            ],
            Roles::ROLE_VENUE_CHARGE_MANAGER => [
                ['controller' => 'controller_venue_charges', 'action' => []],
            ],
            Roles::ROLE_PARKING_MANAGEMENT => [
                ['controller' => 'controller_parking_general', 'action' => []],
                ['controller' => 'controller_parking_lots', 'action' => []],
                ['controller' => 'controller_parking_spots', 'action' => []],
                ['controller' => 'controller_parking_inventory_calendar', 'action' => []],
                ['controller' => 'translation', 'action' => ['view']],
                ['controller' => 'controller_apartment_location', 'action' => ['get-province-options']],
                ['controller' => 'controller_apartment_location', 'action' => ['get-city-options']],
            ],
            Roles::ROLE_PARKING_INVENTORY_MODULE => [
                ['controller' => 'controller_parking_inventory', 'action' => []],
            ],
            Roles::ROLE_CONTACTS_MANAGEMENT => [
                ['controller' => 'contacts', 'action' => []],
            ],
            Roles::ROLE_CONTACTS_GLOBAL_MANAGER => [
                ['controller' => 'contacts', 'action' => []],
            ],
            Roles::ROLE_TAG_MANAGEMENT => [
                ['controller' => 'tag', 'action' => []],
            ],
            Roles::ROLE_ASSET_MANAGEMENT_CATEGORY => [
                ['controller' => 'controller_warehouse_category', 'action' => []],
            ],
            Roles::ROLE_WAREHOUSE_MANAGEMENT => [
                ['controller' => 'controller_warehouse_storage', 'action' => []],
            ],
            Roles::ROLE_BUDGET_MANAGER_GLOBAL => [
                ['controller' => 'budget', 'action' => []],
            ],
            Roles::ROLE_FINANCE_BUDGET_HOLDER => [
                ['controller' => 'budget', 'action' => []],
            ],
            Roles::ROLE_WH_ORDER_MANAGEMENT => [
                ['controller' => 'warehouse_order', 'action' => []]
            ],
            Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL => [
                ['controller' => 'warehouse_order', 'action' => []]
            ],
            Roles::ROLE_WH_CREATE_ORDER_FUNCTION => [
                ['controller' => 'warehouse_order', 'action' => ['add', 'ajax-get-order-locations']]
            ],
            Roles::ROLE_ASSET_MANAGEMENT => [
                ['controller' => 'controller_warehouse_asset', 'action' => []],
            ],
            Roles::ROLE_ASSET_MANAGEMENT_GLOBAL => [
                ['controller' => 'controller_warehouse_asset', 'action' => []],
            ],
            Roles::ROLE_REVIEW_MANAGEMENT => [
                ['controller' => 'controller_reviews_general', 'action' => []],
            ],
            Roles::ROLE_ESPM_MODULE => [
                ['controller' => 'espm', 'action' => []],
                ['controller' => 'purchase-order', 'action' => ['get-accounts']],
            ],
        ];
    }
}
