<?php
// app/models/PeriodoModel.php

class PeriodoModel {
    private $conn;
    
    public function __construct() {
        require_once '../../config/conexion.php';
        $this->conn = Conexion::getConexion();
    }
    
    // Obtener todos los períodos
    public function getAll() {
        $sql = "SELECT * FROM periodos_academicos ORDER BY año DESC, semestre DESC";
        return $this->conn->query($sql);
    }
    
    // Activar un período (desactivar todos primero)
    public function activate($id) {
        // Primero desactivar todos
        $sql1 = "UPDATE periodos_academicos SET estado = 'inactivo'";
        $this->conn->query($sql1);
        
        // Luego activar el seleccionado
        $sql2 = "UPDATE periodos_academicos SET estado = 'activo' WHERE id_periodo = ?";
        $stmt = $this->conn->prepare($sql2);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // Desactivar todos los períodos
    public function deactivateAll() {
        $sql = "UPDATE periodos_academicos SET estado = 'inactivo'";
        return $this->conn->query($sql);
    }
    
    // Eliminar período
    public function delete($id) {
        $sql = "DELETE FROM periodos_academicos WHERE id_periodo = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // Verificar si tiene matrículas
    public function hasMatriculas($id_periodo) {
        $sql = "SELECT COUNT(*) as total FROM matriculas WHERE id_periodo = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_periodo);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] > 0;
    }
    
    // Actualizar estado
    public function updateEstado($id, $estado) {
        $sql = "UPDATE periodos_academicos SET estado = ? WHERE id_periodo = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $estado, $id);
        return $stmt->execute();
    }
}