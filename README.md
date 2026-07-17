# Tshirt Store

A bilingual (English/Hebrew) e-commerce platform for a Jewish-identity streetwear
brand. Laravel API backend, React SPA frontend, PayPal checkout, and a
human-in-the-loop approval workflow for new designs and orders.

Live at [store.guykats.com](https://store.guykats.com).

## Stack

- **Backend:** Laravel 13 (PHP 8.3), MySQL in production / SQLite for local dev and tests
- **Frontend:** React 18 SPA (Vite), Tailwind CSS v4, `react-i18next` for en/he with RTL support
- **Auth:** Laravel Sanctum, SPA session-cookie based (not token auth)
- **Payments:** PayPal Orders v2 REST API via a small custom client (`app/Services/PayPalClient.php`) — no SDK dependency
- **PDF/Email:** `barryvdh/laravel-dompdf` for bilingual invoices, Laravel Mail for localized order confirmations
- **Deploy:** GitHub Actions builds the frontend and deploys to Hostinger over SSH on every push to `main`

## Architecture

The Laravel app serves a single Blade view (`resources/views/app.blade.php`) that
mounts the React SPA. All application data flows through a JSON API under
`/api/*`; there's no server-rendered page content beyond that shell.

```
resources/js/
  App.jsx           routes
  pages/            Catalog, ProductDetail, Login, Register, Checkout, Dashboard
  components/       DesignArt (SVG motif renderer — no raster image assets)
  lib/              api client, auth context
  i18n/             en/he translation strings
  hooks/            useDocumentMeta (per-page title/meta description)

app/
  Http/Controllers/Api/   all API endpoints
  Models/                 Eloquent models (mass assignment via #[Fillable] attributes)
  Services/               PayPalClient, InvoiceService
  Mail/                   OrderConfirmationMail
```

Key flows:

- **Catalog → checkout:** products are public (`GET /api/products`); checkout,
  orders, and the dashboard require an authenticated Sanctum session.
- **Design/order approval:** new designs and orders start in `pending_approval`
  and only become visible/active once an admin approves them from `/dashboard`.
  Every approval is written to `system_events` for a permanent audit trail.
- **Team progress dashboard:** `/dashboard` also shows an "Agent Status" board
  (manually-set task descriptions) and a "Recent Activity" feed that reads
  `git log` live from the server on every request, so it can't go stale.

## Local setup

Requirements: PHP 8.3, Composer, Node 22, and either MySQL or SQLite.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

For local dev, SQLite is simplest — point `.env` at a file and skip installing MySQL:

```bash
touch database/database.sqlite
# in .env: DB_CONNECTION=sqlite  (remove/ignore the other DB_* vars)
```

Run migrations and seed demo data (7 products with original SVG artwork, a demo
admin, a demo customer, and a sample order):

```bash
php artisan migrate --seed
```

Seeded accounts (password `password` for both):

- `admin@tshirt-store.test` — admin, can access `/dashboard`
- `customer@tshirt-store.test` — regular customer

Start both dev servers:

```bash
php artisan serve      # backend, http://127.0.0.1:8000
npm run dev             # Vite dev server with HMR
```

To create a real admin account instead of using the seeded one:

```bash
php artisan app:create-admin
```

## Testing

```bash
php artisan test
```

Feature tests cover auth, product listing, checkout, and the design/order
approval flows. PayPal calls are mocked (`Mockery`) so the suite runs without
real credentials. CI runs the same suite on PHP 8.3/8.4/8.5 via
`.github/workflows/tests.yml` — it builds the frontend first since the SPA
shell's `@vite` directive needs a manifest even to serve `GET /`.

## Environment variables

See `.env.example` for the full list. The ones specific to this app:

| Variable | Purpose |
|---|---|
| `SANCTUM_STATEFUL_DOMAINS` | Domains allowed to authenticate via session cookie — must match the frontend's origin exactly (including port) or Sanctum silently skips session startup |
| `CORS_ALLOWED_ORIGINS` | Frontend origins allowed to call the API cross-origin |
| `PAYPAL_MODE`, `PAYPAL_CLIENT_ID`, `PAYPAL_CLIENT_SECRET`, `PAYPAL_WEBHOOK_ID` | PayPal sandbox/live credentials |
| `VITE_PAYPAL_CLIENT_ID` | Same client ID, exposed to the frontend build |
| `MAIL_*` | SMTP config for order confirmation emails |

Without PayPal/SMTP credentials configured, checkout and email sending degrade
gracefully rather than crashing — useful for demoing the rest of the app.

## Deployment

Pushing to `main` triggers `.github/workflows/deploy.yml`, which builds the
frontend on the GitHub runner, then SSHes into the production server to pull
the latest code, run `composer install` and `php artisan migrate --force`,
upload the built assets, and recache config/routes/views.
