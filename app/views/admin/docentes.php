<?php
// app/views/admin/docentes.php - VERSIÓN COMPLETA CORREGIDA

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';
require_once '../../utils/validaciones.php';

$mensaje = '';
$accion = $_GET['accion'] ?? 'listar';
$id_docente = $_GET['id'] ?? null;

// ========== PROCESAR FORMULARIO ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Sanitizar y validar datos del formulario
        $nombre = Validaciones::sanitizarTexto($_POST['nombre'] ?? '');
        $apellido = Validaciones::sanitizarTexto($_POST['apellido'] ?? '');
        $cedula = Validaciones::sanitizarTexto($_POST['cedula'] ?? '');
        // CORRECCIÓN: Cambiar sanitizarEmail por sanitizarTexto
        $correo = Validaciones::sanitizarTexto($_POST['correo'] ?? '');
        $telefono = Validaciones::sanitizarTexto($_POST['telefono'] ?? '');
        $titulo = Validaciones::sanitizarTexto($_POST['titulo_academico'] ?? '');
        $especialidad = Validaciones::sanitizarTexto($_POST['especialidad'] ?? '');
        $experiencia = Validaciones::sanitizarEntero($_POST['años_experiencia'] ?? 0);
        $estado = $_POST['estado'] ?? 'activo';
        
        // Validaciones básicas
        Validaciones::validarNoVacio($nombre, 'nombre');
        Validaciones::validarNoVacio($apellido, 'apellido');
        Validaciones::validarNoVacio($cedula, 'cédula');
        
        // Validar correo electrónico
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Correo electrónico inválido");
        }
        
        if (isset($_POST['id_docente'])) {
            // ========== ACTUALIZAR DOCENTE ==========
            $id_doc = Validaciones::sanitizarEntero($_POST['id_docente']);
            
            $stmt = $conexion->prepare("
                UPDATE docentes SET 
                nombre = ?, apellido = ?, cedula = ?, titulo_academico = ?, 
                especialidad = ?, telefono = ?, correo = ?, años_experiencia = ?, estado = ?
                WHERE id_docente = ?
            ");
            $stmt->bind_param("sssssssisi", 
                $nombre, $apellido, $cedula, $titulo, $especialidad,
                $telefono, $correo, $experiencia, $estado, $id_doc
            );
            $stmt->execute();
            
            $mensaje = '<div class="alert alert-success">Docente actualizado correctamente</div>';
            
        } else {
            // ========== CREAR NUEVO DOCENTE ==========
            $stmt = $conexion->prepare("
                INSERT INTO docentes 
                (nombre, apellido, cedula, titulo_academico, especialidad, telefono, correo, años_experiencia, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssssssis", 
                $nombre, $apellido, $cedula, $titulo, $especialidad,
                $telefono, $correo, $experiencia, $estado
            );
            $stmt->execute();
            
            $nuevo_id = $conexion->insert_id;
            $mensaje = '<div class="alert alert-success">Docente creado exitosamente (ID: ' . $nuevo_id . ')</div>';
        }
        
        // Crear usuario automáticamente si no existe
        $stmt_check = $conexion->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
        $stmt_check->bind_param("s", $correo);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows == 0) {
            // Crear usuario con contraseña por defecto (cédula)
            $password_hash = password_hash($cedula, PASSWORD_DEFAULT);
            $stmt_usuario = $conexion->prepare("
                INSERT INTO usuario (nombre, apellido, correo, password, rol, estado)
                VALUES (?, ?, ?, ?, 'docente', ?)
            ");
            $stmt_usuario->bind_param("sssss", $nombre, $apellido, $correo, $password_hash, $estado);
            $stmt_usuario->execute();
            
            $mensaje .= '<div class="alert alert-info">Usuario creado automáticamente (contraseña: cédula)</div>';
        }
        
        // Redirigir para evitar reenvío del formulario
        header('Location: docentes.php?accion=listar&mensaje=' . urlencode('Operación exitosa'));
        exit();
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// ========== OBTENER DOCENTE PARA EDITAR ==========
$docente_editar = null;
if ($id_docente && $accion == 'editar') {
    $stmt = $conexion->prepare("SELECT * FROM docentes WHERE id_docente = ?");
    $stmt->bind_param("i", $id_docente);
    $stmt->execute();
    $docente_editar = $stmt->get_result()->fetch_assoc();
    
    if (!$docente_editar) {
        header('Location: docentes.php?mensaje=' . urlencode('Docente no encontrado'));
        exit();
    }
}

// ========== ELIMINAR DOCENTE ==========
if ($accion === 'eliminar' && $id_docente) {
    try {
        // Verificar si tiene materias asignadas
        $stmt_check = $conexion->prepare("SELECT COUNT(*) as total FROM materias WHERE id_docente = ?");
        $stmt_check->bind_param("i", $id_docente);
        $stmt_check->execute();
        $result = $stmt_check->get_result()->fetch_assoc();
        
        if ($result['total'] > 0) {
            // Solo marcar como inactivo
            $stmt = $conexion->prepare("UPDATE docentes SET estado = 'inactivo' WHERE id_docente = ?");
            $stmt->bind_param("i", $id_docente);
            $stmt->execute();
            $msg = "Docente marcado como inactivo (tiene materias asignadas)";
        } else {
            // Eliminar completamente
            $stmt = $conexion->prepare("DELETE FROM docentes WHERE id_docente = ?");
            $stmt->bind_param("i", $id_docente);
            $stmt->execute();
            $msg = "Docente eliminado exitosamente";
        }
        
        // Registrar en auditoría
        $audit = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
        $accion_audit = "Eliminó docente ID: $id_docente";
        $audit->bind_param("ss", $_SESSION['user_name'], $accion_audit);
        $audit->execute();
        
        header('Location: docentes.php?mensaje=' . urlencode($msg));
        exit();
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// ========== OBTENER LISTA DE DOCENTES ==========
$query = "SELECT d.*, 
                 COUNT(m.id_materia) as total_materias
          FROM docentes d
          LEFT JOIN materias m ON d.id_docente = m.id_docente
          GROUP BY d.id_docente
          ORDER BY d.estado DESC, d.apellido, d.nombre";

$docentes = $conexion->query($query);
$total_docentes = $docentes->num_rows;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docentes - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/docentes.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'partials/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="docentes-container">
                <!-- Header -->
                <div class="header-actions">
                    <h1><i class="bi bi-person-video"></i> Administración de Docentes</h1>
                    <a href="?accion=nuevo" class="btn-action btn-green">
                        <i class="bi bi-plus-circle"></i> Nuevo Docente
                    </a>
                </div>
                
                <?php 
                if (isset($_GET['mensaje'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_GET['mensaje']) . '</div>';
                }
                echo $mensaje; 
                ?>
                
                <?php if ($accion == 'nuevo' || $accion == 'editar'): ?>
                    <!-- FORMULARIO DE DOCENTE -->
                    <div class="card">
                        <div class="card-header">
                            <h2>
                                <i class="bi bi-<?php echo $accion == 'nuevo' ? 'plus-circle' : 'pencil'; ?>"></i>
                                <?php echo $accion == 'nuevo' ? 'Nuevo Docente' : 'Editar Docente'; ?>
                            </h2>
                            <a href="docentes.php" class="btn-action">Cancelar</a>
                        </div>
                        
                        <form method="POST" action="">
                            <?php if ($docente_editar): ?>
                                <input type="hidden" name="id_docente" value="<?php echo $docente_editar['id_docente']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nombre *</label>
                                    <input type="text" name="nombre" 
                                           value="<?php echo htmlspecialchars($docente_editar['nombre'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Apellido *</label>
                                    <input type="text" name="apellido" 
                                           value="<?php echo htmlspecialchars($docente_editar['apellido'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Cédula *</label>
                                    <input type="text" name="cedula" 
                                           value="<?php echo htmlspecialchars($docente_editar['cedula'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Correo UTP *</label>
                                    <input type="email" name="correo" 
                                           value="<?php echo htmlspecialchars($docente_editar['correo'] ?? ''); ?>" 
                                           required>
                                    <small class="text-muted">Se recomienda usar correo institucional @utp.edu.ec</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="text" name="telefono" 
                                           value="<?php echo htmlspecialchars($docente_editar['telefono'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Título Académico</label>
                                    <input type="text" name="titulo_academico" 
                                           value="<?php echo htmlspecialchars($docente_editar['titulo_academico'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Especialidad</label>
                                    <input type="text" name="especialidad" 
                                           value="<?php echo htmlspecialchars($docente_editar['especialidad'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Años de Experiencia</label>
                                    <input type="number" name="años_experiencia" min="0" 
                                           value="<?php echo $docente_editar['años_experiencia'] ?? 0; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="estado" required>
                                        <option value="activo" <?php echo ($docente_editar['estado'] ?? 'activo') == 'activo' ? 'selected' : ''; ?>>Activo</option>
                                        <option value="inactivo" <?php echo ($docente_editar['estado'] ?? '') == 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                        <option value="licencia" <?php echo ($docente_editar['estado'] ?? '') == 'licencia' ? 'selected' : ''; ?>>Licencia</option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-action btn-green">
                                <i class="bi bi-save"></i> Guardar Docente
                            </button>
                        </form>
                    </div>
                    
                <?php else: ?>
                    <!-- LISTA DE DOCENTES -->
                    <div class="table-container">
                        <table class="docentes-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Docente</th>
                                    <th>Cédula</th>
                                    <th>Correo</th>
                                    <th>Título</th>
                                    <th>Experiencia</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($total_docentes > 0): ?>
                                    <?php while($doc = $docentes->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $doc['id_docente']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($doc['nombre'] . ' ' . $doc['apellido']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($doc['especialidad']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($doc['cedula']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['correo']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['titulo_academico']); ?></td>
                                        <td><?php echo $doc['años_experiencia']; ?> años</td>
                                        <td>
                                            <?php 
                                            $badge_class = '';
                                            switch($doc['estado']) {
                                                case 'activo': $badge_class = 'badge-success'; break;
                                                case 'inactivo': $badge_class = 'badge-warning'; break;
                                                case 'licencia': $badge_class = 'badge-info'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($doc['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 5px;">
                                                <a href="?accion=editar&id=<?php echo $doc['id_docente']; ?>" 
                                                   class="btn-action btn-blue btn-sm" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="?accion=eliminar&id=<?php echo $doc['id_docente']; ?>" 
                                                   onclick="return confirm('¿Eliminar a <?php echo addslashes($doc['nombre'] . ' ' . $doc['apellido']); ?>?')"
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
                                            <i class="bi bi-person-x"></i>
                                            <h3>No hay docentes registrados</h3>
                                            <p>Comienza agregando el primer docente</p>
                                            <a href="?accion=nuevo" class="btn-action btn-green">
                                                <i class="bi bi-plus-circle"></i> Agregar Docente
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_docentes > 0): ?>
                    <div class="summary-box">
                        <strong>Total:</strong> <?php echo $total_docentes; ?> docente(s)
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conexion->close(); ?>