    </div> <!-- Fin del contenedor principal -->

    <!-- Footer -->
    <footer class="footer mt-5 py-4 bg-light border-top">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-car-front-fill text-primary me-2"></i>
                        <span class="fw-bold">Car Wash Emanuel</span>
                        <small class="text-muted ms-2">v1.0</small>
                    </div>
                    <small class="text-muted">
                        Sistema de Control de Plataforma de Clientes
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        © <?php echo date('Y'); ?> Car Wash Emanuel. 
                        <span class="d-none d-md-inline">Todos los derechos reservados.</span>
                    </small>
                    <br>
                    <small class="text-muted">
                        <i class="bi bi-person me-1"></i>
                        Conectado como: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuario'); ?></strong>
                        <span class="badge bg-secondary ms-1"><?php echo ucfirst($_SESSION['user_role'] ?? 'user'); ?></span>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts de Bootstrap y funcionalidades -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts personalizados -->
    <script>
        // Función de confirmación para eliminaciones
        function confirmDelete(message = '¿Está seguro de que desea eliminar este elemento?') {
            return confirm(message);
        }
        
        // Función para validar formularios
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (!form) return true;
            
            form.classList.add('was-validated');
            return form.checkValidity();
        }
        
        // Función para formatear campos de moneda
        function formatCurrencyInput(input) {
            let value = parseFloat(input.value);
            if (!isNaN(value)) {
                input.value = value.toFixed(2);
            }
        }
        
        // Auto-cerrar alertas después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Función para mostrar loading en botones
        function showButtonLoading(button, text = 'Cargando...') {
            const originalText = button.innerHTML;
            button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>${text}`;
            button.disabled = true;
            
            return function() {
                button.innerHTML = originalText;
                button.disabled = false;
            };
        }
        
        // Función para actualizar la hora actual
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleString('es-CR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // Actualizar hora cada segundo
        setInterval(updateCurrentTime, 1000);
        
        // Tooltips de Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        // Popovers de Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });
        
        // Función para imprimir reportes
        function printReport() {
            window.print();
        }
        
        // Función para exportar datos (placeholder)
        function exportData(format = 'csv') {
            alert('Función de exportación en desarrollo. Formato: ' + format);
        }
        
        // Validación en tiempo real para formularios
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form[data-validate="true"]');
            forms.forEach(function(form) {
                form.addEventListener('input', function(e) {
                    if (e.target.checkValidity()) {
                        e.target.classList.remove('is-invalid');
                        e.target.classList.add('is-valid');
                    } else {
                        e.target.classList.remove('is-valid');
                        e.target.classList.add('is-invalid');
                    }
                });
            });
        });
        
        // Función para mostrar notificaciones toast
        function showToast(message, type = 'info') {
            const toastContainer = document.getElementById('toast-container') || createToastContainer();
            
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            const toastElement = toastContainer.lastElementChild;
            const toast = new bootstrap.Toast(toastElement);
            toast.show();
            
            // Remover el toast del DOM después de que se oculte
            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }
        
        function createToastContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1055';
            document.body.appendChild(container);
            return container;
        }
    </script>
    
    <!-- Script para mantener la sesión activa -->
    <script>
        // Ping al servidor cada 15 minutos para mantener la sesión activa
        setInterval(function() {
            fetch('api/ping.php', {
                method: 'GET',
                credentials: 'same-origin'
            }).catch(function(error) {
                console.log('Error manteniendo sesión:', error);
            });
        }, 15 * 60 * 1000); // 15 minutos
    </script>
</body>
</html>