<?php
// app/views/admin/materias.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

// Obtener parámetros
$id_carrera = $_GET['id_carrera'] ?? null;
$carrera_nombre = $_GET['carrera_nombre'] ?? null;
$accion = $_GET['accion'] ?? 'listar';

// Obtener información de la carrera si se especifica
$carrera_info = null;
if ($id_carrera) {
    $stmt = $conexion->prepare("SELECT * FROM carreras WHERE id_carrera = ?");
    $stmt->bind_param("i", $id_carrera);
    $stmt->execute();
    $carrera_info = $stmt->get_result()->fetch_assoc();
}

// Obtener materias
$query = "SELECT m.*, c.nombre as carrera_nombre 
          FROM materias m
          LEFT JOIN carreras c ON m.id_carrera = c.id_carrera";
          
if ($id_carrera) {
    $query .= " WHERE m.id_carrera = $id_carrera";
}

$query .= " ORDER BY m.nombre ASC";

$materias = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materias - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/style.css">
    <style>
        .materias-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #6B2C91;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .materias-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .materias-table th,
        .materias-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .materias-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .materias-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .btn-action {
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .btn-purple {
            background: #6B2C91;
            color: white;
            border: none;
        }
        
        .btn-green {
            background: #2d8659;
            color: white;
            border: none;
        }
        
        .btn-blue {
            background: #3498db;
            color: white;
            border: none;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">
                <h2>UTP Admin</h2>
                <small>Sistema de Matrícula</small>
            </div>
            
            <div class="user-info">
                <div class="avatar">
                    <i class="bi bi-person-circle"></i>
                </div>
                <h3><?php echo $_SESSION['user_name']; ?></h3>
                <p>Administrador</p>
            </div>
            
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-item">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="estudiantes.php" class="nav-item">
                    <i class="bi bi-people"></i> Estudiantes
                </a>
                <a href="docentes.php" class="nav-item">
                    <i class="bi bi-person-video"></i> Docentes
                </a>
                <a href="materias.php" class="nav-item active">
                    <i class="bi bi-journal-text"></i> Materias
                </a>
                <a href="matriculas.php" class="nav-item">
                    <i class="bi bi-pencil-square"></i> Matrículas
                </a>
                <a href="carreras.php" class="nav-item">
                    <i class="bi bi-mortarboard"></i> Carreras
                </a>
                <a href="periodos.php" class="nav-item">
                    <i class="bi bi-calendar-range"></i> Períodos
                </a>
                <a href="reportes.php" class="nav-item">
                    <i class="bi bi-graph-up"></i> Reportes
                </a>
                <a href="auditoria.php" class="nav-item">
                    <i class="bi bi-clipboard-data"></i> Auditoría
                </a>
                
                <div class="logout">
                    <a href="../auth/logout.php" class="nav-item">
                        <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                    </a>
                </div>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="materias-container">
                <div class="breadcrumb">
                    <a href="carreras.php">Carreras</a>
                    <?php if ($carrera_info): ?>
                    &nbsp;/&nbsp;
                    <a href="materias.php?id_carrera=<?php echo $carrera_info['id_carrera']; ?>">
                        <?php echo htmlspecialchars($carrera_info['nombre']); ?>
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="header-actions">
                    <h1>
                        <i class="bi bi-journal-text me-2"></i>
                        <?php if ($carrera_info): ?>
                        Materias de <?php echo htmlspecialchars($carrera_info['nombre']); ?>
                        <?php else: ?>
                        Todas las Materias
                        <?php endif; ?>
                    </h1>
                    <div>
                        <a href="materias.php?accion=nuevo<?php echo $id_carrera ? '&id_carrera='.$id_carrera : ''; ?>" 
                           class="btn-action btn-purple">
                            <i class="bi bi-plus-circle"></i> Nueva Materia
                        </a>
                        <?php if ($id_carrera): ?>
                        <a href="materias.php" class="btn-action btn-blue" style="margin-left: 10px;">
                            <i class="bi bi-list"></i> Ver Todas
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
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
                        <?php if ($materias->num_rows > 0): ?>
                            <?php while($materia = $materias->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($materia['codigo'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($materia['nombre']); ?></td>
                                <td>
                                    <?php 
                                    $descripcion = $materia['descripcion'] ?? 'Sin descripción';
                                    echo htmlspecialchars(substr($descripcion, 0, 50));
                                    if (strlen($descripcion) > 50) echo '...';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($materia['carrera_nombre']): ?>
                                    <a href="materias.php?id_carrera=<?php echo $materia['id_carrera']; ?>" 
                                       class="badge badge-info" style="background: #e9ecef; color: #495057; padding: 3px 8px; border-radius: 4px; text-decoration: none;">
                                        <?php echo htmlspecialchars($materia['carrera_nombre']); ?>
                                    </a>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?php echo number_format($materia['costo'] ?? 0, 2); ?></td>
                                <td><?php echo htmlspecialchars($materia['docente'] ?? 'Por asignar'); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="materias.php?accion=editar&id=<?php echo $materia['id_materia']; ?>" 
                                           class="btn-action btn-blue btn-sm">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="materias.php?accion=eliminar&id=<?php echo $materia['id_materia']; ?>" 
                                           onclick="return confirm('¿Eliminar esta materia?')"
                                           class="btn-action btn-danger btn-sm">
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
                                    <i class="bi bi-journal-x" style="font-size: 48px; margin-bottom: 15px; color: #ddd;"></i>
                                    <h3>No hay materias registradas</h3>
                                    <?php if ($carrera_info): ?>
                                    <p>No hay materias en la carrera <?php echo htmlspecialchars($carrera_info['nombre']); ?>.</p>
                                    <?php else: ?>
                                    <p>Comienza creando una nueva materia para el sistema.</p>
                                    <?php endif; ?>
                                    <a href="materias.php?accion=nuevo<?php echo $id_carrera ? '&id_carrera='.$id_carrera : ''; ?>" 
                                       class="btn-action btn-purple" style="margin-top: 15px;">
                                        <i class="bi bi-plus-circle"></i> Crear Primera Materia
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if ($materias->num_rows > 0): ?>
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Total de materias:</strong> <?php echo $materias->num_rows; ?>
                    <?php if ($carrera_info): ?>
                    | <strong>Carrera:</strong> <?php echo htmlspecialchars($carrera_info['nombre']); ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conexion->close(); ?>