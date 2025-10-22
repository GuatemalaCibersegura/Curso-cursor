<?php
/**
 * Módulo de Gestión de Servicios/Citas
 * Sistema de Control de Plataforma de Clientes - Car Wash Emanuel
 */

require_once 'includes/functions.php';
require_once 'config/database.php';

// Requerir autenticación
requireLogin();

$page_title = 'Gestión de Servicios';
$page_icon = 'bi bi-calendar-check-fill';
$breadcrumbs = [
    ['title' => 'Servicios']
];

// Inicializar variables
$error_message = '';
$success_message = '';
$action = $_GET['action'] ?? 'list';
$service_id = $_GET['id'] ?? null;
$client_id = $_GET['client_id'] ?? null;
$vehicle_id = $_GET['vehicle_id'] ?? null;

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Procesar acciones POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!verifyCSRFToken($csrf_token)) {
            throw new Exception('Token de seguridad inválido');
        }
        
        switch ($action) {
            case 'add':
                // Agregar nueva cita
                $cliente_id = intval($_POST['cliente_id']);
                $vehiculo_id = intval($_POST['vehiculo_id']);
                $tipo_servicio_id = intval($_POST['tipo_servicio_id']);
                $fecha_cita = $_POST['fecha_cita'];
                $hora_cita = $_POST['hora_cita'];
                
                // Validaciones
                if (empty($cliente_id) || empty($vehiculo_id) || empty($tipo_servicio_id) || empty($fecha_cita) || empty($hora_cita)) {
                    throw new Exception('Todos los campos son obligatorios');
                }
                
                // Combinar fecha y hora
                $fecha_hora_cita = $fecha_cita . ' ' . $hora_cita;
                
                // Verificar que la fecha no sea en el pasado
                if (strtotime($fecha_hora_cita) < time()) {
                    throw new Exception('No se puede programar una cita en el pasado');
                }
                
                // Verificar que el vehículo pertenezca al cliente
                $stmt = $conn->prepare("SELECT id FROM vehiculos WHERE id = ? AND cliente_id = ?");
                $stmt->execute([$vehiculo_id, $cliente_id]);
                if (!$stmt->fetch()) {
                    throw new Exception('El vehículo seleccionado no pertenece al cliente');
                }
                
                // Insertar cita
                $stmt = $conn->prepare("
                    INSERT INTO citas (cliente_id, vehiculo_id, tipo_servicio_id, fecha_cita, estado) 
                    VALUES (?, ?, ?, ?, 'programada')
                ");
                
                if ($stmt->execute([$cliente_id, $vehiculo_id, $tipo_servicio_id, $fecha_hora_cita])) {
                    $new_service_id = $conn->lastInsertId();
                    logActivity('Cita programada', "Cita ID: $new_service_id para cliente ID: $cliente_id");
                    $success_message = 'Cita programada exitosamente';
                    $action = 'list';
                } else {
                    throw new Exception('Error al programar la cita');
                }
                break;
                
            case 'edit':
                // Editar cita existente
                $cliente_id = intval($_POST['cliente_id']);
                $vehiculo_id = intval($_POST['vehiculo_id']);
                $tipo_servicio_id = intval($_POST['tipo_servicio_id']);
                $fecha_cita = $_POST['fecha_cita'];
                $hora_cita = $_POST['hora_cita'];
                $estado = $_POST['estado'];
                
                // Validaciones
                if (empty($cliente_id) || empty($vehiculo_id) || empty($tipo_servicio_id) || empty($fecha_cita) || empty($hora_cita)) {
                    throw new Exception('Todos los campos son obligatorios');
                }
                
                // Combinar fecha y hora
                $fecha_hora_cita = $fecha_cita . ' ' . $hora_cita;
                
                // Actualizar cita
                $stmt = $conn->prepare("
                    UPDATE citas 
                    SET cliente_id = ?, vehiculo_id = ?, tipo_servicio_id = ?, fecha_cita = ?, estado = ?
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$cliente_id, $vehiculo_id, $tipo_servicio_id, $fecha_hora_cita, $estado, $service_id])) {
                    logActivity('Cita actualizada', "Cita ID: $service_id");
                    $success_message = 'Cita actualizada exitosamente';
                    $action = 'list';
                } else {
                    throw new Exception('Error al actualizar la cita');
                }
                break;
                
            case 'complete':
                // Completar servicio
                if (!$service_id) {
                    throw new Exception('ID de servicio requerido');
                }
                
                $stmt = $conn->prepare("UPDATE citas SET estado = 'completada' WHERE id = ?");
                if ($stmt->execute([$service_id])) {
                    logActivity('Servicio completado', "Cita ID: $service_id");
                    $success_message = 'Servicio marcado como completado';
                } else {
                    throw new Exception('Error al completar el servicio');
                }
                $action = 'list';
                break;
                
            case 'cancel':
                // Cancelar servicio
                if (!$service_id) {
                    throw new Exception('ID de servicio requerido');
                }
                
                $stmt = $conn->prepare("UPDATE citas SET estado = 'cancelada' WHERE id = ?");
                if ($stmt->execute([$service_id])) {
                    logActivity('Servicio cancelado', "Cita ID: $service_id");
                    $success_message = 'Servicio cancelado';
                } else {
                    throw new Exception('Error al cancelar el servicio');
                }
                $action = 'list';
                break;
        }
    }
    
    // Procesar eliminaciones (GET)
    if ($action === 'delete' && $service_id) {
        $stmt = $conn->prepare("DELETE FROM citas WHERE id = ?");
        if ($stmt->execute([$service_id])) {
            logActivity('Cita eliminada', "Cita ID: $service_id");
            $success_message = 'Cita eliminada exitosamente';
        } else {
            $error_message = 'Error al eliminar la cita';
        }
        $action = 'list';
    }
    
    // Obtener datos según la acción
    switch ($action) {
        case 'list':
            // Filtros de búsqueda
            $filter_date = $_GET['filter_date'] ?? '';
            $filter_status = $_GET['filter_status'] ?? '';
            $filter_client = $_GET['filter_client'] ?? '';
            
            // Construir consulta con filtros
            $sql = "SELECT * FROM vista_servicios_completos WHERE 1=1";
            $params = [];
            
            if (!empty($filter_date)) {
                $sql .= " AND DATE(fecha_cita) = ?";
                $params[] = $filter_date;
            }
            
            if (!empty($filter_status)) {
                $sql .= " AND estado = ?";
                $params[] = $filter_status;
            }
            
            if (!empty($filter_client)) {
                $sql .= " AND cliente_nombre LIKE ?";
                $params[] = '%' . $filter_client . '%';
            }
            
            if (!empty($client_id)) {
                $sql .= " AND cita_id IN (SELECT id FROM citas WHERE cliente_id = ?)";
                $params[] = $client_id;
            }
            
            $sql .= " ORDER BY fecha_cita DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $services = $stmt->fetchAll();
            break;
            
        case 'add':
        case 'edit':
            // Obtener clientes para el select
            $clients = getClients($conn);
            
            // Obtener tipos de servicio
            $service_types = getServiceTypes($conn);
            
            if ($action === 'edit' && $service_id) {
                // Obtener datos de la cita específica
                $stmt = $conn->prepare("
                    SELECT c.*, cl.nombre as cliente_nombre, v.placa, ts.nombre as tipo_servicio
                    FROM citas c
                    JOIN clientes cl ON c.cliente_id = cl.id
                    JOIN vehiculos v ON c.vehiculo_id = v.id
                    JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
                    WHERE c.id = ?
                ");
                $stmt->execute([$service_id]);
                $service = $stmt->fetch();
                
                if (!$service) {
                    $error_message = 'Cita no encontrada';
                    $action = 'list';
                    break;
                }
                
                // Obtener vehículos del cliente
                $vehicles = getClientVehicles($conn, $service['cliente_id']);
            } elseif ($client_id) {
                // Si se especifica un cliente, obtener sus vehículos
                $vehicles = getClientVehicles($conn, $client_id);
            }
            break;
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log('Error en services.php: ' . $e->getMessage());
}

// Configurar acciones de página
if ($action === 'add') {
    $page_actions = '<a href="services.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>';
} elseif ($action === 'edit') {
    $page_actions = '<a href="services.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>';
} else {
    $page_actions = '<a href="services.php?action=add" class="btn btn-primary"><i class="bi bi-plus"></i> Nueva Cita</a>';
}

include 'includes/header.php';
?>

<!-- Mensajes de estado -->
<?php if ($error_message): ?>
    <?php showError($error_message); ?>
<?php endif; ?>

<?php if ($success_message): ?>
    <?php showSuccess($success_message); ?>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<!-- Lista de Servicios -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-calendar-check me-2"></i>Lista de Citas y Servicios
            </h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <i class="bi bi-funnel"></i> Filtros
                </button>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="collapse" id="filterCollapse">
        <div class="card-body border-bottom">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="filter_date" class="form-label">Fecha</label>
                    <input type="date" class="form-control" id="filter_date" name="filter_date" 
                           value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                <div class="col-md-3">
                    <label for="filter_status" class="form-label">Estado</label>
                    <select class="form-select" id="filter_status" name="filter_status">
                        <option value="">Todos los estados</option>
                        <option value="programada" <?php echo $filter_status === 'programada' ? 'selected' : ''; ?>>Programada</option>
                        <option value="completada" <?php echo $filter_status === 'completada' ? 'selected' : ''; ?>>Completada</option>
                        <option value="cancelada" <?php echo $filter_status === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="filter_client" class="form-label">Cliente</label>
                    <input type="text" class="form-control" id="filter_client" name="filter_client" 
                           placeholder="Buscar por nombre de cliente" value="<?php echo htmlspecialchars($filter_client); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                    <a href="services.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card-body">
        <?php if (empty($services)): ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x display-1 text-muted"></i>
                <h4 class="mt-3">No hay servicios registrados</h4>
                <p class="text-muted">Comience programando la primera cita</p>
                <a href="services.php?action=add" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Nueva Cita
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Cliente</th>
                            <th>Vehículo</th>
                            <th>Servicio</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?php echo formatDate($service['fecha_cita']); ?></strong><br>
                                    <small class="text-muted"><?php echo date('g:i A', strtotime($service['fecha_cita'])); ?></small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($service['cliente_nombre']); ?></strong><br>
                                    <small class="text-muted">
                                        <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($service['cliente_telefono']); ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="badge bg-dark"><?php echo htmlspecialchars($service['placa']); ?></span><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($service['marca'] . ' ' . $service['modelo']); ?></small>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($service['tipo_servicio']); ?></strong>
                            </td>
                            <td>
                                <strong><?php echo formatCurrency($service['precio']); ?></strong>
                            </td>
                            <td>
                                <?php
                                $estado_class = '';
                                $estado_icon = '';
                                switch ($service['estado']) {
                                    case 'completada':
                                        $estado_class = 'bg-success';
                                        $estado_icon = 'bi-check-circle';
                                        break;
                                    case 'programada':
                                        $estado_class = 'bg-primary';
                                        $estado_icon = 'bi-clock';
                                        break;
                                    case 'cancelada':
                                        $estado_class = 'bg-danger';
                                        $estado_icon = 'bi-x-circle';
                                        break;
                                    default:
                                        $estado_class = 'bg-secondary';
                                        $estado_icon = 'bi-question-circle';
                                }
                                ?>
                                <span class="badge <?php echo $estado_class; ?>">
                                    <i class="<?php echo $estado_icon; ?> me-1"></i>
                                    <?php echo ucfirst($service['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="services.php?action=edit&id=<?php echo $service['cita_id']; ?>">
                                                <i class="bi bi-pencil me-2"></i>Editar
                                            </a>
                                        </li>
                                        <?php if ($service['estado'] === 'programada'): ?>
                                        <li>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <button type="submit" class="dropdown-item" 
                                                        onclick="return confirm('¿Marcar este servicio como completado?')"
                                                        formaction="services.php?action=complete&id=<?php echo $service['cita_id']; ?>">
                                                    <i class="bi bi-check-circle me-2"></i>Completar
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <button type="submit" class="dropdown-item text-warning" 
                                                        onclick="return confirm('¿Cancelar este servicio?')"
                                                        formaction="services.php?action=cancel&id=<?php echo $service['cita_id']; ?>">
                                                    <i class="bi bi-x-circle me-2"></i>Cancelar
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" 
                                               href="services.php?action=delete&id=<?php echo $service['cita_id']; ?>"
                                               onclick="return confirm('¿Está seguro de eliminar esta cita?')">
                                                <i class="bi bi-trash me-2"></i>Eliminar
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<!-- Formulario de Cita -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-calendar-plus me-2"></i>
            <?php echo $action === 'add' ? 'Nueva Cita' : 'Editar Cita'; ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" id="serviceForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="cliente_id" class="form-label">Cliente *</label>
                        <select class="form-select" id="cliente_id" name="cliente_id" required 
                                onchange="loadClientVehicles(this.value)">
                            <option value="">Seleccionar cliente</option>
                            <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" 
                                    <?php echo ($client_id == $client['id'] || (isset($service) && $service['cliente_id'] == $client['id'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="vehiculo_id" class="form-label">Vehículo *</label>
                        <select class="form-select" id="vehiculo_id" name="vehiculo_id" required>
                            <option value="">Seleccionar vehículo</option>
                            <?php if (isset($vehicles)): ?>
                                <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?php echo $vehicle['id']; ?>"
                                        <?php echo ($vehicle_id == $vehicle['id'] || (isset($service) && $service['vehiculo_id'] == $vehicle['id'])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vehicle['placa'] . ' - ' . $vehicle['marca'] . ' ' . $vehicle['modelo']); ?>
                                </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="tipo_servicio_id" class="form-label">Tipo de Servicio *</label>
                        <select class="form-select" id="tipo_servicio_id" name="tipo_servicio_id" required>
                            <option value="">Seleccionar servicio</option>
                            <?php foreach ($service_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>" data-price="<?php echo $type['precio']; ?>"
                                    <?php echo (isset($service) && $service['tipo_servicio_id'] == $type['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['nombre'] . ' - ' . formatCurrency($type['precio'])); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="fecha_cita" class="form-label">Fecha *</label>
                        <input type="date" class="form-control" id="fecha_cita" name="fecha_cita" 
                               value="<?php echo isset($service) ? date('Y-m-d', strtotime($service['fecha_cita'])) : date('Y-m-d'); ?>" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="hora_cita" class="form-label">Hora *</label>
                        <input type="time" class="form-control" id="hora_cita" name="hora_cita" 
                               value="<?php echo isset($service) ? date('H:i', strtotime($service['fecha_cita'])) : '09:00'; ?>" 
                               required>
                    </div>
                </div>
            </div>
            
            <?php if ($action === 'edit'): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="programada" <?php echo (isset($service) && $service['estado'] === 'programada') ? 'selected' : ''; ?>>Programada</option>
                            <option value="completada" <?php echo (isset($service) && $service['estado'] === 'completada') ? 'selected' : ''; ?>>Completada</option>
                            <option value="cancelada" <?php echo (isset($service) && $service['estado'] === 'cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="text-end">
                <a href="services.php" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check"></i> 
                    <?php echo $action === 'add' ? 'Programar Cita' : 'Actualizar Cita'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php endif; ?>

<script>
// Cargar vehículos del cliente seleccionado
function loadClientVehicles(clientId) {
    const vehicleSelect = document.getElementById('vehiculo_id');
    vehicleSelect.innerHTML = '<option value="">Cargando...</option>';
    
    if (!clientId) {
        vehicleSelect.innerHTML = '<option value="">Seleccionar vehículo</option>';
        return;
    }
    
    fetch(`api/get_client_vehicles.php?client_id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            vehicleSelect.innerHTML = '<option value="">Seleccionar vehículo</option>';
            data.forEach(vehicle => {
                const option = document.createElement('option');
                option.value = vehicle.id;
                option.textContent = `${vehicle.placa} - ${vehicle.marca} ${vehicle.modelo}`;
                vehicleSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            vehicleSelect.innerHTML = '<option value="">Error al cargar vehículos</option>';
        });
}

// Validación de formulario
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('serviceForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
    
    // Auto-cargar vehículos si hay un cliente preseleccionado
    const clientSelect = document.getElementById('cliente_id');
    if (clientSelect && clientSelect.value) {
        loadClientVehicles(clientSelect.value);
    }
});

// Mostrar precio del servicio seleccionado
document.getElementById('tipo_servicio_id')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    if (price) {
        console.log('Precio del servicio:', price);
    }
});
</script>

<?php include 'includes/footer.php'; ?>