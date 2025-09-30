class EzvizWebViewer {
    constructor() {
        this.socket = null;
        this.hls = null;
        this.token = localStorage.getItem('authToken');
        this.isStreaming = false;
        this.startTime = null;
        this.uptimeInterval = null;
        
        this.initializeApp();
    }

    initializeApp() {
        // Verificar si hay token válido
        if (this.token) {
            this.showMainScreen();
            this.initializeSocket();
        } else {
            this.showLoginScreen();
        }
        
        this.bindEvents();
    }

    bindEvents() {
        // Eventos de login
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Eventos de la pantalla principal
        const startBtn = document.getElementById('startStreamBtn');
        const stopBtn = document.getElementById('stopStreamBtn');
        const logoutBtn = document.getElementById('logoutBtn');
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        const refreshBtn = document.getElementById('refreshBtn');

        if (startBtn) startBtn.addEventListener('click', () => this.startStream());
        if (stopBtn) stopBtn.addEventListener('click', () => this.stopStream());
        if (logoutBtn) logoutBtn.addEventListener('click', () => this.logout());
        if (fullscreenBtn) fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
        if (refreshBtn) refreshBtn.addEventListener('click', () => this.refreshStream());
    }

    async handleLogin(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const errorDiv = document.getElementById('loginError');
        
        try {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.token = data.token;
                localStorage.setItem('authToken', this.token);
                this.showMainScreen();
                this.initializeSocket();
                this.showNotification('Login exitoso', 'success');
            } else {
                errorDiv.textContent = data.message;
                errorDiv.style.display = 'block';
            }
        } catch (error) {
            console.error('Error en login:', error);
            errorDiv.textContent = 'Error de conexión';
            errorDiv.style.display = 'block';
        }
    }

    logout() {
        localStorage.removeItem('authToken');
        this.token = null;
        if (this.socket) {
            this.socket.disconnect();
        }
        if (this.hls) {
            this.hls.destroy();
        }
        this.showLoginScreen();
        this.showNotification('Sesión cerrada', 'success');
    }

    showLoginScreen() {
        document.getElementById('loginScreen').style.display = 'flex';
        document.getElementById('mainScreen').style.display = 'none';
    }

    showMainScreen() {
        document.getElementById('loginScreen').style.display = 'none';
        document.getElementById('mainScreen').style.display = 'block';
        this.loadCameraInfo();
    }

    initializeSocket() {
        this.socket = io();
        
        this.socket.on('connect', () => {
            console.log('Conectado al servidor');
            this.updateConnectionStatus(true);
        });
        
        this.socket.on('disconnect', () => {
            console.log('Desconectado del servidor');
            this.updateConnectionStatus(false);
        });
        
        this.socket.on('streamStatus', (data) => {
            this.handleStreamStatus(data);
        });
    }

    updateConnectionStatus(connected) {
        const statusElement = document.getElementById('connectionStatus');
        if (connected) {
            statusElement.className = 'connection-status connected';
            statusElement.innerHTML = '<i class="fas fa-circle"></i><span>Conectado</span>';
        } else {
            statusElement.className = 'connection-status disconnected';
            statusElement.innerHTML = '<i class="fas fa-circle"></i><span>Desconectado</span>';
        }
    }

    handleStreamStatus(data) {
        const statusElement = document.getElementById('streamStatus');
        const startBtn = document.getElementById('startStreamBtn');
        const stopBtn = document.getElementById('stopStreamBtn');
        
        switch (data.status) {
            case 'connected':
                this.isStreaming = true;
                statusElement.textContent = 'Conectado';
                statusElement.className = 'status-connected';
                startBtn.disabled = true;
                stopBtn.disabled = false;
                this.startUptime();
                this.initializeVideoPlayer();
                this.showNotification(data.message, 'success');
                break;
                
            case 'disconnected':
                this.isStreaming = false;
                statusElement.textContent = 'Desconectado';
                statusElement.className = 'status-disconnected';
                startBtn.disabled = false;
                stopBtn.disabled = true;
                this.stopUptime();
                this.hideVideoPlayer();
                this.showNotification(data.message, 'warning');
                break;
                
            case 'error':
                this.isStreaming = false;
                statusElement.textContent = 'Error';
                statusElement.className = 'status-disconnected';
                startBtn.disabled = false;
                stopBtn.disabled = true;
                this.stopUptime();
                this.hideVideoPlayer();
                this.showNotification(data.message, 'error');
                break;
        }
    }

    async loadCameraInfo() {
        try {
            const response = await fetch('/api/stream/status', {
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            
            const data = await response.json();
            
            if (data.cameraConfig) {
                document.getElementById('cameraIP').textContent = data.cameraConfig.ip;
                document.getElementById('cameraPort').textContent = data.cameraConfig.port;
            }
            
            if (data.isStreaming) {
                this.handleStreamStatus({ status: 'connected', message: 'Stream activo' });
            }
        } catch (error) {
            console.error('Error cargando información de la cámara:', error);
        }
    }

    async startStream() {
        try {
            const response = await fetch('/api/stream/start', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            
            const data = await response.json();
            
            if (!data.success) {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Error iniciando stream:', error);
            this.showNotification('Error iniciando stream', 'error');
        }
    }

    async stopStream() {
        try {
            const response = await fetch('/api/stream/stop', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`
                }
            });
            
            const data = await response.json();
            
            if (!data.success) {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Error deteniendo stream:', error);
            this.showNotification('Error deteniendo stream', 'error');
        }
    }

    initializeVideoPlayer() {
        const video = document.getElementById('videoPlayer');
        const placeholder = document.getElementById('videoPlaceholder');
        
        if (Hls.isSupported()) {
            this.hls = new Hls({
                enableWorker: true,
                lowLatencyMode: true,
                backBufferLength: 90
            });
            
            this.hls.loadSource('/hls/playlist.m3u8');
            this.hls.attachMedia(video);
            
            this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
                console.log('Manifest cargado, iniciando reproducción');
                video.play().catch(e => console.log('Error reproduciendo:', e));
            });
            
            this.hls.on(Hls.Events.ERROR, (event, data) => {
                console.error('Error HLS:', data);
                if (data.fatal) {
                    switch (data.type) {
                        case Hls.ErrorTypes.NETWORK_ERROR:
                            console.log('Error de red, intentando recuperar...');
                            this.hls.startLoad();
                            break;
                        case Hls.ErrorTypes.MEDIA_ERROR:
                            console.log('Error de media, intentando recuperar...');
                            this.hls.recoverMediaError();
                            break;
                        default:
                            console.log('Error fatal, destruyendo HLS');
                            this.hls.destroy();
                            break;
                    }
                }
            });
        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            // Safari nativo
            video.src = '/hls/playlist.m3u8';
        } else {
            this.showNotification('Tu navegador no soporta HLS', 'error');
            return;
        }
        
        // Mostrar video y ocultar placeholder
        setTimeout(() => {
            video.style.display = 'block';
            placeholder.style.display = 'none';
        }, 2000);
        
        // Eventos del video
        video.addEventListener('loadedmetadata', () => {
            const resolution = `${video.videoWidth}x${video.videoHeight}`;
            document.getElementById('videoResolution').textContent = resolution;
        });
    }

    hideVideoPlayer() {
        const video = document.getElementById('videoPlayer');
        const placeholder = document.getElementById('videoPlaceholder');
        
        if (this.hls) {
            this.hls.destroy();
            this.hls = null;
        }
        
        video.style.display = 'none';
        placeholder.style.display = 'flex';
        
        // Limpiar información del video
        document.getElementById('videoResolution').textContent = '--';
        document.getElementById('videoBitrate').textContent = '--';
    }

    toggleFullscreen() {
        const videoContainer = document.getElementById('videoContainer');
        
        if (!document.fullscreenElement) {
            videoContainer.requestFullscreen().catch(err => {
                console.error('Error entrando en pantalla completa:', err);
            });
        } else {
            document.exitFullscreen();
        }
    }

    refreshStream() {
        if (this.isStreaming) {
            this.stopStream();
            setTimeout(() => {
                this.startStream();
            }, 2000);
        } else {
            this.loadCameraInfo();
        }
    }

    startUptime() {
        this.startTime = Date.now();
        this.uptimeInterval = setInterval(() => {
            const elapsed = Date.now() - this.startTime;
            const hours = Math.floor(elapsed / 3600000);
            const minutes = Math.floor((elapsed % 3600000) / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);
            
            const uptimeString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('uptime').textContent = uptimeString;
        }, 1000);
    }

    stopUptime() {
        if (this.uptimeInterval) {
            clearInterval(this.uptimeInterval);
            this.uptimeInterval = null;
        }
        document.getElementById('uptime').textContent = '00:00:00';
    }

    showNotification(message, type = 'info') {
        const notificationsContainer = document.getElementById('notifications');
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        notificationsContainer.appendChild(notification);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Inicializar la aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new EzvizWebViewer();
});