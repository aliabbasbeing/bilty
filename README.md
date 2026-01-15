# Bilty Management System - CodeIgniter 3 HMVC

A professional, scalable, and modular Bilty Management System built with CodeIgniter 3 and HMVC architecture.

## 🚀 Features

- **HMVC Architecture**: Modular structure for better organization and scalability
- **Secure**: CSRF protection, XSS filtering, and SQL injection prevention via Query Builder
- **Clean URLs**: SEO-friendly routes without index.php
- **Responsive Design**: Bootstrap 5 with modern UI/UX
- **Modules**:
  - Dashboard: Statistics and quick search
  - Bilty Management: Create, view, and manage bilties
  - Finance: Reports and payment tracking
  - Vehicles: Maintenance tracking

## 📁 Directory Structure

```
bilty/
├── application/
│   ├── config/             # Configuration files
│   │   ├── database.php    # Database configuration
│   │   ├── routes.php      # URL routes
│   │   └── config.php      # Main config (CSRF enabled)
│   ├── core/               # Core extensions
│   │   ├── MY_Controller.php  # Base controllers with session
│   │   ├── MY_Loader.php   # HMVC Loader
│   │   └── MY_Router.php   # HMVC Router
│   ├── helpers/
│   │   └── bilty_helper.php  # Formatting & utility functions
│   ├── libraries/
│   │   └── Pdf_generator.php # PDF generation library
│   ├── modules/            # HMVC Modules
│   │   ├── auth/           # Authentication module
│   │   ├── dashboard/      # Dashboard module
│   │   ├── bilty/          # Bilty management
│   │   │   ├── controllers/
│   │   │   │   └── Bilty.php
│   │   │   ├── models/
│   │   │   │   └── Bilty_model.php
│   │   │   └── views/
│   │   │       ├── add.php
│   │   │       ├── index.php
│   │   │       └── manage.php
│   │   ├── finance/        # Finance & reporting
│   │   │   ├── controllers/
│   │   │   ├── models/
│   │   │   └── views/
│   │   └── vehicles/       # Vehicle maintenance
│   │       ├── controllers/
│   │       ├── models/
│   │       └── views/
│   ├── views/
│   │   └── layout/         # Shared layouts
│   │       ├── main.php    # Main layout template
│   │       ├── header.php  # Header navigation
│   │       └── footer.php  # Footer
│   └── third_party/
│       └── HMVC/           # HMVC extension
├── assets/                 # Static assets
│   ├── bootstrap/          # Bootstrap 5
│   ├── css/
│   └── js/
├── system/                 # CodeIgniter system files
├── index.php               # Front controller
├── .htaccess               # Clean URLs configuration
└── README.md
```

## 🛠️ Installation

### Prerequisites
- PHP 7.2 or higher
- MySQL 5.7 or higher
- Apache/Nginx with mod_rewrite enabled
- Composer (optional)

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/aliabbasbeing/bilty.git
   cd bilty
   ```

2. **Configure Database**
   
   Edit `application/config/database.php`:
   ```php
   $db['default'] = array(
       'hostname' => '127.0.0.1',
       'username' => 'root',
       'password' => '',
       'database' => 'bilty_db',
       'dbdriver' => 'mysqli',
       'char_set' => 'utf8mb4',
   );
   ```

3. **Import Database**
   
   Create the database and import your existing bilty_db schema.

4. **Configure Base URL** (Optional)
   
   Edit `application/config/config.php`:
   ```php
   $config['base_url'] = 'http://localhost/bilty/';
   ```

5. **Set Permissions**
   ```bash
   chmod -R 755 application/cache
   chmod -R 755 application/logs
   chmod -R 755 bilty_pdfs
   ```

6. **Access Application**
   
   Navigate to: `http://localhost/bilty/`

## 🔗 URL Routes

### Old vs New URLs

| Old URL (Core PHP) | New URL (CodeIgniter) | Module/Method |
|--------------------|----------------------|---------------|
| index.php | /dashboard | dashboard/index |
| add_bilty.php | /bilty/add | bilty/add |
| view_bilty.php | /bilty | bilty/index |
| manage_bills.php | /bilty/manage | bilty/manage |
| reports.php | /finance/reports | finance/reports |
| vehicle_maintenance.php | /vehicles/maintenance | vehicles/maintenance |
| view_bilty.php?id=10 | /bilty/view/10 | bilty/view/$1 |

