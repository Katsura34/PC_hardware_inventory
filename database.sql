-- ==========================================
-- DATABASE: pc_inventory
-- ==========================================
CREATE DATABASE IF NOT EXISTS pc_inventory;
USE pc_inventory;

-- ==========================================
-- 1. Categories Table
-- ==========================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,        -- Category name (CPU, RAM, SSD, etc.)
    description VARCHAR(255)
);

-- Sample Categories
INSERT INTO categories (name, description) VALUES
('CPU', 'Central Processing Unit'),
('RAM', 'Memory Modules'),
('SSD', 'Solid State Drives'),
('Hard Drive', 'Mechanical Storage'),
('GPU', 'Graphics Card'),
('Monitor', 'Display Units'),
('Keyboard', 'Input Devices'),
('Mouse', 'Input Devices'),
('Power Cord', 'Power cables');

-- ==========================================
-- 2. Hardware Table
-- ==========================================
CREATE TABLE IF NOT EXISTS hardware (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    type VARCHAR(100),
    brand VARCHAR(100),
    model VARCHAR(100),
    serial_number VARCHAR(100),
    total_quantity INT DEFAULT 0,
    unused_quantity INT DEFAULT 0,
    in_use_quantity INT DEFAULT 0,
    damaged_quantity INT DEFAULT 0,
    repair_quantity INT DEFAULT 0,
    location VARCHAR(100),
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_hardware_deleted_at (deleted_at)
);

-- Sample Hardware
INSERT INTO hardware (name, category_id, type, brand, model, serial_number, total_quantity, unused_quantity, in_use_quantity, damaged_quantity, repair_quantity, location)
VALUES
('Intel Core i5', 1, '10th Gen', 'Intel', 'i5-10400', 'SNCPU001', 5, 2, 2, 1, 0, 'Lab 1'),
('Corsair 8GB RAM', 2, 'DDR4', 'Corsair', 'Vengeance', 'SNRAM001', 10, 4, 3, 2, 1, 'Lab 1'),
('Samsung SSD 500GB', 3, 'NVMe', 'Samsung', '970 EVO', 'SNSSD001', 6, 3, 2, 1, 0, 'Lab 2'),
('Seagate HDD 1TB', 4, 'SATA', 'Seagate', 'Barracuda', 'SNHDD001', 4, 1, 2, 1, 0, 'Lab 2'),
('NVIDIA GTX 1660', 5, 'GPU', 'NVIDIA', 'GTX 1660', 'SNGPU001', 3, 1, 2, 0, 0, 'Lab 3'),
('Dell Monitor 24"', 6, 'LCD', 'Dell', 'P2419H', 'SNMON001', 7, 5, 2, 0, 0, 'Lab 1');

-- ==========================================
-- 3. Users Table
-- ==========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,           -- store hashed passwords
    full_name VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'staff',        -- admin or staff
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL DEFAULT NULL,   -- tracks when user last logged in
    last_login_duration INT NULL DEFAULT NULL, -- stores duration of last session in seconds
    is_active TINYINT(1) DEFAULT 0,           -- 1 if user is currently logged in, 0 if not
    last_activity TIMESTAMP NULL DEFAULT NULL -- tracks user's last activity for timeout detection
);

-- Sample Users (passwords are hashed using password_hash in PHP)
-- Password for both: password123
INSERT INTO users (username, password, full_name, role)
VALUES
('admin', '$2y$10$P76pH.ufyDynF1s3q5fhH.Dp1IuaJEqDTSHveur6JHdYXl6gOb.nm', 'John Admin', 'admin'),
('staff01', '$2y$10$GYNhhYRjdmmyfkDd72LRrOW1JTxDY.xmCDLML.fK0BDwFXG8aKBNG', 'Mary Staff', 'staff');

-- ==========================================
-- 4. Inventory History Table
-- ==========================================
-- Modified to store denormalized data to avoid foreign key constraint issues
-- This allows deletion of hardware/users without losing history
CREATE TABLE IF NOT EXISTS inventory_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hardware_id INT,                         -- Optional reference (can be NULL if deleted)
    hardware_name VARCHAR(255) NOT NULL,     -- Denormalized: actual hardware name
    category_name VARCHAR(100),              -- Denormalized: category name
    serial_number VARCHAR(100),              -- Denormalized: serial number
    user_id INT,                             -- Optional reference (can be NULL if deleted)
    user_name VARCHAR(255),                  -- Denormalized: actual user name
    action_type VARCHAR(50) NOT NULL,        -- Added, Updated, Deleted
    quantity_change INT,
    old_unused INT,
    old_in_use INT,
    old_damaged INT,
    old_repair INT,
    new_unused INT,
    new_in_use INT,
    new_damaged INT,
    new_repair INT,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    -- NO FOREIGN KEY CONSTRAINTS - history is permanent and independent
);

-- Sample History (updated with denormalized data)
INSERT INTO inventory_history (hardware_id, hardware_name, category_name, serial_number, user_id, user_name, action_type, quantity_change, old_unused, old_in_use, old_damaged, old_repair, new_unused, new_in_use, new_damaged, new_repair)
VALUES
(1, 'Intel Core i5', 'CPU', 'SNCPU001', 1, 'John Admin', 'Added', 5, 0, 0, 0, 0, 2, 2, 1, 0),
(2, 'Corsair 8GB RAM', 'RAM', 'SNRAM001', 2, 'Mary Staff', 'Updated', 2, 2, 2, 2, 1, 4, 3, 2, 1);
