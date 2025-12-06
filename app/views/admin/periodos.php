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
    <style>
        /* Estilos Períodos - Sistema UTP */
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

        .periodos-container {
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

        .header-actions h1 i {
            font-size: 32px;
        }

        /* Alertas */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #2d8659 0%, #1a472a 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .alert-close {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s;
        }

        .alert-close:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Estadísticas */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin: 10px 0;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Tabla */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        .periodos-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }

        .periodos-table th {
            background-color: #f8f9fa;
            color: #333;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        .periodos-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #555;
            font-size: 14px;
        }

        .periodos-table tbody tr {
            transition: background 0.2s;
        }

        .periodos-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Badges para estados */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-activo {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactivo {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-planificado {
            background: #fff3cd;
            color: #856404;
        }

        .badge-semestre {
            background: #e9d5ff;
            color: #6B2C91;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Timeline visual */
        .timeline-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            margin: 10px 0;
            position: relative;
            overflow: hidden;
        }

        .timeline-fill {
            height: 100%;
            position: absolute;
            border-radius: 4px;
        }

        .timeline-activo {
            background: linear-gradient(90deg, #2d8659 0%, #34d399 100%);
        }

        .timeline-inactivo {
            background: linear-gradient(90deg, #6B2C91 0%, #a78bfa 100%);
        }

        .timeline-planificado {
            background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
        }

        .timeline-marker {
            position: absolute;
            top: -4px;
            width: 3px;
            height: 16px;
            background: #333;
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }

        /* Fechas */
        .fecha-cell {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .fecha-label {
            font-weight: 600;
            color: #333;
        }

        .fecha-value {
            color: #666;
            font-size: 13px;
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

        /* Resumen */
        .summary-box {
            margin-top: 30px;
            padding: 20px 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            font-size: 14px;
            color: #555;
        }

        .summary-box strong {
            color: #333;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: white;
            margin: 3% auto;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 25px 30px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            color: #6B2C91;
            font-size: 22px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            color: #999;
            cursor: pointer;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .modal-close:hover {
            background: #f0f0f0;
            color: #333;
        }

        .modal-body {
            padding: 30px;
            max-height: 500px;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            background: #fafafa;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #6B2C91;
            background: white;
            box-shadow: 0 0 0 3px rgba(107, 44, 145, 0.1);
        }

        .form-group-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-actions {
            padding: 20px 30px;
            border-top: 2px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
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
            
            .header-actions h1 {
                font-size: 22px;
            }
            
            .header-actions > div {
                flex-wrap: wrap;
                width: 100%;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .periodos-table {
                min-width: 800px;
            }
            
            .form-group-row {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }

        /* Información adicional del período */
        .periodo-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 5px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .info-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        /* Indicador de período actual */
        .periodo-actual {
            position: relative;
        }

        .periodo-actual::before {
            content: "★";
            color: #FFD700;
            position: absolute;
            left: -20px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
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
                
                <!-- Tabla -->
                <div class="table-container">
                    <table class="periodos-table" id="periodosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Período</th>
                                <th>Fechas</th>
                                <th>Duración</th>
                                <th>Estado</th>
                                <th>Acciones</th>
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
                                                    <span class="info-label">Año</span>
                                                    <span class="info-value"><?php echo $periodo['año']; ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label">Semestre</span>
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
                                                <span class="info-label">Inicio</span>
                                                <span class="fecha-label"><?php echo date('d/m/Y', strtotime($periodo['fecha_inicio'])); ?></span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Fin</span>
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
                                            <?php echo ucfirst($periodo['estado']); ?>
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