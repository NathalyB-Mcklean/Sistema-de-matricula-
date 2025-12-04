<?php
// models/CarreraModel.php
require_once __DIR__ . '/../config/conexion.php';


class CarreraModel {
    private $conn;
    
    public function __construct() {
        $db = Database::getInstance();
        $this->conn = $db->getConnection();
    }
    
    public function listarTodas() {
        $sql = "SELECT * FROM carreras ORDER BY nombre";
        $resultado = $this->conn->query($sql);
        
        $carreras = [];
        while($fila = $resultado->fetch_assoc()) {
            $carreras[] = $fila;
        }
        
        return $carreras;
    }
    
    public function crear($nombre, $descripcion = '') {
        $sql = "INSERT INTO carreras (nombre, descripcion) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $nombre, $descripcion);
        
        return $stmt->execute();
    }
    
    public function obtenerPorId($id) {
        $sql = "SELECT * FROM carreras WHERE id_carrera = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        return $resultado->fetch_assoc();
    }
}
?>