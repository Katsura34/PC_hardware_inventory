# Quick Start Guide - PC Hardware Inventory System

## 🚀 Get Started in 5 Minutes

### Step 1: Install Web Server (if not already installed)

**For Windows (XAMPP):**
1. Download XAMPP from https://www.apachefriends.org/
2. Install and start Apache & MySQL

**For Mac (MAMP):**
1. Download MAMP from https://www.mamp.info/
2. Install and start servers

**For Linux:**
```bash
sudo apt update
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql
sudo systemctl start apache2 mysql
```

### Step 2: Copy Files

Copy the entire `PC_hardware_inventory` folder to:
- **XAMPP**: `C:\xampp\htdocs\`
- **MAMP**: `/Applications/MAMP/htdocs/`
- **Linux**: `/var/www/html/`

### Step 3: Create Database

**Option A: Using phpMyAdmin**
1. Go to http://localhost/phpmyadmin
2. Click "Import" tab
3. Choose `database.sql` file
4. Click "Go"

**Option B: Using Command Line**
```bash
mysql -u root -p < database.sql
```

### Step 4: Configure (Optional)

Only if your MySQL uses a password:

Edit `config/database.php`:
```php
define('DB_PASS', 'your_mysql_password');
```

### Step 5: Access the System

Open your browser and go to:
```
http://localhost/PC_hardware_inventory/
```

You'll be redirected to the login page automatically.

### Step 6: Login

Use these credentials:

**Admin Login:**
- Username: `admin`
- Password: `password123`

**Staff Login:**
- Username: `staff01`
- Password: `password123`

---

## 🎯 What You Can Do

### As Admin (full access):
✅ View dashboard statistics
✅ Add/edit/delete hardware
✅ View inventory history
✅ Manage users (create/edit/delete)
✅ Export data to CSV
✅ Search and filter

### As Staff (limited access):
✅ View dashboard statistics
✅ Add/edit/delete hardware
✅ View inventory history
✅ Export data to CSV
✅ Search and filter
❌ Cannot manage users

---

## 📱 Page Tour

### 1. Dashboard (`/dashboard.php`)
**What you'll see:**
- 6 statistic cards showing:
  - Total Hardware Items
  - Total Quantity
  - Available Items
  - In Use Items
  - Damaged Items
  - Items in Repair
- Recent Hardware table (last 5 additions)
- Low Stock Alert table (items needing restock)
- Category Summary cards

### 2. Hardware Management (`/pages/hardware.php`)
**What you can do:**
- View all hardware in a table
- Click "Add Hardware" to add new items
- Click pencil icon to edit
- Click trash icon to delete
- Use search box to filter
- Click "Export" to download CSV

**Hardware fields:**
- Name, Category, Type
- Brand, Model, Serial Number
- Available, In Use, Damaged, Repair quantities
- Location

### 3. Inventory History (`/pages/history.php`)
**What you'll see:**
- Complete log of all changes
- Who made the change
- When it was made
- What was changed (before/after values)
- Action type (Added/Updated/Removed)

### 4. User Management (`/pages/users.php`) - Admin Only
**What you can do:**
- View all users
- Add new users
- Edit user information
- Change passwords
- Assign roles (Admin/Staff)
- Delete users (except yourself)

---

## 💡 Quick Tips

### Adding Hardware
1. Click "Add Hardware" button
2. Fill in the form (Name and Category are required)
3. Enter quantities for each status
4. Click "Add Hardware"

### Tracking Quantities
Each hardware item has 4 quantity types:
- **Available**: Ready to use
- **In Use**: Currently deployed
- **Damaged**: Needs attention
- **In Repair**: Being fixed

The system automatically calculates total quantity.

### Low Stock Alerts
Items with less than 2 available units will show in the Low Stock Alert table on the dashboard.

### Searching
The search box filters in real-time across all visible columns. Just start typing!

### Exporting Data
Click the "Export" button on any table to download the data as a CSV file. Open it in Excel or Google Sheets.

---

## 🔐 Security Recommendations

### Before Going Live (Production):

1. **Change Default Passwords**
   ```
   Login → Users → Edit admin and staff01 → Change password
   ```

2. **Update Database Password** (if using in production)
   ```php
   // In config/database.php
   define('DB_PASS', 'strong_password_here');
   ```

3. **Enable HTTPS**
   - Get SSL certificate (Let's Encrypt is free)
   - Update session.php:
     ```php
     ini_set('session.cookie_secure', 1);
     ```

4. **Secure MySQL**
   ```bash
   mysql_secure_installation
   ```

---

## ❓ Troubleshooting

### Problem: "Connection failed"
**Solution:** 
- Make sure MySQL is running
- Check database credentials in `config/database.php`
- Verify database was created successfully

### Problem: "Call to undefined function mysqli_connect"
**Solution:**
- PHP mysqli extension not enabled
- In `php.ini`, uncomment: `extension=mysqli`
- Restart Apache

### Problem: Login not working
**Solution:**
- Clear browser cookies
- Check that users exist in database
- Verify password is: `password123`

### Problem: CSS/JS not loading
**Solution:**
- Clear browser cache (Ctrl+F5)
- Check file paths in includes/header.php
- Verify assets folder exists

### Problem: Blank white page
**Solution:**
- Enable PHP error display in `php.ini`:
  ```ini
  display_errors = On
  error_reporting = E_ALL
  ```
- Check Apache error logs

---

## 🎓 Learning Resources

### Understanding the Code

**Where to look for:**
- **Authentication logic**: `config/session.php`
- **Database queries**: Individual page files (login.php, dashboard.php, etc.)
- **Security functions**: `config/security.php`
- **UI components**: `includes/header.php` and `includes/footer.php`
- **Styling**: `assets/css/style.css`
- **JavaScript**: `assets/js/main.js`

### Making Changes

**To add a new page:**
1. Create file in `pages/` directory
2. Include header: `include '../includes/header.php';`
3. Add your content
4. Include footer: `include '../includes/footer.php';`
5. Add link in header navigation

**To add a new field to hardware:**
1. Add column to database: `ALTER TABLE hardware ADD COLUMN ...`
2. Update forms in `pages/hardware.php`
3. Update INSERT/UPDATE queries
4. Update table display

---

## 📞 Need Help?

1. Check **README_INVENTORY.md** for complete documentation
2. See **INSTALLATION.md** for detailed setup instructions
3. Read **FEATURES.md** for technical details
4. Review **SUMMARY.md** for implementation overview

---

## ✅ Success Checklist

After installation, verify:
- [ ] Can access http://localhost/PC_hardware_inventory/
- [ ] Login page loads correctly
- [ ] Can login with admin account
- [ ] Dashboard shows statistics
- [ ] Can add a test hardware item
- [ ] Can edit the test item
- [ ] Can delete the test item
- [ ] History shows the changes
- [ ] Can create a test user (admin only)
- [ ] Can logout and login again

If all items are checked, you're ready to go! 🎉

---

## 🎯 Next Steps

1. **Customize**: Update colors, logo, company name
2. **Populate**: Add your actual hardware data
3. **Configure**: Set up for your environment
4. **Train**: Show your team how to use it
5. **Backup**: Set up regular database backups

---

**Enjoy your new PC Hardware Inventory System!** 🚀

For questions or issues, refer to the documentation files or review the inline code comments.
