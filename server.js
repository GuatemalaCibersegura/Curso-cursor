const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const path = require('path');
const cors = require('cors');
const rateLimit = require('express-rate-limit');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const ffmpeg = require('fluent-ffmpeg');
const fs = require('fs');
require('dotenv').config();

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});

// Configuración de middleware
app.use(cors());
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// Rate limiting
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutos
  max: 100 // máximo 100 requests por IP por ventana
});
app.use(limiter);

// Configuración de la cámara desde variables de entorno
const CAMERA_CONFIG = {
  ip: process.env.CAMERA_IP || '192.168.1.100',
  username: process.env.CAMERA_USERNAME || 'admin',
  password: process.env.CAMERA_PASSWORD || 'password',
  port: process.env.RTSP_PORT || 554,
  streamPath: process.env.RTSP_STREAM_PATH || '/h264Preview_01_main'
};

// Construir URL RTSP
const RTSP_URL = `rtsp://${CAMERA_CONFIG.username}:${CAMERA_CONFIG.password}@${CAMERA_CONFIG.ip}:${CAMERA_CONFIG.port}${CAMERA_CONFIG.streamPath}`;

// Directorio para archivos HLS
const HLS_DIR = path.join(__dirname, 'public', 'hls');
if (!fs.existsSync(HLS_DIR)) {
  fs.mkdirSync(HLS_DIR, { recursive: true });
}

// Variable para controlar el proceso de FFmpeg
let ffmpegProcess = null;
let isStreaming = false;

// Middleware de autenticación
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    return res.status(401).json({ error: 'Token de acceso requerido' });
  }

  jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ error: 'Token inválido' });
    }
    req.user = user;
    next();
  });
};

// Ruta de login
app.post('/api/login', async (req, res) => {
  const { username, password } = req.body;
  
  const validUsername = process.env.WEB_USERNAME || 'admin';
  const validPassword = process.env.WEB_PASSWORD || 'password123';
  
  if (username === validUsername && password === validPassword) {
    const token = jwt.sign(
      { username: username },
      process.env.JWT_SECRET,
      { expiresIn: '24h' }
    );
    
    res.json({ 
      success: true, 
      token: token,
      message: 'Login exitoso'
    });
  } else {
    res.status(401).json({ 
      success: false, 
      message: 'Credenciales inválidas' 
    });
  }
});

// Función para iniciar el streaming
function startStreaming() {
  if (isStreaming) {
    console.log('El streaming ya está activo');
    return;
  }

  console.log('Iniciando streaming desde:', RTSP_URL);
  
  // Limpiar archivos HLS anteriores
  const files = fs.readdirSync(HLS_DIR);
  files.forEach(file => {
    if (file.endsWith('.ts') || file.endsWith('.m3u8')) {
      fs.unlinkSync(path.join(HLS_DIR, file));
    }
  });

  ffmpegProcess = ffmpeg(RTSP_URL)
    .inputOptions([
      '-rtsp_transport', 'tcp',
      '-fflags', '+genpts'
    ])
    .outputOptions([
      '-c:v', 'libx264',
      '-preset', 'ultrafast',
      '-tune', 'zerolatency',
      '-c:a', 'aac',
      '-f', 'hls',
      '-hls_time', process.env.HLS_SEGMENT_DURATION || '2',
      '-hls_list_size', process.env.HLS_PLAYLIST_SIZE || '3',
      '-hls_flags', 'delete_segments',
      '-hls_segment_filename', path.join(HLS_DIR, 'segment_%03d.ts')
    ])
    .output(path.join(HLS_DIR, 'playlist.m3u8'))
    .on('start', (commandLine) => {
      console.log('FFmpeg iniciado:', commandLine);
      isStreaming = true;
      io.emit('streamStatus', { status: 'connected', message: 'Stream iniciado correctamente' });
    })
    .on('error', (err) => {
      console.error('Error en FFmpeg:', err.message);
      isStreaming = false;
      io.emit('streamStatus', { status: 'error', message: 'Error en el stream: ' + err.message });
    })
    .on('end', () => {
      console.log('FFmpeg terminado');
      isStreaming = false;
      io.emit('streamStatus', { status: 'disconnected', message: 'Stream desconectado' });
    });

  ffmpegProcess.run();
}

// Función para detener el streaming
function stopStreaming() {
  if (ffmpegProcess) {
    ffmpegProcess.kill('SIGTERM');
    ffmpegProcess = null;
    isStreaming = false;
    console.log('Streaming detenido');
    io.emit('streamStatus', { status: 'disconnected', message: 'Stream detenido' });
  }
}

// Rutas de la API
app.get('/api/stream/status', authenticateToken, (req, res) => {
  res.json({ 
    isStreaming: isStreaming,
    cameraConfig: {
      ip: CAMERA_CONFIG.ip,
      port: CAMERA_CONFIG.port
    }
  });
});

app.post('/api/stream/start', authenticateToken, (req, res) => {
  try {
    startStreaming();
    res.json({ success: true, message: 'Stream iniciado' });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Error al iniciar stream: ' + error.message });
  }
});

app.post('/api/stream/stop', authenticateToken, (req, res) => {
  try {
    stopStreaming();
    res.json({ success: true, message: 'Stream detenido' });
  } catch (error) {
    res.status(500).json({ success: false, message: 'Error al detener stream: ' + error.message });
  }
});

// Ruta para servir el archivo de playlist HLS
app.get('/hls/playlist.m3u8', (req, res) => {
  const playlistPath = path.join(HLS_DIR, 'playlist.m3u8');
  if (fs.existsSync(playlistPath)) {
    res.setHeader('Content-Type', 'application/vnd.apple.mpegurl');
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.sendFile(playlistPath);
  } else {
    res.status(404).json({ error: 'Playlist no encontrada' });
  }
});

// Conexiones WebSocket
io.on('connection', (socket) => {
  console.log('Cliente conectado:', socket.id);
  
  // Enviar estado actual del stream
  socket.emit('streamStatus', { 
    status: isStreaming ? 'connected' : 'disconnected',
    message: isStreaming ? 'Stream activo' : 'Stream inactivo'
  });

  socket.on('disconnect', () => {
    console.log('Cliente desconectado:', socket.id);
  });
});

// Ruta principal
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Manejo de cierre graceful
process.on('SIGINT', () => {
  console.log('Cerrando servidor...');
  stopStreaming();
  process.exit(0);
});

process.on('SIGTERM', () => {
  console.log('Cerrando servidor...');
  stopStreaming();
  process.exit(0);
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
  console.log(`Servidor ejecutándose en http://localhost:${PORT}`);
  console.log(`Configuración de cámara: ${CAMERA_CONFIG.ip}:${CAMERA_CONFIG.port}`);
});