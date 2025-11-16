# PC Hardware Inventory System

A modern, secure, and user-friendly inventory management system for PC hardware built with PHP, MySQL, Bootstrap 5, and JavaScript.

## Features

- **User Authentication**: Secure login system with session management
- **Role-Based Access Control**: Admin and Staff roles with different permissions
- **Hardware Management**: Complete CRUD operations for hardware inventory
- **Real-time Statistics**: Dashboard with key metrics and analytics
- **Inventory Tracking**: Track available, in-use, damaged, and repair quantities
- **History Logging**: Complete audit trail of all inventory changes
- **User Management**: Admin can manage user accounts (Admin only)
- **Modern UI**: Clean and responsive design using Bootstrap 5
- **Search & Export**: Search functionality and CSV export capabilities
- **Security**: Password hashing, SQL injection prevention, XSS protection, CSRF tokens

## Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.3
- **Icons**: Bootstrap Icons

## Project Structure

```
PC_hardware_inventory/
├── config/
│   ├── database.php        # Database connection
│   ├── session.php         # Session management
│   └── security.php        # Security functions
├── includes/
│   ├── header.php          # Common header (React-like component)
│   └── footer.php          # Common footer (React-like component)
├── pages/
│   ├── hardware.php        # Hardware management
│   ├── history.php         # Inventory history
│   └── users.php           # User management (Admin only)
├── assets/
│   ├── css/
│   │   └── style.css       # Custom styles
│   └── js/
│       └── main.js         # Custom JavaScript
├── database.sql            # Database schema and sample data
├── login.php               # Login page
├── dashboard.php           # Main dashboard
├── logout.php              # Logout handler
└── README_INVENTORY.md     # This file
```

## Installation

### Prerequisites

- Apache/Nginx web server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- phpMyAdmin (optional, for database management)

### Step 1: Clone or Download

Clone this repository or download the ZIP file and extract it to your web server's document root (e.g., `htdocs` for XAMPP/WAMP).

### Step 2: Database Setup

1. Open phpMyAdmin or MySQL command line
2. Import the database schema:
   ```sql
   mysql -u root -p < database.sql
   ```
   Or manually execute the SQL file in phpMyAdmin

3. The database `pc_inventory` will be created with sample data

### Step 3: Configure Database Connection

Edit `config/database.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Your MySQL username
define('DB_PASS', '');              // Your MySQL password
define('DB_NAME', 'pc_inventory');
```

### Step 4: Access the Application

Open your web browser and navigate to:
```
http://localhost/PC_hardware_inventory/login.php
```

## Default Credentials

### Admin Account
- **Username**: `admin`
- **Password**: `password123`

### Staff Account
- **Username**: `staff01`
- **Password**: `password123`

**Important**: Change these passwords after first login!

## Usage Guide

### Dashboard
- View overall statistics (total items, quantities, status)
- See recent hardware additions
- Monitor low stock alerts
- View category summaries

### Hardware Management
- Add new hardware items with detailed information
- Edit existing hardware records
- Delete hardware items
- Track quantities by status (Available, In Use, Damaged, Repair)
- Search and filter hardware
- Export data to CSV

### Inventory History
- View complete audit trail of all changes
- Track who made changes and when
- See before/after values for updates
- Filter and search history records

### User Management (Admin Only)
- Create new user accounts
- Edit user information
- Change user roles (Admin/Staff)
- Delete users (except yourself)
- Reset user passwords

## Security Features

1. **Password Hashing**: All passwords are hashed using PHP's `password_hash()` with bcrypt
2. **SQL Injection Prevention**: Prepared statements used for all database queries
3. **XSS Protection**: Output escaping with `htmlspecialchars()`
4. **CSRF Protection**: Token-based CSRF protection for forms
5. **Session Security**: Secure session configuration with httponly cookies
6. **Input Validation**: Server-side validation for all user inputs
7. **Access Control**: Role-based permissions for different features

## Customization

### Changing Colors
Edit `assets/css/style.css` and modify the CSS variables:

```css
:root {
    --primary-color: #0d6efd;
    --secondary-color: #6c757d;
    /* ... other colors */
}
```

### Adding Categories
1. Login as admin
2. Access database via phpMyAdmin
3. Insert new categories in the `categories` table

### Modifying UI
- Edit `includes/header.php` for navigation changes
- Edit `includes/footer.php` for footer modifications
- Modify individual pages in the `pages/` directory

## Troubleshooting

### Cannot Connect to Database
- Verify MySQL is running
- Check database credentials in `config/database.php`
- Ensure database exists and is accessible

### Login Not Working
- Clear browser cookies/cache
- Verify user exists in `users` table
- Check password is correctly hashed

### Session Issues
- Ensure PHP session is enabled
- Check directory permissions for session storage
- Verify session settings in `config/session.php`

### CSS/JS Not Loading
- Check file paths in header/footer includes
- Verify files exist in `assets/` directory
- Clear browser cache

## System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache 2.4+ or Nginx
- **Browser**: Modern browser (Chrome, Firefox, Safari, Edge)

## Best Practices

1. **Regular Backups**: Backup database regularly
2. **Strong Passwords**: Use strong passwords for all accounts
3. **HTTPS**: Use SSL/TLS in production
4. **Update Regularly**: Keep PHP and MySQL updated
5. **Monitor Logs**: Check error logs periodically

## Support

For issues or questions:
1. Check this README first
2. Review the troubleshooting section
3. Check database and PHP error logs
4. Verify file permissions

## License

This project is provided as-is for educational and commercial use.

## Credits

- Bootstrap 5: https://getbootstrap.com/
- Bootstrap Icons: https://icons.getbootstrap.com/
- PHP: https://www.php.net/
- MySQL: https://www.mysql.com/

---

**Note**: This is a complete, production-ready inventory system with security best practices. Always change default passwords and use HTTPS in production environments.
