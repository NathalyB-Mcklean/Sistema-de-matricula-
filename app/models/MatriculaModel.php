<?php
// app/models/MatriculaModel.php

class MatriculaModel {
    private $conn;
    
    public function __construct() {
        require_once '../../config/conexion.php';
        $this->conn = Conexion::getConexion();
    }
    
    // Obtener todas las matrículas con filtros
    public function getAll($filtros = []) {
        $sql = "SELECT 
                mt.id_matricula,
                mt.fecha,
                e.id_estudiante,
                CONCAT(u.nombre, ' ', u.apellido) as estudiante_nombre,
                e.cedula,
                c.id_carrera,
                c.nombre as carrera_nombre,
                c.codigo as carrera_codigo,
                m.id_materia,
                m.nombre as materia_nombre,
                m.codigo as materia_codigo,
                m.costo,
                CONCAT(d.nombre, ' ', d.apellido) as docente_nombre,
                ghm.id_ghm,
                ghm.aula,
                h.dia,
                TIME_FORMAT(h.hora_inicio, '%H:%i') as hora_inicio,
                TIME_FORMAT(h.hora_fin, '%H:%i') as hora_fin,
                p.id_periodo,
                p.nombre as periodo_nombre,
                p.año as periodo_año,
                p.semestre as periodo_semestre
                FROM matriculas mt
                JOIN estudiantes e ON mt.id_estudiante = e.id_estudiante
                JOIN usuario u ON e.id_usuario = u.id_usuario
                JOIN grupos_horarios_materia ghm ON mt.id_ghm = ghm.id_ghm
                JOIN materias m ON ghm.id_materia = m.id_materia
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                LEFT JOIN docentes d ON m.id_docente = d.id_docente
                LEFT JOIN horarios h ON ghm.id_horario = h.id_horario
                LEFT JOIN periodos_academicos p ON mt.id_periodo = p.id_periodo
                WHERE 1=1";
        
        // Aplicar filtros dinámicamente
        $params = [];
        $types = '';
        
        if (isset($filtros['id_estudiante']) && $filtros['id_estudiante']) {
            $sql .= " AND e.id_estudiante = ?";
            $params[] = $filtros['id_estudiante'];
            $types .= 'i';
        }
        
        if (isset($filtros['id_periodo']) && $filtros['id_periodo']) {
            $sql .= " AND p.id_periodo = ?";
            $params[] = $filtros['id_periodo'];
            $types .= 'i';
        }
        
        if (isset($filtros['id_carrera']) && $filtros['id_carrera']) {
            $sql .= " AND c.id_carrera = ?";
            $params[] = $filtros['id_carrera'];
            $types .= 'i';
        }
        
        $sql .= " ORDER BY mt.fecha DESC, h.dia, h.hora_inicio";
        
        if ($params) {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result();
        } else {
            return $this->conn->query($sql);
        }
    }
    
    // Crear una nueva matrícula
    public function create($data) {
        $sql = "INSERT INTO matriculas (id_estudiante, id_periodo, id_ghm, fecha, estado) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiiss", 
            $data['id_estudiante'], 
            $data['id_periodo'], 
            $data['id_grupo'], 
            $data['fecha'], 
            $data['estado']
        );
        
        return $stmt->execute();
    }
    
    // Eliminar una matrícula
    public function delete($id) {
        $sql = "DELETE FROM matriculas WHERE id_matricula = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // Verificar si ya está matriculado
    public function yaMatriculado($id_estudiante, $id_grupo) {
        $sql = "SELECT COUNT(*) as total FROM matriculas 
                WHERE id_estudiante = ? AND id_ghm = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $id_estudiante, $id_grupo);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] > 0;
    }
    
    // Obtener matrícula por ID
    public function getById($id) {
        $sql = "SELECT * FROM matriculas WHERE id_matricula = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}