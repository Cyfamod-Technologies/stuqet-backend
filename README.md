A complete solution for E-commerce Business with exclusive features & super responsive layout.

# Stuquet

php artisan migrate --path=database/migrations/2026_03_31_000001_add_business_category_to_shops_table.php

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
