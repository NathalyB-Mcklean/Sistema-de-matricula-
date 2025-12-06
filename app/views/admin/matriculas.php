<?php
// app/views/admin/matriculas.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

// Manejar acciones CRUD
$accion = $_GET['accion'] ?? 'listar';
$id_matricula = $_GET['id'] ?? null;
$id_estudiante = $_GET['id_estudiante'] ?? null;

// Procesar eliminación
if ($accion === 'eliminar' && $id_matricula) {
    $stmt = $conexion->prepare("DELETE FROM matriculas WHERE id_matricula = ?");
    $stmt->bind_param("i", $id_matricula);
    $stmt->execute();
    
    // Registrar en auditoría
    $audit_stmt = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
    $accion_audit = "Eliminó matrícula ID: $id_matricula";
    $audit_stmt->bind_param("ss", $_SESSION['user_name'], $accion_audit);
    $audit_stmt->execute();
    
    $mensaje = "Matrícula eliminada exitosamente.";
    header('Location: matriculas.php?mensaje=' . urlencode($mensaje) . ($id_estudiante ? '&id_estudiante=' . $id_estudiante : ''));
    exit();
}

// Obtener filtros - USANDO OPERADOR DE FUSIÓN NULL
$filtro_periodo = $_GET['periodo'] ?? '';
$filtro_carrera = $_GET['carrera'] ?? '';
$filtro_estudiante = $_GET['estudiante'] ?? '';
$filtro_materia = $_GET['materia'] ?? '';

// Construir consulta base con información completa de grupos y horarios
$query = "SELECT 
            mt.id_matricula,
            mt.fecha,
            e.id_estudiante,
            CONCAT(u.nombre, ' ', u.apellido) as estudiante_nombre,
            e.cedula,
            c.id_carrera,
            c.nombre as carrera_nombre,
            c.codigo as carrera_codigo,
            m.id_materia,
            m.nombre as materia_nombre,
            m.codigo as materia_codigo,
            m.costo,
            CONCAT(d.nombre, ' ', d.apellido) as docente_nombre,
            ghm.id_ghm,
            ghm.aula,
            h.dia,
            TIME_FORMAT(h.hora_inicio, '%H:%i') as hora_inicio,
            TIME_FORMAT(h.hora_fin, '%H:%i') as hora_fin,
            p.id_periodo,
            p.nombre as periodo_nombre,
            p.año as periodo_año,
            p.semestre as periodo_semestre
          FROM matriculas mt
          JOIN estudiantes e ON mt.id_estudiante = e.id_estudiante
          JOIN usuario u ON e.id_usuario = u.id_usuario
          JOIN grupos_horarios_materia ghm ON mt.id_ghm = ghm.id_ghm
          JOIN materias m ON ghm.id_materia = m.id_materia
          JOIN carreras c ON m.id_carrera = c.id_carrera
          JOIN docentes d ON m.id_docente = d.id_docente
          JOIN horarios h ON ghm.id_horario = h.id_horario
          JOIN periodos_academicos p ON mt.id_periodo = p.id_periodo
          WHERE 1=1";

// Aplicar filtros
$params = [];
$types = '';

if ($id_estudiante) {
    $query .= " AND e.id_estudiante = ?";
    $params[] = $id_estudiante;
    $types .= 'i';
}

if ($filtro_periodo) {
    $query .= " AND p.id_periodo = ?";
    $params[] = $filtro_periodo;
    $types .= 'i';
}

if ($filtro_carrera) {
    $query .= " AND c.id_carrera = ?";
    $params[] = $filtro_carrera;
    $types .= 'i';
}

if ($filtro_materia) {
    $query .= " AND m.nombre LIKE ?";
    $params[] = "%$filtro_materia%";
    $types .= 's';
}

if ($filtro_estudiante) {
    $query .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR e.cedula LIKE ?)";
    $params[] = "%$filtro_estudiante%";
    $params[] = "%$filtro_estudiante%";
    $params[] = "%$filtro_estudiante%";
    $types .= 'sss';
}

$query .= " ORDER BY mt.fecha DESC, h.dia, h.hora_inicio";

