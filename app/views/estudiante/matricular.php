<?php
// app/views/estudiante/matricular.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'estudiante') {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

$id_estudiante = $_SESSION['id_estudiante'];

// Variables para los partials
$titulo_pagina = 'Matricularme - Sistema UTP';
$pagina_activa = 'matricular';

// Obtener información del estudiante
$query_estudiante = "SELECT e.*, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo, 
                     c.nombre as carrera_nombre, c.id_carrera
                     FROM estudiantes e 
                     JOIN usuario u ON e.id_usuario = u.id_usuario 
                     LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
                     WHERE e.id_estudiante = ?";
$stmt = $conexion->prepare($query_estudiante);
$stmt->bind_param('i', $id_estudiante);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$estudiante_info = $estudiante;

$id_carrera = $estudiante['id_carrera'] ?? null;

// Verificar que hay un período activo
$query_periodo = "SELECT * FROM periodos_academicos WHERE estado = 'activo' LIMIT 1";
$periodo = $conexion->query($query_periodo)->fetch_assoc();

if (!$periodo) {
    header('Location: dashboard.php?error=No hay período activo para matrícula');
    exit();
}

// DEBUG: Mostrar información de período
error_log("DEBUG: Período activo encontrado - ID: " . $periodo['id_periodo'] . ", Nombre: " . $periodo['nombre']);

