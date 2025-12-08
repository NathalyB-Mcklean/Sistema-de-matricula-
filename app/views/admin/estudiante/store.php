<?php
// app/views/admin/estudiantes/store.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar campos requeridos
    $required = ['nombre', 'apellido', 'cedula', 'correo', 'password'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $_SESSION['error'] = "El campo " . ucfirst($field) . " es requerido";
            header('Location: create.php');
            exit();
        }
    }
    
    // Validar cédula única
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM estudiantes WHERE cedula = ?");
    $stmt->bind_param("s", $_POST['cedula']);
    $stmt->execute();
    if ($stmt->get_result()->fetch_row()[0] > 0) {
        $_SESSION['error'] = "La cédula ya está registrada";
        header('Location: create.php');
        exit();
    }
    
    // Validar correo único
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM usuario WHERE correo = ?");
    $stmt->bind_param("s", $_POST['correo']);
    $stmt->execute();
    if ($stmt->get_result()->fetch_row()[0] > 0) {
        $_SESSION['error'] = "El correo ya está registrado";
        header('Location: create.php');
        exit();
    }
    
    $conexion->begin_transaction();
    
    try {
        // 1. Crear usuario
        $sql_usuario = "INSERT INTO usuario (nombre, apellido, correo, password, rol, estado) 
                       VALUES (?, ?, ?, ?, 'estudiante', 'activo')";
        $stmt_usuario = $conexion->prepare($sql_usuario);
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt_usuario->bind_param("ssss", 
            $_POST['nombre'],
            $_POST['apellido'],
            $_POST['correo'],
            $hashed_password
        );
        $stmt_usuario->execute();
        $id_usuario = $conexion->insert_id;
        
        // 2. Crear estudiante
        $sql_estudiante = "INSERT INTO estudiantes 
                          (id_usuario, cedula, telefono, fecha_nacimiento, direccion, 
                           id_carrera, año_carrera, semestre_actual, fecha_ingreso) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
        
        $stmt_estudiante = $conexion->prepare($sql_estudiante);
        $id_carrera = !empty($_POST['id_carrera']) ? $_POST['id_carrera'] : null;
        $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
        
        $stmt_estudiante->bind_param("issssiii",
            $id_usuario,
            $_POST['cedula'],
            $_POST['telefono'],
            $fecha_nacimiento,
            $_POST['direccion'],
            $id_carrera,
            $_POST['año_carrera'],
            $_POST['semestre_actual']
        );
        $stmt_estudiante->execute();
        
        // 3. Auditoría
        $audit_stmt = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
        $accion_audit = "Creó estudiante: " . $_POST['nombre'] . " " . $_POST['apellido'];
        $audit_stmt->bind_param("ss", $_SESSION['user_name'], $accion_audit);
        $audit_stmt->execute();
        
        $conexion->commit();
        $_SESSION['success'] = "Estudiante creado exitosamente";
        header('Location: index.php');
        
    } catch (Exception $e) {
        $conexion->rollback();
        $_SESSION['error'] = "Error al crear estudiante: " . $e->getMessage();
        header('Location: create.php');
    }
    
    exit();
} else {
    header('Location: create.php');
    exit();
}
?>