<?php
// app/views/admin/auditoria.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

// Obtener parámetros para filtros
$usuario_filtro = $_GET['usuario'] ?? '';
$accion_filtro = $_GET['accion'] ?? '';
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

// Construir consulta base
$query = "SELECT * FROM auditoria WHERE 1=1";
$params = [];
$types = '';

if ($usuario_filtro) {
    $query .= " AND usuario LIKE ?";
    $params[] = "%$usuario_filtro%";
    $types .= 's';
}

if ($accion_filtro) {
    $query .= " AND accion LIKE ?";
    $params[] = "%$accion_filtro%";
    $types .= 's';
}

if ($fecha_desde) {
    $query .= " AND DATE(fecha) >= ?";
    $params[] = $fecha_desde;
    $types .= 's';
}

if ($fecha_hasta) {
    $query .= " AND DATE(fecha) <= ?";
    $params[] = $fecha_hasta;
    $types .= 's';
}

$query .= " ORDER BY fecha DESC";

// Preparar y ejecutar consulta
if ($params) {
    $stmt = $conexion->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $auditoria = $stmt->get_result();
} else {
    $auditoria = $conexion->query($query);
}

$total_registros = $auditoria->num_rows;

// Obtener usuarios únicos para filtro
$usuarios = $conexion->query("SELECT DISTINCT usuario FROM auditoria WHERE usuario IS NOT NULL ORDER BY usuario");

// Obtener acciones únicas para filtro
$acciones = $conexion->query("SELECT DISTINCT accion FROM auditoria WHERE accion IS NOT NULL ORDER BY accion LIMIT 50");

// Estadísticas
$query_stats = "SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT usuario) as usuarios_unicos,
                MIN(fecha) as primera_accion,
                MAX(fecha) as ultima_accion,
                SUM(CASE WHEN accion LIKE '%elimin%' THEN 1 ELSE 0 END) as eliminaciones,
                SUM(CASE WHEN accion LIKE '%cre%' OR accion LIKE '%insert%' THEN 1 ELSE 0 END) as creaciones,
                SUM(CASE WHEN accion LIKE '%edit%' OR accion LIKE '%actualiz%' THEN 1 ELSE 0 END) as actualizaciones
                FROM auditoria
                WHERE 1=1";
                
if ($fecha_desde) {
    $query_stats .= " AND DATE(fecha) >= '$fecha_desde'";
}
if ($fecha_hasta) {
    $query_stats .= " AND DATE(fecha) <= '$fecha_hasta'";
}
if ($usuario_filtro) {
    $query_stats .= " AND usuario LIKE '%$usuario_filtro%'";
}
if ($accion_filtro) {
    $query_stats .= " AND accion LIKE '%$accion_filtro%'";
}

