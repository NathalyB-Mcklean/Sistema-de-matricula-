<?php
// app/views/admin/dashboard.php

// Iniciar sesión
session_start();

// Verificar si el usuario está logueado y es admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Conexión a la base de datos
require_once '../../config/conexion.php';

// Obtener período activo
$query_periodo = "SELECT * FROM periodos_academicos WHERE estado = 'activo' LIMIT 1";
$result_periodo = $conexion->query($query_periodo);
$periodo_activo = $result_periodo->fetch_assoc();
$id_periodo_activo = $periodo_activo['id_periodo'] ?? null;

// Obtener estadísticas generales
$estadisticas = [];

// Total de estudiantes
$query = "SELECT COUNT(*) as total FROM usuario WHERE rol = 'estudiante' AND estado = 'activo'";
$result = $conexion->query($query);
$estadisticas['total_estudiantes'] = $result->fetch_assoc()['total'];

// Total de docentes
$query = "SELECT COUNT(*) as total FROM usuario WHERE rol = 'docente' AND estado = 'activo'";
$result = $conexion->query($query);
$estadisticas['total_docentes'] = $result->fetch_assoc()['total'];

// Total de materias
$query = "SELECT COUNT(*) as total FROM materias";
$result = $conexion->query($query);
$estadisticas['total_materias'] = $result->fetch_assoc()['total'];

// Total de matrículas
$query = "SELECT COUNT(*) as total FROM matriculas";
$result = $conexion->query($query);
$estadisticas['total_matriculas'] = $result->fetch_assoc()['total'];

// Recaudación total
$query = "SELECT SUM(m.costo) as total 
          FROM matriculas mat
          JOIN grupos_horarios_materia ghm ON mat.id_ghm = ghm.id_ghm
          JOIN materias m ON ghm.id_materia = m.id_materia";
$result = $conexion->query($query);
$estadisticas['recaudacion'] = $result->fetch_assoc()['total'] ?? 0;

// Estadísticas del período activo
$estadisticas_periodo = [];
if ($id_periodo_activo) {
    // Matrículas del período activo
    $query = "SELECT COUNT(*) as total FROM matriculas WHERE id_periodo = $id_periodo_activo";
    $result = $conexion->query($query);
    $estadisticas_periodo['matriculas'] = $result->fetch_assoc()['total'];
    
    // Estudiantes matriculados en el período activo
    $query = "SELECT COUNT(DISTINCT mat.id_estudiante) as total 
              FROM matriculas mat
              WHERE mat.id_periodo = $id_periodo_activo";
    $result = $conexion->query($query);
    $estadisticas_periodo['estudiantes_matriculados'] = $result->fetch_assoc()['total'];
    
    // Recaudación del período activo
    $query = "SELECT SUM(m.costo) as total 
              FROM matriculas mat
              JOIN grupos_horarios_materia ghm ON mat.id_ghm = ghm.id_ghm
              JOIN materias m ON ghm.id_materia = m.id_materia
              WHERE mat.id_periodo = $id_periodo_activo";
    $result = $conexion->query($query);
    $estadisticas_periodo['recaudacion'] = $result->fetch_assoc()['total'] ?? 0;
}

// Obtener distribución de estudiantes por año de carrera
$query = "SELECT e.año_carrera, COUNT(*) as total 
          FROM estudiantes e
          JOIN usuario u ON e.id_usuario = u.id_usuario
          WHERE u.estado = 'activo' AND u.rol = 'estudiante'
          GROUP BY e.año_carrera
          ORDER BY e.año_carrera";
$distribucion_carrera = $conexion->query($query);

// Obtener últimos estudiantes registrados
$query = "SELECT u.id_usuario, u.nombre, u.apellido, u.correo, u.estado,
                 e.año_carrera, e.semestre_actual, e.fecha_ingreso
          FROM usuario u
          LEFT JOIN estudiantes e ON u.id_usuario = e.id_usuario
          WHERE u.rol = 'estudiante' 
          ORDER BY u.id_usuario DESC 
          LIMIT 5";
