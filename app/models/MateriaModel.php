<?php
// app/models/MateriaModel.php

class MateriaModel {
    private $conn;
    
    public function __construct() {
        // Asume que ya tienes una clase de conexión
        require_once '../../config/conexion.php';
        $this->conn = Conexion::getConexion();
    }
    
    // Obtener todas las materias, opcionalmente filtradas por carrera
    public function getAll($id_carrera = null) {
        $sql = "SELECT m.*, c.nombre as carrera_nombre, 
                       CONCAT(d.nombre, ' ', d.apellido) as docente_nombre
                FROM materias m
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                LEFT JOIN docentes d ON m.id_docente = d.id_docente";
        
        if ($id_carrera) {
            $sql .= " WHERE m.id_carrera = ?";
        }
        
        $sql .= " ORDER BY m.nombre ASC";
        
        $stmt = $this->conn->prepare($sql);
        if ($id_carrera) {
            $stmt->bind_param("i", $id_carrera);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Obtener una materia por ID
    public function getById($id) {
        $sql = "SELECT m.*, c.nombre as carrera_nombre, 
                       CONCAT(d.nombre, ' ', d.apellido) as docente_nombre
                FROM materias m
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                LEFT JOIN docentes d ON m.id_docente = d.id_docente
                WHERE m.id_materia = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Crear una nueva materia
    public function create($data) {
        $sql = "INSERT INTO materias (codigo, nombre, descripcion, costo, id_carrera, id_docente) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssdii", 
            $data['codigo'], 
            $data['nombre'], 
            $data['descripcion'], 
            $data['costo'], 
            $data['id_carrera'], 
            $data['id_docente']
        );
        
        return $stmt->execute();
    }
    
    // Actualizar una materia
    public function update($id, $data) {
        $sql = "UPDATE materias SET 
                codigo = ?, 
                nombre = ?, 
                descripcion = ?, 
                costo = ?, 
                id_carrera = ?, 
                id_docente = ? 
                WHERE id_materia = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssdiii", 
            $data['codigo'], 
            $data['nombre'], 
            $data['descripcion'], 
            $data['costo'], 
            $data['id_carrera'], 
            $data['id_docente'], 
            $id
        );
        
        return $stmt->execute();
    }
    
    // Eliminar una materia
    public function delete($id) {
        $sql = "DELETE FROM materias WHERE id_materia = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // Obtener carreras para dropdown
    public function getCarreras() {
        $sql = "SELECT id_carrera, nombre, codigo FROM carreras WHERE estado = 'activa' ORDER BY nombre";
        $result = $this->conn->query($sql);
        return $result;
    }
    
    // Obtener docentes para dropdown
    public function getDocentes() {
        $sql = "SELECT id_docente, nombre, apellido FROM docentes WHERE estado = 'activo' ORDER BY apellido, nombre";
        $result = $this->conn->query($sql);
        return $result;
    }
    
    // Obtener información de una carrera por ID
    public function getCarreraById($id_carrera) {
        $sql = "SELECT * FROM carreras WHERE id_carrera = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_carrera);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}