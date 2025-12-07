<?php
// app/models/EstudianteModel.php

class EstudianteModel {
    private $conn;
    
    public function __construct() {
        require_once '../../config/conexion.php';
        $this->conn = Conexion::getConexion();
    }
    
    // Obtener todos los estudiantes con información de usuario
    public function getAll() {
        $sql = "SELECT e.*, u.nombre, u.apellido, u.correo, u.estado as estado_usuario, 
                       c.nombre as carrera_nombre, c.codigo as carrera_codigo
                FROM estudiantes e
                JOIN usuario u ON e.id_usuario = u.id_usuario
                LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
                ORDER BY u.apellido, u.nombre";
        
        $result = $this->conn->query($sql);
        return $result;
    }
    
    // Obtener un estudiante por ID
    public function getById($id) {
        $sql = "SELECT e.*, u.nombre, u.apellido, u.correo, u.estado as estado_usuario, 
                       c.nombre as carrera_nombre, c.codigo as carrera_codigo
                FROM estudiantes e
                JOIN usuario u ON e.id_usuario = u.id_usuario
                LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
                WHERE e.id_estudiante = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Crear un nuevo estudiante (asume que el usuario ya fue creado)
    public function create($data) {
        $sql = "INSERT INTO estudiantes (id_usuario, cedula, telefono, fecha_nacimiento, direccion, id_carrera, año_carrera, semestre_actual, fecha_ingreso) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssiiis", 
            $data['id_usuario'], 
            $data['cedula'], 
            $data['telefono'], 
            $data['fecha_nacimiento'], 
            $data['direccion'], 
            $data['id_carrera'], 
            $data['año_carrera'], 
            $data['semestre_actual'], 
            $data['fecha_ingreso']
        );
        
        return $stmt->execute();
    }
    
    // Actualizar un estudiante
    public function update($id, $data) {
        // Aquí iría la lógica de actualización, similar a create pero con UPDATE
    }
    
    // Eliminar un estudiante por ID
    public function delete($id) {
        $sql = "DELETE FROM estudiantes WHERE id_estudiante = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // Verificar si un estudiante tiene matrículas
    public function hasMatriculas($id_estudiante) {
        $sql = "SELECT COUNT(*) as total FROM matriculas WHERE id_estudiante = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_estudiante);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] > 0;
    }
    
    // Obtener carreras para dropdown
    public function getCarreras() {
        $sql = "SELECT id_carrera, nombre, codigo FROM carreras WHERE estado = 'activa' ORDER BY nombre";
        $result = $this->conn->query($sql);
        return $result;
    }
    
    // Obtener estadísticas de estudiantes
    public function getStats() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN u.estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN u.estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
                AVG(e.año_carrera) as año_promedio,
                AVG(e.semestre_actual) as semestre_promedio
                FROM estudiantes e
                JOIN usuario u ON e.id_usuario = u.id_usuario";
        
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }
}