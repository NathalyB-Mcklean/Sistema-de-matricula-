<?php
// app/views/auth/registro.php - VERSIÓN CORREGIDA

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

// Iniciar sesión
session_start();

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
            INSERT INTO usuario (nombre, apellido, correo, password, rol, estado)
            VALUES (?, ?, ?, ?, ?, 'activo')
        ");

        $insert->bind_param("sssss", $nombre, $apellido, $correo, $passwordHash, $rol);

        if ($insert->execute()) {
            // Iniciar sesión automáticamente
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
        $mensaje = "<div class='error'>Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - Sistema de Matrícula</title>
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/style.css">
   

    <script>
        // Validación de contraseña en tiempo real con mensajes detallados
        function validarPassword() {
            var password = document.getElementById('password').value;
            var confirmPassword = document.getElementById('password2').value;
            var strengthBar = document.getElementById('password-strength');
            var mensaje = document.getElementById('password-message');
            var requirementsList = document.getElementById('password-requirements');
            
            // Verificar cada requisito individualmente
            var requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };
            
            // Contar requisitos cumplidos
            var cumplidos = 0;
            var totalRequisitos = 0;
            var mensajesFaltantes = [];
            
            for (var key in requirements) {
                totalRequisitos++;
                if (requirements[key]) cumplidos++;
            }
            
            // Crear mensajes específicos de lo que falta
            if (!requirements.length) {
                mensajesFaltantes.push('al menos 8 caracteres');
            }
            if (!requirements.uppercase) {
                mensajesFaltantes.push('una letra mayúscula');
            }
            if (!requirements.lowercase) {
                mensajesFaltantes.push('una letra minúscula');
            }
            if (!requirements.number) {
                mensajesFaltantes.push('un número');
            }
            if (!requirements.special) {
                mensajesFaltantes.push('un carácter especial (!@#$%^&*)');
            }
            
            // Actualizar barra de fortaleza
            strengthBar.className = 'password-strength';
            
            if (password.length === 0) {
                strengthBar.style.display = 'none';
                mensaje.textContent = '';
                requirementsList.style.display = 'none';
            } else {
                strengthBar.style.display = 'block';
                requirementsList.style.display = 'block';
                
                // Mostrar qué requisitos faltan
                var mensajeHTML = '';
                if (mensajesFaltantes.length > 0) {
                    mensajeHTML = '<div style="margin-top: 5px; padding: 5px; background: #f9f9f9; border-radius: 4px; font-size: 12px;">';
                    mensajeHTML += '<strong>Falta:</strong><br>';
                    mensajeHTML += '• ' + mensajesFaltantes.join('<br>• ') + '<br>';
                    mensajeHTML += '<em>Recomendación: Usa una combinación de todos los tipos</em>';
                    mensajeHTML += '</div>';
                }
                
                requirementsList.innerHTML = mensajeHTML;
                
                // Mostrar nivel de fortaleza
                if (cumplidos < 2) {
                    strengthBar.className += ' strength-weak';
                    mensaje.textContent = 'Contraseña débil (' + cumplidos + '/' + totalRequisitos + ' requisitos)';
                    mensaje.style.color = '#f44336';
                } else if (cumplidos < 4) {
                    strengthBar.className += ' strength-medium';
                    mensaje.textContent = 'Contraseña media (' + cumplidos + '/' + totalRequisitos + ' requisitos)';
                    mensaje.style.color = '#ff9800';
                } else {
                    strengthBar.className += ' strength-strong';
                    mensaje.textContent = 'Contraseña fuerte (' + cumplidos + '/' + totalRequisitos + ' requisitos)';
                    mensaje.style.color = '#2d8659';
                    
                    // Si todos los requisitos están cumplidos, mostrar mensaje positivo
                    if (mensajesFaltantes.length === 0) {
                        requirementsList.innerHTML = '<div style="margin-top: 5px; padding: 5px; background: #e8f5e9; border-radius: 4px; font-size: 12px; color: #1b5e20;">';
                        requirementsList.innerHTML += '✓ ¡Tu contraseña cumple todos los requisitos de seguridad!';
                        requirementsList.innerHTML += '</div>';
                    }
                }
            }
            
            // Verificar coincidencia
            var confirmMsg = document.getElementById('confirm-message');
            if (confirmPassword.length === 0) {
                confirmMsg.textContent = '';
            } else if (password !== confirmPassword) {
                confirmMsg.textContent = '✗ Las contraseñas no coinciden';
                confirmMsg.style.color = '#f44336';
            } else {
                confirmMsg.textContent = '✓ Las contraseñas coinciden';
                confirmMsg.style.color = '#2d8659';
            }
            
            // Verificar que la contraseña cumpla todos los requisitos básicos
            var submitBtn = document.querySelector('button[type="submit"]');
            if (password.length > 0 && confirmPassword.length > 0) {
                if (password === confirmPassword && requirements.length) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                } else {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.6';
                }
            } else {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
        }
        
        // Mostrar/ocultar contraseña
        function togglePasswordVisibility(fieldId) {
            var field = document.getElementById(fieldId);
            if (field.type === 'password') {
                field.type = 'text';
            } else {
                field.type = 'password';
            }
        }
    </script>
</head>
<body>
    <div class="auth-container">
        <h2>REGISTRO DE USUARIO</h2>
        
        <?php 
        // Mostrar mensajes de error/sesión si existen
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
            <label>Nombre:</label>
            <input type="text" name="nombre" placeholder="Ej: Mariah" required>
            
            <label>Apellido:</label>
            <input type="text" name="apellido" placeholder="Ej: Carey" required>
            
            <label>Correo institucional (@utp.ac.pa):</label>
            <input type="email" name="correo" placeholder="mariah.carey@utp.ac.pa" required>
            
            <label>Contraseña (mínimo 8 caracteres):</label>
            <div style="position: relative;">
                <input type="password" id="password" name="password" onkeyup="validarPassword()" required>
                <span onclick="togglePasswordVisibility('password')" style="position: absolute; right: 10px; top: 10px; cursor: pointer; color: #666; font-size: 12px;">
                </span>
            </div>
            <div id="password-strength" class="password-strength" style="display: none; margin-bottom: 5px;"></div>
            <small id="password-message" style="display: block; margin-bottom: 10px;"></small>
            <div id="password-requirements" style="display: none; margin-bottom: 15px;"></div>

            <label>Repetir contraseña:</label>
            <div style="position: relative;">
                <input type="password" id="password2" name="password2" onkeyup="validarPassword()" required>
                <span onclick="togglePasswordVisibility('password2')" style="position: absolute; right: 10px; top: 10px; cursor: pointer; color: #666; font-size: 12px;">
                </span>
            </div>
            <small id="confirm-message" style="display: block; margin-bottom: 15px;"></small>
                        
            <label>Repetir contraseña:</label>
            <input type="password" id="password2" name="password2" onkeyup="validarPassword()" required>
            <small id="confirm-message" style="display: block; margin-top: -10px; margin-bottom: 15px;"></small>
            
            <button type="submit">Registrarse</button>
        </form>
        
        <p>
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </p>
        
        <hr>
        <p style="font-size: 12px; color: #666; text-align: center;">
            Sistema de Matrícula UTP © 2025<br>
            Todos los derechos reservados
        </p>
    </div>
</body>
</html>