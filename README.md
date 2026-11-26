# RealEstate CRM (PHP + MySQL)

A lightweight, daily-use **Real Estate CRM** built with **PHP 8** and **MySQL (PDO)**.

## Features
- **Authentication**: Session-based login (`admin` / `admin123` default)
- **Dashboard**: KPI stats, Today's Follow-ups, Recent Leads, Lead Sources chart, and Activity Log
- **Leads**: Full CRUD, search & status filter, **Smart Property Matchmaker** with `% Match` & WhatsApp Pitch, and CSV Export
- **Deal Pipeline**: 6-stage Kanban board (`New` → `Contacted` → `Site Visit` → `Negotiation` → `Closed` → `Lost`) with stage budget totals
- **Follow-ups**: `Today`, `Overdue`, `Upcoming`, and `Done` tabs with quick scheduling
- **Properties**: Apartment, Villa, Plot, Commercial listings with **EMI & Brokerage Commission Calculator** and CSV Export
- **Activity Log**: Automatic audit trail

## Setup
1. Import `schema.sql` into MySQL (via phpMyAdmin or `mysql -u root -p < schema.sql`).
2. Update MySQL credentials in `includes/config.php` if needed (defaults: `127.0.0.1`, `realestate_crm`, `root`, empty password).
3. *(Optional)* Seed sample data: `php seed.php`
4. Run locally:
   ```bash
   php -S 127.0.0.1:8080
   ```
   Open `http://127.0.0.1:8080` and log in with `admin` / `admin123`.
