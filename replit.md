# Stockify ERP - Replit.md

## Overview

Stockify is an inventory/ERP management web application (inspired by "Stockifly") that helps businesses manage products, brands, categories, and related business data. The project has two distinct parts:

1. **Active Node.js/React frontend** (`client/` and `server/`) — the primary application being built on Replit, using React + Express + Drizzle ORM with a MySQL backend.
2. **Reference Laravel application** (`laravel/` and `attached_assets/`) — an existing PHP/Vue.js version of Stockifly (v4.3.3) included as reference material/assets. This is **not** the active application; it serves as a design/feature reference.

The active application displays a dashboard with stats, plus pages for Products, Brands, and Categories. It connects to a remote MySQL database.

---

## User Preferences

Preferred communication style: Simple, everyday language.

---

## System Architecture

### Frontend (React/TypeScript)

- **Framework**: React 18 with TypeScript, bundled via Vite
- **Routing**: `wouter` (lightweight client-side routing)
- **State/Data Fetching**: TanStack React Query v5 for server state management; all API calls go through `client/src/lib/queryClient.ts`
- **UI Components**: shadcn/ui (Radix UI primitives + Tailwind CSS) in "new-york" style. Custom core components are exported from `@/components/ui/core`
- **Animations**: Framer Motion for page transitions and UI animations
- **Charts**: Recharts for dashboard data visualization
- **Styling**: Tailwind CSS with CSS custom properties for theming (light/dark mode supported). Fonts: Plus Jakarta Sans (body) and Outfit (display/headings)
- **Path aliases**: `@/` maps to `client/src/`, `@shared/` maps to `shared/`

### Backend (Express/TypeScript)

- **Framework**: Express.js running on Node.js, started via `tsx server/index.ts`
- **Entry point**: `server/index.ts` → registers routes → serves static files in production
- **Routes**: Defined in `server/routes.ts`, using a shared route definition object from `shared/routes.ts` (typed, Zod-validated route contracts)
- **Storage layer**: `server/storage.ts` exposes a `DatabaseStorage` class implementing `IStorage` interface. This keeps database logic separate from route handlers.
- **Development**: Vite dev server runs in middleware mode (via `server/vite.ts`) so both frontend HMR and API calls share a single port

### Shared Layer

- `shared/schema.ts` — Drizzle ORM table definitions (MySQL dialect) for all database tables
- `shared/relations.ts` — Drizzle ORM relation definitions
- `shared/routes.ts` — API route contracts shared between client and server (path, method, Zod response schemas). This ensures type-safe API calls from the frontend.

### Database

- **Database**: MySQL (remote hosted at `193.203.168.212`)
- **ORM**: Drizzle ORM with `mysql2` driver
- **Schema**: Very large schema covering: products, brands, categories, companies, users, attendances, leaves, holidays, awards, appreciations, salaries, warehouses, currencies, subscriptions, and more
- **Migrations**: Managed via `drizzle-kit push` (dialect: mysql). Migration files in `drizzle/` and `migrations/`
- **Connection**: Set via `MYSQL_DATABASE_URL` or `DATABASE_URL` environment variable

### Build System

- **Client build**: Vite outputs to `dist/public/`
- **Server build**: esbuild bundles `server/index.ts` to `dist/index.cjs`, with a curated allowlist of dependencies to bundle (reducing cold start times)
- **Build script**: `script/build.ts` orchestrates both builds sequentially

### API Design Pattern

Routes are defined once in `shared/routes.ts` as typed objects with path, method, and Zod response schemas. Both the server (to implement) and client (to call) reference this same object. This avoids magic strings and provides end-to-end type safety.

Current implemented API endpoints:
- `GET /api/products` — list all products
- `GET /api/products/:id` — get single product
- `GET /api/brands` — list all brands
- `GET /api/categories` — list all categories

Write operations (create, update, delete) are UI mock mutations that show toast errors when the unimplemented endpoints return errors — the UI is built ahead of the backend endpoints.

---

## External Dependencies

### Database
- **MySQL** — Remote MySQL server at `193.203.168.212`, database `u931777367_MEMERPDB`. Connection credentials should be stored in `MYSQL_DATABASE_URL` or `DATABASE_URL` environment variable (not hardcoded in production).

### Key npm Packages
| Package | Purpose |
|---|---|
| `drizzle-orm` + `mysql2` | Database ORM and MySQL driver |
| `express` | HTTP server |
| `@tanstack/react-query` | Client-side data fetching and caching |
| `wouter` | Client-side routing |
| `framer-motion` | Animations and page transitions |
| `recharts` | Charts and data visualization |
| `zod` | Schema validation for API responses |
| `shadcn/ui` (Radix UI) | Accessible UI component primitives |
| `tailwindcss` | Utility-first CSS framework |
| `lucide-react` | Icon set |
| `clsx` + `tailwind-merge` | Class name utilities |
| `vite` | Frontend build tool and dev server |
| `tsx` | TypeScript execution for dev/build scripts |
| `esbuild` | Server bundle build |

### Replit-specific Plugins (dev only)
- `@replit/vite-plugin-runtime-error-modal` — shows runtime errors as overlay
- `@replit/vite-plugin-cartographer` — Replit source mapping
- `@replit/vite-plugin-dev-banner` — Replit dev banner

### Reference/Unused in Active App
The `laravel/` folder contains a full Laravel 12 + Vue 3 application (the original Stockifly). It has its own `composer.json`, `package.json`, and `vite.config.js`. This is **reference only** and should not be modified or deployed. Payment integrations in the Laravel app include: Stripe, PayPal, Razorpay, Mollie, Authorize.Net.