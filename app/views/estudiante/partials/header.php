<?php
// app/views/estudiante/partials/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'estudiante') {
    header('Location: ../../auth/login.php');
    exit();
}

// Obtener la ruta base del proyecto
$base_dir = dirname(dirname(dirname(dirname(__FILE__)))); // Raíz del proyecto

if (!isset($conexion)) {
    require_once $base_dir . '/config/conexion.php';
}

if (!isset($estudiante_info) && isset($_SESSION['id_estudiante'])) {
    $id_estudiante = $_SESSION['id_estudiante'];
    $query = "SELECT e.*, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo,
                     c.nombre as carrera_nombre
              FROM estudiantes e 
              JOIN usuario u ON e.id_usuario = u.id_usuario 
              LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
              WHERE e.id_estudiante = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('i', $id_estudiante);
    $stmt->execute();
    $estudiante_info = $stmt->get_result()->fetch_assoc();
}

if (!isset($periodo)) {
    $query_periodo = "SELECT * FROM periodos_academicos WHERE estado = 'activo' LIMIT 1";
    $periodo_result = $conexion->query($query_periodo);
    $periodo = $periodo_result ? $periodo_result->fetch_assoc() : null;
}

if (!isset($pagina_activa)) {
    $pagina_activa = basename($_SERVER['PHP_SELF'], '.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina ?? 'Sistema UTP - Estudiante'; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/estudiante.css">
    <?php if (isset($css_adicional)): ?>
    <style><?php echo $css_adicional; ?></style>
    <?php endif; ?>
</head>
<body>
<div class="dashboard-container">