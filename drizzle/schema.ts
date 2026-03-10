import { mysqlTable, mysqlSchema, AnyMySqlColumn, foreignKey, datetime, double, text, timestamp, date, varchar, int, time, index, mysqlEnum, unique, longtext, char, smallint } from "drizzle-orm/mysql-core"
import { sql } from "drizzle-orm"

export const appreciations = mysqlTable("appreciations", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	awardId: bigint("award_id", { mode: "number" }).default('NULL').references(() => awards.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	userId: bigint("user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	createdBy: bigint("created_by", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	date: datetime({ mode: 'string'}).notNull(),
	priceAmount: double("price_amount").default('NULL'),
	priceGiven: text("price_given").default('NULL'),
	description: text().notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const attendances = mysqlTable("attendances", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	// you can use { mode: 'date' }, if you want to have Date as type for this column
	date: date({ mode: 'string' }).default('NULL'),
	isHoliday: tinyint("is_holiday").default(0).notNull(),
	isLeave: tinyint("is_leave").default(0).notNull(),
	userId: bigint("user_id", { mode: "number" }).notNull().references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	leaveId: bigint("leave_id", { mode: "number" }).default('NULL').references(() => leaves.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	leaveTypeId: bigint("leave_type_id", { mode: "number" }).default('NULL').references(() => leaveTypes.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	holidayId: bigint("holiday_id", { mode: "number" }).default('NULL').references(() => holidays.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	clockInDateTime: datetime("clock_in_date_time", { mode: 'string'}).default('NULL'),
	clockOutDateTime: datetime("clock_out_date_time", { mode: 'string'}).default('NULL'),
	clockInIpAddress: varchar("clock_in_ip_address", { length: 20 }).default('NULL'),
	totalDuration: int("total_duration").default('NULL'),
	clockOutIpAddress: varchar("clock_out_ip_address", { length: 20 }).default('NULL'),
	clockInTime: time("clock_in_time").default('NULL'),
	clockOutTime: time("clock_out_time").default('NULL'),
	officeClockInTime: time("office_clock_in_time").default('NULL'),
	officeClockOutTime: time("office_clock_out_time").default('NULL'),
	isHalfDay: tinyint("is_half_day").default(0).notNull(),
	isLate: tinyint("is_late").default(0).notNull(),
	isPaid: tinyint("is_paid").default(0).notNull(),
	status: varchar({ length: 191 }).default('\'present\'').notNull(),
	reason: text().default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const awards = mysqlTable("awards", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	createdBy: bigint("created_by", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	active: tinyint().default(1).notNull(),
	awardPrice: double("award_price").default('NULL'),
	description: text().notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const basicSalaries = mysqlTable("basic_salaries", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	userId: bigint("user_id", { mode: "number" }).notNull().references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	basicSalary: double("basic_salary"),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const brands = mysqlTable("brands", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	slug: varchar({ length: 191 }).notNull(),
	image: varchar({ length: 191 }).default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const categories = mysqlTable("categories", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	slug: varchar({ length: 191 }).notNull(),
	image: varchar({ length: 191 }).default('NULL'),
	parentId: bigint("parent_id", { mode: "number" }).default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
},
(table) => [
	foreignKey({
			columns: [table.parentId],
			foreignColumns: [table.id],
			name: "categories_parent_id_foreign"
		}).onUpdate("cascade").onDelete("cascade"),
]);

export const companies = mysqlTable("companies", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	name: varchar({ length: 191 }).notNull(),
	shortName: varchar("short_name", { length: 191 }).default('NULL'),
	email: varchar({ length: 191 }).default('NULL'),
	phone: varchar({ length: 191 }).default('NULL'),
	website: varchar({ length: 191 }).default('NULL'),
	lightLogo: varchar("light_logo", { length: 191 }).default('NULL'),
	darkLogo: varchar("dark_logo", { length: 191 }).default('NULL'),
	smallDarkLogo: varchar("small_dark_logo", { length: 191 }).default('NULL'),
	smallLightLogo: varchar("small_light_logo", { length: 191 }).default('NULL'),
	address: varchar({ length: 1000 }).default('NULL'),
	appLayout: varchar("app_layout", { length: 10 }).default('\'sidebar\'').notNull(),
	rtl: tinyint().default(0).notNull(),
	mysqldumpCommand: varchar("mysqldump_command", { length: 191 }).default('\'/usr/bin/mysqldump\'').notNull(),
	shortcutMenus: varchar("shortcut_menus", { length: 20 }).default('\'top_bottom\'').notNull(),
	currencyId: bigint("currency_id", { mode: "number" }).default('NULL').references((): AnyMySqlColumn => currencies.id, { onDelete: "set null", onUpdate: "cascade" } ),
	langId: bigint("lang_id", { mode: "number" }).default('NULL').references(() => langs.id, { onDelete: "set null", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references((): AnyMySqlColumn => warehouses.id, { onDelete: "set null", onUpdate: "cascade" } ),
	leftSidebarTheme: varchar("left_sidebar_theme", { length: 20 }).default('\'dark\'').notNull(),
	primaryColor: varchar("primary_color", { length: 20 }).default('\'#1890ff\'').notNull(),
	dateFormat: varchar("date_format", { length: 20 }).default('\'DD-MM-YYYY\'').notNull(),
	timeFormat: varchar("time_format", { length: 20 }).default('\'hh:mm a\'').notNull(),
	autoDetectTimezone: tinyint("auto_detect_timezone").default(1).notNull(),
	timezone: varchar({ length: 191 }).default('\'Asia/Kolkata\'').notNull(),
	sessionDriver: varchar("session_driver", { length: 20 }).default('\'file\'').notNull(),
	appDebug: tinyint("app_debug").default(0).notNull(),
	updateAppNotification: tinyint("update_app_notification").default(1).notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
	loginImage: varchar("login_image", { length: 191 }).default('NULL'),
	stripeId: varchar("stripe_id", { length: 191 }).default('NULL'),
	cardBrand: varchar("card_brand", { length: 191 }).default('NULL'),
	cardLastFour: varchar("card_last_four", { length: 4 }).default('NULL'),
	trialEndsAt: timestamp("trial_ends_at", { mode: 'string' }).default('NULL'),
	subscriptionPlanId: bigint("subscription_plan_id", { mode: "number" }).default('NULL').references(() => subscriptionPlans.id, { onDelete: "set null", onUpdate: "cascade" } ),
	packageType: mysqlEnum("package_type", ['monthly','annual']).default('\'monthly\'').notNull(),
	// you can use { mode: 'date' }, if you want to have Date as type for this column
	licenceExpireOn: date("licence_expire_on", { mode: 'string' }).default('NULL'),
	isGlobal: tinyint("is_global").default(0).notNull(),
	adminId: bigint("admin_id", { mode: "number" }).default('NULL'),
	status: varchar({ length: 191 }).default('\'active\'').notNull(),
	totalUsers: int("total_users").default(1).notNull(),
	emailVerificationCode: varchar("email_verification_code", { length: 191 }).default('NULL'),
	verified: tinyint().default(0).notNull(),
	whiteLabelCompleted: tinyint("white_label_completed").default(0).notNull(),
	clockInTime: time("clock_in_time").default('''09:30:00'''),
	clockOutTime: time("clock_out_time").default('''18:00:00'''),
	leaveStartMonth: varchar("leave_start_month", { length: 2 }).default('\'01\'').notNull(),
	lateMarkAfter: int("late_mark_after").default('NULL'),
	earlyClockInTime: int("early_clock_in_time").default('NULL'),
	allowClockOutTill: int("allow_clock_out_till").default('NULL'),
	selfClocking: tinyint("self_clocking").default(1).notNull(),
	allowedIpAddress: text("allowed_ip_address").default('NULL'),
},
(table) => [
	index("companies_stripe_id_index").on(table.stripeId),
]);

export const currencies = mysqlTable("currencies", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references((): AnyMySqlColumn => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	code: varchar({ length: 191 }).notNull(),
	symbol: varchar({ length: 191 }).notNull(),
	position: varchar({ length: 191 }).notNull(),
	isDeletable: tinyint("is_deletable").default(1).notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
	decimalSeparator: varchar("decimal_separator", { length: 20 }).default('\'dot\'').notNull(),
	thousandSeparator: varchar("thousand_separator", { length: 20 }).default('\'comma\'').notNull(),
	removeDecimalWithZero: tinyint("remove_decimal_with_zero").default(1).notNull(),
	spaceBetweenPriceAndPriceSymbol: tinyint("space_between_price_and_price_symbol").default(0).notNull(),
});

export const customFields = mysqlTable("custom_fields", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	value: varchar({ length: 191 }).default('NULL'),
	type: varchar({ length: 191 }).default('\'text\'').notNull(),
	active: tinyint().default(0).notNull(),
});

export const departments = mysqlTable("departments", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	createdBy: bigint("created_by", { mode: "number" }).default('NULL').references((): AnyMySqlColumn => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const designations = mysqlTable("designations", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	createdBy: bigint("created_by", { mode: "number" }).default('NULL').references((): AnyMySqlColumn => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const expenses = mysqlTable("expenses", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	bill: varchar({ length: 191 }).default('NULL'),
	expenseCategoryId: bigint("expense_category_id", { mode: "number" }).default('NULL').references(() => expenseCategories.id, { onDelete: "set null", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "set null", onUpdate: "cascade" } ),
	amount: double({ precision: 8, scale: 2 }).notNull(),
	userId: bigint("user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "set null", onUpdate: "cascade" } ),
	notes: varchar({ length: 1000 }).default('NULL'),
	date: datetime({ mode: 'string'}).notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const expenseCategories = mysqlTable("expense_categories", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	description: varchar({ length: 1000 }).default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const failedJobs = mysqlTable("failed_jobs", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	uuid: varchar({ length: 191 }).notNull(),
	connection: text().notNull(),
	queue: text().notNull(),
	payload: longtext().notNull(),
	exception: longtext().notNull(),
	failedAt: timestamp("failed_at", { mode: 'string' }).default('current_timestamp()').notNull(),
},
(table) => [
	unique("failed_jobs_uuid_unique").on(table.uuid),
]);

export const frontProductCards = mysqlTable("front_product_cards", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	title: varchar({ length: 191 }).notNull(),
	subtitle: varchar({ length: 191 }).default('NULL'),
	products: text().notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const frontWebsiteSettings = mysqlTable("front_website_settings", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	featuredCategories: text("featured_categories").notNull(),
	featuredCategoriesTitle: varchar("featured_categories_title", { length: 191 }).default('\'Featured Categories\''),
	featuredCategoriesSubtitle: varchar("featured_categories_subtitle", { length: 191 }).default('\''),
	featuredProducts: text("featured_products").notNull(),
	featuredProductsTitle: varchar("featured_products_title", { length: 191 }).default('\'Featured Products\''),
	featuredProductsSubtitle: varchar("featured_products_subtitle", { length: 191 }).default('\''),
	featuresLists: text("features_lists").notNull(),
	facebookUrl: varchar("facebook_url", { length: 191 }).default('\''),
	twitterUrl: varchar("twitter_url", { length: 191 }).default('\''),
	instagramUrl: varchar("instagram_url", { length: 191 }).default('\''),
	linkedinUrl: varchar("linkedin_url", { length: 191 }).default('\''),
	youtubeUrl: varchar("youtube_url", { length: 191 }).default('\''),
	pagesWidget: text("pages_widget").notNull(),
	contactInfoWidget: text("contact_info_widget").notNull(),
	linksWidget: text("links_widget").notNull(),
	footerCompanyDescription: varchar("footer_company_description", { length: 1000 }).default('\'Stockify have many propular products wiht high discount and special offers.\'').notNull(),
	footerCopyrightText: varchar("footer_copyright_text", { length: 1000 }).default('\'Copyright 2021 @ Stockify, All rights reserved.\'').notNull(),
	topBanners: text("top_banners").notNull(),
	bottomBanners1: text("bottom_banners_1").notNull(),
	bottomBanners2: text("bottom_banners_2").notNull(),
	bottomBanners3: text("bottom_banners_3").notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const holidays = mysqlTable("holidays", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	year: int().notNull(),
	month: int().notNull(),
	// you can use { mode: 'date' }, if you want to have Date as type for this column
	date: date({ mode: 'string' }).notNull(),
	isWeekend: tinyint("is_weekend").default(0).notNull(),
	createdBy: bigint("created_by", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const incrementsPromotions = mysqlTable("increments_promotions", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	userId: bigint("user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	type: varchar({ length: 191 }).default('\'promotion\'').notNull(),
	// you can use { mode: 'date' }, if you want to have Date as type for this column
	date: date({ mode: 'string' }).notNull(),
	description: text().notNull(),
	netSalary: int("net_salary").default('NULL'),
	promotedDesignationId: bigint("promoted_designation_id", { mode: "number" }).default('NULL').references(() => designations.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	currentDesignationId: bigint("current_designation_id", { mode: "number" }).default('NULL').references(() => designations.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const jobs = mysqlTable("jobs", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	queue: varchar({ length: 191 }).notNull(),
	payload: longtext().notNull(),
	attempts: tinyint().notNull(),
	reservedAt: int("reserved_at").default('NULL'),
	availableAt: int("available_at").notNull(),
	createdAt: int("created_at").notNull(),
},
(table) => [
	index("jobs_queue_index").on(table.queue),
]);

export const langs = mysqlTable("langs", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	image: varchar({ length: 191 }).default('NULL'),
	name: varchar({ length: 191 }).notNull(),
	key: varchar({ length: 191 }).notNull(),
	enabled: tinyint().default(1).notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const leaves = mysqlTable("leaves", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	userId: bigint("user_id", { mode: "number" }).notNull().references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	leaveTypeId: bigint("leave_type_id", { mode: "number" }).notNull().references(() => leaveTypes.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	// you can use { mode: 'date' }, if you want to have Date as type for this column
	startDate: date("start_date", { mode: 'string' }).notNull(),
	// you can use { mode: 'date' }, if you want to have Date as type for this column
	endDate: date("end_date", { mode: 'string' }).default('NULL'),
	totalDays: int("total_days").default(0).notNull(),
	isHalfDay: tinyint("is_half_day").default(0).notNull(),
	reason: text().notNull(),
	isPaid: tinyint("is_paid").default(0).notNull(),
	status: varchar({ length: 20 }).default('\'pending\'').notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const leaveTypes = mysqlTable("leave_types", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	isPaid: tinyint("is_paid").default(0).notNull(),
	totalLeaves: int("total_leaves").notNull(),
	maxLeavesPerMonth: int("max_leaves_per_month").default('NULL'),
	createdBy: bigint("created_by", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const migrations = mysqlTable("migrations", {
	id: int().autoincrement().notNull(),
	migration: varchar({ length: 191 }).notNull(),
	batch: int().notNull(),
});

export const notifications = mysqlTable("notifications", {
	id: char({ length: 36 }).notNull(),
	type: varchar({ length: 191 }).notNull(),
	notifiableType: varchar("notifiable_type", { length: 191 }).notNull(),
	notifiableId: bigint("notifiable_id", { mode: "number" }).notNull(),
	data: text().notNull(),
	readAt: timestamp("read_at", { mode: 'string' }).default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
},
(table) => [
	index("notifications_notifiable_type_notifiable_id_index").on(table.notifiableType, table.notifiableId),
]);

export const orders = mysqlTable("orders", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	uniqueId: varchar("unique_id", { length: 20 }).notNull(),
	invoiceNumber: varchar("invoice_number", { length: 20 }).notNull(),
	invoiceType: varchar("invoice_type", { length: 20 }).default('NULL'),
	orderType: varchar("order_type", { length: 20 }).default('\'sales\'').notNull(),
	parentOrderId: bigint("parent_order_id", { mode: "number" }).default('NULL'),
	orderDate: datetime("order_date", { mode: 'string'}).notNull(),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	fromWarehouseId: bigint("from_warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	userId: bigint("user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	taxId: bigint("tax_id", { mode: "number" }).default('NULL').references(() => taxes.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	taxRate: double("tax_rate", { precision: 8, scale: 2 }).default('NULL'),
	taxAmount: double("tax_amount").notNull(),
	discount: double().default('NULL'),
	shipping: double().default('NULL'),
	subtotal: double().notNull(),
	total: double().notNull(),
	paidAmount: double("paid_amount").notNull(),
	dueAmount: double("due_amount").notNull(),
	orderStatus: varchar("order_status", { length: 20 }).notNull(),
	notes: text().default('NULL'),
	supplierInvoiceNumber: varchar("supplier_invoice_number", { length: 100 }).default('NULL'),
	deliveryChallanNo: varchar("delivery_challan_no", { length: 100 }).default('NULL'),
	receivedByName: varchar("received_by_name", { length: 100 }).default('NULL'),
	receivedBySignature: varchar("received_by_signature", { length: 255 }).default('NULL'),
	// you can use { mode: 'date' }, if you want to have Date as type for this column
	receivedByDate: date("received_by_date", { mode: 'string' }).default('NULL'),
	checkedByName: varchar("checked_by_name", { length: 100 }).default('NULL'),
	checkedBySignature: varchar("checked_by_signature", { length: 255 }).default('NULL'),
	// you can use { mode: 'date' }, if you want to have Date as type for this column
	checkedByDate: date("checked_by_date", { mode: 'string' }).default('NULL'),
	approvedByName: varchar("approved_by_name", { length: 100 }).default('NULL'),
	approvedBySignature: varchar("approved_by_signature", { length: 255 }).default('NULL'),
	// you can use { mode: 'date' }, if you want to have Date as type for this column
	approvedByDate: date("approved_by_date", { mode: 'string' }).default('NULL'),
	document: varchar({ length: 191 }).default('NULL'),
	staffUserId: bigint("staff_user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "set null", onUpdate: "cascade" } ),
	paymentStatus: varchar("payment_status", { length: 20 }).default('\'unpaid\'').notNull(),
	totalItems: double("total_items", { precision: 8, scale: 2 }).notNull(),
	totalQuantity: double("total_quantity", { precision: 8, scale: 2 }).notNull(),
	termsCondition: text("terms_condition").default('NULL'),
	isDeletable: tinyint("is_deletable").default(1).notNull(),
	cancelled: tinyint().default(0).notNull(),
	cancelledBy: bigint("cancelled_by", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "set null", onUpdate: "cascade" } ),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
},
(table) => [
	foreignKey({
			columns: [table.parentOrderId],
			foreignColumns: [table.id],
			name: "orders_parent_order_id_foreign"
		}).onUpdate("cascade").onDelete("set null"),
]);

export const orderCustomFields = mysqlTable("order_custom_fields", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	orderId: bigint("order_id", { mode: "number" }).notNull().references(() => orders.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	fieldName: varchar("field_name", { length: 191 }).notNull(),
	fieldValue: varchar("field_value", { length: 191 }).default('NULL'),
});

export const orderItems = mysqlTable("order_items", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	userId: bigint("user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	orderId: bigint("order_id", { mode: "number" }).notNull().references(() => orders.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	productId: bigint("product_id", { mode: "number" }).notNull().references(() => products.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "set null", onUpdate: "cascade" } ),
	unitId: bigint("unit_id", { mode: "number" }).default('NULL').references(() => units.id, { onDelete: "set null", onUpdate: "cascade" } ),
	quantity: double({ precision: 8, scale: 2 }).notNull(),
	receivedQuantity: double("received_quantity").default('NULL'),
	shortDamagedQuantity: double("short_damaged_quantity").default('NULL'),
	mrp: double().default('NULL'),
	unitPrice: double("unit_price").notNull(),
	singleUnitPrice: double("single_unit_price").notNull(),
	taxId: bigint("tax_id", { mode: "number" }).default('NULL').references(() => taxes.id, { onDelete: "set null", onUpdate: "cascade" } ),
	taxRate: double("tax_rate", { precision: 8, scale: 2 }).notNull(),
	taxType: varchar("tax_type", { length: 10 }).default('NULL'),
	discountRate: double("discount_rate", { precision: 8, scale: 2 }).default('NULL'),
	totalTax: double("total_tax").default('NULL'),
	totalDiscount: double("total_discount").default('NULL'),
	subtotal: double().notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const orderItemTaxes = mysqlTable("order_item_taxes", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	taxName: varchar("tax_name", { length: 191 }).notNull(),
	taxType: varchar("tax_type", { length: 20 }).notNull(),
	taxAmount: double("tax_amount").notNull(),
	taxRate: double("tax_rate", { precision: 8, scale: 2 }).notNull(),
	orderId: bigint("order_id", { mode: "number" }).notNull().references(() => orders.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	orderItemId: bigint("order_item_id", { mode: "number" }).notNull().references(() => orderItems.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	taxId: bigint("tax_id", { mode: "number" }).default('NULL').references(() => taxes.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const orderPayments = mysqlTable("order_payments", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	paymentId: bigint("payment_id", { mode: "number" }).notNull().references(() => payments.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	orderId: bigint("order_id", { mode: "number" }).default('NULL').references(() => orders.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	amount: double().notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const orderShippingAddress = mysqlTable("order_shipping_address", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	orderId: bigint("order_id", { mode: "number" }).notNull().references(() => orders.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	email: varchar({ length: 191 }).notNull(),
	phone: varchar({ length: 191 }).notNull(),
	address: varchar({ length: 1000 }).default('NULL'),
	shippingAddress: varchar("shipping_address", { length: 1000 }).default('NULL'),
	city: varchar({ length: 50 }).default('NULL'),
	state: varchar({ length: 50 }).default('NULL'),
	country: varchar({ length: 50 }).default('NULL'),
	zipcode: varchar({ length: 50 }).default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const payments = mysqlTable("payments", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	paymentType: varchar("payment_type", { length: 20 }).default('\'out\'').notNull(),
	paymentNumber: varchar("payment_number", { length: 191 }).default('NULL'),
	date: datetime({ mode: 'string'}).notNull(),
	amount: double().notNull(),
	unusedAmount: double("unused_amount").notNull(),
	paidAmount: double("paid_amount").notNull(),
	paymentModeId: bigint("payment_mode_id", { mode: "number" }).default('NULL').references(() => paymentModes.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	userId: bigint("user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	paymentReceipt: varchar("payment_receipt", { length: 191 }).default('NULL'),
	notes: text().default('NULL'),
	staffUserId: bigint("staff_user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "set null", onUpdate: "cascade" } ),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const paymentModes = mysqlTable("payment_modes", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	modeType: varchar("mode_type", { length: 191 }).default('\'bank\''),
	credentials: text().default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const payrolls = mysqlTable("payrolls", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	userId: bigint("user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	month: int().notNull(),
	year: int().notNull(),
	basicSalary: double("basic_salary").notNull(),
	salaryAmount: double("salary_amount").notNull(),
	prePaymentAmount: double("pre_payment_amount").notNull(),
	expenseAmount: double("expense_amount").notNull(),
	netSalary: double("net_salary").notNull(),
	totalDays: double("total_days", { precision: 8, scale: 2 }).notNull(),
	workingDays: double("working_days", { precision: 8, scale: 2 }).notNull(),
	presentDays: double("present_days", { precision: 8, scale: 2 }).notNull(),
	totalOfficeTime: int("total_office_time").notNull(),
	totalWorkedTime: int("total_worked_time").notNull(),
	halfDays: int("half_days").notNull(),
	lateDays: double("late_days", { precision: 8, scale: 2 }).notNull(),
	paidLeaves: double("paid_leaves", { precision: 8, scale: 2 }).notNull(),
	unpaidLeaves: double("unpaid_leaves", { precision: 8, scale: 2 }).notNull(),
	holidayCount: double("holiday_count", { precision: 8, scale: 2 }).notNull(),
	// you can use { mode: 'date' }, if you want to have Date as type for this column
	paymentDate: date("payment_date", { mode: 'string' }).default('NULL'),
	status: varchar({ length: 191 }).default('\'generated\'').notNull(),
	createdBy: bigint("created_by", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	updatedBy: bigint("updated_by", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	paymentModeId: bigint("payment_mode_id", { mode: "number" }).default('NULL').references(() => paymentModes.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const payrollComponents = mysqlTable("payroll_components", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	userId: bigint("user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	payrollId: bigint("payroll_id", { mode: "number" }).default('NULL').references(() => payrolls.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	prePaymentId: bigint("pre_payment_id", { mode: "number" }).default('NULL').references(() => prePayments.id, { onDelete: "set null", onUpdate: "cascade" } ),
	expenseId: bigint("expense_id", { mode: "number" }).default('NULL').references(() => expenses.id, { onDelete: "set null", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	amount: double().notNull(),
	isEarning: tinyint("is_earning").default(1).notNull(),
	type: varchar({ length: 20 }).default('\'pre_payments\'').notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const permissions = mysqlTable("permissions", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	name: varchar({ length: 191 }).notNull(),
	displayName: varchar("display_name", { length: 191 }).default('NULL'),
	description: varchar({ length: 191 }).default('NULL'),
	moduleName: varchar("module_name", { length: 191 }).default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const permissionRole = mysqlTable("permission_role", {
	permissionId: bigint("permission_id", { mode: "number" }).notNull().references(() => permissions.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	roleId: bigint("role_id", { mode: "number" }).notNull().references(() => roles.id, { onDelete: "cascade", onUpdate: "cascade" } ),
});

export const prePayments = mysqlTable("pre_payments", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	userId: bigint("user_id", { mode: "number" }).notNull().references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	paymentModeId: bigint("payment_mode_id", { mode: "number" }).notNull().references(() => paymentModes.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	amount: double().notNull(),
	dateTime: datetime("date_time", { mode: 'string'}).notNull(),
	deductFromPayroll: tinyint("deduct_from_payroll").default(1).notNull(),
	payrollMonth: int("payroll_month").notNull(),
	payrollYear: int("payroll_year").notNull(),
	notes: longtext().notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const products = mysqlTable("products", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "set null", onUpdate: "cascade" } ),
	productType: varchar("product_type", { length: 10 }).default('\'single\'').notNull(),
	parentId: bigint("parent_id", { mode: "number" }).default('NULL'),
	parentItemCode: varchar("parent_item_code", { length: 191 }).default('NULL'),
	name: varchar({ length: 1000 }).notNull(),
	slug: varchar({ length: 1000 }).notNull(),
	barcodeSymbology: varchar("barcode_symbology", { length: 10 }).notNull(),
	itemCode: varchar("item_code", { length: 191 }).notNull(),
	image: varchar({ length: 191 }).default('NULL'),
	categoryId: bigint("category_id", { mode: "number" }).default('NULL').references(() => categories.id, { onDelete: "set null", onUpdate: "cascade" } ),
	brandId: bigint("brand_id", { mode: "number" }).default('NULL').references(() => brands.id, { onDelete: "set null", onUpdate: "cascade" } ),
	unitId: bigint("unit_id", { mode: "number" }).default('NULL').references(() => units.id, { onDelete: "set null", onUpdate: "cascade" } ),
	description: text().default('NULL'),
	userId: bigint("user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "set null", onUpdate: "cascade" } ),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
},
(table) => [
	foreignKey({
			columns: [table.parentId],
			foreignColumns: [table.id],
			name: "products_parent_id_foreign"
		}).onUpdate("cascade").onDelete("cascade"),
]);

export const productCustomFields = mysqlTable("product_custom_fields", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	productId: bigint("product_id", { mode: "number" }).notNull().references(() => products.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	fieldName: varchar("field_name", { length: 191 }).notNull(),
	fieldValue: varchar("field_value", { length: 191 }).default('NULL'),
});

export const productDetails = mysqlTable("product_details", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	productId: bigint("product_id", { mode: "number" }).default('NULL').references(() => products.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	currentStock: double("current_stock", { precision: 8, scale: 2 }).notNull(),
	mrp: double().default('NULL'),
	purchasePrice: double("purchase_price").notNull(),
	salesPrice: double("sales_price").notNull(),
	taxId: bigint("tax_id", { mode: "number" }).default('NULL').references(() => taxes.id, { onDelete: "set null", onUpdate: "cascade" } ),
	purchaseTaxType: varchar("purchase_tax_type", { length: 10 }).default('\'exclusive\''),
	salesTaxType: varchar("sales_tax_type", { length: 10 }).default('\'exclusive\''),
	stockQuantitiyAlert: int("stock_quantitiy_alert").default('NULL'),
	openingStock: int("opening_stock").default('NULL'),
	// you can use { mode: 'date' }, if you want to have Date as type for this column
	openingStockDate: date("opening_stock_date", { mode: 'string' }).default('NULL'),
	wholesalePrice: double("wholesale_price").default('NULL'),
	wholesaleQuantity: int("wholesale_quantity").default('NULL'),
	status: varchar({ length: 191 }).default('\'in_stock\'').notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const productVariants = mysqlTable("product_variants", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	productId: bigint("product_id", { mode: "number" }).notNull().references(() => products.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	variantId: bigint("variant_id", { mode: "number" }).default('NULL').references(() => variations.id, { onDelete: "set null", onUpdate: "cascade" } ),
	variantValueId: bigint("variant_value_id", { mode: "number" }).default('NULL').references(() => variations.id, { onDelete: "set null", onUpdate: "cascade" } ),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const roles = mysqlTable("roles", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	displayName: varchar("display_name", { length: 191 }).default('NULL'),
	description: varchar({ length: 191 }).default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const roleUser = mysqlTable("role_user", {
	userId: bigint("user_id", { mode: "number" }).notNull().references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	roleId: bigint("role_id", { mode: "number" }).notNull().references(() => roles.id, { onDelete: "cascade", onUpdate: "cascade" } ),
});

export const settings = mysqlTable("settings", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	isGlobal: tinyint("is_global").default(0).notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	settingType: varchar("setting_type", { length: 191 }).notNull(),
	name: varchar({ length: 191 }).notNull(),
	nameKey: varchar("name_key", { length: 191 }).notNull(),
	credentials: text().default('NULL'),
	otherData: text("other_data").default('NULL'),
	status: tinyint().default(0).notNull(),
	verified: tinyint().default(0).notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const shifts = mysqlTable("shifts", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	clockInTime: time("clock_in_time").notNull(),
	clockOutTime: time("clock_out_time").notNull(),
	lateMarkAfter: int("late_mark_after").default('NULL'),
	earlyClockInTime: int("early_clock_in_time").default('NULL'),
	allowClockOutTill: int("allow_clock_out_till").default('NULL'),
	selfClocking: tinyint("self_clocking").default(1).notNull(),
	allowedIpAddress: varchar("allowed_ip_address", { length: 1000 }).default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const stockAdjustments = mysqlTable("stock_adjustments", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).notNull().references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	productId: bigint("product_id", { mode: "number" }).notNull().references(() => products.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	quantity: double({ precision: 8, scale: 2 }).notNull(),
	adjustmentType: varchar("adjustment_type", { length: 20 }).default('\'add\'').notNull(),
	notes: text().default('NULL'),
	createdBy: bigint("created_by", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "set null", onUpdate: "cascade" } ),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const stockHistory = mysqlTable("stock_history", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).notNull().references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	productId: bigint("product_id", { mode: "number" }).notNull().references(() => products.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	quantity: double({ precision: 8, scale: 2 }).notNull(),
	oldQuantity: double("old_quantity", { precision: 8, scale: 2 }).notNull(),
	orderType: varchar("order_type", { length: 20 }).default('\'sales\''),
	stockType: varchar("stock_type", { length: 20 }).default('\'in\'').notNull(),
	actionType: varchar("action_type", { length: 20 }).default('\'add\'').notNull(),
	createdBy: bigint("created_by", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "set null", onUpdate: "cascade" } ),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const subscriptionPlans = mysqlTable("subscription_plans", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	name: varchar({ length: 255 }).notNull(),
	description: varchar({ length: 1000 }).default('NULL'),
	annualPrice: double("annual_price").notNull(),
	monthlyPrice: double("monthly_price").notNull(),
	maxProducts: int("max_products").default(0).notNull(),
	modules: text().default('NULL'),
	default: varchar({ length: 20 }).default('\'no\'').notNull(),
	isPopular: tinyint("is_popular").default(0).notNull(),
	isPrivate: tinyint("is_private").default(0).notNull(),
	billingCycle: tinyint("billing_cycle").default('NULL'),
	stripeMonthlyPlanId: varchar("stripe_monthly_plan_id", { length: 191 }).default('NULL'),
	stripeAnnualPlanId: varchar("stripe_annual_plan_id", { length: 191 }).default('NULL'),
	razorpayMonthlyPlanId: varchar("razorpay_monthly_plan_id", { length: 191 }).default('NULL'),
	razorpayAnnualPlanId: varchar("razorpay_annual_plan_id", { length: 191 }).default('NULL'),
	paystackMonthlyPlanId: varchar("paystack_monthly_plan_id", { length: 191 }).default('NULL'),
	paystackAnnualPlanId: varchar("paystack_annual_plan_id", { length: 191 }).default('NULL'),
	active: tinyint().default(1).notNull(),
	duration: int().default(30),
	notifyBefore: int("notify_before").default('NULL'),
	position: smallint().default('NULL'),
	features: text().default('NULL'),
	currencyCode: varchar("currency_code", { length: 191 }).default('\'USD\''),
	currencySymbol: varchar("currency_symbol", { length: 191 }).default('\'$\''),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const taxes = mysqlTable("taxes", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	parentId: bigint("parent_id", { mode: "number" }).default('NULL'),
	taxType: varchar("tax_type", { length: 20 }).default('\'single\''),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	rate: double({ precision: 8, scale: 2 }).notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
},
(table) => [
	foreignKey({
			columns: [table.parentId],
			foreignColumns: [table.id],
			name: "taxes_parent_id_foreign"
		}).onUpdate("cascade").onDelete("cascade"),
]);

export const translations = mysqlTable("translations", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	langId: bigint("lang_id", { mode: "number" }).default('NULL').references(() => langs.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	group: varchar({ length: 191 }).notNull(),
	key: text().notNull(),
	value: text().default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const units = mysqlTable("units", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	shortName: varchar("short_name", { length: 191 }).notNull(),
	baseUnit: varchar("base_unit", { length: 191 }).default('NULL'),
	parentId: bigint("parent_id", { mode: "number" }).default('NULL'),
	operator: varchar({ length: 191 }).notNull(),
	operatorValue: varchar("operator_value", { length: 191 }).notNull(),
	isDeletable: tinyint("is_deletable").default(1).notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
},
(table) => [
	foreignKey({
			columns: [table.parentId],
			foreignColumns: [table.id],
			name: "units_parent_id_foreign"
		}).onUpdate("cascade").onDelete("cascade"),
]);

export const users = mysqlTable("users", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	isSuperadmin: tinyint("is_superadmin").default(0).notNull(),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	roleId: bigint("role_id", { mode: "number" }).default('NULL'),
	langId: bigint("lang_id", { mode: "number" }).default('NULL').references(() => langs.id, { onDelete: "set null", onUpdate: "cascade" } ),
	userType: varchar("user_type", { length: 191 }).default('\'customers\'').notNull(),
	isWalkinCustomer: tinyint("is_walkin_customer").default(0).notNull(),
	loginEnabled: tinyint("login_enabled").default(1).notNull(),
	name: varchar({ length: 191 }).notNull(),
	email: varchar({ length: 191 }).default('NULL'),
	password: varchar({ length: 191 }).default('NULL'),
	phone: varchar({ length: 191 }).default('NULL'),
	profileImage: varchar("profile_image", { length: 191 }).default('NULL'),
	address: varchar({ length: 1000 }).default('NULL'),
	shippingAddress: varchar("shipping_address", { length: 1000 }).default('NULL'),
	emailVerificationCode: varchar("email_verification_code", { length: 50 }).default('NULL'),
	status: varchar({ length: 191 }).default('\'enabled\'').notNull(),
	resetCode: varchar("reset_code", { length: 191 }).default('NULL'),
	timezone: varchar({ length: 50 }).default('\'Asia/Kolkata\'').notNull(),
	dateFormat: varchar("date_format", { length: 20 }).default('\'d-m-Y\'').notNull(),
	datePickerFormat: varchar("date_picker_format", { length: 20 }).default('\'dd-mm-yyyy\'').notNull(),
	timeFormat: varchar("time_format", { length: 20 }).default('\'h:i a\'').notNull(),
	taxNumber: varchar("tax_number", { length: 191 }).default('NULL'),
	createdBy: bigint("created_by", { mode: "number" }).default('NULL'),
	departmentId: bigint("department_id", { mode: "number" }).default('NULL').references((): AnyMySqlColumn => departments.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	designationId: bigint("designation_id", { mode: "number" }).default('NULL').references((): AnyMySqlColumn => designations.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	shiftId: bigint("shift_id", { mode: "number" }).default('NULL').references(() => shifts.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	resetPasswordToken: varchar("reset_password_token", { length: 191 }).default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
},
(table) => [
	foreignKey({
			columns: [table.createdBy],
			foreignColumns: [table.id],
			name: "users_created_by_foreign"
		}).onUpdate("cascade").onDelete("set null"),
]);

export const userAddress = mysqlTable("user_address", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	userId: bigint("user_id", { mode: "number" }).notNull().references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 191 }).notNull(),
	email: varchar({ length: 191 }).notNull(),
	phone: varchar({ length: 191 }).notNull(),
	address: varchar({ length: 1000 }).default('NULL'),
	shippingAddress: varchar("shipping_address", { length: 1000 }).default('NULL'),
	city: varchar({ length: 50 }).default('NULL'),
	state: varchar({ length: 50 }).default('NULL'),
	country: varchar({ length: 50 }).default('NULL'),
	zipcode: varchar({ length: 50 }).default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const userDetails = mysqlTable("user_details", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	userId: bigint("user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	openingBalance: double("opening_balance").notNull(),
	openingBalanceType: varchar("opening_balance_type", { length: 20 }).default('\'receive\'').notNull(),
	creditPeriod: int("credit_period").default(0).notNull(),
	creditLimit: double("credit_limit").notNull(),
	purchaseOrderCount: int("purchase_order_count").default(0).notNull(),
	purchaseReturnCount: int("purchase_return_count").default(0).notNull(),
	salesOrderCount: int("sales_order_count").default(0).notNull(),
	salesReturnCount: int("sales_return_count").default(0).notNull(),
	totalAmount: double("total_amount").notNull(),
	paidAmount: double("paid_amount").notNull(),
	dueAmount: double("due_amount").notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const userWarehouse = mysqlTable("user_warehouse", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	userId: bigint("user_id", { mode: "number" }).notNull().references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).notNull().references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});

export const variations = mysqlTable("variations", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	name: varchar({ length: 20 }).notNull(),
	parentId: bigint("parent_id", { mode: "number" }).default('NULL'),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
},
(table) => [
	foreignKey({
			columns: [table.parentId],
			foreignColumns: [table.id],
			name: "variations_parent_id_foreign"
		}).onUpdate("cascade").onDelete("cascade"),
]);

export const warehouses = mysqlTable("warehouses", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references((): AnyMySqlColumn => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	logo: varchar({ length: 191 }).default('NULL'),
	darkLogo: varchar("dark_logo", { length: 191 }).default('NULL'),
	name: varchar({ length: 191 }).notNull(),
	slug: varchar({ length: 191 }).default('NULL'),
	email: varchar({ length: 191 }).notNull(),
	phone: varchar({ length: 191 }).notNull(),
	showEmailOnInvoice: tinyint("show_email_on_invoice").default(0).notNull(),
	showPhoneOnInvoice: tinyint("show_phone_on_invoice").default(0).notNull(),
	address: varchar({ length: 191 }).default('NULL'),
	termsCondition: text("terms_condition").default('NULL'),
	bankDetails: text("bank_details").default('NULL'),
	signature: varchar({ length: 191 }).default('NULL'),
	onlineStoreEnabled: tinyint("online_store_enabled").default(1).notNull(),
	customersVisibility: varchar("customers_visibility", { length: 20 }).default('\'all\'').notNull(),
	suppliersVisibility: varchar("suppliers_visibility", { length: 20 }).default('\'all\'').notNull(),
	productsVisibility: varchar("products_visibility", { length: 20 }).default('\'all\'').notNull(),
	defaultPosOrderStatus: varchar("default_pos_order_status", { length: 20 }).default('\'delivered\'').notNull(),
	showMrpOnInvoice: tinyint("show_mrp_on_invoice").default(1).notNull(),
	showDiscountTaxOnInvoice: tinyint("show_discount_tax_on_invoice").default(1).notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
	barcodeType: varchar("barcode_type", { length: 20 }).default('\'barcode\'').notNull(),
});

export const warehouseHistory = mysqlTable("warehouse_history", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	// you can use { mode: 'date' }, if you want to have Date as type for this column
	date: date({ mode: 'string' }).notNull(),
	warehouseId: bigint("warehouse_id", { mode: "number" }).default('NULL').references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	userId: bigint("user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	orderId: bigint("order_id", { mode: "number" }).default('NULL').references(() => orders.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	orderItemId: bigint("order_item_id", { mode: "number" }).default('NULL').references(() => orderItems.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	productId: bigint("product_id", { mode: "number" }).default('NULL').references(() => products.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	paymentId: bigint("payment_id", { mode: "number" }).default('NULL').references(() => payments.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	expenseId: bigint("expense_id", { mode: "number" }).default('NULL').references(() => expenses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	amount: double().notNull(),
	quantity: double({ precision: 8, scale: 2 }).notNull(),
	status: varchar({ length: 191 }).default('NULL'),
	type: varchar({ length: 191 }).default('NULL'),
	transactionNumber: varchar("transaction_number", { length: 191 }).default('NULL'),
	staffUserId: bigint("staff_user_id", { mode: "number" }).default('NULL').references(() => users.id, { onDelete: "set null", onUpdate: "cascade" } ),
	updatedAt: datetime("updated_at", { mode: 'string'}).notNull(),
});

export const warehouseStocks = mysqlTable("warehouse_stocks", {
	id: bigint({ mode: "number" }).autoincrement().notNull(),
	companyId: bigint("company_id", { mode: "number" }).default('NULL').references(() => companies.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	warehouseId: bigint("warehouse_id", { mode: "number" }).notNull().references(() => warehouses.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	productId: bigint("product_id", { mode: "number" }).notNull().references(() => products.id, { onDelete: "cascade", onUpdate: "cascade" } ),
	stockQuantity: double("stock_quantity", { precision: 8, scale: 2 }).notNull(),
	createdAt: timestamp("created_at", { mode: 'string' }).default('NULL'),
	updatedAt: timestamp("updated_at", { mode: 'string' }).default('NULL'),
});
