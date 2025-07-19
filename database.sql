-- Car Wash Client Platform Control System Database
-- Created: 2024

-- Create database
CREATE DATABASE IF NOT EXISTS carwash_system;
USE carwash_system;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
);

-- Clients table
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    vehicle_type VARCHAR(50) NOT NULL,
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_license_plate (license_plate),
    INDEX idx_name (name),
    INDEX idx_contact (contact_number)
);

-- Services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    service_type ENUM('basic', 'deluxe', 'premium', 'full_detail') NOT NULL,
    cost DECIMAL(10, 2) NOT NULL,
    service_date DATE NOT NULL,
    service_time TIME NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client_id (client_id),
    INDEX idx_service_date (service_date),
    INDEX idx_service_type (service_type)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, role, full_name, email) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', 'admin@carwash.com'),
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Staff Member', 'staff@carwash.com');

-- Insert sample clients
INSERT INTO clients (name, contact_number, email, vehicle_type, license_plate) VALUES
('John Doe', '+1234567890', 'john.doe@email.com', 'Sedan', 'ABC123'),
('Jane Smith', '+1234567891', 'jane.smith@email.com', 'SUV', 'XYZ789'),
('Mike Johnson', '+1234567892', 'mike.johnson@email.com', 'Truck', 'DEF456');

-- Insert sample services
INSERT INTO services (client_id, service_type, cost, service_date, service_time, notes) VALUES
(1, 'basic', 15.00, CURDATE(), '10:00:00', 'Regular wash'),
(2, 'deluxe', 25.00, CURDATE(), '11:30:00', 'Wash and wax'),
(3, 'premium', 35.00, CURDATE() - INTERVAL 1 DAY, '14:00:00', 'Full service'),
(1, 'deluxe', 25.00, CURDATE() - INTERVAL 2 DAY, '09:15:00', 'Interior cleaning included');