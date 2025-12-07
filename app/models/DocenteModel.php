<?php
// app/models/DocenteModel.php

class DocenteModel {
    private $conn;
    
    public function __construct() {
        require_once '../../config/conexion.php';
        $this->conn = Conexion::getConexion();
    }
    
    // Obtener todos los docentes
    public function getAll() {
        $sql = "SELECT d.*, u.nombre, u.apellido, u.correo, u.estado as estado_usuario
                FROM docentes d
                JOIN usuario u ON d.id_usuario = u.id_usuario
                ORDER BY u.apellido, u.nombre";
        
        return $this->conn->query($sql);
    }
    
    // Obtener docente por ID
    public function getById($id) {
        $sql = "SELECT d.*, u.nombre, u.apellido, u.correo, u.estado as estado_usuario
                FROM docentes d
                JOIN usuario u ON d.id_usuario = u.id_usuario
                WHERE d.id_docente = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Crear docente
    public function create($data) {
        $sql = "INSERT INTO docentes (id_usuario, cedula, telefono, titulo_academico, especialidad, años_experiencia, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issssis", 
            $data['id_usuario'], 
            $data['cedula'], 
            $data['telefono'], 
            $data['titulo_academico'], 
            $data['especialidad'], 
            $data['años_experiencia'], 
            $data['estado']
        );
        
        return $stmt->execute();
    }
    
    // Actualizar estado
    public function updateEstado($id, $estado) {
        $sql = "UPDATE docentes SET estado = ? WHERE id_docente = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $estado, $id);
        return $stmt->execute();
    }
    
    // Eliminar docente
    public function delete($id) {
        $sql = "DELETE FROM docentes WHERE id_docente = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // Verificar si tiene materias asignadas
    public function hasMaterias($id_docente) {
        $sql = "SELECT COUNT(*) as total FROM materias WHERE id_docente = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_docente);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] > 0;
    }
}