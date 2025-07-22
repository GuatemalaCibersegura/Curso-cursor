#!/bin/bash

echo "=== MySQL Setup Script ==="

# Create necessary directories
sudo mkdir -p /var/run/mysqld
sudo mkdir -p /var/log/mysql

# Set proper ownership
sudo chown -R mysql:mysql /var/lib/mysql
sudo chown -R mysql:mysql /var/log/mysql
sudo chown -R mysql:mysql /var/run/mysqld

# Set proper permissions
sudo chmod 755 /var/run/mysqld
sudo chmod 755 /var/log/mysql

echo "Starting MySQL server..."

# Try to start MySQL in safe mode
sudo -u mysql mysqld_safe --skip-networking --socket=/var/run/mysqld/mysqld.sock --pid-file=/var/run/mysqld/mysqld.pid &

sleep 5

# Test connection
if mysql -u root --socket=/var/run/mysqld/mysqld.sock -e "SELECT 1;" 2>/dev/null; then
    echo "✓ MySQL connection successful"
    
    # Create database and user
    mysql -u root --socket=/var/run/mysqld/mysqld.sock << SQL
CREATE DATABASE IF NOT EXISTS carwash_system;
GRANT ALL PRIVILEGES ON carwash_system.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
SQL
    
    # Import schema
    if [ -f "database.sql" ]; then
        mysql -u root --socket=/var/run/mysqld/mysqld.sock carwash_system < database.sql
        echo "✓ Database schema imported"
    fi
    
    echo "✓ MySQL setup completed successfully"
else
    echo "✗ MySQL connection failed"
    echo "Please check MySQL installation and permissions"
fi
