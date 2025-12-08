<?php
// app/views/admin/periodos.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

// Manejar acciones CRUD
$accion = $_GET['accion'] ?? 'listar';
$id_periodo = $_GET['id'] ?? null;

// Procesar eliminación
if ($accion === 'eliminar' && $id_periodo) {
    // Verificar si el período tiene matrículas asociadas
    $stmt_check = $conexion->prepare("SELECT COUNT(*) as total FROM matriculas WHERE id_periodo = ?");
    $stmt_check->bind_param("i", $id_periodo);
    $stmt_check->execute();
    $result = $stmt_check->get_result()->fetch_assoc();
    
    if ($result['total'] > 0) {
        // No eliminar, solo marcar como inactivo
        $stmt = $conexion->prepare("UPDATE periodos_academicos SET estado = 'inactivo' WHERE id_periodo = ?");
        $stmt->bind_param("i", $id_periodo);
        $stmt->execute();
        $mensaje = "Período marcado como inactivo porque tiene matrículas asociadas.";
    } else {
        // Eliminar completamente
        $stmt = $conexion->prepare("DELETE FROM periodos_academicos WHERE id_periodo = ?");
        $stmt->bind_param("i", $id_periodo);
        $stmt->execute();
        $mensaje = "Período eliminado exitosamente.";
    }
    
    // Registrar en auditoría
    $audit_stmt = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
    $accion_audit = "Eliminó período ID: $id_periodo";
    $audit_stmt->bind_param("ss", $_SESSION['user_name'], $accion_audit);
    $audit_stmt->execute();
    
    header('Location: periodos.php?mensaje=' . urlencode($mensaje));
    exit();
}

// Procesar activación de período
if ($accion === 'activar' && $id_periodo) {
    // Desactivar todos los períodos primero
    $stmt_desactivar = $conexion->prepare("UPDATE periodos_academicos SET estado = 'inactivo' WHERE estado = 'activo'");
    $stmt_desactivar->execute();
    
    // Activar el período seleccionado
    $stmt_activar = $conexion->prepare("UPDATE periodos_academicos SET estado = 'activo' WHERE id_periodo = ?");
    $stmt_activar->bind_param("i", $id_periodo);
    $stmt_activar->execute();
    
    // Registrar en auditoría
    $audit_stmt = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
    $accion_audit = "Activó período ID: $id_periodo";
    $audit_stmt->bind_param("ss", $_SESSION['user_name'], $accion_audit);
    $audit_stmt->execute();
    
    header('Location: periodos.php?mensaje=' . urlencode('Período activado exitosamente'));
    exit();
}

// Obtener todos los períodos
$query = "SELECT *, 
                 DATEDIFF(fecha_fin, fecha_inicio) as duracion_dias,
                 YEAR(fecha_inicio) as año_inicio,
                 MONTH(fecha_inicio) as mes_inicio
          FROM periodos_academicos 
          ORDER BY año DESC, semestre DESC";

$periodos = $conexion->query($query);
$total_periodos = $periodos->num_rows;

// Estadísticas
$query_stats = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
                SUM(CASE WHEN estado = 'planificado' THEN 1 ELSE 0 END) as planificados,
                MIN(YEAR(fecha_inicio)) as año_inicio,
                MAX(YEAR(fecha_fin)) as año_fin
                FROM periodos_academicos";
