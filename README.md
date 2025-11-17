# MIS Barangay - Management Information System

A comprehensive Management Information System designed for barangay administration. This system provides efficient management of residents, certificates, blotter cases, officers, and administrative tasks.

**Version:** 1.3.0  
**Last Updated:** 2024-01-16

---

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [User Roles](#user-roles)
- [Documentation](#documentation)
- [Version History](#version-history)
- [License](#license)

---

## ✨ Features

### 🏠 Dashboard
- Role-based dashboards with real-time statistics
- Interactive cards with clickable filters
- Quick access to key information

### 👥 Resident Management
- Complete resident database with detailed information
- Household and family tracking
- Age auto-calculation
- Search and filter capabilities
- Resident profile management

### 📊 Certificate Management
- Generate and track barangay certificates
- Certificate request system
- Status management (Pending, Printed, Approved, Rejected)
- Certificate printing functionality

### 📝 Blotter System
- Incident reporting and case management
- Auto-generated case numbers (BLT-YYYY-####)
- Case status tracking (Pending, Under Investigation, Resolved, Dismissed)
- Comprehensive case details and resolution notes

### 🧑‍💼 Staff & Officers Management
- Unified account and officer management
- Officer term tracking
- Position management
- Profile picture upload

### 👤 User Management
- Role-based access control (Admin, Staff, Tanod)
- Account status management
- Profile settings

---

## 📦 Requirements

- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher (or MariaDB 10.2+)
- **Web Server:** Apache with mod_rewrite enabled (or Nginx)
- **Extensions:** mysqli, mbstring, gd (for image handling)

---

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/erselmetz/php-mis-brgy.git
cd php-mis-brgy
```

### 2. Configure Web Server

#### Apache (.htaccess)
Ensure `.htaccess` is enabled and configured for URL rewriting.

#### Nginx
Configure URL rewriting in your Nginx configuration.

### 3. Install Dependencies

```bash
cd public
npm install
```

### 4. Database Setup

See [Database Setup](#database-setup) section below.

---

## ⚙️ Configuration

### Database Configuration

Edit `config.php` with your database credentials:

```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'php_mis_brgy');
```

### Base Paths

The system automatically configures base paths. Ensure your web server points to the `public/` directory.

---

## 🗄️ Database Setup

### Option 1: Using Migration Script (Recommended)

#### Via Browser:
1. Access: `http://your-domain/schema/run`
2. Login as admin
3. Click "Run Migrations"

#### Via Command Line:
```bash
cd schema
php run.php
```

### Option 2: Manual Setup

Run each migration file in `schema/` directory in order:
1. `create_users_table.php`
2. `create_households_table.php`
3. `create_families_table.php`
4. `create_residents_table.php`
5. `create_officers_table.php`
6. `create_certificates_request_table.php`
7. `create_blotter_table.php`
8. `add_profile_picture_to_users.php`
9. `migrations_table.php`
10. `add_indexes.php`
11. `merge_staff_officers.php`

### Create Admin Account

Run `seed.php` once to create the initial admin account:

```bash
php seed.php
```

**Default Admin Credentials:**
- Username: `admin`
- Password: `misredzone` (change immediately after first login)

---

## 📖 Usage

### Accessing the System

1. Navigate to your web server URL
2. Login with your credentials
3. Access role-specific features from the dashboard

### Key URLs

- **Login:** `/login`
- **Dashboard:** `/dashboard`
- **Residents:** `/resident/residents`
- **Certificates:** `/certificate/certificates`
- **Blotter:** `/blotter/blotter` (Tanod only)
- **Admin Panel:** `/admin/account` (Admin only)
- **Documentation:** `/docs`

---

## 📁 Project Structure

```
php-mis-brgy/
├── config.php              # Main configuration
├── includes/               # Core includes
│   ├── app.php            # Application bootstrap
│   ├── auth.php           # Authentication functions
│   ├── db.php             # Database connection
│   ├── function.php       # Helper functions
│   └── help.php           # Help documentation
├── public/                 # Public-facing files
│   ├── admin/             # Admin panel
│   ├── api/               # API endpoints
│   ├── assets/            # CSS and JS
│   ├── blotter/           # Blotter system
│   ├── certificate/       # Certificate management
│   ├── docs/              # Web documentation
│   ├── resident/          # Resident management
│   └── uploads/           # Uploaded files
├── schema/                 # Database migrations
│   ├── run.php           # Migration runner
│   └── *.php             # Migration files
├── scripts/                # Utility scripts
│   └── backup.php        # Database backup
├── markdown/               # Markdown documentation (gitignored)
└── README.md              # This file
```

---

## 👥 User Roles

### Admin
- Full system access
- User and account management
- All resident, certificate, and blotter features
- System configuration

### Staff
- Resident management
- Certificate management
- Dashboard with resident statistics
- Cannot access blotter system

### Tanod (Barangay Security)
- Blotter system access only
- Case management
- Dashboard with blotter statistics
- Cannot access residents or certificates

---

## 📚 Documentation

### Available Documentation

- **User Manual:** See `markdown/user_manual.md` (local)
- **Database Schema:** See `markdown/database_schema.md` (local)
- **Coding Style Guide:** See `markdown/CODING_STYLE_GUIDE.md` (local)
- **Changelog:** See `markdown/CHANGELOG.md` (local)
- **Web Documentation:** Access `/docs` in the application

### Version Documentation

- **v1.3.0:** Code Quality & Database Optimization
- **v1.2.0:** Staff & Officers Merge Update
- **v1.1.0:** Bug Fixes & Improvements Update
- **v1.0.0:** Initial Release

---

## 🔄 Version History

### v1.3.0 (2024-01-16)
- Refactored duplicate code
- Added type hints to all core functions
- Created coding style guide
- Added database performance indexes
- Implemented migration versioning system
- Removed testing infrastructure
- Improved documentation

### v1.2.0 (2024-01-16)
- Merged Staff and Officers management
- Improved migration system
- Documentation updates

### v1.1.0 (2024-01-15)
- Security fixes
- Bug fixes
- UI modernization
- Performance optimizations

### v1.0.0 (2024-01-15)
- Initial release
- Core features implementation

---

## 🔧 Development

### Running Migrations

```bash
cd schema
php run.php
```

### Database Backup

```bash
php scripts/backup.php
```

Or use the provided batch file:
```bash
bat/one_click_db_backup.bat
```

### Code Style

Follow the coding style guide in `markdown/CODING_STYLE_GUIDE.md`.

---

## 🐛 Troubleshooting

### Database Connection Issues
- Verify database credentials in `config.php`
- Ensure MySQL service is running
- Check database user permissions

### Migration Errors
- Ensure all previous migrations completed successfully
- Check database connection
- Review error messages in migration output

### Permission Issues
- Ensure `public/uploads/` directory is writable
- Check file permissions on upload directories

---

## 📝 License

See `LICENSE` file for details.

---

## 👨‍💻 Author

**Ersel Magbanua**  
Capstone Project

---

## 🤝 Contributing

This is a capstone project. For issues or suggestions, please contact the author.

---

## 📞 Support

For support and questions:
- Review documentation in `/docs`
- Check `markdown/` folder for detailed guides
- Contact system administrator

---

**Note:** This is a capstone project. Ensure to change default passwords and configure security settings before production use.
