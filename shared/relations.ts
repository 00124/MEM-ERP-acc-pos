import { relations } from "drizzle-orm/relations";
import { awards, appreciations, companies, users, attendances, holidays, leaves, leaveTypes, basicSalaries, brands, categories, currencies, langs, subscriptionPlans, warehouses, customFields, departments, designations, expenses, expenseCategories, frontProductCards, frontWebsiteSettings, incrementsPromotions, orders, taxes, orderCustomFields, orderItems, products, units, orderItemTaxes, orderPayments, payments, orderShippingAddress, paymentModes, payrolls, payrollComponents, prePayments, permissions, permissionRole, roles, productCustomFields, productDetails, productVariants, variations, roleUser, settings, shifts, stockAdjustments, stockHistory, translations, userAddress, userDetails, userWarehouse, warehouseHistory, warehouseStocks } from "./schema";

export const appreciationsRelations = relations(appreciations, ({one}) => ({
	award: one(awards, {
		fields: [appreciations.awardId],
		references: [awards.id]
	}),
	company: one(companies, {
		fields: [appreciations.companyId],
		references: [companies.id]
	}),
	user_createdBy: one(users, {
		fields: [appreciations.createdBy],
		references: [users.id],
		relationName: "appreciations_createdBy_users_id"
	}),
	user_userId: one(users, {
		fields: [appreciations.userId],
		references: [users.id],
		relationName: "appreciations_userId_users_id"
	}),
}));

export const awardsRelations = relations(awards, ({one, many}) => ({
	appreciations: many(appreciations),
	company: one(companies, {
		fields: [awards.companyId],
		references: [companies.id]
	}),
	user: one(users, {
		fields: [awards.createdBy],
		references: [users.id]
	}),
}));

export const companiesRelations = relations(companies, ({one, many}) => ({
	appreciations: many(appreciations),
	attendances: many(attendances),
	awards: many(awards),
	basicSalaries: many(basicSalaries),
	brands: many(brands),
	categories: many(categories),
	currency: one(currencies, {
		fields: [companies.currencyId],
		references: [currencies.id],
		relationName: "companies_currencyId_currencies_id"
	}),
	lang: one(langs, {
		fields: [companies.langId],
		references: [langs.id]
	}),
	subscriptionPlan: one(subscriptionPlans, {
		fields: [companies.subscriptionPlanId],
		references: [subscriptionPlans.id]
	}),
	warehouse: one(warehouses, {
		fields: [companies.warehouseId],
		references: [warehouses.id],
		relationName: "companies_warehouseId_warehouses_id"
	}),
	currencies: many(currencies, {
		relationName: "currencies_companyId_companies_id"
	}),
	customFields: many(customFields),
	departments: many(departments),
	designations: many(designations),
	expenses: many(expenses),
	expenseCategories: many(expenseCategories),
	frontProductCards: many(frontProductCards),
	frontWebsiteSettings: many(frontWebsiteSettings),
	holidays: many(holidays),
	incrementsPromotions: many(incrementsPromotions),
	leaves: many(leaves),
	leaveTypes: many(leaveTypes),
	orders: many(orders),
	orderPayments: many(orderPayments),
	orderShippingAddresses: many(orderShippingAddress),
	payments: many(payments),
	paymentModes: many(paymentModes),
	payrolls: many(payrolls),
	payrollComponents: many(payrollComponents),
	prePayments: many(prePayments),
	products: many(products),
	roles: many(roles),
	settings: many(settings),
	shifts: many(shifts),
	stockAdjustments: many(stockAdjustments),
	stockHistories: many(stockHistory),
	taxes: many(taxes),
	units: many(units),
	users: many(users),
	userAddresses: many(userAddress),
	variations: many(variations),
	warehouses: many(warehouses, {
		relationName: "warehouses_companyId_companies_id"
	}),
	warehouseHistories: many(warehouseHistory),
	warehouseStocks: many(warehouseStocks),
}));