$ultimos_estudiantes = $conexion->query($query);

// Obtener últimas matrículas con información completa
$query = "SELECT mat.id_matricula, u.nombre, u.apellido, mat.fecha, 
                 m.nombre as materia_nombre, pa.nombre as periodo_nombre
          FROM matriculas mat
          JOIN estudiantes est ON mat.id_estudiante = est.id_estudiante
          JOIN usuario u ON est.id_usuario = u.id_usuario
          JOIN grupos_horarios_materia ghm ON mat.id_ghm = ghm.id_ghm
          JOIN materias m ON ghm.id_materia = m.id_materia
          LEFT JOIN periodos_academicos pa ON mat.id_periodo = pa.id_periodo
          ORDER BY mat.fecha DESC 
          LIMIT 5";
$ultimas_matriculas = $conexion->query($query);

// Obtener materias con más estudiantes en el período activo
if ($id_periodo_activo) {
    $query = "SELECT m.nombre, COUNT(mat.id_matricula) as total_estudiantes
              FROM materias m
              JOIN grupos_horarios_materia ghm ON m.id_materia = ghm.id_materia
              JOIN matriculas mat ON ghm.id_ghm = mat.id_ghm
              WHERE mat.id_periodo = $id_periodo_activo
              GROUP BY m.id_materia
              ORDER BY total_estudiantes DESC
              LIMIT 5";
} else {
    $query = "SELECT m.nombre, COUNT(mat.id_matricula) as total_estudiantes
              FROM materias m
              JOIN grupos_horarios_materia ghm ON m.id_materia = ghm.id_materia
              JOIN matriculas mat ON ghm.id_ghm = mat.id_ghm
              GROUP BY m.id_materia
              ORDER BY total_estudiantes DESC
              LIMIT 5";
}


// Definir la ruta base para incluir el sidebar
$base_path = dirname(__FILE__); // Esto devuelve: C:\wamp64\www\Sistema-de-matricula-\app\views\admin
$sidebar_path = $base_path . '\partials\sidebar.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Sistema UTP</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/dashboardadmin.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function updateDateTime() {
                const now = new Date();
                const options = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                };
                document.getElementById('current-datetime').textContent = 
                    now.toLocaleDateString('es-ES', options);
            }
            
            updateDateTime();
            setInterval(updateDateTime, 60000);
        });
    </script>
