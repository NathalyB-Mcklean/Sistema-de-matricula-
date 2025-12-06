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

// Obtener materias CON información del docente
$query = "SELECT m.*, c.nombre as carrera_nombre, 
                 d.nombre as docente_nombre, d.apellido as docente_apellido
          FROM materias m
          LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
          LEFT JOIN docentes d ON m.id_docente = d.id_docente";
          
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
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/materias.css">
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
                </div>
                
                <!-- Tabla -->
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
                            <?php if ($materias->num_rows > 0): ?>
                                <?php while($materia = $materias->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($materia['codigo'] ?? 'N/A'); ?></strong></td>
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
                                            <a href="materias.php?accion=editar&id=<?php echo $materia['id_materia']; ?>" 
                                               class="btn-action btn-blue btn-sm" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="materias.php?accion=eliminar&id=<?php echo $materia['id_materia']; ?>" 
                                               onclick="return confirm('¿Eliminar esta materia?')"
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
                <?php if ($materias->num_rows > 0): ?>
                <div class="summary-box">
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