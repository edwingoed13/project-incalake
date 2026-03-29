# Setup Guide - Incalake Full Stack

Step-by-step guide to get the project running from scratch after cloning.

## Prerequisites

| Tool | Version | Check command |
|------|---------|---------------|
| Node.js | >= 18 | `node -v` |
| npm | >= 9 | `npm -v` |
| PHP | >= 8.2 | `php -v` |
| Composer | >= 2 | `composer -V` |
| MySQL | >= 8.0 | `mysql --version` |

### Windows with XAMPP

If using XAMPP, make sure you have a version with PHP 8.2+. The default XAMPP may have an older PHP. You can install XAMPP 8.2 in a separate folder (e.g., `C:\xampp82`) and use its PHP/MySQL.

**Important PHP extensions** (usually enabled by default in XAMPP):
`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `gd`, `zip`

---

## 1. Clone the repository

```bash
git clone https://github.com/edwingoed13/project-incalake.git
cd project-incalake/incalake-full-stack
```

---

## 2. Backend Setup (Laravel 12)

```bash
cd backend
```

### 2.1 Install PHP dependencies

```bash
composer install
```

If you get memory errors:
```bash
COMPOSER_MEMORY_LIMIT=-1 composer install
```

### 2.2 Environment file

Copy the example and edit it:
```bash
cp .env.example .env
```

Edit `.env` and set your database credentials:
```env
DB_DATABASE=incalake_tours
DB_USERNAME=root
DB_PASSWORD=        # empty for XAMPP default
```

> **Note:** Your team lead will provide you with the production `.env` values if needed.

### 2.3 Generate app key

```bash
php artisan key:generate
```

### 2.4 Create the database

Create the database in MySQL before running migrations:

```sql
CREATE DATABASE incalake_tours CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Or via command line:
```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS incalake_tours CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 2.5 Run migrations and seeders

```bash
php artisan migrate
php artisan db:seed
```

This creates:
- All database tables (87 migrations)
- 6 languages (ES, EN, FR, DE, PT, IT)
- Nationalities, age stages, categories
- Roles & permissions (Super Admin, Admin, Seller, Guide)
- Default admin user: `admin@incalake.com` / `password`

### 2.6 Create storage symlink

```bash
php artisan storage:link
```

This creates `public/storage` -> `storage/app/public` so uploaded images are accessible.

### 2.7 Verify backend works

```bash
php artisan serve --port=8001
```

Visit http://localhost:8001/api/languages - you should see JSON with 6 languages.

---

## 3. Frontend Setup (Nuxt 4)

Open a **new terminal**:

```bash
cd frontend
```

### 3.1 Install dependencies

```bash
npm install
```

### 3.2 Environment file

```bash
cp .env.example .env
```

Default values should work for local development (points to `localhost:8001`).

### 3.3 Start frontend

```bash
npm run dev
```

Visit http://localhost:3001/es/tours

---

## 4. Admin Panel Setup (Nuxt 4)

Open a **new terminal**:

```bash
cd admin
```

### 4.1 Install dependencies

```bash
npm install
```

### 4.2 Environment file

```bash
cp .env.example .env
```

### 4.3 Start admin

```bash
npm run dev
```

Visit http://localhost:3000 and login with:
- Email: `admin@incalake.com`
- Password: `password`

---

## 5. Run all services at once (optional)

From the project root:

```bash
npm install          # installs concurrently
npm run install:all  # installs all 3 projects
npm run dev          # starts backend + frontend + admin
```

---

## Quick Reference - URLs

| Service | URL |
|---------|-----|
| Backend API | http://localhost:8001/api |
| Frontend | http://localhost:3001 |
| Admin Panel | http://localhost:3000 |
| phpMyAdmin (if XAMPP) | http://localhost/phpmyadmin |

---

## Common Issues

### "SQLSTATE[HY000] [1049] Unknown database"
Create the database first: `CREATE DATABASE incalake_tours;`

### "php artisan: command not found"
Make sure PHP 8.2+ is in your PATH. On Windows with XAMPP82:
```bash
# Use full path
C:\xampp82\php\php.exe artisan serve --port=8001
```

### "npm ERR! engine" or Node version errors
Install Node.js 18 or later from https://nodejs.org

### Frontend shows blank page or API errors
1. Make sure the backend is running on port 8001
2. Check `.env` files point to `http://localhost:8001/api`
3. Check browser console for CORS errors

### "Class Spatie\Permission not found"
Run `composer install` again. If persists:
```bash
composer dump-autoload
php artisan cache:clear
```

### Images not loading (404)
Run `php artisan storage:link` in the backend directory.

### Admin login fails
Make sure you ran `php artisan db:seed`. The seeder creates the admin user with roles.

### Port already in use
Change ports in the respective commands:
```bash
php artisan serve --port=8002          # backend
npm run dev -- --port 3002             # frontend
npm run dev -- --port 3003             # admin
```
And update the `.env` files accordingly.

---

## Importing Production Data

If your team lead provides a database dump (`.sql` file):

```bash
mysql -u root incalake_tours < dump.sql
```

This replaces the seeded data with real production tours, images, and bookings.

---

## Tech Stack Summary

- **Backend:** Laravel 12 + PHP 8.2 + MySQL + Sanctum (API auth)
- **Frontend:** Nuxt 4 + Vue 3 + Tailwind CSS + Pinia + i18n (6 languages)
- **Admin:** Nuxt 4 + Vue 3 + Tailwind CSS + TipTap (rich text editor)
- **Payments:** Culqi (Peru) + PayPal
- **Maps:** Google Maps API + Leaflet
