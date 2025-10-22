# 🚀 Instalación Rápida - Car Wash Emanuel

## ⚡ Instalación en 5 Pasos

### 1️⃣ Requisitos Previos
- **Servidor web** (Apache/Nginx/XAMPP/WAMP)
- **PHP 8.0+** con extensiones: PDO, PDO_MySQL
- **MySQL 8.0+**

### 2️⃣ Extraer Archivos
```bash
# Extraer el ZIP en la carpeta del servidor web
unzip carwash_emanuel_system.zip -d /var/www/html/carwash-emanuel
# O en Windows con XAMPP: C:\xampp\htdocs\carwash-emanuel
```

### 3️⃣ Crear Base de Datos
```sql
-- Conectar a MySQL y ejecutar:
CREATE DATABASE carwash_emanuel;
```

### 4️⃣ Importar Datos
```bash
# Importar el esquema de base de datos
mysql -u root -p carwash_emanuel < database.sql
```

### 5️⃣ Configurar Conexión
Editar `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'carwash_emanuel');
define('DB_USER', 'root');           // Tu usuario MySQL
define('DB_PASS', 'tu_contraseña');  // Tu contraseña MySQL
```

## 🔑 Acceso al Sistema

1. **URL**: `http://localhost/carwash-emanuel`
2. **Usuario**: `admin`
3. **Contraseña**: `admin123`

## ⚠️ Importante

1. **Cambiar contraseña** inmediatamente después del primer acceso
2. **Configurar permisos** de la carpeta `logs/` para escritura
3. **Usar HTTPS** en producción

## 🛠️ Para XAMPP/WAMP

1. Extraer en `C:\xampp\htdocs\carwash-emanuel`
2. Iniciar Apache y MySQL
3. Ir a `http://localhost/phpmyadmin`
4. Crear base de datos `carwash_emanuel`
5. Importar `database.sql`
6. Acceder a `http://localhost/carwash-emanuel`

## 🐛 Problemas Comunes

**Error de conexión a BD:**
- Verificar que MySQL esté ejecutándose
- Comprobar credenciales en `config/database.php`

**Permisos:**
```bash
chmod 755 logs/
```

**Páginas en blanco:**
- Habilitar `display_errors` en PHP
- Revisar logs de error del servidor

## 📞 Soporte

Para problemas técnicos, revisar el archivo `README.md` completo incluido en el sistema.

---
**¡Listo para usar! 🚗✨**