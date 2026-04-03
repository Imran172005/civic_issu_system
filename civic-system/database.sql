-- Rural Civic Issue Reporting System - Database Schema
-- MySQL Database

CREATE DATABASE IF NOT EXISTS civic_system;
USE civic_system;

-- Drop tables if they exist (in reverse order)
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS resolutions;
DROP TABLE IF EXISTS issues;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('citizen', 'solver', 'admin') NOT NULL,
    address VARCHAR(255),
    city VARCHAR(100),
    profile_pic VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Issues table
CREATE TABLE issues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    citizen_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('road', 'water', 'electricity', 'sanitation', 'drainage', 'street_light', 'other') NOT NULL,
    address_line VARCHAR(255),
    village VARCHAR(100),
    taluka VARCHAR(100),
    district VARCHAR(100),
    state VARCHAR(100),
    pincode VARCHAR(10),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    photo_path VARCHAR(255),
    status ENUM('pending', 'assigned', 'in_progress', 'resolved', 'closed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    assigned_to INT,
    admin_notes TEXT,
    resolved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (citizen_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Resolutions table
CREATE TABLE resolutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    solver_id INT NOT NULL,
    resolved_photo_path VARCHAR(255),
    resolution_notes TEXT,
    resolved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    FOREIGN KEY (solver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Feedback table
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    issue_id INT NOT NULL,
    citizen_id INT NOT NULL,
    rating INT(1) NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE,
    FOREIGN KEY (citizen_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    issue_id INT,
    message TEXT NOT NULL,
    type ENUM('assigned', 'in_progress', 'resolved', 'feedback') NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE
);

-- Activity Log table
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    issue_id INT,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (issue_id) REFERENCES issues(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_issues_citizen ON issues(citizen_id);
CREATE INDEX idx_issues_status ON issues(status);
CREATE INDEX idx_issues_assigned_to ON issues(assigned_to);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_activity_log_user ON activity_log(user_id);

-- Sample data - Admin user (password: admin123)
INSERT INTO users (full_name, email, phone, password, role, address, city, is_active) VALUES
('System Admin', 'admin@civic.gov.in', '9876543210', '$2y$10$yCWHm8a57C05HE4FUhs5veJkSsF.ZX.v.CykA0I8n1S/REoRap55O', 'admin', 'Collector Office', 'Mumbai', 1);

-- Sample data - Solvers (password: solver123)
INSERT INTO users (full_name, email, phone, password, role, address, city, is_active) VALUES
('Rajesh Kumar', 'rajesh@civic.gov.in', '9876543211', '$2y$10$UJZtSV98rhyiHuk1YQoa6.lMQl189PpGZgCC1bONSC2PNCQKVvOdq', 'solver', 'Block Office, Thane', 'Thane', 1),
('Priya Sharma', 'priya@civic.gov.in', '9876543212', '$2y$10$UJZtSV98rhyiHuk1YQoa6.lMQl189PpGZgCC1bONSC2PNCQKVvOdq', 'solver', 'Block Office, Pune', 'Pune', 1),
('Amit Patel', 'amit@civic.gov.in', '9876543213', '$2y$10$UJZtSV98rhyiHuk1YQoa6.lMQl189PpGZgCC1bONSC2PNCQKVvOdq', 'solver', 'Block Office, Nashik', 'Nashik', 1);

-- Sample data - Citizens (password: citizen123)
INSERT INTO users (full_name, email, phone, password, role, address, city, is_active) VALUES
('John Doe', 'john.doe@email.com', '9876543214', '$2y$10$oHeJJQaSEIBsXF.19NJqIOWd.4Sbl9Ix5X3rxZPsylMaQP14aJC7m', 'citizen', 'Village - Ramnagar, Taluka - Karjat', 'Mumbai', 1),
('Jane Smith', 'jane.smith@email.com', '9876543215', '$2y$10$oHeJJQaSEIBsXF.19NJqIOWd.4Sbl9Ix5X3rxZPsylMaQP14aJC7m', 'citizen', 'Village - Shivaji Nagar, Taluka - Mulshi', 'Pune', 1),
('Mahesh Jadhav', 'mahesh.j@email.com', '9876543216', '$2y$10$oHeJJQaSEIBsXF.19NJqIOWd.4Sbl9Ix5X3rxZPsylMaQP14aJC7m', 'citizen', 'Village - Mhatrewadi, Taluka - Pen', 'Raigad', 1),
('Sunita Rao', 'sunita.rao@email.com', '9876543217', '$2y$10$oHeJJQaSEIBsXF.19NJqIOWd.4Sbl9Ix5X3rxZPsylMaQP14aJC7m', 'citizen', 'Village - Kopardwadi, Taluka - Daman', 'Daman', 1);

-- Sample issues
INSERT INTO issues (citizen_id, title, description, category, address_line, village, taluka, district, state, pincode, status, priority) VALUES
(2, 'Road pothole near main market', 'There is a large pothole on the main market road that is causing accidents. It has been there for 2 weeks.', 'road', 'Near Main Market', 'Ramnagar', 'Karjat', 'Mumbai', 'Maharashtra', '410101', 'pending', 'high'),
(3, 'Water supply interruption', 'Water supply has been interrupted for 3 days. No water in the entire village.', 'water', 'Village Center', 'Shivaji Nagar', 'Mulshi', 'Pune', 'Maharashtra', '411027', 'assigned', 'critical'),
(4, 'Street light not working', 'The street light on the main road has been non-functional for a week causing safety concerns.', 'street_light', 'Near Bus Stand', 'Mhatrewadi', 'Pen', 'Raigad', 'Maharashtra', '402105', 'in_progress', 'medium'),
(5, 'Drainage blockage', 'The drainage near our house is blocked and causing water logging.', 'drainage', 'Lane 3', 'Kopardwadi', 'Daman', 'Daman', 'Daman', '396210', 'resolved', 'low');

-- Sample resolution
INSERT INTO resolutions (issue_id, solver_id, resolved_photo_path, resolution_notes, resolved_at) VALUES
(5, 2, 'uploads/resolved/sample.jpg', 'Drainage cleaned and blockage removed. Regular maintenance scheduled.', NOW() - INTERVAL 1 DAY);

-- Sample notification
INSERT INTO notifications (user_id, issue_id, message, type, is_read) VALUES
(2, 1, 'New issue reported by Mahesh Jadhav requires your attention', 'assigned', 0),
(5, 5, 'Your issue #5 has been resolved by Rajesh Kumar', 'resolved', 1);

-- Sample activity log
INSERT INTO activity_log (user_id, action, issue_id, details) VALUES
(1, 'login', NULL, 'Admin logged in'),
(2, 'issue_resolved', 5, 'Issue #5 marked as resolved'),
(3, 'status_changed', 4, 'Issue #4 status changed to in_progress');