export const usersRelations = relations(users, ({one, many}) => ({
	appreciations_createdBy: many(appreciations, {
		relationName: "appreciations_createdBy_users_id"
	}),
	appreciations_userId: many(appreciations, {
		relationName: "appreciations_userId_users_id"
	}),
	attendances: many(attendances),
	awards: many(awards),
	basicSalaries: many(basicSalaries),
	departments: many(departments, {
		relationName: "departments_createdBy_users_id"
	}),
	designations: many(designations, {
		relationName: "designations_createdBy_users_id"
	}),
	expenses: many(expenses),
	holidays: many(holidays),
	incrementsPromotions: many(incrementsPromotions),
	leaves: many(leaves),
	leaveTypes: many(leaveTypes),
	orders_cancelledBy: many(orders, {
		relationName: "orders_cancelledBy_users_id"
	}),
	orders_staffUserId: many(orders, {
		relationName: "orders_staffUserId_users_id"
	}),
	orders_userId: many(orders, {
		relationName: "orders_userId_users_id"
	}),
	orderItems: many(orderItems),
	payments_staffUserId: many(payments, {
		relationName: "payments_staffUserId_users_id"
	}),
	payments_userId: many(payments, {
		relationName: "payments_userId_users_id"
	}),
	payrolls_createdBy: many(payrolls, {
		relationName: "payrolls_createdBy_users_id"
	}),
	payrolls_updatedBy: many(payrolls, {
		relationName: "payrolls_updatedBy_users_id"
	}),
	payrolls_userId: many(payrolls, {
		relationName: "payrolls_userId_users_id"
	}),
	payrollComponents: many(payrollComponents),
	prePayments: many(prePayments),
	products: many(products),
	roleUsers: many(roleUser),
	stockAdjustments: many(stockAdjustments),
	stockHistories: many(stockHistory),
	company: one(companies, {
		fields: [users.companyId],
		references: [companies.id]
	}),
	user: one(users, {
		fields: [users.createdBy],
		references: [users.id],
		relationName: "users_createdBy_users_id"
	}),
	users: many(users, {
		relationName: "users_createdBy_users_id"
	}),
	department: one(departments, {
		fields: [users.departmentId],
		references: [departments.id],
		relationName: "users_departmentId_departments_id"
	}),
	designation: one(designations, {
		fields: [users.designationId],
		references: [designations.id],
		relationName: "users_designationId_designations_id"
	}),
	lang: one(langs, {
		fields: [users.langId],
		references: [langs.id]
	}),
	shift: one(shifts, {
		fields: [users.shiftId],
		references: [shifts.id]
	}),
	warehouse: one(warehouses, {
		fields: [users.warehouseId],
		references: [warehouses.id]
	}),
	userAddresses: many(userAddress),
	userDetails: many(userDetails),
	userWarehouses: many(userWarehouse),
	warehouseHistories_staffUserId: many(warehouseHistory, {
		relationName: "warehouseHistory_staffUserId_users_id"
	}),
	warehouseHistories_userId: many(warehouseHistory, {
		relationName: "warehouseHistory_userId_users_id"
	}),
}));

export const attendancesRelations = relations(attendances, ({one}) => ({
	company: one(companies, {
		fields: [attendances.companyId],
		references: [companies.id]
	}),
	holiday: one(holidays, {
		fields: [attendances.holidayId],
		references: [holidays.id]
	}),
	leaf: one(leaves, {
		fields: [attendances.leaveId],
		references: [leaves.id]
	}),
	leaveType: one(leaveTypes, {
		fields: [attendances.leaveTypeId],
		references: [leaveTypes.id]
	}),
	user: one(users, {
		fields: [attendances.userId],
		references: [users.id]
	}),
}));

export const holidaysRelations = relations(holidays, ({one, many}) => ({
	attendances: many(attendances),
	company: one(companies, {
		fields: [holidays.companyId],
		references: [companies.id]
	}),
	user: one(users, {
		fields: [holidays.createdBy],
		references: [users.id]
	}),
}));

export const leavesRelations = relations(leaves, ({one, many}) => ({
	attendances: many(attendances),
	company: one(companies, {
		fields: [leaves.companyId],
		references: [companies.id]
	}),
	leaveType: one(leaveTypes, {
		fields: [leaves.leaveTypeId],
		references: [leaveTypes.id]
	}),
	user: one(users, {
		fields: [leaves.userId],
		references: [users.id]
	}),
}));

export const leaveTypesRelations = relations(leaveTypes, ({one, many}) => ({
	attendances: many(attendances),
	leaves: many(leaves),
	company: one(companies, {
		fields: [leaveTypes.companyId],
		references: [companies.id]
	}),
	user: one(users, {
		fields: [leaveTypes.createdBy],
		references: [users.id]
	}),
}));

