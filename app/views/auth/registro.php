<?php
// app/views/auth/registro.php

// Ruta ABSOLUTA
$base_path = "C:/wamp64/www/Sistema-de-matricula-/app";

$conexion_file = $base_path . "/config/conexion.php";
$validaciones_file = $base_path . "/utils/validaciones.php";

if (!file_exists($conexion_file)) {
    die("ERROR: No se encuentra 'conexion.php'");
}

if (!file_exists($validaciones_file)) {
    die("ERROR: No se encuentra 'validaciones.php'");
}

require_once $conexion_file;
require_once $validaciones_file;

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Obtener valores
        $nombre     = trim($_POST["nombre"]);
        $apellido   = trim($_POST["apellido"]);
        $correo     = trim($_POST["correo"]);
        $password   = trim($_POST["password"]);
        $password2  = trim($_POST["password2"]);
        $rol        = "estudiante";   // ROL FIJO

        // Validaciones
        validarNoVacio($nombre, "nombre");
        validarNoVacio($apellido, "apellido");
        validarCorreoUTP($correo);
        validarPassword($password);
        validarCoincidenciaPassword($password, $password2);

        // Encriptar contraseña
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Verificar si ya existe
        $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            throw new Exception("Ya existe una cuenta con ese correo institucional.");
        }

        // Insertar usuario
        $insert = $conexion->prepare("
            INSERT INTO usuario (nombre, apellido, correo, password, rol)
            VALUES (?, ?, ?, ?, ?)
        ");

        $insert->bind_param("sssss", $nombre, $apellido, $correo, $passwordHash, $rol);

        if ($insert->execute()) {
            $mensaje = "<p style='color:green; font-weight:bold;'> Usuario registrado exitosamente. Ahora puedes iniciar sesión.</p>";
            
            // iniciar sesión automáticamente
             $userId = $conexion->insert_id;
             $_SESSION['user_id'] = $userId;
             $_SESSION['user_name'] = $nombre . " " . $apellido;
             $_SESSION['user_email'] = $correo;
             $_SESSION['user_role'] = $rol;
            header("Location: ../../../public/index.php?page=estudiante&action=dashboard");
             exit();
        } else {
            throw new Exception("Error al registrar usuario. Intenta más tarde.");
        }

    } catch (Exception $e) {
        $mensaje = "<p style='color:red; font-weight:bold;'> Error: " . $e->getMessage() . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - Sistema de Matrícula</title>
</head>
<body>
    <h2>Registro de Usuario</h2>
    
    <?php echo $mensaje; ?>
    
    <form method="POST" action="">
        <label>Nombre:</label><br>
        <input type="text" name="nombre" placeholder="Ej: Juan" required><br><br>
        
        <label>Apellido:</label><br>
        <input type="text" name="apellido" placeholder="Ej: Pérez" required><br><br>
        
        <label>Correo institucional:</label><br>
        <input type="email" name="correo" placeholder="juan.perez@utp.ac.pa" required><br><br>
        
        <label>Contraseña (mínimo 8 caracteres):</label><br>
        <input type="password" name="password" required><br><br>
        
        <label>Repetir contraseña:</label><br>
        <input type="password" name="password2" required><br><br>
        
        <button type="submit">Registrarse</button>
    </form>
    
    <p style="text-align: center; margin-top: 20px;">
        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
    </p>
</body>
</html>