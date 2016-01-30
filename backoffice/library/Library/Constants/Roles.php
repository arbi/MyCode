<?php

namespace Library\Constants;

/**
 * Store string constants for roles
 * @category core
 * @package constants
 * @subpackage role_constants
 *
 * @author Tigran Petrosyan
 */
abstract class Roles
{
	const ROLE_NULL                              = 0;

	const ROLE_PEOPLE_MANAGEMENT 			     = 1;
	const ROLE_LOCATION_MANAGEMENT 			     = 2; // Gives access to Location Management

    const ROLE_FINANCE_BUDGET_HOLDER             = 3; // User can see and approve or decline the expence created by managers

	const ROLE_CURRENCY_MANAGEMENT			     = 5; // Gives access to Currency Management
	const ROLE_APARTMENT_MANAGEMENT 		     = 6; // Gives access to Apartment Management
	const ROLE_BOOKING_MANAGEMENT			     = 10; // Gives access to Booking Management Module
	const ROLE_CONFIG_MANAGEMENT			     = 11; // Gives access to System Configuration Module
	const ROLE_PARTNER_MANAGEMENT			     = 13; // Gives access to Partner Management
	const ROLE_CREDIT_CARD					     = 14; // Gives access to bookers credit card information in Booking Management Module
	const ROLE_NEWS_MANAGEMENT				     = 16; // Gives access to News Management
	const ROLE_BLOG_MANAGEMENT				     = 26; // Gives access to Blog Management Module
	const ROLE_RESERVATIONS					     = 28; // Gives permission to edit reservation affiliate on edit booking page
	const ROLE_EXPENSE_CREATOR			         = 35; // Gives permission to add new expenses
	const ROLE_EXPENSE_MANAGEMENT			     = 36; // Gives access to ""Expense Management"
	const ROLE_PO_ITEM_CREATOR 			         = 37; // Gives permission to add new purchase order item
	const ROLE_APARTMENT_INVENTORY_MANAGER 	     = 44; // Gives access to update price and availability
	const ROLE_APARTMENT_INVENTORY_READER        = 46; // Gives access to view information of "Inventory" and "Calendar" tabs
	const ROLE_GLOBAL_APARTMENT_GROUP_MANAGER    = 49; // Gives the ability to create Apartment Groups
	const ROLE_CONCIERGE_DASHBOARD			     = 50; // Select Product Groups which will be available for this user. In Frontier Cards search will be available only for apartments from these groups.
	const ROLE_PROFILE						     = 52; // Allows access to manage the logged in users profile and to see the profiles of others
	const ROLE_HOUSEKEEPING					     = 53; // Gives access to housekeepers to view the jobs which are assigned to them. Gives Access to GEMS to view and assign jobs in their groups
	const ROLE_GLOBAL_HOUSEKEEPING_MANAGER       = 54; // Gives access to see the Housekeeping Module and do any actions on it
	const ROLE_PEOPLE_DIRECTORY 			     = 56; // Gives access to see the list of employees
	const ROLE_APARTMENT_GROUP_MANAGEMENT	     = 57; // Apartment Group Management (Module)
	const ROLE_EXPENSE_UNLOCKER				     = 58; // Allows for editing of already approved expenses
	const ROLE_GLOBAL_TASK_MANAGER			     = 62; // The Task Manager Global can view ALL tasks in the system and make ANY changes to them. Normally, anybody with ""Profile"" permission module can only see tasks they are invovled with
	const ROLE_TASK_MANAGEMENT				     = 63; // Gives access to Task Management Module and ability to add new tasks
    const ROLE_APARTEL_INVENTORY			     = 64; // Gives access to the Apartel Inventory under the Product Menu

    const ROLE_CONTENT_EDITOR_PRODUCT 		     = 65;
	const ROLE_CONTENT_EDITOR_TEXTLINE 		     = 66;
	const ROLE_CONTENT_EDITOR_LOCATION 		     = 67;

