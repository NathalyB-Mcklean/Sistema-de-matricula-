<?php
// obtener_grupos_disponibles.php

session_start();
require_once '../../config/conexion.php';

// DEBUG: Quitar estas líneas en producción
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    // Validar acceso
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("No autorizado");
    }
    
    // Obtener parámetros
    $estudianteId = isset($_GET['estudiante']) ? intval($_GET['estudiante']) : 0;
    $periodoId = isset($_GET['periodo']) ? trim($_GET['periodo']) : '';
    
    // Validar
    if ($estudianteId <= 0 || empty($periodoId)) {
        throw new Exception("Parámetros inválidos");
    }
    
    // Consulta para obtener grupos disponibles
    $sql = "
        SELECT 
            g.id_ghm,
            g.id_materia,
            m.codigo as materia_codigo,
            m.nombre as materia_nombre,
            g.costo,
            CONCAT(d.nombre, ' ', d.apellido) as docente_nombre,
            g.aula,
            g.dia,
            g.hora_inicio,
            g.hora_fin,
            g.cupo_maximo,
            g.cupo_actual,
            (g.cupo_maximo - g.cupo_actual) as cupos_disponibles
        FROM grupos g
        JOIN materias m ON g.id_materia = m.id_materia
        JOIN docentes d ON g.id_docente = d.id_docente
        WHERE g.periodo_academico = ?
        AND g.estado = 'activo'
        AND (g.cupo_maximo - g.cupo_actual) > 0
        ORDER BY m.codigo, g.dia, g.hora_inicio
    ";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $periodoId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $grupos = [];
    while($row = $result->fetch_assoc()) {
        $grupos[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'grupos' => $grupos,
        'count' => count($grupos)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>