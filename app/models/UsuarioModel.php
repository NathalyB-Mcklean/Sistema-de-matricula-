<?php
namespace App\Models;

use Config\Database;

class UsuarioModel {
    private $db;
    private $table = 'usuario';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($correo, $password) {
        $sql = "SELECT * FROM usuario WHERE correo = ? AND estado = 'activo'";
        $usuario = $this->db->fetchOne($sql, [$correo]);
        
        if ($usuario && password_verify($password, $usuario['password'])) {
            return $usuario;
        }
        return false;
    }
    
    public function crearEstudiante($datos) {
        // Hash password
        $passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);
        
        $this->db->beginTransaction();
        try {
            // Insertar usuario
            $sqlUsuario = "INSERT INTO usuario (nombre, apellido, correo, password, rol) 
                          VALUES (?, ?, ?, ?, 'estudiante')";
            $userId = $this->db->insert($sqlUsuario, [
                $datos['nombre'],
                $datos['apellido'],
                $datos['correo'],
                $passwordHash
            ]);
            
            // Insertar estudiante
            $sqlEstudiante = "INSERT INTO estudiantes (cedula, id_usuario, telefono) 
                             VALUES (?, ?, ?)";
            $this->db->query($sqlEstudiante, [
                $datos['cedula'],
                $userId,
                $datos['telefono'] ?? null
            ]);
            
            $this->db->commit();
            return $userId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function listar($rol = null) {
        $sql = "SELECT * FROM usuario WHERE 1=1";
        $params = [];
        
        if ($rol) {
            $sql .= " AND rol = ?";
            $params[] = $rol;
        }
        
        $sql .= " ORDER BY id_usuario DESC";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function buscarPorId($id) {
        $sql = "SELECT * FROM usuario WHERE id_usuario = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function actualizar($id, $datos) {
        $sql = "UPDATE usuario SET nombre = ?, apellido = ?, estado = ? 
                WHERE id_usuario = ?";
        return $this->db->query($sql, [
            $datos['nombre'],
            $datos['apellido'],
            $datos['estado'],
            $id
        ]);
    }
    
    public function eliminar($id) {
        // Cambiar estado en lugar de eliminar
        $sql = "UPDATE usuario SET estado = 'inactivo' WHERE id_usuario = ?";
        return $this->db->query($sql, [$id]);
    }
}
?>