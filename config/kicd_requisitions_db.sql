

CREATE DATABASE IF NOT EXISTS kicd_requisitions;
USE kicd_requisitions;


CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user', 'supervisor', 'procurement') DEFAULT 'user',
    department VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL
);


CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) UNIQUE NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    unit_price DECIMAL(10,2),
    unit_of_measure VARCHAR(50),
    stock_quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 0,
    supplier VARCHAR(255),
    image_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS requisitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    total_amount DECIMAL(12,2) DEFAULT 0,
    status ENUM('draft', 'pending', 'approved', 'rejected', 'ordered', 'received', 'cancelled') DEFAULT 'draft',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    requested_date DATE,
    required_date DATE,
    approved_by INT NULL,
    approved_date TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);


CREATE TABLE IF NOT EXISTS requisition_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2),
    total_price DECIMAL(10,2),
    notes TEXT,
    FOREIGN KEY (requisition_id) REFERENCES requisitions(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS approval_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_id INT NOT NULL,
    approver_id INT NOT NULL,
    action ENUM('approved', 'rejected', 'returned') NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requisition_id) REFERENCES requisitions(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    related_requisition_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_requisition_id) REFERENCES requisitions(id) ON DELETE CASCADE
);





CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


INSERT INTO users (employee_id, first_name, last_name, email, password_hash, role, department) 
VALUES ('KICD/ADMIN/001', 'System', 'Administrator', 'admin@kicd.ac.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'IT');


INSERT INTO items (item_code, item_name, description, category, unit_price, unit_of_measure, stock_quantity) VALUES
('ITM001', 'A4 Paper Ream', 'High quality A4 paper for printing', 'Stationery', 450.00, 'Ream', 100),
('ITM002', 'HP LaserJet Cartridge', 'Black toner cartridge for HP LaserJet printers', 'IT Supplies', 3500.00, 'Piece', 20),
('ITM003', 'Office Chair', 'Ergonomic office chair with adjustable height', 'Furniture', 8500.00, 'Piece', 15),
('ITM004', 'Desktop Computer', 'Intel i5, 8GB RAM, 256GB SSD', 'IT Equipment', 45000.00, 'Unit', 5),
('ITM005', 'Whiteboard', 'Magnetic whiteboard 4x6 feet', 'Office Equipment', 3500.00, 'Piece', 8);


INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('site_name', 'KICD Requisition Management System', 'System display name'),
('max_requisition_amount', '50000', 'Maximum amount for single requisition without special approval'),
('auto_approve_threshold', '5000', 'Amount below which requisitions are auto-approved'),
('notification_email', 'admin@kicd.ac.ke', 'Email for system notifications');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_requisitions_user_id ON requisitions(user_id);
CREATE INDEX idx_requisitions_status ON requisitions(status);
CREATE INDEX idx_requisitions_created_at ON requisitions(created_at);
CREATE INDEX idx_items_category ON items(category);
CREATE INDEX idx_items_item_code ON items(item_code);
