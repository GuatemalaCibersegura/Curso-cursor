-- Car Wash Emanuel - Sistema de Control de Plataforma de Clientes
-- Base de Datos MySQL
-- Creado: 2024

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS carwash_emanuel;
USE carwash_emanuel;

-- Tabla de roles
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    INDEX idx_nombre (nombre)
);

-- Tabla de usuarios para autenticación
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id),
    INDEX idx_nombre_usuario (nombre_usuario),
    INDEX idx_rol_id (rol_id)
);

-- Tabla de clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NOT NULL,
    correo VARCHAR(100),
    direccion TEXT,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_usuario_id (usuario_id),
    INDEX idx_nombre (nombre),
    INDEX idx_telefono (telefono)
);

-- Tabla de vehículos
CREATE TABLE vehiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    placa VARCHAR(20) UNIQUE NOT NULL,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    ano INT NOT NULL,
    color VARCHAR(30) NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_cliente_id (cliente_id),
    INDEX idx_placa (placa)
);

-- Tabla de tipos de servicio
CREATE TABLE tipos_servicio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    duracion INT, -- duración en minutos
    INDEX idx_nombre (nombre)
);

-- Tabla de citas
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    vehiculo_id INT NOT NULL,
    tipo_servicio_id INT NOT NULL,
    fecha_cita DATETIME NOT NULL,
    estado VARCHAR(20) DEFAULT 'programada',
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id),
    FOREIGN KEY (tipo_servicio_id) REFERENCES tipos_servicio(id),
    INDEX idx_cliente_id (cliente_id),
    INDEX idx_vehiculo_id (vehiculo_id),
    INDEX idx_tipo_servicio_id (tipo_servicio_id),
    INDEX idx_fecha_cita (fecha_cita),
    INDEX idx_estado (estado)
);

-- Tabla de artículos de inventario
CREATE TABLE articulos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    cantidad_stock INT NOT NULL DEFAULT 0,
    precio_unitario DECIMAL(10,2) NOT NULL,
    umbral_bajo_stock INT DEFAULT 10,
    INDEX idx_nombre (nombre)
);

-- Tabla de transacciones de inventario
CREATE TABLE transacciones_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    articulo_inventario_id INT NOT NULL,
    tipo_transaccion VARCHAR(20) NOT NULL, -- 'entrada', 'salida', 'ajuste'
    cantidad INT NOT NULL,
    fecha_transaccion DATETIME DEFAULT CURRENT_TIMESTAMP,
    cita_id INT,
    FOREIGN KEY (articulo_inventario_id) REFERENCES articulos_inventario(id),
    FOREIGN KEY (cita_id) REFERENCES citas(id),
    INDEX idx_articulo_inventario_id (articulo_inventario_id),
    INDEX idx_fecha_transaccion (fecha_transaccion),
    INDEX idx_cita_id (cita_id)
);

-- Tabla de reportes
CREATE TABLE reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_reporte VARCHAR(50) NOT NULL,
    fecha_generado DATETIME DEFAULT CURRENT_TIMESTAMP,
    datos JSON,
    usuario_id INT NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_tipo_reporte (tipo_reporte),
    INDEX idx_fecha_generado (fecha_generado),
    INDEX idx_usuario_id (usuario_id)
);

-- Insertar roles predeterminados
INSERT INTO roles (nombre, descripcion) VALUES
('admin', 'Administrador del sistema con acceso completo'),
('personal', 'Personal del carwash con acceso limitado');

-- Insertar usuario administrador predeterminado (contraseña: admin123)
INSERT INTO usuarios (nombre_usuario, contrasena, rol_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('personal1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2);

-- Insertar tipos de servicio predeterminados
INSERT INTO tipos_servicio (nombre, descripcion, precio, duracion) VALUES
('Lavado Básico', 'Lavado exterior básico del vehículo', 15.00, 30),
('Lavado Deluxe', 'Lavado exterior e interior con encerado', 25.00, 45),
('Lavado Premium', 'Lavado completo con encerado y aspirado profundo', 35.00, 60),
('Detallado Completo', 'Servicio de detallado completo interior y exterior', 75.00, 120);

-- Insertar clientes de ejemplo
INSERT INTO clientes (nombre, telefono, correo, direccion) VALUES
('Juan Pérez', '+506 8888-1234', 'juan.perez@email.com', 'San José, Costa Rica'),
('María González', '+506 8888-5678', 'maria.gonzalez@email.com', 'Cartago, Costa Rica'),
('Carlos Rodríguez', '+506 8888-9012', 'carlos.rodriguez@email.com', 'Alajuela, Costa Rica');

-- Insertar vehículos de ejemplo
INSERT INTO vehiculos (cliente_id, placa, marca, modelo, ano, color) VALUES
(1, 'ABC123', 'Toyota', 'Corolla', 2020, 'Blanco'),
(2, 'XYZ789', 'Honda', 'CR-V', 2019, 'Negro'),
(3, 'DEF456', 'Ford', 'F-150', 2021, 'Azul');

-- Insertar citas de ejemplo
INSERT INTO citas (cliente_id, vehiculo_id, tipo_servicio_id, fecha_cita, estado) VALUES
(1, 1, 1, '2024-01-15 10:00:00', 'completada'),
(2, 2, 2, '2024-01-15 14:30:00', 'completada'),
(3, 3, 3, '2024-01-16 09:00:00', 'programada');

-- Insertar artículos de inventario de ejemplo
INSERT INTO articulos_inventario (nombre, descripcion, cantidad_stock, precio_unitario, umbral_bajo_stock) VALUES
('Champú para autos', 'Champú especializado para lavado de vehículos', 50, 12.50, 10),
('Cera líquida', 'Cera protectora para acabado brillante', 25, 18.00, 5),
('Toallas de microfibra', 'Toallas para secado sin rayar la pintura', 100, 8.00, 20),
('Aspiradora industrial', 'Aspiradora de alta potencia para interiores', 3, 250.00, 1);

-- Crear vistas útiles para reportes
CREATE VIEW vista_servicios_completos AS
SELECT 
    c.id as cita_id,
    cl.nombre as cliente_nombre,
    cl.telefono as cliente_telefono,
    v.placa,
    v.marca,
    v.modelo,
    ts.nombre as tipo_servicio,
    ts.precio,
    c.fecha_cita,
    c.estado
FROM citas c
JOIN clientes cl ON c.cliente_id = cl.id
JOIN vehiculos v ON c.vehiculo_id = v.id
JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id;

CREATE VIEW vista_ingresos_diarios AS
SELECT 
    DATE(fecha_cita) as fecha,
    COUNT(*) as total_servicios,
    SUM(ts.precio) as ingresos_totales
FROM citas c
JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
WHERE c.estado = 'completada'
GROUP BY DATE(fecha_cita)
ORDER BY fecha DESC;