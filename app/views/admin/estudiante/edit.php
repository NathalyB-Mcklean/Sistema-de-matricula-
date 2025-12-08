<?php
// app/views/admin/estudiantes/edit.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../../config/conexion.php';

$id_estudiante = $_GET['id'] ?? null;
if (!$id_estudiante) {
    header('Location: index.php');
    exit();
}

// Obtener datos del estudiante
$query = "SELECT e.*, u.nombre, u.apellido, u.correo, u.estado as estado_usuario,
                 c.id_carrera, c.nombre as carrera_nombre, c.codigo as carrera_codigo
          FROM estudiantes e
          JOIN usuario u ON e.id_usuario = u.id_usuario
          LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
          WHERE e.id_estudiante = ?";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $id_estudiante);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();

if (!$estudiante) {
    header('Location: index.php');
    exit();
}

// Obtener carreras para el select
$carreras = $conexion->query("SELECT id_carrera, nombre, codigo FROM carreras WHERE estado = 'activa' ORDER BY nombre");

// Mensajes de sesión
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Estudiante - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/estudiantes.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>

        <main class="main-content">
            <div class="estudiantes-container">
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="../dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
                    <span> / </span>
                    <a href="index.php">Estudiantes</a>
                    <span> / </span>
                    <span>Editar Estudiante</span>
                </div>
                
                <!-- Header -->
                <div class="header-actions">
                    <h1>
                        <i class="bi bi-pencil-square"></i>
                        Editar Estudiante
                    </h1>
                </div>
                
                <!-- Mensajes -->
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <!-- Formulario de edición -->
                <div class="card" style="max-width: 900px; margin: 0 auto;">
                    <div class="card-header">
                        <h3>Información del Estudiante</h3>
                    </div>
                    <div class="card-body">
                        <form action="update.php" method="POST" id="formEditar">
                            <input type="hidden" name="id_estudiante" value="<?php echo $estudiante['id_estudiante']; ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="required">Nombre</label>
                                    <input type="text" name="nombre" value="<?php echo htmlspecialchars($estudiante['nombre']); ?>" 
                                           required class="form-control">
                                </div>
                                <div class="form-group">
                                    <label class="required">Apellido</label>
                                    <input type="text" name="apellido" value="<?php echo htmlspecialchars($estudiante['apellido']); ?>" 
                                           required class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="required">Cédula</label>
                                    <input type="text" name="cedula" value="<?php echo htmlspecialchars($estudiante['cedula']); ?>" 
                                           required class="form-control">
                                </div>
                                <div class="form-group">
                                    <label class="required">Correo Electrónico</label>
                                    <input type="email" name="correo" value="<?php echo htmlspecialchars($estudiante['correo']); ?>" 
                                           required class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($estudiante['telefono'] ?? ''); ?>" 
                                           class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Fecha de Nacimiento</label>
                                    <input type="date" name="fecha_nacimiento" 
                                           value="<?php echo $estudiante['fecha_nacimiento'] ?? ''; ?>" 
                                           class="form-control">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Dirección</label>
                                <textarea name="direccion" rows="3" class="form-control"><?php echo htmlspecialchars($estudiante['direccion'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Carrera</label>
                                    <select name="id_carrera" class="form-control">
                                        <option value="">Seleccionar carrera...</option>
                                        <?php while($carrera = $carreras->fetch_assoc()): ?>
                                        <option value="<?php echo $carrera['id_carrera']; ?>"
                                            <?php echo ($estudiante['id_carrera'] == $carrera['id_carrera']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($carrera['codigo'] . ' - ' . $carrera['nombre']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Estado</label>
                                    <select name="estado" class="form-control">
                                        <option value="activo" <?php echo ($estudiante['estado_usuario'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                                        <option value="inactivo" <?php echo ($estudiante['estado_usuario'] == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Año de Carrera</label>
                                    <select name="año_carrera" class="form-control">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($estudiante['año_carrera'] == $i) ? 'selected' : ''; ?>>
                                            Año <?php echo $i; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Semestre Actual</label>
                                    <select name="semestre_actual" class="form-control">
                                        <option value="1" <?php echo ($estudiante['semestre_actual'] == 1) ? 'selected' : ''; ?>>Semestre 1</option>
                                        <option value="2" <?php echo ($estudiante['semestre_actual'] == 2) ? 'selected' : ''; ?>>Semestre 2</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Cambiar Contraseña (dejar en blanco para mantener la actual)</label>
                                <input type="password" name="password" placeholder="Nueva contraseña" 
                                       class="form-control">
                                <small class="form-text text-muted">Mínimo 6 caracteres</small>
                            </div>
                            
                            <div class="form-actions" style="display: flex; gap: 15px; margin-top: 30px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Actualizar Estudiante
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('formEditar').addEventListener('submit', function(e) {
            const nombre = this.nombre.value.trim();
            const apellido = this.apellido.value.trim();
            const cedula = this.cedula.value.trim();
            const correo = this.correo.value.trim();
            
            if (!nombre || !apellido || !cedula || !correo) {
                e.preventDefault();
                alert('Por favor complete todos los campos obligatorios (*)');
                return false;
            }
            
            if (!/^[0-9]{10,13}$/.test(cedula)) {
                e.preventDefault();
                alert('La cédula debe tener entre 10 y 13 dígitos');
                return false;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
                e.preventDefault();
                alert('Por favor ingrese un correo electrónico válido');
                return false;
            }
            
            const password = this.password.value;
            if (password && password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
<?php $conexion->close(); ?>