</head>
<body>    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'partials/sidebar.php'; ?>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <div>
                    <h1>Saludos, <?php echo $_SESSION['user_name']; ?></h1>
                    <p id="current-datetime"></p>
                </div>
            </div>
            
            <!-- Mostrar información del período activo -->
            <?php if ($periodo_activo): ?>
            <div class="periodo-info" style="background: linear-gradient(135deg, #6B2C91 0%, #8e44ad 100%); color: white; padding: 15px; border-radius: 10px; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin: 0; color: white;">
                            <i class="bi bi-calendar-check-fill"></i> 
                            Período Académico Activo: <?php echo $periodo_activo['nombre']; ?>
                        </h3>
                        <p style="margin: 5px 0 0 0; opacity: 0.9;">
                            <?php echo date('d/m/Y', strtotime($periodo_activo['fecha_inicio'])); ?> 
                            - 
                            <?php echo date('d/m/Y', strtotime($periodo_activo['fecha_fin'])); ?>
                            | 
                            <?php echo $periodo_activo['semestre'] == 1 ? 'Primer Semestre' : ($periodo_activo['semestre'] == 2 ? 'Segundo Semestre' : 'Verano'); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-number"><?php echo $estadisticas['total_estudiantes']; ?></div>
                    <div class="stat-title">Estudiantes Activos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-person-video3"></i>
                    </div>
                    <div class="stat-number"><?php echo $estadisticas['total_docentes']; ?></div>
                    <div class="stat-title">Docentes Activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-pencil-fill"></i>
                    </div>
                    <div class="stat-number"><?php echo $estadisticas['total_matriculas']; ?></div>
                    <div class="stat-title">Matrículas Activas</div>
                </div>
            </div>
            
            <!-- Distribución por Año de Carrera -->
            <div class="card" style="margin-top: 30px;">
                <div class="card-header">
                    <h2><i class="bi bi-graph-up me-2"></i>Distribución de Estudiantes por Año</h2>
                </div>
                <div style="padding: 20px;">
                    <div class="distribucion-carrera">
                        <?php while($dist = $distribucion_carrera->fetch_assoc()): ?>
                        <div style="margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span>Año <?php echo $dist['año_carrera']; ?> de Carrera</span>
                                <span><?php echo $dist['total']; ?> estudiantes</span>
                            </div>
                            <div style="height: 10px; background: #eee; border-radius: 5px; overflow: hidden;">
                                <?php 
                                $porcentaje = $estadisticas['total_estudiantes'] > 0 ? 
                                             ($dist['total'] / $estadisticas['total_estudiantes']) * 100 : 0;
                                ?>
                                <div style="height: 100%; width: <?php echo $porcentaje; ?>%; background: #6B2C91; border-radius: 5px;"></div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Períodos académicos -->
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee;">
                        <h3 style="color: #6B2C91; margin-bottom: 15px;">
                            <i class="bi bi-calendar-range me-2"></i>Períodos Académicos
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                            <?php 
                            $query_periodos = "SELECT * FROM periodos_academicos ORDER BY año DESC, semestre DESC LIMIT 3";
                            $result_periodos = $conexion->query($query_periodos);
                            while($periodo = $result_periodos->fetch_assoc()):
                            ?>
                            <div style="background: <?php echo $periodo['estado'] == 'activo' ? '#e8f5e9' : '#f8f9fa'; ?>; 
                                         border: 1px solid <?php echo $periodo['estado'] == 'activo' ? '#2d8659' : '#dee2e6'; ?>;
                                         border-left: 4px solid <?php echo $periodo['estado'] == 'activo' ? '#2d8659' : '#6B2C91'; ?>;
                                         padding: 15px; border-radius: 5px;">
                                <div style="font-weight: bold; color: #333;"><?php echo $periodo['nombre']; ?></div>
                                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                    <?php echo date('M/Y', strtotime($periodo['fecha_inicio'])); ?> - 
                                    <?php echo date('M/Y', strtotime($periodo['fecha_fin'])); ?>
                                </div>
                                <div style="margin-top: 8px;">
                                    <span class="badge badge-<?php 
                                        echo $periodo['estado'] == 'activo' ? 'success' : 
                                             ($periodo['estado'] == 'planificado' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($periodo['estado']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contenido principal - CORREGIDO -->
            <div class="content-grid">
                <!-- Últimos estudiantes -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="bi bi-people me-2"></i>Últimos estudiantes registrados</h2>
                        <a href="estudiantes.php" class="view-all">Ver todos</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Estado</th>
                                    <th>Año</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($estudiante = $ultimos_estudiantes->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $estudiante['id_usuario']; ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['correo']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $estudiante['estado'] == 'activo' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($estudiante['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">
                                            Año <?php echo $estudiante['año_carrera'] ?? 'N/A'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Últimas matrículas -->
                <div class="card">
                    <div class="card-header">
                        <h2><i class="bi bi-pencil-square me-2"></i>Últimas matrículas</h2>
                        <a href="matriculas.php" class="view-all">Ver todas</a>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Estudiante</th>
                                    <th>Materia</th>
                                    <th>Período</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($matricula = $ultimas_matriculas->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $matricula['id_matricula']; ?></td>
                                    <td><?php echo htmlspecialchars($matricula['nombre'] . ' ' . $matricula['apellido']); ?></td>
                                    <td class="materia-nombre" title="<?php echo htmlspecialchars($matricula['materia_nombre']); ?>">
                                        <?php echo htmlspecialchars($matricula['materia_nombre']); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo $matricula['periodo_nombre'] ?? 'Sin período'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($matricula['fecha'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- Cierre del content-grid -->
            
        </main>
    </div>
</body>
</html>
<?php
$conexion->close();
?>