	const ROLE_RESERVATION_FINANCE			     = 69;
	const ROLE_ACCOUNTS_RECEVEIBLE			     = 70;
	const ROLE_BILLPAY						     = 73;
	const ROLE_PROFILE_VIEWER				     = 74;
	const ROLE_APARTMENT_CONNECTION			     = 75;
	const ROLE_MONEY_ACCOUNT 			         = 76; // Gives access to manage those money accounts which person privileged to manage.
	const ROLE_MONEY_ACCOUNT_CREATOR		     = 77; // Gives access to create new money accounts.
	const ROLE_MONEY_ACCOUNT_GLOBAL_MANAGER      = 78; // Gives access to manage all money accounts.
	const ROLE_DEVELOPMENT_TESTING               = 79; // Gives you access to test products and test reservations, mainly for R&D team members to test
	const ROLE_PSP                               = 80; // Access to PSP Management (Module)
	const ROLE_DISTRIBUTION_VIEW                 = 81; // Access to PSP Management (Module)
	const ROLE_APARTMENT_REVIEW_CATEGORY         = 82; // Access to Apartment Review Category (Module)
    const ROLE_APARTMENT_PERFORMANCE             = 83; // Apartment Performance Monitor (Role).
    const ROLE_FRONTIER_CHARGE                   = 84; // Concierge Point of Sale (Role)
    const ROLE_BOOKING_TRANSACTION_VERIFIER      = 85; // Booking Transaction Verifier (Role)
    const ROLE_PEOPLE_MANAGEMENT_PERMISSIONS     = 86;
    const ROLE_PEOPLE_MANAGEMENT_HR              = 87; // Gives access to the Personal, Administration, Evaluation, Documents tabs for ALL users (Role)
    const ROLE_SUPPLIERS_MANAGEMENT              = 88; // Suppliers management role
    const ROLE_TEAM                              = 89; // Access to Team Management(Module);
    const ROLE_TEAM_MANAGER                      = 90; // Manage Team Management(Role);
    const ROLE_OFFICE                            = 91; // Access to Team Management(Module);
    const ROLE_OFFICE_MANAGER                    = 92; // Manage Team Management(Role);
    const ROLE_CONCIERGE_CURRENT_STAYS           = 93; // Concierge Dashboard Current Stays view (Role)

    const ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL    = 94; // Can create full featured expense

    const ROLE_BETA_TESTER                       = 95; // Beta testers basically for NEW finance module (now)
    const ROLE_PO_APPROVER                       = 96; // Gives access to approve or reject purchase orders
    const ROLE_EXPENSE_CATEGORY_MANAGEMENT	     = 97; // Gives access to ( Add / Edit / Active / Deactivated ) expense categories.

    const ROLE_JOB_MANAGEMENT                    = 98; // Gives access to manage open positions which are its hiring manager (Add / Edit / Delete).
    const ROLE_APPLICANT_MANAGEMENT              = 99; // Gives access to manage applicants which are its hiring manager (Add / Edit / Delete).
    const ROLE_HIRING_COUNTRY_MANAGER            = 100; //Gives full access to manage open positions and applicants which are on your country (Add / Edit / Delete).
    const ROLE_HIRING_MANAGER                    = 101; //Gives full access to manage open positions and applicants(Add / Edit / Delete).

    const ROLE_APARTMENT_AVAILABILITY_MONITOR    = 102; //Gives access to see apartment availability in notification.
    const ROLE_FINANCE_ACCOUNT_RECEIVABLE        = 103;
    const ROLE_FRONTIER_MANAGEMENT 		         = 104;// Gives access to Frontier Management Module

    const ROLE_APARTMENT_COSTS_READER            = 105; // Gives access to view information under "Costs" tab in Apartment Management
    const ROLE_APARTMENT_OCCUPANCY_STATISTICS    = 106; // Gives access to view statistics about the occupancies of apartments.
    const ROLE_APARTMENT_SALES_STATISTICS        = 107; // Gives access to view statistics about the sales of apartments.
    const ROLE_DOCUMENTS_MANAGEMENT 		     = 108; // Gives access to all documents based on security team
    const ROLE_PARTNER_DISCOUNTS                 = 109; // Gives access to edit the discount number of partners (Role).
    const ROLE_DOCUMENTS_MANAGEMENT_GLOBAL       = 110; // Documents Global (Role) - This role gives access to ALL documents in AMM. Very few people should have this role as document security is controlled team-based.
    const ROLE_LEGAL_ENTITIES_MANAGEMENT         = 111; // Legal Entities management role
    const ROLE_GLOBAL_EVALUATION_MANAGER         = 112; // Gives access to view evaluations of everyone. People with this role will receive notifications about every single evaluation throughout company.
	const ROLE_LOCK_MANAGEMENT                   = 113; // Gives access to manage Locks Module
	const ROLE_PARKING_MANAGEMENT                = 114; // Gives access to Parking Management Module
	const ROLE_PO_SETTLER                        = 117;
	const ROLE_PEOPLE_SCHEDULE_VIEWER            = 118; // Gives view access to people schedule module
	const ROLE_PEOPLE_SCHEDULE_EDITOR            = 119; // Gives edit access to people schedule module

