<?php
// app/controllers/EstudianteController.php

class EstudianteController {
    private $model;
    
    public function __construct() {
        require_once '../models/EstudianteModel.php';
        require_once '../models/UsuarioModel.php';
        $this->model = new EstudianteModel();
    }
    
    // LISTAR ESTUDIANTES
    public function index() {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        // Obtener parámetros de búsqueda
        $filtros = [
            'estado' => $_GET['estado'] ?? '',
            'carrera' => $_GET['carrera'] ?? '',
            'año' => $_GET['año'] ?? '',
            'busqueda' => $_GET['busqueda'] ?? ''
        ];
        
        // Obtener datos
        $estudiantes = $this->model->getAll($filtros);
        $carreras = $this->model->getCarreras();
        $stats = $this->model->getStats();
        
        // Incluir vista
        include '../views/admin/estudiantes/index.php';
    }
    
    // MOSTRAR FORMULARIO DE CREACIÓN
    public function create() {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        $carreras = $this->model->getCarreras();
        include '../views/admin/estudiantes/create.php';
    }
    
    // GUARDAR NUEVO ESTUDIANTE
    public function store() {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Validaciones básicas
                if (empty($_POST['nombre']) || empty($_POST['apellido']) || 
                    empty($_POST['correo']) || empty($_POST['cedula'])) {
                    throw new Exception("Todos los campos obligatorios deben ser completados");
                }
                
                // Validar correo único
                if ($this->model->existeCorreo($_POST['correo'])) {
                    throw new Exception("El correo electrónico ya está registrado");
                }
                
                // Validar cédula única
                if ($this->model->existeCedula($_POST['cedula'])) {
                    throw new Exception("La cédula ya está registrada");
                }
                
                $data = [
                    'nombre' => trim($_POST['nombre']),
                    'apellido' => trim($_POST['apellido']),
                    'correo' => trim($_POST['correo']),
                    'password' => password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT), // Contraseña por defecto
                    'cedula' => trim($_POST['cedula']),
                    'telefono' => trim($_POST['telefono'] ?? ''),
                    'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
                    'direccion' => trim($_POST['direccion'] ?? ''),
                    'id_carrera' => intval($_POST['id_carrera'] ?? 0) ?: null,
                    'año_carrera' => intval($_POST['año_carrera'] ?? 1),
                    'semestre_actual' => intval($_POST['semestre_actual'] ?? 1),
                    'fecha_ingreso' => date('Y-m-d')
                ];
                
                if ($this->model->create($data)) {
                    $_SESSION['success'] = 'Estudiante creado exitosamente';
                    header('Location: /admin/estudiantes');
                } else {
                    throw new Exception('Error al crear el estudiante');
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /admin/estudiantes/create');
            }
            exit();
        }
    }
    
    // MOSTRAR FORMULARIO DE EDICIÓN
    public function edit($id) {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        $estudiante = $this->model->getById($id);
        if (!$estudiante) {
            $_SESSION['error'] = 'Estudiante no encontrado';
            header('Location: /admin/estudiantes');
            exit();
        }
        
        $carreras = $this->model->getCarreras();
        include '../views/admin/estudiantes/edit.php';
    }
    
    // ACTUALIZAR ESTUDIANTE
    public function update($id) {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $estudiante = $this->model->getById($id);
                if (!$estudiante) {
                    throw new Exception('Estudiante no encontrado');
                }
                
                $data = [
                    'nombre' => trim($_POST['nombre']),
                    'apellido' => trim($_POST['apellido']),
                    'correo' => trim($_POST['correo']),
                    'cedula' => trim($_POST['cedula']),
                    'telefono' => trim($_POST['telefono'] ?? ''),
                    'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
                    'direccion' => trim($_POST['direccion'] ?? ''),
                    'id_carrera' => intval($_POST['id_carrera'] ?? 0) ?: null,
                    'año_carrera' => intval($_POST['año_carrera'] ?? 1),
                    'semestre_actual' => intval($_POST['semestre_actual'] ?? 1),
                    'estado' => $_POST['estado'] ?? 'activo'
                ];
                
                // Si se cambió la contraseña
                if (!empty($_POST['password'])) {
                    $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                
                if ($this->model->update($id, $data)) {
                    $_SESSION['success'] = 'Estudiante actualizado exitosamente';
                    header('Location: /admin/estudiantes');
                } else {
                    throw new Exception('Error al actualizar el estudiante');
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header("Location: /admin/estudiantes/edit?id=$id");
            }
            exit();
        }
    }
    
    // ELIMINAR ESTUDIANTE
    public function destroy($id) {
        session_start();
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        try {
            $estudiante = $this->model->getById($id);
            if (!$estudiante) {
                throw new Exception('Estudiante no encontrado');
            }
            
            if ($this->model->hasMatriculas($id)) {
                // Solo marcar como inactivo
                if ($this->model->updateEstado($estudiante['id_usuario'], 'inactivo')) {
                    $_SESSION['success'] = 'Estudiante marcado como inactivo (tiene matrículas)';
                }
            } else {
                // Eliminar completamente
                if ($this->model->delete($id)) {
                    $_SESSION['success'] = 'Estudiante eliminado exitosamente';
                }
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /admin/estudiantes');
        exit();
    }
}
?>