export const basicSalariesRelations = relations(basicSalaries, ({one}) => ({
	company: one(companies, {
		fields: [basicSalaries.companyId],
		references: [companies.id]
	}),
	user: one(users, {
		fields: [basicSalaries.userId],
		references: [users.id]
	}),
}));

export const brandsRelations = relations(brands, ({one, many}) => ({
	company: one(companies, {
		fields: [brands.companyId],
		references: [companies.id]
	}),
	products: many(products),
}));

export const categoriesRelations = relations(categories, ({one, many}) => ({
	company: one(companies, {
		fields: [categories.companyId],
		references: [companies.id]
	}),
	category: one(categories, {
		fields: [categories.parentId],
		references: [categories.id],
		relationName: "categories_parentId_categories_id"
	}),
	categories: many(categories, {
		relationName: "categories_parentId_categories_id"
	}),
	products: many(products),
}));

export const currenciesRelations = relations(currencies, ({one, many}) => ({
	companies: many(companies, {
		relationName: "companies_currencyId_currencies_id"
	}),
	company: one(companies, {
		fields: [currencies.companyId],
		references: [companies.id],
		relationName: "currencies_companyId_companies_id"
	}),
}));

export const langsRelations = relations(langs, ({many}) => ({
	companies: many(companies),
	translations: many(translations),
	users: many(users),
}));

export const subscriptionPlansRelations = relations(subscriptionPlans, ({many}) => ({
	companies: many(companies),
}));

export const warehousesRelations = relations(warehouses, ({one, many}) => ({
	companies: many(companies, {
		relationName: "companies_warehouseId_warehouses_id"
	}),
	expenses: many(expenses),
	frontProductCards: many(frontProductCards),
	frontWebsiteSettings: many(frontWebsiteSettings),
	orders_fromWarehouseId: many(orders, {
		relationName: "orders_fromWarehouseId_warehouses_id"
	}),
	orders_warehouseId: many(orders, {
		relationName: "orders_warehouseId_warehouses_id"
	}),
	orderItems: many(orderItems),
	payments: many(payments),
	products: many(products),
	productCustomFields: many(productCustomFields),
	productDetails: many(productDetails),
	stockAdjustments: many(stockAdjustments),
	stockHistories: many(stockHistory),
	users: many(users),
	userDetails: many(userDetails),
	userWarehouses: many(userWarehouse),
	company: one(companies, {
		fields: [warehouses.companyId],
		references: [companies.id],
		relationName: "warehouses_companyId_companies_id"
	}),
	warehouseHistories: many(warehouseHistory),
	warehouseStocks: many(warehouseStocks),
}));

export const customFieldsRelations = relations(customFields, ({one}) => ({
	company: one(companies, {
		fields: [customFields.companyId],
		references: [companies.id]
	}),
}));

export const departmentsRelations = relations(departments, ({one, many}) => ({
	company: one(companies, {
		fields: [departments.companyId],
		references: [companies.id]
	}),
	user: one(users, {
		fields: [departments.createdBy],
		references: [users.id],
		relationName: "departments_createdBy_users_id"
	}),
	users: many(users, {
		relationName: "users_departmentId_departments_id"
	}),
}));

export const designationsRelations = relations(designations, ({one, many}) => ({
	company: one(companies, {
		fields: [designations.companyId],
		references: [companies.id]
	}),
	user: one(users, {
		fields: [designations.createdBy],
		references: [users.id],
		relationName: "designations_createdBy_users_id"
	}),
	incrementsPromotions_currentDesignationId: many(incrementsPromotions, {
		relationName: "incrementsPromotions_currentDesignationId_designations_id"
	}),
	incrementsPromotions_promotedDesignationId: many(incrementsPromotions, {
		relationName: "incrementsPromotions_promotedDesignationId_designations_id"
	}),
	users: many(users, {
		relationName: "users_designationId_designations_id"
	}),
}));

export const expensesRelations = relations(expenses, ({one, many}) => ({
	company: one(companies, {
		fields: [expenses.companyId],
		references: [companies.id]
	}),
	expenseCategory: one(expenseCategories, {
		fields: [expenses.expenseCategoryId],
		references: [expenseCategories.id]
	}),
	user: one(users, {
		fields: [expenses.userId],
		references: [users.id]
	}),
	warehouse: one(warehouses, {
		fields: [expenses.warehouseId],
		references: [warehouses.id]
	}),
	payrollComponents: many(payrollComponents),
	warehouseHistories: many(warehouseHistory),
}));

