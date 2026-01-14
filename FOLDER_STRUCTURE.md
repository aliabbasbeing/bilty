# Folder Structure - Bilty Management System

Complete directory structure of the CodeIgniter 3 HMVC Bilty Management System.

```
bilty/
│
├── application/                    # Main application folder
│   │
│   ├── cache/                     # Cache files (auto-generated)
│   │   └── index.html
│   │
│   ├── config/                    # Configuration files
│   │   ├── autoload.php          # Auto-load libraries, helpers
│   │   ├── config.php            # Main config (CSRF, sessions, etc.)
│   │   ├── database.php          # Database connection settings
│   │   ├── routes.php            # URL routing configuration
│   │   ├── constants.php         # Application constants
│   │   ├── hooks.php             # Pre-system hooks
│   │   └── mimes.php             # MIME types
│   │
│   ├── controllers/               # Global controllers (not used in HMVC)
│   │   ├── Welcome.php           # Default welcome controller
│   │   └── index.html
│   │
│   ├── core/                      # Core extensions
│   │   ├── MY_Controller.php     # Base controller classes
│   │   │                         # - MY_Controller
│   │   │                         # - Auth_Controller
│   │   │                         # - Public_Controller
│   │   ├── MY_Loader.php         # HMVC Loader extension
│   │   ├── MY_Router.php         # HMVC Router extension
│   │   └── index.html
│   │
│   ├── helpers/                   # Helper functions
│   │   ├── bilty_helper.php      # Custom bilty helpers
│   │   │                         # - format_currency()
│   │   │                         # - format_number()
│   │   │                         # - payment_status_badge()
│   │   │                         # - bilty_status_badge()
│   │   │                         # - generate_bilty_number()
│   │   └── index.html
│   │
│   ├── hooks/                     # Hook files
│   │   └── index.html
│   │
│   ├── language/                  # Language files
│   │   ├── english/
│   │   │   ├── form_validation_lang.php
│   │   │   └── ...
│   │   └── index.html
│   │
│   ├── libraries/                 # Custom libraries
│   │   ├── Pdf_generator.php     # PDF generation library
│   │   │                         # - generate_bilty()
│   │   └── index.html
│   │
│   ├── logs/                      # Application logs
│   │   └── index.html
│   │
│   ├── models/                    # Global models (not used in HMVC)
│   │   └── index.html
│   │
│   ├── modules/                   # HMVC Modules (Main application logic)
│   │   │
│   │   ├── auth/                  # Authentication module
│   │   │   ├── controllers/
│   │   │   │   └── Auth.php      # Login, logout, register
│   │   │   ├── models/
│   │   │   │   └── Auth_model.php
│   │   │   └── views/
│   │   │       ├── login.php
│   │   │       └── register.php
│   │   │
│   │   ├── dashboard/             # Dashboard module
│   │   │   ├── controllers/
│   │   │   │   └── Dashboard.php # Main dashboard controller
│   │   │   │                     # Methods: index()
│   │   │   ├── models/
│   │   │   │   └── Dashboard_model.php
│   │   │   │                     # Methods: get_statistics(), search_bilty()
│   │   │   └── views/
│   │   │       └── index.php     # Dashboard view with stats
│   │   │
│   │   ├── bilty/                 # Bilty management module
│   │   │   ├── controllers/
│   │   │   │   └── Bilty.php     # Bilty controller
│   │   │   │                     # Methods: index(), add(), save(),
│   │   │   │                     #          view(), manage(), delete()
│   │   │   ├── models/
│   │   │   │   └── Bilty_model.php
│   │   │   │                     # Methods: get_all(), get_by_id(),
│   │   │   │                     #          insert(), update(), delete()
│   │   │   │                     #          get_companies()
│   │   │   └── views/
│   │   │       ├── add.php       # Add new bilty form
│   │   │       ├── index.php     # View all bilties
│   │   │       ├── manage.php    # Manage bills
│   │   │       └── view.php      # Single bilty view
│   │   │
│   │   ├── finance/               # Finance & reporting module
│   │   │   ├── controllers/
│   │   │   │   └── Payments.php  # Payment controller
│   │   │   │                     # Methods: reports(), update_payment()
│   │   │   ├── models/
│   │   │   │   └── Payment_model.php
│   │   │   │                     # Methods: get_summary(),
│   │   │   │                     #          get_daily_summary(),
│   │   │   │                     #          get_company_breakdown()
│   │   │   └── views/
│   │   │       └── reports.php   # Reports & analytics view
│   │   │
│   │   └── vehicles/              # Vehicle maintenance module
│   │       ├── controllers/
│   │       │   └── Maintenance.php
│   │       │                     # Methods: index(), save()
│   │       ├── models/
│   │       │   └── Maintenance_model.php
│   │       │                     # Methods: get_all(), insert()
│   │       └── views/
│   │           └── maintenance.php
│   │
│   ├── third_party/               # Third-party packages
│   │   ├── HMVC/                 # HMVC extension
│   │   │   ├── Loader.php        # MX Loader
│   │   │   └── Router.php        # MX Router
│   │   └── index.html
│   │
│   ├── views/                     # Global views
│   │   ├── layout/               # Layout templates
│   │   │   ├── main.php          # Main layout wrapper
│   │   │   ├── header.php        # Header navigation
│   │   │   └── footer.php        # Footer
│   │   ├── errors/               # Error pages
│   │   │   ├── html/
│   │   │   │   ├── error_404.php
│   │   │   │   ├── error_db.php
│   │   │   │   └── ...
│   │   │   └── cli/
│   │   └── welcome_message.php
│   │
│   └── index.html                # Directory index blocker
│
├── assets/                        # Static assets
│   ├── bootstrap/                # Bootstrap 5 framework
│   │   ├── css/
│   │   │   └── bootstrap.min.css
│   │   └── js/
│   │       └── bootstrap.bundle.min.js
│   ├── css/                      # Custom CSS
│   │   └── theme.css            # Main theme styles
│   └── js/                       # Custom JavaScript
│       └── app.js               # Application scripts
│
├── bilty_pdfs/                   # Generated PDF files
│   └── .gitkeep
│
├── fontawesome/                  # Font Awesome icons
│   └── css/
│       └── all.min.css
│
├── system/                       # CodeIgniter core system files
│   ├── core/                    # Core classes
│   ├── database/                # Database drivers
│   ├── helpers/                 # System helpers
│   ├── language/                # System language files
│   ├── libraries/               # System libraries
│   └── ...
│
├── .gitignore                   # Git ignore rules
├── .htaccess                    # Apache URL rewriting
├── index.php                    # Front controller (Entry point)
├── README.md                    # Main documentation
├── INSTALLATION.md              # Installation guide
├── FOLDER_STRUCTURE.md          # This file
│
└── old files (for reference):   # Original Core PHP files
    ├── old_index.php           # Original dashboard
    ├── add_bilty.php           # Original add bilty
    ├── view_bilty.php          # Original view bilty
    ├── manage_bills.php        # Original manage bills
    ├── reports.php             # Original reports
    ├── vehicle_maintenance.php # Original maintenance
    └── ...

```

