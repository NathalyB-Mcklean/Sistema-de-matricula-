<?php
// app/views/admin/procesar_matricula.php

session_start();
require_once '../../config/conexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validar datos
        $estudiante = $_POST['estudiante'] ?? null;
        $periodo = $_POST['periodo'] ?? null;
        $grupo = $_POST['grupo'] ?? null;
        
        if (!$estudiante || !$periodo || !$grupo) {
            throw new Exception("Todos los campos son obligatorios");
        }
        
        // Obtener código del período (año-semestre)
        $stmt_periodo = $conexion->prepare("
            SELECT CONCAT(año, '-', semestre) as codigo_periodo 
            FROM periodos_academicos 
            WHERE id_periodo = ?
        ");
        $stmt_periodo->bind_param("i", $periodo);
        $stmt_periodo->execute();
        $periodo_result = $stmt_periodo->get_result()->fetch_assoc();
        
        if (!$periodo_result) {
            throw new Exception("Período no válido");
        }
        
        $codigo_periodo = $periodo_result['codigo_periodo'];
        
        // Verificar si ya está matriculado en este grupo
        $stmt_check = $conexion->prepare("
            SELECT COUNT(*) as total 
            FROM matriculas 
            WHERE id_estudiante = ? AND id_ghm = ?
        ");
        $stmt_check->bind_param("ii", $estudiante, $grupo);
        $stmt_check->execute();
        $result = $stmt_check->get_result()->fetch_assoc();
        
        if ($result['total'] > 0) {
            throw new Exception("El estudiante ya está matriculado en este grupo");
        }
        
        // Insertar la matrícula
        $stmt = $conexion->prepare("
            INSERT INTO matriculas (id_estudiante, id_ghm, id_periodo, fecha) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iis", $estudiante, $grupo, $codigo_periodo);
        $stmt->execute();
        
        // Auditoría
        $audit = $conexion->prepare("INSERT INTO auditoria (usuario, accion) VALUES (?, ?)");
        $accion_audit = "Creó matrícula - Estudiante: $estudiante, Grupo: $grupo, Período: $codigo_periodo";
        $audit->bind_param("ss", $_SESSION['user_name'], $accion_audit);
        $audit->execute();
        
        header('Location: matriculas.php?mensaje=' . urlencode('Matrícula creada exitosamente'));
        exit();
        
    } catch (Exception $e) {
        header('Location: matriculas.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} else {
    header('Location: matriculas.php');
    exit();
}
?>