export const expenseCategoriesRelations = relations(expenseCategories, ({one, many}) => ({
	expenses: many(expenses),
	company: one(companies, {
		fields: [expenseCategories.companyId],
		references: [companies.id]
	}),
}));

export const frontProductCardsRelations = relations(frontProductCards, ({one}) => ({
	company: one(companies, {
		fields: [frontProductCards.companyId],
		references: [companies.id]
	}),
	warehouse: one(warehouses, {
		fields: [frontProductCards.warehouseId],
		references: [warehouses.id]
	}),
}));

export const frontWebsiteSettingsRelations = relations(frontWebsiteSettings, ({one}) => ({
	company: one(companies, {
		fields: [frontWebsiteSettings.companyId],
		references: [companies.id]
	}),
	warehouse: one(warehouses, {
		fields: [frontWebsiteSettings.warehouseId],
		references: [warehouses.id]
	}),
}));

export const incrementsPromotionsRelations = relations(incrementsPromotions, ({one}) => ({
	company: one(companies, {
		fields: [incrementsPromotions.companyId],
		references: [companies.id]
	}),
	designation_currentDesignationId: one(designations, {
		fields: [incrementsPromotions.currentDesignationId],
		references: [designations.id],
		relationName: "incrementsPromotions_currentDesignationId_designations_id"
	}),
	designation_promotedDesignationId: one(designations, {
		fields: [incrementsPromotions.promotedDesignationId],
		references: [designations.id],
		relationName: "incrementsPromotions_promotedDesignationId_designations_id"
	}),
	user: one(users, {
		fields: [incrementsPromotions.userId],
		references: [users.id]
	}),
}));

export const ordersRelations = relations(orders, ({one, many}) => ({
	user_cancelledBy: one(users, {
		fields: [orders.cancelledBy],
		references: [users.id],
		relationName: "orders_cancelledBy_users_id"
	}),
	company: one(companies, {
		fields: [orders.companyId],
		references: [companies.id]
	}),
	warehouse_fromWarehouseId: one(warehouses, {
		fields: [orders.fromWarehouseId],
		references: [warehouses.id],
		relationName: "orders_fromWarehouseId_warehouses_id"
	}),
	order: one(orders, {
		fields: [orders.parentOrderId],
		references: [orders.id],
		relationName: "orders_parentOrderId_orders_id"
	}),
	orders: many(orders, {
		relationName: "orders_parentOrderId_orders_id"
	}),
	user_staffUserId: one(users, {
		fields: [orders.staffUserId],
		references: [users.id],
		relationName: "orders_staffUserId_users_id"
	}),
	tax: one(taxes, {
		fields: [orders.taxId],
		references: [taxes.id]
	}),
	user_userId: one(users, {
		fields: [orders.userId],
		references: [users.id],
		relationName: "orders_userId_users_id"
	}),
	warehouse_warehouseId: one(warehouses, {
		fields: [orders.warehouseId],
		references: [warehouses.id],
		relationName: "orders_warehouseId_warehouses_id"
	}),
	orderCustomFields: many(orderCustomFields),
	orderItems: many(orderItems),
	orderItemTaxes: many(orderItemTaxes),
	orderPayments: many(orderPayments),
	orderShippingAddresses: many(orderShippingAddress),
	warehouseHistories: many(warehouseHistory),
}));

export const taxesRelations = relations(taxes, ({one, many}) => ({
	orders: many(orders),
	orderItems: many(orderItems),
	orderItemTaxes: many(orderItemTaxes),
	productDetails: many(productDetails),
	company: one(companies, {
		fields: [taxes.companyId],
		references: [companies.id]
	}),
	tax: one(taxes, {
		fields: [taxes.parentId],
		references: [taxes.id],
		relationName: "taxes_parentId_taxes_id"
	}),
	taxes: many(taxes, {
		relationName: "taxes_parentId_taxes_id"
	}),
}));

export const orderCustomFieldsRelations = relations(orderCustomFields, ({one}) => ({
	order: one(orders, {
		fields: [orderCustomFields.orderId],
		references: [orders.id]
	}),
}));

