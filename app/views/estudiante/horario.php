<?php
// app/views/estudiante/horario.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'estudiante') {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

$id_estudiante = $_SESSION['id_estudiante'];

// Variables para los partials
$titulo_pagina = 'Mi Horario - Sistema UTP';
$pagina_activa = 'horario';

// Obtener información del estudiante
$query_estudiante = "SELECT e.*, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo, 
                     c.nombre as carrera_nombre
                     FROM estudiantes e 
                     JOIN usuario u ON e.id_usuario = u.id_usuario 
                     LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
                     WHERE e.id_estudiante = ?";
$stmt = $conexion->prepare($query_estudiante);
$stmt->bind_param('i', $id_estudiante);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$estudiante_info = $estudiante;

// Obtener período activo
$query_periodo = "SELECT * FROM periodos_academicos WHERE estado = 'activo' LIMIT 1";
$periodo = $conexion->query($query_periodo)->fetch_assoc();

// Obtener horario del estudiante
$horario = [];
if ($periodo) {
    $query = "
        SELECT m.nombre as materia_nombre, m.codigo as materia_codigo,
               ghm.aula, h.dia, h.hora_inicio, h.hora_fin,
               CONCAT(d.nombre, ' ', d.apellido) as docente_nombre,
               c.nombre as carrera_nombre
        FROM matriculas mat
        JOIN grupos_horarios_materia ghm ON mat.id_ghm = ghm.id_ghm
        JOIN materias m ON ghm.id_materia = m.id_materia
        JOIN horarios h ON ghm.id_horario = h.id_horario
        LEFT JOIN docentes d ON m.id_docente = d.id_docente
        JOIN carreras c ON m.id_carrera = c.id_carrera
        WHERE mat.id_estudiante = ? AND mat.id_periodo = ?
        ORDER BY 
            CASE h.dia 
                WHEN 'Lunes' THEN 1 
                WHEN 'Martes' THEN 2 
                WHEN 'Miércoles' THEN 3 
                WHEN 'Jueves' THEN 4 
                WHEN 'Viernes' THEN 5 
                WHEN 'Sábado' THEN 6 
                ELSE 7 
            END, 
            h.hora_inicio
    ";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('is', $id_estudiante, $periodo['id_periodo']);
    $stmt->execute();
    $horario = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Agrupar por día
$horario_por_dia = [
    'Lunes' => [],
    'Martes' => [],
    'Miércoles' => [],
    'Jueves' => [],
    'Viernes' => [],
    'Sábado' => []
];

foreach ($horario as $clase) {
    $horario_por_dia[$clase['dia']][] = $clase;
}

// CSS adicional para esta página
$css_adicional = "
    .horario-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .dia-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .dia-header {
        background: #2c5282;
        color: white;
        padding: 15px;
        text-align: center;
        font-weight: bold;
        font-size: 1.1rem;
    }
    
    .clase-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        transition: background 0.3s;
    }
    
    .clase-item:hover {
        background: #f8f9fa;
    }
    
    .clase-item:last-child {
        border-bottom: none;
    }
    
    .clase-horario {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        font-size: 0.9rem;
        color: #666;
    }
    
    .clase-materia {
        font-weight: 600;
        margin-bottom: 5px;
        color: #2c5282;
        font-size: 1rem;
    }
    
    .clase-detalles {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 0.9rem;
        color: #666;
    }
    
    .clase-detalles span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .empty-dia {
        padding: 30px;
        text-align: center;
        color: #a0aec0;
    }
    
    .empty-dia i {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    
    @media print {
        .sidebar, .breadcrumb, .header-actions button:not(.btn-print) {
            display: none !important;
        }
        
        .main-content { padding: 0; margin: 0; }
        .horario-grid { display: block; }
        .dia-card {
            break-inside: avoid;
            margin-bottom: 20px;
            box-shadow: none;
            border: 1px solid #ddd;
        }
    }
    
    @media (max-width: 768px) {
        .horario-grid {
            grid-template-columns: 1fr;
        }
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
        <span>Mi Horario</span>
    </div>
    
    <div class="header-actions">
        <h1>
            <i class="bi bi-calendar-week"></i>
            Mi Horario - Período <?php echo $periodo['nombre'] ?? 'No activo'; ?>
        </h1>
        <div>
            <button onclick="window.print()" class="btn-action btn-green" style="margin-right: 10px;">
                <i class="bi bi-printer"></i> Imprimir Horario
            </button>
            <a href="dashboard.php" class="btn-action">Volver</a>
        </div>
    </div>
    
    <?php if (empty($horario)): ?>
    <div style="text-align: center; padding: 50px 20px;">
        <i class="bi bi-calendar-x" style="font-size: 4rem; color: #cbd5e0;"></i>
        <h3>No tienes materias matriculadas</h3>
        <p>No hay clases en tu horario para el período actual.</p>
        <?php if ($periodo): ?>
        <a href="matricular.php" class="btn-action btn-green" style="margin-top: 15px;">
            <i class="bi bi-plus-circle"></i> Matricularme en Materias
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    
    <div class="horario-grid">
        <?php foreach ($horario_por_dia as $dia => $clases): ?>
        <div class="dia-card">
            <div class="dia-header"><?php echo $dia; ?></div>
            
            <?php if (empty($clases)): ?>
            <div class="empty-dia">
                <i class="bi bi-emoji-smile"></i>
                <p>No hay clases este día</p>
            </div>
            <?php else: ?>
                <?php foreach ($clases as $clase): ?>
                <div class="clase-item">
                    <div class="clase-horario">
                        <span>
                            <i class="bi bi-clock"></i>
                            <?php echo substr($clase['hora_inicio'], 0, 5); ?> - <?php echo substr($clase['hora_fin'], 0, 5); ?>
                        </span>
                        <span>
                            <i class="bi bi-geo-alt"></i>
                            <?php echo htmlspecialchars($clase['aula']); ?>
                        </span>
                    </div>
                    <div class="clase-materia">
                        <?php echo htmlspecialchars($clase['materia_codigo'] . ' - ' . $clase['materia_nombre']); ?>
                    </div>
                    <div class="clase-detalles">
                        <span>
                            <i class="bi bi-person"></i>
                            <?php echo htmlspecialchars($clase['docente_nombre'] ?? 'Por asignar'); ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div style="margin-top: 30px; padding: 20px; background: white; border-radius: 10px;">
        <h3>Resumen del Horario</h3>
        <p>Total de materias: <strong><?php echo count($horario); ?></strong></p>
        <p>Período académico: <strong><?php echo $periodo['nombre'] ?? 'No activo'; ?></strong></p>
        <p>Fecha de impresión: <strong><?php echo date('d/m/Y H:i'); ?></strong></p>
    </div>
    <?php endif; ?>
</main>

<?php
$conexion->close();
require_once 'partials/footer.php';
?>