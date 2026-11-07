A complete solution for E-commerce Business with exclusive features & super responsive layout.

# Stuquet

php artisan migrate --path=database/migrations/2026_03_31_000001_add_business_category_to_shops_table.php

## Web access switch

Set `WEB_FRONTEND_STATUS=disable` in the application environment to block the
homepage, storefront, and customer/seller/delivery web dashboards with HTTP 503.
Set it to `enable` (the default) to restore web access. After changing it, run
`php artisan config:clear`, or rebuild the configuration cache during deployment.

Admin routes remain protected by their existing authentication and role checks.
Use `/login` to sign in and `/admin` to access the admin dashboard. Login/logout
and password-reset routes remain available. Admin/staff upload and category
helpers and Paystack/Flutterwave web callbacks remain accessible. API endpoints
and `/api/docs` are unaffected. This switch is independent of `artisan down`.

## Continuous integration

GitHub Actions runs `.github/workflows/ci.yml` on pushes, pull requests, and
manual dispatch. It builds the deployment image and a separate test image from
the same Dockerfile using PHP 8.4. The test image runs Composer validation,
platform checks, PHP syntax checks, both migration commands twice, and PHPUnit
against a disposable MySQL 8.0 database. No repository secrets are required.
Local `.env`, vendor dependencies, and generated caches are excluded from builds.

The build context must be the repository root (`.`), with Dockerfile path
`infra/Dockerfile`. For local builds use `docker build -f infra/Dockerfile .`.
Infrastructure files live under `infra/docker`; the root `.dockerignore` still
applies. Set Dokploy's Dockerfile path to `infra/Dockerfile` and build path to `.`.
The deploy workflow defaults to these paths; update any repository variable
`DOCKERFILE_PATH` that still overrides the path with `Dockerfile`.

Dokploy can continue using the default Dockerfile target, which excludes test
dependencies. CI does not publish images or deploy the application.

## Mobile API documentation

Auction and POS routes are temporarily hidden from Swagger; their runtime routes
remain registered. Active-school lookups are available at `GET /api/v2/schools`
and `GET /api/v2/schools/{id}`. The school list supports `search`, `page`, and
`per_page` (1–100, default 20). Seller forms can also use the authenticated
`GET /api/v2/seller/products/schools` alias. Use the returned `id` as `school_id`.
School create/edit/delete remain admin dashboard operations.

Open `/api/docs` on the application host for Swagger UI. Download the
OpenAPI 3.0 specification from `/api/openapi.json` for Postman or mobile tooling.
The documentation and Swagger UI assets are served by the application.

Use **Authorize** to enter the backend-provided `System-Key`. Log in using
`POST /api/v2/auth/login` with `login_by`, `email`, `password`, and `user_type`
(`customer`, `seller`, or `delivery_boy`). Enter the returned `access_token` in
BearerAuth without the `Bearer` prefix. Optional `App-Language` defaults to `en`.
The docs do not embed credentials or retain authorization after refresh.

Operations are grouped into Authentication, Customer and shared, Seller,
Delivery, and Payments, with sidebar filters. The mobile API supports Paystack
and Flutterwave only; other gateway routes, offline payment submissions, and
wallet/COD payment submissions are removed. `/api/v2/payment-types` returns only
enabled Paystack and Flutterwave methods. Web checkout routes are configured separately.
Missing handlers are listed separately and excluded from executable
operations. Request fields and literal validation declarations are read from
controllers and FormRequests; conditional requirements, helper/service inputs,
and response schemas still need manual contract review. Login, signup, and
delivery-status requests include explicit examples. Use staging data for
interactive requests, which call the actual API and can modify records.

After changing routes, controllers, request rules, or documentation generation:

```bash
php artisan docs:generate
php artisan docs:generate --check
```