export const orderItemsRelations = relations(orderItems, ({one, many}) => ({
	order: one(orders, {
		fields: [orderItems.orderId],
		references: [orders.id]
	}),
	product: one(products, {
		fields: [orderItems.productId],
		references: [products.id]
	}),
	tax: one(taxes, {
		fields: [orderItems.taxId],
		references: [taxes.id]
	}),
	unit: one(units, {
		fields: [orderItems.unitId],
		references: [units.id]
	}),
	user: one(users, {
		fields: [orderItems.userId],
		references: [users.id]
	}),
	warehouse: one(warehouses, {
		fields: [orderItems.warehouseId],
		references: [warehouses.id]
	}),
	orderItemTaxes: many(orderItemTaxes),
	warehouseHistories: many(warehouseHistory),
}));

export const productsRelations = relations(products, ({one, many}) => ({
	orderItems: many(orderItems),
	brand: one(brands, {
		fields: [products.brandId],
		references: [brands.id]
	}),
	category: one(categories, {
		fields: [products.categoryId],
		references: [categories.id]
	}),
	company: one(companies, {
		fields: [products.companyId],
		references: [companies.id]
	}),
	product: one(products, {
		fields: [products.parentId],
		references: [products.id],
		relationName: "products_parentId_products_id"
	}),
	products: many(products, {
		relationName: "products_parentId_products_id"
	}),
	unit: one(units, {
		fields: [products.unitId],
		references: [units.id]
	}),
	user: one(users, {
		fields: [products.userId],
		references: [users.id]
	}),
	warehouse: one(warehouses, {
		fields: [products.warehouseId],
		references: [warehouses.id]
	}),
	productCustomFields: many(productCustomFields),
	productDetails: many(productDetails),
	productVariants: many(productVariants),
	stockAdjustments: many(stockAdjustments),
	stockHistories: many(stockHistory),
	warehouseHistories: many(warehouseHistory),
	warehouseStocks: many(warehouseStocks),
}));

export const unitsRelations = relations(units, ({one, many}) => ({
	orderItems: many(orderItems),
	products: many(products),
	company: one(companies, {
		fields: [units.companyId],
		references: [companies.id]
	}),
	unit: one(units, {
		fields: [units.parentId],
		references: [units.id],
		relationName: "units_parentId_units_id"
	}),
	units: many(units, {
		relationName: "units_parentId_units_id"
	}),
}));

export const orderItemTaxesRelations = relations(orderItemTaxes, ({one}) => ({
	order: one(orders, {
		fields: [orderItemTaxes.orderId],
		references: [orders.id]
	}),
	orderItem: one(orderItems, {
		fields: [orderItemTaxes.orderItemId],
		references: [orderItems.id]
	}),
	tax: one(taxes, {
		fields: [orderItemTaxes.taxId],
		references: [taxes.id]
	}),
}));

export const orderPaymentsRelations = relations(orderPayments, ({one}) => ({
	company: one(companies, {
		fields: [orderPayments.companyId],
		references: [companies.id]
	}),
	order: one(orders, {
		fields: [orderPayments.orderId],
		references: [orders.id]
	}),
	payment: one(payments, {
		fields: [orderPayments.paymentId],
		references: [payments.id]
	}),
}));

export const paymentsRelations = relations(payments, ({one, many}) => ({
	orderPayments: many(orderPayments),
	company: one(companies, {
		fields: [payments.companyId],
		references: [companies.id]
	}),
	paymentMode: one(paymentModes, {
		fields: [payments.paymentModeId],
		references: [paymentModes.id]
	}),
	user_staffUserId: one(users, {
		fields: [payments.staffUserId],
		references: [users.id],
		relationName: "payments_staffUserId_users_id"
	}),
	user_userId: one(users, {
		fields: [payments.userId],
		references: [users.id],
		relationName: "payments_userId_users_id"
	}),
	warehouse: one(warehouses, {
		fields: [payments.warehouseId],
		references: [warehouses.id]
	}),
	warehouseHistories: many(warehouseHistory),
}));

export const orderShippingAddressRelations = relations(orderShippingAddress, ({one}) => ({
	company: one(companies, {
		fields: [orderShippingAddress.companyId],
		references: [companies.id]
	}),
	order: one(orders, {
		fields: [orderShippingAddress.orderId],
		references: [orders.id]
	}),
}));

export const paymentModesRelations = relations(paymentModes, ({one, many}) => ({
	payments: many(payments),
	company: one(companies, {
		fields: [paymentModes.companyId],
		references: [companies.id]
	}),
	payrolls: many(payrolls),
	prePayments: many(prePayments),
}));

