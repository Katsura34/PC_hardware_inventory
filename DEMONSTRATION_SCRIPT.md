# PC Hardware Inventory System - Demonstration Script

This document provides a comprehensive script for demonstrating the PC Hardware Inventory System from the login page through all features. Use this as a guide when presenting the system to stakeholders, panelists, or users.

---

## Table of Contents
1. [Opening Introduction](#1-opening-introduction)
2. [Login Page](#2-login-page)
3. [Dashboard](#3-dashboard)
4. [Hardware Management](#4-hardware-management)
5. [Audit Trail (History)](#5-audit-trail-history)
6. [User Management (Admin Only)](#6-user-management-admin-only)
7. [Backup & Restore (Admin Only)](#7-backup--restore-admin-only)
8. [Profile Settings](#8-profile-settings)
9. [Logout](#9-logout)
10. [Key Features Summary](#10-key-features-summary)

---

## 1. Opening Introduction

> **What to say:**
> 
> "Good [morning/afternoon/evening]. Today I will demonstrate the PC Hardware Inventory System developed for ACLC College of Ormoc. This web-based system helps manage and track all PC hardware components in the institution, including CPUs, RAM, storage drives, monitors, and other peripherals.
> 
> The system features:
> - A modern, responsive user interface built with Bootstrap 5
> - Role-based access control (Admin and Staff)
> - Complete audit trail of all inventory changes
> - Database backup and restore capabilities
> - CSV import/export functionality
> 
> Let me walk you through the system, starting from the login page."

---

## 2. Login Page

**URL:** `login.php`

> **What to say:**
> 
> "This is the login page of the PC Hardware Inventory System. As you can see, it features the ACLC College logo and a clean, professional login form.
> 
> **Security Features:**
> - The system uses secure password hashing
> - SQL injection prevention with prepared statements
> - XSS protection with output escaping
> - Session-based authentication
> 
> **User Experience Features:**
> - Password visibility toggle - users can click the eye icon to show/hide their password
> - 'Remember Me' functionality to save the username for convenience
> - Clear error messages when login fails
> - Loading overlay when signing in
> 
> Let me log in with the admin account to show you the full features of the system."

**Demo Actions:**
1. Show the login form fields
2. Click the eye icon to demonstrate password visibility toggle
3. Point out the "Remember me" checkbox
4. Enter credentials: `admin` / `password123`
5. Click "Sign In" to proceed

---

## 3. Dashboard

**URL:** `dashboard.php`

> **What to say:**
> 
> "After logging in, users are directed to the Dashboard. This is the central hub that provides an at-a-glance overview of the entire inventory.
> 
> **Top Navigation Bar:**
> - The navigation bar shows all available pages
> - The current page is highlighted for easy identification
> - Admin users see additional options like 'Users' and 'Backup'
> - The user's name and role are displayed on the right side
> 
> **Welcome Banner:**
> - Personalized greeting with the logged-in user's name
> - Today's date for reference
> - Quick action button to add new hardware
> 
> **Statistics Cards:**
> These six cards provide real-time inventory metrics:
> 1. **Total Items** - Total number of unique hardware types
> 2. **Total Quantity** - Sum of all hardware units
> 3. **Available** - Units ready for deployment
> 4. **In Use** - Currently deployed units
> 5. **Damaged** - Units that need repair or replacement
> 6. **In Repair** - Units currently being fixed
> 
> **Recent Hardware Section:**
> - Shows the 5 most recently added items
> - Displays name, category, total quantity, and available count
> - Quick link to view all hardware
> 
> **Low Stock Alert Section:**
> - Highlights items with low availability (less than 2 units)
> - Shows warning badges for items at risk
> - Helps administrators prioritize restocking
> 
> **Out of Stock Items:**
> - Lists all items with zero available units
> - Shows where units currently are (in use, damaged, repair)
> - Critical for inventory planning
> 
> **Categories Summary:**
> - Visual overview of all hardware categories
> - Shows item count and total quantity per category
> - Examples: CPU, RAM, SSD, HDD, Monitor, Keyboard, etc.
> 
> This dashboard gives administrators immediate visibility into inventory health and alerts them to potential issues."

**Demo Actions:**
1. Point to the navigation bar and explain each menu item
2. Show the statistics cards and their real-time values
3. Scroll down to show recent hardware
4. Show the low stock alert section
5. Explain the categories summary

---

## 4. Hardware Management

**URL:** `pages/hardware.php`

> **What to say:**
> 
> "The Hardware Management page is where users can view, add, edit, and manage all hardware items in the inventory.
> 
> **Page Header:**
> - Clear title with total item count
> - Import CSV button for bulk data entry
> - Add Hardware button for individual entries
> 
> **Table Features:**
> - **Search:** Click the search button or press '/' to open the search panel. Search filters the table in real-time by name, category, brand, or model.
> - **Filters:** Filter by category, brand, or model using the filter dropdown
> - **Export:** Download inventory data as CSV for reporting
> - **Pagination:** Navigate through large datasets
> 
> **Hardware Table Columns:**
> - Checkbox for batch selection
> - Name and category
> - Brand/Model information
> - Serial number
> - Total quantity
> - Available (unused) quantity
> - In use, damaged, and repair quantities
> - Location
> - Action buttons (Edit/Delete)
> 
> **Batch Operations:**
> - Select multiple items using checkboxes
> - Update status in bulk (change quantities)
> - Delete multiple items at once
> 
> **Let me demonstrate adding a new hardware item...**"

### 4.1 Adding Hardware

> "Click the 'Add Hardware' button to open the form.
> 
> **Form Fields:**
> - **Hardware Name** (required) - Descriptive name like 'Intel Core i5-12400'
> - **Category** (required) - Select from existing categories or add new
> - **Type** - Specific type within category
> - **Brand** - Manufacturer name
> - **Model** - Model number
> - **Serial Number** - For tracking individual units
> - **Location** - Physical location (Lab 1, Lab 2, Office, etc.)
> 
> **Quantity Fields:**
> - **Available** - Units ready for deployment
> - **In Use** - Currently deployed units
> - **Damaged** - Broken units
> - **In Repair** - Units being fixed
> 
> The total quantity is automatically calculated from these four fields."

**Demo Actions:**
1. Click "Add Hardware" button
2. Fill in sample data
3. Show category dropdown (with "Add New Category" option)
4. Show location dropdown
5. Explain quantity fields
6. Submit the form

### 4.2 Editing Hardware

> "To edit an item, click the Edit button. The form pre-fills with existing data, and you can modify any field. All changes are logged in the audit trail."

**Demo Actions:**
1. Click Edit on an existing item
2. Change a quantity
3. Save and show the update

### 4.3 Deleting Hardware (Soft Delete)

> "When you delete hardware, it's not permanently removed. The system uses 'soft delete' - items are moved to trash and can be restored.
> 
> **Trash/Deleted Items View:**
> - Click 'View Trash' to see deleted items
> - Items can be restored by any logged-in user
> - Only admins can permanently delete items"

**Demo Actions:**
1. Delete an item (soft delete)
2. Click "View Trash"
3. Show restore and permanent delete options

### 4.4 CSV Import

> "For bulk data entry, use the CSV Import feature.
> 
> **CSV Format:**
> The system expects these columns:
> name, category, type, brand, model, serial_number, unused_quantity, in_use_quantity, damaged_quantity, repair_quantity, location
> 
> **Import Process:**
> 1. Click 'Import CSV'
> 2. Select your file
> 3. Optionally set a default location
> 4. Preview the data
> 5. Click Import
> 
> All imported items are automatically logged in the audit trail."

**Demo Actions:**
1. Click "Import CSV" button
2. Show the format instructions
3. Demonstrate with sample_hardware.csv if available

---

## 5. Audit Trail (History)

**URL:** `pages/history.php`

> **What to say:**
> 
> "The Audit Trail page provides a complete history of all inventory changes. This is crucial for accountability and tracking.
> 
> **Logged Actions:**
> - **Added** (green) - New hardware items
> - **Updated** (yellow) - Modified items
> - **Deleted** (red) - Removed items
> - **Restored** (blue) - Items recovered from trash
> 
> **Information Captured:**
> - Date and time of the action
> - Hardware item name and serial number
> - Category
> - Who made the change
> - Quantity changes
> - Before and after status
> 
> **Filtering Options:**
> - Filter by action type
> - Filter by date range
> - Search by hardware name or user
> 
> **Denormalized Data:**
> The system stores complete information at the time of the action, so history is preserved even if the original hardware or user is later deleted.
> 
> This audit trail meets compliance requirements and helps track who changed what and when."

**Demo Actions:**
1. Show the history table
2. Demonstrate the action type filter
3. Show date range filtering
4. Point out the before/after status columns
5. Explain how deleted items are still tracked

---

## 6. User Management (Admin Only)

**URL:** `pages/users.php`

> **What to say:**
> 
> "The User Management page is only accessible to administrators. Here, admins can create, edit, and delete user accounts.
> 
> **User Roles:**
> - **Admin** - Full access to all features including user management and backup
> - **Staff** - Can manage hardware and view history, but cannot manage users or backups
> 
> **User Table Shows:**
> - Username and full name
> - Role (Admin/Staff)
> - Online/Offline status with real-time indicator
> - Last login time
> - Live session duration for online users
> - Date account was created
> 
> **Real-time Features:**
> - The table updates automatically every 5 seconds
> - Online users show a green 'Online' badge
> - Session timer counts up in real-time
> 
> **Live Indicator:**
> - The green 'Live' badge shows the table auto-refreshes
> - No need to manually reload the page
> 
> **Managing Users:**
> - Click 'Add User' to create a new account
> - Edit button to modify user details
> - Delete removes the user (cannot delete yourself)
> - Password can be reset by admin"

**Demo Actions:**
1. Show the user table with status indicators
2. Point out the live session timers
3. Click "Add User" and show the form
4. Demonstrate editing a user
5. Show that you cannot delete your own account

---

## 7. Backup & Restore (Admin Only)

**URL:** `pages/backup.php`

> **What to say:**
> 
> "The Backup & Restore page allows administrators to create database backups and restore from previous backups.
> 
> **Backup Includes:**
> - All categories
> - All hardware items
> - All user accounts
> - Complete audit trail/history
> 
> **Creating a Backup:**
> - Click 'Create Backup Now'
> - System generates a timestamped SQL file
> - Files are stored in the /backups directory
> 
> **Available Backups List:**
> - Shows all backup files
> - Creation date and time
> - File size
> - Download, Restore, and Delete options
> 
> **Restoring from Backup:**
> - Select a backup file
> - Confirm the restore action
> - WARNING: This replaces ALL current data
> 
> **Security Validation:**
> - Only backups created by this system can be restored
> - Prevents malicious SQL injection
> - Checks for dangerous SQL patterns
> 
> Regular backups are recommended to prevent data loss."

**Demo Actions:**
1. Click "Create Backup Now"
2. Show the new backup in the list
3. Download a backup to show the file
4. Explain the restore warning (do not actually restore during demo)

---

## 8. Profile Settings

**URL:** `pages/profile.php`

> **What to say:**
> 
> "Every logged-in user can access their Profile Settings from the user dropdown menu in the top navigation.
> 
> **Profile Information:**
> - Change username
> - Update full name (display name)
> - View current role (cannot be self-modified)
> 
> **Change Password:**
> - Enter current password for verification
> - Set new password (minimum 6 characters)
> - Confirm new password
> - Password visibility toggle for all fields
> - Confirmation dialog before changing
> 
> **Security:**
> - Passwords are securely hashed
> - Current password must be verified
> - Session remains active after password change"

**Demo Actions:**
1. Click on user name in navigation
2. Select "Profile Settings"
3. Show the profile form
4. Demonstrate the password change fields (don't actually change)
5. Show the password visibility toggles

---

## 9. Logout

> **What to say:**
> 
> "To log out, click on your name in the top navigation and select 'Sign Out'.
> 
> **Logout Process:**
> - Session is properly destroyed
> - User is redirected to login page
> - Session duration is recorded for the user's record
> 
> **Session Tracking:**
> - The system tracks how long each user is logged in
> - This information is visible in the User Management page
> - Helps monitor system usage"

**Demo Actions:**
1. Click user dropdown
2. Click "Sign Out"
3. Show redirect to login page

---

## 10. Key Features Summary

> **What to say for closing:**
> 
> "To summarize, the PC Hardware Inventory System provides:
> 
> **For Daily Operations:**
> - Easy hardware tracking with detailed information
> - Quick search and filtering capabilities
> - Bulk import via CSV for efficiency
> - Real-time dashboard statistics
> 
> **For Management:**
> - Complete audit trail for accountability
> - Role-based access control
> - Low stock and out-of-stock alerts
> - Category-based organization
> 
> **For IT Administration:**
> - User account management
> - Database backup and restore
> - Soft delete with recovery option
> - Session tracking
> 
> **Technical Excellence:**
> - Responsive design works on all devices
> - Modern UI/UX following HCI principles
> - Secure authentication and authorization
> - SQL injection and XSS prevention
> 
> **Questions?**
> 
> Thank you for your attention. Do you have any questions about the system?"

---

## Appendix: Quick Reference

### Default Credentials
| Role | Username | Password |
|------|----------|----------|
| Admin | admin | password123 |
| Staff | staff01 | password123 |

### Navigation URLs
| Page | URL |
|------|-----|
| Login | `/login.php` |
| Dashboard | `/dashboard.php` |
| Hardware | `/pages/hardware.php` |
| History | `/pages/history.php` |
| Users (Admin) | `/pages/users.php` |
| Backup (Admin) | `/pages/backup.php` |
| Profile | `/pages/profile.php` |

### Keyboard Shortcuts
| Key | Action |
|-----|--------|
| `/` | Open search panel |
| `Esc` | Close search panel |

### CSV Import Format
```csv
name,category,type,brand,model,serial_number,unused_quantity,in_use_quantity,damaged_quantity,repair_quantity,location
```

---

*This demonstration script was created for ACLC College of Ormoc PC Hardware Inventory System.*
