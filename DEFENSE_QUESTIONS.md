# PC Hardware Inventory System - Defense Questions & Answers

This document provides potential questions that might be asked during defense and comprehensive answers.

---

## 1. SYSTEM ARCHITECTURE & DESIGN

### Q: What architecture pattern does your system use?
**A:** The system uses a **Model-View-Controller (MVC)-like pattern** with:
- **Config files** (`config/`) - Database connection, session management, security functions
- **Pages** (`pages/`) - Controllers that handle business logic and render views
- **Includes** (`includes/`) - Shared UI components (header, footer)
- **Assets** (`assets/`) - CSS, JavaScript, images

### Q: Why did you choose PHP for this project?
**A:** PHP was chosen because:
1. It's widely used for web development and easy to deploy
2. Excellent MySQL integration
3. Simple learning curve for team members
4. Large community support and documentation
5. Cost-effective hosting options

### Q: What database system are you using and why?
**A:** We use **MySQL/MariaDB** because:
1. Relational structure fits our inventory data model
2. ACID compliance ensures data integrity
3. Free and open-source
4. Excellent PHP integration via mysqli
5. Widely supported by hosting providers

---

## 2. SECURITY FEATURES

### Q: How do you prevent SQL Injection attacks?
**A:** We use **Prepared Statements** with parameterized queries:
```php
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
```
This ensures user input is never directly concatenated into SQL queries.

### Q: How do you prevent XSS (Cross-Site Scripting) attacks?
**A:** We use the `escapeOutput()` function that applies `htmlspecialchars()`:
```php
function escapeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
```
All output displayed to users is escaped to prevent script injection.

### Q: How are passwords stored?
**A:** Passwords are **hashed using PHP's `password_hash()`** with the default bcrypt algorithm:
```php
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
```
This provides:
- One-way hashing (cannot be reversed)
- Automatic salt generation
- Adaptive cost factor

### Q: How do you manage user sessions securely?
**A:** Our session security includes:
1. **HTTPOnly cookies** - Prevents JavaScript access to session cookies
2. **SameSite=Strict** - Prevents CSRF attacks
3. **Session regeneration** - New session ID every 30 minutes
4. **Secure session configuration** in `session.php`

### Q: Do you have role-based access control?
**A:** Yes, we have two roles:
1. **Admin** - Full access to all features including user management
2. **Staff** - Can manage hardware and view history, but cannot manage users

Implemented via `requireAdmin()` and `isAdmin()` functions.

---

## 3. DATABASE DESIGN

### Q: Explain your database schema.
**A:** We have 4 main tables:
1. **categories** - Hardware categories (CPU, RAM, SSD, etc.)
2. **hardware** - Main inventory items with quantities
3. **users** - System users with roles
4. **inventory_history** - Audit trail of all changes

### Q: Why did you denormalize the history table?
**A:** The history table stores denormalized data (hardware_name, category_name, user_name) because:
1. **Permanent record** - History remains even if hardware/users are deleted
2. **Performance** - No JOIN needed when viewing history
3. **Audit integrity** - Historical data reflects what existed at that time

### Q: How do you track inventory changes?
**A:** Every add, update, or delete operation logs to `inventory_history` with:
- Old values (unused, in_use, damaged, repair)
- New values
- Who made the change
- When the change was made
- Type of action (Added/Updated/Deleted)

---

## 4. USER INTERFACE (UI/UX)

### Q: What design principles did you follow?
**A:** We applied **HCI (Human-Computer Interaction) principles**:
1. **Visibility** - Clear status indicators with color-coded badges
2. **Feedback** - Loading states, success/error messages
3. **Consistency** - Unified color palette and design patterns
4. **Error Prevention** - Confirmation dialogs before delete actions
5. **Flexibility** - Works on both desktop and mobile

### Q: How is your system responsive?
**A:** We use:
1. **Bootstrap 5** grid system for layouts
2. **CSS media queries** for different screen sizes
3. **Sidebar navigation** for desktop, off-canvas menu for mobile
4. **Responsive tables** that hide less critical columns on mobile

