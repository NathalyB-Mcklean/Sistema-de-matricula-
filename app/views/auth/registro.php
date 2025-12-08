<?php
// app/views/auth/registro.php - VERSIÓN USANDO VALIDACIONES.PHP

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
        $telefono   = trim($_POST["telefono"] ?? "");
        $cedula     = trim($_POST["cedula"] ?? "");
        $rol        = "estudiante";

        // Validaciones usando funciones del archivo validaciones.php
        validarNoVacio($nombre, "nombre");
        validarNoVacio($apellido, "apellido");
        validarCorreoUTP($correo);
        validarPassword($password);
        validarCoincidenciaPassword($password, $password2);
        
        // Validar formato de cédula (opcional)
        $cedula = validarFormatoCedula($cedula);
        
        // Validar formato de teléfono (opcional)
        $telefono = validarFormatoTelefono($telefono);

        // Encriptar contraseña
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Verificar unicidad usando funciones del archivo validaciones.php
        validarUnicidadCorreo($correo, $conexion);
        
        if (!empty($cedula)) {
            validarUnicidadCedula($cedula, $conexion);
        }

        // ========== COMENZAR TRANSACCIÓN ==========
        $conexion->begin_transaction();

        try {
            // 1. Insertar usuario
            $insert_usuario = $conexion->prepare("
                INSERT INTO usuario (nombre, apellido, correo, password, rol, estado)
                VALUES (?, ?, ?, ?, ?, 'activo')
            ");
            $insert_usuario->bind_param("sssss", $nombre, $apellido, $correo, $passwordHash, $rol);
            
            if (!$insert_usuario->execute()) {
                throw new Exception("Error al registrar usuario en el sistema.");
            }
            
            $userId = $conexion->insert_id;

            // 2. Generar cédula si no se proporcionó
            if (empty($cedula)) {
                // Generar cédula temporal: 8-XXX-XXX
                $random1 = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
                $random2 = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
                $cedula = "8-{$random1}-{$random2}";
                
                // Verificar que no exista
                $check_stmt = $conexion->prepare("SELECT id_estudiante FROM estudiantes WHERE cedula = ?");
                $check_stmt->bind_param("s", $cedula);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $random1 = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
                    $random2 = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
                    $cedula = "8-{$random1}-{$random2}";
                }
            }

            // ========== IMPORTANTE: ASIGNAR CARRERA ALEATORIA ==========
            // 3. Obtener todas las carreras activas
            $query_carreras = "SELECT id_carrera FROM carreras WHERE estado = 'activa'";
            $result_carreras = $conexion->query($query_carreras);
            
            if ($result_carreras->num_rows === 0) {
                throw new Exception("No hay carreras disponibles en el sistema. Contacta con administración.");
            }
            
            $carreras = [];
            while ($row = $result_carreras->fetch_assoc()) {
                $carreras[] = $row['id_carrera'];
            }
            
            // 4. Seleccionar carrera aleatoria
            $carrera_aleatoria = $carreras[array_rand($carreras)];
            
            // 5. Insertar estudiante con carrera asignada
            $insert_estudiante = $conexion->prepare("
                INSERT INTO estudiantes (cedula, id_usuario, telefono, año_carrera, semestre_actual, fecha_ingreso, id_carrera)
                VALUES (?, ?, ?, 1, 1, NOW(), ?)
            ");
            $insert_estudiante->bind_param("sisi", $cedula, $userId, $telefono, $carrera_aleatoria);
            
            if (!$insert_estudiante->execute()) {
                throw new Exception("Error al crear perfil de estudiante.");
            }
            
            $id_estudiante = $conexion->insert_id;

            // ========== CONFIRMAR TRANSACCIÓN ==========
            $conexion->commit();

            // Obtener nombre de la carrera asignada para mostrar
            $query_nombre_carrera = $conexion->prepare("SELECT nombre FROM carreras WHERE id_carrera = ?");
            $query_nombre_carrera->bind_param("i", $carrera_aleatoria);
            $query_nombre_carrera->execute();
            $carrera_info = $query_nombre_carrera->get_result()->fetch_assoc();
            $nombre_carrera = $carrera_info['nombre'] ?? 'Carrera asignada';

            // Iniciar sesión automáticamente
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $nombre . " " . $apellido;
            $_SESSION['user_email'] = $correo;
            $_SESSION['user_role'] = $rol;
            $_SESSION['id_estudiante'] = $id_estudiante;
            $_SESSION['carrera_asignada'] = $nombre_carrera;
            $_SESSION['id_carrera'] = $carrera_aleatoria;
            
            // Mostrar mensaje de éxito con la carrera asignada
            $_SESSION['success'] = "¡Registro exitoso! Has sido asignado a la carrera: " . htmlspecialchars($nombre_carrera);
            
            // Redirigir al dashboard del estudiante
            header("Location: ../../views/estudiante/dashboard.php");
            exit();

        } catch (Exception $e) {
            // ========== REVERTIR TRANSACCIÓN EN CASO DE ERROR ==========
            $conexion->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        $mensaje = "<div class='error'>Error: " . $e->getMessage() . "</div>";
    }
}

