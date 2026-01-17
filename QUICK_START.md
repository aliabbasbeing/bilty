# Quick Start Guide - Bilty Management System

Get up and running in minutes!

## 🚀 Prerequisites

- PHP 7.2+ installed
- MySQL/MariaDB installed
- Apache/Nginx with mod_rewrite
- Web server running (XAMPP, WAMP, LAMP, etc.)

## ⚡ 5-Minute Setup

### Step 1: Get the Code
```bash
git clone https://github.com/aliabbasbeing/bilty.git
cd bilty
```

### Step 2: Create Database
```sql
CREATE DATABASE bilty_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### Step 3: Import Your Schema
Import your existing bilty_db tables.

### Step 4: Configure Database
Edit `application/config/database.php`:
```php
'hostname' => '127.0.0.1',
'username' => 'root',
'password' => '',          // Your MySQL password
'database' => 'bilty_db',
```

### Step 5: Set Permissions (Linux/Mac only)
```bash
chmod -R 755 application/cache
chmod -R 755 application/logs
chmod -R 755 bilty_pdfs
```

### Step 6: Access Application
Open browser: `http://localhost/bilty/`

## 🎯 Quick Test

### Test Dashboard
→ Navigate to: `http://localhost/bilty/dashboard`

Should show:
- Statistics cards
- Quick search
- Navigation menu

### Test Bilty Creation
→ Navigate to: `http://localhost/bilty/add`

Try creating a new bilty.

### Test Reports
→ Navigate to: `http://localhost/bilty/finance/reports`

View analytics and reports.

## 📱 Main URLs

| Feature | URL |
|---------|-----|
| Dashboard | `/dashboard` |
| Add Bilty | `/bilty/add` |
| View All | `/bilty` |
| Manage Bills | `/bilty/manage` |
| Reports | `/finance/reports` |
| Maintenance | `/vehicles/maintenance` |

## 🔧 Common Issues

### "404 Not Found"
**Fix:** Enable mod_rewrite in Apache
```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

### "Database connection failed"
**Fix:** Check credentials in `database.php`

### "Blank page"
**Fix:** Check PHP error logs or enable errors:
```php
// index.php - Change to development
define('ENVIRONMENT', 'development');
```

### CSS/JS not loading
**Fix:** Check base_url in `config/config.php`

### PHP 8.2 Deprecation Warnings
**Symptoms:** "Creation of dynamic property..." warnings

**Fix:** Add to top of `index.php`:
```php
<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
```
These warnings are harmless and don't affect functionality.

## 📂 Project Structure

```
bilty/
├── application/
│   ├── modules/        # Your modules here
│   ├── config/         # Configuration
│   └── views/layout/   # Templates
├── assets/             # CSS, JS
├── system/             # Framework
└── index.php           # Entry point
```

## 🎨 Key Features

✅ **Dashboard** - Stats and quick search
✅ **Bilty Management** - Full CRUD operations
✅ **Finance** - Reports and analytics
✅ **Vehicles** - Maintenance tracking
✅ **Clean URLs** - No index.php in URLs
✅ **Secure** - CSRF, XSS protection
✅ **Bootstrap 5** - Modern responsive UI

## 📖 Documentation

- `README.md` - Full documentation
- `INSTALLATION.md` - Detailed setup
- `FOLDER_STRUCTURE.md` - Directory guide
- `REFACTORING_SUMMARY.md` - Technical details

## 💡 Development Tips

### Enable Debug Mode
```php
// index.php
define('ENVIRONMENT', 'development');
```

### Check Logs
```
application/logs/log-YYYY-MM-DD.php
```

### Clear Cache
```bash
rm -rf application/cache/*
```

### Test Database Connection
```php
// In any controller
$this->db->query("SELECT 1");
echo "Connected!";
```

## 🔐 Security Checklist

For production:
- [ ] Set ENVIRONMENT to 'production'
- [ ] Use strong database password
- [ ] Change encryption key in config.php
- [ ] Enable HTTPS
- [ ] Set proper file permissions (755/644)
- [ ] Review security settings

## 📞 Need Help?

**Developer**: Ali Abbas
**Phone**: +92 348 3469617

## 🎓 Learning Path

1. Start with Dashboard module
2. Explore Bilty module (most complex)
3. Check Finance module for reports
4. Review helpers in `bilty_helper.php`
5. Study route definitions in `routes.php`

## ⚙️ Configuration Quick Reference

### Database
`application/config/database.php`

### Routes
`application/config/routes.php`

### Main Settings
`application/config/config.php`

### Autoload
`application/config/autoload.php`

## 🚦 Status Check

After setup, verify:
- ✅ Database connected
- ✅ Dashboard loads
- ✅ Add bilty form works
- ✅ Navigation functional
- ✅ No 404 errors

## 📈 Next Steps

1. Import your existing bilty data
2. Test all CRUD operations
3. Customize branding/colors
4. Add user authentication (if needed)
5. Deploy to production

## 🎉 You're Ready!

Your Bilty Management System is now running with:
- Modern HMVC architecture
- Secure database operations
- Clean URLs
- Professional UI
- Complete documentation

Happy coding! 🚀

---

**Framework**: CodeIgniter 3.1.13
**Architecture**: HMVC
**UI**: Bootstrap 5
**Version**: 2.0.0
