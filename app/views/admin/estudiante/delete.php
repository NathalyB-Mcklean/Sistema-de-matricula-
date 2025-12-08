<?php
// app/views/admin/estudiantes/delete.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../../config/conexion.php';

$id_estudiante = $_GET['id'] ?? null;

if (!$id_estudiante) {
    $_SESSION['error'] = 'ID de estudiante no válido';
    header('Location: index.php');
    exit();
}

// Obtener información del estudiante
$query = "SELECT e.*, u.nombre, u.apellido 
          FROM estudiantes e 
          JOIN usuario u ON e.id_usuario = u.id_usuario 
          WHERE e.id_estudiante = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $id_estudiante);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();

if (!$estudiante) {
    $_SESSION['error'] = 'Estudiante no encontrado';
    header('Location: index.php');
    exit();
}

// Verificar si tiene matrículas
$query_matriculas = "SELECT COUNT(*) as total FROM matriculas WHERE id_estudiante = ?";
$stmt_matriculas = $conexion->prepare($query_matriculas);
$stmt_matriculas->bind_param("i", $id_estudiante);
$stmt_matriculas->execute();
$result_matriculas = $stmt_matriculas->get_result()->fetch_assoc();

$conexion->begin_transaction();

try {
    if ($result_matriculas['total'] > 0) {
        // Solo marcar como inactivo
        $sql_update = "UPDATE usuario u 
                       JOIN estudiantes e ON u.id_usuario = e.id_usuario 
                       SET u.estado = 'inactivo' 
                       WHERE e.id_estudiante = ?";
        $stmt_update = $conexion->prepare($sql_update);
        $stmt_update->bind_param("i", $id_estudiante);
        $stmt_update->execute();
        
        $_SESSION['success'] = "Estudiante marcado como inactivo (tiene matrículas)";
    } else {
        // Eliminar completamente
        $sql_delete_estudiante = "DELETE FROM estudiantes WHERE id_estudiante = ?";
        $stmt_delete_estudiante = $conexion->prepare($sql_delete_estudiante);
        $stmt_delete_estudiante->bind_param("i", $id_estudiante);
        $stmt_delete_estudiante->execute();
        
        $sql_delete_usuario = "DELETE FROM usuario WHERE id_usuario = ?";
        $stmt_delete_usuario = $conexion->prepare($sql_delete_usuario);
        $stmt_delete_usuario->bind_param("i", $estudiante['id_usuario']);
        $stmt_delete_usuario->execute();
        
        $_SESSION['success'] = "Estudiante eliminado exitosamente";
    }
    
    // Auditoría
    $audit_stmt = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
    $accion_audit = "Eliminó estudiante ID: $id_estudiante - " . $estudiante['nombre'] . " " . $estudiante['apellido'];
    $audit_stmt->bind_param("ss", $_SESSION['user_name'], $accion_audit);
    $audit_stmt->execute();
    
    $conexion->commit();
    
} catch (Exception $e) {
    $conexion->rollback();
    $_SESSION['error'] = "Error al eliminar: " . $e->getMessage();
}

header('Location: index.php');
exit();