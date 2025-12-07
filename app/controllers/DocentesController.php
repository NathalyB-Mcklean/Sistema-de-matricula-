<?php
// app/controllers/DocenteController.php
session_start();
require_once '../models/DocenteModel.php';
require_once '../models/UsuarioModel.php';
require_once '../utils/validaciones.php';

class DocenteController {
    private $model;
    private $usuarioModel;
    
    public function __construct() {
        $this->model = new DocenteModel();
        $this->usuarioModel = new UsuarioModel();
    }
    
    // CREAR DOCENTE
    public function store() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Validaciones
                validarNombre($_POST['nombre']);
                validarApellido($_POST['apellido']);
                validarCorreo($_POST['correo']);
                validarPassword($_POST['password']);
                validarCoincidenciaPassword($_POST['password'], $_POST['confirm_password']);
                validarCedula($_POST['cedula']);
                validarTitulo($_POST['titulo_academico']);
                validarEspecialidad($_POST['especialidad']);
                validarNumero($_POST['años_experiencia'], 0, 50);
                
                // Crear usuario
                $usuarioData = [
                    'nombre' => limpiarEntrada($_POST['nombre']),
                    'apellido' => limpiarEntrada($_POST['apellido']),
                    'correo' => limpiarEntrada($_POST['correo']),
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    'rol' => 'docente',
                    'estado' => 'activo'
                ];
                
                $id_usuario = $this->usuarioModel->create($usuarioData);
                
                // Crear docente
                $docenteData = [
                    'id_usuario' => $id_usuario,
                    'cedula' => limpiarEntrada($_POST['cedula']),
                    'telefono' => limpiarEntrada($_POST['telefono'] ?? ''),
                    'titulo_academico' => limpiarEntrada($_POST['titulo_academico']),
                    'especialidad' => limpiarEntrada($_POST['especialidad']),
                    'años_experiencia' => intval($_POST['años_experiencia']),
                    'estado' => 'activo'
                ];
                
                if ($this->model->create($docenteData)) {
                    $_SESSION['success'] = 'Docente creado exitosamente';
                    header('Location: /admin/docentes');
                } else {
                    $this->usuarioModel->delete($id_usuario);
                    throw new Exception('Error al crear el docente');
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /admin/docentes/create');
            }
            exit();
        }
    }
    
    // ELIMINAR DOCENTE (CON RESTRICCIONES)
    public function destroy($id) {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        try {
            $docente = $this->model->getById($id);
            
            if (!$docente) {
                throw new Exception('Docente no encontrado');
            }
            
            // Verificar si tiene materias asignadas
            if ($this->model->hasMaterias($id)) {
                // Solo marcar como inactivo
                $this->model->updateEstado($id, 'inactivo');
                $this->usuarioModel->updateEstado($docente['id_usuario'], 'inactivo');
                $_SESSION['success'] = 'Docente marcado como inactivo (tiene materias asignadas)';
            } else {
                // Eliminar completamente
                $this->model->delete($id);
                $this->usuarioModel->delete($docente['id_usuario']);
                $_SESSION['success'] = 'Docente eliminado exitosamente';
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /admin/docentes');
        exit();
    }
}