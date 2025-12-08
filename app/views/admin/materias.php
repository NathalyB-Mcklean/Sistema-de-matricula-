<?php
// app/views/admin/materias.php - VERSIÓN ADAPTADA A TU ESTRUCTURA DE BD

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';
require_once '../../utils/validaciones.php';

$mensaje = '';
$accion = $_GET['accion'] ?? 'listar';
$id_materia = $_GET['id'] ?? null;
$id_carrera = $_GET['id_carrera'] ?? null;

// ========== PROCESAR FORMULARIO (CREAR/EDITAR) ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Sanitizar y validar datos
        $codigo = Validaciones::sanitizarTexto($_POST['codigo']);
        $nombre = Validaciones::sanitizarTexto($_POST['nombre']);
        $descripcion = Validaciones::sanitizarTexto($_POST['descripcion']);
        $costo = Validaciones::sanitizarNumero($_POST['costo']);
        $id_carrera_post = Validaciones::sanitizarEntero($_POST['id_carrera']);
        $id_docente = !empty($_POST['id_docente']) ? Validaciones::sanitizarEntero($_POST['id_docente']) : null;
        
        // Validaciones básicas
        Validaciones::validarNoVacio($codigo, 'código');
        Validaciones::validarNoVacio($nombre, 'nombre');
        
        if (isset($_POST['id_materia'])) {
            // ========== ACTUALIZAR MATERIA ==========
            $id_mat = Validaciones::sanitizarEntero($_POST['id_materia']);
            
            $stmt = $conexion->prepare("
                UPDATE materias SET 
                codigo = ?, nombre = ?, descripcion = ?, costo = ?, 
                id_carrera = ?, id_docente = ?
                WHERE id_materia = ?
            ");
            $stmt->bind_param("sssdiii", 
                $codigo, $nombre, $descripcion, $costo,
                $id_carrera_post, $id_docente, $id_mat
            );
            $stmt->execute();
            
            $mensaje = '<div class="alert alert-success">Materia actualizada correctamente</div>';
            
        } else {
            // ========== CREAR NUEVA MATERIA ==========
            $stmt = $conexion->prepare("
                INSERT INTO materias 
                (codigo, nombre, descripcion, costo, id_carrera, id_docente)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssdii", 
                $codigo, $nombre, $descripcion, $costo,
                $id_carrera_post, $id_docente
            );
            $stmt->execute();
            
            $mensaje = '<div class="alert alert-success">Materia creada correctamente</div>';
        }
        
        // Redirigir después de guardar
        header('Location: materias.php?mensaje=' . urlencode('Operación exitosa') . ($id_carrera_post ? '&id_carrera=' . $id_carrera_post : ''));
        exit();
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// ========== OBTENER MATERIA PARA EDITAR ==========
$materia_editar = null;
if ($id_materia && $accion == 'editar') {
    $stmt = $conexion->prepare("SELECT * FROM materias WHERE id_materia = ?");
    $stmt->bind_param("i", $id_materia);
    $stmt->execute();
    $materia_editar = $stmt->get_result()->fetch_assoc();
}

// ========== ELIMINAR MATERIA ==========
if ($accion === 'eliminar' && $id_materia) {
    try {
        // Verificar si tiene grupos asignados
        $stmt_check = $conexion->prepare("SELECT COUNT(*) as total FROM grupos WHERE id_materia = ?");
        $stmt_check->bind_param("i", $id_materia);
        $stmt_check->execute();
        $result = $stmt_check->get_result()->fetch_assoc();
        
        if ($result['total'] > 0) {
            // Si tiene grupos, no permitir eliminación
            throw new Exception("No se puede eliminar la materia porque tiene grupos asignados.");
        } else {
            // Eliminar completamente
            $stmt = $conexion->prepare("DELETE FROM materias WHERE id_materia = ?");
            $stmt->bind_param("i", $id_materia);
            $stmt->execute();
            $msg = "Materia eliminada exitosamente";
        }
        
        // Auditoría
        $audit = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
        $accion_audit = "Eliminó materia ID: $id_materia";
        $audit->bind_param("ss", $_SESSION['user_name'], $accion_audit);
        $audit->execute();
        
        header('Location: materias.php?mensaje=' . urlencode($msg) . ($id_carrera ? '&id_carrera=' . $id_carrera : ''));
        exit();
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// ========== OBTENER LISTA DE MATERIAS ==========
$query = "SELECT m.*, c.nombre as carrera_nombre, 
                 d.nombre as docente_nombre, d.apellido as docente_apellido
          FROM materias m
          LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
          LEFT JOIN docentes d ON m.id_docente = d.id_docente";
          
if ($id_carrera) {
    $query .= " WHERE m.id_carrera = $id_carrera";
}

$query .= " ORDER BY m.codigo ASC";

$materias = $conexion->query($query);
$total_materias = $materias->num_rows;

// Obtener información de la carrera si se especifica
$carrera_info = null;
if ($id_carrera) {
    $stmt = $conexion->prepare("SELECT * FROM carreras WHERE id_carrera = ?");
    $stmt->bind_param("i", $id_carrera);
    $stmt->execute();
    $carrera_info = $stmt->get_result()->fetch_assoc();
}

// Obtener carreras y docentes para formularios
$carreras = $conexion->query("SELECT id_carrera, nombre FROM carreras WHERE estado = 'activa' ORDER BY nombre");
$docentes = $conexion->query("SELECT id_docente, nombre, apellido FROM docentes WHERE estado = 'activo' ORDER BY apellido, nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materias - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/materias.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 25px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .card-header h2 {
            margin: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
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
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
        }
        .btn-action:hover {
            background: #5a6268;
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
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }
        .badge-info {
            background: #17a2b8;
            color: white;
        }
        .badge-secondary {
            background: #6c757d;
            color: white;
        }
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 60px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        .empty-state h3 {
            color: #495057;
            margin-bottom: 10px;
        }
        .empty-state p {
            margin-bottom: 20px;
        }
        .summary-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            border-left: 4px solid #007bff;
        }
        .breadcrumb {
            margin-bottom: 20px;
            font-size: 14px;
        }
        .breadcrumb a {
            color: #007bff;
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
        }
        .header-actions h1 {
            margin: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .table-container {
            overflow-x: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .materias-table {
            width: 100%;
            border-collapse: collapse;
        }
        .materias-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        .materias-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        .materias-table tr:hover {
            background: #f8f9fa;
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
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
                <h3><?php echo $_SESSION['user_name']; ?></h3>
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
                <a href="materias.php" class="nav-item active">
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
            <div class="materias-container">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="carreras.php"><i class="bi bi-house-door"></i> Carreras</a>
                    <?php if ($carrera_info): ?>
                    <span> / </span>
                    <a href="materias.php?id_carrera=<?php echo $carrera_info['id_carrera']; ?>">
                        <?php echo htmlspecialchars($carrera_info['nombre']); ?>
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Header -->
                <div class="header-actions">
                    <h1>
                        <i class="bi bi-journal-text"></i>
                        <?php if ($carrera_info): ?>
                        Materias de <?php echo htmlspecialchars($carrera_info['nombre']); ?>
                        <?php else: ?>
                        Todas las Materias
                        <?php endif; ?>
                    </h1>
                    
                    <?php if ($accion != 'nuevo' && $accion != 'editar'): ?>
                    <div style="display: flex; gap: 10px;">
                        <a href="materias.php?accion=nuevo<?php echo $id_carrera ? '&id_carrera='.$id_carrera : ''; ?>" 
                           class="btn-action btn-purple">
                            <i class="bi bi-plus-circle"></i> Nueva Materia
                        </a>
                        <?php if ($id_carrera): ?>
                        <a href="materias.php" class="btn-action btn-blue">
                            <i class="bi bi-list"></i> Ver Todas
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php 
                if (isset($_GET['mensaje'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['mensaje']) . '</div>';
                }
                echo $mensaje; 
                ?>
                
                <?php if ($accion == 'nuevo' || $accion == 'editar'): ?>
                    <!-- FORMULARIO DE MATERIA -->
                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <i class="bi bi-<?php echo $accion == 'nuevo' ? 'plus-circle' : 'pencil'; ?>"></i>
                                <?php echo $accion == 'nuevo' ? 'Nueva Materia' : 'Editar Materia'; ?>
                            </h2>
                            <a href="materias.php<?php echo $id_carrera ? '?id_carrera='.$id_carrera : ''; ?>" 
                               class="btn-action">Cancelar</a>
                        </div>
                        
                        <form method="POST" action="">
                            <?php if ($materia_editar): ?>
                                <input type="hidden" name="id_materia" value="<?php echo $materia_editar['id_materia']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Código *</label>
                                    <input type="text" name="codigo" 
                                           value="<?php echo htmlspecialchars($materia_editar['codigo'] ?? ''); ?>" 
                                           required maxlength="20">
                                </div>
                                
                                <div class="form-group">
                                    <label>Nombre *</label>
                                    <input type="text" name="nombre" 
                                           value="<?php echo htmlspecialchars($materia_editar['nombre'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Descripción</label>
                                    <textarea name="descripcion"><?php echo htmlspecialchars($materia_editar['descripcion'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Costo ($)</label>
                                    <input type="number" name="costo" step="0.01" min="0"
                                           value="<?php echo $materia_editar['costo'] ?? 0; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Carrera *</label>
                                    <select name="id_carrera" required>
                                        <option value="">Seleccione una carrera</option>
                                        <?php while($carrera = $carreras->fetch_assoc()): ?>
                                        <option value="<?php echo $carrera['id_carrera']; ?>"
                                            <?php echo ($materia_editar['id_carrera'] ?? ($id_carrera ?? 0)) == $carrera['id_carrera'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($carrera['nombre']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Docente Asignado</label>
                                    <select name="id_docente">
                                        <option value="">Sin asignar</option>
                                        <?php while($docente = $docentes->fetch_assoc()): ?>
                                        <option value="<?php echo $docente['id_docente']; ?>"
                                            <?php echo ($materia_editar['id_docente'] ?? 0) == $docente['id_docente'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($docente['nombre'] . ' ' . $docente['apellido']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px; display: flex; gap: 10px;">
                                <button type="submit" class="btn-action btn-green">
                                    <i class="bi bi-save"></i> Guardar Materia
                                </button>
                                <a href="materias.php<?php echo $id_carrera ? '?id_carrera='.$id_carrera : ''; ?>" 
                                   class="btn-action">Cancelar</a>
                            </div>
                        </form>
                    </div>
                    
                <?php else: ?>
                    <!-- LISTA DE MATERIAS -->
                    <div class="table-container">
                        <table class="materias-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Carrera</th>
                                    <th>Costo</th>
                                    <th>Docente</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($total_materias > 0): ?>
                                    <?php while($materia = $materias->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($materia['codigo'] ?? 'N/A'); ?></strong></td>
                                        <td><strong><?php echo htmlspecialchars($materia['nombre']); ?></strong></td>
                                        <td>
                                            <?php 
                                            $descripcion = $materia['descripcion'] ?? 'Sin descripción';
                                            if (strlen($descripcion) > 50) {
                                                echo htmlspecialchars(substr($descripcion, 0, 50)) . '...';
                                            } else {
                                                echo htmlspecialchars($descripcion);
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($materia['carrera_nombre']): ?>
                                            <a href="materias.php?id_carrera=<?php echo $materia['id_carrera']; ?>" 
                                               class="badge badge-info">
                                                <?php echo htmlspecialchars($materia['carrera_nombre']); ?>
                                            </a>
                                            <?php else: ?>
                                            <span class="badge badge-secondary">Sin asignar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong>$<?php echo number_format($materia['costo'] ?? 0, 2); ?></strong></td>
                                        <td>
                                            <?php if (!empty($materia['docente_nombre'])): ?>
                                                <?php echo htmlspecialchars($materia['docente_nombre'] . ' ' . $materia['docente_apellido']); ?>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Por asignar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <a href="?accion=editar&id=<?php echo $materia['id_materia']; ?><?php echo $id_carrera ? '&id_carrera='.$id_carrera : ''; ?>" 
                                                   class="btn-action btn-blue btn-sm" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?accion=eliminar&id=<?php echo $materia['id_materia']; ?><?php echo $id_carrera ? '&id_carrera='.$id_carrera : ''; ?>" 
                                                   onclick="return confirm('¿Eliminar materia \'<?php echo addslashes($materia['nombre']); ?>\'?')"
                                                   class="btn-action btn-danger btn-sm" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="bi bi-journal-x"></i>
                                            <h3>No hay materias registradas</h3>
                                            <?php if ($carrera_info): ?>
                                            <p>No hay materias en la carrera <?php echo htmlspecialchars($carrera_info['nombre']); ?>.</p>
                                            <?php else: ?>
                                            <p>Comienza creando una nueva materia para el sistema.</p>
                                            <?php endif; ?>
                                            <a href="materias.php?accion=nuevo<?php echo $id_carrera ? '&id_carrera='.$id_carrera : ''; ?>" 
                                               class="btn-action btn-purple">
                                                <i class="bi bi-plus-circle"></i> Crear Primera Materia
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Resumen -->
                    <?php if ($total_materias > 0): ?>
                    <div class="summary-box">
                        <strong>Total de materias:</strong> <?php echo $total_materias; ?>
                        <?php if ($carrera_info): ?>
                        | <strong>Carrera:</strong> <?php echo htmlspecialchars($carrera_info['nombre']); ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conexion->close(); ?>