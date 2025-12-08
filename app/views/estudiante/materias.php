<?php
// app/views/estudiante/materias.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'estudiante') {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

$id_estudiante = $_SESSION['id_estudiante'];

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

// Obtener período activo
$query_periodo = "SELECT * FROM periodos_academicos WHERE estado = 'activo' LIMIT 1";
$periodo = $conexion->query($query_periodo)->fetch_assoc();

// Obtener todas las materias del estudiante (historial)
$query_materias = "
    SELECT m.nombre as materia_nombre, m.codigo as materia_codigo, m.costo,
           ghm.aula, h.dia, h.hora_inicio, h.hora_fin,
           CONCAT(d.nombre, ' ', d.apellido) as docente_nombre,
           p.nombre as periodo_nombre, p.año, p.semestre,
           c.nombre as carrera_nombre
    FROM matriculas mat
    JOIN grupos_horarios_materia ghm ON mat.id_ghm = ghm.id_ghm
    JOIN materias m ON ghm.id_materia = m.id_materia
    JOIN horarios h ON ghm.id_horario = h.id_horario
    LEFT JOIN docentes d ON m.id_docente = d.id_docente
    JOIN periodos_academicos p ON mat.id_periodo = p.id_periodo
    JOIN carreras c ON m.id_carrera = c.id_carrera
    WHERE mat.id_estudiante = ?
    ORDER BY p.año DESC, p.semestre DESC, h.dia, h.hora_inicio
";
$stmt = $conexion->prepare($query_materias);
$stmt->bind_param('i', $id_estudiante);
$stmt->execute();
$materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conexion->close();

// Agrupar por período
$materias_por_periodo = [];
foreach ($materias as $materia) {
    $periodo_key = $materia['periodo_nombre'];
    if (!isset($materias_por_periodo[$periodo_key])) {
        $materias_por_periodo[$periodo_key] = [];
    }
    $materias_por_periodo[$periodo_key][] = $materia;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Materias - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/estudiante.css">
    <style>
        .periodo-section {
            background: white;
            border-radius: 10px;
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .periodo-header {
            background: #2c5282;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .periodo-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .materias-list {
            padding: 20px;
        }
        
        .materia-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        
        .materia-item:last-child {
            border-bottom: none;
        }
        
        .materia-item:hover {
            background: #f8f9fa;
        }
        
        .materia-info h4 {
            margin: 0 0 5px 0;
            color: #2c5282;
        }
        
        .materia-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .materia-horario, .materia-aula, .materia-docente {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .materia-horario span, .materia-aula span, .materia-docente span {
            font-size: 0.9rem;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .materia-item {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">
                <h2>UTP Estudiante</h2>
                <small>Sistema de Matrícula</small>
            </div>
            
            <div class="user-info">
                <div class="avatar">
                    <?php 
                    $nombre = $estudiante['nombre_completo'] ?? 'Estudiante';
                    $nombre_parts = explode(' ', $nombre);
                    $iniciales = '';
                    
                    if (count($nombre_parts) >= 2) {
                        $iniciales = strtoupper(substr($nombre_parts[0], 0, 1) . substr($nombre_parts[1], 0, 1));
                    } elseif (!empty($nombre_parts[0])) {
                        $iniciales = strtoupper(substr($nombre_parts[0], 0, 1));
                    } else {
                        $iniciales = 'E';
                    }
                    echo $iniciales;
                    ?>
                </div>
                <h3><?php echo htmlspecialchars($nombre); ?></h3>
                <p><?php echo htmlspecialchars($estudiante['carrera_nombre'] ?? 'Estudiante'); ?></p>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-item">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="materias.php" class="nav-item active">
                    <i class="bi bi-journal-text"></i>
                    <span>Mis Materias</span>
                </a>
                <a href="horario.php" class="nav-item">
                    <i class="bi bi-calendar-week"></i>
                    <span>Mi Horario</span>
                </a>
                <?php if ($periodo): ?>
                <a href="matricular.php" class="nav-item">
                    <i class="bi bi-pencil-square"></i>
                    <span>Matricularme</span>
                </a>
                <?php endif; ?>
                <a href="encuesta.php" class="nav-item">
                    <i class="bi bi-clipboard-check"></i>
                    <span>Encuesta</span>
                </a>
                
                <div class="logout">
                    <a href="../../auth/logout.php" class="nav-item">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="breadcrumb">
                <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
                <span> / </span>
                <span>Mis Materias</span>
            </div>
            
            <div class="header-actions">
                <h1>
                    <i class="bi bi-journal-text"></i>
                    Mis Materias (Historial Académico)
                </h1>
                <a href="dashboard.php" class="btn-action">Volver al Dashboard</a>
            </div>
            
            <?php if (empty($materias)): ?>
            <div class="empty-state">
                <i class="bi bi-journal-x"></i>
                <h3>No tienes materias matriculadas</h3>
                <p>No hay registro de materias en tu historial académico.</p>
                <?php if ($periodo): ?>
                <a href="matricular.php" class="btn-action btn-green" style="margin-top: 15px;">
                    <i class="bi bi-plus-circle"></i> Matricularme en Materias
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            
            <?php foreach ($materias_por_periodo as $periodo_nombre => $materias_periodo): ?>
            <div class="periodo-section">
                <div class="periodo-header">
                    <h3>
                        <i class="bi bi-calendar-check"></i>
                        Período: <?php echo htmlspecialchars($periodo_nombre); ?>
                    </h3>
                    <span style="background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 4px;">
                        <?php echo count($materias_periodo); ?> materias
                    </span>
                </div>
                
                <div class="materias-list">
                    <?php foreach ($materias_periodo as $materia): ?>
                    <div class="materia-item">
                        <div class="materia-info">
                            <h4><?php echo htmlspecialchars($materia['materia_codigo'] . ' - ' . $materia['materia_nombre']); ?></h4>
                            <p><?php echo htmlspecialchars($materia['carrera_nombre']); ?></p>
                        </div>
                        
                        <div class="materia-horario">
                            <span><i class="bi bi-clock"></i> <?php echo htmlspecialchars($materia['dia']); ?></span>
                            <span><?php echo substr($materia['hora_inicio'], 0, 5); ?> - <?php echo substr($materia['hora_fin'], 0, 5); ?></span>
                        </div>
                        
                        <div class="materia-aula">
                            <span><i class="bi bi-geo-alt"></i> Aula</span>
                            <span><?php echo htmlspecialchars($materia['aula']); ?></span>
                        </div>
                        
                        <div class="materia-docente">
                            <span><i class="bi bi-person"></i> Docente</span>
                            <span><?php echo htmlspecialchars($materia['docente_nombre'] ?? 'Por asignar'); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div style="background: white; padding: 20px; border-radius: 10px; margin-top: 20px;">
                <h3 style="color: #2c5282; margin-bottom: 15px;">Resumen Académico</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <p style="color: #666; font-size: 0.9rem;">Total de materias:</p>
                        <p style="font-size: 1.5rem; font-weight: bold; color: #2c5282;"><?php echo count($materias); ?></p>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 0.9rem;">Períodos cursados:</p>
                        <p style="font-size: 1.5rem; font-weight: bold; color: #2c5282;"><?php echo count($materias_por_periodo); ?></p>
                    </div>
                    <div>
                        <p style="color: #666; font-size: 0.9rem;">Última actualización:</p>
                        <p style="font-size: 1rem; font-weight: bold; color: #666;"><?php echo date('d/m/Y H:i'); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        document.querySelectorAll('a[href*="logout.php"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>