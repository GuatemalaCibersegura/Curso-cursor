            </main>
        </div>
    </div>

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
                alerts.forEach(function(alert) {
                    try {
                        var bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    } catch(e) {
                        // Silently handle if alert is already closed
                    }
                });
            }, 5000);
            
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Real-time form validation
            const forms = document.querySelectorAll('form[novalidate]');
            forms.forEach(form => {
                form.addEventListener('input', function(e) {
                    if (e.target.hasAttribute('required')) {
                        if (e.target.value.trim()) {
                            e.target.classList.remove('is-invalid');
                            e.target.classList.add('is-valid');
                        } else {
                            e.target.classList.remove('is-valid');
                        }
                    }
                    
                    // Email validation
                    if (e.target.type === 'email' && e.target.value) {
                        if (isValidEmail(e.target.value)) {
                            e.target.classList.remove('is-invalid');
                            e.target.classList.add('is-valid');
                        } else {
                            e.target.classList.remove('is-valid');
                            e.target.classList.add('is-invalid');
                        }
                    }
                });
            });
        });
        
        // Confirm delete actions
        function confirmDelete(message = '¿Está seguro de que desea eliminar este elemento?') {
            return confirm(message);
        }
        
        // Format currency inputs
        function formatCurrencyInput(input) {
            let value = input.value.replace(/[^\d.]/g, '');
            if (value) {
                input.value = parseFloat(value).toFixed(2);
            }
        }
        
        // Email validation
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        // Validate form before submission
        function validateForm(formId) {
            var form = document.getElementById(formId);
            let isValid = true;
            
            // Check required fields
            const requiredFields = form.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');
                }
            });
            
            // Check email fields
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(field => {
                if (field.value && !isValidEmail(field.value)) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
            });
            
            form.classList.add('was-validated');
            return isValid;
        }
        
        // Print function for reports
        function printReport() {
            window.print();
        }
        
        // Show loading spinner
        function showLoading(buttonId) {
            const button = document.getElementById(buttonId);
            if (button) {
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...';
            }
        }
        
        // Hide loading spinner
        function hideLoading(buttonId, originalText) {
            const button = document.getElementById(buttonId);
            if (button) {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }
    </script>
    
    <!-- Print styles -->
    <style media="print">
        .no-print, .navbar, .sidebar, .btn-toolbar {
            display: none !important;
        }
        
        .main-content {
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .table {
            font-size: 12px !important;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
    
    <?php if (isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
</body>
</html>