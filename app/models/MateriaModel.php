<?php
namespace App\Models;

use Config\Database;

class MateriaModel {
    private $db;
    private $table = 'materias';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function crear($datos) {
        $sql = "INSERT INTO materias (nombre, descripcion, costo, docente, id_carrera) 
                VALUES (?, ?, ?, ?, ?)";
        return $this->db->insert($sql, [
            $datos['nombre'],
            $datos['descripcion'] ?? '',
            $datos['costo'] ?? 0,
            $datos['docente'],  // Nombre del docente como string
            $datos['id_carrera']
        ]);
    }
    
    public function listar($idCarrera = null) {
        $sql = "SELECT m.*, c.nombre as carrera 
                FROM materias m 
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera 
                WHERE 1=1";
        $params = [];
        
        if ($idCarrera) {
            $sql .= " AND m.id_carrera = ?";
            $params[] = $idCarrera;
        }
        
        $sql .= " ORDER BY m.nombre";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function buscarPorId($id) {
        $sql = "SELECT m.*, c.nombre as carrera 
                FROM materias m 
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera 
                WHERE m.id_materia = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function actualizar($id, $datos) {
        $sql = "UPDATE materias SET 
                nombre = ?, 
                descripcion = ?, 
                costo = ?, 
                docente = ?, 
                id_carrera = ? 
                WHERE id_materia = ?";
        
        return $this->db->query($sql, [
            $datos['nombre'],
            $datos['descripcion'] ?? '',
            $datos['costo'] ?? 0,
            $datos['docente'],  // Solo nombre, no ID
            $datos['id_carrera'],
            $id
        ]);
    }
    
    public function eliminar($id) {
        // Verificar si tiene matrículas
        $sqlCheck = "SELECT COUNT(*) as count FROM grupos_horarios_materia WHERE id_materia = ?";
        $result = $this->db->fetchOne($sqlCheck, [$id]);
        
        if ($result['count'] > 0) {
            throw new \Exception("No se puede eliminar, la materia tiene grupos asignados");
        }
        
        $sql = "DELETE FROM materias WHERE id_materia = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Buscar materias por docente (nombre)
    public function buscarPorDocente($nombreDocente) {
        $sql = "SELECT m.*, c.nombre as carrera 
                FROM materias m 
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera 
                WHERE m.docente LIKE ? 
                ORDER BY m.nombre";
        return $this->db->fetchAll($sql, ["%$nombreDocente%"]);
    }
}
?>