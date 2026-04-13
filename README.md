# Production Sales Tracking System

Native PHP + MySQL + Tailwind + Chart.js + Midtrans Snap.

## Run
1. Place folder in `C:\xampp\htdocs\LINK`
2. Start Apache + MySQL
3. Configure `.env` Midtrans keys
4. Open `http://localhost/LINK/install.php` and click **Run Setup**

## Main Routes
- Public checkout: `http://localhost/LINK/index.php`
- Admin login: `http://localhost/LINK/admin/login.php`
- Admin dashboard: `http://localhost/LINK/admin/dashboard.php`
- Seller manager: `http://localhost/LINK/admin/sellers.php`
- Seller links: `http://localhost/LINK/admin/seller_links.php`
- CSV export: `http://localhost/LINK/admin/export.php`
- Seller login: `http://localhost/LINK/seller/login.php`

## Credentials
- Admin: `admin` / `admin123`
- Default Seller: `seller1` / `seller123`

## Features
- Multi seller with commission rate
- Up to 10 links per seller (`seller_links`)
- Link tracking via `index.php?ref=link_code`
- Auto assign seller and link to transactions
- Midtrans Snap payment + callback update
- Transfer receipt upload (`/uploads/`)
- Admin analytics (seller/link/revenue charts)
- Seller dashboard (sales, commission, per-link stats)
- CSV export
- Reset transactions/all data via POST actions
