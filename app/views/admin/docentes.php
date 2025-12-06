<?php
// app/views/admin/docentes.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

// Manejar acciones CRUD
$accion = $_GET['accion'] ?? 'listar';
$id_docente = $_GET['id'] ?? null;

// Procesar eliminación
if ($accion === 'eliminar' && $id_docente) {
    // Verificar si el docente tiene materias asignadas
    $stmt_check = $conexion->prepare("SELECT COUNT(*) as total FROM materias WHERE id_docente = ?");
    $stmt_check->bind_param("i", $id_docente);
    $stmt_check->execute();
    $result = $stmt_check->get_result()->fetch_assoc();
    
    if ($result['total'] > 0) {
        // No eliminar, solo marcar como inactivo
        $stmt = $conexion->prepare("UPDATE docentes SET estado = 'inactivo' WHERE id_docente = ?");
        $stmt->bind_param("i", $id_docente);
        $stmt->execute();
        $mensaje = "Docente marcado como inactivo porque tiene materias asignadas.";
    } else {
        // Eliminar completamente
        $stmt = $conexion->prepare("DELETE FROM docentes WHERE id_docente = ?");
        $stmt->bind_param("i", $id_docente);
        $stmt->execute();
        $mensaje = "Docente eliminado exitosamente.";
    }
    
    // Registrar en auditoría
    $audit_stmt = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
    $accion_audit = "Eliminó docente ID: $id_docente";
    $audit_stmt->bind_param("ss", $_SESSION['user_name'], $accion_audit);
    $audit_stmt->execute();
    
    header('Location: docentes.php?mensaje=' . urlencode($mensaje));
    exit();
}

// Obtener todos los docentes con todas sus materias
$query = "SELECT d.*, 
                 COUNT(m.id_materia) as total_materias,
                 GROUP_CONCAT(CONCAT(m.nombre, ' (', c.nombre, ')') ORDER BY m.nombre SEPARATOR '; ') as materias_imparte
          FROM docentes d
          LEFT JOIN materias m ON d.id_docente = m.id_docente
          LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
          GROUP BY d.id_docente
          ORDER BY d.estado DESC, d.apellido ASC, d.nombre ASC";

$docentes = $conexion->query($query);
$total_docentes = $docentes->num_rows;

// Estadísticas
$query_stats = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
                SUM(CASE WHEN estado = 'licencia' THEN 1 ELSE 0 END) as licencia,
                AVG(años_experiencia) as experiencia_promedio
                FROM docentes";
