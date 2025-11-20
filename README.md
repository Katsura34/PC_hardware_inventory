# PC Hardware Inventory System

A comprehensive web-based inventory management system for tracking PC hardware components at ACLC College of Ormoc.

## Features

### Core Functionality
- 🖥️ **Hardware Management** - Add, edit, delete, and track hardware items
- 📊 **Dashboard** - Real-time statistics and insights
- 📜 **History Tracking** - Complete audit trail of all inventory changes
- 👥 **User Management** - Admin and staff role management
- 📤 **CSV Import** - Bulk import hardware from CSV files
- 🏢 **Location Tracking** - Track hardware by physical location

### Recent Updates (v2.0)
- ✅ **Denormalized History** - History preserved even after deletions
- ✅ **No Foreign Key Errors** - Fixed constraint issues when deleting users
- ✅ **Location Dropdown** - Smart location suggestions with custom input
- ✅ **CSV Import Relocation** - Moved to Hardware page for better workflow
- ✅ **Complete Audit Trail** - All operations logged with full details

## Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx or PHP built-in server
- Modern web browser

## Installation

### New Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Katsura34/PC_hardware_inventory.git
   cd PC_hardware_inventory
   ```

2. **Create database and import schema**
   ```bash
   mysql -u root -p
   ```
   ```sql
   source database.sql;
   exit;
   ```

3. **Configure database connection**
   Edit `config/database.php` if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'pc_inventory');
   ```

4. **Start web server**
   ```bash
   # Using PHP built-in server
   php -S localhost:8000
   
   # Or configure Apache/Nginx to point to the directory
   ```

5. **Access the system**
   - URL: `http://localhost:8000/login.php`
   - Default Admin: `admin` / `password123`
   - Default Staff: `staff01` / `password123`

### Upgrading from Previous Version

If you have an existing installation, see [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) for upgrade instructions.

## Usage

### Adding Hardware
1. Navigate to **Hardware** page
2. Click **Add Hardware** button
3. Fill in the details (name, category, quantities, location)
4. Submit the form

### Importing from CSV
1. Navigate to **Hardware** page
2. Click **Import CSV** button
3. Select your CSV file (see `sample_hardware.csv` for format)
4. Preview the data
5. Click **Import** to process

### CSV Format
```csv
name,category_id,type,brand,model,serial_number,unused_quantity,in_use_quantity,damaged_quantity,repair_quantity,location
AMD Ryzen 5,1,3rd Gen,AMD,Ryzen 5 3600,SNCPU002,3,2,0,0,Lab 1
```

### Managing Users (Admin Only)
1. Navigate to **Users** page
2. Add or edit users
3. Assign roles (admin/staff)

### Viewing History
1. Navigate to **History** page
2. View all inventory changes
3. Search and filter as needed

## Features in Detail

### Denormalized History System
The system now stores complete data in the history table instead of just IDs. This means:
- ✅ History is preserved even when hardware or users are deleted
- ✅ No foreign key constraint errors
- ✅ Better reporting and auditing
- ✅ Self-contained history records

### Location Management
- Smart dropdown with common locations (Lab 1, Lab 2, Office, etc.)
- Allows custom location input
- Autocompletes with existing locations
- Reduces data entry errors

### CSV Import
- Bulk import multiple hardware items
- Preview before importing
- Error reporting for invalid data
- Automatically logs to history

## File Structure

```
PC_hardware_inventory/
├── assets/
│   ├── css/           # Stylesheets
│   ├── js/            # JavaScript files
│   └── images/        # Images and icons
├── config/
│   ├── database.php   # Database configuration
│   ├── session.php    # Session management
│   └── security.php   # Security functions
├── includes/
│   ├── header.php     # Common header
│   └── footer.php     # Common footer
├── pages/
│   ├── hardware.php   # Hardware management
│   ├── history.php    # History view
│   ├── users.php      # User management
│   └── import_csv.php # CSV import handler
├── database.sql       # Database schema
├── migration_denormalize_history.sql  # Migration script
├── dashboard.php      # Main dashboard
├── login.php          # Login page
├── logout.php         # Logout handler
└── README.md          # This file
```

## Documentation

- [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) - Upgrade instructions
- [CHANGES.md](CHANGES.md) - Detailed changelog
- [TESTING_GUIDE.md](TESTING_GUIDE.md) - Testing procedures

## Security

- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS prevention with output escaping
- CSRF protection with session tokens
- Role-based access control (admin/staff)

## Default Credentials

⚠️ **Important:** Change default passwords after first login!

- **Admin:** `admin` / `password123`
- **Staff:** `staff01` / `password123`

## Troubleshooting

### Foreign Key Constraint Error
If you see "Cannot delete or update a parent row", you need to run the migration script:
```bash
mysql -u root -p pc_inventory < migration_denormalize_history.sql
```

### CSV Import Issues
- Ensure CSV format matches the template
- Check that category_id values exist in categories table
- Verify file encoding is UTF-8

### Location Dropdown Not Working
- Clear browser cache
- Check JavaScript console for errors
- Ensure main.js is loaded correctly

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For issues, questions, or contributions:
- GitHub Issues: https://github.com/Katsura34/PC_hardware_inventory/issues
- Documentation: See the `/docs` folder

## Credits

Developed for ACLC College of Ormoc
- Institution: ACLC COLLEGE OF ORMOC
- System: PC HARDWARE INVENTORY SYSTEM

## Version History

### Version 2.0 (Current)
- Denormalized history system
- Fixed foreign key constraint errors
- Added location dropdown with suggestions
- Moved CSV import to Hardware page
- Complete audit trail preservation
- Migration support for existing installations

### Version 1.0
- Initial release
- Basic hardware management
- User management
- History tracking
- CSV import (navbar)

---

**Last Updated:** 2025
**Status:** ✅ Production Ready

