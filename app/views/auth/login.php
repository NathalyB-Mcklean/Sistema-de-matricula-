<?php
// app/views/auth/login.php - VERSIÓN CORREGIDA Y FUNCIONAL

// Ruta ABSOLUTA para evitar problemas
$base_path = "C:/wamp64/www/Sistema-de-matricula-/app";

// Verificar si los archivos existen
$conexion_file = $base_path . "/config/conexion.php";
$validaciones_file = $base_path . "/utils/validaciones.php";

if (!file_exists($conexion_file)) {
    die("ERROR: No se encuentra 'conexion.php' en: $conexion_file<br>
         Por favor, crea el archivo en esa ubicación.");
}

if (!file_exists($validaciones_file)) {
    die("ERROR: No se encuentra 'validaciones.php' en: $validaciones_file<br>
         Por favor, crea el archivo en esa ubicación.");
}

// Incluir los archivos
require_once $conexion_file;
require_once $validaciones_file;

// Iniciar sesión
session_start();

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $correo = trim($_POST["correo"]);
        $password = trim($_POST["password"]);
        
        // Validar
        validarNoVacio($correo, "correo");
        validarNoVacio($password, "contraseña");
        validarCorreoUTP($correo);
        
        // Buscar usuario
        $stmt = $conexion->prepare("SELECT * FROM usuario WHERE correo = ? AND estado = 'activo'");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 0) {
            throw new Exception("Correo o contraseña incorrectos.");
        }
        
        $usuario = $resultado->fetch_assoc();
        
        // Verificar contraseña
        if (!password_verify($password, $usuario["password"])) {
            throw new Exception("Correo o contraseña incorrectos.");
        }
        
        // Crear sesión
        $_SESSION["user_id"] = $usuario["id_usuario"];
        $_SESSION["user_name"] = $usuario["nombre"] . " " . $usuario["apellido"];
        $_SESSION["user_email"] = $usuario["correo"];
        $_SESSION["user_role"] = $usuario["rol"];
        
        // Redirigir según rol
        if ($usuario["rol"] === "admin") {
            header("Location: ../../../public/index.php?page=admin&action=dashboard");
        } else {
            header("Location: ../../../public/index.php?page=estudiante&action=dashboard");
        }
        exit();
        
    } catch (Exception $e) {
        $mensaje = "<p style='color:red; font-weight:bold;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistema de Matrícula</title>
</head>
<body>
    <h2>Iniciar Sesión</h2>
    
    <?php 
    // Mostrar mensajes de error si existen
    if (isset($_SESSION['error'])) {
        echo "<div class='error'>" . $_SESSION['error'] . "</div>";
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['success'])) {
        echo "<div class='success'>" . $_SESSION['success'] . "</div>";
        unset($_SESSION['success']);
    }
    
    echo $mensaje; 
    ?>
    
    <form method="POST" action="">
        <label>Correo institucional (@utp.ac.pa):</label><br>
        <input type="email" name="correo" placeholder="ejemplo@utp.ac.pa" required><br><br>
        
        <label>Contraseña:</label><br>
        <input type="password" name="password" required><br><br>
        
        <button type="submit">Ingresar</button>
    </form>
    
    <p style="text-align: center; margin-top: 20px;">
        ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
    </p>
    
    <hr>
    <p style="font-size: 12px; color: #666; text-align: center;">
        Sistema de Matrícula UTP © 2025<br>
    </p>
</body>
</html>