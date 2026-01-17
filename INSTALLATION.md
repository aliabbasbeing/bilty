# Installation Guide - Bilty Management System

## Quick Start Guide

This guide will help you set up the Bilty Management System on your local or production server.

## Prerequisites

Before you begin, ensure you have:

- **PHP**: Version 7.2 or higher
- **MySQL**: Version 5.7 or higher
- **Apache/Nginx**: With mod_rewrite enabled
- **Web Server**: XAMPP, WAMP, LAMP, or similar

## Installation Steps

### Step 1: Download/Clone the Repository

```bash
git clone https://github.com/aliabbasbeing/bilty.git
cd bilty
```

Or download and extract the ZIP file to your web server directory.

### Step 2: Database Setup

1. **Create Database**
   
   Open phpMyAdmin or MySQL command line and create a new database:
   
   ```sql
   CREATE DATABASE bilty_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```

2. **Import Existing Schema**
   
   If you have an existing bilty_db, import it:
   
   ```sql
   mysql -u root -p bilty_db < bilty_db.sql
   ```

3. **Verify Tables**
   
   Required tables:
   - `consignments` - Bilty records
   - `companies` - Company information
   - `bills` (optional) - Bills management
   - `vehicle_maintenance` (optional) - Maintenance records

### Step 3: Configure Database Connection

Edit `application/config/database.php`:

```php
$db['default'] = array(
    'hostname' => '127.0.0.1',      // Database host
    'username' => 'root',            // Database username
    'password' => '',                // Database password
    'database' => 'bilty_db',        // Database name
    'dbdriver' => 'mysqli',
    'char_set' => 'utf8mb4',
    'dbcollat' => 'utf8mb4_general_ci',
);
```

### Step 4: Set Base URL (Optional)

If not using the root directory, edit `application/config/config.php`:

```php
$config['base_url'] = 'http://localhost/bilty/';
```

For production:
```php
$config['base_url'] = 'https://yourdomain.com/';
```

### Step 5: Set Directory Permissions

For Linux/Mac:

```bash
chmod -R 755 application/cache
chmod -R 755 application/logs
chmod -R 755 bilty_pdfs
```

For Windows:
- Right-click folders → Properties → Security → Give write permissions

### Step 6: Verify .htaccess

Ensure `.htaccess` exists in the root directory:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]
```

### Step 7: Enable mod_rewrite (Apache)

**XAMPP/WAMP:**
1. Open `httpd.conf`
2. Find and uncomment: `LoadModule rewrite_module modules/mod_rewrite.so`
3. Restart Apache

**Linux:**
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

### Step 8: Access the Application

Open your browser and navigate to:

```
http://localhost/bilty/
```

or

```
http://localhost/bilty/dashboard
```

## Directory Structure

After installation, your structure should look like:

```
bilty/
├── application/          # CodeIgniter application
│   ├── config/          # Configuration files
│   ├── controllers/
│   ├── models/
│   ├── modules/         # HMVC modules
│   └── views/
├── assets/              # CSS, JS, images
│   ├── bootstrap/
│   ├── css/
│   └── js/
├── bilty_pdfs/          # Generated PDFs
├── system/              # CodeIgniter core
├── .htaccess            # Clean URLs
└── index.php            # Front controller
```

## Configuration Options

### 1. Session Configuration

Edit `application/config/config.php`:

```php
$config['sess_driver'] = 'files';
$config['sess_cookie_name'] = 'bilty_session';
$config['sess_expiration'] = 7200;  // 2 hours
$config['sess_save_path'] = APPPATH . 'cache/';
```

### 2. Error Reporting

For development:
```php
// index.php
define('ENVIRONMENT', 'development');
```

For production:
```php
// index.php
define('ENVIRONMENT', 'production');
```

### 3. CSRF Protection

Already enabled in `application/config/config.php`:

```php
$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'csrf_token';
$config['csrf_cookie_name'] = 'csrf_cookie';
```

## Testing the Installation

### 1. Test Dashboard

Navigate to: `http://localhost/bilty/dashboard`

You should see:
- Dashboard with statistics
- Quick search functionality
- Navigation menu

### 2. Test Bilty Creation

Navigate to: `http://localhost/bilty/add`

Try creating a new bilty record.

### 3. Test Reports

Navigate to: `http://localhost/bilty/finance/reports`

Check if reports are loading correctly.

## Troubleshooting

### Issue: "404 Page Not Found"

**Solution:**
1. Check if mod_rewrite is enabled
2. Verify .htaccess exists
3. Check `$config['index_page']` is set to `''` in config.php

### Issue: "Database connection failed"

**Solution:**
1. Verify database credentials in `database.php`
2. Ensure MySQL service is running
3. Check if database exists

### Issue: "Permission Denied"

**Solution:**
```bash
chmod -R 755 application/cache
chmod -R 755 application/logs
```

### Issue: Blank page or errors

**Solution:**
1. Check PHP error logs
2. Enable error reporting in index.php:
   ```php
   define('ENVIRONMENT', 'development');
   ```
3. Check application/logs/ folder

### Issue: Assets (CSS/JS) not loading

**Solution:**
1. Check if `base_url` is set correctly
2. Verify assets folder permissions
3. Check browser console for 404 errors

### Issue: PHP 8.2 Deprecation Warnings

**Symptoms:**
```
Creation of dynamic property CI_URI::$config is deprecated
Creation of dynamic property MY_Router::$uri is deprecated
```

**Solution:**
These are harmless warnings from CodeIgniter 3's core files on PHP 8.2+. To suppress them:

1. Edit `index.php` and add after line 1:
   ```php
   <?php
   error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
   ```

2. Or upgrade to PHP 8.1 or lower for production use.

These warnings don't affect functionality and will be fixed in CodeIgniter 4.

## Production Deployment

### 1. Security Checklist

- [ ] Change encryption key in config.php
- [ ] Set ENVIRONMENT to 'production' in index.php
- [ ] Disable error display
- [ ] Use strong database passwords
- [ ] Enable HTTPS
- [ ] Set appropriate file permissions

### 2. Performance Optimization

```php
// config.php
$config['compress_output'] = TRUE;
$config['cache_query_string'] = TRUE;
```

### 3. Backup Strategy

Regular backups:
1. Database: Export via phpMyAdmin or mysqldump
2. Files: bilty_pdfs folder
3. Code: Git repository

## Updating

To update to a new version:

1. Backup database and files
2. Pull latest changes: `git pull origin main`
3. Clear cache: Delete `application/cache/*`
4. Test thoroughly

## Support

For issues or questions:

- **Developer**: Ali Abbas
- **Phone**: +92 348 3469617
- **GitHub**: https://github.com/aliabbasbeing/bilty

## License

Proprietary - All rights reserved.

---

**Last Updated**: January 2024