## Key Directories Explained

### Application Structure

- **`application/modules/`** - Heart of HMVC architecture. Each module is self-contained.
- **`application/core/`** - Extended CodeIgniter core classes
- **`application/config/`** - All configuration settings
- **`application/views/layout/`** - Shared layout templates

### Module Structure

Each module follows MVC pattern:
```
module_name/
├── controllers/     # Handle HTTP requests
├── models/         # Database operations
└── views/          # HTML templates
```

### Assets Organization

```
assets/
├── bootstrap/      # Framework CSS/JS
├── css/           # Custom styles
└── js/            # Custom scripts
```

## File Naming Conventions

### Controllers
- Class name: `Ucfirst` (e.g., `Dashboard.php`)
- File name: Match class name
- Extends: `MY_Controller` or `Auth_Controller`

### Models
- Class name: `Module_model` (e.g., `Bilty_model.php`)
- Extends: `CI_Model`

### Views
- File name: `lowercase` with underscores (e.g., `add_bilty.php`)
- Extension: `.php`

## Important Configuration Files

| File | Purpose |
|------|---------|
| `config/config.php` | Main settings, CSRF, sessions |
| `config/database.php` | Database connection |
| `config/routes.php` | URL routing |
| `config/autoload.php` | Auto-load helpers/libraries |
| `.htaccess` | Clean URLs (Apache) |

## Module Communication

Modules can call each other using:

```php
// Load another module's controller
modules::run('module/controller/method');

// Load another module's model
$this->load->model('module/module_model');
```

## Permissions

Writable directories:
- `application/cache/`
- `application/logs/`
- `bilty_pdfs/`

Set to `755` or `775` depending on server configuration.

## Security

Protected directories (via .htaccess or index.html):
- `application/`
- `system/`
- All subdirectories

## Development vs Production

**Development:**
- Environment: `development`
- Error display: ON
- Debugging: Enabled

**Production:**
- Environment: `production`
- Error display: OFF
- Logging: Enabled

---

**Last Updated**: January 2024
