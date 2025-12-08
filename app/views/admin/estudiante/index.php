<?php
// app/views/admin/estudiantes/index.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../../config/conexion.php';

// Manejar acciones CRUD
$accion = $_GET['accion'] ?? 'listar';
$id_estudiante = $_GET['id'] ?? null;

// Procesar eliminación
if ($accion === 'eliminar' && $id_estudiante) {
    // Verificar si el estudiante tiene matrículas
    $stmt_check = $conexion->prepare("SELECT COUNT(*) as total FROM matriculas WHERE id_estudiante = ?");
    $stmt_check->bind_param("i", $id_estudiante);
    $stmt_check->execute();
    $result = $stmt_check->get_result()->fetch_assoc();
    
    if ($result['total'] > 0) {
        // No eliminar, solo marcar como inactivo en usuario
        $stmt_get_user = $conexion->prepare("SELECT id_usuario FROM estudiantes WHERE id_estudiante = ?");
        $stmt_get_user->bind_param("i", $id_estudiante);
        $stmt_get_user->execute();
        $user_result = $stmt_get_user->get_result()->fetch_assoc();
        
        $stmt_update = $conexion->prepare("UPDATE usuario SET estado = 'inactivo' WHERE id_usuario = ?");
        $stmt_update->bind_param("i", $user_result['id_usuario']);
        $stmt_update->execute();
        
        $_SESSION['success'] = "Estudiante marcado como inactivo porque tiene matrículas registradas.";
    } else {
        // Eliminar completamente
        $stmt_get_user = $conexion->prepare("SELECT id_usuario FROM estudiantes WHERE id_estudiante = ?");
        $stmt_get_user->bind_param("i", $id_estudiante);
        $stmt_get_user->execute();
        $user_result = $stmt_get_user->get_result()->fetch_assoc();
        
        // Eliminar estudiante
        $stmt1 = $conexion->prepare("DELETE FROM estudiantes WHERE id_estudiante = ?");
        $stmt1->bind_param("i", $id_estudiante);
        $stmt1->execute();
        
        // Eliminar usuario
        $stmt2 = $conexion->prepare("DELETE FROM usuario WHERE id_usuario = ?");
        $stmt2->bind_param("i", $user_result['id_usuario']);
        $stmt2->execute();
        
        $_SESSION['success'] = "Estudiante eliminado exitosamente.";
    }
    
    // Registrar en auditoría
    $audit_stmt = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
    $accion_audit = "Eliminó estudiante ID: $id_estudiante";
    $audit_stmt->bind_param("ss", $_SESSION['user_name'], $accion_audit);
    $audit_stmt->execute();
    
    header('Location: index.php');
    exit();
}

// Obtener todos los estudiantes con información de usuario y carrera
$query = "SELECT e.*, u.nombre, u.apellido, u.correo, u.estado as estado_usuario, 
                 c.nombre as carrera_nombre, c.codigo as carrera_codigo
          FROM estudiantes e
          JOIN usuario u ON e.id_usuario = u.id_usuario
          LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
          ORDER BY u.apellido ASC, u.nombre ASC";

$estudiantes = $conexion->query($query);
$total_estudiantes = $estudiantes->num_rows;

// Obtener estadísticas
$query_stats = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN u.estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN u.estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
                COALESCE(AVG(e.año_carrera), 0) as año_promedio,
                COALESCE(AVG(e.semestre_actual), 0) as semestre_promedio
                FROM estudiantes e
                JOIN usuario u ON e.id_usuario = u.id_usuario";
$stats = $conexion->query($query_stats)->fetch_assoc();

