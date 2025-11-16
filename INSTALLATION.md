# Installation Guide

## Quick Start Guide for PC Hardware Inventory System

### For XAMPP/WAMP/MAMP Users

1. **Copy Files**
   - Copy the entire `PC_hardware_inventory` folder to your web server's document root
   - XAMPP: `C:\xampp\htdocs\`
   - WAMP: `C:\wamp64\www\`
   - MAMP: `/Applications/MAMP/htdocs/`

2. **Start Services**
   - Start Apache and MySQL from your control panel

3. **Create Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Click "Import" tab
   - Choose file: `database.sql` from the project folder
   - Click "Go" to import

4. **Configure Database (if needed)**
   - Open `config/database.php`
   - Update credentials if your MySQL setup is different:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');  // Your MySQL password
     define('DB_NAME', 'pc_inventory');
     ```

5. **Access the Application**
   - Open browser: `http://localhost/PC_hardware_inventory/login.php`
   - Login with:
     - **Admin**: username: `admin`, password: `password123`
     - **Staff**: username: `staff01`, password: `password123`

### For Production Servers

1. **Upload Files**
   - Upload all files via FTP/SFTP to your web server

2. **Create Database**
   - Create a MySQL database via cPanel or command line
   - Import `database.sql` file

3. **Update Configuration**
   - Edit `config/database.php` with your server's database credentials

4. **Set Permissions**
   ```bash
   chmod 755 /path/to/PC_hardware_inventory
   chmod 644 /path/to/PC_hardware_inventory/*.php
   ```

5. **Enable HTTPS**
   - Configure SSL certificate
   - Update session settings in `config/session.php`:
     ```php
     ini_set('session.cookie_secure', 1);
     ```

### Testing the Installation

1. **Login Test**
   - Try logging in with both admin and staff accounts
   - Verify dashboard loads correctly

2. **Hardware Test**
   - Navigate to Hardware page
   - Try adding a new hardware item
   - Edit and delete test items

3. **User Management Test (Admin only)**
   - Navigate to Users page
   - Create a test user
   - Edit and verify changes

4. **History Test**
   - Check that all actions are logged in History page

### Troubleshooting

**Error: "Connection failed"**
- MySQL service is not running
- Database credentials are incorrect
- Database doesn't exist

**Error: "Call to undefined function mysqli_connect()"**
- PHP mysqli extension is not enabled
- Enable it in php.ini: `extension=mysqli`

**Error: "Headers already sent"**
- Check for whitespace before `<?php` in PHP files
- Ensure no output before session_start()

**CSS/JS Not Loading**
- Clear browser cache
- Check file paths in includes/header.php and includes/footer.php
- Verify files exist in assets/ directory

### Security Checklist (Production)

- [ ] Change default passwords
- [ ] Enable HTTPS/SSL
- [ ] Update database credentials
- [ ] Set strong MySQL password
- [ ] Disable directory listing
- [ ] Configure firewall rules
- [ ] Regular database backups
- [ ] Keep PHP/MySQL updated

### System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache 2.4+ or Nginx
- Modern web browser

### Support

Check README_INVENTORY.md for detailed documentation and usage guide.
