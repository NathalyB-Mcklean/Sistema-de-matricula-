<?php
// app/models/EstudianteModel.php

class EstudianteModel {
    private $conn;
    
    public function __construct() {
        require_once '../../config/conexion.php';
        $this->conn = Conexion::getConexion();
    }
    
    // OBTENER TODOS LOS ESTUDIANTES CON FILTROS
    public function getAll($filtros = []) {
        $sql = "SELECT e.*, u.nombre, u.apellido, u.correo, u.estado as estado_usuario, 
                       c.nombre as carrera_nombre, c.codigo as carrera_codigo
                FROM estudiantes e
                JOIN usuario u ON e.id_usuario = u.id_usuario
                LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
                WHERE 1=1";
        
        $params = [];
        $types = '';
        
        // Aplicar filtros
        if (!empty($filtros['estado'])) {
            $sql .= " AND u.estado = ?";
            $params[] = $filtros['estado'];
            $types .= 's';
        }
        
        if (!empty($filtros['carrera'])) {
            $sql .= " AND c.id_carrera = ?";
            $params[] = $filtros['carrera'];
            $types .= 'i';
        }
        
        if (!empty($filtros['año'])) {
            $sql .= " AND e.año_carrera = ?";
            $params[] = $filtros['año'];
            $types .= 'i';
        }
        
        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.correo LIKE ? OR e.cedula LIKE ?)";
            $search = "%{$filtros['busqueda']}%";
            $params = array_merge($params, [$search, $search, $search, $search]);
            $types .= 'ssss';
        }
        
        $sql .= " ORDER BY u.apellido, u.nombre";
        
        if ($params) {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result();
        } else {
            return $this->conn->query($sql);
        }
    }
    
    // OBTENER ESTUDIANTE POR ID
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
    
    // CREAR ESTUDIANTE (CON USUARIO)
    public function create($data) {
        // Iniciar transacción
        $this->conn->begin_transaction();
        
        try {
            // 1. Crear usuario
            $sql1 = "INSERT INTO usuario (nombre, apellido, correo, password, rol, estado) 
                     VALUES (?, ?, ?, ?, 'estudiante', 'activo')";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->bind_param("ssss", 
                $data['nombre'], 
                $data['apellido'], 
                $data['correo'], 
                $data['password']
            );
            
            if (!$stmt1->execute()) {
                throw new Exception("Error al crear usuario");
            }
            
            $id_usuario = $this->conn->insert_id;
            
            // 2. Crear estudiante
            $sql2 = "INSERT INTO estudiantes 
                    (id_usuario, cedula, telefono, fecha_nacimiento, direccion, 
                     id_carrera, año_carrera, semestre_actual, fecha_ingreso) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->bind_param("issssiiis", 
                $id_usuario,
                $data['cedula'],
                $data['telefono'],
                $data['fecha_nacimiento'],
                $data['direccion'],
                $data['id_carrera'],
                $data['año_carrera'],
                $data['semestre_actual'],
                $data['fecha_ingreso']
            );
            
            if (!$stmt2->execute()) {
                throw new Exception("Error al crear estudiante");
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    // ACTUALIZAR ESTUDIANTE
    public function update($id, $data) {
        $this->conn->begin_transaction();
        
        try {
            // 1. Obtener id_usuario del estudiante
            $sql = "SELECT id_usuario FROM estudiantes WHERE id_estudiante = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $id_usuario = $result['id_usuario'];
            
            // 2. Actualizar usuario
            $sql1 = "UPDATE usuario SET 
                    nombre = ?, apellido = ?, correo = ?, estado = ?";
            
            // Si hay nueva contraseña
            if (isset($data['password'])) {
                $sql1 .= ", password = ? WHERE id_usuario = ?";
                $stmt1 = $this->conn->prepare($sql1);
                $stmt1->bind_param("sssssi",
                    $data['nombre'],
                    $data['apellido'],
                    $data['correo'],
                    $data['estado'],
                    $data['password'],
                    $id_usuario
                );
            } else {
                $sql1 .= " WHERE id_usuario = ?";
                $stmt1 = $this->conn->prepare($sql1);
                $stmt1->bind_param("ssssi",
                    $data['nombre'],
                    $data['apellido'],
                    $data['correo'],
                    $data['estado'],
                    $id_usuario
                );
            }
            
            if (!$stmt1->execute()) {
                throw new Exception("Error al actualizar usuario");
            }
            
            // 3. Actualizar estudiante
            $sql2 = "UPDATE estudiantes SET 
                    cedula = ?, telefono = ?, fecha_nacimiento = ?, direccion = ?,
                    id_carrera = ?, año_carrera = ?, semestre_actual = ?
                    WHERE id_estudiante = ?";
            
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->bind_param("ssssiiii",
                $data['cedula'],
                $data['telefono'],
                $data['fecha_nacimiento'],
                $data['direccion'],
                $data['id_carrera'],
                $data['año_carrera'],
                $data['semestre_actual'],
                $id
            );
            
            if (!$stmt2->execute()) {
                throw new Exception("Error al actualizar estudiante");
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    // ELIMINAR ESTUDIANTE
    public function delete($id) {
        $this->conn->begin_transaction();
        
        try {
            // Obtener id_usuario
            $sql = "SELECT id_usuario FROM estudiantes WHERE id_estudiante = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $id_usuario = $result['id_usuario'];
            
            // Eliminar estudiante
            $sql1 = "DELETE FROM estudiantes WHERE id_estudiante = ?";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->bind_param("i", $id);
            
            if (!$stmt1->execute()) {
                throw new Exception("Error al eliminar estudiante");
            }
            
            // Eliminar usuario
            $sql2 = "DELETE FROM usuario WHERE id_usuario = ?";
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->bind_param("i", $id_usuario);
            
            if (!$stmt2->execute()) {
                throw new Exception("Error al eliminar usuario");
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    // VERIFICAR SI TIENE MATRÍCULAS
    public function hasMatriculas($id_estudiante) {
        $sql = "SELECT COUNT(*) as total FROM matriculas WHERE id_estudiante = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_estudiante);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] > 0;
    }
    
    // ACTUALIZAR ESTADO DEL USUARIO
    public function updateEstado($id_usuario, $estado) {
        $sql = "UPDATE usuario SET estado = ? WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $estado, $id_usuario);
        return $stmt->execute();
    }
    
    // VERIFICAR SI EL CORREO EXISTE
    public function existeCorreo($correo) {
        $sql = "SELECT COUNT(*) as total FROM usuario WHERE correo = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] > 0;
    }
    
    // VERIFICAR SI LA CÉDULA EXISTE
    public function existeCedula($cedula) {
        $sql = "SELECT COUNT(*) as total FROM estudiantes WHERE cedula = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $cedula);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'] > 0;
    }
    
    // OBTENER CARRERAS
    public function getCarreras() {
        $sql = "SELECT id_carrera, nombre, codigo FROM carreras WHERE estado = 'activa' ORDER BY nombre";
        return $this->conn->query($sql);
    }
    
    // OBTENER ESTADÍSTICAS
    public function getStats() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN u.estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN u.estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
                COALESCE(AVG(e.año_carrera), 0) as año_promedio,
                COALESCE(AVG(e.semestre_actual), 0) as semestre_promedio
                FROM estudiantes e
                JOIN usuario u ON e.id_usuario = u.id_usuario";
        
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }
}
?>