Commit `resources/docs/openapi.json` with the change. CI checks that it is current.
Swagger UI 5.32.15 is vendored under `public/vendor/swagger-ui` with its license;
its configuration follows the [Swagger UI documentation](https://swagger.io/docs/open-source-tools/swagger-ui/usage/configuration/).

## Database migrations

`migrate` imports `shop.sql` on an empty database and skips the import when
the baseline is already present. It does not run application or package migrations.
`migrate:local` applies the migrations in `database/migrations`, using the same
migration history to skip completed changes. It also ensures the baseline exists
first. An explicit `migrate --path=...` runs only the requested migration path.

Run both commands in order (Docker startup does this with `--force`):

```bash
php artisan migrate
php artisan migrate:local
```

Both commands can be rerun safely; an incomplete baseline stops the process
instead of attempting to overwrite existing tables. Docker stops on migration errors.

## Seller Flow for Food

### Mobile API support

Use the Seller section of `/api/docs`. Food items use the existing product
create/update endpoints with `listing_type: "food"` and a nested `food_meta`
object. Swagger includes a complete example and field enums.

| Operation | Endpoint |
| --- | --- |
| List food items | `GET /api/v2/seller/products/all?listing_type=food&search=rice` |
| Create food item | `POST /api/v2/seller/products/add` |
| Load editor data | `GET /api/v2/seller/products/edit/{id}` |
| Update full food item | `POST /api/v2/seller/products/update/{product}` |
| Quick-update availability, stock, or menu visibility | `POST /api/v2/seller/products/food/{id}/availability` |
| Upload images and obtain upload IDs | `POST /api/v2/seller/file/upload` |
| Publish/unpublish a menu item | `POST /api/v2/seller/product/change-status` |

Food metadata covers short description, ingredients, days/time slots, limited
or unlimited stock, sizes, add-ons, combo deals, delivery/pickup/dine-in options,
prep time, special instructions, automatic availability flags, and manual
available/sold-out overrides. Dietary labels use `tags: ["vegetarian", "spicy"]`.
Image fields use upload IDs, not image URLs. Food requires a positive
`unit_price`; `current_stock` is required for limited stock. `unit` defaults to
`portion`, `min_qty` to 1, and `button` to `publish`.

The quick-update endpoint accepts partial `food_meta`, `published`, and/or
`current_stock`, preserves omitted settings, and replaces submitted nested
arrays. Changing unlimited stock to limited requires an explicit quantity.
It only updates the authenticated seller's food items. Variant-stock changes
use the full product editor instead.

Seller list/edit and customer product list/detail responses include `food_meta`
and `food_availability` (`is_available`, `status`, `timezone`). Availability is
calculated at request time in the application's timezone. Unpublished items
are unavailable; manual overrides take priority over enabled stock/time checks.
Schedules include their start time and exclude their end time, and overnight
slots belong to their starting day.

This exposes the implemented food configuration to mobile clients. Availability
is a display status, not new cart/checkout enforcement. Sizes/add-ons/combos are
configuration records; automatic selection pricing, push/live order tracking,
and additional food analytics are not implemented by these endpoints.

### Adding / Managing a Food Item

#### Step 1: Basic Food Information
- Food Name
- Category
- Short Description + Full Ingredients/Description
- Image upload
- Base Price

#### Step 2: Availability Settings
- Always Available
- Scheduled Availability (Days + Time slots)
- Quantity-Based / Limited Stock

#### Step 3: Variants, Options & Add-ons
- Sizes with pricing
- Add-ons (extra items)
- Combo Deals

#### Step 4: Order Handling & Fulfillment
- Delivery Options
- Prep Time
- Special Instructions

#### Step 5: Auto Status Management
- Availability based on time
- Stock-based `Sold Out`
- Manual override

### Seller Dashboard Features
- Toggle menu/items ON/OFF
- Quick edits
- Real-time order tracking
- Order analytics
- Notifications

### Customer Experience
- Images, pricing, availability
- Prep time
- Variants & add-ons
- Ratings & dietary tags

### Ordering Flow
- Browse Food Section
- Item Detail Page
- Cart & Checkout




corrections

1. create a a system for school that will function like brand also ensure it is under brand in admin dashboard for us to be able to add/edit and delte schools then on services for seller remove brand and put school on the add/edit services screen  

2. remove unit on seller add/edit  services screens 

3. change the Revision text to No of times changes are  Allowed on the add/edit services



under Service Types add the extra • Consultation • One-Time Service • Recurring Service • Installation • Repair & Maintenance • Training / Coaching • Custom Project • On-Site Service • Remote / Virtual Service • Package / Bundle • Subscription • Emergency Service

update Pricing Price Types • Fixed Price • Hourly Rate • Daily Rate • Weekly Rate • Monthly Subscription • Custom Quote • Free Consultation

<!-- Security scan triggered at 2026-08-31 18:22:00 -->
