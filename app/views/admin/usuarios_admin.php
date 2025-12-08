<?php
// app/views/admin/usuarios_admin.php - CRUD USUARIOS ADMINISTRATIVOS

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';
require_once '../../utils/validaciones.php';

$mensaje = '';
$accion = $_GET['accion'] ?? 'listar';
$id_usuario = $_GET['id'] ?? null;

// ========== PROCESAR FORMULARIO ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Sanitizar y validar
        $nombre = Validaciones::sanitizarTexto($_POST['nombre']);
        $apellido = Validaciones::sanitizarTexto($_POST['apellido']);
        $correo = Validaciones::validarCorreoUTP($_POST['correo']);
        $rol = Validaciones::sanitizarTexto($_POST['rol']);
        $estado = $_POST['estado'] ?? 'activo';
        $password = $_POST['password'] ?? '';
        
        Validaciones::validarNoVacio($nombre, 'nombre');
        Validaciones::validarNoVacio($apellido, 'apellido');
        
        // Validar roles permitidos
        $roles_permitidos = ['admin', 'coordinador', 'secretario'];
        if (!in_array($rol, $roles_permitidos)) {
            throw new Exception("Rol no válido");
        }
        
        if (isset($_POST['id_usuario'])) {
            // ========== ACTUALIZAR USUARIO ==========
            $id_user = Validaciones::sanitizarEntero($_POST['id_usuario']);
            
            // Verificar si es el último admin activo
            if ($rol != 'admin' && $estado == 'inactivo') {
                $stmt_check = $conexion->prepare("
                    SELECT COUNT(*) as total 
                    FROM usuario 
                    WHERE rol = 'admin' AND estado = 'activo' AND id_usuario != ?
                ");
                $stmt_check->bind_param("i", $id_user);
                $stmt_check->execute();
                $result = $stmt_check->get_result()->fetch_assoc();
                
                if ($result['total'] == 0) {
                    throw new Exception("No puede inactivar/eliminar el único administrador activo");
                }
            }
            
            if (!empty($password)) {
                Validaciones::validarPassword($password);
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conexion->prepare("
                    UPDATE usuario SET 
                    nombre = ?, apellido = ?, correo = ?, password = ?, rol = ?, estado = ?
                    WHERE id_usuario = ?
                ");
                $stmt->bind_param("ssssssi", $nombre, $apellido, $correo, $password_hash, $rol, $estado, $id_user);
            } else {
                $stmt = $conexion->prepare("
                    UPDATE usuario SET 
                    nombre = ?, apellido = ?, correo = ?, rol = ?, estado = ?
                    WHERE id_usuario = ?
                ");
                $stmt->bind_param("sssssi", $nombre, $apellido, $correo, $rol, $estado, $id_user);
            }
            $stmt->execute();
            
            $mensaje = '<div class="alert alert-success">Usuario actualizado</div>';
            
        } else {
            // ========== CREAR NUEVO USUARIO ==========
            Validaciones::validarPassword($password);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Verificar correo único
            $stmt_check = $conexion->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
            $stmt_check->bind_param("s", $correo);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                throw new Exception("Ya existe un usuario con ese correo");
            }
            
            $stmt = $conexion->prepare("
                INSERT INTO usuario (nombre, apellido, correo, password, rol, estado)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssss", $nombre, $apellido, $correo, $password_hash, $rol, $estado);
            $stmt->execute();
            
            $mensaje = '<div class="alert alert-success">Usuario creado</div>';
        }
        
        // Auditoría
        $audit = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
        $accion_audit = isset($_POST['id_usuario']) ? "Actualizó usuario administrativo" : "Creó usuario administrativo";
        $audit->bind_param("ss", $_SESSION['user_name'], $accion_audit);
        $audit->execute();
        
        header('Location: usuarios_admin.php?mensaje=' . urlencode('Operación exitosa'));
        exit();
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// ========== OBTENER USUARIO PARA EDITAR ==========
$usuario_editar = null;
if ($id_usuario && $accion == 'editar') {
    $stmt = $conexion->prepare("SELECT * FROM usuario WHERE id_usuario = ? AND rol != 'estudiante'");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $usuario_editar = $stmt->get_result()->fetch_assoc();
}

// ========== ELIMINAR USUARIO ==========
if ($accion === 'eliminar' && $id_usuario) {
    try {
        // Verificar si es el último admin
        $stmt_check = $conexion->prepare("
            SELECT rol FROM usuario WHERE id_usuario = ?
        ");
        $stmt_check->bind_param("i", $id_usuario);
        $stmt_check->execute();
        $user = $stmt_check->get_result()->fetch_assoc();
        
        if ($user['rol'] == 'admin') {
            // Verificar si es el último admin activo
            $stmt_check2 = $conexion->prepare("
                SELECT COUNT(*) as total 
                FROM usuario 
                WHERE rol = 'admin' AND estado = 'activo' AND id_usuario != ?
            ");
            $stmt_check2->bind_param("i", $id_usuario);
            $stmt_check2->execute();
            $result = $stmt_check2->get_result()->fetch_assoc();
            
            if ($result['total'] == 0) {
                throw new Exception("No puede eliminar el único administrador activo");
            }
        }
        
        // Marcar como inactivo (no eliminar físicamente)
        $stmt = $conexion->prepare("UPDATE usuario SET estado = 'inactivo' WHERE id_usuario = ?");
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        
        // Auditoría
        $audit = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
        $accion_audit = "Eliminó usuario administrativo ID: $id_usuario";
        $audit->bind_param("ss", $_SESSION['user_name'], $accion_audit);
        $audit->execute();
        
        header('Location: usuarios_admin.php?mensaje=' . urlencode('Usuario marcado como inactivo'));
        exit();
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// ========== OBTENER LISTA DE USUARIOS ADMIN ==========
$query = "SELECT * FROM usuario WHERE rol != 'estudiante' ORDER BY rol, apellido, nombre";
$usuarios = $conexion->query($query);
$total_usuarios = $usuarios->num_rows;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Administrativos - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/usuarios.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'partials/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="usuarios-container">
                <!-- Header -->
                <div class="header-actions">
                    <h1><i class="bi bi-people-fill"></i> Usuarios Administrativos</h1>
                    <a href="?accion=nuevo" class="btn-action btn-green">
                        <i class="bi bi-plus-circle"></i> Nuevo Usuario
                    </a>
                </div>
                
                <?php 
                if (isset($_GET['mensaje'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['mensaje']) . '</div>';
                }
                echo $mensaje; 
                ?>
                
                <?php if ($accion == 'nuevo' || $accion == 'editar'): ?>
                    <!-- FORMULARIO DE USUARIO -->
                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <i class="bi bi-<?php echo $accion == 'nuevo' ? 'plus-circle' : 'pencil'; ?>"></i>
                                <?php echo $accion == 'nuevo' ? 'Nuevo Usuario' : 'Editar Usuario'; ?>
                            </h2>
                            <a href="usuarios_admin.php" class="btn-action">Cancelar</a>
                        </div>
                        
                        <form method="POST" action="">
                            <?php if ($usuario_editar): ?>
                                <input type="hidden" name="id_usuario" value="<?php echo $usuario_editar['id_usuario']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nombre *</label>
                                    <input type="text" name="nombre" 
                                           value="<?php echo htmlspecialchars($usuario_editar['nombre'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Apellido *</label>
                                    <input type="text" name="apellido" 
                                           value="<?php echo htmlspecialchars($usuario_editar['apellido'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Correo UTP *</label>
                                    <input type="email" name="correo" 
                                           value="<?php echo htmlspecialchars($usuario_editar['correo'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Contraseña <?php echo $accion == 'nuevo' ? '*' : '(dejar en blanco para no cambiar)'; ?></label>
                                    <input type="password" name="password" 
                                           <?php echo $accion == 'nuevo' ? 'required' : ''; ?>>
                                </div>
                                
                                <div class="form-group">
                                    <label>Rol *</label>
                                    <select name="rol" required>
                                        <option value="admin" <?php echo ($usuario_editar['rol'] ?? '') == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                        <option value="coordinador" <?php echo ($usuario_editar['rol'] ?? '') == 'coordinador' ? 'selected' : ''; ?>>Coordinador</option>
                                        <option value="secretario" <?php echo ($usuario_editar['rol'] ?? '') == 'secretario' ? 'selected' : ''; ?>>Secretario</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Estado *</label>
                                    <select name="estado" required>
                                        <option value="activo" <?php echo ($usuario_editar['estado'] ?? 'activo') == 'activo' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="inactivo" <?php echo ($usuario_editar['estado'] ?? '') == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-action btn-green">
                                <i class="bi bi-save"></i> Guardar Usuario
                            </button>
                        </form>
                    </div>
                    
                <?php else: ?>
                    <!-- LISTA DE USUARIOS -->
                    <div class="table-container">
                        <table class="usuarios-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Correo</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($total_usuarios > 0): ?>
                                    <?php while($user = $usuarios->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $user['id_usuario']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['nombre'] . ' ' . $user['apellido']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['correo']); ?></td>
                                        <td>
                                            <?php 
                                            $badge_class = '';
                                            switch($user['rol']) {
                                                case 'admin': $badge_class = 'badge-danger'; break;
                                                case 'coordinador': $badge_class = 'badge-primary'; break;
                                                case 'secretario': $badge_class = 'badge-info'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($user['rol']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $user['estado'] == 'activo' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($user['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <a href="?accion=editar&id=<?php echo $user['id_usuario']; ?>" 
                                                   class="btn-action btn-blue btn-sm" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($user['id_usuario'] != $_SESSION['user_id']): ?>
                                                <a href="?accion=eliminar&id=<?php echo $user['id_usuario']; ?>" 
                                                   onclick="return confirm('¿Marcar como inactivo a <?php echo addslashes($user['nombre'] . ' ' . $user['apellido']); ?>?')"
                                                   class="btn-action btn-danger btn-sm" title="Eliminar">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="bi bi-people"></i>
                                            <h3>No hay usuarios administrativos</h3>
                                            <p>Comienza agregando el primer usuario</p>
                                            <a href="?accion=nuevo" class="btn-action btn-green">
                                                <i class="bi bi-plus-circle"></i> Agregar Usuario
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_usuarios > 0): ?>
                    <div class="summary-box">
                        <strong>Total:</strong> <?php echo $total_usuarios; ?> usuario(s) administrativo(s)
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conexion->close(); ?>