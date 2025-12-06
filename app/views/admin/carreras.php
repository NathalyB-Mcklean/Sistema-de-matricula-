<?php
// app/views/admin/carreras.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

$mensaje = '';
$accion = $_GET['accion'] ?? 'listar';

// Obtener la carrera para editar
$carrera_editar = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $conexion->prepare("SELECT * FROM carreras WHERE id_carrera = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $carrera_editar = $stmt->get_result()->fetch_assoc();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $nombre = trim($_POST['nombre']);
        $codigo = trim($_POST['codigo']);
        $descripcion = trim($_POST['descripcion']);
        $duracion = (int)$_POST['duracion_semestres'];
        $creditos = (int)$_POST['creditos_totales'];
        $estado = $_POST['estado'];
        
        // Validaciones básicas
        if (empty($nombre)) throw new Exception("El nombre es requerido");
        if (empty($codigo)) throw new Exception("El código es requerido");
        if ($duracion < 1 || $duracion > 20) throw new Exception("Duración inválida (1-20 semestres)");
        if ($creditos < 1 || $creditos > 300) throw new Exception("Créditos inválidos (1-300)");
        
        if (isset($_POST['id_carrera'])) {
            // Actualizar carrera
            $stmt = $conexion->prepare("UPDATE carreras SET 
                nombre = ?, codigo = ?, descripcion = ?, duracion_semestres = ?, 
                creditos_totales = ?, estado = ?
                WHERE id_carrera = ?");
            $stmt->bind_param("sssiisi", $nombre, $codigo, $descripcion, $duracion, $creditos, $estado, $_POST['id_carrera']);
            $mensaje = '<div class="alert alert-success">Carrera actualizada correctamente</div>';
        } else {
            // Insertar nueva carrera
            $stmt = $conexion->prepare("INSERT INTO carreras 
                (nombre, codigo, descripcion, duracion_semestres, creditos_totales, estado) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiis", $nombre, $codigo, $descripcion, $duracion, $creditos, $estado);
            $mensaje = '<div class="alert alert-success">Carrera creada correctamente</div>';
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Error al guardar: " . $conexion->error);
        }
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// Eliminar carrera
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    // Primero verificar si hay materias asociadas
    $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM materias WHERE id_carrera = ?");
    $stmt->bind_param("i", $_GET['eliminar']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['total'] > 0) {
        $mensaje = '<div class="alert alert-warning">No se puede eliminar la carrera porque tiene materias asociadas.</div>';
    } else {
        $stmt = $conexion->prepare("DELETE FROM carreras WHERE id_carrera = ?");
        $stmt->bind_param("i", $_GET['eliminar']);
        if ($stmt->execute()) {
            $mensaje = '<div class="alert alert-success">Carrera eliminada correctamente</div>';
        } else {
            $mensaje = '<div class="alert alert-danger">Error al eliminar la carrera</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Carreras - Sistema UTP</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/style.css">
    <style>
        .carreras-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .carreras-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .carrera-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .carrera-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .carrera-activa {
            border-left: 4px solid #2d8659;
        }
        
        .carrera-inactiva {
            border-left: 4px solid #dc3545;
            background: #f8f9fa;
        }
        
        .carrera-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .carrera-info {
            margin-bottom: 10px;
        }
        
        .carrera-info .codigo {
            font-family: monospace;
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .stats {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
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
        
        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .header-actions h1 {
            color: #6B2C91;
            margin: 0;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .search-box button {
            padding: 10px 20px;
            background: #6B2C91;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }
    </style>
</head>
<body>
    <?php 
    // Incluir el sidebar del dashboard
    // Si ya estás en una página con el sidebar, puedes incluir solo el contenido
    // Si no, necesitas la estructura completa. Voy a asumir que quieres el sidebar.
    // Te sugiero crear un archivo partials/sidebar.php para reutilizar
    ?>
    
    <!-- Incluir el header del dashboard -->
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
                <a href="materias.php" class="nav-item">
                    <i class="bi bi-journal-text"></i> Materias
                </a>
                <a href="matriculas.php" class="nav-item">
                    <i class="bi bi-pencil-square"></i> Matrículas
                </a>
                <a href="carreras.php" class="nav-item active">
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
            <div class="carreras-container">
                <div class="header-actions">
                    <h1><i class="bi bi-mortarboard me-2"></i>Gestión de Carreras</h1>
                    <a href="?accion=nuevo" class="btn-action btn-purple">
                        <i class="bi bi-plus-circle"></i> Nueva Carrera
                    </a>
                </div>
                
                <?php echo $mensaje; ?>
                
                <?php if ($accion == 'nuevo' || $accion == 'editar'): ?>
                <!-- Formulario para nuevo/editar carrera -->
                <div class="card">
                    <div class="card-header">
                        <h2><?php echo $accion == 'nuevo' ? 'Crear Nueva Carrera' : 'Editar Carrera'; ?></h2>
                        <a href="carreras.php" class="view-all">Cancelar</a>
                    </div>
                    <form method="POST" action="">
                        <?php if ($carrera_editar): ?>
                        <input type="hidden" name="id_carrera" value="<?php echo $carrera_editar['id_carrera']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Código de la Carrera *</label>
                                <input type="text" name="codigo" 
                                       value="<?php echo htmlspecialchars($carrera_editar['codigo'] ?? ''); ?>" 
                                       placeholder="Ej: IS-01, ADM-02" required>
                                <small style="color: #666;">Identificador único para la carrera</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Nombre de la Carrera *</label>
                                <input type="text" name="nombre" 
                                       value="<?php echo htmlspecialchars($carrera_editar['nombre'] ?? ''); ?>" 
                                       placeholder="Ej: Ingeniería en Sistemas" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Duración (Semestres) *</label>
                                <select name="duracion_semestres" required>
                                    <?php for($i = 1; $i <= 20; $i++): ?>
                                    <option value="<?php echo $i; ?>" 
                                        <?php echo (($carrera_editar['duracion_semestres'] ?? 10) == $i) ? 'selected' : ''; ?>>
                                        <?php echo $i; ?> semestre<?php echo $i != 1 ? 's' : ''; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Créditos Totales *</label>
                                <input type="number" name="creditos_totales" 
                                       value="<?php echo $carrera_editar['creditos_totales'] ?? 180; ?>" 
                                       min="1" max="300" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Estado *</label>
                                <select name="estado" required>
                                    <option value="activa" <?php echo (($carrera_editar['estado'] ?? 'activa') == 'activa') ? 'selected' : ''; ?>>Activa</option>
                                    <option value="inactiva" <?php echo (($carrera_editar['estado'] ?? '') == 'inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="descripcion" placeholder="Describe los objetivos y perfil de la carrera..."><?php echo htmlspecialchars($carrera_editar['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn-action btn-purple" style="margin-top: 20px; padding: 12px 30px;">
                            <i class="bi bi-save"></i> Guardar Carrera
                        </button>
                    </form>
                </div>
                
                <?php else: ?>
                <!-- Lista de carreras -->
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Buscar carreras por nombre o código...">
                    <button onclick="searchCarreras()">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
                
                <div id="carrerasList" class="carreras-grid">
                    <?php 
                    // Consultar carreras con estadísticas
                    $query = "SELECT c.*, 
                             COUNT(m.id_materia) as total_materias,
                             COUNT(DISTINCT e.id_estudiante) as total_estudiantes
                             FROM carreras c
                             LEFT JOIN materias m ON c.id_carrera = m.id_carrera
                             LEFT JOIN estudiantes e ON c.id_carrera = e.id_carrera
                             GROUP BY c.id_carrera
                             ORDER BY c.estado DESC, c.nombre ASC";
                    
                    $result = $conexion->query($query);
                    
                    if ($result->num_rows > 0):
                        while($carrera = $result->fetch_assoc()):
                    ?>
                    <div class="carrera-card carrera-<?php echo $carrera['estado']; ?>">
                        <div class="carrera-info">
                            <span class="codigo"><?php echo htmlspecialchars($carrera['codigo'] ?? 'N/A'); ?></span>
                            <h3 style="margin: 10px 0 5px 0; color: #6B2C91;">
                                <?php echo htmlspecialchars($carrera['nombre']); ?>
                            </h3>
                            <p style="font-size: 14px; color: #666; margin-bottom: 10px;">
                                <?php echo htmlspecialchars(substr($carrera['descripcion'] ?? 'Sin descripción', 0, 100)); ?>
                                <?php if (strlen($carrera['descripcion'] ?? '') > 100): ?>...<?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="stats">
                            <div class="stat-item">
                                <i class="bi bi-clock"></i>
                                <span><?php echo $carrera['duracion_semestres'] ?? 10; ?> semestres</span>
                            </div>
                            <div class="stat-item">
                                <i class="bi bi-journal"></i>
                                <!-- HAZ EL NÚMERO DE MATERIAS HACER CLICK -->
                                <a href="materias.php?id_carrera=<?php echo $carrera['id_carrera']; ?>&carrera_nombre=<?php echo urlencode($carrera['nombre']); ?>" 
                                style="text-decoration: none; color: inherit; cursor: pointer;" 
                                title="Ver materias de esta carrera">
                                    <span><?php echo $carrera['total_materias']; ?> materias</span>
                                </a>
                            </div>
                            <div class="stat-item">
                                <i class="bi bi-people"></i>
                                <span><?php echo $carrera['total_estudiantes']; ?> estudiantes</span>
                            </div>
                        </div>
                        
                        <div class="carrera-actions">
                            <span class="badge badge-<?php echo $carrera['estado'] == 'activa' ? 'success' : 'danger'; ?>" 
                                style="flex-grow: 1;">
                                <?php echo ucfirst($carrera['estado']); ?>
                            </span>
                            
                            <!-- BOTÓN PARA VER MATERIAS -->
                            <a href="materias.php?id_carrera=<?php echo $carrera['id_carrera']; ?>&carrera_nombre=<?php echo urlencode($carrera['nombre']); ?>" 
                            class="btn-action btn-green btn-sm" title="Ver materias">
                                <i class="bi bi-journal-text"></i>
                            </a>
                            
                            <a href="?accion=editar&id=<?php echo $carrera['id_carrera']; ?>" 
                            class="btn-action btn-blue btn-sm" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            
                            <a href="?eliminar=<?php echo $carrera['id_carrera']; ?>" 
                            onclick="return confirm('¿Eliminar esta carrera? Esta acción no se puede deshacer.')"
                            class="btn-action btn-danger btn-sm" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div class="empty-state">
                        <i class="bi bi-mortarboard"></i>
                        <h3>No hay carreras registradas</h3>
                        <p>Comienza creando una nueva carrera para el sistema.</p>
                        <a href="?accion=nuevo" class="btn-action btn-purple" style="margin-top: 15px;">
                            <i class="bi bi-plus-circle"></i> Crear Primera Carrera
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script>
        // Búsqueda en tiempo real
        function searchCarreras() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const grid = document.getElementById('carrerasList');
            const cards = grid.getElementsByClassName('carrera-card');
            
            for (let i = 0; i < cards.length; i++) {
                const card = cards[i];
                const nombre = card.querySelector('h3').textContent.toUpperCase();
                const codigo = card.querySelector('.codigo').textContent.toUpperCase();
                
                if (nombre.includes(filter) || codigo.includes(filter)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            }
        }
        
        // Búsqueda al escribir
        document.getElementById('searchInput').addEventListener('keyup', searchCarreras);
        
        // Confirmar eliminación
        function confirmDelete(carreraId, carreraNombre) {
            return confirm(`¿Estás seguro de eliminar la carrera "${carreraNombre}"?\n\nEsta acción no se puede deshacer.`);
        }
    </script>
</body>
</html>
<?php $conexion->close(); ?>