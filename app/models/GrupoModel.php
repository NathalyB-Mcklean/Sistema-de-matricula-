<?php
// app/models/GrupoModel.php

class GrupoModel {
    private $conn;
    
    public function __construct() {
        require_once '../../config/conexion.php';
        $this->conn = Conexion::getConexion();
    }
    
    // Verificar si hay cupo en un grupo
    public function tieneCupo($id_grupo) {
        // Asumiendo que la tabla grupos_horarios_materia tiene campos cupo_maximo e inscritos
        $sql = "SELECT cupo_maximo, inscritos FROM grupos_horarios_materia WHERE id_ghm = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_grupo);
        $stmt->execute();
        $grupo = $stmt->get_result()->fetch_assoc();
        
        if ($grupo) {
            return $grupo['inscritos'] < $grupo['cupo_maximo'];
        }
        return false;
    }
    
    // Incrementar inscritos
    public function incrementarInscritos($id_grupo) {
        $sql = "UPDATE grupos_horarios_materia SET inscritos = inscritos + 1 WHERE id_ghm = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_grupo);
        return $stmt->execute();
    }
    
    // Decrementar inscritos
    public function decrementarInscritos($id_grupo) {
        $sql = "UPDATE grupos_horarios_materia SET inscritos = inscritos - 1 WHERE id_ghm = ? AND inscritos > 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_grupo);
        return $stmt->execute();
    }
    
    // Obtener grupos disponibles para un estudiante en un período
    public function getGruposDisponibles($id_estudiante, $id_periodo) {
        // Esta consulta debe ser adaptada según tu esquema de base de datos
        $sql = "SELECT ghm.*, m.nombre as materia_nombre, m.codigo as materia_codigo, m.costo,
                       CONCAT(d.nombre, ' ', d.apellido) as docente_nombre,
                       h.dia, TIME_FORMAT(h.hora_inicio, '%H:%i') as hora_inicio, 
                       TIME_FORMAT(h.hora_fin, '%H:%i') as hora_fin
                FROM grupos_horarios_materia ghm
                JOIN materias m ON ghm.id_materia = m.id_materia
                LEFT JOIN docentes d ON m.id_docente = d.id_docente
                LEFT JOIN horarios h ON ghm.id_horario = h.id_horario
                WHERE ghm.inscritos < ghm.cupo_maximo
                AND ghm.id_periodo = ?
                AND m.id_carrera = (SELECT id_carrera FROM estudiantes WHERE id_estudiante = ?)
                AND ghm.id_ghm NOT IN (
                    SELECT id_ghm FROM matriculas 
                    WHERE id_estudiante = ? AND id_periodo = ?
                )";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiii", $id_periodo, $id_estudiante, $id_estudiante, $id_periodo);
        $stmt->execute();
        return $stmt->get_result();
    }
}