// Obtener lista de carreras disponibles para mostrar
$query_carreras_info = "SELECT nombre, codigo FROM carreras WHERE estado = 'activa'";
$carreras_info = $conexion->query($query_carreras_info);
$carreras_lista = [];
while ($row = $carreras_info->fetch_assoc()) {
    $carreras_lista[] = $row;
}

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Estudiante - Sistema UTP</title>
    <link rel="stylesheet" href="http://localhost/Sistema-de-matricula-/app/public/assets/css/style.css">
    
    <style>
        /* Solo el CSS específico para el registro */
        .carrera-info {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border-left: 4px solid #6B2C91;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .carrera-info h4 {
            color: #6B2C91;
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .carrera-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .carrera-list li {
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.7);
            margin-bottom: 8px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .carrera-code {
            background: #6B2C91;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
    
    <script>
        // JavaScript para validaciones en tiempo real (ya tienes este)
        function validarPassword() {
            var password = document.getElementById('password').value;
            var confirmPassword = document.getElementById('password2').value;
            var strengthBar = document.getElementById('password-strength');
            var mensaje = document.getElementById('password-message');
            var requirementsList = document.getElementById('password-requirements');
            
            var requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };
            
            var cumplidos = 0;
            var totalRequisitos = 0;
            var mensajesFaltantes = [];
            
            for (var key in requirements) {
                totalRequisitos++;
                if (requirements[key]) cumplidos++;
            }
            
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
            
            strengthBar.className = 'password-strength';
            
            if (password.length === 0) {
                strengthBar.style.display = 'none';
                mensaje.textContent = '';
                requirementsList.style.display = 'none';
            } else {
                strengthBar.style.display = 'block';
                requirementsList.style.display = 'block';
                
                var mensajeHTML = '';
                if (mensajesFaltantes.length > 0) {
                    mensajeHTML = '<div style="margin-top: 5px; padding: 5px; background: #f9f9f9; border-radius: 4px; font-size: 12px;">';
                    mensajeHTML += '<strong>Requisitos faltantes:</strong><br>';
                    mensajeHTML += '• ' + mensajesFaltantes.join('<br>• ') + '<br>';
                    mensajeHTML += '</div>';
                } else {
                    mensajeHTML = '<div style="margin-top: 5px; padding: 5px; background: #e8f5e9; border-radius: 4px; font-size: 12px;">';
                    mensajeHTML += '<strong>✓ Todos los requisitos cumplidos</strong>';
                    mensajeHTML += '</div>';
                }
                
                requirementsList.innerHTML = mensajeHTML;
                
                if (cumplidos < 2) {
                    strengthBar.className += ' strength-weak';
                    mensaje.textContent = 'Contraseña débil';
                    mensaje.style.color = '#f44336';
                } else if (cumplidos < 4) {
                    strengthBar.className += ' strength-medium';
                    mensaje.textContent = 'Contraseña media';
                    mensaje.style.color = '#ff9800';
                } else {
                    strengthBar.className += ' strength-strong';
                    mensaje.textContent = 'Contraseña fuerte';
                    mensaje.style.color = '#2d8659';
                }
            }
            
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
            
            var submitBtn = document.querySelector('button[type="submit"]');
            if (password.length > 0 && confirmPassword.length > 0) {
                if (password === confirmPassword && requirements.length) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.style.cursor = 'pointer';
                } else {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.6';
                    submitBtn.style.cursor = 'not-allowed';
                }
            } else {
                submitBtn.disabled = false;
                submitBtn.style.opacity = '1';
            }
        }
        
        function togglePasswordVisibility(fieldId) {
            var field = document.getElementById(fieldId);
            var icon = document.getElementById('icon-' + fieldId);
            if (field.type === 'password') {
                field.type = 'text';
                icon.textContent = '🙈';
            } else {
                field.type = 'password';
                icon.textContent = '👁️';
            }
        }
        
        function validarCedula() {
            var cedula = document.getElementById('cedula').value;
            var mensaje = document.getElementById('cedula-message');
            
            if (cedula.length === 0) {
                mensaje.textContent = '';
                return;
            }
            
            var regex = /^[0-9]{1}-[0-9]{3}-[0-9]{3}$/;
            if (regex.test(cedula)) {
                mensaje.textContent = '✓ Formato válido (ej: 8-123-456)';
                mensaje.style.color = '#2d8659';
            } else {
                mensaje.textContent = '✗ Formato debe ser: 8-XXX-XXX';
                mensaje.style.color = '#f44336';
            }
        }
        
        function validarTelefono() {
            var telefono = document.getElementById('telefono').value;
            var mensaje = document.getElementById('telefono-message');
            
            if (telefono.length === 0) {
                mensaje.textContent = '';
                return;
            }
            
            var regex = /^[0-9]{4}-[0-9]{4}$/;
            if (regex.test(telefono)) {
                mensaje.textContent = '✓ Formato válido (ej: 6000-1234)';
                mensaje.style.color = '#2d8659';
            } else {
                mensaje.textContent = '✗ Formato: XXXX-XXXX';
                mensaje.style.color = '#f44336';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            validarPassword();
        });
    </script>
