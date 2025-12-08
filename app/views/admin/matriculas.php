<?php
// app/views/admin/matriculas.php - VERSIÓN COMPLETA CON CRUD (CREAR, EDITAR, ELIMINAR)

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';
require_once '../../utils/validaciones.php';

// Manejar acciones CRUD
$accion = $_GET['accion'] ?? 'listar';
$id_matricula = $_GET['id'] ?? null;
$id_estudiante = $_GET['id_estudiante'] ?? null;
$mensaje = '';

// ========== PROCESAR FORMULARIO (CREAR/EDITAR) ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Sanitizar y validar datos
        $estudiante = Validaciones::sanitizarEntero($_POST['estudiante']);
        $periodo = Validaciones::sanitizarEntero($_POST['periodo']);
        $grupo = Validaciones::sanitizarEntero($_POST['grupo']);
        
        Validaciones::validarNoVacio($estudiante, 'estudiante');
        Validaciones::validarNoVacio($periodo, 'período');
        Validaciones::validarNoVacio($grupo, 'grupo');
        
        // Obtener código del período (año-semestre)
        $stmt_periodo = $conexion->prepare("
            SELECT CONCAT(año, '-', semestre) as codigo_periodo 
            FROM periodos_academicos 
            WHERE id_periodo = ?
        ");
        $stmt_periodo->bind_param("i", $periodo);
        $stmt_periodo->execute();
        $periodo_result = $stmt_periodo->get_result()->fetch_assoc();
        
        if (!$periodo_result) {
            throw new Exception("Período no válido");
        }
        
        $codigo_periodo = $periodo_result['codigo_periodo'];
        
        if (isset($_POST['id_matricula'])) {
            // ========== ACTUALIZAR MATRÍCULA ==========
            $id_mat = Validaciones::sanitizarEntero($_POST['id_matricula']);
            
            // Verificar si el grupo ya está asignado a otro estudiante en el mismo período
            $stmt_check = $conexion->prepare("
                SELECT COUNT(*) as total 
                FROM matriculas 
                WHERE id_ghm = ? 
                AND id_periodo = ? 
                AND id_estudiante != ?
                AND id_matricula != ?
            ");
            $stmt_check->bind_param("isii", $grupo, $codigo_periodo, $estudiante, $id_mat);
            $stmt_check->execute();
            $result = $stmt_check->get_result()->fetch_assoc();
            
            if ($result['total'] > 0) {
                throw new Exception("Este grupo ya está asignado a otro estudiante en el mismo período");
            }
            
            // Actualizar matrícula
            $stmt = $conexion->prepare("
                UPDATE matriculas SET 
                id_estudiante = ?, id_ghm = ?, id_periodo = ?, fecha = NOW()
                WHERE id_matricula = ?
            ");
            $stmt->bind_param("iisi", $estudiante, $grupo, $codigo_periodo, $id_mat);
            $stmt->execute();
            
            $mensaje = '<div class="alert alert-success">Matrícula actualizada correctamente</div>';
            
            // Auditoría
            $audit = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
            $accion_audit = "Actualizó matrícula ID: $id_mat";
            $audit->bind_param("ss", $_SESSION['user_name'], $accion_audit);
            $audit->execute();
            
        } else {
            // ========== CREAR NUEVA MATRÍCULA ==========
            // Verificar si ya está matriculado en este grupo en el mismo período
            $stmt_check = $conexion->prepare("
                SELECT COUNT(*) as total 
                FROM matriculas 
                WHERE id_estudiante = ? AND id_ghm = ? AND id_periodo = ?
            ");
            $stmt_check->bind_param("iis", $estudiante, $grupo, $codigo_periodo);
            $stmt_check->execute();
            $result = $stmt_check->get_result()->fetch_assoc();
            
            if ($result['total'] > 0) {
                throw new Exception("El estudiante ya está matriculado en este grupo en este período");
            }
            
            // Insertar la matrícula
            $stmt = $conexion->prepare("
                INSERT INTO matriculas (id_estudiante, id_ghm, id_periodo, fecha) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->bind_param("iis", $estudiante, $grupo, $codigo_periodo);
            $stmt->execute();
            
            $mensaje = '<div class="alert alert-success">Matrícula creada correctamente</div>';
            
            // Auditoría
            $audit = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
            $accion_audit = "Creó matrícula - Estudiante: $estudiante, Grupo: $grupo, Período: $codigo_periodo";
            $audit->bind_param("ss", $_SESSION['user_name'], $accion_audit);
            $audit->execute();
        }
        
        // Redirigir después de guardar
        header('Location: matriculas.php?mensaje=' . urlencode('Operación exitosa') . ($id_estudiante ? '&id_estudiante=' . $id_estudiante : ''));
        exit();
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// ========== OBTENER MATRÍCULA PARA EDITAR ==========
$matricula_editar = null;
if ($id_matricula && $accion == 'editar') {
    $stmt = $conexion->prepare("
        SELECT mt.*, 
               p.id_periodo as periodo_id,
               CONCAT(p.año, '-', p.semestre) as periodo_codigo
        FROM matriculas mt
        JOIN periodos_academicos p ON mt.id_periodo = p.id_periodo
        WHERE mt.id_matricula = ?
    ");
    $stmt->bind_param("i", $id_matricula);
    $stmt->execute();
    $matricula_editar = $stmt->get_result()->fetch_assoc();
}

// ========== ELIMINAR MATRÍCULA ==========
if ($accion === 'eliminar' && $id_matricula) {
    try {
        // Eliminar matrícula
        $stmt = $conexion->prepare("DELETE FROM matriculas WHERE id_matricula = ?");
        $stmt->bind_param("i", $id_matricula);
        $stmt->execute();
        
        // Registrar en auditoría
        $audit_stmt = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
        $accion_audit = "Eliminó matrícula ID: $id_matricula";
        $audit_stmt->bind_param("ss", $_SESSION['user_name'], $accion_audit);
        $audit_stmt->execute();
        
        $mensaje = '<div class="alert alert-success">Matrícula eliminada exitosamente.</div>';
        header('Location: matriculas.php?mensaje=' . urlencode('Matrícula eliminada exitosamente') . ($id_estudiante ? '&id_estudiante=' . $id_estudiante : ''));
        exit();
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// Obtener filtros
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

// Obtener información de la carrera si se especifica
// Obtener información de la carrera si se especifica
$id_carrera = null;
$carrera_info = null;

// Determinar id_carrera basado en el contexto
if (!empty($estudiante_info['id_carrera'])) {
    $id_carrera = $estudiante_info['id_carrera'];
} elseif (!empty($filtro_carrera)) {
    $id_carrera = $filtro_carrera;
}

if ($id_carrera) {
    $stmt = $conexion->prepare("SELECT * FROM carreras WHERE id_carrera = ?");
    $stmt->bind_param("i", $id_carrera);
    $stmt->execute();
    $carrera_info = $stmt->get_result()->fetch_assoc();
}

// Obtener carreras y docentes para formularios
$carreras = $conexion->query("SELECT id_carrera, nombre FROM carreras WHERE estado = 'activa' ORDER BY nombre");
$docentes = $conexion->query("SELECT id_docente, nombre, apellido FROM docentes WHERE estado = 'activo' ORDER BY apellido, nombre");

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

// Obtener datos para filtros
$periodos = $conexion->query("SELECT id_periodo, nombre, año, semestre FROM periodos_academicos ORDER BY año DESC, semestre DESC");
$carreras = $conexion->query("SELECT id_carrera, nombre, codigo FROM carreras WHERE estado = 'activa' ORDER BY nombre");

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

// Obtener estudiantes y períodos activos para formularios
$estudiantes_activos = $conexion->query("
    SELECT e.id_estudiante, CONCAT(u.nombre, ' ', u.apellido) as nombre, e.cedula, c.nombre as carrera
    FROM estudiantes e
    JOIN usuario u ON e.id_usuario = u.id_usuario
    LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
    WHERE u.estado = 'activo'
    ORDER BY u.apellido, u.nombre
");

$periodos_activos = $conexion->query("SELECT * FROM periodos_academicos WHERE estado = 'activo' ORDER BY año DESC, semestre DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matrículas - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* ESTILOS COMPLETOS INCLUIDOS - Mismos estilos que antes */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: #1e3c72;
            color: white;
            padding: 20px 0;
        }
        
        .logo {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .logo h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .logo small {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .user-info {
            padding: 0 20px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .avatar {
            width: 60px;
            height: 60px;
            background: #38a169;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 10px;
        }
        
        .user-info h3 {
            font-size: 1rem;
            margin: 5px 0;
        }
        
        .user-info p {
            font-size: 0.8rem;
            opacity: 0.8;
            margin: 0;
        }
        
        .nav-menu {
            padding: 0 10px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .logout {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .matriculas-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .breadcrumb {
            margin-bottom: 25px;
            font-size: 14px;
            color: #666;
        }
        
        .breadcrumb a {
            color: #1e3c72;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header-actions h1 {
            font-size: 1.8rem;
            color: #1e3c72;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .btn-action:hover {
            background: #5a6268;
            color: white;
        }
        
        .btn-green {
            background: #28a745;
        }
        
        .btn-green:hover {
            background: #218838;
        }
        
        .btn-purple {
            background: #6f42c1;
        }
        
        .btn-purple:hover {
            background: #5a3792;
        }
        
        .btn-blue {
            background: #007bff;
        }
        
        .btn-blue:hover {
            background: #0056b3;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        
        .alert-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: inherit;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            border-top: 4px solid #1e3c72;
        }
        
        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #1e3c72;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filter-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-form select,
        .filter-form input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 180px;
        }
        
        .filter-form button {
            padding: 10px 15px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 25px;
        }
        
        .matriculas-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .matriculas-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        
        .matriculas-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        
        .matriculas-table tr:hover {
            background: #f8f9fa;
        }
        
        .horario-cell {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .horario-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .badge-aula {
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .badge-horario {
            background: #f3e5f5;
            color: #7b1fa2;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .dia-semana {
            font-weight: 500;
            min-width: 60px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            color: #495057;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        
        .empty-state p {
            margin-bottom: 25px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .summary-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            color: #495057;
            font-size: 0.9rem;
        }
        
        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalAppear 0.3s;
        }
        
        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #1e3c72;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .grupo-option {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .grupo-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        
        .grupo-option.selected {
            border-color: #28a745;
            background: #f0fff4;
        }
        
        .grupo-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .grupo-codigo {
            font-weight: 500;
            color: #1e3c72;
        }
        
        .grupo-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .grupo-details div {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-actions {
            padding: 20px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                position: static;
            }
            
            .header-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-form select,
            .filter-form input {
                min-width: 100%;
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
                    
                    <?php if ($accion != 'editar'): ?>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
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
                    <?php endif; ?>
                </div>
                
                <!-- Mensajes -->
                <?php echo $mensaje; ?>
                
                <?php if (isset($_GET['mensaje'])): ?>
                <div class="alert alert-success">
                    <span><?php echo htmlspecialchars($_GET['mensaje'] ?? ''); ?></span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <span><?php echo htmlspecialchars($_GET['error'] ?? ''); ?></span>
                    <button class="alert-close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php endif; ?>
                
                <?php if ($accion == 'editar'): ?>
                    <!-- FORMULARIO DE EDICIÓN -->
                    <div class="card" style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 25px;">
                        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0;">
                            <h2 style="margin: 0; color: #333; display: flex; align-items: center; gap: 10px;">
                                <i class="bi bi-pencil"></i> Editar Matrícula #<?php echo $matricula_editar['id_matricula'] ?? ''; ?>
                            </h2>
                            <a href="matriculas.php<?php echo $id_estudiante ? '?id_estudiante='.$id_estudiante : ''; ?>" class="btn-action">Cancelar</a>
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="id_matricula" value="<?php echo $matricula_editar['id_matricula'] ?? ''; ?>">
                            
                            <div class="form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
                                <div class="form-group">
                                    <label>Estudiante *</label>
                                    <select name="estudiante" required onchange="cargarMateriasDisponibles()">
                                        <option value="">Seleccionar estudiante...</option>
                                        <?php
                                        $estudiantes_activos->data_seek(0);
                                        while($est = $estudiantes_activos->fetch_assoc()):
                                        ?>
                                        <option value="<?php echo $est['id_estudiante']; ?>"
                                            <?php echo ($matricula_editar['id_estudiante'] ?? 0) == $est['id_estudiante'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($est['nombre'] . ' - ' . $est['cedula'] . ' (' . ($est['carrera'] ?? 'Sin carrera') . ')'); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Período Académico *</label>
                                    <select name="periodo" required onchange="cargarMateriasDisponibles()">
                                        <option value="">Seleccionar período...</option>
                                        <?php
                                        $periodos_activos->data_seek(0);
                                        while($periodo = $periodos_activos->fetch_assoc()):
                                        ?>
                                        <option value="<?php echo $periodo['id_periodo']; ?>"
                                            <?php echo ($matricula_editar['periodo_id'] ?? 0) == $periodo['id_periodo'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($periodo['nombre'] . ' (' . $periodo['año'] . '-' . $periodo['semestre'] . ')'); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div id="grupos-container-editar" style="display: <?php echo $matricula_editar ? 'block' : 'none'; ?>;">
                                <h4 style="margin-bottom: 15px; color: #1e3c72;">Seleccionar Grupo Disponible</h4>
                                <div id="grupos-list-editar">
                                    <!-- Los grupos se cargarán aquí dinámicamente -->
                                </div>
                                <input type="hidden" id="grupo_seleccionado_editar" name="grupo" 
                                       value="<?php echo $matricula_editar['id_ghm'] ?? ''; ?>">
                            </div>
                            
                            <div style="margin-top: 20px; display: flex; gap: 10px;">
                                <button type="submit" class="btn-action btn-green" id="btnGuardarEditar">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                                <a href="matriculas.php<?php echo $id_estudiante ? '?id_estudiante='.$id_estudiante : ''; ?>" 
                                   class="btn-action">Cancelar</a>
                            </div>
                        </form>
                    </div>
                    
                    <script>
                        // Cargar grupos automáticamente al editar
                        document.addEventListener('DOMContentLoaded', function() {
                            const estudianteSelect = document.querySelector('select[name="estudiante"]');
                            const periodoSelect = document.querySelector('select[name="periodo"]');
                            
                            if (estudianteSelect.value && periodoSelect.value) {
                                cargarGruposParaEditar();
                            }
                        });
                        
                        function cargarGruposParaEditar() {
                            const estudianteId = document.querySelector('select[name="estudiante"]').value;
                            const periodoId = document.querySelector('select[name="periodo"]').value;
                            const gruposList = document.getElementById('grupos-list-editar');
                            const gruposContainer = document.getElementById('grupos-container-editar');
                            const grupoSeleccionado = document.getElementById('grupo_seleccionado_editar').value;
                            
                            if (!estudianteId || !periodoId) {
                                gruposContainer.style.display = 'none';
                                return;
                            }
                            
                            // Mostrar cargando
                            gruposList.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="bi bi-hourglass"></i> Cargando grupos disponibles...</div>';
                            gruposContainer.style.display = 'block';
                            
                            // Hacer petición AJAX
                            fetch(`obtener_grupos_disponibles.php?estudiante=${estudianteId}&periodo=${periodoId}`)
                                .then(response => response.json())
                                .then(data => {
                                    gruposList.innerHTML = '';
                                    
                                    if (data.error) {
                                        gruposList.innerHTML = `<div class="alert" style="background: #fed7d7; color: #742a2a;">${data.error}</div>`;
                                        return;
                                    }
                                    
                                    if (data.length === 0) {
                                        gruposList.innerHTML = '<div class="alert alert-warning">No hay grupos disponibles</div>';
                                        return;
                                    }
                                    
                                    data.forEach(grupo => {
                                        const grupoDiv = document.createElement('div');
                                        grupoDiv.className = 'grupo-option';
                                        if (grupo.id_ghm == grupoSeleccionado) {
                                            grupoDiv.classList.add('selected');
                                        }
                                        
                                        grupoDiv.innerHTML = `
                                            <div class="grupo-header">
                                                <div class="grupo-codigo">${grupo.materia_codigo} - ${grupo.materia_nombre}</div>
                                                <div><strong>$${grupo.costo ? parseFloat(grupo.costo).toFixed(2) : '0.00'}</strong></div>
                                            </div>
                                            <div class="grupo-details">
                                                <div><i class="bi bi-person"></i> ${grupo.docente_nombre || 'Sin asignar'}</div>
                                                <div><i class="bi bi-geo-alt"></i> ${grupo.aula || 'Sin aula asignada'}</div>
                                                <div><i class="bi bi-calendar-week"></i> ${grupo.dia || 'Sin día'} ${grupo.hora_inicio || ''} - ${grupo.hora_fin || ''}</div>
                                                <div><i class="bi bi-layers"></i> Grupo ${grupo.id_ghm}</div>
                                            </div>
                                        `;
                                        
                                        grupoDiv.onclick = () => {
                                            // Deseleccionar todos
                                            document.querySelectorAll('#grupos-list-editar .grupo-option').forEach(g => g.classList.remove('selected'));
                                            // Seleccionar este
                                            grupoDiv.classList.add('selected');
                                            document.getElementById('grupo_seleccionado_editar').value = grupo.id_ghm;
                                        };
                                        
                                        gruposList.appendChild(grupoDiv);
                                    });
                                })
                                .catch(error => {
                                    gruposList.innerHTML = `<div class="alert" style="background: #fed7d7; color: #742a2a;">Error al cargar grupos: ${error.message}</div>`;
                                });
                        }
                        
                        // Escuchar cambios en los selects
                        document.querySelector('select[name="estudiante"]').onchange = cargarGruposParaEditar;
                        document.querySelector('select[name="periodo"]').onchange = cargarGruposParaEditar;
                    </script>
                    
                <?php else: ?>
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
                    
                    <!-- Filtros -->
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
                            
                            <button type="button" onclick="limpiarFiltros()" class="btn-action">
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
                                                    <span class="badge-aula"><?php echo htmlspecialchars($matricula['aula']); ?></span>
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
                                                <a href="?accion=editar&id=<?php echo $matricula['id_matricula']; ?><?php echo $id_estudiante ? '&id_estudiante=' . $id_estudiante : ''; ?>" 
                                                   class="btn-action btn-warning btn-sm" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?accion=eliminar&id=<?php echo $matricula['id_matricula']; ?><?php echo $id_estudiante ? '&id_estudiante=' . $id_estudiante : ''; ?>" 
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
                    
                    <!-- Modal para nueva matrícula -->
                    <div class="modal" id="modalNuevaMatricula">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3><i class="bi bi-plus-circle"></i> Nueva Matrícula</h3>
                                <button class="modal-close" onclick="cerrarModal()">&times;</button>
                            </div>
                            <form method="POST" action="">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="estudiante">Estudiante *</label>
                                        <select id="estudiante" name="estudiante" required onchange="cargarMateriasDisponibles()">
                                            <option value="">Seleccionar estudiante...</option>
                                            <?php
                                            $estudiantes_activos->data_seek(0);
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
                                            $periodos_activos->data_seek(0);
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
                    
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Modal para nueva matrícula
        function mostrarModalNuevaMatricula() {
            document.getElementById('modalNuevaMatricula').style.display = 'flex';
        }
        
        function cerrarModal() {
            document.getElementById('modalNuevaMatricula').style.display = 'none';
            document.getElementById('grupos-container').style.display = 'none';
            document.getElementById('sin-grupos').style.display = 'none';
            document.getElementById('btnGuardar').disabled = true;
            document.getElementById('grupo_seleccionado').value = '';
            // Resetear selects
            document.getElementById('estudiante').selectedIndex = 0;
            document.getElementById('periodo').selectedIndex = 0;
        }
        
        // Cargar materias disponibles para el estudiante (para nueva matrícula)
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
            btnGuardar.disabled = true;
            document.getElementById('grupo_seleccionado').value = '';
            
            // Hacer petición AJAX
            fetch(`obtener_grupos_disponibles.php?estudiante=${estudianteId}&periodo=${periodoId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status}`);
                    }
                    return response.text().then(text => {
                        console.log("Respuesta cruda:", text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error("Error parseando JSON:", e);
                            throw new Error("El servidor devolvió una respuesta inválida");
                        }
                    });
                })
                .then(data => {
                    gruposList.innerHTML = '';
                    
                    if (data.error) {
                        gruposList.innerHTML = `<div class="alert" style="background: #fed7d7; color: #742a2a; padding: 10px; border-radius: 5px;">${data.error}</div>`;
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
                                <div><strong>$${grupo.costo ? parseFloat(grupo.costo).toFixed(2) : '0.00'}</strong></div>
                            </div>
                            <div class="grupo-details">
                                <div><i class="bi bi-person"></i> ${grupo.docente_nombre || 'Sin asignar'}</div>
                                <div><i class="bi bi-geo-alt"></i> ${grupo.aula || 'Sin aula asignada'}</div>
                                <div><i class="bi bi-calendar-week"></i> ${grupo.dia || 'Sin día'} ${grupo.hora_inicio || ''} - ${grupo.hora_fin || ''}</div>
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
                    console.error('Error en fetch:', error);
                    gruposList.innerHTML = `<div class="alert" style="background: #fed7d7; color: #742a2a; padding: 10px; border-radius: 5px;">
                        Error al cargar grupos: ${error.message}<br>
                        Verifica la consola del navegador para más detalles.
                    </div>`;
                });
        }
        
        // Limpiar filtros
        function limpiarFiltros() {
            const form = document.getElementById('filterForm');
            form.reset();
            // Quitar parámetros de la URL sin recargar
            const url = new URL(window.location);
            url.searchParams.delete('periodo');
            url.searchParams.delete('carrera');
            url.searchParams.delete('estudiante');
            url.searchParams.delete('materia');
            window.location.href = url.toString();
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