#!/bin/bash

# Script de instalación para EZVIZ EB8 4G Web Viewer
# Autor: Asistente IA
# Descripción: Instala automáticamente todas las dependencias necesarias

set -e  # Salir si hay algún error

echo "🚀 Instalando EZVIZ EB8 4G Web Viewer..."
echo "=========================================="

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir mensajes coloreados
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Detectar sistema operativo
detect_os() {
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        if [ -f /etc/debian_version ]; then
            OS="debian"
        elif [ -f /etc/redhat-release ]; then
            OS="redhat"
        else
            OS="linux"
        fi
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        OS="macos"
    else
        OS="unknown"
    fi
    print_status "Sistema operativo detectado: $OS"
}

# Verificar si Node.js está instalado
check_nodejs() {
    if command -v node &> /dev/null; then
        NODE_VERSION=$(node --version)
        print_success "Node.js encontrado: $NODE_VERSION"
        
        # Verificar versión mínima (v16)
        MAJOR_VERSION=$(echo $NODE_VERSION | cut -d'.' -f1 | sed 's/v//')
        if [ "$MAJOR_VERSION" -lt 16 ]; then
            print_warning "Node.js versión $NODE_VERSION detectada. Se recomienda v16 o superior."
        fi
    else
        print_error "Node.js no está instalado."
        install_nodejs
    fi
}

# Instalar Node.js
install_nodejs() {
    print_status "Instalando Node.js..."
    
    case $OS in
        "debian")
            curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
            sudo apt-get install -y nodejs
            ;;
        "redhat")
            curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
            sudo yum install -y nodejs npm
            ;;
        "macos")
            if command -v brew &> /dev/null; then
                brew install node
            else
                print_error "Homebrew no encontrado. Instala Node.js manualmente desde https://nodejs.org/"
                exit 1
            fi
            ;;
        *)
            print_error "Sistema operativo no soportado para instalación automática."
            print_status "Por favor instala Node.js manualmente desde https://nodejs.org/"
            exit 1
            ;;
    esac
    
    print_success "Node.js instalado correctamente"
}

# Verificar si FFmpeg está instalado
check_ffmpeg() {
    if command -v ffmpeg &> /dev/null; then
        FFMPEG_VERSION=$(ffmpeg -version | head -n1)
        print_success "FFmpeg encontrado: $FFMPEG_VERSION"
    else
        print_error "FFmpeg no está instalado."
        install_ffmpeg
    fi
}

# Instalar FFmpeg
install_ffmpeg() {
    print_status "Instalando FFmpeg..."
    
    case $OS in
        "debian")
            sudo apt update
            sudo apt install -y ffmpeg
            ;;
        "redhat")
            # Habilitar repositorio EPEL
            sudo yum install -y epel-release
            sudo yum install -y ffmpeg
            ;;
        "macos")
            if command -v brew &> /dev/null; then
                brew install ffmpeg
            else
                print_error "Homebrew no encontrado. Instala FFmpeg manualmente."
                exit 1
            fi
            ;;
        *)
            print_error "Sistema operativo no soportado para instalación automática de FFmpeg."
            print_status "Por favor instala FFmpeg manualmente desde https://ffmpeg.org/"
            exit 1
            ;;
    esac
    
    print_success "FFmpeg instalado correctamente"
}

# Instalar dependencias npm
install_npm_dependencies() {
    print_status "Instalando dependencias de Node.js..."
    
    if [ -f "package.json" ]; then
        npm install
        print_success "Dependencias instaladas correctamente"
    else
        print_error "package.json no encontrado. Asegúrate de estar en el directorio correcto."
        exit 1
    fi
}

# Configurar archivo .env
setup_env_file() {
    print_status "Configurando archivo de entorno..."
    
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            print_success "Archivo .env creado desde .env.example"
            print_warning "IMPORTANTE: Edita el archivo .env con la configuración de tu cámara"
        else
            print_error ".env.example no encontrado"
            exit 1
        fi
    else
        print_warning "El archivo .env ya existe. No se sobrescribirá."
    fi
}

# Crear directorios necesarios
create_directories() {
    print_status "Creando directorios necesarios..."
    
    mkdir -p public/hls
    print_success "Directorios creados correctamente"
}

# Verificar puertos
check_ports() {
    print_status "Verificando disponibilidad de puertos..."
    
    PORT=3000
    if lsof -Pi :$PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
        print_warning "El puerto $PORT está en uso. Puedes cambiarlo en el archivo .env"
    else
        print_success "Puerto $PORT disponible"
    fi
}

# Función principal
main() {
    echo
    print_status "Iniciando instalación..."
    echo
    
    # Detectar sistema operativo
    detect_os
    
    # Verificar e instalar Node.js
    check_nodejs
    
    # Verificar e instalar FFmpeg
    check_ffmpeg
    
    # Instalar dependencias npm
    install_npm_dependencies
    
    # Configurar archivo .env
    setup_env_file
    
    # Crear directorios
    create_directories
    
    # Verificar puertos
    check_ports
    
    echo
    print_success "¡Instalación completada!"
    echo
    echo "📋 Próximos pasos:"
    echo "1. Edita el archivo .env con la configuración de tu cámara EZVIZ EB8 4G"
    echo "2. Ejecuta: npm start"
    echo "3. Abre tu navegador en: http://localhost:3000"
    echo
    print_warning "No olvides configurar la IP y credenciales de tu cámara en el archivo .env"
    echo
}

# Verificar si el script se ejecuta como root cuando es necesario
if [[ $EUID -eq 0 ]] && [[ "$1" != "--allow-root" ]]; then
    print_warning "Este script no debería ejecutarse como root."
    print_status "Si necesitas permisos de administrador, el script te lo pedirá."
    print_status "Ejecuta: bash install.sh --allow-root si realmente necesitas ejecutar como root."
    exit 1
fi

# Ejecutar función principal
main