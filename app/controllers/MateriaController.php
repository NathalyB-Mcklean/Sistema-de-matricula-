<?php
// app/controllers/MateriaController.php
session_start();
require_once '../models/MateriaModel.php';
require_once '../utils/validaciones.php';

class MateriaController {
    private $model;
    
    public function __construct() {
        $this->model = new MateriaModel();
    }
    
    // LISTAR MATERIAS
    public function index() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        $id_carrera = $_GET['id_carrera'] ?? null;
        $materias = $this->model->getAll($id_carrera);
        $carrera = $id_carrera ? $this->model->getCarreraById($id_carrera) : null;
        
        include '../views/admin/materias/listar.php';
    }
    
    // MOSTRAR FORMULARIO DE CREACIÓN
    public function create() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        $id_carrera = $_GET['id_carrera'] ?? null;
        $carreras = $this->model->getCarreras();
        $docentes = $this->model->getDocentes();
        
        include '../views/admin/materias/crear.php';
    }
    
    // GUARDAR NUEVA MATERIA
    public function store() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                // Validaciones
                validarNoVacio($_POST['codigo'], 'Código');
                validarNoVacio($_POST['nombre'], 'Nombre');
                validarCodigoMateria($_POST['codigo']);
                validarNombreMateria($_POST['nombre']);
                validarNumero($_POST['costo'], 0, 10000);
                
                $data = [
                    'codigo' => limpiarEntrada($_POST['codigo']),
                    'nombre' => limpiarEntrada($_POST['nombre']),
                    'descripcion' => limpiarEntrada($_POST['descripcion'] ?? ''),
                    'costo' => floatval($_POST['costo']),
                    'id_carrera' => intval($_POST['id_carrera']) ?: null,
                    'id_docente' => intval($_POST['id_docente']) ?: null
                ];
                
                if ($this->model->create($data)) {
                    $_SESSION['success'] = 'Materia creada exitosamente';
                    header('Location: /admin/materias' . ($data['id_carrera'] ? '?id_carrera=' . $data['id_carrera'] : ''));
                } else {
                    throw new Exception('Error al crear la materia');
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: /admin/materias/create' . (isset($_POST['id_carrera']) ? '?id_carrera=' . $_POST['id_carrera'] : ''));
            }
            exit();
        }
    }
    
    // MOSTRAR FORMULARIO DE EDICIÓN
    public function edit($id) {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        $materia = $this->model->getById($id);
        if (!$materia) {
            $_SESSION['error'] = 'Materia no encontrada';
            header('Location: /admin/materias');
            exit();
        }
        
        $carreras = $this->model->getCarreras();
        $docentes = $this->model->getDocentes();
        
        include '../views/admin/materias/editar.php';
    }
    
    // ACTUALIZAR MATERIA
    public function update($id) {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $data = [
                    'codigo' => limpiarEntrada($_POST['codigo']),
                    'nombre' => limpiarEntrada($_POST['nombre']),
                    'descripcion' => limpiarEntrada($_POST['descripcion'] ?? ''),
                    'costo' => floatval($_POST['costo']),
                    'id_carrera' => intval($_POST['id_carrera']) ?: null,
                    'id_docente' => intval($_POST['id_docente']) ?: null
                ];
                
                if ($this->model->update($id, $data)) {
                    $_SESSION['success'] = 'Materia actualizada exitosamente';
                    header('Location: /admin/materias' . ($data['id_carrera'] ? '?id_carrera=' . $data['id_carrera'] : ''));
                } else {
                    throw new Exception('Error al actualizar la materia');
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header("Location: /admin/materias/edit/$id");
            }
            exit();
        }
    }
    
    // ELIMINAR MATERIA
    public function destroy($id) {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /auth/login');
            exit();
        }
        
        try {
            if ($this->model->delete($id)) {
                $_SESSION['success'] = 'Materia eliminada exitosamente';
            } else {
                throw new Exception('Error al eliminar la materia');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /admin/materias' . (isset($_GET['id_carrera']) ? '?id_carrera=' . $_GET['id_carrera'] : ''));
        exit();
    }
}