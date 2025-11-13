<div align="center">

# 🎓 University Networking Portal (UNP)

A responsive PHP + MySQL web application that unites students, moderators, administrators, and alumni under one professional networking ecosystem.

</div>

## ✨ Highlights

- Role-based access control with tailored dashboards for admins, moderators, and students
- Complete CRUD coverage for users, clubs, events, opportunities, and alumni profiles
- Student self-service portal: opportunity board, event registrations, alumni directory, profile management
- Moderator console for end-to-end event ownership (create, edit, attendance tracking)
- Creative Bootstrap 5 + custom CSS styling for a polished, modern look
- Secure development practices (prepared statements, password hashing, session-based auth)

## 📂 Project Structure

```
university-networking-portal/
├── assets/
│   ├── css/style.css
│   └── js/main.js
├── config/
│   └── database.php
├── database/
│   └── schema.sql
├── includes/
│   ├── footer.php
│   ├── functions.php
│   └── header.php
├── public/
│   ├── admin/…         # Admin dashboards & management pages
│   ├── moderator/…     # Moderator hub & event management
│   ├── student/…       # Student experiences (dashboard, events, alumni, profile)
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   └── register.php
├── scripts/
│   └── seed_admin.php  # CLI utility to create the first admin
└── README.md
```

## 🚀 Getting Started

### 1. Prerequisites

- PHP 8.1+ with PDO extension enabled
- MySQL 8.0+
- Composer (optional, if you prefer autoloaders)  
- Web server (Apache/Nginx) or PHP’s built-in server for local testing

### 2. Configure the Database

1. Create a new database, e.g. `university_networking_portal`.
2. Import the schema:

   ```bash
   mysql -u root -p university_networking_portal < database/schema.sql
   ```

### 3. Environment Configuration

The app reads connection details from environment variables (recommended for production):

```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=university_networking_portal
DB_USER=root
DB_PASS=secret
DB_CHARSET=utf8mb4
```

Alternatively, adjust defaults directly inside `config/database.php`.

### 4. Seed the First Admin

Run the CLI helper (requires PHP CLI):

```bash
php scripts/seed_admin.php
```

Follow the prompts to set the initial administrator credentials.

### 5. Serve the Application

Using PHP’s built-in server:

```bash
php -S localhost:8080 -t public
```

Or configure your Apache/Nginx virtual host to point at the `public/` directory.

### 6. Explore Roles

- **Admin**: `/public/admin/dashboard.php`
- **Moderator**: `/public/moderator/dashboard.php`
- **Student**: `/public/student/dashboard.php`

## 🧠 Feature Overview

- **Authentication**: Login via institutional ID/email + password (hashed with `password_hash`)
- **Admin Suite**:
  - Manage users (create, edit, delete, role assignments)
  - Manage clubs and moderator relationships
  - Post opportunities (jobs, internships, research)
  - Oversee events and review participant rosters
  - Curate the alumni directory
- **Moderator Toolkit**:
  - Personalized dashboard with registration analytics
  - Event creation & editing tied to their assigned clubs
  - Participant management for each workshop
- **Student Experience**:
  - Dashboard with upcoming reservations & featured content
  - Event hub with live registration tracking & capacity rules
  - Opportunity board with search & filters
  - Rich alumni directory with multi-criteria filtering
  - Profile center including password updates

## 🛡️ Security & Best Practices

- Prepared statements across all SQL operations
- Password hashing using PHP’s `password_hash`/`password_verify`
- Role gates on every restricted route via `require_role`
- Flash messaging for user feedback
- Graceful error handling with minimal leakage of internal details

## 🧪 Testing Suggestions

- Create sample users for each role via the admin panel
- Add clubs and assign moderators to verify event ownership
- Seed opportunities with varying types to exercise filters
- Register/unregister students for events to validate capacity logic
- Populate alumni directory and try different search combinations

## 📄 Licensing & Notes

This project brief is intended for academic/demo use. Customize, extend, or integrate additional services (notifications, analytics, resume uploads) as required by your institution.

---

Need help or want to extend the platform? Start with the scripts and modular controller structure in `public/`—everything is intentionally lightweight for rapid enhancement.
