<?php
// app/views/admin/estudiantes.php - VERSIÓN COMPLETA CON CRUD

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';
require_once '../../utils/validaciones.php';

$mensaje = '';
$accion = $_GET['accion'] ?? 'listar';
$id_estudiante = $_GET['id'] ?? null;

// ========== PROCESAR FORMULARIO ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Sanitizar y validar datos
        $nombre = Validaciones::sanitizarTexto($_POST['nombre']);
        $apellido = Validaciones::sanitizarTexto($_POST['apellido']);
        $cedula = Validaciones::sanitizarTexto($_POST['cedula']);
        $correo = Validaciones::validarCorreoUTP($_POST['correo']);
        $telefono = Validaciones::sanitizarTexto($_POST['telefono']);
        $año_carrera = Validaciones::validarRango($_POST['año_carrera'], 1, 5, 'año de carrera');
        $semestre_actual = Validaciones::validarRango($_POST['semestre_actual'], 1, 2, 'semestre actual');
        $id_carrera = Validaciones::sanitizarEntero($_POST['id_carrera']);
        $password = $_POST['password'] ?? '';
        $estado = $_POST['estado'] ?? 'activo';
        
        // Validaciones específicas
        Validaciones::validarNoVacio($nombre, 'nombre');
        Validaciones::validarNoVacio($apellido, 'apellido');
        Validaciones::validarNoVacio($cedula, 'cédula');
        
        // Verificar si el correo ya existe (solo para nuevo)
        if (!isset($_POST['id_estudiante'])) {
            $stmt_check = $conexion->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
            $stmt_check->bind_param("s", $correo);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                throw new Exception("Ya existe un usuario con ese correo.");
            }
        }
        
        if (isset($_POST['id_estudiante'])) {
            // ========== ACTUALIZAR ESTUDIANTE ==========
            $id_est = Validaciones::sanitizarEntero($_POST['id_estudiante']);
            
            // 1. Obtener id_usuario del estudiante
            $stmt_get = $conexion->prepare("SELECT id_usuario FROM estudiantes WHERE id_estudiante = ?");
            $stmt_get->bind_param("i", $id_est);
            $stmt_get->execute();
            $result = $stmt_get->get_result()->fetch_assoc();
            $id_usuario = $result['id_usuario'];
            
            // 2. Actualizar tabla usuario
            if (!empty($password)) {
                Validaciones::validarPassword($password);
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt_usuario = $conexion->prepare("UPDATE usuario SET nombre = ?, apellido = ?, correo = ?, password = ?, estado = ? WHERE id_usuario = ?");
                $stmt_usuario->bind_param("sssssi", $nombre, $apellido, $correo, $password_hash, $estado, $id_usuario);
            } else {
                $stmt_usuario = $conexion->prepare("UPDATE usuario SET nombre = ?, apellido = ?, correo = ?, estado = ? WHERE id_usuario = ?");
                $stmt_usuario->bind_param("ssssi", $nombre, $apellido, $correo, $estado, $id_usuario);
            }
            $stmt_usuario->execute();
            
            // 3. Actualizar tabla estudiantes
            $stmt_est = $conexion->prepare("UPDATE estudiantes SET cedula = ?, telefono = ?, año_carrera = ?, semestre_actual = ?, id_carrera = ? WHERE id_estudiante = ?");
            $stmt_est->bind_param("ssiiii", $cedula, $telefono, $año_carrera, $semestre_actual, $id_carrera, $id_est);
            $stmt_est->execute();
            
            $mensaje = '<div class="alert alert-success">Estudiante actualizado correctamente</div>';
            
        } else {
            // ========== CREAR NUEVO ESTUDIANTE ==========
            // 1. Validar contraseña para nuevo estudiante
            Validaciones::validarPassword($password);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // 2. Insertar en usuario (rol = 'estudiante')
            $stmt_usuario = $conexion->prepare("INSERT INTO usuario (nombre, apellido, correo, password, rol, estado) VALUES (?, ?, ?, ?, 'estudiante', ?)");
            $stmt_usuario->bind_param("sssss", $nombre, $apellido, $correo, $password_hash, $estado);
            $stmt_usuario->execute();
            $id_usuario = $conexion->insert_id;
            
            // 3. Insertar en estudiantes
            $stmt_est = $conexion->prepare("INSERT INTO estudiantes (cedula, id_usuario, telefono, año_carrera, semestre_actual, id_carrera) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_est->bind_param("sisiii", $cedula, $id_usuario, $telefono, $año_carrera, $semestre_actual, $id_carrera);
            $stmt_est->execute();
            
            $mensaje = '<div class="alert alert-success">Estudiante creado correctamente</div>';
        }
        
        // Redirigir después de guardar
        header('Location: estudiantes.php?mensaje=' . urlencode('Operación realizada con éxito'));
        exit();
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// ========== OBTENER ESTUDIANTE PARA EDITAR ==========
$estudiante_editar = null;
if ($id_estudiante && $accion == 'editar') {
    $stmt = $conexion->prepare("
        SELECT e.*, u.nombre, u.apellido, u.correo, u.estado as estado_usuario
        FROM estudiantes e
        JOIN usuario u ON e.id_usuario = u.id_usuario
        WHERE e.id_estudiante = ?
    ");
    $stmt->bind_param("i", $id_estudiante);
    $stmt->execute();
    $estudiante_editar = $stmt->get_result()->fetch_assoc();
}

// ========== ELIMINAR ESTUDIANTE ==========
if ($accion === 'eliminar' && $id_estudiante) {
    try {
        // Verificar si tiene matrículas
        $stmt_check = $conexion->prepare("SELECT COUNT(*) as total FROM matriculas WHERE id_estudiante = ?");
        $stmt_check->bind_param("i", $id_estudiante);
        $stmt_check->execute();
        $result = $stmt_check->get_result()->fetch_assoc();
        
        if ($result['total'] > 0) {
            // Solo marcar como inactivo
            $stmt_get = $conexion->prepare("SELECT id_usuario FROM estudiantes WHERE id_estudiante = ?");
            $stmt_get->bind_param("i", $id_estudiante);
            $stmt_get->execute();
            $user_result = $stmt_get->get_result()->fetch_assoc();
            
            $stmt_update = $conexion->prepare("UPDATE usuario SET estado = 'inactivo' WHERE id_usuario = ?");
            $stmt_update->bind_param("i", $user_result['id_usuario']);
            $stmt_update->execute();
            
            $msg = "Estudiante marcado como inactivo (tiene matrículas)";
        } else {
            // Eliminar completamente
            $stmt_get = $conexion->prepare("SELECT id_usuario FROM estudiantes WHERE id_estudiante = ?");
            $stmt_get->bind_param("i", $id_estudiante);
            $stmt_get->execute();
            $user_result = $stmt_get->get_result()->fetch_assoc();
            
            // Eliminar estudiante
            $stmt1 = $conexion->prepare("DELETE FROM estudiantes WHERE id_estudiante = ?");
            $stmt1->bind_param("i", $id_estudiante);
            $stmt1->execute();
            
            // Eliminar usuario
            $stmt2 = $conexion->prepare("DELETE FROM usuario WHERE id_usuario = ?");
            $stmt2->bind_param("i", $user_result['id_usuario']);
            $stmt2->execute();
            
            $msg = "Estudiante eliminado exitosamente";
        }
        
        // Auditoría
        $audit = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
        $accion_audit = "Eliminó estudiante ID: $id_estudiante";
        $audit->bind_param("ss", $_SESSION['user_name'], $accion_audit);
        $audit->execute();
        
        header('Location: estudiantes.php?mensaje=' . urlencode($msg));
        exit();
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// ========== OBTENER LISTA DE ESTUDIANTES ==========
$query = "SELECT e.*, u.nombre, u.apellido, u.correo, u.estado as estado_usuario, 
                 c.nombre as carrera_nombre
          FROM estudiantes e
          JOIN usuario u ON e.id_usuario = u.id_usuario
          LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
          ORDER BY u.apellido, u.nombre";

$estudiantes = $conexion->query($query);
$total_estudiantes = $estudiantes->num_rows;

// Obtener carreras para combobox
$carreras = $conexion->query("SELECT id_carrera, nombre FROM carreras WHERE estado = 'activa' ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudiantes - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/estudiantes.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'partials/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="estudiantes-container">
                <!-- Header -->
                <div class="header-actions">
                    <h1><i class="bi bi-people"></i> Administración de Estudiantes</h1>
                    <a href="?accion=nuevo" class="btn-action btn-green">
                        <i class="bi bi-plus-circle"></i> Nuevo Estudiante
                    </a>
                </div>
                
                <?php 
                if (isset($_GET['mensaje'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['mensaje']) . '</div>';
                }
                echo $mensaje; 
                ?>
                
                <?php if ($accion == 'nuevo' || $accion == 'editar'): ?>
                    <!-- FORMULARIO DE ESTUDIANTE -->
                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <i class="bi bi-<?php echo $accion == 'nuevo' ? 'plus-circle' : 'pencil'; ?>"></i>
                                <?php echo $accion == 'nuevo' ? 'Nuevo Estudiante' : 'Editar Estudiante'; ?>
                            </h2>
                            <a href="estudiantes.php" class="btn-action">Cancelar</a>
                        </div>
                        
                        <form method="POST" action="">
                            <?php if ($estudiante_editar): ?>
                                <input type="hidden" name="id_estudiante" value="<?php echo $estudiante_editar['id_estudiante']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nombre *</label>
                                    <input type="text" name="nombre" 
                                           value="<?php echo htmlspecialchars($estudiante_editar['nombre'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Apellido *</label>
                                    <input type="text" name="apellido" 
                                           value="<?php echo htmlspecialchars($estudiante_editar['apellido'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Cédula *</label>
                                    <input type="text" name="cedula" 
                                           value="<?php echo htmlspecialchars($estudiante_editar['cedula'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Correo UTP *</label>
                                    <input type="email" name="correo" 
                                           value="<?php echo htmlspecialchars($estudiante_editar['correo'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="text" name="telefono" 
                                           value="<?php echo htmlspecialchars($estudiante_editar['telefono'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Contraseña <?php echo $accion == 'nuevo' ? '*' : '(dejar en blanco para no cambiar)'; ?></label>
                                    <input type="password" name="password" 
                                           <?php echo $accion == 'nuevo' ? 'required' : ''; ?>>
                                </div>
                                
                                <div class="form-group">
                                    <label>Año de Carrera</label>
                                    <select name="año_carrera" required>
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                            <?php echo ($estudiante_editar['año_carrera'] ?? 1) == $i ? 'selected' : ''; ?>>
                                            Año <?php echo $i; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Semestre Actual</label>
                                    <select name="semestre_actual" required>
                                        <option value="1" <?php echo ($estudiante_editar['semestre_actual'] ?? 1) == 1 ? 'selected' : ''; ?>>1</option>
                                        <option value="2" <?php echo ($estudiante_editar['semestre_actual'] ?? 1) == 2 ? 'selected' : ''; ?>>2</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Carrera</label>
                                    <select name="id_carrera">
                                        <option value="">Sin carrera asignada</option>
                                        <?php while($carrera = $carreras->fetch_assoc()): ?>
                                        <option value="<?php echo $carrera['id_carrera']; ?>"
                                            <?php echo ($estudiante_editar['id_carrera'] ?? 0) == $carrera['id_carrera'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($carrera['nombre']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="estado" required>
                                        <option value="activo" <?php echo ($estudiante_editar['estado_usuario'] ?? 'activo') == 'activo' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="inactivo" <?php echo ($estudiante_editar['estado_usuario'] ?? '') == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-action btn-green">
                                <i class="bi bi-save"></i> Guardar Estudiante
                            </button>
                        </form>
                    </div>
                    
                <?php else: ?>
                    <!-- LISTA DE ESTUDIANTES -->
                    <div class="table-container">
                        <table class="estudiantes-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Estudiante</th>
                                    <th>Cédula</th>
                                    <th>Correo</th>
                                    <th>Carrera</th>
                                    <th>Año</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($total_estudiantes > 0): ?>
                                    <?php while($est = $estudiantes->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $est['id_estudiante']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($est['nombre'] . ' ' . $est['apellido']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($est['telefono'] ?? 'Sin teléfono'); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($est['cedula']); ?></td>
                                        <td><?php echo htmlspecialchars($est['correo']); ?></td>
                                        <td><?php echo htmlspecialchars($est['carrera_nombre'] ?? 'Sin asignar'); ?></td>
                                        <td>Año <?php echo $est['año_carrera']; ?> - S<?php echo $est['semestre_actual']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $est['estado_usuario'] == 'activo' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($est['estado_usuario']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <a href="?accion=editar&id=<?php echo $est['id_estudiante']; ?>" 
                                                   class="btn-action btn-blue btn-sm" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="matriculas.php?id_estudiante=<?php echo $est['id_estudiante']; ?>" 
                                                   class="btn-action btn-purple btn-sm" title="Ver matrículas">
                                                    <i class="bi bi-list-check"></i>
                                                </a>
                                                <a href="?accion=eliminar&id=<?php echo $est['id_estudiante']; ?>" 
                                                   onclick="return confirm('¿Eliminar a <?php echo addslashes($est['nombre'] . ' ' . $est['apellido']); ?>?')"
                                                   class="btn-action btn-danger btn-sm" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="bi bi-people"></i>
                                            <h3>No hay estudiantes registrados</h3>
                                            <p>Comienza agregando el primer estudiante</p>
                                            <a href="?accion=nuevo" class="btn-action btn-green">
                                                <i class="bi bi-plus-circle"></i> Agregar Estudiante
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_estudiantes > 0): ?>
                    <div class="summary-box">
                        <strong>Total:</strong> <?php echo $total_estudiantes; ?> estudiante(s)
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conexion->close(); ?>