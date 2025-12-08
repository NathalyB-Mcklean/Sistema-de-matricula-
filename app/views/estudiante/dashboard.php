<?php
// app/views/estudiante/dashboard.php

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'estudiante') {
    header('Location: ../../auth/login.php');
    exit();
}

// Incluir conexión a base de datos
require_once '../../config/conexion.php';

// Variables para los parciales
$titulo_pagina = 'Dashboard Estudiante - Sistema UTP';
$pagina_activa = 'dashboard';

// Verificar que el usuario tenga id_estudiante en sesión
if (!isset($_SESSION['id_estudiante'])) {
    // Si no tiene id_estudiante, obtenerlo de la tabla estudiantes
    $id_usuario = $_SESSION['user_id'];
    
    $query_get_estudiante_id = "SELECT id_estudiante FROM estudiantes WHERE id_usuario = ?";
    $stmt = $conexion->prepare($query_get_estudiante_id);
    $stmt->bind_param('i', $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $estudiante_data = $result->fetch_assoc();
    
    if (!$estudiante_data) {
        echo '<main class="main-content"><div class="empty-state">
                <i class="bi bi-exclamation-triangle"></i>
                <h3>Error: No se encontró información del estudiante</h3>
                <p>Contacta con administración para configurar tu perfil de estudiante.</p>
                <a href="../../auth/logout.php" class="btn-action">Cerrar sesión</a>
              </div></main>';
        exit();
    }
    
    $id_estudiante = $estudiante_data['id_estudiante'];
    $_SESSION['id_estudiante'] = $id_estudiante;
} else {
    $id_estudiante = $_SESSION['id_estudiante'];
}

// Obtener información completa del estudiante para el dashboard
$query_estudiante = "SELECT e.*, u.nombre, u.apellido, u.correo, 
                     c.nombre as carrera_nombre, c.codigo as carrera_codigo,
                     c.id_carrera
                     FROM estudiantes e
                     JOIN usuario u ON e.id_usuario = u.id_usuario
                     LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
                     WHERE e.id_estudiante = ?";
$stmt = $conexion->prepare($query_estudiante);
$stmt->bind_param('i', $id_estudiante);
$stmt->execute();
$result = $stmt->get_result();
$estudiante = $result->fetch_assoc();

if (!$estudiante) {
    echo '<main class="main-content"><div class="empty-state">
            <i class="bi bi-exclamation-triangle"></i>
            <h3>Error: Perfil de estudiante incompleto</h3>
            <p>No se pudo cargar la información del estudiante.</p>
            <a href="../../auth/logout.php" class="btn-action">Cerrar sesión</a>
          </div></main>';
    exit();
}

// Obtener período activo
$query_periodo = "SELECT * FROM periodos_academicos WHERE estado = 'activo' LIMIT 1";
$periodo_result = $conexion->query($query_periodo);
$periodo = $periodo_result ? $periodo_result->fetch_assoc() : null;

// Obtener matrículas del estudiante en el período activo
$matriculas = [];
if ($periodo && isset($periodo['id_periodo'])) {
    $query_matriculas = "SELECT m.nombre as materia_nombre, m.codigo as materia_codigo, 
                         ghm.aula, h.dia, h.hora_inicio, h.hora_fin,
                         CONCAT(d.nombre, ' ', d.apellido) as docente_nombre
                         FROM matriculas mat
                         JOIN grupos_horarios_materia ghm ON mat.id_ghm = ghm.id_ghm
                         JOIN materias m ON ghm.id_materia = m.id_materia
                         JOIN horarios h ON ghm.id_horario = h.id_horario
                         LEFT JOIN docentes d ON m.id_docente = d.id_docente
                         WHERE mat.id_estudiante = ? AND mat.id_periodo = ?
                         ORDER BY CASE h.dia 
                             WHEN 'Lunes' THEN 1 
                             WHEN 'Martes' THEN 2 
                             WHEN 'Miércoles' THEN 3 
                             WHEN 'Jueves' THEN 4 
                             WHEN 'Viernes' THEN 5 
                             WHEN 'Sábado' THEN 6 
                             ELSE 7 
                         END, h.hora_inicio";
    $stmt = $conexion->prepare($query_matriculas);
    $periodo_id = $periodo['id_periodo'];
    $stmt->bind_param('is', $id_estudiante, $periodo_id);
    $stmt->execute();
    $matriculas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Verificar si ya respondió la encuesta
$encuesta_respondida = false;
$query_encuesta = "SELECT * FROM encuestas WHERE id_estudiante = ?";
$stmt = $conexion->prepare($query_encuesta);
$stmt->bind_param('i', $id_estudiante);
$stmt->execute();
$encuesta_info = $stmt->get_result()->fetch_assoc();
$encuesta_respondida = ($encuesta_info !== null);

// Guardar variables para los parciales
$estudiante_info = $estudiante;
$estudiante_info['nombre_completo'] = $estudiante['nombre'] . ' ' . $estudiante['apellido'];

// Ahora incluimos el header
require_once 'partials/header.php';
?>

<!-- Incluir sidebar -->
<?php require_once 'partials/sidebar.php'; ?>

<!-- Main Content -->
<main class="main-content">
    <div class="breadcrumb">
        <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
    </div>
    
    <!-- Header -->
    <div class="header-actions">
        <h1>
            <i class="bi bi-speedometer2"></i>
            Dashboard del Estudiante
        </h1>
        
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if ($periodo): ?>
            <a href="matricular.php" class="btn-action btn-green">
                <i class="bi bi-plus-circle"></i> Matricularme en Materias
            </a>
            <?php endif; ?>
            <a href="horario.php" class="btn-action btn-blue">
                <i class="bi bi-printer"></i> Imprimir Horario
            </a>
        </div>
    </div>
    
    <!-- Estadísticas -->
    <div class="stats-container">
        <div class="stat-card">
            <i class="bi bi-journal-text" style="color: #2a5298;"></i>
            <div class="stat-number"><?php echo count($matriculas); ?></div>
            <div class="stat-label">Materias Matriculadas</div>
        </div>
        
        <div class="stat-card">
            <i class="bi bi-calendar-check" style="color: #38a169;"></i>
            <div class="stat-number">
                <?php 
                if ($periodo) {
                    echo htmlspecialchars($periodo['nombre'] ?? '');
                } else {
                    echo 'Ninguno';
                }
                ?>
            </div>
            <div class="stat-label">Período Actual</div>
        </div>
        
        <div class="stat-card">
            <i class="bi bi-clipboard-check" style="color: #d69e2e;"></i>
            <div class="stat-number">
                <?php echo $encuesta_respondida ? 'Respondida' : 'Pendiente'; ?>
            </div>
            <div class="stat-label">Encuesta</div>
        </div>
        
        <div class="stat-card">
            <i class="bi bi-mortarboard" style="color: #805ad5;"></i>
            <div class="stat-number"><?php echo htmlspecialchars($estudiante['semestre_actual'] ?? '1'); ?></div>
            <div class="stat-label">Semestre Actual</div>
        </div>
    </div>
    
    <!-- Información del Estudiante -->
    <div class="card">
        <div class="card-header">
            <h2><i class="bi bi-person-circle"></i> Información Personal</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <p><strong>Nombre:</strong><br><?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?></p>
                <p><strong>Cédula:</strong><br><?php echo htmlspecialchars($estudiante['cedula']); ?></p>
                <p><strong>Correo:</strong><br><?php echo htmlspecialchars($estudiante['correo']); ?></p>
            </div>
            <div>
                <p><strong>Carrera:</strong><br><?php echo htmlspecialchars($estudiante['carrera_nombre'] ?? 'No asignada'); ?></p>
                <p><strong>Año:</strong><br><?php echo htmlspecialchars($estudiante['año_carrera']); ?> año</p>
                <p><strong>Semestre:</strong><br><?php echo htmlspecialchars($estudiante['semestre_actual']); ?></p>
            </div>
            <div>
                <p><strong>Teléfono:</strong><br><?php echo htmlspecialchars($estudiante['telefono'] ?? 'No registrado'); ?></p>
                <p><strong>Ingreso:</strong><br><?php echo $estudiante['fecha_ingreso'] ? date('d/m/Y', strtotime($estudiante['fecha_ingreso'])) : 'No registrada'; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Materias Matriculadas -->
    <div class="card">
        <div class="card-header">
            <h2><i class="bi bi-journal-text"></i> Materias Matriculadas - <?php echo $periodo['nombre'] ?? 'No activo'; ?></h2>
            <a href="materias.php" class="btn-action">Ver Todas</a>
        </div>
        
        <?php if (count($matriculas) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>Código</th><th>Materia</th><th>Docente</th><th>Horario</th><th>Aula</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($matriculas as $m): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['materia_codigo']); ?></td>
                        <td><strong><?php echo htmlspecialchars($m['materia_nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars($m['docente_nombre'] ?? 'Por asignar'); ?></td>
                        <td><?php echo htmlspecialchars($m['dia']) . ' ' . substr($m['hora_inicio'], 0, 5) . ' - ' . substr($m['hora_fin'], 0, 5); ?></td>
                        <td><?php echo htmlspecialchars($m['aula']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-journal-x"></i>
            <h3>No tienes materias matriculadas</h3>
            <p><?php echo $periodo ? 'Puedes matricular materias.' : 'No hay período activo.'; ?></p>
            <?php if ($periodo): ?>
            <a href="matricular.php" class="btn-action btn-green">
                <i class="bi bi-plus-circle"></i> Matricularme Ahora
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Estado de Encuesta -->
    <div class="card">
        <div class="card-header">
            <h2><i class="bi bi-clipboard-check"></i> Encuesta de Satisfacción</h2>
        </div>
        
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 0;">
            <div>
                <h3 style="margin-bottom: 10px;">Estado: 
                    <?php if ($encuesta_respondida): ?>
                        <span class="badge badge-success">Respondida</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Pendiente</span>
                    <?php endif; ?>
                </h3>
                <p style="color: #666;">Tu opinión es importante para mejorar nuestro servicio.</p>
            </div>
            
            <div>
                <a href="encuesta.php" class="btn-action <?php echo $encuesta_respondida ? 'btn-warning' : 'btn-green'; ?>">
                    <i class="bi bi-clipboard-check"></i>
                    <?php echo $encuesta_respondida ? 'Ver/Editar Encuesta' : 'Responder Encuesta'; ?>
                </a>
            </div>
        </div>
    </div>
</main>

<?php
// Cerrar conexión y mostrar footer
$conexion->close();
require_once 'partials/footer.php';
?>