$stats = $conexion->query($query_stats)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Períodos Académicos - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/periodos.css">
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
                <a href="periodos.php" class="nav-item active">
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
            <div class="periodos-container">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
                    <span> / </span>
                    <span>Períodos Académicos</span>
                </div>
                
                <!-- Header -->
                <div class="header-actions">
                    <h1>
                        <i class="bi bi-calendar-range"></i>
                        Administración de Períodos Académicos
                    </h1>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="mostrarModalNuevoPeriodo()" class="btn-action btn-green">
                            <i class="bi bi-plus-circle"></i> Nuevo Período
                        </button>
                        <a href="reportes.php?tipo=periodos" class="btn-action btn-purple">
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
                    <span><?php echo htmlspecialchars($_GET['mensaje'] ?? ''); ?></span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php endif; ?>
                
                <!-- Estadísticas -->
                <div class="stats-container">
                    <div class="stat-card">
                        <i class="bi bi-calendar" style="color: #6B2C91;"></i>
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Períodos</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-check-circle" style="color: #2d8659;"></i>
                        <div class="stat-number"><?php echo $stats['activos']; ?></div>
                        <div class="stat-label">Períodos Activos</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-clock-history" style="color: #f59e0b;"></i>
                        <div class="stat-number"><?php echo $stats['planificados']; ?></div>
                        <div class="stat-label">Planificados</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-calendar-range" style="color: #3498db;"></i>
                        <div class="stat-number"><?php echo $stats['año_fin'] - $stats['año_inicio'] + 1; ?></div>
                        <div class="stat-label">Años Cubiertos</div>
                    </div>
                </div>
                
                <!-- Tabla CORREGIDA -->
                <div class="table-container">
                    <table class="periodos-table" id="periodosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>PERÍODO</th>
                                <th>FECHAS</th>
                                <th>DURACIÓN</th>
                                <th>ESTADO</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_periodos > 0): ?>
                                <?php while($periodo = $periodos->fetch_assoc()): ?>
                                <?php 
                                $clase_estado = '';
                                switch($periodo['estado']) {
                                    case 'activo': $clase_estado = 'badge-activo'; break;
                                    case 'inactivo': $clase_estado = 'badge-inactivo'; break;
                                    case 'planificado': $clase_estado = 'badge-planificado'; break;
                                }
                                
                                $clase_timeline = '';
                                switch($periodo['estado']) {
                                    case 'activo': $clase_timeline = 'timeline-activo'; break;
                                    case 'inactivo': $clase_timeline = 'timeline-inactivo'; break;
                                    case 'planificado': $clase_timeline = 'timeline-planificado'; break;
                                }
                                
                                $hoy = new DateTime();
                                $inicio = new DateTime($periodo['fecha_inicio']);
                                $fin = new DateTime($periodo['fecha_fin']);
                                $duracion_total = $inicio->diff($fin)->days;
                                $dias_transcurridos = $hoy >= $inicio ? $inicio->diff(min($hoy, $fin))->days : 0;
                                $porcentaje = $duracion_total > 0 ? ($dias_transcurridos / $duracion_total) * 100 : 0;
                                ?>
                                <tr <?php echo $periodo['estado'] == 'activo' ? 'class="periodo-actual"' : ''; ?>>
                                    <td><strong>#<?php echo $periodo['id_periodo']; ?></strong></td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($periodo['nombre']); ?></strong>
                                            <div class="periodo-info">
                                                <div class="info-item">
                                                    <span class="info-label">AÑO</span>
                                                    <span class="info-value"><?php echo $periodo['año']; ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label">SEMESTRE</span>
                                                    <span class="badge-semestre">
                                                        <?php 
                                                        switch($periodo['semestre']) {
                                                            case 1: echo 'Primer Semestre'; break;
                                                            case 2: echo 'Segundo Semestre'; break;
                                                            case 3: echo 'Verano'; break;
                                                            default: echo 'Semestre ' . $periodo['semestre'];
                                                        }
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fecha-cell">
                                            <div class="info-item">
                                                <span class="info-label">INICIO</span>
                                                <span class="fecha-label"><?php echo date('d/m/Y', strtotime($periodo['fecha_inicio'])); ?></span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">FIN</span>
                                                <span class="fecha-label"><?php echo date('d/m/Y', strtotime($periodo['fecha_fin'])); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo $periodo['duracion_dias']; ?> días</strong>
                                            <?php if ($periodo['estado'] != 'planificado'): ?>
                                            <div class="timeline-bar">
                                                <div class="timeline-fill <?php echo $clase_timeline; ?>" 
                                                     style="width: <?php echo min($porcentaje, 100); ?>%">
                                                </div>
                                                <?php if ($hoy >= $inicio && $hoy <= $fin): ?>
                                                <div class="timeline-marker" style="left: <?php echo $porcentaje; ?>%"></div>
                                                <?php endif; ?>
                                            </div>
                                            <small style="color: #666;">
                                                <?php 
                                                if ($hoy < $inicio) {
                                                    echo 'Comienza en ' . $inicio->diff($hoy)->days . ' días';
                                                } elseif ($hoy > $fin) {
                                                    echo 'Finalizó hace ' . $fin->diff($hoy)->days . ' días';
                                                } else {
                                                    echo $dias_transcurridos . '/' . $duracion_total . ' días (' . round($porcentaje, 1) . '%)';
                                                }
                                                ?>
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $clase_estado; ?>">
                                            <?php 
                                            echo strtoupper($periodo['estado']);
                                            ?>
                                        </span>
                                        <div style="margin-top: 5px; font-size: 12px; color: #666;">
                                            <i class="bi bi-clock"></i> <?php echo date('d/m/Y H:i', strtotime($periodo['fecha_registro'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <?php if ($periodo['estado'] != 'activo'): ?>
                                            <a href="periodos.php?accion=activar&id=<?php echo $periodo['id_periodo']; ?>" 
                                               onclick="return confirm('¿Activar este período como el período activo actual?')"
                                               class="btn-action btn-green btn-sm" title="Activar">
                                                <i class="bi bi-check-circle"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <a href="periodos.php?accion=editar&id=<?php echo $periodo['id_periodo']; ?>" 
                                               class="btn-action btn-blue btn-sm" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            
                                            <a href="periodos.php?accion=eliminar&id=<?php echo $periodo['id_periodo']; ?>" 
                                               onclick="return confirm('¿Estás seguro de eliminar el período <?php echo addslashes($periodo['nombre']); ?>?')"
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
                                        <i class="bi bi-calendar-x"></i>
                                        <h3>No hay períodos registrados</h3>
                                        <p>Comienza creando el primer período académico para el sistema.</p>
                                        <button onclick="mostrarModalNuevoPeriodo()" class="btn-action btn-green">
                                            <i class="bi bi-plus-circle"></i> Crear Primer Período
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Resumen -->
                <?php if ($total_periodos > 0): ?>
                <div class="summary-box">
                    <strong>Total de períodos:</strong> <?php echo $total_periodos; ?> |
                    <strong>Activos:</strong> <?php echo $stats['activos']; ?> |
                    <strong>Inactivos:</strong> <?php echo $stats['inactivos']; ?> |
                    <strong>Planificados:</strong> <?php echo $stats['planificados']; ?> |
                    <strong>Rango de años:</strong> <?php echo $stats['año_inicio'] ?? 'N/A'; ?> - <?php echo $stats['año_fin'] ?? 'N/A'; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Modal para nuevo período -->
    <div class="modal" id="modalNuevoPeriodo">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-plus-circle"></i> Nuevo Período Académico</h3>
                <button class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            <form method="POST" action="procesar_periodo.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombre">Nombre del Período *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               placeholder="Ej: 2025-1, 2025-V, Primer Semestre 2025">
                    </div>
                    
                    <div class="form-group-row">
                        <div class="form-group">
                            <label for="año">Año *</label>
                            <select id="año" name="año" required>
                                <option value="">Seleccionar año...</option>
                                <?php
                                $año_actual = date('Y');
                                for ($i = $año_actual - 2; $i <= $año_actual + 3; $i++):
                                ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == $año_actual ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="semestre">Semestre *</label>
                            <select id="semestre" name="semestre" required>
                                <option value="">Seleccionar semestre...</option>
                                <option value="1">Primer Semestre (1)</option>
                                <option value="2">Segundo Semestre (2)</option>
                                <option value="3">Verano (3)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group-row">
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha de Inicio *</label>
                            <input type="date" id="fecha_inicio" name="fecha_inicio" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_fin">Fecha de Fin *</label>
                            <input type="date" id="fecha_fin" name="fecha_fin" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado *</label>
                        <select id="estado" name="estado" required>
                            <option value="planificado">Planificado</option>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-action" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-action btn-green">
                        <i class="bi bi-check-circle"></i> Guardar Período
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal
        function mostrarModalNuevoPeriodo() {
            // Establecer fecha mínima para hoy en fecha_inicio
            const hoy = new Date().toISOString().split('T')[0];
            document.getElementById('fecha_inicio').min = hoy;
            document.getElementById('fecha_inicio').value = hoy;
            
            // Establecer fecha_fin 4 meses después por defecto
            const fechaFin = new Date();
            fechaFin.setMonth(fechaFin.getMonth() + 4);
            document.getElementById('fecha_fin').min = hoy;
            document.getElementById('fecha_fin').value = fechaFin.toISOString().split('T')[0];
            
            document.getElementById('modalNuevoPeriodo').style.display = 'block';
        }
        
        function cerrarModal() {
            document.getElementById('modalNuevoPeriodo').style.display = 'none';
        }
        
        // Exportar a CSV
        function exportToCSV() {
            const rows = document.querySelectorAll('#periodosTable tr');
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
            link.download = 'periodos_utp_' + new Date().toISOString().slice(0,10) + '.csv';
            link.click();
        }
        
        // Validación de fechas
        document.getElementById('fecha_inicio').addEventListener('change', function() {
            const fechaFin = document.getElementById('fecha_fin');
            if (fechaFin.value && this.value > fechaFin.value) {
                fechaFin.value = this.value;
            }
            fechaFin.min = this.value;
        });
        
        document.getElementById('fecha_fin').addEventListener('change', function() {
            const fechaInicio = document.getElementById('fecha_inicio');
            if (fechaInicio.value && this.value < fechaInicio.value) {
                this.value = fechaInicio.value;
            }
        });
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalNuevoPeriodo');
            if (event.target === modal) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>
<?php $conexion->close(); ?>