export const payrollsRelations = relations(payrolls, ({one, many}) => ({
	company: one(companies, {
		fields: [payrolls.companyId],
		references: [companies.id]
	}),
	user_createdBy: one(users, {
		fields: [payrolls.createdBy],
		references: [users.id],
		relationName: "payrolls_createdBy_users_id"
	}),
	paymentMode: one(paymentModes, {
		fields: [payrolls.paymentModeId],
		references: [paymentModes.id]
	}),
	user_updatedBy: one(users, {
		fields: [payrolls.updatedBy],
		references: [users.id],
		relationName: "payrolls_updatedBy_users_id"
	}),
	user_userId: one(users, {
		fields: [payrolls.userId],
		references: [users.id],
		relationName: "payrolls_userId_users_id"
	}),
	payrollComponents: many(payrollComponents),
}));

export const payrollComponentsRelations = relations(payrollComponents, ({one}) => ({
	company: one(companies, {
		fields: [payrollComponents.companyId],
		references: [companies.id]
	}),
	expense: one(expenses, {
		fields: [payrollComponents.expenseId],
		references: [expenses.id]
	}),
	payroll: one(payrolls, {
		fields: [payrollComponents.payrollId],
		references: [payrolls.id]
	}),
	prePayment: one(prePayments, {
		fields: [payrollComponents.prePaymentId],
		references: [prePayments.id]
	}),
	user: one(users, {
		fields: [payrollComponents.userId],
		references: [users.id]
	}),
}));

export const prePaymentsRelations = relations(prePayments, ({one, many}) => ({
	payrollComponents: many(payrollComponents),
	company: one(companies, {
		fields: [prePayments.companyId],
		references: [companies.id]
	}),
	paymentMode: one(paymentModes, {
		fields: [prePayments.paymentModeId],
		references: [paymentModes.id]
	}),
	user: one(users, {
		fields: [prePayments.userId],
		references: [users.id]
	}),
}));

export const permissionRoleRelations = relations(permissionRole, ({one}) => ({
	permission: one(permissions, {
		fields: [permissionRole.permissionId],
		references: [permissions.id]
	}),
	role: one(roles, {
		fields: [permissionRole.roleId],
		references: [roles.id]
	}),
}));

export const permissionsRelations = relations(permissions, ({many}) => ({
	permissionRoles: many(permissionRole),
}));

export const rolesRelations = relations(roles, ({one, many}) => ({
	permissionRoles: many(permissionRole),
	company: one(companies, {
		fields: [roles.companyId],
		references: [companies.id]
	}),
	roleUsers: many(roleUser),
}));

export const productCustomFieldsRelations = relations(productCustomFields, ({one}) => ({
	product: one(products, {
		fields: [productCustomFields.productId],
		references: [products.id]
	}),
	warehouse: one(warehouses, {
		fields: [productCustomFields.warehouseId],
		references: [warehouses.id]
	}),
}));

export const productDetailsRelations = relations(productDetails, ({one}) => ({
	product: one(products, {
		fields: [productDetails.productId],
		references: [products.id]
	}),
	tax: one(taxes, {
		fields: [productDetails.taxId],
		references: [taxes.id]
	}),
	warehouse: one(warehouses, {
		fields: [productDetails.warehouseId],
		references: [warehouses.id]
	}),
}));

export const productVariantsRelations = relations(productVariants, ({one}) => ({
	product: one(products, {
		fields: [productVariants.productId],
		references: [products.id]
	}),
	variation_variantId: one(variations, {
		fields: [productVariants.variantId],
		references: [variations.id],
		relationName: "productVariants_variantId_variations_id"
	}),
	variation_variantValueId: one(variations, {
		fields: [productVariants.variantValueId],
		references: [variations.id],
		relationName: "productVariants_variantValueId_variations_id"
	}),
}));

export const variationsRelations = relations(variations, ({one, many}) => ({
	productVariants_variantId: many(productVariants, {
		relationName: "productVariants_variantId_variations_id"
	}),
	productVariants_variantValueId: many(productVariants, {
		relationName: "productVariants_variantValueId_variations_id"
	}),
	company: one(companies, {
		fields: [variations.companyId],
		references: [companies.id]
	}),
	variation: one(variations, {
		fields: [variations.parentId],
		references: [variations.id],
		relationName: "variations_parentId_variations_id"
	}),
	variations: many(variations, {
		relationName: "variations_parentId_variations_id"
	}),
}));