// Preparar y ejecutar consulta
if ($params) {
    $stmt = $conexion->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $matriculas = $stmt->get_result();
} else {
    $matriculas = $conexion->query($query);
}

$total_matriculas = $matriculas->num_rows;

// Obtener datos para filtros
$periodos = $conexion->query("SELECT id_periodo, nombre, año, semestre FROM periodos_academicos ORDER BY año DESC, semestre DESC");
$carreras = $conexion->query("SELECT id_carrera, nombre, codigo FROM carreras WHERE estado = 'activa' ORDER BY nombre");

// Si hay un estudiante específico, obtener sus datos
$estudiante_info = null;
if ($id_estudiante) {
    $stmt_est = $conexion->prepare("SELECT e.*, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo 
                                    FROM estudiantes e 
                                    JOIN usuario u ON e.id_usuario = u.id_usuario 
                                    WHERE e.id_estudiante = ?");
    $stmt_est->bind_param("i", $id_estudiante);
    $stmt_est->execute();
    $estudiante_info = $stmt_est->get_result()->fetch_assoc();
}

// Estadísticas
$query_stats = "SELECT 
                COUNT(DISTINCT mt.id_estudiante) as estudiantes_matriculados,
                COUNT(DISTINCT m.id_carrera) as carreras_con_matriculas,
                COUNT(DISTINCT m.id_materia) as materias_matriculadas,
                COALESCE(SUM(m.costo), 0) as ingresos_totales
                FROM matriculas mt
                JOIN grupos_horarios_materia ghm ON mt.id_ghm = ghm.id_ghm
                JOIN materias m ON ghm.id_materia = m.id_materia";
$stats = $conexion->query($query_stats)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matrículas - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/matriculas.css">
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
                <a href="matriculas.php" class="nav-item active">
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
            <div class="matriculas-container">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
                    <span> / </span>
                    <?php if ($estudiante_info): ?>
                    <a href="estudiantes.php">Estudiantes</a>
                    <span> / </span>
                    <span><?php echo htmlspecialchars($estudiante_info['nombre_completo'] ?? ''); ?></span>
                    <?php else: ?>
                    <span>Matrículas</span>
                    <?php endif; ?>
                </div>
                
                <!-- Header -->
                <div class="header-actions">
                    <h1>
                        <i class="bi bi-pencil-square"></i>
                        <?php if ($estudiante_info): ?>
                        Matrículas de <?php echo htmlspecialchars($estudiante_info['nombre_completo'] ?? ''); ?>
                        <?php else: ?>
                        Administración de Matrículas
                        <?php endif; ?>
                    </h1>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="mostrarModalNuevaMatricula()" class="btn-action btn-green">
                            <i class="bi bi-plus-circle"></i> Nueva Matrícula
                        </button>
                        <a href="reportes.php?tipo=matriculas" class="btn-action btn-purple">
                            <i class="bi bi-printer"></i> Generar Reporte
                        </a>
                        <button onclick="exportToCSV()" class="btn-action" style="background: #38a169;">
                            <i class="bi bi-download"></i> Exportar CSV
                        </button>
                        <?php if ($estudiante_info): ?>
                        <a href="matriculas.php" class="btn-action btn-blue">
                            <i class="bi bi-list"></i> Ver Todas
                        </a>
                        <?php endif; ?>
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
                        <i class="bi bi-file-text" style="color: #2a5298;"></i>
                        <div class="stat-number"><?php echo $total_matriculas; ?></div>
                        <div class="stat-label">Total Matrículas</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-people" style="color: #38a169;"></i>
                        <div class="stat-number"><?php echo $stats['estudiantes_matriculados'] ?? 0; ?></div>
                        <div class="stat-label">Estudiantes Matriculados</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-mortarboard" style="color: #d69e2e;"></i>
                        <div class="stat-number"><?php echo $stats['carreras_con_matriculas'] ?? 0; ?></div>
                        <div class="stat-label">Carreras Activas</div>
                    </div>
                    
                    <div class="stat-card">
                        <i class="bi bi-cash" style="color: #805ad5;"></i>
                        <div class="stat-number">$<?php echo number_format($stats['ingresos_totales'] ?? 0, 2); ?></div>
                        <div class="stat-label">Ingresos Totales</div>
                    </div>
                </div>
                
                <!-- Filtros - CORREGIDO: usando operador de fusión null ?? -->
                <div class="filters">
                    <form method="GET" class="filter-form" id="filterForm">
                        <input type="hidden" name="id_estudiante" value="<?php echo htmlspecialchars($id_estudiante ?? ''); ?>">
                        
                        <select name="periodo" onchange="this.form.submit()">
                            <option value="">Todos los períodos</option>
                            <?php while($periodo = $periodos->fetch_assoc()): ?>
                            <option value="<?php echo $periodo['id_periodo']; ?>" 
                                <?php echo ($filtro_periodo ?? '') == $periodo['id_periodo'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($periodo['nombre'] . ' (' . $periodo['año'] . '-' . $periodo['semestre'] . ')'); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <select name="carrera" onchange="this.form.submit()">
                            <option value="">Todas las carreras</option>
                            <?php 
                            $carreras->data_seek(0);
                            while($carrera = $carreras->fetch_assoc()): 
                            ?>
                            <option value="<?php echo $carrera['id_carrera']; ?>" 
                                <?php echo ($filtro_carrera ?? '') == $carrera['id_carrera'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($carrera['codigo'] . ' - ' . $carrera['nombre']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <?php if (!$id_estudiante): ?>
                        <input type="text" name="estudiante" placeholder="Buscar estudiante..." 
                               value="<?php echo htmlspecialchars($filtro_estudiante ?? ''); ?>" 
                               onchange="this.form.submit()">
                        <?php endif; ?>
                        
                        <input type="text" name="materia" placeholder="Buscar materia..." 
                               value="<?php echo htmlspecialchars($filtro_materia ?? ''); ?>" 
                               onchange="this.form.submit()">
                        
                        <button type="button" onclick="document.getElementById('filterForm').reset(); document.getElementById('filterForm').submit();">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </button>
                    </form>
                </div>
                
                <!-- Tabla -->
                <div class="table-container">
                    <table class="matriculas-table" id="matriculasTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Estudiante</th>
                                <th>Materia y Carrera</th>
                                <th>Grupo - Horario</th>
                                <th>Docente</th>
                                <th>Período</th>
                                <th>Costo</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_matriculas > 0): ?>
                                <?php while($matricula = $matriculas->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $matricula['id_matricula']; ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($matricula['estudiante_nombre']); ?></strong><br>
                                        <small style="color: #666;"><?php echo htmlspecialchars($matricula['cedula']); ?></small>
                                    </td>
                                    <td>
                                        <div><strong><?php echo htmlspecialchars($matricula['materia_codigo'] . ' - ' . $matricula['materia_nombre']); ?></strong></div>
                                        <div><small><?php echo htmlspecialchars($matricula['carrera_codigo'] . ' - ' . $matricula['carrera_nombre']); ?></small></div>
                                    </td>
                                    <td>
                                        <div class="horario-cell">
                                            <div class="horario-item">
                                                <i class="bi bi-geo-alt"></i>
                                                <span class="badge-aula">Aula <?php echo htmlspecialchars($matricula['aula']); ?></span>
                                            </div>
                                            <div class="horario-item">
                                                <i class="bi bi-calendar-week"></i>
                                                <span class="dia-semana"><?php echo htmlspecialchars($matricula['dia']); ?></span>
                                                <span class="badge-horario"><?php echo $matricula['hora_inicio'] . ' - ' . $matricula['hora_fin']; ?></span>
                                            </div>
                                            <div class="horario-item">
                                                <i class="bi bi-layers"></i>
                                                <small>Grupo ID: <?php echo $matricula['id_ghm']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($matricula['docente_nombre']); ?></div>
                                    </td>
                                    <td>
                                        <div><strong><?php echo htmlspecialchars($matricula['periodo_nombre']); ?></strong></div>
                                        <div><small><?php echo $matricula['periodo_año'] . '-' . $matricula['periodo_semestre']; ?></small></div>
                                    </td>
                                    <td>
                                        <strong>$<?php echo number_format($matricula['costo'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($matricula['fecha'])); ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <a href="matriculas.php?accion=eliminar&id=<?php echo $matricula['id_matricula']; ?><?php echo $id_estudiante ? '&id_estudiante=' . $id_estudiante : ''; ?>" 
                                               onclick="return confirm('¿Estás seguro de eliminar esta matrícula?')"
                                               class="btn-action btn-danger btn-sm" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="bi bi-file-x"></i>
                                        <h3>No hay matrículas registradas</h3>
                                        <p><?php if ($filtro_periodo || $filtro_carrera || $filtro_estudiante || $filtro_materia): ?>
                                            No hay matrículas que coincidan con los filtros aplicados.
                                        <?php else: ?>
                                            Comienza creando una nueva matrícula para el sistema.
                                        <?php endif; ?></p>
                                        <button onclick="mostrarModalNuevaMatricula()" class="btn-action btn-green">
                                            <i class="bi bi-plus-circle"></i> Crear Primera Matrícula
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Resumen -->
                <?php if ($total_matriculas > 0): ?>
                <div class="summary-box">
                    <strong>Total de matrículas:</strong> <?php echo $total_matriculas; ?> |
                    <?php if ($estudiante_info): ?>
                    <strong>Estudiante:</strong> <?php echo htmlspecialchars($estudiante_info['nombre_completo'] ?? ''); ?> |
                    <?php endif; ?>
                    <?php if ($filtro_periodo): ?>
                    <strong>Período filtrado</strong> |
                    <?php endif; ?>
                    <strong>Mostrando:</strong> <?php echo $total_matriculas; ?> registro(s)
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Modal para nueva matrícula -->
    <div class="modal" id="modalNuevaMatricula">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="bi bi-plus-circle"></i> Nueva Matrícula</h3>
                <button class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            <form method="POST" action="procesar_matricula.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="estudiante">Estudiante *</label>
                        <select id="estudiante" name="estudiante" required onchange="cargarMateriasDisponibles()">
                            <option value="">Seleccionar estudiante...</option>
                            <?php
                            $estudiantes_activos = $conexion->query("
                                SELECT e.id_estudiante, CONCAT(u.nombre, ' ', u.apellido) as nombre, e.cedula, c.nombre as carrera
                                FROM estudiantes e
                                JOIN usuario u ON e.id_usuario = u.id_usuario
                                LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
                                WHERE u.estado = 'activo'
                                ORDER BY u.apellido, u.nombre
                            ");
                            while($est = $estudiantes_activos->fetch_assoc()):
                            ?>
                            <option value="<?php echo $est['id_estudiante']; ?>">
                                <?php echo htmlspecialchars($est['nombre'] . ' - ' . $est['cedula'] . ' (' . ($est['carrera'] ?? 'Sin carrera') . ')'); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="periodo">Período Académico *</label>
                        <select id="periodo" name="periodo" required onchange="cargarMateriasDisponibles()">
                            <option value="">Seleccionar período...</option>
                            <?php
                            $periodos_activos = $conexion->query("SELECT * FROM periodos_academicos WHERE estado = 'activo' ORDER BY año DESC, semestre DESC");
                            while($periodo = $periodos_activos->fetch_assoc()):
                            ?>
                            <option value="<?php echo $periodo['id_periodo']; ?>">
                                <?php echo htmlspecialchars($periodo['nombre'] . ' (' . $periodo['año'] . '-' . $periodo['semestre'] . ')'); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div id="grupos-container" style="display: none;">
                        <h4 style="margin-bottom: 15px; color: #1e3c72;">Seleccionar Grupo Disponible</h4>
                        <div id="grupos-list">
                            <!-- Los grupos se cargarán aquí dinámicamente -->
                        </div>
                        <input type="hidden" id="grupo_seleccionado" name="grupo" value="">
                    </div>
                    
                    <div id="sin-grupos" style="display: none; text-align: center; padding: 20px; color: #718096;">
                        <i class="bi bi-exclamation-circle" style="font-size: 3rem;"></i>
                        <p>No hay grupos disponibles para el estudiante seleccionado en este período.</p>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-action" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="btn-action btn-green" id="btnGuardar" disabled>
                        <i class="bi bi-check-circle"></i> Guardar Matrícula
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal
        function mostrarModalNuevaMatricula() {
            document.getElementById('modalNuevaMatricula').style.display = 'block';
        }
        
        function cerrarModal() {
            document.getElementById('modalNuevaMatricula').style.display = 'none';
            document.getElementById('grupos-container').style.display = 'none';
            document.getElementById('sin-grupos').style.display = 'none';
            document.getElementById('btnGuardar').disabled = true;
            document.getElementById('grupo_seleccionado').value = '';
        }
        
        // Cargar materias disponibles para el estudiante
        function cargarMateriasDisponibles() {
            const estudianteId = document.getElementById('estudiante').value;
            const periodoId = document.getElementById('periodo').value;
            const gruposList = document.getElementById('grupos-list');
            const gruposContainer = document.getElementById('grupos-container');
            const sinGrupos = document.getElementById('sin-grupos');
            const btnGuardar = document.getElementById('btnGuardar');
            
            if (!estudianteId || !periodoId) {
                gruposContainer.style.display = 'none';
                sinGrupos.style.display = 'none';
                return;
            }
            
            // Mostrar cargando
            gruposList.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="bi bi-hourglass"></i> Cargando grupos disponibles...</div>';
            gruposContainer.style.display = 'block';
            sinGrupos.style.display = 'none';
            
            // Hacer petición AJAX
            fetch(`obtener_grupos_disponibles.php?estudiante=${estudianteId}&periodo=${periodoId}`)
                .then(response => response.json())
                .then(data => {
                    gruposList.innerHTML = '';
                    
                    if (data.error) {
                        gruposList.innerHTML = `<div class="alert" style="background: #fed7d7; color: #742a2a;">${data.error}</div>`;
                        sinGrupos.style.display = 'block';
                        gruposContainer.style.display = 'none';
                        return;
                    }
                    
                    if (data.length === 0) {
                        sinGrupos.style.display = 'block';
                        gruposContainer.style.display = 'none';
                        return;
                    }
                    
                    data.forEach(grupo => {
                        const grupoDiv = document.createElement('div');
                        grupoDiv.className = 'grupo-option';
                        grupoDiv.innerHTML = `
                            <div class="grupo-header">
                                <div class="grupo-codigo">${grupo.materia_codigo} - ${grupo.materia_nombre}</div>
                                <div><strong>$${grupo.costo.toFixed(2)}</strong></div>
                            </div>
                            <div class="grupo-details">
                                <div><i class="bi bi-person"></i> ${grupo.docente_nombre}</div>
                                <div><i class="bi bi-geo-alt"></i> Aula ${grupo.aula}</div>
                                <div><i class="bi bi-calendar-week"></i> ${grupo.dia} ${grupo.hora_inicio} - ${grupo.hora_fin}</div>
                                <div><i class="bi bi-layers"></i> Grupo ${grupo.id_ghm}</div>
                            </div>
                        `;
                        
                        grupoDiv.onclick = () => {
                            // Deseleccionar todos
                            document.querySelectorAll('.grupo-option').forEach(g => g.classList.remove('selected'));
                            // Seleccionar este
                            grupoDiv.classList.add('selected');
                            document.getElementById('grupo_seleccionado').value = grupo.id_ghm;
                            btnGuardar.disabled = false;
                        };
                        
                        gruposList.appendChild(grupoDiv);
                    });
                })
                .catch(error => {
                    gruposList.innerHTML = `<div class="alert" style="background: #fed7d7; color: #742a2a;">Error al cargar grupos: ${error.message}</div>`;
                });
        }
        
        // Exportar a CSV
        function exportToCSV() {
            const rows = document.querySelectorAll('#matriculasTable tr');
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
            link.download = 'matriculas_utp_' + new Date().toISOString().slice(0,10) + '.csv';
            link.click();
        }
        
        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalNuevaMatricula');
            if (event.target === modal) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>
<?php $conexion->close(); ?>