#!/bin/bash

# Script de inicio rápido para EZVIZ EB8 4G Web Viewer
# Uso: ./start.sh [dev|prod]

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_banner() {
    echo -e "${BLUE}"
    echo "╔══════════════════════════════════════╗"
    echo "║        EZVIZ EB8 4G Web Viewer       ║"
    echo "║            Iniciando...              ║"
    echo "╚══════════════════════════════════════╝"
    echo -e "${NC}"
}

print_info() {
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

check_requirements() {
    print_info "Verificando requisitos..."
    
    # Verificar Node.js
    if ! command -v node &> /dev/null; then
        print_error "Node.js no está instalado"
        print_info "Instala Node.js desde: https://nodejs.org/"
        exit 1
    fi
    
    NODE_VERSION=$(node --version | cut -d'.' -f1 | sed 's/v//')
    if [ "$NODE_VERSION" -lt 16 ]; then
        print_warning "Node.js versión $(node --version) detectada. Se recomienda v16+"
    else
        print_success "Node.js $(node --version) ✓"
    fi
    
    # Verificar FFmpeg
    if ! command -v ffmpeg &> /dev/null; then
        print_error "FFmpeg no está instalado"
        print_info "Instala FFmpeg:"
        print_info "  Ubuntu/Debian: sudo apt install ffmpeg"
        print_info "  CentOS/RHEL:   sudo yum install ffmpeg"
        print_info "  macOS:         brew install ffmpeg"
        exit 1
    else
        print_success "FFmpeg $(ffmpeg -version 2>&1 | head -n1 | cut -d' ' -f3) ✓"
    fi
    
    # Verificar dependencias npm
    if [ ! -d "node_modules" ]; then
        print_warning "Dependencias no instaladas. Instalando..."
        npm install
        if [ $? -eq 0 ]; then
            print_success "Dependencias instaladas ✓"
        else
            print_error "Error instalando dependencias"
            exit 1
        fi
    else
        print_success "Dependencias npm ✓"
    fi
    
    # Verificar archivo .env
    if [ ! -f ".env" ]; then
        print_warning "Archivo .env no encontrado. Creando desde .env.example..."
        if [ -f ".env.example" ]; then
            cp .env.example .env
            print_success "Archivo .env creado ✓"
            print_warning "IMPORTANTE: Edita el archivo .env con la configuración de tu cámara"
        else
            print_error "Archivo .env.example no encontrado"
            exit 1
        fi
    else
        print_success "Archivo .env ✓"
    fi
}

show_config_info() {
    if [ -f ".env" ]; then
        print_info "Configuración actual:"
        echo "  📷 Cámara IP: $(grep CAMERA_IP .env | cut -d'=' -f2)"
        echo "  🌐 Puerto web: $(grep PORT .env | cut -d'=' -f2 | head -n1)"
        echo "  👤 Usuario web: $(grep WEB_USERNAME .env | cut -d'=' -f2)"
        echo
    fi
}

check_port() {
    PORT=$(grep PORT .env | cut -d'=' -f2 | head -n1)
    PORT=${PORT:-3000}
    
    if lsof -Pi :$PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
        print_warning "Puerto $PORT está en uso"
        print_info "Puedes cambiar el puerto en el archivo .env"
    else
        print_success "Puerto $PORT disponible ✓"
    fi
}

start_server() {
    MODE=${1:-prod}
    
    print_info "Iniciando servidor en modo: $MODE"
    
    case $MODE in
        "dev"|"development")
            if command -v nodemon &> /dev/null; then
                print_success "Iniciando en modo desarrollo con nodemon..."
                npm run dev
            else
                print_warning "nodemon no encontrado, usando node normal..."
                npm start
            fi
            ;;
        "prod"|"production"|*)
            print_success "Iniciando en modo producción..."
            npm start
            ;;
    esac
}

show_access_info() {
    PORT=$(grep PORT .env | cut -d'=' -f2 | head -n1)
    PORT=${PORT:-3000}
    
    echo
    print_success "¡Servidor iniciado correctamente!"
    echo
    echo -e "${GREEN}🌐 Accede al sitio web en:${NC}"
    echo "   http://localhost:$PORT"
    echo "   http://127.0.0.1:$PORT"
    
    # Intentar obtener IP local
    LOCAL_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || ifconfig 2>/dev/null | grep -Eo 'inet (addr:)?([0-9]*\.){3}[0-9]*' | grep -Eo '([0-9]*\.){3}[0-9]*' | grep -v '127.0.0.1' | head -n1)
    if [ ! -z "$LOCAL_IP" ]; then
        echo "   http://$LOCAL_IP:$PORT (desde otros dispositivos)"
    fi
    
    echo
    echo -e "${YELLOW}📋 Credenciales por defecto:${NC}"
    echo "   Usuario: $(grep WEB_USERNAME .env | cut -d'=' -f2)"
    echo "   Contraseña: $(grep WEB_PASSWORD .env | cut -d'=' -f2)"
    echo
    echo -e "${BLUE}💡 Consejos:${NC}"
    echo "   • Edita el archivo .env para configurar tu cámara"
    echo "   • Usa Ctrl+C para detener el servidor"
    echo "   • Revisa CONFIGURACION.md para ayuda detallada"
    echo
}

main() {
    print_banner
    check_requirements
    show_config_info
    check_port
    
    # Mostrar información de acceso antes de iniciar
    show_access_info
    
    print_info "Presiona Ctrl+C para detener el servidor"
    print_info "Iniciando en 3 segundos..."
    sleep 3
    
    start_server $1
}

# Verificar argumentos
if [[ "$1" == "-h" || "$1" == "--help" ]]; then
    echo "Uso: $0 [modo]"
    echo
    echo "Modos disponibles:"
    echo "  dev, development  - Modo desarrollo con auto-reload"
    echo "  prod, production  - Modo producción (por defecto)"
    echo
    echo "Ejemplos:"
    echo "  $0              # Modo producción"
    echo "  $0 dev          # Modo desarrollo"
    echo "  $0 prod         # Modo producción"
    exit 0
fi

# Ejecutar función principal
main $1