### Q: Why did you choose a sidebar navigation instead of a navbar?
**A:** Sidebar benefits:
1. Better organization for more menu items
2. Always visible navigation on desktop
3. More screen space for content
4. Modern, professional appearance
5. Better UX for inventory management workflows

---

## 5. FEATURES & FUNCTIONALITY

### Q: What are the main features of your system?
**A:**
1. **Dashboard** - Overview statistics, recent items, low stock alerts
2. **Hardware Management** - CRUD operations with filtering
3. **CSV Import/Export** - Bulk data operations
4. **User Management** - Admin can manage system users
5. **Inventory History** - Complete audit trail
6. **Search & Filter** - Find items quickly

### Q: How does the CSV import work?
**A:** The import feature:
1. Accepts CSV files with specific format
2. Validates each row before insertion
3. Supports category names (not just IDs)
4. Provides import summary with error details
5. Logs all imported items to history

### Q: How do you handle low stock alerts?
**A:** The dashboard shows items where `unused_quantity < 2`:
- Color-coded badges (warning for low, danger for out of stock)
- Quick visibility on dashboard
- Helps proactive inventory management

---

## 6. POTENTIAL IMPROVEMENTS

### Q: What limitations does your system have?
**A:**
1. No API for external integrations
2. Single database (no replication)
3. No email notifications for low stock
4. No barcode/QR code scanning
5. No multi-location inventory tracking (locations are text-based)

### Q: How would you scale this system?
**A:**
1. Add Redis/Memcached for caching
2. Implement database read replicas
3. Add REST API for mobile apps
4. Use message queues for async operations
5. Containerize with Docker for easier deployment

### Q: What future features would you add?
**A:**
1. Email/SMS notifications for low stock
2. Barcode scanning integration
3. Reports and analytics dashboard
4. Equipment checkout/return system
5. Maintenance scheduling
6. Mobile app with offline sync

---

## 7. TECHNICAL QUESTIONS

### Q: How do you handle concurrent updates?
**A:** Currently, we use database-level locking through transactions. For high-concurrency, we would implement:
1. Optimistic locking with version numbers
2. Database transactions with appropriate isolation levels

### Q: How is the "Remember Me" feature implemented?
**A:** When checked:
1. Username is stored in a cookie for 30 days
2. Only username (not password) is remembered
3. Cookie is used to pre-fill the login form

### Q: How do you validate user input?
**A:** Multiple layers:
1. **Client-side** - HTML5 validation (`required`, `minlength`)
2. **Server-side** - `sanitizeInput()` and `sanitizeForDB()` functions
3. **Database** - Prepared statements for final protection

---

## 8. DEPLOYMENT & MAINTENANCE

### Q: What are the system requirements?
**A:**
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx web server
- Modern web browser

### Q: How would you deploy this system?
**A:**
1. Set up LAMP/LEMP stack
2. Import `database.sql`
3. Configure `config/database.php`
4. Set proper file permissions
5. Enable HTTPS for production

### Q: How do you handle backups?
**A:** Recommended approach:
1. Regular MySQL dumps (daily)
2. File system backups for uploads
3. Off-site backup storage
4. Test restore procedures regularly

---

## 9. TESTING

### Q: How did you test the system?
**A:**
1. **Manual testing** - All CRUD operations
2. **Input validation testing** - SQL injection, XSS attempts
3. **Responsive testing** - Multiple screen sizes
4. **Cross-browser testing** - Chrome, Firefox, Edge

### Q: What edge cases did you consider?
**A:**
1. Empty database states
2. Special characters in hardware names
3. Large quantity numbers
4. Concurrent user operations
5. Session timeout scenarios

---

## 10. CODE QUALITY

### Q: How did you ensure code quality?
**A:**
1. Consistent naming conventions
2. Separation of concerns (config, logic, view)
3. Reusable functions for common operations
4. Comments explaining complex logic
5. Security-first approach

### Q: What coding standards did you follow?
**A:**
- PSR-12 style guide for PHP
- Bootstrap conventions for CSS
- ES6+ for JavaScript
- Semantic HTML5 markup
