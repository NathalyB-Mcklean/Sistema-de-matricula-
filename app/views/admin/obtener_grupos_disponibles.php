<?php
// obtener_grupos_disponibles.php - VERSIÓN MEJORADA

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
        throw new Exception("Parámetros inválidos: estudiante=$estudianteId, periodo=$periodoId");
    }
    
    // OBTENER EL CÓDIGO DEL PERÍODO (ej: "2025-2")
    $stmt_periodo = $conexion->prepare("SELECT CONCAT(año, '-', semestre) as codigo_periodo 
                                       FROM periodos_academicos WHERE id_periodo = ?");
    $stmt_periodo->bind_param("i", $periodoId);
    $stmt_periodo->execute();
    $periodo_result = $stmt_periodo->get_result()->fetch_assoc();
    $codigo_periodo = $periodo_result['codigo_periodo'] ?? '';
    
    if (empty($codigo_periodo)) {
        throw new Exception("Período no válido");
    }
    
    // CONSULTA SIMPLIFICADA - Obtener todos los grupos
    $sql = "
        SELECT 
            ghm.id_ghm,
            ghm.id_materia,
            m.codigo as materia_codigo,
            m.nombre as materia_nombre,
            m.costo,
            CONCAT(d.nombre, ' ', d.apellido) as docente_nombre,
            ghm.aula,
            h.dia,
            TIME_FORMAT(h.hora_inicio, '%H:%i') as hora_inicio,
            TIME_FORMAT(h.hora_fin, '%H:%i') as hora_fin
        FROM grupos_horarios_materia ghm
        JOIN materias m ON ghm.id_materia = m.id_materia
        LEFT JOIN docentes d ON m.id_docente = d.id_docente
        JOIN horarios h ON ghm.id_horario = h.id_horario
        WHERE ghm.id_ghm NOT IN (
            SELECT mt.id_ghm 
            FROM matriculas mt
            WHERE mt.id_estudiante = ?
            AND mt.id_periodo = ?
        )
        ORDER BY m.codigo, h.dia, h.hora_inicio
    ";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("is", $estudianteId, $codigo_periodo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $grupos = [];
    while($row = $result->fetch_assoc()) {
        $grupos[] = $row;
    }
    
    echo json_encode($grupos, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => [
            'estudiante' => $estudianteId ?? '',
            'periodo' => $periodoId ?? ''
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>