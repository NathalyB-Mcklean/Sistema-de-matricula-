<?php
// app/models/UsuarioModel.php

class UsuarioModel {
    private $conn;
    
    public function __construct() {
        require_once '../../config/conexion.php';
        $this->conn = Conexion::getConexion();
    }
    
    // Crear un nuevo usuario
    public function create($data) {
        $sql = "INSERT INTO usuario (nombre, apellido, correo, password, rol, estado) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssss", 
            $data['nombre'], 
            $data['apellido'], 
            $data['correo'], 
            $data['password'], 
            $data['rol'], 
            $data['estado']
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }
    
    // Eliminar un usuario por ID
    public function delete($id) {
        $sql = "DELETE FROM usuario WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // Actualizar estado de un usuario
    public function updateEstado($id, $estado) {
        $sql = "UPDATE usuario SET estado = ? WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $estado, $id);
        return $stmt->execute();
    }
    
    // Autenticar usuario
    public function authenticate($correo, $password) {
        $sql = "SELECT id_usuario, nombre, apellido, correo, password, rol, estado 
                FROM usuario WHERE correo = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        
        if ($usuario && password_verify($password, $usuario['password'])) {
            return $usuario;
        }
        return false;
    }
}