$stats = $conexion->query($query_stats)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/auditorias.css">
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
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? '', 0, 1)); ?>
                </div>
                <h3><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></h3>
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
                <a href="docentes.php" class="nav-item">
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
                <a href="auditoria.php" class="nav-item active">
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
            <div class="auditoria-container">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
                    <span> / </span>
                    <span>Auditoría del Sistema</span>
                </div>
                
                <!-- Header -->
                <div class="header-actions">
                    <h1>
                        <i class="bi bi-clipboard-data"></i>
                        Registro de Auditoría
                    </h1>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="exportarCSV()" class="btn-action btn-green">
                            <i class="bi bi-download"></i> Exportar CSV
                        </button>
                        <button onclick="limpiarFiltros()" class="btn-action">
                            <i class="bi bi-x-circle"></i> Limpiar Filtros
                        </button>
                        <button onclick="window.print()" class="btn-action btn-blue">
                            <i class="bi bi-printer"></i> Imprimir
                        </button>
                    </div>
                </div>
                
                <!-- Estadísticas -->
                <div class="stats-container">
                    <div class="stat-card">
                        <i class="bi bi-list-ol" style="color: #6B2C91;"></i>
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total de Acciones</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-people" style="color: #2d8659;"></i>
                        <div class="stat-number"><?php echo $stats['usuarios_unicos']; ?></div>
                        <div class="stat-label">Usuarios Activos</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-trash" style="color: #e53e3e;"></i>
                        <div class="stat-number"><?php echo $stats['eliminaciones']; ?></div>
                        <div class="stat-label">Eliminaciones</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-plus-circle" style="color: #38a169;"></i>
                        <div class="stat-number"><?php echo $stats['creaciones']; ?></div>
                        <div class="stat-label">Creaciones</div>
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="filters-card">
                    <h3><i class="bi bi-funnel"></i> Filtros de Auditoría</h3>
                    <form method="GET" class="filter-form" id="filterForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="bi bi-person"></i> Usuario</label>
                                <select name="usuario">
                                    <option value="">Todos los usuarios</option>
                                    <?php while($usuario = $usuarios->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($usuario['usuario']); ?>" 
                                        <?php echo $usuario_filtro == $usuario['usuario'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($usuario['usuario']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="bi bi-gear"></i> Tipo de Acción</label>
                                <select name="accion">
                                    <option value="">Todas las acciones</option>
                                    <?php while($accion = $acciones->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($accion['accion']); ?>" 
                                        <?php echo $accion_filtro == $accion['accion'] ? 'selected' : ''; ?>>
                                        <?php 
                                        $accion_text = $accion['accion'];
                                        if (strlen($accion_text) > 50) {
                                            $accion_text = substr($accion_text, 0, 50) . '...';
                                        }
                                        echo htmlspecialchars($accion_text); 
                                        ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="bi bi-calendar"></i> Fecha Desde</label>
                                <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="bi bi-calendar"></i> Fecha Hasta</label>
                                <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>">
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-action btn-purple">
                                <i class="bi bi-search"></i> Aplicar Filtros
                            </button>
                            <button type="button" onclick="document.getElementById('filterForm').reset(); document.getElementById('filterForm').submit();" 
                                    class="btn-action">
                                <i class="bi bi-x-circle"></i> Limpiar
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Tabla de Auditoría -->
                <div class="table-container">
                    <table class="auditoria-table" id="auditoriaTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha y Hora</th>
                                <th>Usuario</th>
                                <th>Acción Realizada</th>
                                <th>IP / Origen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_registros > 0): ?>
                                <?php while($registro = $auditoria->fetch_assoc()): 
                                    $tipo_accion = '';
                                    $badge_class = 'badge-info';
                                    
                                    if (stripos($registro['accion'], 'elimin') !== false) {
                                        $tipo_accion = 'Eliminación';
                                        $badge_class = 'badge-danger';
                                    } elseif (stripos($registro['accion'], 'cre') !== false || stripos($registro['accion'], 'insert') !== false) {
                                        $tipo_accion = 'Creación';
                                        $badge_class = 'badge-success';
                                    } elseif (stripos($registro['accion'], 'edit') !== false || stripos($registro['accion'], 'actualiz') !== false) {
                                        $tipo_accion = 'Actualización';
                                        $badge_class = 'badge-warning';
                                    } elseif (stripos($registro['accion'], 'activ') !== false) {
                                        $tipo_accion = 'Activación';
                                        $badge_class = 'badge-primary';
                                    } else {
                                        $tipo_accion = 'Consulta';
                                    }
                                ?>
                                <tr>
                                    <td><strong>#<?php echo $registro['id_auditoria']; ?></strong></td>
                                    <td>
                                        <div class="fecha-cell">
                                            <span class="fecha"><?php echo date('d/m/Y', strtotime($registro['fecha'])); ?></span>
                                            <span class="hora"><?php echo date('H:i:s', strtotime($registro['fecha'])); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="usuario-cell">
                                            <div class="usuario-avatar">
                                                <?php echo strtoupper(substr($registro['usuario'] ?? 'U', 0, 1)); ?>
                                            </div>
                                            <div class="usuario-info">
                                                <strong><?php echo htmlspecialchars($registro['usuario'] ?? 'Usuario Desconocido'); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="accion-cell">
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $tipo_accion; ?></span>
                                            <div class="accion-descripcion">
                                                <?php echo htmlspecialchars($registro['accion']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="origen-cell">
                                            <i class="bi bi-laptop"></i>
                                            <small>Sistema Administrativo</small>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="bi bi-clipboard-x"></i>
                                        <h3>No hay registros de auditoría</h3>
                                        <p>No se encontraron acciones registradas con los filtros aplicados.</p>
                                        <button onclick="limpiarFiltros()" class="btn-action btn-purple">
                                            <i class="bi bi-arrow-clockwise"></i> Ver todos los registros
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación y Resumen -->
                <?php if ($total_registros > 0): ?>
                <div class="summary-box">
                    <div class="summary-info">
                        <strong>Mostrando:</strong> <?php echo $total_registros; ?> registro(s) |
                        <strong>Período:</strong> <?php echo date('d/m/Y', strtotime($fecha_desde)); ?> - <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?> |
                        <?php if ($usuario_filtro): ?>
                        <strong>Usuario:</strong> <?php echo htmlspecialchars($usuario_filtro); ?> |
                        <?php endif; ?>
                        <?php if ($accion_filtro): ?>
                        <strong>Acción:</strong> <?php echo htmlspecialchars($accion_filtro); ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="summary-stats">
                        <span class="stat-item">
                            <i class="bi bi-clock-history"></i>
                            <span>Última acción: <?php echo $stats['ultima_accion'] ? date('d/m/Y H:i', strtotime($stats['ultima_accion'])) : 'N/A'; ?></span>
                        </span>
                        <span class="stat-item">
                            <i class="bi bi-calendar-plus"></i>
                            <span>Primera acción: <?php echo $stats['primera_accion'] ? date('d/m/Y', strtotime($stats['primera_accion'])) : 'N/A'; ?></span>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Exportar a CSV
        function exportarCSV() {
            const rows = document.querySelectorAll('#auditoriaTable tr');
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
            link.download = 'auditoria_utp_' + new Date().toISOString().slice(0,10) + '.csv';
            link.click();
        }
        
        // Limpiar filtros
        function limpiarFiltros() {
            window.location.href = 'auditoria.php';
        }
        
        // Validación de fechas
        document.querySelector('input[name="fecha_desde"]').addEventListener('change', function() {
            const fechaHasta = document.querySelector('input[name="fecha_hasta"]');
            if (fechaHasta.value && this.value > fechaHasta.value) {
                fechaHasta.value = this.value;
            }
            fechaHasta.min = this.value;
        });
        
        document.querySelector('input[name="fecha_hasta"]').addEventListener('change', function() {
            const fechaDesde = document.querySelector('input[name="fecha_desde"]');
            if (fechaDesde.value && this.value < fechaDesde.value) {
                this.value = fechaDesde.value;
            }
        });
        
        // Resaltar búsqueda
        function resaltarTexto(texto, busqueda) {
            if (!busqueda) return texto;
            
            const regex = new RegExp(`(${busqueda})`, 'gi');
            return texto.replace(regex, '<mark>$1</mark>');
        }
        
        // Aplicar resaltado si hay búsqueda
        const urlParams = new URLSearchParams(window.location.search);
        const busqueda = urlParams.get('accion') || urlParams.get('usuario');
        
        if (busqueda) {
            document.querySelectorAll('.accion-descripcion').forEach(el => {
                el.innerHTML = resaltarTexto(el.textContent, busqueda);
            });
        }
        
        // Estilos para impresión
        const style = document.createElement('style');
        style.innerHTML = `
            @media print {
                .sidebar, .breadcrumb, .filters-card, .header-actions > div:last-child, 
                .btn-action, .summary-stats, .usuario-avatar, .badge {
                    display: none !important;
                }
                
                .dashboard-container {
                    display: block !important;
                }
                
                .main-content {
                    padding: 0 !important;
                    margin: 0 !important;
                }
                
                .table-container {
                    box-shadow: none !important;
                    border: none !important;
                }
                
                .auditoria-table {
                    font-size: 10px !important;
                }
                
                .stats-container {
                    display: none !important;
                }
                
                .summary-info {
                    font-size: 10px !important;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
<?php $conexion->close(); ?>