// Obtener carreras para filtros
$carreras = $conexion->query("SELECT id_carrera, nombre, codigo FROM carreras WHERE estado = 'activa' ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudiantes - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/estudiantes.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="estudiantes-container">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="../dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
                    <span> / </span>
                    <span>Estudiantes</span>
                </div>
                
                <!-- Header -->
                <div class="header-actions">
                    <h1>
                        <i class="bi bi-people"></i>
                        Administración de Estudiantes
                    </h1>
                    <div style="display: flex; gap: 10px;">
                        <a href="create.php" class="btn-action btn-green">
                            <i class="bi bi-plus-circle"></i> Nuevo Estudiante
                        </a>
                        <a href="../reportes.php?tipo=estudiantes" class="btn-action btn-purple">
                            <i class="bi bi-printer"></i> Generar Reporte
                        </a>
                        <button onclick="exportToCSV()" class="btn-action" style="background: #38a169;">
                            <i class="bi bi-download"></i> Exportar CSV
                        </button>
                    </div>
                </div>
                
                <!-- Mensajes de sesión -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
                <?php endif; ?>
                
                <!-- Estadísticas -->
                <div class="stats-container">
                    <div class="stat-card">
                        <i class="bi bi-people" style="color: #2a5298;"></i>
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total de Estudiantes</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-check-circle" style="color: #38a169;"></i>
                        <div class="stat-number"><?php echo $stats['activos']; ?></div>
                        <div class="stat-label">Estudiantes Activos</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-x-circle" style="color: #e53e3e;"></i>
                        <div class="stat-number"><?php echo $stats['inactivos']; ?></div>
                        <div class="stat-label">Estudiantes Inactivos</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-graph-up" style="color: #805ad5;"></i>
                        <div class="stat-number">Año <?php echo number_format($stats['año_promedio'], 1); ?></div>
                        <div class="stat-label">Año Promedio de Carrera</div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="filters">
                    <div class="filter-group">
                        <select id="filterEstado">
                            <option value="">Todos los estados</option>
                            <option value="activo">Activos</option>
                            <option value="inactivo">Inactivos</option>
                        </select>
                        <select id="filterCarrera">
                            <option value="">Todas las carreras</option>
                            <?php 
                            $carreras->data_seek(0);
                            while($carrera = $carreras->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $carrera['id_carrera']; ?>">
                                <?php echo htmlspecialchars($carrera['codigo'] . ' - ' . $carrera['nombre']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <select id="filterAño">
                            <option value="">Todos los años</option>
                            <option value="1">Año 1</option>
                            <option value="2">Año 2</option>
                            <option value="3">Año 3</option>
                            <option value="4">Año 4</option>
                            <option value="5">Año 5</option>
                        </select>
                        <input type="text" id="searchEstudiante" placeholder="Buscar por nombre, cédula o correo..." style="flex: 1;">
                    </div>
                </div>
                
                <!-- Tabla -->
                <div class="table-container">
                    <table class="estudiantes-table" id="estudiantesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Estudiante</th>
                                <th>Información</th>
                                <th>Carrera</th>
                                <th>Avance</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($estudiantes->num_rows > 0): ?>
                                <?php while($estudiante = $estudiantes->fetch_assoc()): ?>
                                <tr data-carrera-id="<?php echo $estudiante['id_carrera'] ?? ''; ?>" 
                                    data-año="<?php echo $estudiante['año_carrera']; ?>"
                                    data-estado="<?php echo $estudiante['estado_usuario']; ?>">
                                    <td><strong>#<?php echo $estudiante['id_estudiante']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($estudiante['cedula']); ?></small>
                                    </td>
                                    <td>
                                        <div><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($estudiante['correo']); ?></div>
                                        <div><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($estudiante['telefono'] ?? 'No registrado'); ?></div>
                                        <div><i class="bi bi-calendar"></i> Ingreso: <?php echo date('d/m/Y', strtotime($estudiante['fecha_ingreso'] ?? date('Y-m-d'))); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($estudiante['carrera_nombre']): ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($estudiante['carrera_codigo']); ?></strong><br>
                                            <?php echo htmlspecialchars($estudiante['carrera_nombre']); ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="badge badge-secondary">Sin asignar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>Año <?php echo $estudiante['año_carrera']; ?> - Semestre <?php echo $estudiante['semestre_actual']; ?></strong>
                                            <div class="progress-bar">
                                                <?php 
                                                $semestres_totales = 8;
                                                $semestres_completados = (($estudiante['año_carrera'] - 1) * 2) + ($estudiante['semestre_actual'] - 1);
                                                $porcentaje = min(100, ($semestres_completados / $semestres_totales) * 100);
                                                ?>
                                                <div class="progress-fill" style="width: <?php echo $porcentaje; ?>%"></div>
                                            </div>
                                            <small style="color: #666;"><?php echo round($porcentaje, 1); ?>% completado</small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $badge_class = $estudiante['estado_usuario'] === 'activo' ? 'badge-success' : 'badge-warning';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo ucfirst($estudiante['estado_usuario']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <a href="edit.php?id=<?php echo $estudiante['id_estudiante']; ?>" 
                                               class="btn-action btn-blue btn-sm" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="../matriculas.php?id_estudiante=<?php echo $estudiante['id_estudiante']; ?>" 
                                               class="btn-action btn-purple btn-sm" title="Ver matrículas">
                                                <i class="bi bi-list-check"></i>
                                            </a>
                                            <a href="index.php?accion=eliminar&id=<?php echo $estudiante['id_estudiante']; ?>" 
                                               onclick="return confirm('¿Estás seguro de eliminar a <?php echo addslashes($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?>?')"
                                               class="btn-action btn-danger btn-sm" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="bi bi-people"></i>
                                        <h3>No hay estudiantes registrados</h3>
                                        <p>Comienza agregando el primer estudiante al sistema.</p>
                                        <a href="create.php" class="btn-action btn-green">
                                            <i class="bi bi-plus-circle"></i> Agregar Primer Estudiante
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Resumen -->
                <?php if ($total_estudiantes > 0): ?>
                <div class="summary-box">
                    <strong>Total de estudiantes:</strong> <?php echo $total_estudiantes; ?> |
                    <strong>Activos:</strong> <?php echo $stats['activos']; ?> |
                    <strong>Inactivos:</strong> <?php echo $stats['inactivos']; ?> |
                    <strong>Año promedio:</strong> <?php echo number_format($stats['año_promedio'], 1); ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Filtro por estado
        document.getElementById('filterEstado').addEventListener('change', function() {
            const estado = this.value;
            const rows = document.querySelectorAll('#estudiantesTable tbody tr');
            
            rows.forEach(row => {
                const estadoData = row.getAttribute('data-estado');
                if (!estado || estadoData === estado) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Filtro por carrera
        document.getElementById('filterCarrera').addEventListener('change', function() {
            const carreraId = this.value;
            const rows = document.querySelectorAll('#estudiantesTable tbody tr');
            
            rows.forEach(row => {
                const carreraData = row.getAttribute('data-carrera-id');
                if (!carreraId || carreraData === carreraId) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Filtro por año
        document.getElementById('filterAño').addEventListener('change', function() {
            const año = this.value;
            const rows = document.querySelectorAll('#estudiantesTable tbody tr');
            
            rows.forEach(row => {
                const añoData = row.getAttribute('data-año');
                if (!año || añoData === año) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Búsqueda
        document.getElementById('searchEstudiante').addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('#estudiantesTable tbody tr');
            
            rows.forEach(row => {
                const nombre = row.querySelector('td:nth-child(2) strong').textContent.toLowerCase();
                const cedula = row.querySelector('td:nth-child(2) small').textContent.toLowerCase();
                const correo = row.querySelector('td:nth-child(3) div:nth-child(1)').textContent.toLowerCase();
                
                if (nombre.includes(searchText) || cedula.includes(searchText) || correo.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Exportar a CSV
        function exportToCSV() {
            const rows = document.querySelectorAll('#estudiantesTable tr');
            const csv = [];
            
            rows.forEach(row => {
                const rowData = [];
                const cols = row.querySelectorAll('td, th');
                
                cols.forEach(col => {
                    let text = col.innerText.replace(/\n/g, ' ').trim();
                    text = text.replace(/,/g, ';');
                    rowData.push(`"${text}"`);
                });
                
                csv.push(rowData.join(','));
            });
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'estudiantes_utp_' + new Date().toISOString().slice(0,10) + '.csv';
            link.click();
        }
    </script>
</body>
</html>
<?php $conexion->close(); ?>