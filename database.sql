-- Car Wash Client Platform Control System Database
-- Created: 2024
-- Improved version with enhanced features

-- Create database
CREATE DATABASE IF NOT EXISTS carwash_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE carwash_system;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Clients table
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    vehicle_type ENUM('sedan', 'suv', 'truck', 'motorcycle', 'van', 'coupe', 'convertible', 'other') NOT NULL,
    vehicle_brand VARCHAR(50),
    vehicle_model VARCHAR(50),
    vehicle_year YEAR,
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    notes TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_license_plate (license_plate),
    INDEX idx_name (name),
    INDEX idx_contact (contact_number),
    INDEX idx_status (status)
);

-- Service types table for better management
CREATE TABLE service_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    base_price DECIMAL(10, 2) NOT NULL,
    duration_minutes INT NOT NULL DEFAULT 30,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    service_type_id INT NOT NULL,
    cost DECIMAL(10, 2) NOT NULL,
    service_date DATE NOT NULL,
    service_time TIME NOT NULL,
    duration_minutes INT DEFAULT 30,
    employee_id INT,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'completed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (service_type_id) REFERENCES service_types(id),
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_client_id (client_id),
    INDEX idx_service_date (service_date),
    INDEX idx_service_type_id (service_type_id),
    INDEX idx_status (status),
    INDEX idx_employee_id (employee_id)
);

-- Insert default service types
INSERT INTO service_types (name, description, base_price, duration_minutes) VALUES
('Lavado Básico', 'Lavado exterior básico con agua y jabón', 15.00, 30),
('Lavado Deluxe', 'Lavado exterior + aspirado interior + limpieza de llantas', 25.00, 45),
('Lavado Premium', 'Lavado completo + encerado + limpieza interior profunda', 35.00, 60),
('Detallado Completo', 'Servicio completo de detallado interior y exterior', 50.00, 90),
('Solo Aspirado', 'Aspirado interior únicamente', 8.00, 15),
('Encerado', 'Aplicación de cera protectora', 20.00, 30);

-- Insert default admin user (password: admin123)
-- Staff user (password: staff123)
INSERT INTO users (username, password, role, full_name, email, phone) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrador Principal', 'admin@carwash.com', '+1234567890'),
('staff1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Carlos Martinez', 'carlos@carwash.com', '+1234567891'),
('staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Ana Rodriguez', 'ana@carwash.com', '+1234567892');

-- Insert sample clients
INSERT INTO clients (name, contact_number, email, vehicle_type, vehicle_brand, vehicle_model, vehicle_year, license_plate, notes) VALUES
('Juan Pérez', '+1234567890', 'juan.perez@email.com', 'sedan', 'Toyota', 'Corolla', 2020, 'ABC123', 'Cliente frecuente'),
('María García', '+1234567891', 'maria.garcia@email.com', 'suv', 'Honda', 'CR-V', 2019, 'XYZ789', 'Prefiere servicio premium'),
('Carlos López', '+1234567892', 'carlos.lopez@email.com', 'truck', 'Ford', 'F-150', 2021, 'DEF456', 'Vehículo de trabajo'),
('Ana Martínez', '+1234567893', 'ana.martinez@email.com', 'sedan', 'Nissan', 'Sentra', 2018, 'GHI789', ''),
('Luis Rodríguez', '+1234567894', 'luis.rodriguez@email.com', 'motorcycle', 'Yamaha', 'R6', 2022, 'JKL012', 'Motocicleta deportiva');

-- Insert sample services
INSERT INTO services (client_id, service_type_id, cost, service_date, service_time, employee_id, notes) VALUES
(1, 1, 15.00, CURDATE(), '10:00:00', 2, 'Lavado regular matutino'),
(2, 3, 35.00, CURDATE(), '11:30:00', 2, 'Servicio premium con encerado'),
(3, 2, 25.00, CURDATE() - INTERVAL 1 DAY, '14:00:00', 3, 'Lavado de camioneta'),
(1, 2, 25.00, CURDATE() - INTERVAL 2 DAY, '09:15:00', 2, 'Servicio deluxe'),
(4, 1, 15.00, CURDATE() - INTERVAL 3 DAY, '16:30:00', 3, 'Cliente nueva'),
(5, 1, 12.00, CURDATE() - INTERVAL 1 DAY, '13:45:00', 2, 'Lavado de motocicleta - precio especial'),
(2, 4, 50.00, CURDATE() - INTERVAL 5 DAY, '08:00:00', 3, 'Detallado completo');

-- Create view for service reports
CREATE VIEW service_report_view AS
SELECT 
    s.id,
    s.service_date,
    s.service_time,
    s.cost,
    c.name as client_name,
    c.license_plate,
    c.vehicle_type,
    st.name as service_type_name,
    u.full_name as employee_name,
    s.notes,
    s.status
FROM services s
JOIN clients c ON s.client_id = c.id
JOIN service_types st ON s.service_type_id = st.id
LEFT JOIN users u ON s.employee_id = u.id
ORDER BY s.service_date DESC, s.service_time DESC;

-- Create view for daily income summary
CREATE VIEW daily_income_view AS
SELECT 
    service_date,
    COUNT(*) as total_services,
    SUM(cost) as total_income,
    AVG(cost) as average_service_cost
FROM services
WHERE status = 'completed'
GROUP BY service_date
ORDER BY service_date DESC;