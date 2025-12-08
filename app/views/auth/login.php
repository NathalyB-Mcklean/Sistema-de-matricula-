<?php
// app/views/auth/login.php - VERSIÓN COMPLETA CON OBTENCIÓN DE ID_ESTUDIANTE

// Ruta ABSOLUTA para evitar problemas
$base_path = "C:/wamp64/www/Sistema-de-matricula-/app";

// Verificar si los archivos existen
$conexion_file = $base_path . "/config/conexion.php";
$validaciones_file = $base_path . "/utils/validaciones.php";

if (!file_exists($conexion_file)) {
    die("ERROR: No se encuentra 'conexion.php'");
}

if (!file_exists($validaciones_file)) {
    die("ERROR: No se encuentra 'validaciones.php'");
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
        
        // ========== NUEVO: OBTENER ID_ESTUDIANTE SI ES ESTUDIANTE ==========
        if ($usuario["rol"] === "estudiante") {
            // Buscar el id_estudiante asociado
            $stmt_estudiante = $conexion->prepare("SELECT id_estudiante FROM estudiantes WHERE id_usuario = ?");
            $stmt_estudiante->bind_param("i", $usuario["id_usuario"]);
            $stmt_estudiante->execute();
            $resultado_estudiante = $stmt_estudiante->get_result();
            
            if ($resultado_estudiante->num_rows > 0) {
                $estudiante = $resultado_estudiante->fetch_assoc();
                $_SESSION["id_estudiante"] = $estudiante["id_estudiante"];
            } else {
                // Si no tiene perfil de estudiante, redirigir a completar perfil
                $_SESSION['error'] = "Tu perfil de estudiante no está completo. Por favor contacta con administración.";
                header("Location: registro.php?completar_perfil=true");
                exit();
            }
        }
        
        // Redirigir según rol
        if ($usuario["rol"] === "admin") {
            header("Location: ../../views/admin/dashboard.php");
        } else {
            header("Location: ../../views/estudiante/dashboard.php");
        }
        exit();
        
    } catch (Exception $e) {
        $mensaje = "<div class='error'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Matrícula UTP</title>
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/style.css">
    <style>
        /* Estilos específicos para la página de login */
        .login-logo {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            display: block;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #2d8659;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #2d8659;
            box-shadow: 0 0 0 2px rgba(45, 134, 89, 0.1);
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .remember-forgot a {
            color: #2d8659;
            text-decoration: none;
        }
        
        .remember-forgot a:hover {
            text-decoration: underline;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #2d8659, #1a5c3a);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .login-button:hover {
            background: linear-gradient(135deg, #1a5c3a, #2d8659);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 92, 58, 0.2);
        }
        
        .login-button:active {
            transform: translateY(0);
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }
        
        .register-link a {
            color: #6B2C91;
            font-weight: 600;
            text-decoration: none;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .demo-credentials {
            margin-top: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #6B2C91;
            font-size: 13px;
        }
        
        .demo-credentials h4 {
            color: #6B2C91;
            margin-top: 0;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .demo-credentials p {
            margin: 5px 0;
            color: #555;
        }
        
        .demo-credentials strong {
            color: #333;
        }
        
        @media (max-width: 480px) {
            .login-logo {
                width: 100px;
                height: 100px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
            
            .form-group input {
                padding: 10px 12px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo-container">
            <img src="http://localhost/Sistema-de-matricula-/app/public/assets/images/utp.png" alt="Logo UTP" class="logo-utp">
        </div>

        <div class="login-header">
            <h1>Sistema de Matrícula UTP</h1>
            <p>Accede a tu cuenta institucional</p>
        </div>
        
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
            <div class="form-group">
                <label>Correo institucional</label>
                <input type="email" name="correo" placeholder="usuario@utp.ac.pa" required>
            </div>
            
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" placeholder="Tu contraseña" required>
            </div>
            
            <div class="remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Recordarme</span>
                </label>
                <a href="forgot_password.php">¿Olvidaste tu contraseña?</a>
            </div>
            
            <button type="submit" class="login-button">Ingresar al Sistema</button>
        </form>
        
        <?php
        ?>
        
        <div class="register-link">
            <p>¿No tienes cuenta de estudiante? <a href="registro.php">Regístrate aquí</a></p>
        </div>
        
        <hr>
        <p style="font-size: 12px; color: #666; text-align: center;">
            Sistema de Matrícula UTP © 2025<br>
            Universidad Tecnológica de Panamá<br>
            Todos los derechos reservados
        </p>
    </div>
</body>
</html>