	const ROLE_CONTACTS_MANAGEMENT               = 120;
	const ROLE_CONTACTS_GLOBAL_MANAGER           = 122;

	const ROLE_TAG_MANAGEMENT                    = 123; //Gives access to tag management module
	const ROLE_PARKING_INVENTORY_MODULE          = 124; //Gives access to parking inventory page to view info for ONLY access-able lots
	const ROLE_NO_TRACK_AVAILABILITY_CHANGES     = 125; //The availability notifications of users with this roles will be ignored and NOT sent to the [Apartment Availability Monitor] team
	const ROLE_ASSET_MANAGEMENT_CATEGORY         = 126; //Asset Management Category
	const ROLE_WAREHOUSE_MANAGEMENT              = 127; //Warehouse Management
	const ROLE_OFFICE_COST_VIEWER                = 128; // Gives access to the Office Management's Costs Tab

    const ROLE_BUDGET_MANAGEMENT_MODULE          = 129; // Access to the budget management
    const ROLE_BUDGET_MANAGER_GLOBAL             = 130; // Access to all budgets with read/write/deactivate rights

	const ROLE_WH_ORDER_MANAGEMENT               = 131; // Gives full access to view orders list
	const ROLE_WH_ORDER_MANAGEMENT_GLOBAL        = 132; // Gives full access to Order Management

	const ROLE_ASSET_MANAGEMENT                  = 133; // Have access to view consumable and valuable assets in view only mode
	const ROLE_ASSET_MANAGEMENT_GLOBAL           = 134; // Have access to view assets and edit assets
	const ROLE_WH_CREATE_ORDER_FUNCTION          = 135; // Gives access to create new Order with simple view from profile menu

	const ROLE_MOBILE_APPLICATION			     = 136; // Has permission to login to mobile application
	const ROLE_MOBILE_INCIDENT_REPORT			 = 137; // Can report incident report via mobile
	const ROLE_MOBILE_ASSET_MANAGER			     = 138; // Can check-in check-out asset via mobile

	const ROLE_VENUE_MANAGEMENT					 = 139; // Gives access to manage Venue Module
	const ROLE_VENUE_MANAGER					 = 149; // Gives access to manage Venue Module
	const ROLE_VENUE_CHARGE_MANAGER				 = 150; // Gives access to manage Venue Module

    const ROLE_REVIEW_MANAGEMENT				 = 140; // Gives access to manage Venue Module

    const ROLE_PEOPLE_SALARY_MANAGEMENT			 = 141; // Gives access to Salary Module

    const ROLE_PO_ITEM_MANAGEMENT    			 = 142; // Gives access to search PO Items

	const ROLE_PEOPLE_SALARY_MANAGEMENT_EDITOR	 = 148; // Gives access to manage User Salary Module

	const ROLE_ESPM_MODULE                       = 143; // Gives access to actual module (View Only)
	const ROLE_ESPM_PAYMENT_MANAGER              = 144; // Allows full access to everything
	const ROLE_ESPM_PAYMENT_PAYER                = 145; // Can change statuses of NON archived payments
	const ROLE_ESPM_PAYMENT_ADDER                = 146; // Can add payments
    const ROLE_UNIVERSAL_TEXTLINE_CREATOR 		 = 151; // Gives ability to create universal textlines
    const ROLE_HR_VACATION_EDITOR 		         = 152; // Gives ability to cancel and add vacations for employees
    const ROLE_PEOPLE_DIGITAL_ID_MANAGEMENT      = 153; // Allows to see and manage user devices

}