export const roleUserRelations = relations(roleUser, ({one}) => ({
	role: one(roles, {
		fields: [roleUser.roleId],
		references: [roles.id]
	}),
	user: one(users, {
		fields: [roleUser.userId],
		references: [users.id]
	}),
}));

export const settingsRelations = relations(settings, ({one}) => ({
	company: one(companies, {
		fields: [settings.companyId],
		references: [companies.id]
	}),
}));

export const shiftsRelations = relations(shifts, ({one, many}) => ({
	company: one(companies, {
		fields: [shifts.companyId],
		references: [companies.id]
	}),
	users: many(users),
}));

export const stockAdjustmentsRelations = relations(stockAdjustments, ({one}) => ({
	company: one(companies, {
		fields: [stockAdjustments.companyId],
		references: [companies.id]
	}),
	user: one(users, {
		fields: [stockAdjustments.createdBy],
		references: [users.id]
	}),
	product: one(products, {
		fields: [stockAdjustments.productId],
		references: [products.id]
	}),
	warehouse: one(warehouses, {
		fields: [stockAdjustments.warehouseId],
		references: [warehouses.id]
	}),
}));

export const stockHistoryRelations = relations(stockHistory, ({one}) => ({
	company: one(companies, {
		fields: [stockHistory.companyId],
		references: [companies.id]
	}),
	user: one(users, {
		fields: [stockHistory.createdBy],
		references: [users.id]
	}),
	product: one(products, {
		fields: [stockHistory.productId],
		references: [products.id]
	}),
	warehouse: one(warehouses, {
		fields: [stockHistory.warehouseId],
		references: [warehouses.id]
	}),
}));

export const translationsRelations = relations(translations, ({one}) => ({
	lang: one(langs, {
		fields: [translations.langId],
		references: [langs.id]
	}),
}));

export const userAddressRelations = relations(userAddress, ({one}) => ({
	company: one(companies, {
		fields: [userAddress.companyId],
		references: [companies.id]
	}),
	user: one(users, {
		fields: [userAddress.userId],
		references: [users.id]
	}),
}));

export const userDetailsRelations = relations(userDetails, ({one}) => ({
	user: one(users, {
		fields: [userDetails.userId],
		references: [users.id]
	}),
	warehouse: one(warehouses, {
		fields: [userDetails.warehouseId],
		references: [warehouses.id]
	}),
}));

export const userWarehouseRelations = relations(userWarehouse, ({one}) => ({
	user: one(users, {
		fields: [userWarehouse.userId],
		references: [users.id]
	}),
	warehouse: one(warehouses, {
		fields: [userWarehouse.warehouseId],
		references: [warehouses.id]
	}),
}));

export const warehouseHistoryRelations = relations(warehouseHistory, ({one}) => ({
	company: one(companies, {
		fields: [warehouseHistory.companyId],
		references: [companies.id]
	}),
	expense: one(expenses, {
		fields: [warehouseHistory.expenseId],
		references: [expenses.id]
	}),
	order: one(orders, {
		fields: [warehouseHistory.orderId],
		references: [orders.id]
	}),
	orderItem: one(orderItems, {
		fields: [warehouseHistory.orderItemId],
		references: [orderItems.id]
	}),
	payment: one(payments, {
		fields: [warehouseHistory.paymentId],
		references: [payments.id]
	}),
	product: one(products, {
		fields: [warehouseHistory.productId],
		references: [products.id]
	}),
	user_staffUserId: one(users, {
		fields: [warehouseHistory.staffUserId],
		references: [users.id],
		relationName: "warehouseHistory_staffUserId_users_id"
	}),
	user_userId: one(users, {
		fields: [warehouseHistory.userId],
		references: [users.id],
		relationName: "warehouseHistory_userId_users_id"
	}),
	warehouse: one(warehouses, {
		fields: [warehouseHistory.warehouseId],
		references: [warehouses.id]
	}),
}));

export const warehouseStocksRelations = relations(warehouseStocks, ({one}) => ({
	company: one(companies, {
		fields: [warehouseStocks.companyId],
		references: [companies.id]
	}),
	product: one(products, {
		fields: [warehouseStocks.productId],
		references: [products.id]
	}),
	warehouse: one(warehouses, {
		fields: [warehouseStocks.warehouseId],
		references: [warehouses.id]
	}),
}));