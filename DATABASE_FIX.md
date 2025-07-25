# Solución para Error de Base de Datos

## Problema Identificado
El error `SQLSTATE[HY000] [2002] Permission denied` indica que MySQL server no está ejecutándose o tiene problemas de permisos.

## Soluciones

### Opción 1: Configurar MySQL (Recomendada)

1. **Ejecutar el script de configuración:**
   ```bash
   sudo ./setup_mysql.sh
   ```

2. **Si el script no funciona, configurar manualmente:**
   ```bash
   # Crear directorios necesarios
   sudo mkdir -p /var/run/mysqld /var/log/mysql
   
   # Establecer permisos
   sudo chown -R mysql:mysql /var/lib/mysql /var/log/mysql /var/run/mysqld
   sudo chmod 755 /var/run/mysqld
   
   # Iniciar MySQL
   sudo -u mysql mysqld_safe --socket=/var/run/mysqld/mysqld.sock &
   
   # Crear base de datos
   mysql -u root --socket=/var/run/mysqld/mysqld.sock -e "CREATE DATABASE IF NOT EXISTS carwash_system;"
   ```

### Opción 2: Usar Docker (Alternativa)

```bash
# Instalar y ejecutar MySQL en Docker
docker run --name carwash-mysql -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=carwash_system -p 3306:3306 -d mysql:8.0

# Actualizar config/database.php para usar la contraseña
# Cambiar DB_PASS de '' a 'root'
```

### Opción 3: Verificar Estado Actual

```bash
# Probar conexión
php test_db.php

# Verificar logs de error
sudo tail -f /var/log/mysql/error.log
```

## Cambios Realizados en el Código

1. **Mejorado el manejo de errores** en `clients.php`
2. **Agregado fallback a SQLite** en `config/database.php` (aunque SQLite no está disponible en este sistema)
3. **Mejorados los mensajes de error** para ser más descriptivos

## Verificación

Después de configurar MySQL, ejecutar:
```bash
php test_db.php
```

Debería mostrar:
```
✓ PDO MySQL extension loaded
✓ Database connection successful
✓ Database query test successful
```

## Notas Importantes

- El sistema ahora muestra errores más específicos
- Se registran errores detallados en los logs
- La configuración de base de datos intenta MySQL primero
- Si MySQL falla, el sistema mostrará un mensaje claro sobre el problema
