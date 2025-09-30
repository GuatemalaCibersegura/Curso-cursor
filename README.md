# EZVIZ EB8 4G - Visualizador Web

Un sitio web moderno y responsive para visualizar en tiempo real el stream de tu cámara EZVIZ EB8 4G directamente desde el navegador.

## 🚀 Características

- ✅ **Streaming en vivo** - Visualización en tiempo real del feed de la cámara
- ✅ **Interfaz moderna** - Diseño responsive y fácil de usar
- ✅ **Autenticación segura** - Sistema de login con JWT
- ✅ **Control de stream** - Iniciar/detener transmisión desde la web
- ✅ **Pantalla completa** - Modo fullscreen para mejor visualización
- ✅ **Información en tiempo real** - Estado de conexión y estadísticas
- ✅ **Notificaciones** - Alertas visuales del estado del sistema
- ✅ **Compatibilidad móvil** - Funciona en dispositivos móviles

## 📋 Requisitos Previos

### Software Necesario
- **Node.js** (versión 16 o superior)
- **FFmpeg** (para conversión de video)
- **npm** o **yarn**

### Instalación de FFmpeg

#### Ubuntu/Debian:
```bash
sudo apt update
sudo apt install ffmpeg
```

#### CentOS/RHEL:
```bash
sudo yum install epel-release
sudo yum install ffmpeg
```

#### macOS:
```bash
brew install ffmpeg
```

#### Windows:
1. Descargar desde [https://ffmpeg.org/download.html](https://ffmpeg.org/download.html)
2. Extraer y agregar al PATH del sistema

## 🛠️ Instalación

1. **Clonar o descargar el proyecto**
```bash
git clone <url-del-repositorio>
cd ezviz-eb8-web-viewer
```

2. **Instalar dependencias**
```bash
npm install
```

3. **Configurar variables de entorno**
```bash
cp .env.example .env
```

4. **Editar el archivo .env con tu configuración:**
```env
# Configuración de la cámara EZVIZ EB8 4G
CAMERA_IP=192.168.1.100          # IP de tu cámara
CAMERA_USERNAME=admin            # Usuario de la cámara
CAMERA_PASSWORD=tu_password      # Contraseña de la cámara
RTSP_PORT=554                    # Puerto RTSP (generalmente 554)
RTSP_STREAM_PATH=/h264Preview_01_main  # Ruta del stream

# Configuración del servidor
PORT=3000                        # Puerto del servidor web
JWT_SECRET=tu_jwt_secret_muy_seguro_aqui

# Credenciales de acceso web
WEB_USERNAME=admin               # Usuario para acceder a la web
WEB_PASSWORD=password123         # Contraseña para acceder a la web
```

## 🔧 Configuración de la Cámara EZVIZ EB8 4G

### 1. Obtener la IP de la cámara
- Abre la app EZVIZ en tu móvil
- Ve a configuración de la cámara
- Busca la información de red/WiFi
- Anota la dirección IP asignada

### 2. Habilitar RTSP (si está disponible)
- En la app EZVIZ, ve a configuración avanzada
- Busca opciones de "Streaming" o "RTSP"
- Habilita el protocolo RTSP si está disponible

### 3. Configurar credenciales
- Usa las mismas credenciales que tienes en la app EZVIZ
- Por defecto suele ser `admin` como usuario

### 4. Formatos de URL RTSP comunes para EZVIZ:
```
rtsp://usuario:contraseña@IP:554/h264Preview_01_main
rtsp://usuario:contraseña@IP:554/h264Preview_01_sub
rtsp://usuario:contraseña@IP:554/
```

## 🚀 Uso

### 1. Iniciar el servidor
```bash
npm start
```

### 2. Acceder a la aplicación
Abre tu navegador y ve a: `http://localhost:3000`

### 3. Iniciar sesión
- Usuario: `admin` (o el que configuraste en .env)
- Contraseña: `password123` (o la que configuraste en .env)

### 4. Controlar el stream
- Haz clic en "Iniciar Stream" para comenzar la transmisión
- Usa "Pantalla Completa" para mejor visualización
- "Actualizar" para reiniciar la conexión si hay problemas

## 🔧 Desarrollo

### Ejecutar en modo desarrollo
```bash
npm run dev
```

### Estructura del proyecto
```
ezviz-eb8-web-viewer/
├── server.js              # Servidor principal
├── package.json           # Dependencias
├── .env.example          # Configuración de ejemplo
├── README.md             # Este archivo
└── public/               # Archivos estáticos
    ├── index.html        # Interfaz principal
    ├── styles.css        # Estilos CSS
    ├── app.js           # JavaScript del cliente
    └── hls/             # Archivos de streaming (generados automáticamente)
```

## 🔍 Solución de Problemas

### Error: "No se puede conectar a la cámara"
1. Verifica que la IP de la cámara sea correcta
2. Asegúrate de que la cámara esté en la misma red
3. Verifica las credenciales de usuario/contraseña
4. Comprueba que el puerto RTSP esté abierto

### Error: "FFmpeg no encontrado"
1. Instala FFmpeg siguiendo las instrucciones de arriba
2. Verifica que esté en el PATH: `ffmpeg -version`
3. Reinicia el servidor después de instalar FFmpeg

### El video no se reproduce
1. Verifica que tu navegador soporte HLS
2. Intenta con un navegador diferente (Chrome, Firefox, Safari)
3. Revisa la consola del navegador para errores
4. Asegúrate de que el stream esté activo

### Problemas de rendimiento
1. Reduce la calidad del stream en la configuración de la cámara
2. Asegúrate de tener buena conexión de red
3. Cierra otras aplicaciones que usen la cámara

## 📱 Compatibilidad

### Navegadores soportados:
- ✅ Chrome 70+
- ✅ Firefox 65+
- ✅ Safari 12+
- ✅ Edge 79+
- ✅ Navegadores móviles modernos

### Dispositivos:
- ✅ PC/Mac
- ✅ Tablets
- ✅ Smartphones
- ✅ Smart TVs con navegador

## 🔒 Seguridad

- Autenticación JWT con tokens seguros
- Rate limiting para prevenir ataques
- Variables de entorno para credenciales sensibles
- Validación de entrada en todas las rutas

## 📞 Soporte

Si tienes problemas:

1. **Revisa los logs del servidor** - Información útil en la consola
2. **Verifica la configuración** - Especialmente IP y credenciales
3. **Prueba la conexión RTSP** - Usa VLC para probar: `rtsp://usuario:contraseña@IP:554/`
4. **Consulta la documentación de EZVIZ** - Para configuración específica de tu modelo

## 📄 Licencia

MIT License - Puedes usar, modificar y distribuir libremente.

---

**Nota:** Este proyecto es independiente y no está afiliado oficialmente con EZVIZ. Es una solución de terceros para integrar cámaras EZVIZ en aplicaciones web.