# University Networking Portal (UNP)

A comprehensive web-based platform for connecting students, administrators, and alumni. The portal streamlines internship postings, research opportunities, club events, and alumni networking in one centralized location.

## Features

### Role-Based Access Control
- **Admin**: Full system control - manage users, opportunities, alumni, clubs, and events
- **Moderator**: Manage club events and view registrations
- **Student**: Browse opportunities, register for events, and search alumni directory

### Core Functionalities
- ✅ Secure authentication system with password hashing
- ✅ User management (CRUD operations)
- ✅ Job, Internship, and Research opportunity management
- ✅ Event and workshop management
- ✅ Event registration system
- ✅ Alumni directory with advanced search and filtering
- ✅ Club management
- ✅ Activity logging
- ✅ Responsive design with modern UI

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP (Core PHP)
- **Database**: MySQL
- **Server**: Apache (XAMPP/WAMP for local development)

## Installation Instructions

### Prerequisites
- XAMPP, WAMP, or any PHP/MySQL server environment
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server

### Step 1: Clone/Download the Project
1. Download or clone this repository
2. Place the project folder in your web server directory:
   - **XAMPP**: `C:\xampp\htdocs\` (Windows) or `/opt/lampp/htdocs/` (Linux)
   - **WAMP**: `C:\wamp64\www\`
   - **MAMP**: `/Applications/MAMP/htdocs/` (Mac)

### Step 2: Database Setup
1. Start your MySQL server (via XAMPP/WAMP control panel)
2. Open phpMyAdmin (usually at `http://localhost/phpmyadmin`)
3. Import the database schema:
   - Click on "Import" tab
   - Choose the file: `database/schema.sql`
   - Click "Go" to import

   **OR** manually run the SQL file:
   ```sql
   -- Open database/schema.sql and execute it in phpMyAdmin SQL tab
   ```

### Step 3: Configure Database Connection
1. Open `config/database.php`
2. Update database credentials if needed (default XAMPP settings):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Empty for XAMPP default
   define('DB_NAME', 'university_portal');
   ```

### Step 4: Access the Application
1. Start Apache and MySQL from XAMPP/WAMP control panel
2. Open your web browser
3. Navigate to: `http://localhost/university-portal/` (or your project folder name)
4. You will be redirected to the login page

### Default Login Credentials

**Admin Account:**
- Institutional ID: `ADMIN001`
- Password: `admin123`

**Note**: You can create additional users through the admin panel after logging in.

## Project Structure

```
university-portal/
├── admin/                  # Admin panel pages
│   ├── dashboard.php
│   ├── users.php
│   ├── opportunities.php
│   ├── alumni.php
│   ├── clubs.php
│   ├── events.php
│   ├── event_registrations.php
│   ├── activity_logs.php
│   └── includes/
│       ├── sidebar.php
│       └── navbar.php
├── moderator/              # Moderator panel pages
│   ├── dashboard.php
│   ├── events.php
│   ├── event_registrations.php
│   └── includes/
│       ├── sidebar.php
│       └── navbar.php
├── student/                # Student panel pages
│   ├── dashboard.php
│   ├── opportunities.php
│   ├── opportunity_details.php
│   ├── events.php
│   ├── event_details.php
│   ├── alumni.php
│   ├── my_registrations.php
│   └── includes/
│       ├── sidebar.php
│       └── navbar.php
├── config/                 # Configuration files
│   ├── database.php
│   └── auth.php
├── database/               # Database files
│   └── schema.sql
├── assets/                 # Static assets
│   └── css/
│       └── style.css
├── index.php              # Main entry point
├── login.php              # Login page
├── logout.php             # Logout handler
├── unauthorized.php       # Access denied page
└── README.md              # This file
```

## Usage Guide

### For Administrators

1. **Login** with admin credentials
2. **Manage Users**:
   - Add new students, moderators, or admins
   - Edit user information
   - Assign moderator roles
   - Delete users

3. **Manage Opportunities**:
   - Post new job, internship, or research opportunities
   - Edit existing opportunities
   - Delete opportunities

4. **Manage Alumni**:
   - Add alumni records
   - Update alumni information
   - Search and filter alumni directory

5. **Manage Clubs**:
   - Create new clubs
   - Assign moderators to clubs
   - Edit club information

6. **View Events**:
   - View all events across the system
   - Check event registrations
   - Monitor activity logs

### For Moderators

1. **Login** with moderator credentials
2. **Create Events**:
   - Create new club events or workshops
   - Set event date, venue, and capacity
   - Link events to clubs

3. **Manage Events**:
   - Edit event details
   - View registration lists
   - Delete events

### For Students

1. **Login** with student credentials
2. **Browse Opportunities**:
   - View available jobs, internships, and research positions
   - Filter by type
   - View detailed information

3. **Register for Events**:
   - Browse upcoming events and workshops
   - Register for events
   - View registered events
   - Unregister if needed

4. **Alumni Directory**:
   - Search alumni by name, department, year, or profession
   - View alumni profiles
   - Access LinkedIn profiles

## Security Features

- ✅ Password hashing using PHP `password_hash()` (bcrypt)
- ✅ Prepared statements to prevent SQL injection
- ✅ Session-based authentication
- ✅ Role-based access control
- ✅ Input validation and sanitization
- ✅ CSRF protection ready (can be enhanced)

## Database Schema

The database includes the following main tables:
- `users` - User accounts (admin, moderator, student)
- `opportunities` - Job, internship, and research postings
- `events` - Club events and workshops
- `event_registrations` - Student event registrations
- `alumni` - Alumni directory
- `clubs` - Student clubs
- `activity_logs` - System activity tracking

## Customization

### Changing Colors
Edit `assets/css/style.css` and modify the CSS variables:
```css
:root {
    --admin-color: #2563eb;
    --moderator-color: #10b981;
    --student-color: #f59e0b;
}
```

### Adding New Features
The codebase is modular and well-structured. You can easily:
- Add new pages in respective role folders
- Extend database schema in `database/schema.sql`
- Add new functionality following existing patterns

## Troubleshooting

### Database Connection Error
- Ensure MySQL is running
- Check database credentials in `config/database.php`
- Verify database `university_portal` exists

### Page Not Found (404)
- Check Apache is running
- Verify project folder is in correct location
- Check URL path matches folder name

### Login Not Working
- Verify database is imported correctly
- Check default admin credentials
- Clear browser cache and cookies

### Permission Denied
- Check file permissions (Linux/Mac)
- Ensure web server has read access to all files

## Future Enhancements

- Email/SMS notifications for events
- Alumni login system for direct mentoring
- Resume upload and application tracking
- Advanced analytics dashboard
- Real-time notifications
- Mobile app integration

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review the code comments
3. Verify database and server configurations

## License

This project is created for educational purposes as part of a DBMS project.

## Credits

Developed as a comprehensive University Networking Portal demonstrating:
- Database design and normalization
- CRUD operations
- Role-based access control
- Secure authentication
- Modern web development practices

---

**Note**: This is a demonstration project. For production use, additional security measures, error handling, and optimizations should be implemented.