$stats = $conexion->query($query_stats)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docentes - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/docentes.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h2>UTP Admin</h2>
                <small>Sistema de Matrícula</small>
            </div>
            
            <div class="user-info">
                <div class="avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
                <h3><?php echo $_SESSION['user_name']; ?></h3>
                <p>Administrador</p>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-item">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="estudiantes.php" class="nav-item">
                    <i class="bi bi-people"></i>
                    <span>Estudiantes</span>
                </a>
                <a href="docentes.php" class="nav-item active">
                    <i class="bi bi-person-video"></i>
                    <span>Docentes</span>
                </a>
                <a href="materias.php" class="nav-item">
                    <i class="bi bi-journal-text"></i>
                    <span>Materias</span>
                </a>
                <a href="matriculas.php" class="nav-item">
                    <i class="bi bi-pencil-square"></i>
                    <span>Matrículas</span>
                </a>
                <a href="carreras.php" class="nav-item">
                    <i class="bi bi-mortarboard"></i>
                    <span>Carreras</span>
                </a>
                <a href="periodos.php" class="nav-item">
                    <i class="bi bi-calendar-range"></i>
                    <span>Períodos</span>
                </a>
                <a href="reportes.php" class="nav-item">
                    <i class="bi bi-graph-up"></i>
                    <span>Reportes</span>
                </a>
                <a href="auditoria.php" class="nav-item">
                    <i class="bi bi-clipboard-data"></i>
                    <span>Auditoría</span>
                </a>
                
                <div class="logout">
                    <a href="../auth/logout.php" class="nav-item">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="docentes-container">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
                    <span> / </span>
                    <span>Docentes</span>
                </div>
                
                <!-- Header -->
                <div class="header-actions">
                    <h1>
                        <i class="bi bi-person-video"></i>
                        Administración de Docentes
                    </h1>
                    <div style="display: flex; gap: 10px;">
                        <a href="docentes.php?accion=nuevo" class="btn-action btn-green">
                            <i class="bi bi-plus-circle"></i> Nuevo Docente
                        </a>
                        <a href="reportes.php?tipo=docentes" class="btn-action btn-purple">
                            <i class="bi bi-printer"></i> Generar Reporte
                        </a>
                        <button onclick="exportToCSV()" class="btn-action" style="background: #38a169;">
                            <i class="bi bi-download"></i> Exportar CSV
                        </button>
                    </div>
                </div>
                
                <!-- Mensaje de éxito/error -->
                <?php if (isset($_GET['mensaje'])): ?>
                <div class="alert">
                    <span><?php echo htmlspecialchars($_GET['mensaje']); ?></span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php endif; ?>
                
                <!-- Estadísticas -->
                <div class="stats-container">
                    <div class="stat-card">
                        <i class="bi bi-people" style="color: #2a5298;"></i>
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total de Docentes</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-check-circle" style="color: #38a169;"></i>
                        <div class="stat-number"><?php echo $stats['activos']; ?></div>
                        <div class="stat-label">Docentes Activos</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-clock-history" style="color: #d69e2e;"></i>
                        <div class="stat-number"><?php echo $stats['licencia']; ?></div>
                        <div class="stat-label">En Licencia</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-award" style="color: #805ad5;"></i>
                        <div class="stat-number"><?php echo round($stats['experiencia_promedio'], 1); ?> años</div>
                        <div class="stat-label">Experiencia Promedio</div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="filters">
                    <div class="filter-group">
                        <select id="filterEstado">
                            <option value="">Todos los estados</option>
                            <option value="activo">Activos</option>
                            <option value="inactivo">Inactivos</option>
                            <option value="licencia">Licencia</option>
                        </select>
                        <select id="filterExperiencia">
                            <option value="">Toda experiencia</option>
                            <option value="0-5">0-5 años</option>
                            <option value="6-10">6-10 años</option>
                            <option value="11+">Más de 10 años</option>
                        </select>
                        <input type="text" id="searchDocente" placeholder="Buscar por nombre, cédula o especialidad..." style="flex: 1;">
                    </div>
                </div>
                
                <!-- Tabla -->
                <div class="table-container">
                    <table class="docentes-table" id="docentesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Docente</th>
                                <th>Información</th>
                                <th>Materias que Imparte</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($docentes->num_rows > 0): ?>
                                <?php while($docente = $docentes->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $docente['id_docente']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?></strong><br>
                                        <small style="color: #666;">Cédula: <?php echo htmlspecialchars($docente['cedula']); ?></small>
                                    </td>
                                    <td>
                                        <div><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($docente['correo']); ?></div>
                                        <div><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($docente['telefono']); ?></div>
                                        <div><i class="bi bi-mortarboard"></i> <?php echo htmlspecialchars($docente['titulo_academico']); ?></div>
                                        <div><i class="bi bi-star"></i> <?php echo $docente['años_experiencia']; ?> años de experiencia</div>
                                        <div><i class="bi bi-tags"></i> <?php echo htmlspecialchars($docente['especialidad']); ?></div>
                                    </td>
                                    <td>
                                        <div><strong><?php echo $docente['total_materias']; ?> materia(s)</strong></div>
                                        <?php if (!empty($docente['materias_imparte'])): ?>
                                        <div class="materias-list">
                                            <?php 
                                            $materias_array = explode('; ', $docente['materias_imparte']);
                                            foreach ($materias_array as $materia):
                                            ?>
                                                <div class="materia-item">
                                                    <i class="bi bi-book"></i>
                                                    <span><?php echo htmlspecialchars($materia); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php else: ?>
                                        <div style="color: #a0aec0; font-style: italic; margin-top: 8px;">
                                            <i class="bi bi-exclamation-circle"></i> No tiene materias asignadas
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $badge_class = '';
                                        switch($docente['estado']) {
                                            case 'activo': $badge_class = 'badge-success'; break;
                                            case 'inactivo': $badge_class = 'badge-warning'; break;
                                            case 'licencia': $badge_class = 'badge-info'; break;
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo ucfirst($docente['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <a href="docentes.php?accion=editar&id=<?php echo $docente['id_docente']; ?>" 
                                               class="btn-action btn-blue btn-sm" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="docentes.php?accion=eliminar&id=<?php echo $docente['id_docente']; ?>" 
                                               onclick="return confirm('¿Estás seguro de eliminar a <?php echo addslashes($docente['nombre'] . ' ' . $docente['apellido']); ?>?')"
                                               class="btn-action btn-danger btn-sm" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="bi bi-person-x"></i>
                                        <h3>No hay docentes registrados</h3>
                                        <p>Comienza agregando el primer docente al sistema.</p>
                                        <a href="docentes.php?accion=nuevo" class="btn-action btn-green">
                                            <i class="bi bi-plus-circle"></i> Agregar Primer Docente
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Resumen -->
                <?php if ($total_docentes > 0): ?>
                <div class="summary-box">
                    <strong>Total de docentes:</strong> <?php echo $total_docentes; ?> |
                    <strong>Activos:</strong> <?php echo $stats['activos']; ?> |
                    <strong>Inactivos:</strong> <?php echo $stats['inactivos']; ?> |
                    <strong>Licencia:</strong> <?php echo $stats['licencia']; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Filtro por estado
        document.getElementById('filterEstado').addEventListener('change', function() {
            const estado = this.value;
            const rows = document.querySelectorAll('#docentesTable tbody tr');
            
            rows.forEach(row => {
                const estadoCell = row.querySelector('td:nth-child(5) span');
                if (!estado || estadoCell.textContent.toLowerCase().includes(estado)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Filtro por experiencia
        document.getElementById('filterExperiencia').addEventListener('change', function() {
            const experiencia = this.value;
            const rows = document.querySelectorAll('#docentesTable tbody tr');
            
            rows.forEach(row => {
                const experienciaText = row.querySelector('td:nth-child(3) div:nth-child(4)').textContent;
                const años = parseInt(experienciaText.match(/\d+/)[0]);
                
                let show = true;
                if (experiencia === '0-5' && años > 5) show = false;
                if (experiencia === '6-10' && (años < 6 || años > 10)) show = false;
                if (experiencia === '11+' && años < 11) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        });
        
        // Búsqueda
        document.getElementById('searchDocente').addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('#docentesTable tbody tr');
            
            rows.forEach(row => {
                const nombre = row.querySelector('td:nth-child(2) strong').textContent.toLowerCase();
                const cedula = row.querySelector('td:nth-child(2) small').textContent.toLowerCase();
                const especialidad = row.querySelector('td:nth-child(3) div:nth-child(5)').textContent.toLowerCase();
                
                if (nombre.includes(searchText) || cedula.includes(searchText) || especialidad.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Exportar a CSV
        function exportToCSV() {
            const rows = document.querySelectorAll('#docentesTable tr');
            const csv = [];
            
            rows.forEach(row => {
                const rowData = [];
                const cols = row.querySelectorAll('td, th');
                
                cols.forEach(col => {
                    // Limpiar el texto de HTML
                    let text = col.innerText.replace(/\n/g, ' ').trim();
                    text = text.replace(/,/g, ';'); // Reemplazar comas por punto y coma
                    rowData.push(`"${text}"`);
                });
                
                csv.push(rowData.join(','));
            });
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'docentes_utp_' + new Date().toISOString().slice(0,10) + '.csv';
            link.click();
        }
    </script>
</body>
</html>
<?php $conexion->close(); ?>