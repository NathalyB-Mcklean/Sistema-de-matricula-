<?php
// app/views/admin/estudiantes/create.php
// FALTA: sesión, validación, sidebar, estructura completa

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../../config/conexion.php';

// Obtener carreras
$carreras = $conexion->query("SELECT id_carrera, nombre, codigo FROM carreras WHERE estado = 'activa' ORDER BY nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Estudiante - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/estudiantes.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/../partials/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="container">
                <h2><i class="bi bi-person-plus"></i> Nuevo Estudiante</h2>
                
                <!-- Mensajes -->
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="store.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre *</label>
                            <input type="text" name="nombre" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Apellido *</label>
                            <input type="text" name="apellido" required class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Cédula *</label>
                            <input type="text" name="cedula" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Correo *</label>
                            <input type="email" name="correo" required class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Fecha Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Dirección</label>
                        <textarea name="direccion" rows="3" class="form-control"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Carrera</label>
                            <select name="id_carrera" class="form-control">
                                <option value="">Seleccionar carrera...</option>
                                <?php while($carrera = $carreras->fetch_assoc()): ?>
                                <option value="<?php echo $carrera['id_carrera']; ?>">
                                    <?php echo htmlspecialchars($carrera['codigo'] . ' - ' . $carrera['nombre']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Contraseña *</label>
                            <input type="password" name="password" required class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Año Carrera</label>
                            <select name="año_carrera" class="form-control">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>">Año <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Semestre</label>
                            <select name="semestre_actual" class="form-control">
                                <option value="1">Semestre 1</option>
                                <option value="2">Semestre 2</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Guardar
                        </button>
                        <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conexion->close(); ?>