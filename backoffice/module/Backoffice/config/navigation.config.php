<?php

use \Library\Constants\Roles;

return [
	'navigation' => [
		'main' => [
			[
				'label' => '<span class="visible-sm-inline-block">Dash</span><span class="hidden-sm">Dashboards</span>',
				'pages' => [
                    [
						'label'      => 'Concierge Dashboard',
						'controller' => 'concierge-dashboard',
						'action'     => 'index',
						'permission' => Roles::ROLE_CONCIERGE_DASHBOARD,
					],
                    [
                        'label'      => 'Housekeeping Dashboard',
                        'controller' => 'housekeeping-tasks',
                        'permission' => [
                            Roles::ROLE_HOUSEKEEPING,
                            Roles::ROLE_GLOBAL_HOUSEKEEPING_MANAGER,
                        ],
                    ],
                    [
						'label'      => 'Universal Dashboard',
						'route'      => 'universal-dashboard/default',
						'controller' => 'universal-dashboard',
					],
                    [
						'label'      => 'Frontier Cards',
						'controller' => 'frontier',
                        'permission' => Roles::ROLE_FRONTIER_MANAGEMENT,
					],

				]
			], [
				'label' => '<span class="visible-sm-inline-block">Admin</span><span class="hidden-sm">Administration</span>',
				'name'  => 'admin',
				'pages' => [
					[
						'label'      => 'Booking Management',
						'controller' => 'booking',
						'permission' => Roles::ROLE_BOOKING_MANAGEMENT,
					],
                    [
						'label'      => 'Partner Management',
						'controller' => 'partners',
						'permission' => Roles::ROLE_PARTNER_MANAGEMENT,
					],
                    [
						'label' => 'Task Management',
						'controller' => 'task',
						'permission' => [
                            Roles::ROLE_GLOBAL_TASK_MANAGER,
                            Roles::ROLE_TASK_MANAGEMENT
                        ],
					],
                    [
                        'label' => 'Tag Management',
                        'controller' => 'tag',
                        'permission' => [
                            Roles::ROLE_TAG_MANAGEMENT,
                        ],
                    ],
                    [
                        'label'      => '[Warehouse Management]',
                        'permission' => [
                            Roles::ROLE_ASSET_MANAGEMENT_CATEGORY,
                            Roles::ROLE_WAREHOUSE_MANAGEMENT,
                            Roles::ROLE_ASSET_MANAGEMENT,
                            Roles::ROLE_ASSET_MANAGEMENT_GLOBAL,
                        ],
                    ],
                    [
                        'label'      => 'Asset Management',
                        'route' => 'warehouse/asset',
                        'permission' => [
                            Roles::ROLE_ASSET_MANAGEMENT,
                            Roles::ROLE_ASSET_MANAGEMENT_GLOBAL,
                        ],
                    ],
                    [
                        'label'      => 'Asset Categories',
                        'route' => 'warehouse/category',
                        'permission' => Roles::ROLE_ASSET_MANAGEMENT_CATEGORY,
                    ],
                    [
                        'label'      => 'Storage Management',
                        'route' => 'warehouse/storage',
                        'permission' => Roles::ROLE_WAREHOUSE_MANAGEMENT,
                    ],
                    [
                        'label'      => 'Order Management',
                        'route'      => 'orders',
                        'permission' =>
                            Roles::ROLE_WH_ORDER_MANAGEMENT,
                            Roles::ROLE_WH_ORDER_MANAGEMENT_GLOBAL
                    ],
                    [
						'label'      => '[People Management]',
						'permission' => [
                            Roles::ROLE_PEOPLE_DIRECTORY,
                            Roles::ROLE_TEAM,
                            Roles::ROLE_OFFICE,
                        ],
					],
                    [
						'label'      => 'People Directory',
						'controller' => 'company-directory',
						'permission' => Roles::ROLE_PEOPLE_DIRECTORY,
					],
                    [
						'label'      => 'People Schedule',
						'controller' => 'user-schedule',
						'permission' => Roles::ROLE_PEOPLE_SCHEDULE_VIEWER,
					],
                    [
						'label'      => 'People Teams',
						'controller' => 'team',
						'permission' => Roles::ROLE_TEAM,
                    ],
                    [
                        'label'      => 'Contact Cards',
                        'route'		 => 'contacts',
                        'permission' => [
                            Roles::ROLE_CONTACTS_MANAGEMENT
                        ],
                    ],
                    [
                        'label'      => 'Our Offices',
                        'controller' => 'office',
                        'permission' => Roles::ROLE_OFFICE,
                    ],
                    [
                        'label'      => '[HR]',
                        'permission' => [
                            Roles::ROLE_JOB_MANAGEMENT,
                            Roles::ROLE_APPLICANT_MANAGEMENT
                        ],
                    ],
                    [
                        'label'      => 'Job Management',
                        'route'      => 'recruitment/jobs',
                        'permission' => [
                            Roles::ROLE_JOB_MANAGEMENT,
                        ],
                    ],
                    [
                        'label'      => 'Applicant Management',
                        'route'      => 'recruitment/applicants',
                        'permission' => [
                            Roles::ROLE_APPLICANT_MANAGEMENT,
                        ],
                    ],
                    [
                        'label'      => '[Venue]',
                        'permission' => [
                            Roles::ROLE_VENUE_MANAGEMENT
                        ],
                    ],
                    [
                        'label'      => 'Venue Management',
                        'route'      => 'venue',
                        'permission' => [
                            Roles::ROLE_VENUE_MANAGEMENT,
                        ],
                    ],
                    [
						'label'      => '[Control]',
						'permission' => [
                            Roles::ROLE_CONFIG_MANAGEMENT
                        ],
					],
                    [
						'label'      => 'System Configuration',
						'controller' => 'system',
						'permission' => Roles::ROLE_CONFIG_MANAGEMENT,
					],
				],
            ], [
                'label' => 'Finance',
                'pages' => [
                    [
                        'label'      => 'ESPM Management',
                        'route'      => 'finance/espm',
                        'permission' => Roles::ROLE_ESPM_MODULE,
                    ],
                    [
                        'label'      => 'Currency Management',
                        'controller' => 'currency',
                        'permission' => Roles::ROLE_CURRENCY_MANAGEMENT,
                    ],
                    [
                        'label'      => 'Money Accounts',
                        'route'		 => 'finance/money-account',
                        'controller' => 'money-account',
                        'action'     => 'index',
                        'permission' => Roles::ROLE_MONEY_ACCOUNT,
                    ],
                    [
                        'label' => 'Suppliers Management',
                        'controller' => 'finance',
                        'action'     => 'suppliers',
                        'permission' => Roles::ROLE_SUPPLIERS_MANAGEMENT,
                    ],
                    [
                        'label'      => 'Legal Entities Management',
                        'route'		 => 'finance/legal-entities',
                        'controller' => 'legal-entities',
                        'action'     => 'index',
                        'permission' => Roles::ROLE_LEGAL_ENTITIES_MANAGEMENT,
                    ],
                    [
                        'label'      => 'PSP Management',
                        'route'		 => 'finance/psp',
                        'action'     => 'index',
                        'permission' => Roles::ROLE_PSP,
                    ],
                    [
                        'label' => '[Account Receivable]',
                        'permission' => Roles::ROLE_FINANCE_ACCOUNT_RECEIVABLE,
                    ],
                    [
                        'label'      => 'Charges',
                        'route'		 => 'finance/chart/charge',
                        'permission' => Roles::ROLE_FINANCE_ACCOUNT_RECEIVABLE,
                    ],
                    [
                        'label'      => 'Transactions',
                        'route'		 => 'finance/chart/transaction',
                        'permission' => Roles::ROLE_FINANCE_ACCOUNT_RECEIVABLE,
                    ],
                    [
                        'label'      => '[Account Payable]',
                        'permission' => [
                            Roles::ROLE_EXPENSE_MANAGEMENT,
                            Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL,
                            Roles::ROLE_PO_ITEM_MANAGEMENT
                        ],
                    ],
                    [
                        'label'      => 'PO Management',
                        'route'      => 'finance/purchase-order',
                        'permission' => Roles::ROLE_EXPENSE_MANAGEMENT,
                    ],
                    [
                        'label'      => 'PO Item Search',
                        'route'		 => 'finance/item/search',
                        'permission' => Roles::ROLE_PO_ITEM_MANAGEMENT,
                    ],
					[
						'label'      => 'PO Categories',
						'route'      => 'finance/expense-item-categories',
						'action'     => 'index',
						'permission' => Roles::ROLE_EXPENSE_CATEGORY_MANAGEMENT,
					],
                    [
                        'label'      => 'Create Transfer',
                        'route'		 => 'finance/transfer',
                        'permission' => Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL,
                    ],

                    [
                        'label' => '[Budget]',
                        'permission' => Roles::ROLE_BUDGET_MANAGEMENT_MODULE,
                    ],
                    [
                        'label'      => 'Budget Management',
                        'route'      => 'finance/budget',
                        'permission' => Roles::ROLE_BUDGET_MANAGEMENT_MODULE,
                    ],
                    [
                        'label'      => 'Budget Overview',
                        'route'      => 'finance/budget/chart',
                        'permission' => Roles::ROLE_BUDGET_MANAGER_GLOBAL,
                    ],
                ],
			], [
				'label' => 'Content',
				'pages' => [
                    [
						'label'      => 'Blog',
						'controller' => 'blog',
						'permission' => Roles::ROLE_BLOG_MANAGEMENT,
					],
					[
						'label'      => 'Locations',
						'controller' => 'location',
						'permission' => Roles::ROLE_LOCATION_MANAGEMENT,
					],
                    [
						'label'      => 'News',
						'controller' => 'news',
						'permission' => Roles::ROLE_NEWS_MANAGEMENT,
					],
                    [
						'label'      => 'Textlines',
						'controller' => 'translation',
						'permission' => [
                            Roles::ROLE_CONTENT_EDITOR_PRODUCT,
                            Roles::ROLE_CONTENT_EDITOR_TEXTLINE,
                            Roles::ROLE_CONTENT_EDITOR_LOCATION
                        ],
					]
				]
			],
            [
				'label' => 'Apartments',
				'pages' => [
                    [
                        'label' => '[Apartment]',
                        'permission' => [
                            Roles::ROLE_APARTMENT_MANAGEMENT,
                            Roles::ROLE_LOCK_MANAGEMENT,
                            Roles::ROLE_APARTMENT_REVIEW_CATEGORY,
                        ]
                    ],
                    [
                        'label'      => 'Add New',
                        'permission' => Roles::ROLE_APARTMENT_MANAGEMENT,
                        'route'      => 'add_apartment',
                    ],
                    [
                        'label'      => 'Lock Management',
                        'route'      => 'lock',
                        'permission' => Roles::ROLE_LOCK_MANAGEMENT,
                    ],
                    [
                        'label'      => 'Review Codes',
                        'route'      => 'apartment_review_category',
                        'controller' => 'apartment-review-category',
                        'action'     => 'index',
                        'permission' => Roles::ROLE_APARTMENT_REVIEW_CATEGORY,
                    ],
                    [
                        'label' => '[Group]',
                        'permission' => [
                            Roles::ROLE_DISTRIBUTION_VIEW,
                            Roles::ROLE_APARTEL_INVENTORY,
                            Roles::ROLE_APARTMENT_GROUP_MANAGEMENT
                        ]
                    ],
                    [
						'label'      => 'Distribution',
						'controller' => 'distribution-view',
						'permission' => Roles::ROLE_DISTRIBUTION_VIEW,
					],
                    [
                        'label'      => 'Inventory',
                        'controller' => 'group-inventory',
                        'permission' => Roles::ROLE_APARTEL_INVENTORY,
                    ],
                    [
						'label'      => 'Management',
						'controller' => 'apartment-group',
						'permission' => Roles::ROLE_APARTMENT_GROUP_MANAGEMENT,
					],
                    [
                        'label' => '[Parking]',
                        'permission' => [
                            Roles::ROLE_PARKING_MANAGEMENT,
                            Roles::ROLE_PARKING_INVENTORY_MODULE,
                        ]
                    ],
                    [
                        'label'      => 'Lots',
                        'route' => 'parking_lots',
                        'controller' => 'lots',
                        'action' => 'index',
                        'permission' => Roles::ROLE_PARKING_MANAGEMENT,
                    ],
                    [
                        'label'      => 'Inventory',
                        'route' => 'parking_inventory',
                        'controller' => 'lots',
                        'action' => 'index',
                        'permission' => [
                            Roles::ROLE_PARKING_INVENTORY_MODULE,
                        ],
                    ],
                    [
                        'label' => '[Search]',
                        'permission' => [
                            Roles::ROLE_APARTMENT_MANAGEMENT,
                            Roles::ROLE_DOCUMENTS_MANAGEMENT
                        ]
                    ],
                    [
						'label'      => 'Apartments',
                        'route'      => 'apartments',
						'controller' => 'apartment',
						'action'     => 'search',
						'permission' => Roles::ROLE_APARTMENT_MANAGEMENT,
					],
                    [
						'label'      => 'Documents',
                        'route'      => 'documents',
						'controller' => 'controller_document',
						'action'     => 'search',
						'permission' => Roles::ROLE_DOCUMENTS_MANAGEMENT
					],
                    [
                        'label' => '[Statistics]',
                        'permission' => [
                            Roles::ROLE_APARTMENT_OCCUPANCY_STATISTICS,
                            Roles::ROLE_APARTMENT_SALES_STATISTICS
                        ]
                    ],
                    [
                        'label'      => 'Occupancy',
                        'route'      => 'occupancy_statistics',
                        'controller' => 'occupancy-statistics',
                        'action'     => 'statistics',
                        'permission' => Roles::ROLE_APARTMENT_OCCUPANCY_STATISTICS,
                    ],
                    [
                        'label'      => 'Sales',
                        'route'      => 'sales_statistics',
                        'controller' => 'sales-statistics',
                        'action'     => 'index',
                        'permission' => Roles::ROLE_APARTMENT_SALES_STATISTICS,
                    ],
                    [
                        'label'      => 'Review Management',
                        'route'      => 'reviews',
                        'permission' => Roles::ROLE_REVIEW_MANAGEMENT,
                    ],
				],
			]
		],
		'profile' => [
			[
				'label' => \Backoffice\View\Helper\Navigation::USERNAME,
				'pages' => [
					[
						'label'      => 'Profile',
						'controller' => 'profile',
						'permission' => Roles::ROLE_PROFILE,
					],
                    [
						'label'      => 'Request Time Off',
						'controller' => 'profile',
						'action'     => 'vacation-request',
						'permission' => Roles::ROLE_PROFILE,
					],
                    [
						'label'      => Backoffice\View\Helper\Navigation::SEPARATOR,
                        'permission' => Roles::ROLE_PROFILE,
					],
                    [
                        'label'      => 'Add Expense',
                        'route'		 => 'finance/item',
                        'permission' => [
                            Roles::ROLE_PO_ITEM_CREATOR,
                        ],
                    ],
                    [
                        'label'      => 'Add Contacts',
                        'route'		 => 'contacts/edit',
                        'permission' => [
                            Roles::ROLE_CONTACTS_MANAGEMENT,
                        ],
                    ],
                    [
						'label'      => 'Add Task',
						'controller' => 'task',
						'action'     => 'edit',
						'permission' => [
                            Roles::ROLE_GLOBAL_TASK_MANAGER,
                            Roles::ROLE_TASK_MANAGEMENT
                        ],
					],
                    [
                        'label'      => 'Add Order',
                        'route'		 => 'orders/add',
                        'permission' => [
                            Roles::ROLE_WH_CREATE_ORDER_FUNCTION,
                        ],
                    ],
                    [
                        'label'      => 'Lunchroom',
                        'route'      => 'lunchroom'
                    ],
                    [
                        'label'      => Backoffice\View\Helper\Navigation::SEPARATOR,
                        'permission' => [
                            Roles::ROLE_PO_ITEM_CREATOR,
                            Roles::ROLE_CONTACTS_MANAGEMENT,
                            Roles::ROLE_GLOBAL_TASK_MANAGER,
                            Roles::ROLE_TASK_MANAGEMENT,
                            Roles::ROLE_WH_CREATE_ORDER_FUNCTION
                        ],
                    ],
                    [
						'label'      => 'lampushka',
					],
                    [
						'label'      => Backoffice\View\Helper\Navigation::SEPARATOR,
					],
                    [
						'label'      => 'Logout',
						'controller' => 'authentication',
						'action'     => 'logout',
					],
				],
			],
		],

        'notifications' => [
            [
                'label' => 'notifications',
            ],
        ],
	],
];
