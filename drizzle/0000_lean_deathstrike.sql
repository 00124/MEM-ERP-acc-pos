-- Current sql file was generated after introspecting the database
-- If you want to run this migration please uncomment this code before executing migrations
/*
CREATE TABLE `appreciations` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`award_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_id` bigint(20) unsigned DEFAULT 'NULL',
	`created_by` bigint(20) unsigned DEFAULT 'NULL',
	`date` datetime NOT NULL,
	`price_amount` double DEFAULT 'NULL',
	`price_given` text DEFAULT 'NULL',
	`description` text NOT NULL,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `attendances` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`date` date DEFAULT 'NULL',
	`is_holiday` tinyint(1) NOT NULL DEFAULT 0,
	`is_leave` tinyint(1) NOT NULL DEFAULT 0,
	`user_id` bigint(20) unsigned NOT NULL,
	`leave_id` bigint(20) unsigned DEFAULT 'NULL',
	`leave_type_id` bigint(20) unsigned DEFAULT 'NULL',
	`holiday_id` bigint(20) unsigned DEFAULT 'NULL',
	`clock_in_date_time` datetime DEFAULT 'NULL',
	`clock_out_date_time` datetime DEFAULT 'NULL',
	`clock_in_ip_address` varchar(20) DEFAULT 'NULL',
	`total_duration` int(11) DEFAULT 'NULL',
	`clock_out_ip_address` varchar(20) DEFAULT 'NULL',
	`clock_in_time` time DEFAULT 'NULL',
	`clock_out_time` time DEFAULT 'NULL',
	`office_clock_in_time` time DEFAULT 'NULL',
	`office_clock_out_time` time DEFAULT 'NULL',
	`is_half_day` tinyint(1) NOT NULL DEFAULT 0,
	`is_late` tinyint(1) NOT NULL DEFAULT 0,
	`is_paid` tinyint(1) NOT NULL DEFAULT 0,
	`status` varchar(191) NOT NULL DEFAULT '''present''',
	`reason` text DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `awards` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`created_by` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`active` tinyint(1) NOT NULL DEFAULT 1,
	`award_price` double DEFAULT 'NULL',
	`description` text NOT NULL,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `basic_salaries` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_id` bigint(20) unsigned NOT NULL,
	`basic_salary` double DEFAULT 0,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `brands` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`slug` varchar(191) NOT NULL,
	`image` varchar(191) DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `categories` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`slug` varchar(191) NOT NULL,
	`image` varchar(191) DEFAULT 'NULL',
	`parent_id` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `companies` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`name` varchar(191) NOT NULL,
	`short_name` varchar(191) DEFAULT 'NULL',
	`email` varchar(191) DEFAULT 'NULL',
	`phone` varchar(191) DEFAULT 'NULL',
	`website` varchar(191) DEFAULT 'NULL',
	`light_logo` varchar(191) DEFAULT 'NULL',
	`dark_logo` varchar(191) DEFAULT 'NULL',
	`small_dark_logo` varchar(191) DEFAULT 'NULL',
	`small_light_logo` varchar(191) DEFAULT 'NULL',
	`address` varchar(1000) DEFAULT 'NULL',
	`app_layout` varchar(10) NOT NULL DEFAULT '''sidebar''',
	`rtl` tinyint(1) NOT NULL DEFAULT 0,
	`mysqldump_command` varchar(191) NOT NULL DEFAULT '''/usr/bin/mysqldump''',
	`shortcut_menus` varchar(20) NOT NULL DEFAULT '''top_bottom''',
	`currency_id` bigint(20) unsigned DEFAULT 'NULL',
	`lang_id` bigint(20) unsigned DEFAULT 'NULL',
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`left_sidebar_theme` varchar(20) NOT NULL DEFAULT '''dark''',
	`primary_color` varchar(20) NOT NULL DEFAULT '''#1890ff''',
	`date_format` varchar(20) NOT NULL DEFAULT '''DD-MM-YYYY''',
	`time_format` varchar(20) NOT NULL DEFAULT '''hh:mm a''',
	`auto_detect_timezone` tinyint(1) NOT NULL DEFAULT 1,
	`timezone` varchar(191) NOT NULL DEFAULT '''Asia/Kolkata''',
	`session_driver` varchar(20) NOT NULL DEFAULT '''file''',
	`app_debug` tinyint(1) NOT NULL DEFAULT 0,
	`update_app_notification` tinyint(1) NOT NULL DEFAULT 1,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL',
	`login_image` varchar(191) DEFAULT 'NULL',
	`stripe_id` varchar(191) DEFAULT 'NULL',
	`card_brand` varchar(191) DEFAULT 'NULL',
	`card_last_four` varchar(4) DEFAULT 'NULL',
	`trial_ends_at` timestamp DEFAULT 'NULL',
	`subscription_plan_id` bigint(20) unsigned DEFAULT 'NULL',
	`package_type` enum('monthly','annual') NOT NULL DEFAULT '''monthly''',
	`licence_expire_on` date DEFAULT 'NULL',
	`is_global` tinyint(1) NOT NULL DEFAULT 0,
	`admin_id` bigint(20) unsigned DEFAULT 'NULL',
	`status` varchar(191) NOT NULL DEFAULT '''active''',
	`total_users` int(11) NOT NULL DEFAULT 1,
	`email_verification_code` varchar(191) DEFAULT 'NULL',
	`verified` tinyint(1) NOT NULL DEFAULT 0,
	`white_label_completed` tinyint(1) NOT NULL DEFAULT 0,
	`clock_in_time` time DEFAULT '''09:30:00''',
	`clock_out_time` time DEFAULT '''18:00:00''',
	`leave_start_month` varchar(2) NOT NULL DEFAULT '''01''',
	`late_mark_after` int(11) DEFAULT 'NULL',
	`early_clock_in_time` int(11) DEFAULT 'NULL',
	`allow_clock_out_till` int(11) DEFAULT 'NULL',
	`self_clocking` tinyint(1) NOT NULL DEFAULT 1,
	`allowed_ip_address` text DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `currencies` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`code` varchar(191) NOT NULL,
	`symbol` varchar(191) NOT NULL,
	`position` varchar(191) NOT NULL,
	`is_deletable` tinyint(1) NOT NULL DEFAULT 1,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL',
	`decimal_separator` varchar(20) NOT NULL DEFAULT '''dot''',
	`thousand_separator` varchar(20) NOT NULL DEFAULT '''comma''',
	`remove_decimal_with_zero` tinyint(1) NOT NULL DEFAULT 1,
	`space_between_price_and_price_symbol` tinyint(1) NOT NULL DEFAULT 0
);
--> statement-breakpoint
CREATE TABLE `custom_fields` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`value` varchar(191) DEFAULT 'NULL',
	`type` varchar(191) NOT NULL DEFAULT '''text''',
	`active` tinyint(1) NOT NULL DEFAULT 0
);
--> statement-breakpoint
CREATE TABLE `departments` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`created_by` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `designations` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`created_by` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `expenses` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`bill` varchar(191) DEFAULT 'NULL',
	`expense_category_id` bigint(20) unsigned DEFAULT 'NULL',
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`amount` double(8,2) NOT NULL,
	`user_id` bigint(20) unsigned DEFAULT 'NULL',
	`notes` varchar(1000) DEFAULT 'NULL',
	`date` datetime NOT NULL,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `expense_categories` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`description` varchar(1000) DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `failed_jobs` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`uuid` varchar(191) NOT NULL,
	`connection` text NOT NULL,
	`queue` text NOT NULL,
	`payload` longtext NOT NULL,
	`exception` longtext NOT NULL,
	`failed_at` timestamp NOT NULL DEFAULT 'current_timestamp()',
	CONSTRAINT `failed_jobs_uuid_unique` UNIQUE(`uuid`)
);
--> statement-breakpoint
CREATE TABLE `front_product_cards` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`title` varchar(191) NOT NULL,
	`subtitle` varchar(191) DEFAULT 'NULL',
	`products` text NOT NULL,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `front_website_settings` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`featured_categories` text NOT NULL,
	`featured_categories_title` varchar(191) DEFAULT '''Featured Categories''',
	`featured_categories_subtitle` varchar(191) DEFAULT '''''',
	`featured_products` text NOT NULL,
	`featured_products_title` varchar(191) DEFAULT '''Featured Products''',
	`featured_products_subtitle` varchar(191) DEFAULT '''''',
	`features_lists` text NOT NULL,
	`facebook_url` varchar(191) DEFAULT '''''',
	`twitter_url` varchar(191) DEFAULT '''''',
	`instagram_url` varchar(191) DEFAULT '''''',
	`linkedin_url` varchar(191) DEFAULT '''''',
	`youtube_url` varchar(191) DEFAULT '''''',
	`pages_widget` text NOT NULL,
	`contact_info_widget` text NOT NULL,
	`links_widget` text NOT NULL,
	`footer_company_description` varchar(1000) NOT NULL DEFAULT '''Stockify have many propular products wiht high discount and special offers.''',
	`footer_copyright_text` varchar(1000) NOT NULL DEFAULT '''Copyright 2021 @ Stockify, All rights reserved.''',
	`top_banners` text NOT NULL,
	`bottom_banners_1` text NOT NULL,
	`bottom_banners_2` text NOT NULL,
	`bottom_banners_3` text NOT NULL,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `holidays` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`year` int(11) NOT NULL,
	`month` int(11) NOT NULL,
	`date` date NOT NULL,
	`is_weekend` tinyint(1) NOT NULL DEFAULT 0,
	`created_by` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `increments_promotions` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_id` bigint(20) unsigned DEFAULT 'NULL',
	`type` varchar(191) NOT NULL DEFAULT '''promotion''',
	`date` date NOT NULL,
	`description` text NOT NULL,
	`net_salary` int(11) DEFAULT 'NULL',
	`promoted_designation_id` bigint(20) unsigned DEFAULT 'NULL',
	`current_designation_id` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `jobs` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`queue` varchar(191) NOT NULL,
	`payload` longtext NOT NULL,
	`attempts` tinyint(3) unsigned NOT NULL,
	`reserved_at` int(10) unsigned DEFAULT 'NULL',
	`available_at` int(10) unsigned NOT NULL,
	`created_at` int(10) unsigned NOT NULL
);
--> statement-breakpoint
CREATE TABLE `langs` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`image` varchar(191) DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`key` varchar(191) NOT NULL,
	`enabled` tinyint(1) NOT NULL DEFAULT 1,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `leaves` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_id` bigint(20) unsigned NOT NULL,
	`leave_type_id` bigint(20) unsigned NOT NULL,
	`start_date` date NOT NULL,
	`end_date` date DEFAULT 'NULL',
	`total_days` int(11) NOT NULL DEFAULT 0,
	`is_half_day` tinyint(1) NOT NULL DEFAULT 0,
	`reason` text NOT NULL,
	`is_paid` tinyint(1) NOT NULL DEFAULT 0,
	`status` varchar(20) NOT NULL DEFAULT '''pending''',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `leave_types` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`is_paid` tinyint(1) NOT NULL DEFAULT 0,
	`total_leaves` int(11) NOT NULL,
	`max_leaves_per_month` int(11) DEFAULT 'NULL',
	`created_by` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `migrations` (
	`id` int(10) unsigned AUTO_INCREMENT NOT NULL,
	`migration` varchar(191) NOT NULL,
	`batch` int(11) NOT NULL
);
--> statement-breakpoint
CREATE TABLE `notifications` (
	`id` char(36) NOT NULL,
	`type` varchar(191) NOT NULL,
	`notifiable_type` varchar(191) NOT NULL,
	`notifiable_id` bigint(20) unsigned NOT NULL,
	`data` text NOT NULL,
	`read_at` timestamp DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `orders` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`unique_id` varchar(20) NOT NULL,
	`invoice_number` varchar(20) NOT NULL,
	`invoice_type` varchar(20) DEFAULT 'NULL',
	`order_type` varchar(20) NOT NULL DEFAULT '''sales''',
	`parent_order_id` bigint(20) unsigned DEFAULT 'NULL',
	`order_date` datetime NOT NULL,
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`from_warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_id` bigint(20) unsigned DEFAULT 'NULL',
	`tax_id` bigint(20) unsigned DEFAULT 'NULL',
	`tax_rate` double(8,2) DEFAULT 'NULL',
	`tax_amount` double NOT NULL DEFAULT 0,
	`discount` double DEFAULT 'NULL',
	`shipping` double DEFAULT 'NULL',
	`subtotal` double NOT NULL,
	`total` double NOT NULL,
	`paid_amount` double NOT NULL DEFAULT 0,
	`due_amount` double NOT NULL DEFAULT 0,
	`order_status` varchar(20) NOT NULL,
	`notes` text DEFAULT 'NULL',
	`supplier_invoice_number` varchar(100) DEFAULT 'NULL',
	`delivery_challan_no` varchar(100) DEFAULT 'NULL',
	`received_by_name` varchar(100) DEFAULT 'NULL',
	`received_by_signature` varchar(255) DEFAULT 'NULL',
	`received_by_date` date DEFAULT 'NULL',
	`checked_by_name` varchar(100) DEFAULT 'NULL',
	`checked_by_signature` varchar(255) DEFAULT 'NULL',
	`checked_by_date` date DEFAULT 'NULL',
	`approved_by_name` varchar(100) DEFAULT 'NULL',
	`approved_by_signature` varchar(255) DEFAULT 'NULL',
	`approved_by_date` date DEFAULT 'NULL',
	`document` varchar(191) DEFAULT 'NULL',
	`staff_user_id` bigint(20) unsigned DEFAULT 'NULL',
	`payment_status` varchar(20) NOT NULL DEFAULT '''unpaid''',
	`total_items` double(8,2) NOT NULL DEFAULT 0,
	`total_quantity` double(8,2) NOT NULL DEFAULT 0,
	`terms_condition` text DEFAULT 'NULL',
	`is_deletable` tinyint(1) NOT NULL DEFAULT 1,
	`cancelled` tinyint(1) NOT NULL DEFAULT 0,
	`cancelled_by` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `order_custom_fields` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`order_id` bigint(20) unsigned NOT NULL,
	`field_name` varchar(191) NOT NULL,
	`field_value` varchar(191) DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `order_items` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`user_id` bigint(20) unsigned DEFAULT 'NULL',
	`order_id` bigint(20) unsigned NOT NULL,
	`product_id` bigint(20) unsigned NOT NULL,
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`unit_id` bigint(20) unsigned DEFAULT 'NULL',
	`quantity` double(8,2) NOT NULL,
	`received_quantity` double DEFAULT 'NULL',
	`short_damaged_quantity` double DEFAULT 'NULL',
	`mrp` double DEFAULT 'NULL',
	`unit_price` double NOT NULL,
	`single_unit_price` double NOT NULL,
	`tax_id` bigint(20) unsigned DEFAULT 'NULL',
	`tax_rate` double(8,2) NOT NULL DEFAULT 0,
	`tax_type` varchar(10) DEFAULT 'NULL',
	`discount_rate` double(8,2) DEFAULT 'NULL',
	`total_tax` double DEFAULT 'NULL',
	`total_discount` double DEFAULT 'NULL',
	`subtotal` double NOT NULL,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `order_item_taxes` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`tax_name` varchar(191) NOT NULL,
	`tax_type` varchar(20) NOT NULL,
	`tax_amount` double NOT NULL,
	`tax_rate` double(8,2) NOT NULL,
	`order_id` bigint(20) unsigned NOT NULL,
	`order_item_id` bigint(20) unsigned NOT NULL,
	`tax_id` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `order_payments` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`payment_id` bigint(20) unsigned NOT NULL,
	`order_id` bigint(20) unsigned DEFAULT 'NULL',
	`amount` double NOT NULL DEFAULT 0,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `order_shipping_address` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`order_id` bigint(20) unsigned NOT NULL,
	`name` varchar(191) NOT NULL,
	`email` varchar(191) NOT NULL,
	`phone` varchar(191) NOT NULL,
	`address` varchar(1000) DEFAULT 'NULL',
	`shipping_address` varchar(1000) DEFAULT 'NULL',
	`city` varchar(50) DEFAULT 'NULL',
	`state` varchar(50) DEFAULT 'NULL',
	`country` varchar(50) DEFAULT 'NULL',
	`zipcode` varchar(50) DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `payments` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`payment_type` varchar(20) NOT NULL DEFAULT '''out''',
	`payment_number` varchar(191) DEFAULT 'NULL',
	`date` datetime NOT NULL,
	`amount` double NOT NULL DEFAULT 0,
	`unused_amount` double NOT NULL DEFAULT 0,
	`paid_amount` double NOT NULL DEFAULT 0,
	`payment_mode_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_id` bigint(20) unsigned DEFAULT 'NULL',
	`payment_receipt` varchar(191) DEFAULT 'NULL',
	`notes` text DEFAULT 'NULL',
	`staff_user_id` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `payment_modes` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`mode_type` varchar(191) DEFAULT '''bank''',
	`credentials` text DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `payrolls` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_id` bigint(20) unsigned DEFAULT 'NULL',
	`month` int(11) NOT NULL,
	`year` int(11) NOT NULL,
	`basic_salary` double NOT NULL,
	`salary_amount` double NOT NULL,
	`pre_payment_amount` double NOT NULL DEFAULT 0,
	`expense_amount` double NOT NULL DEFAULT 0,
	`net_salary` double NOT NULL,
	`total_days` double(8,2) NOT NULL,
	`working_days` double(8,2) NOT NULL,
	`present_days` double(8,2) NOT NULL,
	`total_office_time` int(11) NOT NULL,
	`total_worked_time` int(11) NOT NULL,
	`half_days` int(11) NOT NULL,
	`late_days` double(8,2) NOT NULL,
	`paid_leaves` double(8,2) NOT NULL,
	`unpaid_leaves` double(8,2) NOT NULL,
	`holiday_count` double(8,2) NOT NULL,
	`payment_date` date DEFAULT 'NULL',
	`status` varchar(191) NOT NULL DEFAULT '''generated''',
	`created_by` bigint(20) unsigned DEFAULT 'NULL',
	`updated_by` bigint(20) unsigned DEFAULT 'NULL',
	`payment_mode_id` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `payroll_components` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_id` bigint(20) unsigned DEFAULT 'NULL',
	`payroll_id` bigint(20) unsigned DEFAULT 'NULL',
	`pre_payment_id` bigint(20) unsigned DEFAULT 'NULL',
	`expense_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`amount` double NOT NULL,
	`is_earning` tinyint(1) NOT NULL DEFAULT 1,
	`type` varchar(20) NOT NULL DEFAULT '''pre_payments''',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `permissions` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`name` varchar(191) NOT NULL,
	`display_name` varchar(191) DEFAULT 'NULL',
	`description` varchar(191) DEFAULT 'NULL',
	`module_name` varchar(191) DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `permission_role` (
	`permission_id` bigint(20) unsigned NOT NULL,
	`role_id` bigint(20) unsigned NOT NULL
);
--> statement-breakpoint
CREATE TABLE `pre_payments` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_id` bigint(20) unsigned NOT NULL,
	`payment_mode_id` bigint(20) unsigned NOT NULL,
	`amount` double NOT NULL,
	`date_time` datetime NOT NULL,
	`deduct_from_payroll` tinyint(1) NOT NULL DEFAULT 1,
	`payroll_month` int(11) NOT NULL,
	`payroll_year` int(11) NOT NULL,
	`notes` longtext NOT NULL,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `products` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`product_type` varchar(10) NOT NULL DEFAULT '''single''',
	`parent_id` bigint(20) unsigned DEFAULT 'NULL',
	`parent_item_code` varchar(191) DEFAULT 'NULL',
	`name` varchar(1000) NOT NULL,
	`slug` varchar(1000) NOT NULL,
	`barcode_symbology` varchar(10) NOT NULL,
	`item_code` varchar(191) NOT NULL,
	`image` varchar(191) DEFAULT 'NULL',
	`category_id` bigint(20) unsigned DEFAULT 'NULL',
	`brand_id` bigint(20) unsigned DEFAULT 'NULL',
	`unit_id` bigint(20) unsigned DEFAULT 'NULL',
	`description` text DEFAULT 'NULL',
	`user_id` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `product_custom_fields` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`product_id` bigint(20) unsigned NOT NULL,
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`field_name` varchar(191) NOT NULL,
	`field_value` varchar(191) DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `product_details` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`product_id` bigint(20) unsigned DEFAULT 'NULL',
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`current_stock` double(8,2) NOT NULL DEFAULT 0,
	`mrp` double DEFAULT 'NULL',
	`purchase_price` double NOT NULL,
	`sales_price` double NOT NULL,
	`tax_id` bigint(20) unsigned DEFAULT 'NULL',
	`purchase_tax_type` varchar(10) DEFAULT '''exclusive''',
	`sales_tax_type` varchar(10) DEFAULT '''exclusive''',
	`stock_quantitiy_alert` int(11) DEFAULT 'NULL',
	`opening_stock` int(11) DEFAULT 'NULL',
	`opening_stock_date` date DEFAULT 'NULL',
	`wholesale_price` double DEFAULT 'NULL',
	`wholesale_quantity` int(11) DEFAULT 'NULL',
	`status` varchar(191) NOT NULL DEFAULT '''in_stock''',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `product_variants` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`product_id` bigint(20) unsigned NOT NULL,
	`variant_id` bigint(20) unsigned DEFAULT 'NULL',
	`variant_value_id` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `roles` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`display_name` varchar(191) DEFAULT 'NULL',
	`description` varchar(191) DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `role_user` (
	`user_id` bigint(20) unsigned NOT NULL,
	`role_id` bigint(20) unsigned NOT NULL
);
--> statement-breakpoint
CREATE TABLE `settings` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`is_global` tinyint(1) NOT NULL DEFAULT 0,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`setting_type` varchar(191) NOT NULL,
	`name` varchar(191) NOT NULL,
	`name_key` varchar(191) NOT NULL,
	`credentials` text DEFAULT 'NULL',
	`other_data` text DEFAULT 'NULL',
	`status` tinyint(1) NOT NULL DEFAULT 0,
	`verified` tinyint(1) NOT NULL DEFAULT 0,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `shifts` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`clock_in_time` time NOT NULL,
	`clock_out_time` time NOT NULL,
	`late_mark_after` int(11) DEFAULT 'NULL',
	`early_clock_in_time` int(11) DEFAULT 'NULL',
	`allow_clock_out_till` int(11) DEFAULT 'NULL',
	`self_clocking` tinyint(1) NOT NULL DEFAULT 1,
	`allowed_ip_address` varchar(1000) DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `stock_adjustments` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`warehouse_id` bigint(20) unsigned NOT NULL,
	`product_id` bigint(20) unsigned NOT NULL,
	`quantity` double(8,2) NOT NULL,
	`adjustment_type` varchar(20) NOT NULL DEFAULT '''add''',
	`notes` text DEFAULT 'NULL',
	`created_by` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `stock_history` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`warehouse_id` bigint(20) unsigned NOT NULL,
	`product_id` bigint(20) unsigned NOT NULL,
	`quantity` double(8,2) NOT NULL,
	`old_quantity` double(8,2) NOT NULL DEFAULT 0,
	`order_type` varchar(20) DEFAULT '''sales''',
	`stock_type` varchar(20) NOT NULL DEFAULT '''in''',
	`action_type` varchar(20) NOT NULL DEFAULT '''add''',
	`created_by` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `subscription_plans` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`name` varchar(255) NOT NULL,
	`description` varchar(1000) DEFAULT 'NULL',
	`annual_price` double NOT NULL DEFAULT 0,
	`monthly_price` double NOT NULL DEFAULT 0,
	`max_products` int(10) unsigned NOT NULL DEFAULT 0,
	`modules` text DEFAULT 'NULL',
	`default` varchar(20) NOT NULL DEFAULT '''no''',
	`is_popular` tinyint(1) NOT NULL DEFAULT 0,
	`is_private` tinyint(1) NOT NULL DEFAULT 0,
	`billing_cycle` tinyint(4) DEFAULT 'NULL',
	`stripe_monthly_plan_id` varchar(191) DEFAULT 'NULL',
	`stripe_annual_plan_id` varchar(191) DEFAULT 'NULL',
	`razorpay_monthly_plan_id` varchar(191) DEFAULT 'NULL',
	`razorpay_annual_plan_id` varchar(191) DEFAULT 'NULL',
	`paystack_monthly_plan_id` varchar(191) DEFAULT 'NULL',
	`paystack_annual_plan_id` varchar(191) DEFAULT 'NULL',
	`active` tinyint(1) NOT NULL DEFAULT 1,
	`duration` int(11) DEFAULT 30,
	`notify_before` int(11) DEFAULT 'NULL',
	`position` smallint(6) DEFAULT 'NULL',
	`features` text DEFAULT 'NULL',
	`currency_code` varchar(191) DEFAULT '''USD''',
	`currency_symbol` varchar(191) DEFAULT '''$''',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `taxes` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`parent_id` bigint(20) unsigned DEFAULT 'NULL',
	`tax_type` varchar(20) DEFAULT '''single''',
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`rate` double(8,2) NOT NULL,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `translations` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`lang_id` bigint(20) unsigned DEFAULT 'NULL',
	`group` varchar(191) NOT NULL,
	`key` text NOT NULL,
	`value` text DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `units` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`short_name` varchar(191) NOT NULL,
	`base_unit` varchar(191) DEFAULT 'NULL',
	`parent_id` bigint(20) unsigned DEFAULT 'NULL',
	`operator` varchar(191) NOT NULL,
	`operator_value` varchar(191) NOT NULL,
	`is_deletable` tinyint(1) NOT NULL DEFAULT 1,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `users` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`is_superadmin` tinyint(1) NOT NULL DEFAULT 0,
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`role_id` bigint(20) unsigned DEFAULT 'NULL',
	`lang_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_type` varchar(191) NOT NULL DEFAULT '''customers''',
	`is_walkin_customer` tinyint(1) NOT NULL DEFAULT 0,
	`login_enabled` tinyint(1) NOT NULL DEFAULT 1,
	`name` varchar(191) NOT NULL,
	`email` varchar(191) DEFAULT 'NULL',
	`password` varchar(191) DEFAULT 'NULL',
	`phone` varchar(191) DEFAULT 'NULL',
	`profile_image` varchar(191) DEFAULT 'NULL',
	`address` varchar(1000) DEFAULT 'NULL',
	`shipping_address` varchar(1000) DEFAULT 'NULL',
	`email_verification_code` varchar(50) DEFAULT 'NULL',
	`status` varchar(191) NOT NULL DEFAULT '''enabled''',
	`reset_code` varchar(191) DEFAULT 'NULL',
	`timezone` varchar(50) NOT NULL DEFAULT '''Asia/Kolkata''',
	`date_format` varchar(20) NOT NULL DEFAULT '''d-m-Y''',
	`date_picker_format` varchar(20) NOT NULL DEFAULT '''dd-mm-yyyy''',
	`time_format` varchar(20) NOT NULL DEFAULT '''h:i a''',
	`tax_number` varchar(191) DEFAULT 'NULL',
	`created_by` bigint(20) unsigned DEFAULT 'NULL',
	`department_id` bigint(20) unsigned DEFAULT 'NULL',
	`designation_id` bigint(20) unsigned DEFAULT 'NULL',
	`shift_id` bigint(20) unsigned DEFAULT 'NULL',
	`reset_password_token` varchar(191) DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `user_address` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_id` bigint(20) unsigned NOT NULL,
	`name` varchar(191) NOT NULL,
	`email` varchar(191) NOT NULL,
	`phone` varchar(191) NOT NULL,
	`address` varchar(1000) DEFAULT 'NULL',
	`shipping_address` varchar(1000) DEFAULT 'NULL',
	`city` varchar(50) DEFAULT 'NULL',
	`state` varchar(50) DEFAULT 'NULL',
	`country` varchar(50) DEFAULT 'NULL',
	`zipcode` varchar(50) DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `user_details` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_id` bigint(20) unsigned DEFAULT 'NULL',
	`opening_balance` double NOT NULL DEFAULT 0,
	`opening_balance_type` varchar(20) NOT NULL DEFAULT '''receive''',
	`credit_period` int(11) NOT NULL DEFAULT 0,
	`credit_limit` double NOT NULL DEFAULT 0,
	`purchase_order_count` int(11) NOT NULL DEFAULT 0,
	`purchase_return_count` int(11) NOT NULL DEFAULT 0,
	`sales_order_count` int(11) NOT NULL DEFAULT 0,
	`sales_return_count` int(11) NOT NULL DEFAULT 0,
	`total_amount` double NOT NULL DEFAULT 0,
	`paid_amount` double NOT NULL DEFAULT 0,
	`due_amount` double NOT NULL DEFAULT 0,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `user_warehouse` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`user_id` bigint(20) unsigned NOT NULL,
	`warehouse_id` bigint(20) unsigned NOT NULL,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `variations` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`name` varchar(20) NOT NULL,
	`parent_id` bigint(20) unsigned DEFAULT 'NULL',
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
CREATE TABLE `warehouses` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`logo` varchar(191) DEFAULT 'NULL',
	`dark_logo` varchar(191) DEFAULT 'NULL',
	`name` varchar(191) NOT NULL,
	`slug` varchar(191) DEFAULT 'NULL',
	`email` varchar(191) NOT NULL,
	`phone` varchar(191) NOT NULL,
	`show_email_on_invoice` tinyint(1) NOT NULL DEFAULT 0,
	`show_phone_on_invoice` tinyint(1) NOT NULL DEFAULT 0,
	`address` varchar(191) DEFAULT 'NULL',
	`terms_condition` text DEFAULT 'NULL',
	`bank_details` text DEFAULT 'NULL',
	`signature` varchar(191) DEFAULT 'NULL',
	`online_store_enabled` tinyint(1) NOT NULL DEFAULT 1,
	`customers_visibility` varchar(20) NOT NULL DEFAULT '''all''',
	`suppliers_visibility` varchar(20) NOT NULL DEFAULT '''all''',
	`products_visibility` varchar(20) NOT NULL DEFAULT '''all''',
	`default_pos_order_status` varchar(20) NOT NULL DEFAULT '''delivered''',
	`show_mrp_on_invoice` tinyint(1) NOT NULL DEFAULT 1,
	`show_discount_tax_on_invoice` tinyint(1) NOT NULL DEFAULT 1,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL',
	`barcode_type` varchar(20) NOT NULL DEFAULT '''barcode'''
);
--> statement-breakpoint
CREATE TABLE `warehouse_history` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`date` date NOT NULL,
	`warehouse_id` bigint(20) unsigned DEFAULT 'NULL',
	`user_id` bigint(20) unsigned DEFAULT 'NULL',
	`order_id` bigint(20) unsigned DEFAULT 'NULL',
	`order_item_id` bigint(20) unsigned DEFAULT 'NULL',
	`product_id` bigint(20) unsigned DEFAULT 'NULL',
	`payment_id` bigint(20) unsigned DEFAULT 'NULL',
	`expense_id` bigint(20) unsigned DEFAULT 'NULL',
	`amount` double NOT NULL DEFAULT 0,
	`quantity` double(8,2) NOT NULL DEFAULT 0,
	`status` varchar(191) DEFAULT 'NULL',
	`type` varchar(191) DEFAULT 'NULL',
	`transaction_number` varchar(191) DEFAULT 'NULL',
	`staff_user_id` bigint(20) unsigned DEFAULT 'NULL',
	`updated_at` datetime NOT NULL
);
--> statement-breakpoint
CREATE TABLE `warehouse_stocks` (
	`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
	`company_id` bigint(20) unsigned DEFAULT 'NULL',
	`warehouse_id` bigint(20) unsigned NOT NULL,
	`product_id` bigint(20) unsigned NOT NULL,
	`stock_quantity` double(8,2) NOT NULL DEFAULT 0,
	`created_at` timestamp DEFAULT 'NULL',
	`updated_at` timestamp DEFAULT 'NULL'
);
--> statement-breakpoint
ALTER TABLE `appreciations` ADD CONSTRAINT `appreciations_award_id_foreign` FOREIGN KEY (`award_id`) REFERENCES `awards`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `appreciations` ADD CONSTRAINT `appreciations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `appreciations` ADD CONSTRAINT `appreciations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `appreciations` ADD CONSTRAINT `appreciations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `attendances` ADD CONSTRAINT `attendances_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `attendances` ADD CONSTRAINT `attendances_holiday_id_foreign` FOREIGN KEY (`holiday_id`) REFERENCES `holidays`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `attendances` ADD CONSTRAINT `attendances_leave_id_foreign` FOREIGN KEY (`leave_id`) REFERENCES `leaves`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `attendances` ADD CONSTRAINT `attendances_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `attendances` ADD CONSTRAINT `attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `awards` ADD CONSTRAINT `awards_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `awards` ADD CONSTRAINT `awards_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `basic_salaries` ADD CONSTRAINT `basic_salaries_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `basic_salaries` ADD CONSTRAINT `basic_salaries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `brands` ADD CONSTRAINT `brands_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `categories` ADD CONSTRAINT `categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `categories` ADD CONSTRAINT `categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `companies` ADD CONSTRAINT `companies_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `companies` ADD CONSTRAINT `companies_lang_id_foreign` FOREIGN KEY (`lang_id`) REFERENCES `langs`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `companies` ADD CONSTRAINT `companies_subscription_plan_id_foreign` FOREIGN KEY (`subscription_plan_id`) REFERENCES `subscription_plans`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `companies` ADD CONSTRAINT `companies_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `currencies` ADD CONSTRAINT `currencies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `custom_fields` ADD CONSTRAINT `custom_fields_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `departments` ADD CONSTRAINT `departments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `departments` ADD CONSTRAINT `departments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `designations` ADD CONSTRAINT `designations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `designations` ADD CONSTRAINT `designations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `expenses` ADD CONSTRAINT `expenses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `expenses` ADD CONSTRAINT `expenses_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `expenses` ADD CONSTRAINT `expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `expenses` ADD CONSTRAINT `expenses_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `expense_categories` ADD CONSTRAINT `expense_categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `front_product_cards` ADD CONSTRAINT `front_product_cards_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `front_product_cards` ADD CONSTRAINT `front_product_cards_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `front_website_settings` ADD CONSTRAINT `front_website_settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `front_website_settings` ADD CONSTRAINT `front_website_settings_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `holidays` ADD CONSTRAINT `holidays_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `holidays` ADD CONSTRAINT `holidays_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `increments_promotions` ADD CONSTRAINT `increments_promotions_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `increments_promotions` ADD CONSTRAINT `increments_promotions_current_designation_id_foreign` FOREIGN KEY (`current_designation_id`) REFERENCES `designations`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `increments_promotions` ADD CONSTRAINT `increments_promotions_promoted_designation_id_foreign` FOREIGN KEY (`promoted_designation_id`) REFERENCES `designations`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `increments_promotions` ADD CONSTRAINT `increments_promotions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `leaves` ADD CONSTRAINT `leaves_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `leaves` ADD CONSTRAINT `leaves_leave_type_id_foreign` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `leaves` ADD CONSTRAINT `leaves_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `leave_types` ADD CONSTRAINT `leave_types_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `leave_types` ADD CONSTRAINT `leave_types_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `orders` ADD CONSTRAINT `orders_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `orders` ADD CONSTRAINT `orders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `orders` ADD CONSTRAINT `orders_from_warehouse_id_foreign` FOREIGN KEY (`from_warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `orders` ADD CONSTRAINT `orders_parent_order_id_foreign` FOREIGN KEY (`parent_order_id`) REFERENCES `orders`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `orders` ADD CONSTRAINT `orders_staff_user_id_foreign` FOREIGN KEY (`staff_user_id`) REFERENCES `users`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `orders` ADD CONSTRAINT `orders_tax_id_foreign` FOREIGN KEY (`tax_id`) REFERENCES `taxes`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `orders` ADD CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `orders` ADD CONSTRAINT `orders_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_custom_fields` ADD CONSTRAINT `order_custom_fields_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_items` ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_items` ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_items` ADD CONSTRAINT `order_items_tax_id_foreign` FOREIGN KEY (`tax_id`) REFERENCES `taxes`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_items` ADD CONSTRAINT `order_items_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_items` ADD CONSTRAINT `order_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_items` ADD CONSTRAINT `order_items_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_item_taxes` ADD CONSTRAINT `order_item_taxes_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_item_taxes` ADD CONSTRAINT `order_item_taxes_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_item_taxes` ADD CONSTRAINT `order_item_taxes_tax_id_foreign` FOREIGN KEY (`tax_id`) REFERENCES `taxes`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_payments` ADD CONSTRAINT `order_payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_payments` ADD CONSTRAINT `order_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_payments` ADD CONSTRAINT `order_payments_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_shipping_address` ADD CONSTRAINT `order_shipping_address_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `order_shipping_address` ADD CONSTRAINT `order_shipping_address_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payments` ADD CONSTRAINT `payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payments` ADD CONSTRAINT `payments_payment_mode_id_foreign` FOREIGN KEY (`payment_mode_id`) REFERENCES `payment_modes`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payments` ADD CONSTRAINT `payments_staff_user_id_foreign` FOREIGN KEY (`staff_user_id`) REFERENCES `users`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payments` ADD CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payments` ADD CONSTRAINT `payments_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payment_modes` ADD CONSTRAINT `payment_modes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payrolls` ADD CONSTRAINT `payrolls_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payrolls` ADD CONSTRAINT `payrolls_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payrolls` ADD CONSTRAINT `payrolls_payment_mode_id_foreign` FOREIGN KEY (`payment_mode_id`) REFERENCES `payment_modes`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payrolls` ADD CONSTRAINT `payrolls_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payrolls` ADD CONSTRAINT `payrolls_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payroll_components` ADD CONSTRAINT `payroll_components_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payroll_components` ADD CONSTRAINT `payroll_components_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `expenses`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payroll_components` ADD CONSTRAINT `payroll_components_payroll_id_foreign` FOREIGN KEY (`payroll_id`) REFERENCES `payrolls`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payroll_components` ADD CONSTRAINT `payroll_components_pre_payment_id_foreign` FOREIGN KEY (`pre_payment_id`) REFERENCES `pre_payments`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `payroll_components` ADD CONSTRAINT `payroll_components_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `permission_role` ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `permission_role` ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `pre_payments` ADD CONSTRAINT `pre_payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `pre_payments` ADD CONSTRAINT `pre_payments_payment_mode_id_foreign` FOREIGN KEY (`payment_mode_id`) REFERENCES `payment_modes`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `pre_payments` ADD CONSTRAINT `pre_payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `products` ADD CONSTRAINT `products_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `products` ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `products` ADD CONSTRAINT `products_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `products` ADD CONSTRAINT `products_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `products`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `products` ADD CONSTRAINT `products_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `products` ADD CONSTRAINT `products_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `products` ADD CONSTRAINT `products_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `product_custom_fields` ADD CONSTRAINT `product_custom_fields_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `product_custom_fields` ADD CONSTRAINT `product_custom_fields_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `product_details` ADD CONSTRAINT `product_details_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `product_details` ADD CONSTRAINT `product_details_tax_id_foreign` FOREIGN KEY (`tax_id`) REFERENCES `taxes`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `product_details` ADD CONSTRAINT `product_details_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `product_variants` ADD CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `product_variants` ADD CONSTRAINT `product_variants_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `variations`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `product_variants` ADD CONSTRAINT `product_variants_variant_value_id_foreign` FOREIGN KEY (`variant_value_id`) REFERENCES `variations`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `roles` ADD CONSTRAINT `roles_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `role_user` ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `role_user` ADD CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `settings` ADD CONSTRAINT `settings_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `shifts` ADD CONSTRAINT `shifts_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `stock_adjustments` ADD CONSTRAINT `stock_adjustments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `stock_adjustments` ADD CONSTRAINT `stock_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `stock_adjustments` ADD CONSTRAINT `stock_adjustments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `stock_adjustments` ADD CONSTRAINT `stock_adjustments_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `stock_history` ADD CONSTRAINT `stock_history_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `stock_history` ADD CONSTRAINT `stock_history_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `stock_history` ADD CONSTRAINT `stock_history_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `stock_history` ADD CONSTRAINT `stock_history_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `taxes` ADD CONSTRAINT `taxes_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `taxes` ADD CONSTRAINT `taxes_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `taxes`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `translations` ADD CONSTRAINT `translations_lang_id_foreign` FOREIGN KEY (`lang_id`) REFERENCES `langs`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `units` ADD CONSTRAINT `units_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `units` ADD CONSTRAINT `units_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `units`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `users` ADD CONSTRAINT `users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `users` ADD CONSTRAINT `users_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `users` ADD CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `users` ADD CONSTRAINT `users_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `users` ADD CONSTRAINT `users_lang_id_foreign` FOREIGN KEY (`lang_id`) REFERENCES `langs`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `users` ADD CONSTRAINT `users_shift_id_foreign` FOREIGN KEY (`shift_id`) REFERENCES `shifts`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `users` ADD CONSTRAINT `users_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `user_address` ADD CONSTRAINT `user_address_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `user_address` ADD CONSTRAINT `user_address_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `user_details` ADD CONSTRAINT `user_details_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `user_details` ADD CONSTRAINT `user_details_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `user_warehouse` ADD CONSTRAINT `user_warehouse_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `user_warehouse` ADD CONSTRAINT `user_warehouse_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `variations` ADD CONSTRAINT `variations_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `variations` ADD CONSTRAINT `variations_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `variations`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouses` ADD CONSTRAINT `warehouses_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouse_history` ADD CONSTRAINT `warehouse_history_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouse_history` ADD CONSTRAINT `warehouse_history_expense_id_foreign` FOREIGN KEY (`expense_id`) REFERENCES `expenses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouse_history` ADD CONSTRAINT `warehouse_history_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouse_history` ADD CONSTRAINT `warehouse_history_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouse_history` ADD CONSTRAINT `warehouse_history_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouse_history` ADD CONSTRAINT `warehouse_history_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouse_history` ADD CONSTRAINT `warehouse_history_staff_user_id_foreign` FOREIGN KEY (`staff_user_id`) REFERENCES `users`(`id`) ON DELETE set null ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouse_history` ADD CONSTRAINT `warehouse_history_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouse_history` ADD CONSTRAINT `warehouse_history_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouse_stocks` ADD CONSTRAINT `warehouse_stocks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouse_stocks` ADD CONSTRAINT `warehouse_stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
ALTER TABLE `warehouse_stocks` ADD CONSTRAINT `warehouse_stocks_warehouse_id_foreign` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE cascade ON UPDATE cascade;--> statement-breakpoint
CREATE INDEX `companies_stripe_id_index` ON `companies` (`stripe_id`);--> statement-breakpoint
CREATE INDEX `jobs_queue_index` ON `jobs` (`queue`);--> statement-breakpoint
CREATE INDEX `notifications_notifiable_type_notifiable_id_index` ON `notifications` (`notifiable_type`,`notifiable_id`);
*/