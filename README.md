# Car Wash Emanuel - Sistema de Control de Plataforma de Clientes

![Car Wash Emanuel](https://img.shields.io/badge/Car%20Wash-Emanuel-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3)

Sistema completo de gestión de clientes, servicios y reportes para Car Wash Emanuel, desarrollado en PHP y MySQL con una interfaz moderna usando Bootstrap 5.

## 📋 Características Principales

### 🔐 Sistema de Autenticación
- **Roles separados**: Administrador y Personal
- **Sesiones seguras** con tokens CSRF
- **Contraseñas encriptadas** con bcrypt

### 👥 Gestión de Clientes
- Registro completo de clientes con información de contacto
- Gestión de múltiples vehículos por cliente
- Historial completo de servicios
- Búsqueda y filtrado avanzado

### 🚗 Gestión de Vehículos
- Registro detallado: placa, marca, modelo, año, color
- Asociación automática con clientes
- Validación de placas únicas

### 📅 Sistema de Citas y Servicios
- Programación de citas con fecha y hora
- Múltiples tipos de servicio con precios configurables
- Estados de cita: Programada, Completada, Cancelada
- Gestión de servicios en tiempo real

### 📊 Reportes Completos
- **Dashboard interactivo** con estadísticas en tiempo real
- **Reportes de ingresos**: diarios, semanales, mensuales
- **Análisis de servicios** más populares
- **Clientes más activos** y estadísticas de fidelidad
- **Gráficos interactivos** con Chart.js

### 🎨 Interfaz Moderna
- **Diseño responsive** con Bootstrap 5
- **Tema personalizado** con gradientes y animaciones
- **Iconos Bootstrap Icons** para mejor UX
- **Navegación intuitiva** y accesible

## 🛠️ Tecnologías Utilizadas

- **Backend**: PHP 8.0+
- **Base de Datos**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript ES6
- **Framework CSS**: Bootstrap 5.3
- **Gráficos**: Chart.js
- **Iconos**: Bootstrap Icons
- **Seguridad**: PDO con sentencias preparadas, CSRF tokens

## 📁 Estructura del Proyecto

```
carwash-emanuel/
├── api/                          # Endpoints API
│   ├── get_client_vehicles.php   # Obtener vehículos por cliente
│   └── ping.php                  # Mantener sesión activa
├── config/                       # Configuración
│   └── database.php              # Configuración de BD
├── includes/                     # Archivos compartidos
│   ├── functions.php             # Funciones auxiliares
│   ├── header.php                # Header común
│   └── footer.php                # Footer común
├── logs/                         # Logs del sistema
│   └── activity.log              # Log de actividades
├── database.sql                  # Script de base de datos
├── index.php                     # Página principal
├── login.php                     # Módulo de login
├── logout.php                    # Cerrar sesión
├── dashboard.php                 # Dashboard principal
├── clients.php                   # Gestión de clientes
├── services.php                  # Gestión de servicios
├── reports.php                   # Módulo de reportes
├── users.php                     # Gestión de usuarios (admin)
└── README.md                     # Este archivo
```

## 🚀 Instalación

### Requisitos Previos

- **Servidor web** (Apache/Nginx)
- **PHP 8.0 o superior**
- **MySQL 8.0 o superior**
- **Extensiones PHP**: PDO, PDO_MySQL, mbstring, openssl

### Paso 1: Clonar el Repositorio

```bash
git clone https://github.com/tu-usuario/carwash-emanuel.git
cd carwash-emanuel
```

### Paso 2: Configurar la Base de Datos

1. Crear la base de datos:
```sql
CREATE DATABASE carwash_emanuel;
```

2. Importar el esquema:
```bash
mysql -u root -p carwash_emanuel < database.sql
```

### Paso 3: Configurar la Conexión

Editar `config/database.php` con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'carwash_emanuel');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### Paso 4: Configurar Permisos

```bash
chmod 755 logs/
chmod 644 logs/activity.log
```

### Paso 5: Acceder al Sistema

1. Abrir en el navegador: `http://tu-servidor/carwash-emanuel`
2. Usar las credenciales por defecto:
   - **Usuario**: `admin`
   - **Contraseña**: `admin123`

## 📖 Guía de Uso

### Primer Acceso

1. **Iniciar sesión** con las credenciales de administrador
2. **Cambiar la contraseña** por defecto (recomendado)
3. **Configurar tipos de servicio** según tu negocio
4. **Agregar el primer cliente** y su vehículo

