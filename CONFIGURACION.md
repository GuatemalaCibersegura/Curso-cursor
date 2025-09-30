# 🔧 Guía de Configuración - EZVIZ EB8 4G

## 📋 Configuración Rápida

### 1. Configurar tu cámara EZVIZ EB8 4G

#### Paso 1: Obtener la IP de la cámara
1. Abre la app **EZVIZ** en tu móvil
2. Selecciona tu cámara EB8 4G
3. Ve a **Configuración** → **Información del dispositivo**
4. Anota la **dirección IP** (ej: 192.168.1.100)

#### Paso 2: Verificar credenciales
- **Usuario**: Generalmente es `admin`
- **Contraseña**: La que configuraste en la app EZVIZ

#### Paso 3: Probar conexión RTSP
Puedes probar la conexión usando VLC:
```
rtsp://admin:tu_password@192.168.1.100:554/h264Preview_01_main
```

### 2. Configurar el archivo .env

Edita el archivo `.env` con los datos de tu cámara:

```env
# 🎥 Configuración de tu cámara EZVIZ EB8 4G
CAMERA_IP=192.168.1.100              # ← Cambia por la IP de tu cámara
CAMERA_USERNAME=admin                # ← Usuario de tu cámara
CAMERA_PASSWORD=tu_password_aqui     # ← Contraseña de tu cámara
RTSP_PORT=554                        # ← Puerto RTSP (normalmente 554)
RTSP_STREAM_PATH=/h264Preview_01_main # ← Ruta del stream

# 🌐 Configuración del servidor web
PORT=3000                            # ← Puerto donde correrá el sitio web
JWT_SECRET=cambia_este_secreto       # ← Cambia por algo más seguro

# 🔐 Credenciales para acceder al sitio web
WEB_USERNAME=admin                   # ← Usuario para entrar al sitio
WEB_PASSWORD=password123             # ← Contraseña para entrar al sitio
```

### 3. Rutas RTSP comunes para EZVIZ

Prueba estas rutas si la por defecto no funciona:

```bash
# Calidad principal (alta resolución)
/h264Preview_01_main

# Calidad secundaria (menor resolución, menos ancho de banda)
/h264Preview_01_sub

# Otras rutas comunes
/
/live
/stream1
/cam/realmonitor?channel=1&subtype=0
```

### 4. Solución de problemas comunes

#### ❌ "No se puede conectar a la cámara"

**Posibles causas:**
- IP incorrecta
- Credenciales incorrectas
- Cámara en red diferente
- RTSP no habilitado

**Soluciones:**
1. Verifica la IP con `ping 192.168.1.100`
2. Prueba con VLC: `rtsp://admin:password@IP:554/`
3. Asegúrate de estar en la misma red WiFi
4. Revisa las credenciales en la app EZVIZ

#### ❌ "FFmpeg no encontrado"

**Solución Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install ffmpeg
```

**Solución CentOS/RHEL:**
```bash
sudo yum install epel-release
sudo yum install ffmpeg
```

**Solución macOS:**
```bash
brew install ffmpeg
```

#### ❌ "El video no se reproduce"

**Soluciones:**
1. Espera 10-15 segundos después de iniciar el stream
2. Actualiza la página
3. Prueba con otro navegador (Chrome, Firefox)
4. Verifica que no haya otras apps usando la cámara

#### ❌ "Puerto 3000 en uso"

**Solución:**
Cambia el puerto en el archivo `.env`:
```env
PORT=8080  # o cualquier otro puerto libre
```

### 5. Configuración avanzada

#### Mejorar calidad de video
```env
# En .env, ajusta estos valores:
HLS_SEGMENT_DURATION=1    # Menor latencia (1-4 segundos)
HLS_PLAYLIST_SIZE=5       # Más segmentos en buffer (3-10)
STREAM_QUALITY=1080p      # Calidad más alta
```

#### Reducir uso de datos (para 4G)
```env
HLS_SEGMENT_DURATION=4    # Mayor latencia pero menos datos
HLS_PLAYLIST_SIZE=2       # Menos buffer
STREAM_QUALITY=480p       # Calidad más baja
```

### 6. Configuración de red

#### Para acceso desde internet (avanzado)
1. Configura port forwarding en tu router:
   - Puerto externo: 3000 → Puerto interno: 3000
   - IP interna: La de tu servidor
2. Usa tu IP pública para acceder desde fuera

#### Para acceso local solamente
- Mantén la configuración por defecto
- Accede solo desde `http://localhost:3000` o `http://IP_LOCAL:3000`

### 7. Verificar instalación

#### Comprobar que todo funciona:
```bash
# 1. Verificar Node.js
node --version  # Debe ser v16 o superior

# 2. Verificar FFmpeg
ffmpeg -version  # Debe mostrar información de FFmpeg

# 3. Verificar dependencias
npm list  # Debe mostrar todas las dependencias instaladas

# 4. Probar conexión a la cámara
ping 192.168.1.100  # Cambia por tu IP
```

### 8. Iniciar el sistema

```bash
# Desarrollo (con auto-reload)
npm run dev

# Producción
npm start
```

Luego abre: `http://localhost:3000`

---

## 🆘 ¿Necesitas ayuda?

### Información útil para soporte:
- Modelo de cámara: EZVIZ EB8 4G
- Sistema operativo: [Windows/Mac/Linux]
- Versión de Node.js: `node --version`
- Versión de FFmpeg: `ffmpeg -version`
- Mensaje de error completo
- Configuración de red (misma WiFi, IP, etc.)

### Logs útiles:
- Consola del navegador (F12)
- Logs del servidor (terminal donde ejecutas `npm start`)
- Archivo de logs si existe

¡Con esta configuración deberías poder ver tu cámara EZVIZ EB8 4G en el navegador! 🎉