### Route Configuration

Routes are defined in `application/config/routes.php`:

```php
// Dashboard
$route['dashboard'] = 'dashboard/dashboard/index';

// Bilty routes
$route['bilty'] = 'bilty/bilty/index';
$route['bilty/add'] = 'bilty/bilty/add';
$route['bilty/view/(:num)'] = 'bilty/bilty/view/$1';

// Finance routes
$route['finance/reports'] = 'finance/payments/reports';

// Vehicles routes
$route['vehicles/maintenance'] = 'vehicles/maintenance/index';
```

## 🔐 Security Features

1. **CSRF Protection**: Enabled in `config.php`
   ```php
   $config['csrf_protection'] = TRUE;
   ```

2. **XSS Filtering**: Applied via CodeIgniter's Input class
   ```php
   $this->input->post('field', TRUE); // XSS clean
   ```

3. **SQL Injection Prevention**: Using Query Builder
   ```php
   $this->db->where('id', $id)->get('table');
   ```

4. **Password Hashing**: Ready for implementation with `password_hash()`

## 📚 Helper Functions

Available in `application/helpers/bilty_helper.php`:

```php
format_currency($amount)           // Format: Rs. 1,000.00
format_number($number)             // Format: 1,000
payment_status_badge($status)      // Returns HTML badge
bilty_status_badge($balance)       // Returns HTML badge
format_date($date)                 // Format dates
generate_bilty_number()            // Get next bilty number
calculate_balance($amount, $adv)   // Calculate balance
```

## 🎨 UI Components

- **Bootstrap 5**: Modern responsive framework
- **Font Awesome 6**: Icon library
- **Custom Theme**: Brand colors and styling

## 🔧 Core Components

### MY_Controller

Base controller classes:

```php
MY_Controller        // Base for all controllers
Auth_Controller      // Requires authentication
Public_Controller    // Public pages (login, etc.)
```

### Bilty Model

CRUD operations using Query Builder:

```php
$this->bilty_model->get_all($filters);     // Get all bilties
$this->bilty_model->get_by_id($id);        // Get single bilty
$this->bilty_model->insert($data);         // Create new bilty
$this->bilty_model->update($id, $data);    // Update bilty
$this->bilty_model->delete($id);           // Delete bilty
```

## 📝 Development Guide

### Creating a New Module

1. Create module directory:
   ```bash
   mkdir -p application/modules/mymodule/{controllers,models,views}
   ```

2. Create controller:
   ```php
   <?php
   class Mymodule extends MY_Controller {
       public function index() {
           $this->render('mymodule/index', $this->data);
       }
   }
   ```

3. Create model:
   ```php
   <?php
   class Mymodule_model extends CI_Model {
       // Model methods
   }
   ```

4. Add routes in `config/routes.php`:
   ```php
   $route['mymodule'] = 'mymodule/mymodule/index';
   ```

### Using the Layout System

All views use the main layout template:

```php
$this->render('module/view', $data);
```

The layout includes:
- Header navigation
- Content area (your view)
- Footer

## 🚦 Migration from Core PHP

The old Core PHP files are preserved with `old_` prefix:
- `old_index.php` - Original dashboard
- `add_bilty.php` - Reference for migration
- `view_bilty.php` - Reference for migration
- etc.

These files serve as reference but are not used by the new system.

## 📊 Database Requirements

Required tables:
- `consignments` - Bilty records
- `companies` - Company information
- `bills` (optional) - Bill management
- `vehicle_maintenance` (optional) - Maintenance records

## 🤝 Contributing

This is a private project for Ali Abbas. For any modifications or support, contact:

- **Developer**: Ali Abbas
- **Phone**: +92 348 3469617

## 📄 License

Proprietary - All rights reserved.

## 🔄 Version

- **CodeIgniter**: 3.1.13
- **HMVC**: Modular Extensions
- **Bootstrap**: 5.x
- **PHP**: 7.2+

## 📞 Support

For technical support or issues, please contact the developer.

---

**Developed with ❤️ by Ali Abbas**