// Procesar matrícula
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['materias'])) {
    try {
        $conexion->begin_transaction();
        
        foreach ($_POST['materias'] as $id_ghm) {
            // Verificar que el grupo esté disponible
            $query_grupo = "SELECT gp.cupo_maximo, gp.cupo_actual 
                           FROM grupos_periodo gp 
                           WHERE gp.id_ghm = ? AND gp.id_periodo = ?";
            $stmt = $conexion->prepare($query_grupo);
            $stmt->bind_param('ii', $id_ghm, $periodo['id_periodo']);
            $stmt->execute();
            $grupo = $stmt->get_result()->fetch_assoc();
            
            if (!$grupo || $grupo['cupo_actual'] >= $grupo['cupo_maximo']) {
                throw new Exception("El grupo seleccionado no tiene cupo disponible");
            }
            
            // Verificar que no esté ya matriculado en este grupo
            $query_check = "SELECT COUNT(*) as total FROM matriculas 
                           WHERE id_estudiante = ? AND id_ghm = ? AND id_periodo = ?";
            $stmt = $conexion->prepare($query_check);
            $stmt->bind_param('iis', $id_estudiante, $id_ghm, $periodo['id_periodo']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['total'] > 0) {
                throw new Exception("Ya estás matriculado en uno de los grupos seleccionados");
            }
            
            // Insertar matrícula
            $query_insert = "INSERT INTO matriculas (id_estudiante, id_ghm, id_periodo, fecha) 
                            VALUES (?, ?, ?, NOW())";
            $stmt = $conexion->prepare($query_insert);
            $stmt->bind_param('iis', $id_estudiante, $id_ghm, $periodo['id_periodo']);
            $stmt->execute();
            
            // Actualizar cupo del grupo
            $query_update = "UPDATE grupos_periodo SET cupo_actual = cupo_actual + 1 
                            WHERE id_ghm = ? AND id_periodo = ?";
            $stmt = $conexion->prepare($query_update);
            $stmt->bind_param('ii', $id_ghm, $periodo['id_periodo']);
            $stmt->execute();
        }
        
        $conexion->commit();
        $mensaje = '<div class="alert alert-success">Matrícula realizada exitosamente</div>';
        
        // Redirigir después de 2 segundos
        header('Refresh: 2; URL=dashboard.php');
        
    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// Obtener materias disponibles - CONSULTA CORREGIDA
$materias_disponibles = [];
if ($id_carrera) {
    $query_materias = "
        SELECT DISTINCT 
            m.id_materia, 
            m.codigo, 
            m.nombre, 
            m.descripcion, 
            m.costo,
            ghm.id_ghm, 
            ghm.aula, 
            h.dia, 
            h.hora_inicio, 
            h.hora_fin,
            CONCAT(d.nombre, ' ', d.apellido) as docente_nombre,
            gp.cupo_maximo, 
            gp.cupo_actual,
            (gp.cupo_maximo - gp.cupo_actual) as cupos_disponibles
        FROM materias m
        JOIN grupos_horarios_materia ghm ON m.id_materia = ghm.id_materia
        JOIN horarios h ON ghm.id_horario = h.id_horario
        LEFT JOIN docentes d ON m.id_docente = d.id_docente
        JOIN grupos_periodo gp ON ghm.id_ghm = gp.id_ghm
        JOIN periodos_academicos pa ON gp.id_periodo = pa.id_periodo
        WHERE m.id_carrera = ?
          AND pa.id_periodo = ?
          AND pa.estado = 'activo'
          AND gp.estado = 'activo'
          AND gp.cupo_actual < gp.cupo_maximo
        ORDER BY m.codigo, h.dia, h.hora_inicio
    ";
    $stmt = $conexion->prepare($query_materias);
    $periodo_id = $periodo['id_periodo'];
    $stmt->bind_param('ii', $id_carrera, $periodo_id);
    $stmt->execute();
    $materias_disponibles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // DEBUG: Log para verificar qué se encontró
    error_log("DEBUG: Carrera ID: $id_carrera, Período ID: $periodo_id");
    error_log("DEBUG: Materias encontradas: " . count($materias_disponibles));
    
    // Si no hay materias, verificar si hay datos en las tablas
    if (empty($materias_disponibles)) {
        // Consulta de diagnóstico
        $query_diagnostico = "
            SELECT 
                (SELECT COUNT(*) FROM materias WHERE id_carrera = ?) as total_materias,
                (SELECT COUNT(*) FROM grupos_horarios_materia ghm2 
                 JOIN materias m2 ON ghm2.id_materia = m2.id_materia 
                 WHERE m2.id_carrera = ?) as total_grupos,
                (SELECT COUNT(*) FROM grupos_periodo gp2 
                 JOIN grupos_horarios_materia ghm2 ON gp2.id_ghm = ghm2.id_ghm
                 JOIN materias m2 ON ghm2.id_materia = m2.id_materia
                 WHERE m2.id_carrera = ? AND gp2.id_periodo = ?) as grupos_periodo
        ";
        $stmt = $conexion->prepare($query_diagnostico);
        $stmt->bind_param('iiii', $id_carrera, $id_carrera, $id_carrera, $periodo_id);
        $stmt->execute();
        $diagnostico = $stmt->get_result()->fetch_assoc();
        
        error_log("DEBUG DIAGNÓSTICO: Materias: " . $diagnostico['total_materias'] . 
                 ", Grupos: " . $diagnostico['total_grupos'] . 
                 ", Grupos/Período: " . $diagnostico['grupos_periodo']);
    }
}

// Obtener materias ya matriculadas para evitar conflictos
$matriculas_actuales = [];
$query_matriculas = "
    SELECT ghm.id_ghm, h.dia, h.hora_inicio, h.hora_fin
    FROM matriculas m
    JOIN grupos_horarios_materia ghm ON m.id_ghm = ghm.id_ghm
    JOIN horarios h ON ghm.id_horario = h.id_horario
    WHERE m.id_estudiante = ? AND m.id_periodo = ?
";
$stmt = $conexion->prepare($query_matriculas);
$stmt->bind_param('is', $id_estudiante, $periodo['id_periodo']);
$stmt->execute();
$matriculas_actuales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// CSS adicional para esta página
$css_adicional = "
    .materias-container { max-width: 1000px; margin: 0 auto; }
    .materia-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 15px; transition: all 0.3s; }
    .materia-card:hover { border-color: #2c5282; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .materia-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; flex-wrap: wrap; gap: 10px; }
    .materia-info { flex: 1; }
    .materia-codigo { font-weight: bold; color: #2c5282; font-size: 0.9rem; }
    .materia-nombre { font-weight: 600; color: #333; margin: 5px 0; font-size: 1.1rem; }
    .materia-costo { font-weight: bold; color: #38a169; font-size: 1rem; white-space: nowrap; }
    .materia-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 15px 0; font-size: 0.9rem; color: #666; }
    .materia-details div { display: flex; align-items: center; gap: 5px; }
    .checkbox-container { display: flex; align-items: center; gap: 10px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee; }
    .cupo-disponible { font-size: 0.9rem; padding: 4px 10px; border-radius: 4px; background: #e6fffa; color: #234e52; font-weight: 500; }
    .cupo-lleno { background: #fed7d7; color: #742a2a; }
    .form-actions { position: sticky; bottom: 0; background: white; padding: 20px; border-top: 1px solid #ddd; text-align: center; margin-top: 30px; }
    .horario-conflict { border: 2px solid #e53e3e !important; background: #fff5f5; }
    .horario-conflict-message { color: #e53e3e; font-size: 0.9rem; margin-top: 5px; display: flex; align-items: center; gap: 5px; }
    
    .info-box {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .info-box h4 {
        margin-top: 0;
        color: #1565c0;
    }
    
    .alert {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
";

// Incluir header y sidebar
require_once 'partials/header.php';
require_once 'partials/sidebar.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="breadcrumb">
        <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
        <span> / </span>
        <span>Matricularme</span>
    </div>
    
    <div class="header-actions">
        <h1>
            <i class="bi bi-pencil-square"></i>
            Matricular Materias - Período <?php echo $periodo['nombre']; ?>
        </h1>
        <a href="dashboard.php" class="btn-action">Volver al Dashboard</a>
    </div>
    
    <?php echo $mensaje; ?>
    
    <?php if (!$id_carrera): ?>
    <div class="alert alert-danger">
        <strong>Error:</strong> No tienes una carrera asignada. Contacta con administración.
    </div>
    <?php elseif (empty($materias_disponibles)): ?>
    <div class="alert alert-warning">
        <h4><i class="bi bi-exclamation-triangle"></i> No hay materias disponibles para matricular</h4>
        
        <div class="info-box">
            <h4>Información de tu cuenta:</h4>
            <p><strong>Carrera:</strong> <?php echo htmlspecialchars($estudiante['carrera_nombre'] ?? 'No asignada'); ?></p>
            <p><strong>Período activo:</strong> <?php echo htmlspecialchars($periodo['nombre']); ?></p>
            <p><strong>Semestre actual:</strong> <?php echo htmlspecialchars($estudiante['semestre_actual'] ?? '1'); ?></p>
        </div>
        
        <h5>Posibles razones:</h5>
        <ul>
            <li>Todas las materias para tu carrera ya están llenas</li>
            <li>No se han configurado grupos para este período</li>
            <li>Ya estás matriculado en todas las materias disponibles</li>
            <li>El período de matrícula aún no ha comenzado o ya cerró</li>
        </ul>
        
        <p>Contacta con el departamento de registro para más información.</p>
    </div>
    <?php else: ?>
    
    <form method="POST" action="">
        <div style="margin-bottom: 20px;">
            <h3>Materias Disponibles para tu Carrera</h3>
            <p>Selecciona las materias en las que deseas matricularte. Verifica los horarios para evitar conflictos.</p>
            <p><strong>Total encontrado:</strong> <?php echo count($materias_disponibles); ?> materias disponibles</p>
        </div>
        
        <?php foreach ($materias_disponibles as $materia): 
            // Verificar conflictos de horario
            $conflicto = false;
            $mensaje_conflicto = '';
            foreach ($matriculas_actuales as $matriculada) {
                if ($materia['dia'] == $matriculada['dia']) {
                    $hora_inicio_nueva = strtotime($materia['hora_inicio']);
                    $hora_fin_nueva = strtotime($materia['hora_fin']);
                    $hora_inicio_matriculada = strtotime($matriculada['hora_inicio']);
                    $hora_fin_matriculada = strtotime($matriculada['hora_fin']);
                    
                    if (($hora_inicio_nueva >= $hora_inicio_matriculada && $hora_inicio_nueva < $hora_fin_matriculada) ||
                        ($hora_fin_nueva > $hora_inicio_matriculada && $hora_fin_nueva <= $hora_fin_matriculada) ||
                        ($hora_inicio_nueva <= $hora_inicio_matriculada && $hora_fin_nueva >= $hora_fin_matriculada)) {
                        $conflicto = true;
                        $mensaje_conflicto = "Conflicto con materia ya matriculada: " . 
                                             $matriculada['dia'] . " " . 
                                             substr($matriculada['hora_inicio'], 0, 5) . "-" . 
                                             substr($matriculada['hora_fin'], 0, 5);
                        break;
                    }
                }
            }
        ?>
        <div class="materia-card <?php echo $conflicto ? 'horario-conflict' : ''; ?>" 
             data-id="<?php echo $materia['id_ghm']; ?>"
             data-dia="<?php echo $materia['dia']; ?>"
             data-hora-inicio="<?php echo $materia['hora_inicio']; ?>"
             data-hora-fin="<?php echo $materia['hora_fin']; ?>">
            
            <div class="materia-header">
                <div>
                    <span class="materia-codigo"><?php echo htmlspecialchars($materia['codigo']); ?></span>
                    <strong><?php echo htmlspecialchars($materia['nombre']); ?></strong>
                </div>
                <div>
                    <span class="materia-costo">$<?php echo number_format($materia['costo'], 2); ?></span>
                </div>
            </div>
            
            <div class="materia-details">
                <div><i class="bi bi-person"></i> <?php echo htmlspecialchars($materia['docente_nombre'] ?? 'Por asignar'); ?></div>
                <div><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($materia['aula']); ?></div>
                <div><i class="bi bi-calendar-week"></i> <?php echo htmlspecialchars($materia['dia']); ?></div>
                <div><i class="bi bi-clock"></i> <?php echo substr($materia['hora_inicio'], 0, 5); ?> - <?php echo substr($materia['hora_fin'], 0, 5); ?></div>
                <div>
                    <span class="cupo-disponible <?php echo $materia['cupos_disponibles'] <= 0 ? 'cupo-lleno' : ''; ?>">
                        <i class="bi bi-people"></i> 
                        <?php echo $materia['cupos_disponibles']; ?> cupos disponibles
                        (<?php echo $materia['cupo_actual']; ?>/<?php echo $materia['cupo_maximo']; ?>)
                    </span>
                </div>
            </div>
            
            <?php if ($conflicto): ?>
            <div class="horario-conflict-message" style="display: block;">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $mensaje_conflicto; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($materia['cupos_disponibles'] > 0 && !$conflicto): ?>
            <div class="checkbox-container">
                <input type="checkbox" 
                       name="materias[]" 
                       value="<?php echo $materia['id_ghm']; ?>" 
                       id="materia_<?php echo $materia['id_ghm']; ?>"
                       onchange="verificarConflictos()">
                <label for="materia_<?php echo $materia['id_ghm']; ?>">
                    Seleccionar para matricular
                </label>
            </div>
            <?php elseif ($materia['cupos_disponibles'] <= 0): ?>
            <div style="color: #e53e3e; font-size: 0.9rem;">
                <i class="bi bi-x-circle"></i> Sin cupos disponibles
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn-action btn-green" id="btnMatricular">
                <i class="bi bi-check-circle"></i> Confirmar Matrícula
            </button>
            <a href="dashboard.php" class="btn-action">Cancelar</a>
        </div>
    </form>
    <?php endif; ?>
</main>

<script>
    // Verificación de conflictos de horario
    function verificarConflictos() {
        const materiasSeleccionadas = document.querySelectorAll('input[name="materias[]"]:checked');
        const materiasCards = document.querySelectorAll('.materia-card');
        
        materiasCards.forEach(card => {
            card.classList.remove('horario-conflict');
            const mensaje = card.querySelector('.horario-conflict-message');
            if (mensaje) mensaje.style.display = 'none';
        });
        
        const horariosSeleccionados = [];
        
        materiasSeleccionadas.forEach(checkbox => {
            const card = checkbox.closest('.materia-card');
            const dia = card.dataset.dia;
            const horaInicio = card.dataset.horaInicio;
            const horaFin = card.dataset.horaFin;
            
            horariosSeleccionados.push({
                card: card,
                dia: dia,
                horaInicio: horaInicio,
                horaFin: horaFin,
                id: card.dataset.id
            });
        });
        
        for (let i = 0; i < horariosSeleccionados.length; i++) {
            for (let j = i + 1; j < horariosSeleccionados.length; j++) {
                if (horariosSeleccionados[i].dia === horariosSeleccionados[j].dia) {
                    const inicio1 = new Date('1970-01-01T' + horariosSeleccionados[i].horaInicio);
                    const fin1 = new Date('1970-01-01T' + horariosSeleccionados[i].horaFin);
                    const inicio2 = new Date('1970-01-01T' + horariosSeleccionados[j].horaInicio);
                    const fin2 = new Date('1970-01-01T' + horariosSeleccionados[j].horaFin);
                    
                    if ((inicio1 >= inicio2 && inicio1 < fin2) || 
                        (fin1 > inicio2 && fin1 <= fin2) ||
                        (inicio1 <= inicio2 && fin1 >= fin2)) {
                        
                        horariosSeleccionados[i].card.classList.add('horario-conflict');
                        horariosSeleccionados[j].card.classList.add('horario-conflict');
                        
                        let mensaje1 = horariosSeleccionados[i].card.querySelector('.horario-conflict-message');
                        let mensaje2 = horariosSeleccionados[j].card.querySelector('.horario-conflict-message');
                        
                        if (!mensaje1) {
                            mensaje1 = document.createElement('div');
                            mensaje1.className = 'horario-conflict-message';
                            horariosSeleccionados[i].card.appendChild(mensaje1);
                        }
                        
                        if (!mensaje2) {
                            mensaje2 = document.createElement('div');
                            mensaje2.className = 'horario-conflict-message';
                            horariosSeleccionados[j].card.appendChild(mensaje2);
                        }
                        
                        mensaje1.innerHTML = `<i class="bi bi-exclamation-triangle"></i> Conflicto de horario con otra materia seleccionada`;
                        mensaje1.style.display = 'block';
                        
                        mensaje2.innerHTML = `<i class="bi bi-exclamation-triangle"></i> Conflicto de horario con otra materia seleccionada`;
                        mensaje2.style.display = 'block';
                    }
                }
            }
        }
        
        const btnMatricular = document.getElementById('btnMatricular');
        const tieneConflictos = document.querySelectorAll('.horario-conflict').length > 0;
        btnMatricular.disabled = tieneConflictos || materiasSeleccionadas.length === 0;
    }
    
    document.addEventListener('DOMContentLoaded', verificarConflictos);
</script>

<?php
$conexion->close();
require_once 'partials/footer.php';
?>