# Bilty Management System

A comprehensive Laravel-based transport operations platform for managing consignments (bilties), billing, vehicle maintenance, payments, and reports.

## Features

- **Dashboard**: KPI cards showing total consignments, revenue, pending balance, and monthly statistics
- **Consignment Management**: Create and manage bilties with auto-numbering, fixed/per-km rates, and balance tracking
- **Company Management**: CRUD operations for companies with case-insensitive unique name constraint
- **Bill Management**: Multi-consignment billing with tax calculations and payment tracking
- **Vehicle Maintenance**: Track maintenance records with filterable listings
- **Payment Tracking**: Log payments and automatically update consignment balances
- **PDF Generation**: Export bilties and bills as printable PDFs
- **Reporting**: Date-range reports with filters and CSV export

## Tech Stack

- **Backend**: Laravel 10.x, PHP 8.1+
- **Database**: MySQL/MariaDB (utf8mb4)
- **Frontend**: Bootstrap 5 + Font Awesome
- **PDF**: Laravel DOMPDF (barryvdh/laravel-dompdf)

## Installation

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL 5.7+ or MariaDB

### Setup Steps

1. Install PHP dependencies:
   ```bash
   composer install
   ```

2. Create environment file:
   ```bash
   cp .env.example .env
   ```

3. Configure database in `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=bilty_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. Generate application key:
   ```bash
   php artisan key:generate
   ```

5. Create database:
   ```bash
   mysql -u root -p
   CREATE DATABASE bilty_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   EXIT;
   ```

6. Run migrations:
   ```bash
   php artisan migrate
   ```

7. Create storage symlink:
   ```bash
   php artisan storage:link
   ```

8. Start development server:
   ```bash
   php artisan serve
   ```

9. Visit `http://localhost:8000` in your browser

## Database Schema

### Companies Table
- id, name (unique, case-insensitive), address, timestamps

### Consignments Table
- id, company_id, bilty_no (unique), date, vehicle details, shipment info
- Financial fields: km, rate, rate_type (Fixed/PerKM), amount, advance, balance
- pdf_path, timestamps

### Bills Table
- id, bill_no (unique), financial_year, issue_date, company_id
- consignment_ids (JSON array), gross_amount, tax_percent (default 4%), tax_amount, net_amount
- meta (JSON), status (DRAFT/FINAL), payment_status (UNPAID/PAID)
- payment_date, payment_note, pdf_path, timestamps

### Vehicle Maintenance Table
- id, entry_date, vehicle_no, expense_type, amount, narration, timestamps

### Payments Table
- id, consignment_id, payment_date, amount, method, timestamps

## Business Rules

- **Bilty Numbering**: Auto-generated based on MAX(id) + 1
- **Rate Types**: 
  - Fixed: Amount is manually entered
  - PerKM: Amount = km × rate
- **Balance Calculation**: balance = amount - advance
- **Tax**: Default 4% but editable per bill
- **Multi-Consignment Billing**: Select multiple consignments by IDs for a single bill

## Support

Developed by **Ali Abbas**  
Contact: +92 348 3469617
