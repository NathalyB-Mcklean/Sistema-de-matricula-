<?php
// app/views/admin/reportes.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

// Obtener parámetros para filtros
$tipo_reporte = $_GET['tipo'] ?? 'general';
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-t');
$id_carrera = $_GET['id_carrera'] ?? '';
$id_periodo = $_GET['id_periodo'] ?? '';
$id_estudiante = $_GET['id_estudiante'] ?? '';
$formato = $_GET['formato'] ?? 'html';

// Obtener datos para filtros
$carreras = $conexion->query("SELECT id_carrera, nombre, codigo FROM carreras WHERE estado = 'activa' ORDER BY nombre");
$periodos = $conexion->query("SELECT id_periodo, nombre, año, semestre FROM periodos_academicos ORDER BY año DESC, semestre DESC");

// Procesar generación de reportes
$datos_reporte = [];
$titulo_reporte = '';
$query = '';
$params = [];
$types = '';

// Para debug - muestra errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    switch ($tipo_reporte) {
        case 'estudiantes':
            $titulo_reporte = 'Reporte de Estudiantes';
            $query = "SELECT 
                        e.id_estudiante,
                        e.cedula,
                        CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
                        u.correo,
                        u.estado as estado_usuario,
                        c.nombre as carrera_nombre,
                        c.codigo as carrera_codigo,
                        e.año_carrera,
                        e.semestre_actual,
                        e.fecha_ingreso,
                        (SELECT COUNT(*) FROM matriculas m WHERE m.id_estudiante = e.id_estudiante) as total_matriculas,
                        (SELECT COALESCE(SUM(mat.costo), 0) 
                         FROM matriculas m 
                         JOIN grupos_horarios_materia ghm ON m.id_ghm = ghm.id_ghm
                         JOIN materias mat ON ghm.id_materia = mat.id_materia
                         WHERE m.id_estudiante = e.id_estudiante) as total_gastado
                      FROM estudiantes e
                      JOIN usuario u ON e.id_usuario = u.id_usuario
                      LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
                      WHERE 1=1";
            
            if ($id_carrera && $id_carrera !== '') {
                $query .= " AND e.id_carrera = ?";
                $params[] = $id_carrera;
                $types .= 'i';
            }
            if ($fecha_desde && $fecha_desde !== '') {
                $query .= " AND DATE(e.fecha_ingreso) >= ?";
                $params[] = $fecha_desde;
                $types .= 's';
            }
            if ($fecha_hasta && $fecha_hasta !== '') {
                $query .= " AND DATE(e.fecha_ingreso) <= ?";
                $params[] = $fecha_hasta;
                $types .= 's';
            }
            
            $query .= " ORDER BY u.apellido, u.nombre";
            break;
            
        case 'matriculas':
            $titulo_reporte = 'Reporte de Matrículas';
            $query = "SELECT 
                        mt.id_matricula,
                        mt.fecha,
                        CONCAT(u.nombre, ' ', u.apellido) as estudiante_nombre,
                        e.cedula,
                        m.nombre as materia_nombre,
                        m.codigo as materia_codigo,
                        m.costo,
                        c.nombre as carrera_nombre,
                        p.nombre as periodo_nombre,
                        CONCAT(d.nombre, ' ', d.apellido) as docente_nombre,
                        h.dia,
                        TIME_FORMAT(h.hora_inicio, '%H:%i') as hora_inicio,
                        TIME_FORMAT(h.hora_fin, '%H:%i') as hora_fin,
                        ghm.aula
                      FROM matriculas mt
                      JOIN estudiantes e ON mt.id_estudiante = e.id_estudiante
                      JOIN usuario u ON e.id_usuario = u.id_usuario
                      JOIN grupos_horarios_materia ghm ON mt.id_ghm = ghm.id_ghm
                      JOIN materias m ON ghm.id_materia = m.id_materia
                      LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                      LEFT JOIN periodos_academicos p ON mt.id_periodo = p.id_periodo
                      LEFT JOIN docentes d ON m.id_docente = d.id_docente
                      LEFT JOIN horarios h ON ghm.id_horario = h.id_horario
                      WHERE 1=1";
            
            if ($id_carrera && $id_carrera !== '') {
                $query .= " AND c.id_carrera = ?";
                $params[] = $id_carrera;
                $types .= 'i';
            }
            if ($id_periodo && $id_periodo !== '') {
                $query .= " AND p.id_periodo = ?";
                $params[] = $id_periodo;
                $types .= 'i';
            }
            if ($id_estudiante && $id_estudiante !== '') {
                $query .= " AND e.id_estudiante = ?";
                $params[] = $id_estudiante;
                $types .= 'i';
            }
            if ($fecha_desde && $fecha_desde !== '') {
                $query .= " AND DATE(mt.fecha) >= ?";
                $params[] = $fecha_desde;
                $types .= 's';
            }
            if ($fecha_hasta && $fecha_hasta !== '') {
                $query .= " AND DATE(mt.fecha) <= ?";
                $params[] = $fecha_hasta;
                $types .= 's';
            }
            
            $query .= " ORDER BY mt.fecha DESC";
            break;
            
        case 'ingresos':
            $titulo_reporte = 'Reporte de Ingresos';
            $query = "SELECT 
                        p.nombre as periodo,
                        c.nombre as carrera,
                        COUNT(DISTINCT mt.id_matricula) as total_matriculas,
                        COUNT(DISTINCT mt.id_estudiante) as total_estudiantes,
                        COALESCE(SUM(m.costo), 0) as ingresos_totales,
                        COALESCE(AVG(m.costo), 0) as promedio_por_materia
                      FROM matriculas mt
                      JOIN grupos_horarios_materia ghm ON mt.id_ghm = ghm.id_ghm
                      JOIN materias m ON ghm.id_materia = m.id_materia
                      LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                      LEFT JOIN periodos_academicos p ON mt.id_periodo = p.id_periodo
                      WHERE 1=1";
            
            if ($id_carrera && $id_carrera !== '') {
                $query .= " AND c.id_carrera = ?";
                $params[] = $id_carrera;
                $types .= 'i';
            }
            if ($id_periodo && $id_periodo !== '') {
                $query .= " AND p.id_periodo = ?";
                $params[] = $id_periodo;
                $types .= 'i';
            }
            if ($fecha_desde && $fecha_desde !== '') {
                $query .= " AND DATE(mt.fecha) >= ?";
                $params[] = $fecha_desde;
                $types .= 's';
            }
            if ($fecha_hasta && $fecha_hasta !== '') {
                $query .= " AND DATE(mt.fecha) <= ?";
                $params[] = $fecha_hasta;
                $types .= 's';
            }
            
            $query .= " GROUP BY p.id_periodo, c.id_carrera ORDER BY p.año DESC, p.semestre DESC, c.nombre";
            break;
            
        case 'materias':
            $titulo_reporte = 'Reporte de Materias';
            $query = "SELECT 
                        m.codigo,
                        m.nombre,
                        m.descripcion,
                        m.costo,
                        c.nombre as carrera_nombre,
                        c.codigo as carrera_codigo,
                        CONCAT(d.nombre, ' ', d.apellido) as docente_nombre,
                        COUNT(DISTINCT ghm.id_ghm) as total_grupos,
                        COUNT(DISTINCT mt.id_estudiante) as total_estudiantes,
                        COALESCE(SUM(m.costo), 0) as ingresos_totales
                      FROM materias m
                      LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                      LEFT JOIN docentes d ON m.id_docente = d.id_docente
                      LEFT JOIN grupos_horarios_materia ghm ON m.id_materia = ghm.id_materia
                      LEFT JOIN matriculas mt ON ghm.id_ghm = mt.id_ghm
                      WHERE 1=1";
            
            if ($id_carrera && $id_carrera !== '') {
                $query .= " AND c.id_carrera = ?";
                $params[] = $id_carrera;
                $types .= 'i';
            }
            
            $query .= " GROUP BY m.id_materia ORDER BY c.nombre, m.nombre";
            break;
            
        case 'encuestas':
            $titulo_reporte = 'Reporte de Encuestas de Satisfacción';
            $query = "SELECT 
                        en.id_encuesta,
                        en.satisfaccion,
                        en.observaciones,
                        en.fecha,
                        CONCAT(u.nombre, ' ', u.apellido) as estudiante_nombre,
                        e.cedula,
                        c.nombre as carrera_nombre,
                        c.codigo as carrera_codigo,
                        e.año_carrera,
                        e.semestre_actual
                      FROM encuestas en
                      JOIN estudiantes e ON en.id_estudiante = e.id_estudiante
                      JOIN usuario u ON e.id_usuario = u.id_usuario
                      LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
                      WHERE 1=1";
            
            if ($id_carrera && $id_carrera !== '') {
                $query .= " AND c.id_carrera = ?";
                $params[] = $id_carrera;
                $types .= 'i';
            }
            if ($fecha_desde && $fecha_desde !== '') {
                $query .= " AND DATE(en.fecha) >= ?";
                $params[] = $fecha_desde;
                $types .= 's';
            }
            if ($fecha_hasta && $fecha_hasta !== '') {
                $query .= " AND DATE(en.fecha) <= ?";
                $params[] = $fecha_hasta;
                $types .= 's';
            }
            if ($id_estudiante && $id_estudiante !== '') {
                $query .= " AND e.id_estudiante = ?";
                $params[] = $id_estudiante;
                $types .= 'i';
            }
            
            $query .= " ORDER BY en.fecha DESC";
            break;
            
        default:
            $titulo_reporte = 'Reporte General del Sistema';
            // Estadísticas generales
            $datos_reporte = [
                'total_estudiantes' => $conexion->query("SELECT COUNT(*) as total FROM estudiantes")->fetch_assoc()['total'] ?? 0,
                'total_docentes' => $conexion->query("SELECT COUNT(*) as total FROM docentes WHERE estado = 'activo'")->fetch_assoc()['total'] ?? 0,
                'total_materias' => $conexion->query("SELECT COUNT(*) as total FROM materias")->fetch_assoc()['total'] ?? 0,
                'total_matriculas' => $conexion->query("SELECT COUNT(*) as total FROM matriculas")->fetch_assoc()['total'] ?? 0,
                'total_ingresos' => $conexion->query("SELECT COALESCE(SUM(m.costo), 0) as total FROM matriculas mt JOIN grupos_horarios_materia ghm ON mt.id_ghm = ghm.id_ghm JOIN materias m ON ghm.id_materia = m.id_materia")->fetch_assoc()['total'] ?? 0,
                'carrera_mas_popular' => $conexion->query("SELECT c.nombre, COUNT(DISTINCT e.id_estudiante) as total FROM carreras c LEFT JOIN estudiantes e ON c.id_carrera = e.id_carrera GROUP BY c.id_carrera ORDER BY total DESC LIMIT 1")->fetch_assoc() ?? ['nombre' => 'N/A', 'total' => 0],
                'materia_mas_solicitada' => $conexion->query("SELECT m.nombre, COUNT(mt.id_matricula) as total FROM materias m JOIN grupos_horarios_materia ghm ON m.id_materia = ghm.id_materia LEFT JOIN matriculas mt ON ghm.id_ghm = mt.id_ghm GROUP BY m.id_materia ORDER BY total DESC LIMIT 1")->fetch_assoc() ?? ['nombre' => 'N/A', 'total' => 0],
            ];
            
            // Estadísticas de encuestas para el reporte general
            $query_encuestas = $conexion->query("
                SELECT 
                    COUNT(*) as total_encuestas,
                    SUM(CASE WHEN satisfaccion = 'Excelente' THEN 1 ELSE 0 END) as excelente,
                    SUM(CASE WHEN satisfaccion = 'Conforme' THEN 1 ELSE 0 END) as conforme,
                    SUM(CASE WHEN satisfaccion = 'Inconforme' THEN 1 ELSE 0 END) as inconforme,
                    SUM(CASE WHEN satisfaccion = 'No respondida' THEN 1 ELSE 0 END) as no_respondida,
                    ROUND(AVG(
                        CASE 
                            WHEN satisfaccion = 'Excelente' THEN 4
                            WHEN satisfaccion = 'Conforme' THEN 3
                            WHEN satisfaccion = 'Inconforme' THEN 2
                            WHEN satisfaccion = 'No respondida' THEN 1
                            ELSE 0 
                        END
                    ), 1) as promedio_satisfaccion
                FROM encuestas
            ");
            
            $estadisticas_encuestas = $query_encuestas->fetch_assoc();
            $datos_reporte['estadisticas_encuestas'] = $estadisticas_encuestas;
            
            // Distribución de encuestas por carrera
            $query_encuestas_carrera = $conexion->query("
                SELECT 
                    c.nombre as carrera,
                    COUNT(en.id_encuesta) as total_encuestas,
                    SUM(CASE WHEN en.satisfaccion = 'Excelente' THEN 1 ELSE 0 END) as excelente,
                    SUM(CASE WHEN en.satisfaccion = 'Conforme' THEN 1 ELSE 0 END) as conforme,
                    SUM(CASE WHEN en.satisfaccion = 'Inconforme' THEN 1 ELSE 0 END) as inconforme
                FROM carreras c
                LEFT JOIN estudiantes e ON c.id_carrera = e.id_carrera
                LEFT JOIN encuestas en ON e.id_estudiante = en.id_estudiante
                GROUP BY c.id_carrera
                ORDER BY total_encuestas DESC
            ");
            
            $distribucion_encuestas = [];
            while($row = $query_encuestas_carrera->fetch_assoc()) {
                $distribucion_encuestas[] = $row;
            }
            $datos_reporte['distribucion_encuestas'] = $distribucion_encuestas;
            break;
    }
    
    // Ejecutar consulta si no es el reporte general
    if ($tipo_reporte !== 'general' && !empty($query)) {
        if (!empty($params)) {
            $stmt = $conexion->prepare($query);
            if ($stmt) {
                if (!empty($types)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $datos_reporte = $stmt->get_result();
            } else {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }
        } else {
            $datos_reporte = $conexion->query($query);
        }
        
        // Verificar si hubo error
        if (!$datos_reporte) {
            throw new Exception("Error en la consulta: " . $conexion->error);
        }
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Exportar a CSV si se solicita
if ($formato === 'csv' && $tipo_reporte !== 'general') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_' . $tipo_reporte . '_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Escribir encabezados
    if (is_object($datos_reporte) && $datos_reporte->num_rows > 0) {
        $first_row = $datos_reporte->fetch_assoc();
        fputcsv($output, array_keys($first_row), ';');
        $datos_reporte->data_seek(0); // Resetear puntero
        
        while($row = $datos_reporte->fetch_assoc()) {
            fputcsv($output, $row, ';');
        }
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Estilos Reportes - Sistema UTP */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(180deg, #6B2C91 0%, #4a1e6e 100%);
            color: white;
            padding: 20px 0;
        }

        .logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo h2 {
            color: white;
            font-size: 22px;
        }

        .logo small {
            color: rgba(255,255,255,0.7);
            font-size: 12px;
        }

        .user-info {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .user-info .avatar {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6B2C91;
            font-size: 40px;
        }

        .user-info h3 {
            margin-bottom: 5px;
            color: white;
        }

        .user-info p {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
        }

        .nav-menu {
            padding: 20px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #2d8659;
        }

        .nav-item i {
            font-size: 18px;
        }

        .logout {
            margin-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
        }

        /* Main Content */
        .main-content {
            padding: 30px;
            background: #f5f5f5;
        }

        .reportes-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .breadcrumb {
            margin-bottom: 20px;
            padding: 12px 20px;
            background: white;
            border-radius: 10px;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .breadcrumb a {
            color: #6B2C91;
            text-decoration: none;
            font-weight: 600;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .header-actions h1 {
            color: #6B2C91;
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        /* Filtros */
        .filters-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .filters-card h3 {
            color: #6B2C91;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-group select,
        .form-group input {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: #fafafa;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #6B2C91;
            background: white;
            box-shadow: 0 0 0 3px rgba(107, 44, 145, 0.1);
        }

        .form-group button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        /* Reporte Container */
        .reporte-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .reporte-header {
            border-bottom: 3px solid #6B2C91;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .reporte-header h2 {
            color: #6B2C91;
            font-size: 24px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .reporte-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 14px;
            color: #666;
        }

        .reporte-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Estadísticas Grid */
        .estadisticas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .estadistica-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 10px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .estadistica-card:hover {
            transform: translateY(-5px);
        }

        .estadistica-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            background: linear-gradient(135deg, #6B2C91 0%, #8e44ad 100%);
            color: white;
        }

        .estadistica-content h3 {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .estadistica-content p {
            color: #666;
            font-size: 14px;
        }

        /* Nuevos estilos para gráficos de encuestas */
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }

        .encuestas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .encuesta-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .encuesta-card h3 {
            color: #6B2C91;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .satisfaccion-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .satisfaccion-item:last-child {
            border-bottom: none;
        }

        .satisfaccion-label {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .satisfaccion-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        .icon-excelente {
            background: #38a169;
        }

        .icon-conforme {
            background: #3182ce;
        }

        .icon-inconforme {
            background: #e53e3e;
        }

        .icon-no-respondida {
            background: #a0aec0;
        }

        .satisfaccion-count {
            font-weight: bold;
            color: #333;
        }

        .satisfaccion-percent {
            font-size: 12px;
            color: #666;
            margin-left: 5px;
        }

        /* Gráficos */
        .graficos-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .grafico-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .grafico-card h3 {
            color: #6B2C91;
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .distribucion-item {
            margin-bottom: 20px;
        }

        .distribucion-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
            color: #555;
        }

        .distribucion-bar {
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #6B2C91 0%, #2d8659 100%);
            border-radius: 5px;
            transition: width 0.5s ease;
        }

        .distribucion-porcentaje {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }

        .info-destacada {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            align-items: center;
        }

        .info-item i {
            font-size: 24px;
            color: #6B2C91;
        }

        .info-item strong {
            color: #333;
        }

        /* Tabla */
        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }

        .reporte-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .reporte-table th {
            background-color: #f8f9fa;
            color: #333;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        .reporte-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #555;
            font-size: 14px;
        }

        .reporte-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }

        /* Resumen */
        .resumen-reporte {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #6B2C91;
        }

        .resumen-reporte h4 {
            color: #6B2C91;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .resumen-content {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }

        .resumen-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #555;
            font-size: 14px;
        }

        .resumen-item i {
            color: #6B2C91;
        }

        /* Botones */
        .btn-action {
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-purple {
            background: #6B2C91;
            color: white;
        }

        .btn-purple:hover {
            background: #5a2478;
        }

        .btn-green {
            background: #2d8659;
            color: white;
        }

        .btn-green:hover {
            background: #246d48;
        }

        .btn-blue {
            background: #3498db;
            color: white;
        }

        .btn-blue:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 40px;
            color: #666;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ddd;
            display: block;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .empty-state p {
            color: #999;
            margin-bottom: 25px;
            font-size: 16px;
        }

        /* Alertas de error */
        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #e53e3e;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .header-actions {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .graficos-container {
                grid-template-columns: 1fr;
            }
            
            .encuestas-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <a href="reportes.php" class="nav-item active">
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
            <div class="reportes-container">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
                    <span> / </span>
                    <span>Reportes</span>
                </div>
                
                <!-- Header -->
                <div class="header-actions">
                    <h1>
                        <i class="bi bi-graph-up"></i>
                        Sistema de Reportes
                    </h1>
                    <?php if ($tipo_reporte !== 'general' && is_object($datos_reporte) && $datos_reporte->num_rows > 0): ?>
                    <div style="display: flex; gap: 10px;">
                        <a href="?tipo=<?php echo $tipo_reporte; ?>&formato=csv&fecha_desde=<?php echo $fecha_desde; ?>&fecha_hasta=<?php echo $fecha_hasta; ?>&id_carrera=<?php echo $id_carrera; ?>&id_periodo=<?php echo $id_periodo; ?>&id_estudiante=<?php echo $id_estudiante; ?>" 
                           class="btn-action btn-green">
                            <i class="bi bi-file-earmark-excel"></i> Exportar CSV
                        </a>
                        <button onclick="window.print()" class="btn-action btn-blue">
                            <i class="bi bi-printer"></i> Imprimir
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Mostrar errores si los hay -->
                <?php if (isset($error)): ?>
                <div class="alert-error">
                    <strong><i class="bi bi-exclamation-triangle"></i> Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <!-- Filtros -->
                <div class="filters-card">
                    <h3><i class="bi bi-funnel"></i> Filtros de Reporte</h3>
                    <form method="GET" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="bi bi-clipboard-data"></i> Tipo de Reporte</label>
                                <select name="tipo" onchange="this.form.submit()">
                                    <option value="general" <?php echo $tipo_reporte == 'general' ? 'selected' : ''; ?>>General del Sistema</option>
                                    <option value="estudiantes" <?php echo $tipo_reporte == 'estudiantes' ? 'selected' : ''; ?>>Estudiantes</option>
                                    <option value="matriculas" <?php echo $tipo_reporte == 'matriculas' ? 'selected' : ''; ?>>Matrículas</option>
                                    <option value="ingresos" <?php echo $tipo_reporte == 'ingresos' ? 'selected' : ''; ?>>Ingresos</option>
                                    <option value="materias" <?php echo $tipo_reporte == 'materias' ? 'selected' : ''; ?>>Materias</option>
                                    <option value="encuestas" <?php echo $tipo_reporte == 'encuestas' ? 'selected' : ''; ?>>Encuestas de Satisfacción</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="bi bi-mortarboard"></i> Carrera</label>
                                <select name="id_carrera" onchange="this.form.submit()">
                                    <option value="">Todas las carreras</option>
                                    <?php 
                                    $carreras->data_seek(0); // Resetear puntero
                                    while($carrera = $carreras->fetch_assoc()): ?>
                                    <option value="<?php echo $carrera['id_carrera']; ?>" <?php echo $id_carrera == $carrera['id_carrera'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($carrera['codigo'] . ' - ' . $carrera['nombre']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <?php if ($tipo_reporte == 'matriculas' || $tipo_reporte == 'ingresos'): ?>
                            <div class="form-group">
                                <label><i class="bi bi-calendar"></i> Período</label>
                                <select name="id_periodo" onchange="this.form.submit()">
                                    <option value="">Todos los períodos</option>
                                    <?php 
                                    $periodos->data_seek(0); // Resetear puntero
                                    while($periodo = $periodos->fetch_assoc()): 
                                    ?>
                                    <option value="<?php echo $periodo['id_periodo']; ?>" <?php echo $id_periodo == $periodo['id_periodo'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($periodo['nombre'] . ' (' . $periodo['año'] . '-' . $periodo['semestre'] . ')'); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="bi bi-calendar"></i> Fecha Desde</label>
                                <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>" onchange="this.form.submit()">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="bi bi-calendar"></i> Fecha Hasta</label>
                                <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>" onchange="this.form.submit()">
                            </div>
                            
                            <?php if ($tipo_reporte == 'matriculas' || $tipo_reporte == 'encuestas'): ?>
                            <div class="form-group">
                                <label><i class="bi bi-person"></i> ID Estudiante</label>
                                <input type="number" name="id_estudiante" value="<?php echo $id_estudiante; ?>" placeholder="ID del estudiante" min="1" onchange="this.form.submit()">
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <button type="submit" class="btn-action btn-purple">
                                    <i class="bi bi-search"></i> Generar Reporte
                                </button>
                                <button type="button" onclick="window.location.href='reportes.php'" class="btn-action">
                                    <i class="bi bi-x-circle"></i> Limpiar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Reporte -->
                <div class="reporte-container">
                    <div class="reporte-header">
                        <h2>
                            <i class="bi bi-file-earmark-text"></i>
                            <?php echo $titulo_reporte; ?>
                        </h2>
                        <div class="reporte-info">
                            <span><i class="bi bi-calendar"></i> <?php echo date('d/m/Y H:i'); ?></span>
                            <span><i class="bi bi-person"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
                            <?php if ($fecha_desde && $fecha_hasta): ?>
                            <span><i class="bi bi-clock"></i> <?php echo date('d/m/Y', strtotime($fecha_desde)); ?> - <?php echo date('d/m/Y', strtotime($fecha_hasta)); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($tipo_reporte == 'general'): ?>
                    <!-- Reporte General -->
                    <div class="estadisticas-grid">
                        <div class="estadistica-card">
                            <div class="estadistica-icon">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div class="estadistica-content">
                                <h3><?php echo number_format($datos_reporte['total_estudiantes']); ?></h3>
                                <p>Estudiantes Registrados</p>
                            </div>
                        </div>
                        
                        <div class="estadistica-card">
                            <div class="estadistica-icon">
                                <i class="bi bi-person-video"></i>
                            </div>
                            <div class="estadistica-content">
                                <h3><?php echo number_format($datos_reporte['total_docentes']); ?></h3>
                                <p>Docentes Activos</p>
                            </div>
                        </div>
                        
                        <div class="estadistica-card">
                            <div class="estadistica-icon">
                                <i class="bi bi-journal-text"></i>
                            </div>
                            <div class="estadistica-content">
                                <h3><?php echo number_format($datos_reporte['total_materias']); ?></h3>
                                <p>Materias Disponibles</p>
                            </div>
                        </div>
                        
                        <div class="estadistica-card">
                            <div class="estadistica-icon">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                            <div class="estadistica-content">
                                <h3>$<?php echo number_format($datos_reporte['total_ingresos'], 2); ?></h3>
                                <p>Ingresos Totales</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estadísticas de Encuestas -->
                    <div class="encuestas-grid">
                        <div class="encuesta-card">
                            <h3><i class="bi bi-clipboard-check"></i> Resumen de Encuestas</h3>
                            <div class="satisfaccion-item">
                                <div class="satisfaccion-label">
                                    <div class="satisfaccion-icon icon-excelente">
                                        <i class="bi bi-emoji-laughing"></i>
                                    </div>
                                    <span>Excelente</span>
                                </div>
                                <div class="satisfaccion-count">
                                    <?php echo $datos_reporte['estadisticas_encuestas']['excelente'] ?? 0; ?>
                                    <span class="satisfaccion-percent">
                                        <?php 
                                        $total_encuestas = $datos_reporte['estadisticas_encuestas']['total_encuestas'] ?? 1;
                                        $porcentaje = $total_encuestas > 0 ? (($datos_reporte['estadisticas_encuestas']['excelente'] ?? 0) / $total_encuestas) * 100 : 0;
                                        echo '(' . round($porcentaje, 1) . '%)';
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="satisfaccion-item">
                                <div class="satisfaccion-label">
                                    <div class="satisfaccion-icon icon-conforme">
                                        <i class="bi bi-emoji-smile"></i>
                                    </div>
                                    <span>Conforme</span>
                                </div>
                                <div class="satisfaccion-count">
                                    <?php echo $datos_reporte['estadisticas_encuestas']['conforme'] ?? 0; ?>
                                    <span class="satisfaccion-percent">
                                        <?php 
                                        $porcentaje = $total_encuestas > 0 ? (($datos_reporte['estadisticas_encuestas']['conforme'] ?? 0) / $total_encuestas) * 100 : 0;
                                        echo '(' . round($porcentaje, 1) . '%)';
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="satisfaccion-item">
                                <div class="satisfaccion-label">
                                    <div class="satisfaccion-icon icon-inconforme">
                                        <i class="bi bi-emoji-frown"></i>
                                    </div>
                                    <span>Inconforme</span>
                                </div>
                                <div class="satisfaccion-count">
                                    <?php echo $datos_reporte['estadisticas_encuestas']['inconforme'] ?? 0; ?>
                                    <span class="satisfaccion-percent">
                                        <?php 
                                        $porcentaje = $total_encuestas > 0 ? (($datos_reporte['estadisticas_encuestas']['inconforme'] ?? 0) / $total_encuestas) * 100 : 0;
                                        echo '(' . round($porcentaje, 1) . '%)';
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="satisfaccion-item">
                                <div class="satisfaccion-label">
                                    <div class="satisfaccion-icon icon-no-respondida">
                                        <i class="bi bi-dash-circle"></i>
                                    </div>
                                    <span>No respondida</span>
                                </div>
                                <div class="satisfaccion-count">
                                    <?php echo $datos_reporte['estadisticas_encuestas']['no_respondida'] ?? 0; ?>
                                    <span class="satisfaccion-percent">
                                        <?php 
                                        $porcentaje = $total_encuestas > 0 ? (($datos_reporte['estadisticas_encuestas']['no_respondida'] ?? 0) / $total_encuestas) * 100 : 0;
                                        echo '(' . round($porcentaje, 1) . '%)';
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; text-align: center;">
                                <strong>Total encuestas:</strong> <?php echo $datos_reporte['estadisticas_encuestas']['total_encuestas'] ?? 0; ?><br>
                                <strong>Promedio de satisfacción:</strong> <?php echo $datos_reporte['estadisticas_encuestas']['promedio_satisfaccion'] ?? 'N/A'; ?>/4
                            </div>
                        </div>
                        
                        <div class="encuesta-card">
                            <h3><i class="bi bi-bar-chart"></i> Gráfico de Satisfacción</h3>
                            <div class="chart-container">
                                <canvas id="chartSatisfaccion"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Distribución por Carrera -->
                    <?php if (!empty($datos_reporte['distribucion_encuestas'])): ?>
                    <div style="margin-top: 30px;">
                        <h3 style="color: #6B2C91; margin-bottom: 20px;"><i class="bi bi-mortarboard"></i> Encuestas por Carrera</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px;">
                            <?php foreach ($datos_reporte['distribucion_encuestas'] as $carrera): ?>
                            <div style="background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                                <h4 style="color: #333; margin-bottom: 10px;"><?php echo htmlspecialchars($carrera['carrera']); ?></h4>
                                <div style="font-size: 14px; color: #666;">
                                    <div>Total encuestas: <strong><?php echo $carrera['total_encuestas']; ?></strong></div>
                                    <div>Excelente: <strong><?php echo $carrera['excelente']; ?></strong></div>
                                    <div>Conforme: <strong><?php echo $carrera['conforme']; ?></strong></div>
                                    <div>Inconforme: <strong><?php echo $carrera['inconforme']; ?></strong></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Gráficos del Sistema -->
                    <div class="graficos-container">
                        <div class="grafico-card">
                            <h3><i class="bi bi-bar-chart"></i> Distribución por Carrera</h3>
                            <div class="grafico-content">
                                <?php
                                $distribucion = $conexion->query("
                                    SELECT c.nombre, COUNT(e.id_estudiante) as total 
                                    FROM carreras c 
                                    LEFT JOIN estudiantes e ON c.id_carrera = e.id_carrera 
                                    GROUP BY c.id_carrera
                                    ORDER BY total DESC
                                ");
                                
                                if ($distribucion && $distribucion->num_rows > 0):
                                    while($dist = $distribucion->fetch_assoc()):
                                        $porcentaje = $datos_reporte['total_estudiantes'] > 0 ? ($dist['total'] / $datos_reporte['total_estudiantes']) * 100 : 0;
                                ?>
                                <div class="distribucion-item">
                                    <div class="distribucion-info">
                                        <span><?php echo htmlspecialchars($dist['nombre']); ?></span>
                                        <span><?php echo $dist['total']; ?> estudiantes</span>
                                    </div>
                                    <div class="distribucion-bar">
                                        <div class="bar-fill" style="width: <?php echo $porcentaje; ?>%"></div>
                                    </div>
                                    <div class="distribucion-porcentaje"><?php echo round($porcentaje, 1); ?>%</div>
                                </div>
                                <?php endwhile; else: ?>
                                <p style="color: #666; text-align: center;">No hay datos de distribución</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="grafico-card">
                            <h3><i class="bi bi-pie-chart"></i> Información Destacada</h3>
                            <div class="grafico-content">
                                <div class="info-destacada">
                                    <div class="info-item">
                                        <i class="bi bi-trophy"></i>
                                        <div>
                                            <strong>Carrera más popular:</strong><br>
                                            <?php echo htmlspecialchars($datos_reporte['carrera_mas_popular']['nombre'] ?? 'N/A'); ?>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-star"></i>
                                        <div>
                                            <strong>Materia más solicitada:</strong><br>
                                            <?php echo htmlspecialchars($datos_reporte['materia_mas_solicitada']['nombre'] ?? 'N/A'); ?>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <i class="bi bi-calendar-check"></i>
                                        <div>
                                            <strong>Período activo:</strong><br>
                                            <?php 
                                            $periodo_activo = $conexion->query("SELECT nombre FROM periodos_academicos WHERE estado = 'activo' LIMIT 1")->fetch_assoc();
                                            echo htmlspecialchars($periodo_activo['nombre'] ?? 'No hay período activo');
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php elseif ($tipo_reporte == 'encuestas' && is_object($datos_reporte)): ?>
                    <!-- Reporte Detallado de Encuestas -->
                    <?php if ($datos_reporte->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="reporte-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Estudiante</th>
                                    <th>Cédula</th>
                                    <th>Carrera</th>
                                    <th>Satisfacción</th>
                                    <th>Observaciones</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $datos_reporte->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id_encuesta']; ?></td>
                                    <td><?php echo htmlspecialchars($row['estudiante_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($row['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($row['carrera_nombre'] . ' (' . $row['carrera_codigo'] . ')'); ?></td>
                                    <td>
                                        <?php 
                                        $badge_class = '';
                                        switch($row['satisfaccion']) {
                                            case 'Excelente': $badge_class = 'badge-success'; break;
                                            case 'Conforme': $badge_class = 'badge-warning'; break;
                                            case 'Inconforme': $badge_class = 'badge-danger'; break;
                                            default: $badge_class = 'badge-secondary';
                                        }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($row['satisfaccion']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($row['observaciones'] ?? 'Sin observaciones', 0, 50)) . (strlen($row['observaciones'] ?? '') > 50 ? '...' : ''); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['fecha'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="resumen-reporte">
                        <h4><i class="bi bi-info-circle"></i> Resumen del Reporte de Encuestas</h4>
                        <div class="resumen-content">
                            <div class="resumen-item">
                                <i class="bi bi-list-ol"></i>
                                <span>Total de encuestas: <?php echo $datos_reporte->num_rows; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-clipboard-x"></i>
                        <h3>No hay datos de encuestas</h3>
                        <p>No se encontraron encuestas con los filtros aplicados.</p>
                    </div>
                    <?php endif; ?>
                    
                    <?php elseif (is_object($datos_reporte) && $datos_reporte->num_rows > 0): ?>
                    <!-- Reporte Tabular (otros tipos) -->
                    <div class="table-responsive">
                        <table class="reporte-table">
                            <thead>
                                <tr>
                                    <?php 
                                    $first_row = $datos_reporte->fetch_assoc();
                                    $datos_reporte->data_seek(0); // Resetear puntero
                                    
                                    foreach($first_row as $key => $value):
                                        $titulos = [
                                            'id_estudiante' => 'ID Estudiante',
                                            'cedula' => 'Cédula',
                                            'nombre_completo' => 'Nombre Completo',
                                            'correo' => 'Correo',
                                            'estado_usuario' => 'Estado',
                                            'carrera_nombre' => 'Carrera',
                                            'carrera_codigo' => 'Código Carrera',
                                            'año_carrera' => 'Año',
                                            'semestre_actual' => 'Semestre',
                                            'fecha_ingreso' => 'Fecha Ingreso',
                                            'total_matriculas' => 'Total Matrículas',
                                            'total_gastado' => 'Total Gastado',
                                            'id_matricula' => 'ID Matrícula',
                                            'fecha' => 'Fecha',
                                            'estudiante_nombre' => 'Estudiante',
                                            'materia_nombre' => 'Materia',
                                            'materia_codigo' => 'Código Materia',
                                            'costo' => 'Costo',
                                            'periodo_nombre' => 'Período',
                                            'docente_nombre' => 'Docente',
                                            'dia' => 'Día',
                                            'hora_inicio' => 'Hora Inicio',
                                            'hora_fin' => 'Hora Fin',
                                            'aula' => 'Aula',
                                            'periodo' => 'Período',
                                            'carrera' => 'Carrera',
                                            'total_matriculas' => 'Total Matrículas',
                                            'total_estudiantes' => 'Total Estudiantes',
                                            'ingresos_totales' => 'Ingresos Totales',
                                            'promedio_por_materia' => 'Promedio por Materia',
                                            'descripcion' => 'Descripción',
                                            'total_grupos' => 'Total Grupos',
                                            'total_estudiantes' => 'Total Estudiantes',
                                            'ingresos_totales' => 'Ingresos'
                                        ];
                                    ?>
                                    <th><?php echo $titulos[$key] ?? ucfirst(str_replace('_', ' ', $key)); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $datos_reporte->fetch_assoc()): ?>
                                <tr>
                                    <?php foreach($row as $key => $value): ?>
                                    <td>
                                        <?php 
                                        if (in_array($key, ['costo', 'total_gastado', 'ingresos_totales', 'promedio_por_materia']) && is_numeric($value)) {
                                            echo '$' . number_format($value, 2);
                                        } elseif (in_array($key, ['fecha', 'fecha_ingreso']) && $value) {
                                            echo date('d/m/Y H:i', strtotime($value));
                                        } elseif ($key == 'estado_usuario') {
                                            $badge_class = $value == 'activo' ? 'badge-success' : 'badge-warning';
                                            echo '<span class="badge ' . $badge_class . '">' . ucfirst($value) . '</span>';
                                        } else {
                                            echo htmlspecialchars($value ?? '');
                                        }
                                        ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Resumen -->
                    <div class="resumen-reporte">
                        <h4><i class="bi bi-info-circle"></i> Resumen del Reporte</h4>
                        <div class="resumen-content">
                            <div class="resumen-item">
                                <i class="bi bi-list-ol"></i>
                                <span>Total de registros: <?php echo $datos_reporte->num_rows; ?></span>
                            </div>
                            <?php if (in_array($tipo_reporte, ['matriculas', 'ingresos'])): 
                                // Re-ejecutar consulta para obtener ingresos totales
                                $total_ingresos = 0;
                                $datos_reporte->data_seek(0);
                                while($row = $datos_reporte->fetch_assoc()) {
                                    if (isset($row['ingresos_totales'])) {
                                        $total_ingresos += $row['ingresos_totales'];
                                    }
                                }
                                if ($total_ingresos > 0):
                            ?>
                            <div class="resumen-item">
                                <i class="bi bi-cash-coin"></i>
                                <span>Ingresos totales: $<?php echo number_format($total_ingresos, 2); ?></span>
                            </div>
                            <?php endif; endif; ?>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <!-- Sin datos -->
                    <div class="empty-state">
                        <i class="bi bi-file-x"></i>
                        <h3>No hay datos para el reporte seleccionado</h3>
                        <p>No se encontraron registros con los filtros aplicados.</p>
                        <button onclick="window.location.href='reportes.php'" class="btn-action btn-purple">
                            <i class="bi bi-arrow-clockwise"></i> Volver al inicio
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Actualizar fecha_hasta mínima cuando cambia fecha_desde
        document.querySelector('input[name="fecha_desde"]').addEventListener('change', function() {
            const fechaHasta = document.querySelector('input[name="fecha_hasta"]');
            if (fechaHasta.value && this.value > fechaHasta.value) {
                fechaHasta.value = this.value;
            }
            fechaHasta.min = this.value;
        });
        
        // Actualizar fecha_desde máxima cuando cambia fecha_hasta
        document.querySelector('input[name="fecha_hasta"]').addEventListener('change', function() {
            const fechaDesde = document.querySelector('input[name="fecha_desde"]');
            if (fechaDesde.value && this.value < fechaDesde.value) {
                this.value = fechaDesde.value;
            }
        });
        
        <?php if ($tipo_reporte == 'general' && isset($datos_reporte['estadisticas_encuestas'])): ?>
        // Gráfico de satisfacción
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('chartSatisfaccion').getContext('2d');
            
            const data = {
                labels: ['Excelente', 'Conforme', 'Inconforme', 'No respondida'],
                datasets: [{
                    data: [
                        <?php echo $datos_reporte['estadisticas_encuestas']['excelente'] ?? 0; ?>,
                        <?php echo $datos_reporte['estadisticas_encuestas']['conforme'] ?? 0; ?>,
                        <?php echo $datos_reporte['estadisticas_encuestas']['inconforme'] ?? 0; ?>,
                        <?php echo $datos_reporte['estadisticas_encuestas']['no_respondida'] ?? 0; ?>
                    ],
                    backgroundColor: [
                        '#38a169', // Excelente - verde
                        '#3182ce', // Conforme - azul
                        '#e53e3e', // Inconforme - rojo
                        '#a0aec0'  // No respondida - gris
                    ],
                    borderWidth: 1
                }]
            };
            
            const config = {
                type: 'pie',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            };
            
            new Chart(ctx, config);
        });
        <?php endif; ?>
        
        // Estilos para impresión
        const style = document.createElement('style');
        style.innerHTML = `
            @media print {
                .sidebar, .breadcrumb, .filters-card, .header-actions > div:last-child, 
                .reporte-info, .btn-action, .resumen-reporte {
                    display: none !important;
                }
                
                .dashboard-container {
                    display: block !important;
                }
                
                .main-content {
                    padding: 0 !important;
                    margin: 0 !important;
                }
                
                .reporte-container {
                    box-shadow: none !important;
                    border: none !important;
                }
                
                .reporte-header h2 {
                    color: #000 !important;
                }
                
                .reporte-table {
                    font-size: 10px !important;
                }
                
                .estadisticas-grid, .graficos-container {
                    break-inside: avoid;
                }
                
                .chart-container {
                    height: 250px !important;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
<?php 
// Solo cerrar la conexión si está abierta y no se ha cerrado antes
if (isset($conexion) && is_object($conexion) && method_exists($conexion, 'close') && $conexion->thread_id) {
    $conexion->close();
}
?>