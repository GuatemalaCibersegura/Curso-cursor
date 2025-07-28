<?php
/**
 * Módulo de Gestión de Clientes
 * Sistema de Control de Plataforma de Clientes - Car Wash Emanuel
 */

require_once 'includes/functions.php';
require_once 'config/database.php';

// Requerir autenticación
requireLogin();

$page_title = 'Gestión de Clientes';
$page_icon = 'bi bi-people-fill';
$breadcrumbs = [
    ['title' => 'Clientes']
];

// Inicializar variables
$error_message = '';
$success_message = '';
$action = $_GET['action'] ?? 'list';
$client_id = $_GET['id'] ?? null;

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
                // Agregar nuevo cliente
                $nombre = sanitizeInput($_POST['nombre']);
                $telefono = sanitizeInput($_POST['telefono']);
                $correo = sanitizeInput($_POST['correo']);
                $direccion = sanitizeInput($_POST['direccion']);
                
                // Validaciones
                if (empty($nombre) || empty($telefono)) {
                    throw new Exception('El nombre y teléfono son obligatorios');
                }
                
                if (!empty($correo) && !validateEmail($correo)) {
                    throw new Exception('Formato de email inválido');
                }
                
                if (!validatePhone($telefono)) {
                    throw new Exception('Formato de teléfono inválido');
                }
                
                // Insertar cliente
                $stmt = $conn->prepare("
                    INSERT INTO clientes (nombre, telefono, correo, direccion) 
                    VALUES (?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$nombre, $telefono, $correo, $direccion])) {
                    $new_client_id = $conn->lastInsertId();
                    logActivity('Cliente agregado', "Cliente: $nombre (ID: $new_client_id)");
                    $success_message = 'Cliente agregado exitosamente';
                    $action = 'list';
                } else {
                    throw new Exception('Error al agregar el cliente');
                }
                break;
                
            case 'edit':
                // Editar cliente existente
                $nombre = sanitizeInput($_POST['nombre']);
                $telefono = sanitizeInput($_POST['telefono']);
                $correo = sanitizeInput($_POST['correo']);
                $direccion = sanitizeInput($_POST['direccion']);
                
                // Validaciones
                if (empty($nombre) || empty($telefono)) {
                    throw new Exception('El nombre y teléfono son obligatorios');
                }
                
                if (!empty($correo) && !validateEmail($correo)) {
                    throw new Exception('Formato de email inválido');
                }
                
                if (!validatePhone($telefono)) {
                    throw new Exception('Formato de teléfono inválido');
                }
                
                // Actualizar cliente
                $stmt = $conn->prepare("
                    UPDATE clientes 
                    SET nombre = ?, telefono = ?, correo = ?, direccion = ? 
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$nombre, $telefono, $correo, $direccion, $client_id])) {
                    logActivity('Cliente actualizado', "Cliente: $nombre (ID: $client_id)");
                    $success_message = 'Cliente actualizado exitosamente';
                    $action = 'list';
                } else {
                    throw new Exception('Error al actualizar el cliente');
                }
                break;
                
            case 'add_vehicle':
                // Agregar vehículo al cliente
                $placa = strtoupper(sanitizeInput($_POST['placa']));
                $marca = sanitizeInput($_POST['marca']);
                $modelo = sanitizeInput($_POST['modelo']);
                $ano = intval($_POST['ano']);
                $color = sanitizeInput($_POST['color']);
                
                // Validaciones
                if (empty($placa) || empty($marca) || empty($modelo) || empty($color)) {
                    throw new Exception('Todos los campos del vehículo son obligatorios');
                }
                
                if (!validatePlate($placa)) {
                    throw new Exception('Formato de placa inválido');
                }
                
                if ($ano < 1900 || $ano > date('Y') + 1) {
                    throw new Exception('Año del vehículo inválido');
                }
                
                // Verificar que la placa no exista
                $stmt = $conn->prepare("SELECT id FROM vehiculos WHERE placa = ?");
                $stmt->execute([$placa]);
                if ($stmt->fetch()) {
                    throw new Exception('Ya existe un vehículo con esta placa');
                }
                
                // Insertar vehículo
                $stmt = $conn->prepare("
                    INSERT INTO vehiculos (cliente_id, placa, marca, modelo, ano, color) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$client_id, $placa, $marca, $modelo, $ano, $color])) {
                    logActivity('Vehículo agregado', "Placa: $placa para cliente ID: $client_id");
                    $success_message = 'Vehículo agregado exitosamente';
                    $action = 'view';
                } else {
                    throw new Exception('Error al agregar el vehículo');
                }
                break;
        }
    }
    
    // Procesar eliminaciones (GET)
    if ($action === 'delete' && $client_id) {
        // Verificar que el cliente no tenga citas pendientes
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM citas 
            WHERE cliente_id = ? AND estado = 'programada'
        ");
        $stmt->execute([$client_id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $error_message = 'No se puede eliminar el cliente porque tiene citas programadas';
        } else {
            // Eliminar cliente (los vehículos se eliminan automáticamente por CASCADE)
            $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
            if ($stmt->execute([$client_id])) {
                logActivity('Cliente eliminado', "Cliente ID: $client_id");
                $success_message = 'Cliente eliminado exitosamente';
                $action = 'list';
            } else {
                $error_message = 'Error al eliminar el cliente';
            }
        }
    }
    
    // Eliminar vehículo
    if ($action === 'delete_vehicle') {
        $vehicle_id = $_GET['vehicle_id'] ?? null;
        if ($vehicle_id) {
            // Verificar que el vehículo no tenga citas pendientes
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM citas 
                WHERE vehiculo_id = ? AND estado = 'programada'
            ");
            $stmt->execute([$vehicle_id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $error_message = 'No se puede eliminar el vehículo porque tiene citas programadas';
            } else {
                $stmt = $conn->prepare("DELETE FROM vehiculos WHERE id = ?");
                if ($stmt->execute([$vehicle_id])) {
                    logActivity('Vehículo eliminado', "Vehículo ID: $vehicle_id");
                    $success_message = 'Vehículo eliminado exitosamente';
                    $action = 'view';
                } else {
                    $error_message = 'Error al eliminar el vehículo';
                }
            }
        }
    }
    
    // Obtener datos según la acción
    switch ($action) {
        case 'list':
            // Obtener lista de clientes con conteo de vehículos
            $stmt = $conn->prepare("
                SELECT c.*, 
                       COUNT(v.id) as total_vehiculos,
                       MAX(citas.fecha_cita) as ultima_cita
                FROM clientes c
                LEFT JOIN vehiculos v ON c.id = v.cliente_id
                LEFT JOIN citas ON c.id = citas.cliente_id
                GROUP BY c.id
                ORDER BY c.nombre ASC
            ");
            $stmt->execute();
            $clients = $stmt->fetchAll();
            break;
            
        case 'view':
        case 'edit':
            // Obtener datos del cliente específico
            if (!$client_id) {
                header("Location: clients.php");
                exit();
            }
            
            $client = getClientById($conn, $client_id);
            if (!$client) {
                $error_message = 'Cliente no encontrado';
                $action = 'list';
                break;
            }
            
            // Obtener vehículos del cliente
            $vehicles = getClientVehicles($conn, $client_id);
            
            // Obtener historial de citas
            $stmt = $conn->prepare("
                SELECT c.*, ts.nombre as tipo_servicio, ts.precio, v.placa
                FROM citas c
                JOIN tipos_servicio ts ON c.tipo_servicio_id = ts.id
                JOIN vehiculos v ON c.vehiculo_id = v.id
                WHERE c.cliente_id = ?
                ORDER BY c.fecha_cita DESC
                LIMIT 10
            ");
            $stmt->execute([$client_id]);
            $appointments = $stmt->fetchAll();
            break;
    }
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
    error_log('Error en clients.php: ' . $e->getMessage());
}

// Configurar acciones de página
if ($action === 'add') {
    $page_actions = '<a href="clients.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>';
} elseif ($action === 'edit') {
    $page_actions = '<a href="clients.php?action=view&id=' . $client_id . '" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>';
} elseif ($action === 'view') {
    $page_actions = '
        <a href="clients.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
        <a href="clients.php?action=edit&id=' . $client_id . '" class="btn btn-primary"><i class="bi bi-pencil"></i> Editar</a>
    ';
} else {
    $page_actions = '<a href="clients.php?action=add" class="btn btn-primary"><i class="bi bi-plus"></i> Nuevo Cliente</a>';
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
<!-- Lista de Clientes -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-people me-2"></i>Lista de Clientes
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($clients)): ?>
            <div class="text-center py-5">
                <i class="bi bi-people display-1 text-muted"></i>
                <h4 class="mt-3">No hay clientes registrados</h4>
                <p class="text-muted">Comience agregando su primer cliente</p>
                <a href="clients.php?action=add" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Agregar Cliente
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Vehículos</th>
                            <th>Última Cita</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($client['nombre']); ?></strong>
                            </td>
                            <td>
                                <i class="bi bi-telephone me-1"></i>
                                <?php echo htmlspecialchars(formatPhone($client['telefono'])); ?>
                            </td>
                            <td>
                                <?php if ($client['correo']): ?>
                                    <i class="bi bi-envelope me-1"></i>
                                    <?php echo htmlspecialchars($client['correo']); ?>
                                <?php else: ?>
                                    <span class="text-muted">No registrado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo $client['total_vehiculos']; ?> vehículo(s)
                                </span>
                            </td>
                            <td>
                                <?php if ($client['ultima_cita']): ?>
                                    <?php echo formatDate($client['ultima_cita']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin citas</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="clients.php?action=view&id=<?php echo $client['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="clients.php?action=edit&id=<?php echo $client['id']; ?>" 
                                       class="btn btn-sm btn-outline-secondary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="clients.php?action=delete&id=<?php echo $client['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger" title="Eliminar"
                                       onclick="return confirm('¿Está seguro de eliminar este cliente?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
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
<!-- Formulario de Cliente -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bi bi-person-plus me-2"></i>
            <?php echo $action === 'add' ? 'Nuevo Cliente' : 'Editar Cliente'; ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?php echo htmlspecialchars($client['nombre'] ?? ''); ?>" 
                               required maxlength="100">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono *</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono" 
                               value="<?php echo htmlspecialchars($client['telefono'] ?? ''); ?>" 
                               required maxlength="20" placeholder="+502 1234-5678"
                               onblur="formatPhoneInput(this)">
                        <div class="form-text">Formato: +502 1234-5678 o 12345678</div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="correo" class="form-label">Email</label>
                        <input type="email" class="form-control" id="correo" name="correo" 
                               value="<?php echo htmlspecialchars($client['correo'] ?? ''); ?>" 
                               maxlength="100" placeholder="cliente@email.com">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <textarea class="form-control" id="direccion" name="direccion" 
                                  rows="3" placeholder="Dirección completa del cliente"><?php echo htmlspecialchars($client['direccion'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="text-end">
                <a href="clients.php" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check"></i> <?php echo $action === 'add' ? 'Agregar Cliente' : 'Actualizar Cliente'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php elseif ($action === 'view'): ?>
<!-- Vista de Cliente -->
<div class="row">
    <!-- Información del Cliente -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-person-circle me-2"></i>Información del Cliente
                </h5>
            </div>
            <div class="card-body">
                <h4><?php echo htmlspecialchars($client['nombre']); ?></h4>
                
                <div class="mb-3">
                    <strong><i class="bi bi-telephone me-2"></i>Teléfono:</strong><br>
                                                <a href="tel:<?php echo $client['telefono']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars(formatPhone($client['telefono'])); ?>
                    </a>
                </div>
                
                <?php if ($client['correo']): ?>
                <div class="mb-3">
                    <strong><i class="bi bi-envelope me-2"></i>Email:</strong><br>
                    <a href="mailto:<?php echo $client['correo']; ?>" class="text-decoration-none">
                        <?php echo htmlspecialchars($client['correo']); ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($client['direccion']): ?>
                <div class="mb-3">
                    <strong><i class="bi bi-geo-alt me-2"></i>Dirección:</strong><br>
                    <?php echo nl2br(htmlspecialchars($client['direccion'])); ?>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <strong><i class="bi bi-calendar me-2"></i>Cliente desde:</strong><br>
                    <?php echo formatDate($client['creado_en']); ?>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="services.php?action=add&client_id=<?php echo $client['id']; ?>" 
                       class="btn btn-success">
                        <i class="bi bi-plus"></i> Nueva Cita
                    </a>
                    <a href="clients.php?action=edit&id=<?php echo $client['id']; ?>" 
                       class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Editar Cliente
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Vehículos -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-car-front me-2"></i>Vehículos
                </h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                    <i class="bi bi-plus"></i> Agregar Vehículo
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($vehicles)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-car-front display-4 text-muted"></i>
                        <h5 class="mt-3">No hay vehículos registrados</h5>
                        <p class="text-muted">Agregue el primer vehículo de este cliente</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($vehicles as $vehicle): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="card-title">
                                                <span class="badge bg-dark"><?php echo htmlspecialchars($vehicle['placa']); ?></span>
                                            </h6>
                                            <p class="card-text mb-1">
                                                <strong><?php echo htmlspecialchars($vehicle['marca'] . ' ' . $vehicle['modelo']); ?></strong>
                                            </p>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <?php echo $vehicle['ano']; ?> • <?php echo htmlspecialchars($vehicle['color']); ?>
                                                </small>
                                            </p>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="services.php?action=add&client_id=<?php echo $client['id']; ?>&vehicle_id=<?php echo $vehicle['id']; ?>">
                                                        <i class="bi bi-plus me-2"></i>Nueva Cita
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item text-danger" 
                                                       href="clients.php?action=delete_vehicle&id=<?php echo $client['id']; ?>&vehicle_id=<?php echo $vehicle['id']; ?>"
                                                       onclick="return confirm('¿Está seguro de eliminar este vehículo?')">
                                                        <i class="bi bi-trash me-2"></i>Eliminar
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Historial de Citas -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Historial de Citas
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($appointments)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-calendar-x display-4 text-muted"></i>
                        <h5 class="mt-3">No hay citas registradas</h5>
                        <p class="text-muted">Este cliente aún no tiene historial de servicios</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Vehículo</th>
                                    <th>Servicio</th>
                                    <th>Estado</th>
                                    <th>Costo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo formatDateTime($appointment['fecha_cita']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($appointment['placa']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['tipo_servicio']); ?></td>
                                    <td>
                                        <?php
                                        $estado_class = '';
                                        switch ($appointment['estado']) {
                                            case 'completada':
                                                $estado_class = 'bg-success';
                                                break;
                                            case 'programada':
                                                $estado_class = 'bg-primary';
                                                break;
                                            case 'cancelada':
                                                $estado_class = 'bg-danger';
                                                break;
                                            default:
                                                $estado_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $estado_class; ?>">
                                            <?php echo ucfirst($appointment['estado']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatCurrency($appointment['precio']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="services.php?client_id=<?php echo $client['id']; ?>" class="btn btn-outline-primary">
                            Ver todas las citas
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Agregar Vehículo -->
<div class="modal fade" id="addVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-car-front me-2"></i>Agregar Vehículo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="placa" class="form-label">Placa *</label>
                                <input type="text" class="form-control" id="placa" name="placa" 
                                       required maxlength="20" placeholder="ABC123" style="text-transform: uppercase;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="marca" class="form-label">Marca *</label>
                                <input type="text" class="form-control" id="marca" name="marca" 
                                       required maxlength="50" placeholder="Toyota">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="modelo" class="form-label">Modelo *</label>
                                <input type="text" class="form-control" id="modelo" name="modelo" 
                                       required maxlength="50" placeholder="Corolla">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ano" class="form-label">Año *</label>
                                <input type="number" class="form-control" id="ano" name="ano" 
                                       required min="1900" max="<?php echo date('Y') + 1; ?>" 
                                       value="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="color" class="form-label">Color *</label>
                        <input type="text" class="form-control" id="color" name="color" 
                               required maxlength="30" placeholder="Blanco">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check"></i> Agregar Vehículo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
// Auto-formatear placa en mayúsculas
document.getElementById('placa')?.addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});

// Validación de formulario
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>