</head>
<body>
    <div class="auth-container">
        <div class="logo-container">
            <img src="http://localhost/Sistema-de-matricula-/app/public/assets/images/utp.png" alt="Logo UTP" class="logo-utp">
        </div>
        <h2>REGISTRO DE ESTUDIANTE</h2>
        
        <?php 
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
            <div class="form-row">
                <div>
                    <label>Nombre:</label>
                    <input type="text" name="nombre" placeholder="Ej: Juan" required>
                </div>
                
                <div>
                    <label>Apellido:</label>
                    <input type="text" name="apellido" placeholder="Ej: Pérez" required>
                </div>
            </div>
            
            <div class="form-row">
                <div>
                    <label>Cédula (opcional):</label>
                    <input type="text" name="cedula" id="cedula" placeholder="Ej: 8-123-456" 
                           onkeyup="validarCedula()" pattern="[0-9]{1}-[0-9]{3}-[0-9]{3}">
                    <small id="cedula-message" style="display: block; font-size: 12px; margin-top: 5px;"></small>
                </div>
                
                <div>
                    <label>Teléfono (opcional):</label>
                    <input type="text" name="telefono" id="telefono" placeholder="Ej: 6000-1234"
                           onkeyup="validarTelefono()" pattern="[0-9]{4}-[0-9]{4}">
                    <small id="telefono-message" style="display: block; font-size: 12px; margin-top: 5px;"></small>
                </div>
            </div>
            
            <div>
                <label>Correo institucional (@utp.ac.pa):</label>
                <input type="email" name="correo" placeholder="juan.perez@utp.ac.pa" required>
                <small style="color: #666; font-size: 12px;">Debe terminar en @utp.ac.pa</small>
            </div>
            
            <div class="password-row">
                <div class="password-field">
                    <label>Contraseña:</label>
                    <div class="field-with-icon">
                        <input type="password" id="password" name="password" 
                               onkeyup="validarPassword()" required>
                    </div>
                    <div id="password-strength" class="password-strength"></div>
                </div>

                <div class="password-field">
                    <label>Repetir contraseña:</label>
                    <div class="field-with-icon">
                        <input type="password" id="password2" name="password2" 
                               onkeyup="validarPassword()" required>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                <small id="password-message" style="display: block; font-size: 12px;"></small>
                <small id="confirm-message" style="display: block; font-size: 12px;"></small>
            </div>

            <div id="password-requirements" style="display: none; margin-bottom: 15px;"></div>
        
            
            <button type="submit">Registrarme como Estudiante</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px;">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </p>
        
        <hr>
        <p style="font-size: 12px; color: #666; text-align: center;">
            Sistema de Matrícula UTP © 2025<br>
            Universidad Tecnológica de Panamá
        </p>
    </div>
</body>
</html>