### Gestión Diaria

#### Registrar un Cliente
1. Ir a **Clientes** → **Nuevo Cliente**
2. Completar información personal
3. Agregar vehículo(s) del cliente
4. Guardar registro

#### Programar una Cita
1. Ir a **Servicios** → **Nueva Cita**
2. Seleccionar cliente y vehículo
3. Elegir tipo de servicio
4. Definir fecha y hora
5. Confirmar cita

#### Completar un Servicio
1. Ir a **Servicios** → Buscar la cita
2. Marcar como **Completada**
3. El ingreso se registra automáticamente

#### Ver Reportes
1. Ir a **Reportes**
2. Seleccionar tipo de reporte:
   - Dashboard general
   - Ingresos por período
   - Servicios populares
   - Clientes activos
   - Estadísticas de vehículos

## 🔧 Configuración Avanzada

### Tipos de Servicio

Los tipos de servicio se configuran directamente en la base de datos:

```sql
INSERT INTO tipos_servicio (nombre, descripcion, precio, duracion) VALUES
('Lavado Express', 'Lavado rápido exterior', 10.00, 20),
('Lavado Completo', 'Exterior e interior', 20.00, 45),
('Detallado Premium', 'Servicio completo con encerado', 50.00, 90);
```

### Roles de Usuario

- **admin**: Acceso completo al sistema
- **personal**: Acceso limitado (sin gestión de usuarios)

### Backup de Base de Datos

```bash
mysqldump -u root -p carwash_emanuel > backup_$(date +%Y%m%d).sql
```

## 🛡️ Seguridad

### Medidas Implementadas

- **Autenticación obligatoria** en todas las páginas
- **Tokens CSRF** en todos los formularios
- **Sentencias preparadas** para prevenir SQL injection
- **Validación de entrada** en servidor y cliente
- **Sesiones seguras** con timeout automático
- **Logging de actividades** para auditoría

### Recomendaciones

1. **Cambiar contraseñas por defecto**
2. **Usar HTTPS en producción**
3. **Actualizar PHP y MySQL regularmente**
4. **Hacer backups periódicos**
5. **Revisar logs de actividad**

## 📊 Estructura de Base de Datos

### Tablas Principales

- **roles**: Roles del sistema
- **usuarios**: Usuarios del sistema
- **clientes**: Información de clientes
- **vehiculos**: Vehículos de los clientes
- **tipos_servicio**: Tipos de servicios disponibles
- **citas**: Citas/servicios programados
- **articulos_inventario**: Inventario (opcional)
- **transacciones_inventario**: Movimientos de inventario
- **reportes**: Reportes generados

### Relaciones

```
usuarios (1) → (N) reportes
roles (1) → (N) usuarios
clientes (1) → (N) vehiculos
clientes (1) → (N) citas
vehiculos (1) → (N) citas
tipos_servicio (1) → (N) citas
```

## 🔄 API Endpoints

### Obtener Vehículos de Cliente
```
GET /api/get_client_vehicles.php?client_id={id}
```

### Mantener Sesión Activa
```
GET /api/ping.php
```

## 🐛 Solución de Problemas

### Error de Conexión a BD
- Verificar credenciales en `config/database.php`
- Comprobar que MySQL esté ejecutándose
- Verificar que la base de datos existe

### Sesión Expirada Constantemente
- Verificar configuración de sesiones en PHP
- Comprobar permisos de escritura en `/tmp`

### Gráficos No Se Muestran
- Verificar que Chart.js se carga correctamente
- Comprobar consola del navegador por errores JavaScript

## 📝 Registro de Cambios

### v1.0.0 (2024-01-15)
- ✅ Sistema de autenticación completo
- ✅ Gestión de clientes y vehículos
- ✅ Sistema de citas y servicios
- ✅ Módulo de reportes con gráficos
- ✅ Dashboard interactivo
- ✅ Interfaz responsive moderna
- ✅ API para funcionalidades AJAX
- ✅ Sistema de logging

## 🤝 Contribuir

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 👨‍💻 Autor

**Car Wash Emanuel System**
- Sistema desarrollado para la gestión integral de servicios de lavado de vehículos
- Contacto: [tu-email@ejemplo.com]

## 🙏 Agradecimientos

- Bootstrap team por el framework CSS
- Chart.js por la librería de gráficos
- Bootstrap Icons por los iconos
- Comunidad PHP por las mejores prácticas

---

**¡Gracias por usar Car Wash Emanuel System!** 🚗✨