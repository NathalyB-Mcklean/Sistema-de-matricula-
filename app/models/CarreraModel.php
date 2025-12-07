<?php
// app/models/CarreraModel.php

class CarreraModel {
    private $conn;
    
    public function __construct() {
        require_once '../../config/conexion.php';
        $this->conn = Conexion::getConexion();
    }
    
    // Obtener todas las carreras con estadísticas
    public function getAll() {
        $sql = "SELECT c.*, 
                COUNT(DISTINCT m.id_materia) as total_materias,
                COUNT(DISTINCT e.id_estudiante) as total_estudiantes
                FROM carreras c
                LEFT JOIN materias m ON c.id_carrera = m.id_carrera
                LEFT JOIN estudiantes e ON c.id_carrera = e.id_carrera
                GROUP BY c.id_carrera
                ORDER BY c.estado DESC, c.nombre";
        
        return $this->conn->query($sql);
    }
    
    // Crear carrera
    public function create($data) {
        $sql = "INSERT INTO carreras (codigo, nombre, descripcion, duracion_semestres, creditos_totales, estado) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssiis", 
            $data['codigo'], 
            $data['nombre'], 
            $data['descripcion'], 
            $data['duracion_semestres'], 
            $data['creditos_totales'], 
            $data['estado']
        );
        
        return $stmt->execute();
    }
    
    // Eliminar carrera
    public function delete($id) {
        $sql = "DELETE FROM carreras WHERE id_carrera = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // Verificar si tiene materias
    public function hasMaterias($id_carrera) {
        $sql = "SELECT COUNT(*) as total FROM materias WHERE id_carrera = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_carrera);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] > 0;
    }
    
    // Verificar si tiene estudiantes
    public function hasEstudiantes($id_carrera) {
        $sql = "SELECT COUNT(*) as total FROM estudiantes WHERE id_carrera = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_carrera);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] > 0;
    }
}