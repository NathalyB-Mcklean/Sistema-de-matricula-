<?php
// app/views/admin/horarios.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

$mensaje = '';
$accion = $_GET['accion'] ?? 'listar';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $dia = trim($_POST['dia']);
        $hora_inicio = trim($_POST['hora_inicio']);
        $hora_fin = trim($_POST['hora_fin']);
        $tipo = trim($_POST['tipo']); // teórica, práctica, laboratorio
        
        // Validaciones
        if (empty($dia)) throw new Exception("El día es requerido");
        if (empty($hora_inicio) || empty($hora_fin)) throw new Exception("Las horas son requeridas");
        
        // Convertir a formato de hora
        $hora_inicio = date('H:i:s', strtotime($hora_inicio));
        $hora_fin = date('H:i:s', strtotime($hora_fin));
        
        if ($hora_inicio >= $hora_fin) {
            throw new Exception("La hora de inicio debe ser anterior a la hora de fin");
        }
        
        if (isset($_POST['id_horario'])) {
            // Actualizar horario
            $stmt = $conexion->prepare("UPDATE horarios SET dia = ?, hora_inicio = ?, hora_fin = ?, tipo = ? WHERE id_horario = ?");
            $stmt->bind_param("ssssi", $dia, $hora_inicio, $hora_fin, $tipo, $_POST['id_horario']);
            $mensaje = '<div class="alert alert-success">Horario actualizado</div>';
        } else {
            // Insertar nuevo horario
            $stmt = $conexion->prepare("INSERT INTO horarios (dia, hora_inicio, hora_fin, tipo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $dia, $hora_inicio, $hora_fin, $tipo);
            $mensaje = '<div class="alert alert-success">Horario creado</div>';
        }
        
        if ($stmt->execute()) {
            header('Location: horarios.php?mensaje=' . urlencode('Horario guardado correctamente'));
            exit();
        } else {
            throw new Exception("Error: " . $conexion->error);
        }
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// Obtener datos para editar
$horario_editar = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $conexion->prepare("SELECT * FROM horarios WHERE id_horario = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $horario_editar = $stmt->get_result()->fetch_assoc();
}

// Eliminar horario
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    // Verificar si está en uso
    $stmt_check = $conexion->prepare("SELECT COUNT(*) as total FROM grupos_horarios_materia WHERE id_horario = ?");
    $stmt_check->bind_param("i", $_GET['eliminar']);
    $stmt_check->execute();
    $result = $stmt_check->get_result()->fetch_assoc();
    
    if ($result['total'] > 0) {
        $mensaje = '<div class="alert alert-warning">No se puede eliminar: está asignado a ' . $result['total'] . ' grupo(s)</div>';
    } else {
        $stmt = $conexion->prepare("DELETE FROM horarios WHERE id_horario = ?");
        $stmt->bind_param("i", $_GET['eliminar']);
        if ($stmt->execute()) {
            header('Location: horarios.php?mensaje=' . urlencode('Horario eliminado'));
            exit();
        }
    }
}

