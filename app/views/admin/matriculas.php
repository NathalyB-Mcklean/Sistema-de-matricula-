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
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- CSS del dashboard -->
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/dashboardadmin.css">
    <!-- CSS específico para matrículas -->
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/matriculas.css">
    <style>
        /* ESTILOS ADICIONALES PARA CORREGIR BOTONES EN TABLA - ARREGLADOS */
        .matriculas-table {
            table-layout: fixed;
        }
        
        .matriculas-table th:nth-child(1) { width: 60px; } /* ID */
        .matriculas-table th:nth-child(2) { width: 180px; } /* Estudiante */
        .matriculas-table th:nth-child(3) { width: 220px; } /* Materia y Carrera */
        .matriculas-table th:nth-child(4) { width: 180px; } /* Grupo - Horario */
        .matriculas-table th:nth-child(5) { width: 150px; } /* Docente */
        .matriculas-table th:nth-child(6) { width: 100px; } /* Periodo */
        .matriculas-table th:nth-child(7) { width: 90px; } /* Costo */
        .matriculas-table th:nth-child(8) { width: 140px; } /* Fecha */
        .matriculas-table th:nth-child(9) { width: 160px; } /* Acciones - AUMENTADO */
        
        /* Contenedor para botones - MEJORADO */
        .acciones-tabla {
            display: flex !important;
            gap: 8px !important;
            align-items: center !important;
            justify-content: center !important;
            flex-wrap: nowrap !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            min-width: 150px !important;
        }
        
        /* Botones dentro de la tabla - MEJORADOS */
        .btn-tabla {
            padding: 6px 12px !important;
            border: none !important;
            border-radius: 5px !important;
            font-size: 12px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            white-space: nowrap !important;
            min-width: 68px !important;
            height: 32px !important;
            text-decoration: none !important;
            flex-shrink: 0 !important;
        }
        
        .btn-tabla i {
            margin-right: 4px !important;
            font-size: 11px !important;
        }
        
        .btn-editar {
            background-color: #e7f3ff !important;
            color: #0d6efd !important;
            border: 1px solid #b6d4fe !important;
        }
        
        .btn-editar:hover {
            background-color: #cfe2ff !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 4px rgba(13, 110, 253, 0.2) !important;
        }
        
        .btn-eliminar {
            background-color: #f8d7da !important;
            color: #dc3545 !important;
            border: 1px solid #f5c2c7 !important;
        }
        
        .btn-eliminar:hover {
            background-color: #f5c6cb !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2) !important;
        }
        
        /* Asegurar que la celda de acciones tenga suficiente espacio */
        .matriculas-table td:last-child {
            padding: 8px 5px !important;
            overflow: visible !important;
            position: relative !important;
        }
        
        /* Responsive para botones - MEJORADO */
        @media (max-width: 1400px) {
            .matriculas-table th:nth-child(2) { width: 160px; } /* Estudiante */
            .matriculas-table th:nth-child(3) { width: 200px; } /* Materia y Carrera */
            .matriculas-table th:nth-child(4) { width: 160px; } /* Grupo - Horario */
            .matriculas-table th:nth-child(5) { width: 130px; } /* Docente */
            .matriculas-table th:last-child { width: 150px; } /* Acciones */
        }
        
        @media (max-width: 1200px) {
            .matriculas-table th:nth-child(9) { width: 140px; } /* Acciones */
            .acciones-tabla { min-width: 130px !important; }
            .btn-tabla { min-width: 60px !important; padding: 5px 10px !important; }
        }
        
        @media (max-width: 768px) {
            .matriculas-table th:last-child,
            .matriculas-table td:last-child {
                width: 120px !important;
                min-width: 120px !important;
            }
            
            .acciones-tabla {
                flex-direction: column !important;
                gap: 5px !important;
                min-width: 100px !important;
            }
            
            .btn-tabla {
                width: 100% !important;
                min-width: 85px !important;
                padding: 5px 8px !important;
            }
        }
        
        /* Estilos para grupos en edición */
        .grupo-option-editar {
            border: 2px solid #e0e0e0 !important;
            border-radius: 10px !important;
            padding: 20px !important;
            cursor: pointer !important;
            transition: all 0.3s !important;
            background: white !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .grupo-option-editar:hover {
            border-color: #6B2C91 !important;
            background: #f9f5ff !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 6px 15px rgba(107, 44, 145, 0.1) !important;
        }
        
        .grupo-option-editar.selected {
            border-color: #2d8659 !important;
            background: #f0fff4 !important;
            box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.15) !important;
        }
        
        .grupo-option-editar.selected::before {
            content: '✓' !important;
            position: absolute !important;
            top: 10px !important;
            right: 10px !important;
            width: 25px !important;
            height: 25px !important;
            background: #2d8659 !important;
            color: white !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-weight: bold !important;
            font-size: 14px !important;
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
                    <!-- FORMULARIO DE EDICIÓN - VERSIÓN CORREGIDA -->
                    <div style="background: white; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 30px; overflow: hidden;">
                        <!-- Header del formulario -->
                        <div style="background: linear-gradient(135deg, #6B2C91 0%, #4a1e6e 100%); color: white; padding: 20px 30px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h2 style="margin: 0; color: white; display: flex; align-items: center; gap: 10px;">
                                        <i class="bi bi-pencil-square"></i> Editar Matrícula #<?php echo $matricula_editar['id_matricula'] ?? ''; ?>
                                    </h2>
                                    <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">
                                        Modifica los datos de la matrícula seleccionada
                                    </p>
                                </div>
                                <a href="matriculas.php<?php echo $id_estudiante ? '?id_estudiante='.$id_estudiante : ''; ?>" 
                                   class="btn-action" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); text-decoration: none;">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                            </div>
                        </div>
                        
                        <form method="POST" action="" style="padding: 30px;">
                            <input type="hidden" name="id_matricula" value="<?php echo $matricula_editar['id_matricula'] ?? ''; ?>">
                            
                            <!-- Información actual -->
                            <?php if ($matricula_editar): ?>
                            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #6B2C91;">
                                <h4 style="margin: 0 0 15px 0; color: #6B2C91; display: flex; align-items: center; gap: 8px;">
                                    <i class="bi bi-info-circle"></i> Matrícula Actual
                                </h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                                    <div>
                                        <span style="font-weight: 600; color: #666; font-size: 13px;">Estudiante:</span>
                                        <div style="color: #333; font-weight: 500;">
                                            <?php 
                                            // Obtener nombre del estudiante actual
                                            $stmt_est = $conexion->prepare("SELECT CONCAT(u.nombre, ' ', u.apellido) as nombre_completo, e.cedula 
                                                                           FROM estudiantes e 
                                                                           JOIN usuario u ON e.id_usuario = u.id_usuario 
                                                                           WHERE e.id_estudiante = ?");
                                            $stmt_est->bind_param("i", $matricula_editar['id_estudiante']);
                                            $stmt_est->execute();
                                            $est_actual = $stmt_est->get_result()->fetch_assoc();
                                            echo htmlspecialchars($est_actual['nombre_completo'] ?? '') . ' - ' . htmlspecialchars($est_actual['cedula'] ?? '');
                                            ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span style="font-weight: 600; color: #666; font-size: 13px;">Período:</span>
                                        <div style="color: #333; font-weight: 500;">
                                            <?php echo $matricula_editar['periodo_codigo'] ?? ''; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span style="font-weight: 600; color: #666; font-size: 13px;">Fecha:</span>
                                        <div style="color: #333; font-weight: 500;">
                                            <?php echo date('d/m/Y H:i', strtotime($matricula_editar['fecha'] ?? '')); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Campos del formulario -->
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #6B2C91; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                                    <i class="bi bi-gear"></i> Modificar Datos
                                </h3>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 25px;">
                                    <!-- Estudiante -->
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">
                                            <i class="bi bi-person"></i> Estudiante *
                                        </label>
                                        <select name="estudiante" required onchange="cargarGruposParaEditar()" 
                                                style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; background: white; transition: all 0.3s;"
                                                onfocus="this.style.borderColor='#6B2C91'; this.style.boxShadow='0 0 0 3px rgba(107, 44, 145, 0.1)';"
                                                onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none';">
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
                                    
                                    <!-- Período Académico -->
                                    <div>
                                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">
                                            <i class="bi bi-calendar"></i> Período Académico *
                                        </label>
                                        <select name="periodo" required onchange="cargarGruposParaEditar()" 
                                                style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px; background: white; transition: all 0.3s;"
                                                onfocus="this.style.borderColor='#6B2C91'; this.style.boxShadow='0 0 0 3px rgba(107, 44, 145, 0.1)';"
                                                onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none';">
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
                                
                                <!-- Nota informativa -->
                                <div style="background: #e3f2fd; padding: 15px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #2196f3;">
                                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                                        <i class="bi bi-info-circle" style="color: #2196f3; font-size: 16px; margin-top: 2px;"></i>
                                        <div style="font-size: 13px; color: #0d47a1;">
                                            <strong>Nota:</strong> Al cambiar el estudiante o período, se mostrarán los grupos disponibles para la nueva selección.
                                            El grupo actualmente seleccionado es: <strong>ID <?php echo $matricula_editar['id_ghm'] ?? 'N/A'; ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contenedor de grupos disponibles -->
                            <div id="grupos-container-editar" style="display: <?php echo $matricula_editar ? 'block' : 'none'; ?>; margin-top: 30px;">
                                <h3 style="color: #6B2C91; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; display: flex; align-items: center; gap: 10px;">
                                    <i class="bi bi-layers"></i> Seleccionar Grupo Disponible
                                </h3>
                                
                                <div id="grupos-list-editar" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; max-height: 500px; overflow-y: auto; padding: 10px;">
                                    <!-- Los grupos se cargarán aquí dinámicamente -->
                                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px;">
                                        <div style="display: inline-block; padding: 20px;">
                                            <i class="bi bi-hourglass" style="font-size: 32px; color: #6B2C91;"></i>
                                            <p style="margin-top: 15px; color: #666;">Selecciona un estudiante y período para ver los grupos disponibles</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <input type="hidden" id="grupo_seleccionado_editar" name="grupo" 
                                       value="<?php echo $matricula_editar['id_ghm'] ?? ''; ?>">
                                
                                <div style="margin-top: 15px; font-size: 13px; color: #666; display: flex; align-items: center; gap: 8px; padding: 12px 15px; background: #f8f9fa; border-radius: 6px;">
                                    <i class="bi bi-info-circle"></i>
                                    <span>Haz clic en un grupo para seleccionarlo. El grupo seleccionado aparecerá con borde verde.</span>
                                </div>
                            </div>
                            
                            <!-- Mensaje cuando no hay grupos -->
                            <div id="sin-grupos-editar" style="display: none; text-align: center; padding: 40px 20px; background: #f8f9fa; border-radius: 8px; margin-top: 20px; border: 2px dashed #dee2e6;">
                                <i class="bi bi-exclamation-circle" style="font-size: 48px; color: #6c757d; margin-bottom: 15px;"></i>
                                <h4 style="color: #495057; margin-bottom: 10px;">No hay grupos disponibles</h4>
                                <p style="color: #6c757d; margin-bottom: 0; max-width: 500px; margin-left: auto; margin-right: auto;">
                                    No hay grupos disponibles para el estudiante seleccionado en este período. 
                                    Por favor, verifica que el estudiante cumpla con los prerrequisitos o intenta con otro período.
                                </p>
                            </div>
                            
                            <!-- Botones de acción -->
                            <div style="margin-top: 40px; display: flex; gap: 15px; padding-top: 25px; border-top: 2px solid #f0f0f0;">
                                <button type="submit" class="btn-action btn-green" id="btnGuardarEditar" 
                                        style="padding: 14px 30px; font-size: 15px; flex: 1; max-width: 250px;"
                                        <?php echo $matricula_editar['id_ghm'] ? '' : 'disabled'; ?>>
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                                <a href="matriculas.php<?php echo $id_estudiante ? '?id_estudiante='.$id_estudiante : ''; ?>" 
                                   class="btn-action" style="padding: 14px 30px; font-size: 15px; flex: 1; max-width: 150px; background: #6c757d; text-decoration: none;">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
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
                            const sinGrupos = document.getElementById('sin-grupos-editar');
                            const grupoSeleccionado = document.getElementById('grupo_seleccionado_editar').value;
                            const btnGuardar = document.getElementById('btnGuardarEditar');
                            
                            if (!estudianteId || !periodoId) {
                                gruposContainer.style.display = 'block';
                                sinGrupos.style.display = 'none';
                                gruposList.innerHTML = `
                                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px 20px;">
                                        <div style="display: inline-block; padding: 20px;">
                                            <i class="bi bi-hourglass" style="font-size: 32px; color: #6B2C91;"></i>
                                            <p style="margin-top: 15px; color: #666;">Selecciona un estudiante y período para ver los grupos disponibles</p>
                                        </div>
                                    </div>
                                `;
                                return;
                            }
                            
                            // Mostrar cargando
                            gruposList.innerHTML = `
                                <div style="grid-column: 1 / -1; text-align: center; padding: 50px 20px;">
                                    <div style="display: inline-block; padding: 30px; background: #f8f9fa; border-radius: 10px;">
                                        <div style="width: 50px; height: 50px; border: 3px solid #f3f3f3; border-top: 3px solid #6B2C91; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px;"></div>
                                        <style>@keyframes spin {0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); }}</style>
                                        <p style="margin-top: 15px; color: #666; font-weight: 500;">Buscando grupos disponibles...</p>
                                    </div>
                                </div>
                            `;
                            
                            gruposContainer.style.display = 'block';
                            sinGrupos.style.display = 'none';
                            btnGuardar.disabled = true;
                            
                            // Hacer petición AJAX
                            fetch(`obtener_grupos_disponibles.php?estudiante=${estudianteId}&periodo=${periodoId}`)
                                .then(response => response.json())
                                .then(data => {
                                    gruposList.innerHTML = '';
                                    
                                    if (data.error) {
                                        sinGrupos.innerHTML = `
                                            <i class="bi bi-exclamation-triangle" style="font-size: 48px; color: #dc3545;"></i>
                                            <h4 style="color: #dc3545; margin-bottom: 10px;">Error al cargar grupos</h4>
                                            <p style="color: #721c24;">${data.error}</p>
                                            <div style="margin-top: 20px;">
                                                <button onclick="cargarGruposParaEditar()" class="btn-action" style="background: #6c757d; padding: 10px 20px;">
                                                    <i class="bi bi-arrow-clockwise"></i> Reintentar
                                                </button>
                                            </div>
                                        `;
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
                                        grupoDiv.className = 'grupo-option-editar';
                                        if (grupo.id_ghm == grupoSeleccionado) {
                                            grupoDiv.classList.add('selected');
                                            btnGuardar.disabled = false;
                                        }
                                        
                                        // Formatear el día
                                        const diasMap = {
                                            'Monday': 'Lunes',
                                            'Tuesday': 'Martes',
                                            'Wednesday': 'Miércoles',
                                            'Thursday': 'Jueves',
                                            'Friday': 'Viernes',
                                            'Saturday': 'Sábado',
                                            'Sunday': 'Domingo'
                                        };
                                        const diaFormateado = diasMap[grupo.dia] || grupo.dia;
                                        
                                        grupoDiv.innerHTML = `
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;">
                                                <div style="font-weight: 600; color: #333; font-size: 16px; flex: 1; line-height: 1.3;">
                                                    ${grupo.materia_codigo} - ${grupo.materia_nombre}
                                                </div>
                                                <div style="font-weight: bold; color: #2d8659; font-size: 18px; background: #e8f5e9; padding: 5px 12px; border-radius: 20px; white-space: nowrap;">
                                                    $${grupo.costo ? parseFloat(grupo.costo).toFixed(2) : '0.00'}
                                                </div>
                                            </div>
                                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 13px; color: #666;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <i class="bi bi-person" style="color: #6B2C91; font-size: 14px; min-width: 16px;"></i> 
                                                    ${grupo.docente_nombre || 'Sin asignar'}
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <i class="bi bi-geo-alt" style="color: #6B2C91; font-size: 14px; min-width: 16px;"></i> 
                                                    ${grupo.aula || 'Sin aula asignada'}
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <i class="bi bi-calendar-week" style="color: #6B2C91; font-size: 14px; min-width: 16px;"></i> 
                                                    ${diaFormateado} ${grupo.hora_inicio || ''} - ${grupo.hora_fin || ''}
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <i class="bi bi-layers" style="color: #6B2C91; font-size: 14px; min-width: 16px;"></i> 
                                                    Grupo ${grupo.id_ghm}
                                                </div>
                                            </div>
                                        `;
                                        
                                        grupoDiv.onclick = () => {
                                            // Deseleccionar todos
                                            document.querySelectorAll('#grupos-list-editar .grupo-option-editar').forEach(g => g.classList.remove('selected'));
                                            // Seleccionar este
                                            grupoDiv.classList.add('selected');
                                            document.getElementById('grupo_seleccionado_editar').value = grupo.id_ghm;
                                            btnGuardar.disabled = false;
                                            
                                            // Feedback visual
                                            grupoDiv.style.transform = 'scale(0.98)';
                                            setTimeout(() => {
                                                grupoDiv.style.transform = '';
                                            }, 150);
                                        };
                                        
                                        gruposList.appendChild(grupoDiv);
                                    });
                                })
                                .catch(error => {
                                    gruposList.innerHTML = '';
                                    sinGrupos.innerHTML = `
                                        <i class="bi bi-exclamation-triangle" style="font-size: 48px; color: #dc3545;"></i>
                                        <h4 style="color: #dc3545; margin-bottom: 10px;">Error de conexión</h4>
                                        <p style="color: #721c24;">No se pudo cargar la información. Verifica tu conexión a internet.</p>
                                        <div style="margin-top: 20px;">
                                            <button onclick="cargarGruposParaEditar()" class="btn-action" style="background: #6c757d; padding: 10px 20px;">
                                                <i class="bi bi-arrow-clockwise"></i> Reintentar
                                            </button>
                                        </div>
                                    `;
                                    sinGrupos.style.display = 'block';
                                    gruposContainer.style.display = 'none';
                                    console.error('Error:', error);
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
                    
                    <!-- Tabla - CORREGIDA -->
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
                                            <!-- CONTENEDOR CORREGIDO PARA BOTONES - FIXED -->
                                            <div class="acciones-tabla">
                                                <a href="?accion=editar&id=<?php echo $matricula['id_matricula']; ?><?php echo $id_estudiante ? '&id_estudiante=' . $id_estudiante : ''; ?>" 
                                                   class="btn-tabla btn-editar" title="Editar">
                                                    <i class="bi bi-pencil"></i> Editar
                                                </a>
                                                <a href="?accion=eliminar&id=<?php echo $matricula['id_matricula']; ?><?php echo $id_estudiante ? '&id_estudiante=' . $id_estudiante : ''; ?>" 
                                                   onclick="return confirm('¿Estás seguro de eliminar esta matrícula?')"
                                                   class="btn-tabla btn-eliminar" title="Eliminar">
                                                    <i class="bi bi-trash"></i> Borrar
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