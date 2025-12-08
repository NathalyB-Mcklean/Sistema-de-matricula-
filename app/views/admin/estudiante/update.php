<?php
// app/views/admin/estudiantes/update.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_estudiante = $_POST['id_estudiante'] ?? null;
    
    if (!$id_estudiante) {
        $_SESSION['error'] = 'ID de estudiante no válido';
        header('Location: index.php');
        exit();
    }
    
    // Obtener datos actuales del estudiante
    $query = "SELECT e.id_usuario, u.correo as correo_actual, e.cedula as cedula_actual 
              FROM estudiantes e 
              JOIN usuario u ON e.id_usuario = u.id_usuario 
              WHERE e.id_estudiante = ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $id_estudiante);
    $stmt->execute();
    $estudiante_actual = $stmt->get_result()->fetch_assoc();
    
    // Validar que el estudiante existe
    if (!$estudiante_actual) {
        $_SESSION['error'] = 'Estudiante no encontrado';
        header('Location: index.php');
        exit();
    }
    
    // Validaciones
    $errores = [];
    
    // Validar cédula única (si cambió)
    if ($_POST['cedula'] != $estudiante_actual['cedula_actual']) {
        $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM estudiantes WHERE cedula = ? AND id_estudiante != ?");
        $stmt->bind_param("si", $_POST['cedula'], $id_estudiante);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result['total'] > 0) {
            $errores[] = "La cédula ya está registrada por otro estudiante";
        }
    }
    
    // Validar correo único (si cambió)
    if ($_POST['correo'] != $estudiante_actual['correo_actual']) {
        $stmt = $conexion->prepare("SELECT COUNT(*) as total FROM usuario WHERE correo = ? AND id_usuario != ?");
        $stmt->bind_param("si", $_POST['correo'], $estudiante_actual['id_usuario']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result['total'] > 0) {
            $errores[] = "El correo ya está registrado por otro usuario";
        }
    }
    
    // Validar formato de cédula
    if (!preg_match('/^[0-9]{10,13}$/', $_POST['cedula'])) {
        $errores[] = "La cédula debe tener entre 10 y 13 dígitos";
    }
    
    // Validar formato de correo
    if (!filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    }
    
    // Validar contraseña si se proporciona
    if (!empty($_POST['password']) && strlen($_POST['password']) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    
    // Si hay errores, redirigir al formulario
    if (!empty($errores)) {
        $_SESSION['error'] = implode(', ', $errores);
        header('Location: edit.php?id=' . $id_estudiante);
        exit();
    }
    
    // Iniciar transacción
    $conexion->begin_transaction();
    
    try {
        // Actualizar usuario
        if (!empty($_POST['password'])) {
            // Si hay nueva contraseña
            $sql_usuario = "UPDATE usuario SET 
                           nombre = ?, apellido = ?, correo = ?, estado = ?,
                           password = ?
                           WHERE id_usuario = ?";
            
            $stmt_usuario = $conexion->prepare($sql_usuario);
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt_usuario->bind_param("sssssi",
                $_POST['nombre'],
                $_POST['apellido'],
                $_POST['correo'],
                $_POST['estado'],
                $hashed_password,
                $estudiante_actual['id_usuario']
            );
        } else {
            // Sin cambiar contraseña
            $sql_usuario = "UPDATE usuario SET 
                           nombre = ?, apellido = ?, correo = ?, estado = ?
                           WHERE id_usuario = ?";
            
            $stmt_usuario = $conexion->prepare($sql_usuario);
            $stmt_usuario->bind_param("ssssi",
                $_POST['nombre'],
                $_POST['apellido'],
                $_POST['correo'],
                $_POST['estado'],
                $estudiante_actual['id_usuario']
            );
        }
        
        if (!$stmt_usuario->execute()) {
            throw new Exception("Error al actualizar usuario: " . $stmt_usuario->error);
        }
        
        // Actualizar estudiante
        $sql_estudiante = "UPDATE estudiantes SET 
                          cedula = ?, telefono = ?, fecha_nacimiento = ?, direccion = ?,
                          id_carrera = ?, año_carrera = ?, semestre_actual = ?
                          WHERE id_estudiante = ?";
        
        $stmt_estudiante = $conexion->prepare($sql_estudiante);
        
        // Preparar valores NULL si están vacíos
        $telefono = !empty($_POST['telefono']) ? $_POST['telefono'] : null;
        $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
        $direccion = !empty($_POST['direccion']) ? $_POST['direccion'] : null;
        $id_carrera = !empty($_POST['id_carrera']) ? $_POST['id_carrera'] : null;
        $año_carrera = intval($_POST['año_carrera']);
        $semestre_actual = intval($_POST['semestre_actual']);
        
        // Para valores NULL en bind_param, usar "s" para string y luego asignar null
        $stmt_estudiante->bind_param("ssssiiii",
            $_POST['cedula'],
            $telefono,
            $fecha_nacimiento,
            $direccion,
            $id_carrera,
            $año_carrera,
            $semestre_actual,
            $id_estudiante
        );
        
        if (!$stmt_estudiante->execute()) {
            throw new Exception("Error al actualizar estudiante: " . $stmt_estudiante->error);
        }
        
        // Registrar en auditoría
        $audit_stmt = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
        $accion_audit = "Actualizó estudiante ID: " . $id_estudiante . " - " . $_POST['nombre'] . " " . $_POST['apellido'];
        $audit_stmt->bind_param("ss", $_SESSION['user_name'], $accion_audit);
        $audit_stmt->execute();
        
        // Confirmar transacción
        $conexion->commit();
        
        $_SESSION['success'] = "✅ Estudiante actualizado exitosamente";
        header('Location: index.php');
        exit();
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conexion->rollback();
        $_SESSION['error'] = "❌ Error: " . $e->getMessage();
        header('Location: edit.php?id=' . $id_estudiante);
        exit();
    }
    
} else {
    // Si no es POST, redirigir al listado
    header('Location: index.php');
    exit();
}

$conexion->close();
?>