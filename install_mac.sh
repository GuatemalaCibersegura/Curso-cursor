#!/bin/bash

echo "🚗 Instalador del Sistema Car Wash para Mac"
echo "=========================================="

# Verificar si Homebrew está instalado
if ! command -v brew &> /dev/null; then
    echo "📦 Instalando Homebrew..."
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
fi

# Instalar PHP y MySQL
echo "🔧 Instalando PHP y MySQL..."
brew install php mysql

# Iniciar MySQL
echo "🗄️ Iniciando MySQL..."
brew services start mysql

# Crear directorio del proyecto
echo "📁 Creando directorio del proyecto..."
mkdir -p ~/carwash_system/{config,includes}
cd ~/carwash_system

echo "✅ Instalación completada!"
echo ""
echo "📋 Próximos pasos:"
echo "1. Copia todos los archivos PHP al directorio ~/carwash_system/"
echo "2. Ejecuta: mysql -u root < database.sql"
echo "3. Inicia el servidor: php -S localhost:8080"
echo "4. Abre http://localhost:8080 en tu navegador"
echo ""
echo "🔑 Credenciales:"
echo "Admin: admin / admin123"
echo "Staff: staff / staff123"