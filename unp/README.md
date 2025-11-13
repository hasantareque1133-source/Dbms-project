# University Networking Portal (UNP)

The University Networking Portal is a full-stack PHP + MySQL platform that unifies internships and research opportunities, club events, and alumni outreach in a single secure hub. It showcases DBMS fundamentals with a normalized schema, role-based access control, and robust CRUD operations.

## ✨ Key Features

- **Authentication & Security**
  - PHP session-based login with hashed passwords (`password_hash`)
  - CSRF protection on all POST forms, role-aware authorization
  - Activity logging for administrative oversight

- **Role-Specific Dashboards**
  - **Admin**: Manage users (assign moderator roles), opportunities, clubs, events, alumni directory, and audit logs
  - **Student**: Discover opportunities, register/unregister for events, search alumni, update profile, change password
  - **Moderator**: Publish and manage club events, view live participant lists

- **Interactive Modules**
  - Opportunity board with filtering and modal detail views
  - Event registration with capacity checks and personal enrollment tracking
  - Alumni directory with department/year/profession filters and contact links
  - Glassmorphism-inspired UI with Bootstrap 5 and custom CSS theme

## 🗂️ Project Structure

```
unp/
├── database/
│   └── schema.sql          # MySQL schema + seed data
├── includes/
│   ├── auth.php            # Authentication helpers + activity logging
│   ├── config.php          # Application configuration constants
│   ├── database.php        # PDO connection wrapper
│   └── helpers.php         # CSRF, flash messaging, sanitization utilities
├── public/
│   ├── assets/
│   │   ├── css/style.css   # Custom styling and role color palette
│   │   └── js/main.js      # UI enhancements (alerts, toggles, nav state)
│   ├── admin/…             # Admin modules: users, events, opportunities, alumni, activity
│   ├── student/…           # Student-facing views: opportunities, events, alumni, profile
│   ├── moderator/…         # Moderator event management console
│   ├── dashboard.php       # Role-aware landing page post-login
│   ├── index.php           # Marketing/landing page
│   ├── login.php, logout.php
│   └── …
├── templates/
│   ├── header.php          # Navigation, global resources
│   └── footer.php          # Footer + script imports
└── README.md
```

## ⚙️ Technology Stack

- **Language:** PHP (8.x recommended)
- **Database:** MySQL 8+ (UTF8MB4)
- **Frontend:** Bootstrap 5.3, Bootstrap Icons, vanilla JS
- **Server:** Apache/Nginx or PHP built-in server for local development

## 🚀 Getting Started

1. **Install dependencies**
   - PHP 8.0 or higher with PDO MySQL extension
   - MySQL server

2. **Create the database**
   ```sh
   mysql -u root -p < database/schema.sql
   ```
   The script creates the `university_networking_portal` database, tables, and seeds an admin account.

3. **Configure environment**
   - Copy `includes/config.php` and update `DB_HOST`, `DB_USER`, `DB_PASS` to match your MySQL credentials.
   - Optionally adjust `BASE_URL` if deploying under a subdirectory.

4. **Run the application locally**
   ```sh
   cd public
   php -S localhost:8000
   ```
   Visit `http://localhost:8000` in your browser.

5. **Default admin credentials**
   - Email: `admin@unp.test`
   - Password: `Admin@123`

6. **Initial workflow**
   - Log in as admin and create student/moderator accounts from **Admin → Users**
   - Assign moderators to clubs via **Admin → Events & Clubs**
   - Post opportunities and alumni profiles to populate student-facing modules

## 🧪 Testing & Verification

- Validate schema import: `SHOW TABLES IN university_networking_portal;`
- Create sample students/moderators and ensure CRUD actions populate `activity_log`
- Use the event registration module with capacities to confirm validation logic
- Review browser console/network tab for any missing assets or 401/403 redirects

## 📦 Deployment Notes

- For production, update `SESSION_COOKIE_NAME` and set `DB_PASS` securely
- Serve through Apache/Nginx with HTTPS; ensure `/includes` & `/database` are outside public web root or protected via server rules
- Configure a cron or manual process for database backups (`mysqldump`)

## 📚 License & Credits

This portal is designed as an academic DBMS showcase. Feel free to extend it with notifications, resume uploads, or alumni mentoring workflows.

---

Questions or enhancements? Open an issue or reach out—happy building!