// Obtener todos los horarios
$horarios = $conexion->query("SELECT * FROM horarios ORDER BY 
                              FIELD(dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'), 
                              hora_inicio");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horarios - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .horario-container {
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .horario-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .horario-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #f9f9f9;
        }
        
        .horario-card .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .horario-card .dia {
            font-weight: bold;
            color: #6B2C91;
            font-size: 18px;
        }
        
        .horario-card .hora {
            background: #2d8659;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'partials/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="horario-container">
                <h1><i class="bi bi-clock"></i> Gestión de Horarios</h1>
                
                <?php if (isset($_GET['mensaje'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_GET['mensaje']); ?>
                    </div>
                <?php endif; ?>
                
                <?php echo $mensaje; ?>
                
                <div style="margin: 20px 0;">
                    <a href="?accion=nuevo" class="btn-action btn-green">
                        <i class="bi bi-plus-circle"></i> Nuevo Horario
                    </a>
                </div>
                
                <?php if ($accion == 'nuevo' || $accion == 'editar'): ?>
                    <!-- Formulario -->
                    <div class="card">
                        <h2><?php echo $accion == 'nuevo' ? 'Nuevo Horario' : 'Editar Horario'; ?></h2>
                        
                        <form method="POST">
                            <?php if ($horario_editar): ?>
                                <input type="hidden" name="id_horario" value="<?php echo $horario_editar['id_horario']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Día de la semana *</label>
                                    <select name="dia" required>
                                        <option value="">Seleccionar día...</option>
                                        <option value="Lunes" <?php echo ($horario_editar['dia'] ?? '') == 'Lunes' ? 'selected' : ''; ?>>Lunes</option>
                                        <option value="Martes" <?php echo ($horario_editar['dia'] ?? '') == 'Martes' ? 'selected' : ''; ?>>Martes</option>
                                        <option value="Miércoles" <?php echo ($horario_editar['dia'] ?? '') == 'Miércoles' ? 'selected' : ''; ?>>Miércoles</option>
                                        <option value="Jueves" <?php echo ($horario_editar['dia'] ?? '') == 'Jueves' ? 'selected' : ''; ?>>Jueves</option>
                                        <option value="Viernes" <?php echo ($horario_editar['dia'] ?? '') == 'Viernes' ? 'selected' : ''; ?>>Viernes</option>
                                        <option value="Sábado" <?php echo ($horario_editar['dia'] ?? '') == 'Sábado' ? 'selected' : ''; ?>>Sábado</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Tipo de clase</label>
                                    <select name="tipo">
                                        <option value="teorica" <?php echo ($horario_editar['tipo'] ?? '') == 'teorica' ? 'selected' : ''; ?>>Teórica</option>
                                        <option value="practica" <?php echo ($horario_editar['tipo'] ?? '') == 'practica' ? 'selected' : ''; ?>>Práctica</option>
                                        <option value="laboratorio" <?php echo ($horario_editar['tipo'] ?? '') == 'laboratorio' ? 'selected' : ''; ?>>Laboratorio</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Hora de inicio *</label>
                                    <input type="time" name="hora_inicio" required 
                                           value="<?php echo isset($horario_editar['hora_inicio']) ? date('H:i', strtotime($horario_editar['hora_inicio'])) : '07:00'; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Hora de fin *</label>
                                    <input type="time" name="hora_fin" required 
                                           value="<?php echo isset($horario_editar['hora_fin']) ? date('H:i', strtotime($horario_editar['hora_fin'])) : '08:00'; ?>">
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn-action btn-green">
                                    <i class="bi bi-save"></i> Guardar Horario
                                </button>
                                <a href="horarios.php" class="btn-action">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                    
                <?php else: ?>
                    <!-- Lista de horarios -->
                    <div class="horario-grid">
                        <?php if ($horarios->num_rows > 0): ?>
                            <?php while($horario = $horarios->fetch_assoc()): ?>
                                <div class="horario-card">
                                    <div class="header">
                                        <div class="dia"><?php echo htmlspecialchars($horario['dia']); ?></div>
                                        <div class="badge" style="background: #6B2C91; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">
                                            <?php echo ucfirst($horario['tipo'] ?? 'teorica'); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="hora">
                                        <?php echo date('H:i', strtotime($horario['hora_inicio'])); ?> - 
                                        <?php echo date('H:i', strtotime($horario['hora_fin'])); ?>
                                    </div>
                                    
                                    <div style="margin-top: 10px; display: flex; gap: 10px;">
                                        <a href="?accion=editar&id=<?php echo $horario['id_horario']; ?>" 
                                           class="btn-action btn-blue btn-sm">
                                            <i class="bi bi-pencil"></i> Editar
                                        </a>
                                        <a href="?eliminar=<?php echo $horario['id_horario']; ?>" 
                                           onclick="return confirm('¿Eliminar este horario?')"
                                           class="btn-action btn-danger btn-sm">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                                <i class="bi bi-clock" style="font-size: 48px; color: #ddd;"></i>
                                <h3>No hay horarios configurados</h3>
                                <p>Crea horarios para asignarlos a los grupos de materias.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conexion->close(); ?>