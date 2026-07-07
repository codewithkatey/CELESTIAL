# Celestial — Clothing Inventory Management

A modern web-based inventory and product management system for clothing businesses. Built with **Laravel**, **jQuery**, and **AJAX**, using **JSON file storage** instead of a database — ideal for deployment on **Vercel**.

## Features

- **Product Management** — Add, edit, delete clothing items with SKU, pricing, sizes, colors, and stock
- **Category Organization** — Create and manage product categories
- **Image Upload** — Real-time image preview with base64 storage (Vercel-compatible)
- **Live Search & Filters** — Search by name/SKU, filter by category and status
- **Dashboard Stats** — Total products, active count, low stock alerts, inventory value
- **AJAX-Powered UI** — Smooth, real-time updates without page reloads
- **JSON Storage** — No MySQL or external database required

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 10 (PHP 8.1+) |
| Storage | JSON files (`storage/app/data/`) |
| Frontend | Blade, jQuery 3.7, AJAX |
| Styling | Custom CSS (dark luxury theme) |
| Deployment | Vercel (vercel-php runtime) |

## Local Development

### Requirements

- PHP 8.1+
- Composer

### Setup

```bash
# Install dependencies
composer install

# Copy environment file (if needed)
cp .env.example .env

# Generate app key
php artisan key:generate

# Start development server
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000) in your browser.

### Data Storage

Products and categories are stored as JSON files:

- `storage/app/data/products.json`
- `storage/app/data/categories.json`

Sample data is included. Images are stored as base64 data URLs inside the product JSON for Vercel compatibility.

## Deploy to Vercel

### 1. Push to GitHub

```bash
git init
git add .
git commit -m "Initial commit: Celestial inventory system"
git remote add origin <your-repo-url>
git push -u origin main
```

### 2. Import on Vercel

1. Go to [vercel.com](https://vercel.com) and import your repository
2. Vercel will detect the `vercel.json` configuration automatically

### 3. Environment Variables

Add these in your Vercel project settings:

| Variable | Value |
|----------|-------|
| `APP_KEY` | Run `php artisan key:generate --show` locally and paste the key |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | Your Vercel URL (e.g. `https://your-app.vercel.app`) |

The `vercel.json` file already configures cache paths, logging, and JSON data directory (`/tmp/celestial-data`).

### Vercel Notes

- Vercel serverless functions use `/tmp` for writable storage
- Data persists within the same function instance but resets on cold starts
- For production persistence, consider Vercel KV, Blob storage, or an external API

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/products` | List products (supports `search`, `category_id`, `status` query params) |
| GET | `/api/products/{id}` | Get single product |
| POST | `/api/products` | Create product |
| POST | `/api/products/{id}` | Update product |
| DELETE | `/api/products/{id}` | Delete product |
| GET | `/api/categories` | List categories |
| POST | `/api/categories` | Create category |
| PUT | `/api/categories/{id}` | Update category |
| DELETE | `/api/categories/{id}` | Delete category |

## Project Structure

```
app/
├── Http/Controllers/
│   ├── DashboardController.php
│   ├── ProductController.php
│   └── CategoryController.php
└── Services/
    └── JsonStorage.php          # JSON file read/write service
storage/app/data/
├── products.json                # Product inventory data
└── categories.json              # Category data
resources/views/
├── layouts/app.blade.php
└── inventory/index.blade.php
public/
├── css/app.css
└── js/inventory.js
api/index.php                    # Vercel serverless entry point
vercel.json                      # Vercel deployment config
```

## License

MIT
