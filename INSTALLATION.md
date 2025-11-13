# Quick Installation Guide

## Step-by-Step Setup

### 1. Prerequisites
- Install XAMPP, WAMP, or MAMP
- Ensure PHP 7.4+ and MySQL 5.7+ are available

### 2. Setup Database
1. Start MySQL from XAMPP/WAMP control panel
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Click "Import" tab
4. Select `database/schema.sql`
5. Click "Go" to import

### 3. Configure Database (if needed)
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for XAMPP default
define('DB_NAME', 'university_portal');
```

### 4. Start Server
1. Start Apache and MySQL from XAMPP/WAMP
2. Place project in `htdocs` folder
3. Access: `http://localhost/university-portal/`

### 5. Login
- **Admin**: ID: `ADMIN001`, Password: `admin123`
- Create more users via admin panel

## Troubleshooting

**Database Error?**
- Check MySQL is running
- Verify database name is `university_portal`
- Check credentials in `config/database.php`

**404 Error?**
- Ensure Apache is running
- Check folder name matches URL
- Verify files are in `htdocs` folder

**Login Issues?**
- Clear browser cache
- Verify database was imported
- Check default admin credentials

## Project Structure
```
university-portal/
├── admin/          # Admin panel
├── moderator/      # Moderator panel
├── student/        # Student panel
├── config/         # Configuration
├── database/       # SQL schema
├── assets/         # CSS/JS
└── index.php       # Entry point
```

## Default Data
The schema includes:
- 1 admin user (ADMIN001/admin123)
- 3 sample clubs
- 3 sample opportunities
- 4 sample alumni
- 3 sample events

You can start using the system immediately after installation!
