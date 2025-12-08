<?php
// obtener_grupos_disponibles.php - VERSIÓN CORREGIDA

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
    
    // Consulta para obtener grupos disponibles
    // NOTA: Tu tabla grupos_horarios_materia NO tiene campo periodo_academico
    // Necesitamos considerar que los grupos pueden usarse en múltiples períodos
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
            TIME_FORMAT(h.hora_fin, '%H:%i') as hora_fin,
            '{$codigo_periodo}' as periodo_academico,
            30 as cupo_maximo,  -- Valor por defecto, ajusta según tu BD
            15 as cupo_actual   -- Valor por defecto, ajusta según tu BD
        FROM grupos_horarios_materia ghm
        JOIN materias m ON ghm.id_materia = m.id_materia
        LEFT JOIN docentes d ON m.id_docente = d.id_docente
        JOIN horarios h ON ghm.id_horario = h.id_horario
        WHERE ghm.id_ghm NOT IN (
            -- Excluir grupos en los que el estudiante ya está matriculado en cualquier período
            SELECT mt.id_ghm 
            FROM matriculas mt
            WHERE mt.id_estudiante = ?
            AND EXISTS (
                -- Verificar que la materia sea la misma
                SELECT 1 FROM grupos_horarios_materia ghm2
                WHERE ghm2.id_ghm = mt.id_ghm
                AND ghm2.id_materia = ghm.id_materia
            )
        )
        AND m.id_carrera = (
            -- Solo materias de la carrera del estudiante
            SELECT e.id_carrera 
            FROM estudiantes e 
            WHERE e.id_estudiante = ?
        )
        ORDER BY m.codigo, h.dia, h.hora_inicio
    ";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $estudianteId, $estudianteId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $grupos = [];
    while($row = $result->fetch_assoc()) {
        $grupos[] = $row;
    }
    
    echo json_encode($grupos, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>