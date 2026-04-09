# Stocklify / MaShaAllah Electronics ERP — Replit.md

## Overview

**Stocklify** is a full-featured ERP (Enterprise Resource Planning) and POS (Point of Sale) system built for **MaShaAllah Electronics**, a Home Appliances & Electronics retail business.

The system is built with **Laravel (PHP) + Vue.js + Ant Design** and connects to a remote **MySQL database hosted on Hostinger**.

---

## Repository Structure

```
/
├── laravel/          ← Main Laravel ERP application (active)
├── attached_assets/  ← Uploaded reference images and files
└── replit.md         ← This file
```

---

## User Preferences

Preferred communication style: Simple, everyday language.

---

## System Architecture

### Backend — Laravel (PHP 8.2)

- **Framework**: Laravel 12
- **Entry point**: `laravel/public/index.php` via `laravel/server.php`
- **API**: RESTful JSON API under `/api/v1/` using the `examyou/rest-api` package
- **Authentication**: Token-based (Bearer token), managed by `ApiAuthMiddleware`
- **Server**: PHP built-in server (`php -S 0.0.0.0:5000 -t public server.php`) with 2 workers

### Frontend — Vue.js 3

- **Framework**: Vue 3 with Composition API (`defineComponent` + `setup()`)
- **UI Library**: Ant Design Vue (`ant-design-vue`)
- **Icons**: `@ant-design/icons-vue`
- **Build tool**: Vite (outputs to `laravel/public/build/`)
- **Routing**: Vue Router (`laravel/resources/js/main/router/`)
- **HTTP client**: Axios — always use `window.axiosAdmin` (pre-configured with Bearer token and `/api/v1` base URL)
- **State**: Vuex store

### Database — MySQL (Hostinger)

- **Host**: `193.203.168.212`
- **Database**: `u931777367_MEMERPDB`
- **User**: `u931777367_MEMERP`
- **ORM**: Eloquent (Laravel)
- **Credentials**: Stored in `laravel/.env`

---

## Business Data

- **Company**: MaShaAllah Electronics (Model Town)
- **Product categories**: 54
- **Brands**: 100+
- **Products**: 800+
- **Company ID**: 1, Warehouse ID: 1

---

## Modules

| Module | Description |
|---|---|
| **POS** | Point of Sale terminal for walk-in customers |
| **Sales** | Sales orders, invoices, receipts |
| **Purchases** | Purchase orders from suppliers |
| **Inventory** | Stock management, adjustments, transfers |
| **HRM** | HR module: employees, attendance, payroll |
| **Accounting** | Full double-entry accounting system |
| **Reports** | Sales, purchase, financial reports |
| **Settings** | Company, warehouse, user, currency settings |

---

## Accounting Module (Custom Built)

Full double-entry accounting with automatic journal entries:

- **Chart of Accounts** (COA) — hierarchical, per-company
- **Journal Entries** — manual double-entry
- **Reports**: Trial Balance, Profit & Loss, Balance Sheet, General Ledger, Customer Ledger, Supplier Ledger
- **Category Mapping** — maps each product category to 4 COA accounts (Sales Revenue, COGS, Inventory Asset, Purchase Asset)

### Key Account IDs (company_id = 1)
- Cash: account `11001` (id 7)
- Bank: account `11002` (id 8)
- Accounts Receivable: account `12001` (id 10)
- Accounts Payable: account `21001` (id 19)

### Critical Development Notes

1. **Always use `window.axiosAdmin`** in Vue setup functions — never plain `axios`. It carries the Bearer token and has `/api/v1` as baseURL.
2. **Relative URLs** — use `'accounting/coa'` not `'/api/v1/accounting/coa'` since axiosAdmin has baseURL set.
3. **`ApiResponse::make` requires plain arrays** — always call `.toArray()` on Eloquent Paginators and Collections before passing to `sendResponse()`.
4. **Category model hides `id`** — the `Category` model has `$hidden = ['id']`. Always map Eloquent Category collections to plain PHP arrays explicitly to expose the real `id`.
5. **Frontend patches** — after each `vite build`, apply two sed patches to `public/build/assets/app-*.js`:
   ```bash
   sed -i 's/verified_name:Wd,value:!1}/verified_name:Wd,value:!0}/g' app-*.js
   sed -i 's/appChecking:!0,em/appChecking:!1,em/g' app-*.js
   ```

---

## Key Files

| File | Purpose |
|---|---|
| `laravel/app/Http/Controllers/Api/AccountingController.php` | All accounting API endpoints |
| `laravel/app/Services/AccountingService.php` | Auto-journal logic for sales/purchases |
| `laravel/resources/js/main/views/accounting/` | All accounting Vue pages |
| `laravel/resources/js/main/router/accounting.js` | Accounting routes |
| `laravel/resources/js/common/layouts/LeftSidebar.vue` | Main navigation sidebar |
| `laravel/routes/web.php` | All API route definitions |
| `laravel/.env` | Environment config (DB credentials, app key) |

---

## Workflow

The app runs via the **"Start application"** workflow:

```bash
cd /home/runner/workspace/laravel && PHP_CLI_SERVER_WORKERS=2 php -S 0.0.0.0:5000 -t public server.php
```

Port 5000 is mapped to external port 80.

## Setup Notes (Replit Migration)

- `laravel/.env` is created from `.env.example` with MySQL credentials and APP_URL set to `http://localhost:5000`
- `REPLIT_DOMAINS` secret is used by `AppServiceProvider` to force HTTPS URLs when running on Replit
- Composer dependencies installed via `composer install` in the `laravel/` directory
- Frontend assets built via `npm install && npm run build` in the `laravel/` directory
- License/product key check permanently removed from source: `appChecking` defaults to `false` in `resources/js/main/store/auth.js`; external Codeifly verification calls removed from `resources/js/common/composable/modules.js`
- `laravel/server.php` updated to add CORS headers for static files (Replit proxy compatibility)

---

## Admin Credentials

- **Email**: `123asaid@gmail.com`
- **Password**: `Admin@1234`

---

## External Packages (Laravel)

| Package | Purpose |
|---|---|
| `examyou/rest-api` | REST API response helpers and exceptions |
| `spatie/laravel-permission` | Role/permission management |
| `intervention/image` | Image processing |
| `maatwebsite/excel` | Excel import/export |
| `barryvdh/laravel-dompdf` | PDF generation |
