# Production Deployment Checklist & Readiness Guide
**Project:** Sugandha Farms and Nursery E-Commerce  
**Platform:** Laravel 13 + PHP 8.4 + MySQL 8.0+

> [!NOTE]  
> This guide is for staging and production readiness preparation. It documents the exact steps and requirements needed to deploy and maintain this application.

---

## 1. Environment & Server Requirements

| Component | Minimum Specification | Notes |
| :--- | :--- | :--- |
| **PHP Version** | `PHP 8.4.x` | Strict type enforcement, modern match and enum support. |
| **Required PHP Extensions** | `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `json`, `mbstring`, `openssl`, `pcre`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `gd` (or `imagick`) | `bcmath` is mandatory for deterministic financial calculations. |
| **Database** | `MySQL 8.0+` or `MariaDB 10.5+` | Requires support for JSON columns, check constraints, and row locking (`FOR UPDATE`). |
| **Web Server** | `Nginx` or `Apache 2.4+` | Must route all traffic through `public/index.php`. |
| **HTTPS Certificate** | Valid SSL/TLS certificate (e.g. Let's Encrypt) | Enforces HSTS and secure cookie transmission. |
| **Node.js (Build)** | `Node.js 20+` & `npm 10+` | Required for building production frontend Vite assets (`npm run build`). |

---

## 2. Deployment Step-by-Step Checklist

### Step 1: Code Deployment
Clone or pull the approved release commit to the target server directory:
```bash
git clone -b main <repository_url> /var/www/nursery-ecommerce
cd /var/www/nursery-ecommerce
```

### Step 2: Composer Dependency Installation
Install production dependencies without dev packages:
```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

### Step 3: Frontend Asset Compilation
Compile production CSS, JavaScript, and generate Vite `manifest.json`:
```bash
npm ci
npm run build
```
*Verify that `public/build/manifest.json` is generated.*

### Step 4: Environment Configuration (.env)
Copy the production environment example and configure production credentials:
```bash
cp .env.example .env
```
Ensure the following critical environment values are set:
- `APP_NAME="Sugandha Farms and Nursery"`
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.com`
- `APP_TIMEZONE=Asia/Kolkata`
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=nursery_ecommerce`
- `DB_USERNAME=<production_user>`
- `DB_PASSWORD=<secure_password>`
- `SESSION_DRIVER=database`
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=database`
- `PAYMENT_GATEWAY=razorpay` (or `null` during staging/testing)
- `RAZORPAY_KEY_ID=<key>`
- `RAZORPAY_KEY_SECRET=<secret>`
- `SMS_DRIVER=log`
- `ECOMMERCE_SHIPPING_FLAT_RATE=0.00`
- `UNPAID_ORDER_EXPIRY_MINUTES=30`

### Step 5: Application Encryption Key
Generate application encryption key (if not already set):
```bash
php artisan key:generate --force
```

### Step 6: Database Setup & Migrations
Run atomic database migrations:
```bash
php artisan migrate --force
```
*Never run `migrate:fresh` or destructive commands in production.*

### Step 7: Storage Symlink
Link the public storage directory:
```bash
php artisan storage:link
```

### Step 8: Production Optimization & Caching
Compile and cache configuration, routes, and views for optimal performance:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 9: Permissions & Ownership
Ensure web server user (e.g., `www-data` / `nginx`) owns storage and bootstrap cache:
```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Step 10: Scheduled Tasks (Cron)
Configure the system cron to run Laravel's scheduler every minute:
```crontab
* * * * * cd /var/www/nursery-ecommerce && php artisan schedule:run >> /dev/null 2>&1
```
*This powers the automated unpaid order expiration (`orders:expire-unpaid`) every 10 minutes and OTP cleanup.*

---

## 3. Smoke Testing & Verification

Immediately post-deployment, verify:
1. **Health Check:** `curl -I https://your-domain.com/up` returns `HTTP 200`.
2. **Robots:** `curl https://your-domain.com/robots.txt` disallows private paths.
3. **Sitemap:** `curl https://your-domain.com/sitemap.xml` returns valid XML.
4. **Security Headers:** Response includes `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Content-Security-Policy`, and `Strict-Transport-Security`.
5. **Storefront Browsing:** Homepage, Shop, Category, and Product detail pages load.
6. **Cart & Checkout:** Add item to cart and verify checkout page loads.
7. **Error Handling:** Visiting an invalid route (e.g. `/non-existent-page`) renders the branded 404 page without stack traces or database information.

---

## 4. Rollback Plan

If a deployment failure occurs:
1. **Restore Previous Release:** Switch the web server root symlink to the previous stable release.
2. **Clear Caches:**
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
3. **Database Considerations:** Phase migrations are additive and backward-compatible. Do not roll back migrations unless strictly necessary and safe for data retention.
